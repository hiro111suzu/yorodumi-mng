-----
json
	dbid => strid
	dbid => count( strid )

sqlite db
	dbid => title, num of str
-----
<?php
require_once( "unp-common.php" );
$data = [];
$cnt = [];

//. データ読み込み
_line( '読み込み' );
foreach ([ 
	'pdb'  => FN_DBDATA_PDB,
	'emdb' => FN_DBDATA_EMDB,
	'sas'  => FN_DBDATA_SASBDB
] as $db => $fn ) {
	_m( '読み込み: ' . basename( $fn ) );
	foreach ( _json_load( $fn ) as $str_id => $db_ids ) {
		if ( $db == 'emdb' ) {
			$str_id = "e$str_id";
//			_pause( $str_id );
		}
		foreach ( $db_ids as $db_id ) {
			list( $d, $i ) = explode( ':', $db_id );
			if ( !$i ) continue;
			$data[ "$d:".strtoupper( $i ) ][] = $str_id;
		}
	}
}

_line( '集計' );
foreach ( $data as $db_id => $str_ids ) {
	$i = array_values( array_unique( $str_ids ) );
	$data[ $db_id ] = $i;
	$cnt[ strtolower( $db_id ) ] = count( $i );
}
_comp_save( DN_PREP. '/dbid/dbid2strids.json.gz', $data ); //- 使っていない、念の為
_comp_save( FN_DBID2STRCNT, $cnt );

//.. db dbid2strid
define( 'FN_DBID2STRIDS', DN_DATA. '/dbid2strids.sqlite' );

_line( 'db: dbid2strid' );
$o_fh2str = new cls_sqlw([
	'fn' => FN_DBID2STRIDS , 
	'cols' => [
		'dbid UNIQUE COLLATE NOCASE' ,
		'strids COLLATE NOCASE' ,
		'score REAL' ,
	],
	'new' => true ,
	'indexcols' => [ 'dbid' ] ,
]);
foreach ( $data as $dbid => $str_id ) {
	_count( 10000 );
	if ( ! $cnt[ strtolower( $dbid ) ] )
		_m( $dbid. ': zero entry', 'red' );
	$o_fh2str->set([
		$dbid,
		implode( '|', $str_id ),
		1 / ( $cnt[ strtolower( $dbid ) ] ?: 1000000 )
	]);
}
_count();
$o_fh2str->end();
unset( $data );

//. prep DB
$sqlite = new cls_sqlw([
	'fn' => 'dbid', 
	'cols' => [
		'db_id UNIQUE COLLATE NOCASE' ,
		'type COLLATE NOCASE' ,
		'title COLLATE NOCASE' ,
		'num INTEGER' ,
	],
	'indexcols' => [ 'db_id', 'type' ] ,
	'new' => true
]);

//. db書き込み
define( 'INFO_EC'		, _json_load( FN_EC_NAME ) );
define( 'INFO_PFAM'		, _json_load( FN_PFAM_JSON ) );
define( 'INFO_INTERPRO'	, _json_load( FN_INTERPRO_JSON ) );
define( 'INFO_GO'		, _json_load( FN_GO_JSON ) );
define( 'INFO_REACT'	, _json_load( FN_REACT_JSON ) );
define( 'INFO_PROSITE'	, _json_load( FN_PROSITE_JSON ) );
define( 'INFO_SMART'    , _json_load( FN_SMART_JSON ) );
define( 'INFO_CATH'		, _json_load( FN_CATH_NAME_JSON ) );
define( 'INFO_BIRD'		, _json_load( DN_PREP. '/prd/prd_info.json.gz' ) );

$stat = [];
$cnt_items = 0;
foreach ( $cnt as $db_id => $num ) {
	if ( _count( 5000, 0 ) ) _pause();
	list( $db, $id ) = explode( ':', $db_id );
	if ( !$id ) continue;
	++ $stat[ $db ];
	$id_num = (integer)_numonly( $id );
	$id = strtoupper( $id );
	$txt = '';
	if ( $db == 'un' ) {
		$j = _json_load( _fn( 'unp_json', $id ) );
		$txt = [ $j['org'], $j['name'] ];
	} else if ( $db == 'ec' ) {
		$txt = INFO_EC[ trim( $id, '.-' ) ];
	} else if ( $db == 'pf' ) {
		$txt = INFO_PFAM[ $id_num ];
	} else if ( $db == 'in' ) {
		$txt = INFO_INTERPRO[ $id_num ];
	} else if ( $db == 'go' ) {
		$txt = INFO_GO[$id];
	} else if ( $db == 'rt' ) {
		$txt = INFO_REACT[$id];
	} else if ( $db == 'pr' ) {
		$txt = INFO_PROSITE[ $id_num ];
	} else if ( $db == 'sm' ) {
		$txt = INFO_SMART[ $id ];
	} else if ( $db == 'ct' ) {
		$txt = INFO_CATH[ $id ];
		if ( ! $txt ) {
			list( $i1, $i2, $i3, $i4 ) = explode( '.', $id );
			if ( INFO_CATH[ "$i1.$i2.$i3" ] ) {
				$txt = INFO_CATH[ "$i1.$i2.$i3" ]. " - #$i4";
			} else if ( INFO_CATH[ "$i1.$i2" ] ) {
				_m( "$id: lev2" );
				$txt = INFO_CATH[ "$i1.$i2" ]. " - #$i3.$i4";
			} else if ( INFO_CATH[ $i1 ] ) {
				_m( "$id: lev1" );
				$txt = INFO_CATH[ $i1 ]. " - #$i2.$i3.$i4";
			} else {
				_m( "$id: unknown entry" );
				$txt = 'Unknown';
			}
		}
			
		
	} else if ( $db == 'bd' ) {
		$txt = INFO_BIRD[ $id ];
	} else if ( $db ==  'chem' ) {
		$i = strtoupper( $id );
		$t = _ezsqlite([
			'dbname' => 'chem' ,
			'select' => 'name' ,
			'where'  => [ 'id', $i  ] ,
		]);
		$txt = strlen( $t ) < 50 ? $t : "Chem-$i";
	} else if ( $db == 'poly' ){
		$num = _numonly( $id );
		$type = strtr( $id, [ $num => '' ] );
		$txt = ( [
			'PEPD'		=> 'polypeptide(D)' ,
			'DNA_RNA'	=> 'DNA/RNA hybrid' ,
			'pep_nuc'	=> 'peptide nucleic acid' ,
		][ $type ] ?: strtoupper( $type ) )
		. ( $num ? " (> $num)" : '' )
		;
	}
	$sqlite->set([
		$db_id ,
		_db_name2categ( $db ) ,
		is_array( $txt ) ? implode( '|', $txt ) : $txt, 
		$num ,
	]);
	++ $cnt_items;
}
$sqlite->end();

ksort( $stat );
_tsv_save( DN_PREP. '/dbid/dbid-stat.tsv', $stat );

//. func
//.. _imp_b
function _imp_b( $ar ) {
	return implode( '|', $ar );
}
