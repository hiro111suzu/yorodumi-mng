<?php
include "omo-calc-common.php";

/*
omo-pdbスクリプトで利用
（mngの中のみ）
*/

//. directory & file name
define( 'DN_OMODATA', DN_ROOT . '/omopdb' );

//- _fnデータの追加
$_filenames += [

	'altmodel'	=> DN_OMODATA. '/altmodels/<name>.pdb.gz' , //- <s> = id-type
	'altmodel2'	=> DN_OMODATA. '/altmodels2/<name>.pdb.gz' , //- テンポラリ、テスト用

	//- vq
	'pdb_vq30'	=> DN_DATA. '/pdb/vq/<name>-30.pdb' ,
	'pdb_vq50'	=> DN_DATA. '/pdb/vq/<name>-50.pdb' ,

	//- profile
	'prof_emdb'	=> DN_OMODATA. '/prof-emdb/<id>.json.gz' ,
	'prof_pdb'	=> DN_OMODATA. '/prof/<name>.json.gz' ,
	'prof_sas'	=> DN_OMODATA. '/prof-sas/<name>.json.gz' ,

	//- composデータ
	'compinfo' 	=> DN_PREP   . '/omodev/compinfo/<id>.json'

];

//. func
//.. _situs
function _situs( $cmd, $params, $timeout = 600 ) {
	$p = '';
	if ( ! defined( 'DN_SITUS_BIN' ) ) {
		_envset( 'situs' );
	}
	foreach ( $params as $s )
		$p .= 'echo '	. ( is_numeric( $s ) ? $s : "\"$s\"" ) . '; ';

	exec( "($p) | timeout $timeout " .DN_SITUS_BIN. "/$cmd", $out );
	return $out;
}
