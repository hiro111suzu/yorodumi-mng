EMDBポリゴン(Jmol)ムービー作成

<?php
//. init
require_once( "commonlib.php" );

define( 'JMOLCMD', <<<EOD
set background white;
set ambientPercent 20;
set diffusePercent 40;
set specular ON;
set specularPower 80;
set specularExponent 2;
set specularPercent 70;
set antialiasDisplay ON;
set ribbonBorder ON;
set imageState OFF;
frank OFF;
select all;
load "_pdbfn_";
isosurface s1 insideout file "_pgfn_|o1.jvxl";
isosurface s1 OPAQUE [xb2ffff]
center all;
select all;
zoom 120;
slab off; set zshade on; slab 70;

function saveimg( num ) {
	var fn = "img" + ( "00000" + num )[-4][0] + ".jpeg";
	write image jpg 90 @fn;
	num++;
	return num;
}

var num = 0; # グローバル変数の使い方が分からん
# Y
for ( var i = 0; i < 180; i++ ) {
	num = saveimg( num );
	rotate y 2;
}
# sleep
for ( var i = 0; i < 15; i++ ) {
	num = saveimg( num );
}
# X
for ( var i = 0; i < 180; i++ ) {
	num = saveimg( num );
	rotate x 2;
}
EOD
);

//. start main loop
foreach( _idlist( 'emdb' ) as $id ) {
	_count( 'emdb' );
	
	//.. ファイル名
	$dn_ent = _fn( 'emdb_med', $id );
	$dn_frame   = "$dn_ent/imgjm";
	$fn_movinfo = _fn( 'movinfo', $id );
	$fn_snap    = "$dn_ent/snapsjm.jpg";
	$fn_polygon = "$dn_ent/ym/o1.zip";
	$fn_sizepdb = "$dn_ent/ym/pg1.pdb";
	$fn_frame	= "$dn_frame/img00370.jpeg";

	//.. やるかやらないか
	//- ポリゴンがなかったらやらない
	if ( ! file_exists( $fn_polygon ) ) continue;

	//- movinfoなかったらやらない
	if ( ! file_exists( $fn_movinfo ) ) continue;
	$j = _json_load( $fn_movinfo );
	
	//- solidじゃなかったらやらない
	if ( $j[1][ 'mode' ] != 'solid' ) continue;

	//- ポリゴンが新しかったら、スナップを消す
	if ( file_exists( $fn_snap ) )
		if ( filemtime( $fn_polygon ) > filemtime( $fn_snap ) )
			_del( $fn_snap );

	//- redo?
	if ( FLG_REDO )
		_del( $fn_snap );

	//- 画像かスナップがあったらやらない
	if ( file_exists( $fn_frame ) || file_exists( $fn_snap ) )
		continue;

	if ( _proc( "jmol-mov-$id" ) ) continue;

	//.. 作成
	_line( "ポリゴンムービー作成", $id );

	exec( "rm -rf $dn_frame" );
	mkdir( $dn_frame );
	chdir( $dn_frame );
	_jmol( strtr( JMOLCMD, [
		'_pgfn_'  => $fn_polygon,
		'_pdbfn_' => $fn_sizepdb,
		'insideout' => _mng_conf( 'pg_insideout', "$id-1" ) ? 'insideout' : ''
	]) );

	if ( file_exists( $fn_frame ) ) {
		_log( "$id: ポリゴン ムービーフレーム画像作成" );
		_mov_snaps( $dn_ent, 'jm', '00000' );
	} else {
		_problem( "$id: ポリゴン ムービーフレーム画像作成失敗" );
	}
	_proc();
}
//. end

_end();
