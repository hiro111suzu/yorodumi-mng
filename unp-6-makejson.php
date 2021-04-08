<?php
require_once( "unp-common.php" );

//. init
define( 'NG_TYPE', array_fill_keys([
	'interaction' ,
	'alternative products' ,
	'caution' ,
], true ));
define( 'OK_LINK', array_fill_keys([

	'Pfam' ,
	'PRINTS' ,
//	'SMART' ,
	'SUPFAM' ,
	'HAMAP' ,
	'PROSITE' ,
	'GeneWiki' ,
	'KEGG' ,
	'BioGrid' ,
	'ComplexPortal' ,
	'IntAct' ,
	'MINT',
	'STRING' ,
	'EuPathDB' ,
	'SUPFAM' ,
	'PRINTS' ,
], true ));

define( 'NG_FEATURE', array_fill_keys([
//	'chain' ,
	'helix' ,
	'strand' ,
	'turn' ,

], true ));

define( 'PFAM_DESC'		, _json_load( FN_PFAM_JSON ) );
define( 'INTERPRO_DESC'	, _json_load( FN_INTERPRO_JSON ) );
define( 'SMART_DESC'	, _json_load( FN_SMART_JSON ) );
define( 'PRORULE_DESC'	, _json_load( FN_PRORULE_JSON ) );


//. main
$flg_changed = false;
foreach ( _idloop( 'unp_xml', 'json作成' ) as $fn_in ) {
	if ( _count( 1000, 0 ) ) _pause();
	$unp_id = _fn2id( $fn_in );
	$fn_out = _fn( 'unp_json', $unp_id );
	if ( FLG_REDO )
		_del( $fn_out );
	if ( _newer( $fn_out, $fn_in ) ) continue;

	if ( filesize( $fn_in ) == 0 ) continue;
	$xml = simplexml_load_file( $fn_in )->entry;

	$links = [];
	$ref = [];
	$cmnt = [];
	$subc_loc = [];
	$go_info = [];
	$intp = [];
	$ec = [];
	$name2dbid = [];
	$smart = [];
	$other_dbid = [];

	//.. name
	$ent_name =
		(string)$xml->protein->recommendedName->fullName ?:
		(string)$xml->protein->submittedName->fullName 
	;
	
	//.. org
	$s = $o = '';
	foreach ( (object)$xml->organism->name as $c ) {
		$o = (string)$c;
		if ( (string)$c['type'] == 'scientific' )
			$s = (string)$c;
	}
	$org = $s ?: $o;

	//.. taxid
	$taxid = [];
	foreach ( (object)$xml->organism->dbReference as $c ) {
		if ( (string)$c['type'] != 'NCBI Taxonomy' ) continue;
		$taxid[] = (string)$c['id'];
	}
	sort( $taxid );
	$taxid = $taxid[0];

	//.. comment
	foreach ( (object)$xml->comment as $c ) {
		if ( ! is_object( $c ) ) continue;
		$type = (string)$c[ 'type' ];
		$text = (string)$c->text;
		if ( $type == 'subcellular location' ) {
			//... subcellular location
			foreach ( (object)$c->subcellularLocation as $c2 ) {
				foreach ( (object)$c2->location as $num => $val ) {
					if ( $num == '@attributes' ) continue;
					$subc_loc[] = _getval_with_evd( $val );
				}
			}
		} else if ( $type == 'online information' ) {
			//... online information
			$u = (string)$c->link[ 'uri' ];
			if ( $u ) 
				$links[] = [ implode( ' - ', [ $text, (string)$c[ 'name' ] ] ), $u ];
		} else if ( $type == 'disease' ) {
			//... disease
			$evd = explode( ' ', (string)$c['evidence'] );
			foreach ( $c->disease as $c2 ) {
				$s = implode( ': ', [ (string)$c2->name, (string)$c2->description ] );
				if ( $s )
					$cmnt[ 'disease' ][] = $evd ? [ $s, $evd ] : $s;
			}
			if ( $text )
				$cmnt[ 'disease' ][] = $evd ? [ $text, $evd ] : $text;

		} else if ( $type == 'catalytic activity' ) {
			//... catalytic activity
			foreach ( $c->reaction as $c2 ) {
				$dbref = [];
				foreach ( (object)$c2->dbReference as $c3 ) {
					$dbref[] = _instr( ':', $c3['id'] )
						? (string)$c3['id']
						: $c3['type']. ':'. $c3['id']
					;
				}
	 			$evd = explode( ' ', (string)$c2['evidence'] );
				$cmnt[ 'catalytic activity' ][] = [
					(string)$c2->text,
					$evd ,
					$dbref
				];
			}
		} else if ( ! NG_TYPE[ $type ] ) {
			//... others
			if ( $text ) {
				$cmnt[ $type ][] = _getval_with_evd( $c->text );
			}
		}
	}
	//.. dbReference
	foreach ( (object)$xml->dbReference as $c ) {
		if ( ! is_object( $c ) ) continue;
		$type = (string)$c[ 'type' ];
		$id = (string)$c[ 'id' ];

//		_m( "$type: $id" );
		$ename = _get_value( $c, 'entry name' );
		if ( $ename )
			$name2dbid[ $ename ][] = [ $type, $id ];

		//... go
		if ( $type == 'GO' ) {
			$go_term = _get_value( $c, 'term' );
			list( $type, $text ) = explode( ':', $go_term );
			$go_info[ $type ][] = [ strtr( $id, [ 'GO:' => '' ] ), $text ];

		//... interpro
		} else if ( $type == 'InterPro' ) {
			list( $type, $name ) =
				INTERPRO_DESC[ (integer)_numonly( $id ) ] ?:
				[ '-', $name ]
			;
			$intp[ $type ][] = [ $id, $name ];

		//... EC
		} else if ( $type == 'EC' ) {
			$ec[] = $id;

		//... Reactome
		} else if ( $type == 'Reactome' ) {
			$ref[ 'Reactome' ][] = [ $id, _get_value( $c, 'pathway name' ) ];
			$other_dbid['Reactome'][] = [ $id, _get_value( $c, 'pathway name' ) ];
//			_pause( print_r( $c, true ) );
//			$Reactome = true;

		//... smart
		} else if ( $type == 'SMART' ) {
			$other_dbid['SMART'][] = [ $id, SMART_DESC[ $id ][0] ];

			//... others
		} else if ( OK_LINK[ $type ] ) {
			$other_dbid[ $type ][] = [ $id, _imp(
				$ename,
				_ifnn( _get_value( $c, 'interactions' ), '\1 interactions' )
			)];
		}
	}
/*
	if ( ! $ref ) continue;
	_m( "$unp_id:" );
	_pause( json_encode( $ref,JSON_PRETTY_PRINT ) );
*/

	//.. ec number
	foreach ( (object)$xml->protein->recommendedName->ecNumber as $n )
		$ec[] = $n;

	//.. evicence
	$evd = [];
	$dic = [];
	$evd_name = [];
	foreach ( (object)$xml->evidence as $c ) {
		$type = $c->source ? (string)$c->source->dbReference[ 'type' ] : '';
		$id   = $c->source ? (string)$c->source->dbReference[ 'id' ] : '';
		$evd[ (string)$c[ 'key' ] ] = array_values( array_filter([
			(string)$c[ 'type' ],
			$type ,
			$id ,
		]) );

		if ( $type == 'PROSITE-ProRule' ) {
			$dic[ $id ] = PRORULE_DESC[ $id ]; 
			$evd_name[ (string)$c[ 'key' ] ] =  PRORULE_DESC[ $id ];
		}
	}

	//.. feature
	$fet =[];
	foreach ( (object)$xml->feature as $c ) {
		$type = (string)$c[ 'type' ];
		if ( ! $type || NG_FEATURE[ $type ] ) continue;

		$name_from_evd = [];
		foreach ( explode( ' ', (string)$c[ 'evidence' ] ) as $i ) {
			$name_from_evd[] = $evd_name[ $i ];
		}

		$fet[] = array_filter([
			'type' => (string)$c[ 'type' ] ,
			'id'   => (string)$c[ 'id' ] ,
			'desc' => (string)$c[ 'description' ] ?: _imp( $name_from_evd ) ,
			'evd'  => explode( ' ', (string)$c[ 'evidence' ] ) ,
			'ref'  => explode( ' ', (string)$c[ 'ref' ] ) ,
			'var'  => $c->original || $c->variation
				? $c->original. '&#x2192;'. $c->variation : ''
			,
			'loc'  => (integer)$c->location->position[ 'position' ]
				?: [
					(integer)$c->location->begin[ 'position' ] ,
					(integer)$c->location->end[ 'position' ]
				]
		]);
	}

	//.. ref
	$ref = [];
	foreach ( (object)$xml->reference as $c ) {
		$cit = $c->citation;

		//- ref
		$dbref = [];
		foreach ( (object)$cit->dbReference as $c2 ) {
			$dbref[ (string)$c2['type'] ] = (string)$c2['id'];
		}
		//- auth
		$auth = [];
		foreach ( (object)$cit->authorList->children() as $c2 ) {
			$auth[] = (string)$c2['name'];
		}
		//- src
		$src = [];
		if ( $c->source ) {
			foreach ( (array)$c->source->children() as $k => $v ) {
				$src[] = "$k: ". ( is_array( $v ) ? _imp( $v ) : $v );
			}
		}
//		_pause();

		//- main
		$ref[ (string)$c[ 'key' ] ] = array_filter([
			'scope'		=> implode( ', ', (array)$c->scope ) ,
			(string)$cit['type'] ?: 'Reference' => _imp([
				$cit[ 'name' ] ,
				$cit[ 'db' ] ,
				$cit[ 'date' ] ,
				$cit[ 'volume' ] ? 'Vol.'. $cit[ 'volume' ] : '',
				$cit[ 'first' ]  ? 'p'  . $cit[ 'first' ] : '' ,
			]) ,
//			'title'		=> (string)$cit->title ,
			'author'	=> $auth[0]. ( 1 < count( $auth ) ? ' et al.': '' ) ,
			'_ref' => $dbref ,
			'source' => _imp( $src ) ,
		]);
	}

	//.. save
	if ( _json_save( $fn_out, array_filter([
		'name' => $ent_name ,
		'org'  => $org ,
		'taxid' => $taxid ,
		'cmnt' => $cmnt ,
		'loc'  => array_values( _uniqfilt( $subc_loc ) ) ,
		'go'   => $go_info ,
		'link' => $links ,
		'ref'  => $ref ,
		'intp' => $intp ,
		'ec'   => array_unique( $ec ) ,
		'fet'  => $fet ,
		'evd'  => $evd ,
		'n2dbid' => $name2dbid ,
		'dbref'	=> $other_dbid ,
		'dic'	=> $dic ,
	]), 'nomsg'))
		_log( "$unp_id: 保存" );

//	_m( json_encode( _json_load( $fn_out ), JSON_PRETTY_PRINT ) );
//	_pause();

}

//. function
//.. _get_value
function _get_value( $o, $type ) {
	foreach ( (object)$o->property as $child ) {
		if ( (string)$child[ 'type' ] == $type )
			return (string)$child[ 'value' ];
	}
}

//.. 
function _getval_with_evd( $xml ) {
	return $xml[ 'evidence' ] 
		? [ (string)$xml, explode( ' ', $xml[ 'evidence' ] ) ]
		: (string)$xml
	;
}
