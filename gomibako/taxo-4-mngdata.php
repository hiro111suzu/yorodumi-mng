taxoデータを管理
ダウンロードからするときは 'dl'
和名データのダウンロードは手動
http://lifesciencedb.jp/lsdb.cgi?gg=dic

生物アイコン
http://biosciencedbc.jp/taxonomy_icon/taxonomy_icon.cgi

==========
<?php
require_once( "commonlib.php" );
$tdn = DN_DATA . '/taxo';
define( 'DL', in_array( 'dl', $argv ) );

//. icon data
_m( 'icon data', 1 );
$datafn = "$tdn/taxonomyicon_data.txt";
$url = 'http://biosciencedbc.jp/taxonomy_icon/taxonomyicon_data.txt';
$jfn = "$tdn/taxoicon.json";

//.. data
if ( DL )
	_del( $datafn );

if ( ! file_exists( $datafn) ) {
	copy( $url, $datafn );
	if ( file_exists( $datafn ) )
		_m( 'Downloaded icondata' );
	else
		die( 'データが無い!!!' );
}

//.. 処理
$data = array();
foreach ( _file( $datafn ) as $l ) {
	$d = explode( "\t", $l );
	if ( $d[5] == '' or $d[5] == 's_name' ) continue;
	foreach ( $d as $i => $v ) {
		$d[ $i ] = strtr( trim( $v ), array( '\\N' => '' ) );
	}

	$j = explode( '|', $d[3] .'|'. $d[6] );
	$e = explode( '|', $d[4] .'|'. $d[7] );
	
	$data[ $d[5] ] = array(
		'id' => $d[2] ,
		'j' => implode( ', ', array_filter( array_unique( $j ) ) ) ,
		'e' => implode( ', ', array_filter( array_unique( $e ) ) )
	);
}
_comp_save( $jfn, $data );

//. taxo j-name
_m( '和名データ', 1 );

//- ダウンロードは手動
$data = array();
$dfn = DN_FDATA . "/taxo/species_names.latin_vs_japanese.utf8.txt";
$jfn = "$tdn/taxojname.json.gz";

//- load
if ( ! file_exists( $dfn ) )
	die( '和名データがない!!!!!' . $dfn );

foreach ( _file( $dfn ) as $l ) {
	$a = explode( "\t", $l );
	$data[ $a[0] ][ $a[1] ] = 1;
}

_m( '読み込み完了 データ数: ' . count( $data ) );

//- proc
foreach ( $data as $n => $v ) {
	$data[ $n ] = implode( ', ', array_keys( $v ) );
}

_comp_save( $jfn, $data );


