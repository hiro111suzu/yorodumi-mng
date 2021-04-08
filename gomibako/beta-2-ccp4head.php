<?php
/* 
ccp4ヘッダ取得
Situsのmap2mapを使って、ccp4のヘッダを読み込む
input: mapファイルのみ
output: jsonファイル
* situsのエラーメッセージは必ず出る。

*/

//. init
require_once( "commonlib.php" );

$srcbase = "$_rootdn/emdb-beta/structures";
$destbase = "$_rootdn/beta-test";

$idlist = array();
foreach ( scandir( $srcbase ) as $dn ) {
	if ( substr( $dn, 0, 3 ) != 'EMD' ) continue;
	$idlist[] = substr( $dn, -4 );
}


$map2map = "map2map";

$cmdfn = 'template/cmd.txt';
if ( ! file_exists( $cmdfn ) )
	file_put_contents( $cmdfn, "3\nexit\n" ); //- パラメータ表示して終わるだけのコマンド

//. start main loop
foreach( $idlist as $id ) {
	$dn = "$destbase/$id";
	$mapfn = "$dn/emd_$id.map";
	$datafn = "$dn/mapinfo.json";

	print ".";
	if ( ! file_exists( $mapfn ) ) continue;

	if ( file_exists( $datafn ) ) {
		if ( filemtime( $mapfn ) < filemtime( $datafn ) )
			continue;
		_print( "$id: changed" );
	} else {
		_print( "$id: new map" );
	}

	//.. run map2map
	$out = array();
	@exec( "$map2map $mapfn hoge.situs < $cmdfn", $out );
	
	echo "\n=== $id ===\n";
	foreach ( $out as $s ) {
		if ( preg_match_all( '/> +(.+?) = +(.+?) /', $s, $v ) > 0 )
			$data[ $v[1][0] ] = $v[2][0];
	}
	$data[ 'APIX X' ] = $data[ 'X length' ] / $data[ 'MX' ];
	$data[ 'APIX Y' ] = $data[ 'Y length' ] / $data[ 'MY' ];
	$data[ 'APIX Z' ] = $data[ 'Z length' ] / $data[ 'MZ' ];
	
//.. end of loop
	_json_save( $datafn, $data );
	_print( "made $id info file" );

} // end of main loop (foreach)

_writelog();
return;

?>