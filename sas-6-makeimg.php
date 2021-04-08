<?php
include "commonlib.php";
include "sas-common.php";

//. jmol
$cmd_base = <<<'EOD'
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
slab off;
set zshade on;
set zshadepower 1;
slab 60;
frank OFF;

load "<fn_cif>" FILTER "![HOH]";

hide water;
select !unk; cartoon ONLY;

<style>

select all; center all;
rotate best; rotate z 90;
write image jpeg "<fn_img>";

EOD;

$cmd_atomic = <<<'EOD'
cartoon only; color chain;
select hetero; wireframe 0.5; spacefill 50%; color CPK;
select (unk and !sidechain); cpk 50%; backbone 200;color chain;
select connected(0,0) and (!hetero);cpk 70%; backbone 200; color chain;
EOD;

$cmd_dummy = 'spacefill only; color CPK;';

//. init

$midinfo = _json_load( FN_SAS_MID );
$sasid2scanid = [];

//. 全画像
foreach ( _idloop( 'sas_split_cif' ) as $fn_cif ) {
	$mid = _fn2id( $fn_cif );
	$fn_img = _fn( 'sas_img', $mid );
	if ( _newer( $fn_img, $fn_cif ) ) continue;

	$id = $midinfo[ 'mid2id' ][ $mid ];
	$json = _json_load2( _fn( 'sas_json', $id ) );
	foreach ( (array)$json->sas_model as $j ) {
		if ( $j->id != $mid ) continue;
		$type = $j->type_of_model;
	}

	_line( "$id - $mid\ntype: $type" );

	$fn_tmpimg = _tempfn( 'jpg' );
	$cmd = strtr( $cmd_base, [
		'<fn_cif>' => $fn_cif ,
		'<style>' => $type == 'atomic' ? $cmd_atomic : $cmd_dummy ,
		'<fn_img>' => $fn_tmpimg 
	]);
	_jmol( $cmd, 200  );

	_imgres( $fn_tmpimg, _fn( 'sas_img', $mid ) )
		? _log( "$id: 画像作成" )
		: _problem( "$id: 画像作成失敗" )
	;

	_del( $fn_tmpimg );
}

//. 代表画像
foreach ( _idlist( 'sasbdb' ) as $id ) {
	$fn_in  = _fn( 'sas_img', $midinfo[ 'id2mid' ][ $id ][0] );
	$fn_out = _fn( 'sas_img', $id );
	if ( ! file_exists( $fn_in ) ) {
//		_m( 'hoge' );
		$scan_id = _json_load2( _fn( 'sas_json', $id ) )->sas_scan[0]->id;
		$fn_in = DN_DATA. "/sas/plot/scan_intensity-$scan_id.svg";
		_m( "$fn_in => $fn_out". (file_exists($fn_in) ? 'o' : 'x' ) );
		if ( _newer( $fn_out, $fn_in ) ) continue;
		_imgres( $fn_in, $fn_out );
	}
//	_m( 'fuga' );
	if ( _newer( $fn_out, $fn_in ) ) continue;
	copy( $fn_in, $fn_out );
}

_end();

