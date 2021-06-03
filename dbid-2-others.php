<?php
include "commonlib.php";

//. テンポラリ設定
//define( 'DBS_TODO', 'prorule' );
//define( 'DBS_TODO', 'all' );
define( 'DBS_TODO', $argv[1] ?: 'all' );
define( 'FLG_DOWNLOAD', true );
//define( 'FLG_DOWNLOAD', false );

//. conf
define( 'DN_FDATA_DBID', DN_FDATA. '/dbid' );
//- pfam
define( 'URL_PFAM_TSV',
	'ftp://ftp.ebi.ac.uk/pub/databases/Pfam/current_release/Pfam-A.clans.tsv.gz'
);

define( 'FN_PFAM_TSV', DN_FDATA_DBID. '/Pfam-A.clans.tsv.gz' );

//- interpro
define( 'URL_INTERPRO_TSV', 'ftp://ftp.ebi.ac.uk/pub/databases/interpro/entry.list' );
define( 'FN_INTERPRO_TSV', DN_FDATA_DBID. '/interpro_list.tsv' );

//- go
define( 'URL_GO_OBO', 'http://snapshot.geneontology.org/ontology/go.obo' );
define( 'FN_GO_OBO', DN_FDATA_DBID. '/go.obo' );

//- reactome
define( 'URL_REACT_TXT', 'https://reactome.org/download/current/ReactomePathways.txt' );
define( 'FN_REACT_TSV', DN_FDATA_DBID. '/reactome.tsv' );

//- prosite
define( 'URL_PROSITE_TXT', 'http://ftp.expasy.org/databases/prosite/prosite.dat' );
define( 'FN_PROSITE_TXT', DN_FDATA_DBID. '/prosite.txt' );

//- prorule
define( 'URL_PRORULE_TXT', 'http://ftp.expasy.org/databases/prosite/prorule.dat' );
define( 'FN_PRORULE_TXT', DN_FDATA_DBID. '/prorule.txt' );

//- smart
define( 'URL_SMART_TSV', 'http://smart.embl-heidelberg.de/smart/descriptions.pl' );
define( 'FN_SMART_TSV', DN_FDATA_DBID. '/smart.tsv' );

//. convert
//.. Pfam
if ( _flg_do( 'pfam' ) ) {
	_line( 'PFAM', 'downloading' );
	_flg_copy( URL_PFAM_TSV, FN_PFAM_TSV );

	_line( 'PFAM', 'conversion' );
	$out = [];
	foreach ( gzfile( FN_PFAM_TSV ) as $line ) {
		list( $id, $dummy, $c1, $c2, $c3 ) = explode( "\t", trim( $line, "\t\r\n" ) );
		if ( _similar( $c1, $c2 ) ) $c2 = '';
		if ( _similar( $c1, $c3 ) ) $c1 = '';
		if ( _similar( $c2, $c3 ) ) $c2 = '';
		
		$out[ (integer)_numonly( $id ) ] = [ _imp( _uniqfilt([ $c1, $c2 ]) ), $c3 ];
	}
	_comp_save( FN_PFAM_JSON, $out );
	_m( count( $out ) );
}
//.. InterPro
if ( _flg_do( 'interpro' ) ) {
	_line( 'InterPro', 'downloading' );
	_flg_copy( URL_INTERPRO_TSV, FN_INTERPRO_TSV );

	_line( 'InterPro', 'conversion' );
	$out = [];
	foreach ( gzfile( FN_INTERPRO_TSV ) as $line ) {
		list( $id, $type, $name ) = explode( "\t", trim( $line, "\t\r\n" ), 3 );
		$id = (integer)_numonly( $id );
		if ( ! $id ) continue;
		$out[ $id ] = [ strtr( $type, [ '_' => ' ' ] ) , $name ];
	}
	_comp_save( FN_INTERPRO_JSON, $out );
	_m( count( $out ) );
}
//.. Go
if ( _flg_do( 'go' ) ) {
	_line( 'GO', 'downloading' );
	_flg_copy( URL_GO_OBO, FN_GO_OBO );

	_line( 'GO', 'conversion' );
	$out = [];
	$data = [];
	$alt_id = [];
	foreach ( _file( FN_GO_OBO ) as $line ) {
	//	_m( $line );
		if ( substr( $line, 0, 1 ) == '[' ) {
			if ( $data['id'] && $data['name'] && !$data['is_obsolete'] ) {
				$out[ _numonly( $data['id'] ) ] = [ $data['namespace'], $data['name'] ];
			}
			$data = [];
		} else {
			list( $key, $val ) = explode( ': ', $line, 2 );
			if ( $key == 'namespace' )
				$val = ucfirst( strtr( $val, [ '_' => ' ' ] ) );
			if ( $key && $val ) {
				if ( $key == 'alt_id' ) {
					$alt_id[ $val ] = $data['id'];
				}
				$data[ trim( $key ) ] = trim( $val );
			}
		}
	}
	_m( 'count-orig: '. count( $out ) );
	_m( 'count-alt: '. count( $alt_id ) );
	
	foreach ( $alt_id as $alt => $orig ) {
		$alt = _numonly( $alt );
		if ( $out[ $alt ] ) {
			_m( 'alt_id: オリジナルがある', -1 );
		} else {
			$o = $out[ _numonly( $orig ) ];
			$out[ $alt ] = [ $o[0], $o[1]. " => $orig" ];
		}
	}

	_comp_save( FN_GO_JSON, $out );
	_m( "count total: " . count( $out ) );
}

//.. Reatome
if ( _flg_do( 'reactome' ) ) {
	_line( 'Reatome', 'downloading' );
	_flg_copy( URL_REACT_TXT, FN_REACT_TSV );

	_line( 'Reactome', 'conversion' );
	$out = [];
	$data = [];
	foreach ( _file( FN_REACT_TSV ) as $line ) {
		list( $id, $name, $taxo ) = explode( "\t", $line );
		$out[ trim($id) ] = [ $taxo, $name ];
	}
	_comp_save( FN_REACT_JSON, $out );
	_m( "count: " . count( $out ) );
}

//.. prosite
if ( _flg_do( 'prosite' ) ) {
	_line( 'PROSITE', 'downloading' );
	_flg_copy( URL_PROSITE_TXT, FN_PROSITE_TXT );

	_line( 'PROSITE', 'conversion' );
	$out = [];
	$data = [];
	foreach ( _file( FN_PROSITE_TXT ) as $line ) {
		list( $key, $line ) = explode( '   ', $line, 2 );
		if ( $key == '//' && $data['AC'] && $data['ID'] ){
			$out[ (integer)_numonly( $data['AC'] ) ] = [ $data['ID'], $data['DE'] ];
			$data = [];
		} else {
			if ( in_array( $key, [ 'AC', 'ID', 'DE'] ) )
				$data[ $key ] = trim( $line );
		}
	}
	_comp_save( FN_PROSITE_JSON, $out );
	_m( "count: " . count( $out ) );
}

//.. prorule
if ( _flg_do( 'prorule' ) ) {
	_line( 'PRORULE', 'downloading' );
	_flg_copy( URL_PRORULE_TXT, FN_PRORULE_TXT );

	_line( 'PRORULE', 'conversion' );
	$ac = $name = '';
	$out = [];
	foreach ( _file( FN_PRORULE_TXT ) as $line ) {
		list( $key, $val ) = explode( '   ', $line, 2 );
		list( $key2, $val2 ) = explode( ': ', $line, 2 );
		if ( $key == '//' && $ac && $name ){
//			$out[ (integer)_numonly( $ac ) ] = $name;
			$out[ trim( $ac, " ;\n\r\t" ) ] = $name;
			$ac = $name = '';
		} else {
			if ( $key == 'AC' ) {
				$ac = $val;
			} else if ( $key2 == 'Names' ) {
				$name = $val2;
			}
		}
	}
	_comp_save( FN_PRORULE_JSON, $out );
	_m( "count: " . count( $out ) );
}

//.. smart
if ( _flg_do( 'smart' ) ) {
	_line( 'SMART', 'downloading' );
	_flg_copy( URL_SMART_TSV, FN_SMART_TSV );

	_line( 'SMART', 'conversion' );
	$out = [];
	$data = [];
	foreach ( _file( FN_SMART_TSV ) as $line ) {
		list( $domain, $acc, $def, $desc ) = explode( "\t", $line, 4 );
		if ( ! $acc || $acc == 'ACC' ) continue;
		$out[ $acc ] = [ $domain, $def ?: $domain ];
	}
	_comp_save( FN_SMART_JSON, $out );
	_m( "count: " . count( $out ) );
}

//. end
_end();

//. func
//.. _flg_do
function _flg_do( $db ) {
	return DBS_TODO == 'all' || DBS_TODO == $db;
}

//.. _flg_copy
function _flg_copy( $in, $out ) {
	_m( "$in\n=> $out ");
	if ( ! FLG_DOWNLOAD ) {
		_m( 'Downloading is canceled' );
		return;
	}
	_download( $in, $out );
}

//.. _similar
function _similar( $s1, $s2 ) {
	$c = [];
	foreach ([ $s1, $s2 ] as $s ) {
		$c[] = strtolower( strtr( $s, [ ','=>' ', '_'=> ' ' ] ) );
	}
	return $c[0] == $c[1];
}
