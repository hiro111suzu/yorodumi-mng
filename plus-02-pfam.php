Pfamデータ取得

<?php

include "commonlib.php";

define( 'DBS_TODO', 'all' );
define( 'FLG_DOWNLOAD', true );


//- pfam
define( 'URL_PFAM_TSV',
	'ftp://ftp.ebi.ac.uk/pub/databases/Pfam/current_release/Pfam-A.clans.tsv.gz'
);
define( 'FN_PFAM_TSV', DN_FDATA. '/Pfam-A.clans.tsv.gz' );
define( 'FN_PFAM_JSON', DN_PREP. '/pfam_description.json.gz' );

//- interpro
define( 'URL_INTERPRO_TSV', 'ftp://ftp.ebi.ac.uk/pub/databases/interpro/entry.list' );
define( 'FN_INTERPRO_TSV', DN_FDATA. '/interpro_list.tsv' );
define( 'FN_INTERPRO_JSON', DN_PREP. '/interpro_info.json.gz' );

//- go
define( 'URL_GO_OBO', 'http://snapshot.geneontology.org/ontology/go.obo' );
define( 'FN_GO_OBO', DN_FDATA. '/go.obo' );
define( 'FN_GO_JSON', DN_PREP. '/go_info.json.gz' );

//- reactome
define( 'URL_REACT_OBO', 'https://reactome.org/download/current/ReactomePathways.txt' );
define( 'FN_REACT_TSV', DN_FDATA. '/reactome.tsv' );
define( 'FN_REACT_JSON', DN_PREP. '/reactome.json.gz' );

//- prosite
define( 'URL_PROSITE_TXT', 'ftp://ftp.expasy.org/databases/prosite/prosite.dat' );
define( 'FN_PROSITE_TXT', DN_FDATA. '/prosite.txt' );
define( 'FN_PROSITE_JSON', DN_PREP. '/prosite.json.gz' );


//. convert
//.. Pfam
if ( _flg_do( 'pfam' ) ) {
	_line( 'PFAM', 'downloading' );
	_flg_copy( URL_PFAM_TSV, FN_PFAM_TSV );

	_line( 'PFAM', 'conversion' );
	$out = [];
	foreach ( gzfile( FN_PFAM_TSV ) as $line ) {
		list( $id, $dummy, $str ) = explode( "\t", trim( $line, "\t\r\n" ), 3 );
		$out[ (integer)_numonly( $id ) ] = _imp( _uniqfilt( explode( "\t", $str ) ) );
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
			if ( $key && $val )
				$data[ trim( $key ) ] = trim( $val );
		}
	}
	_comp_save( FN_GO_JSON, $out );
	_m( "count: " . count( $out ) );
}

//.. Reatome
if ( _flg_do( 'reactome' ) ) {
	_line( 'Reatome', 'downloading' );
	_flg_copy( URL_REACT_OBO, FN_REACT_TSV );

	_line( 'Reatome', 'conversion' );
	$out = [];
	$data = [];
	foreach ( _file( FN_REACT_TSV ) as $line ) {
		list( $id, $name, $taxo ) = explode( "\t", $line );
		$out[ trim($id) ] = [ $name, $taxo ];
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
		} else {
			if ( in_array( $key, [ 'AC', 'ID', 'DE'] ) )
				$data[ $key ] = trim( $line );
		}
	}
	_comp_save( FN_PROSITE_JSON, $out );
	_m( "count: " . count( $out ) );
}

//. func
//.. _flg_do
function _flg_do( $db ) {
	return DBS_TODO == 'all' || DBS_TODO == $db;
}
function _flg_copy( $in, $out ) {
	if ( FLG_DOWNLOAD )
		copy( $in, $out );
	else
		_m( 'Downloading is canceled' );
}
