<?php
die();
//. misc init
require_once( "commonlib.php" );

//define( 'URL_BASE', 'http://txsearch.ddbj.nig.ac.jp/txsearch/txsearch.TXSearch?tx_Clas=scientific+name&tx_Name=' );

define( 'URL_BASE', 'http://ddbj.nig.ac.jp/tx_search/search?query=' );


$dn = DN_DATA . '/taxo';
$dn_nd = DN_PREP . '/taxo/nodata';
$count = 0;
define( 'LIMIT', 500 );

//.. emdb
_line( 'EMDB data' );
foreach ( _json_load( DN_PREP . '/ranking_taxo_em.json' ) as $name => $num ) {
	if ( _main( $name, $num ) ) break;
}

//.. pdb

_line( 'PDB data' );
foreach ( _json_load( DN_PREP . '/ranking_taxo.json' ) as $name => $num ) {
	if ( $num == 1 ) {
		_m( "$name: $num: too rare" );
		break;
	}
	if ( _main( $name, $num ) ) break;
}

//. main func
function _main( $name, $num ) {
	global $dn, $dn_nd;
	$name = ucfirst( strtolower( $name ) );
	$name_ = strtr( $name, [ ' ' => '_', '/' => '_' ] );
	$namep = strtr( $name, [ ' ' => '+' ] );

	$fn_json = "$dn/json/$name_.json";
	$fn_nodata = "$dn_nd/$name_.txt";
	if ( file_exists( $fn_json ) ) return;
	if ( file_exists( $fn_nodata ) ) return;

	_m( "$name - $num entries", 1 );
	//- 検索
	$get = _get( URL_BASE . $namep );

	$page = '';
	foreach ( preg_split( "/[\n\r]+/", $get ) as $line ) {
		if ( !_instr( 'Taxonomic name:<A HREF="/txsearch/txsearch.TXSearch?', $line ) )
			continue;
		$page = $line;
		break;
	}
	if ( $page == '' ) {
		_m( "No data for $name" );
//		touch( $fn_nodata );
		return;
	}

	//- データ取得
	$get = _get( 'http://txsearch.ddbj.nig.ac.jp' . 
		strtr( $page, [ 'Taxonomic name:<A HREF="' => '', '">' => '' ] ) );
	
	//- 解析
	$data = [];
	foreach ( explode( '<LI>', $get ) as $i ) {
		if ( ! _instr( ':', $i ) ) continue;
		$i = preg_replace(
			[ '/[\n\r]+/', '/ +/' ], [ ' ', ' ' ],
			strip_tags( $i )
		);
		$a = explode( ':', $i, 2 );
		$data[ trim( $a[0] ) ] = trim( $a[1] );
	}

	$out = [];
	$out = [
		'name'	=> trim( preg_replace( '/\[.+$/', '', $data[ 'Taxonomic name' ] ) ) ,
		'tid'	=> [ $data[ 'Taxonomy ID'] ] ,
		'line'	=> strtr( $data[ 'Full lineage' ], [ ' ; ' => ';' ] ) ,
		'oname'	=> $data[ 'Other name(s)' ] ,
	];

	//- 書き込み
	if ( ! _instr( ';', $out[ 'line' ] ) ) return;
//	_json_save( $fn_json, $out );
	_m( json_encode( $out, JSON_PRETTY_PRINT ) );
	
}

//. func
function _get( $url ) {
	global $count;
	++ $count;
	if (  $count > LIMIT )
		die( LIMIT . "アクセスしたので終了" );

	sleep( 2 );
	return file_get_contents( $url );
}

