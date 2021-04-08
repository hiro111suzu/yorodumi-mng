<?php
include 'commonlib.php';

//. db
_line( 'DBの登録数などの基本情報' );

define( 'FN_OUT', DN_DATA. '/binfo.json' );
$num = [];
foreach ( [ 'taxo',  'pap', 'doc', 'dbid', 'met' ] as $name) {
	$num[ $name ] = ( new cls_sqlite( $name ) )->cnt();
}

$data = [
	'datacount' => [
		'pdb'	=> count( _idlist( 'pdb' ) ) ,
		'emdb'	=> count( _idlist( 'emdb' ) ) ,
		'sas'	=> count( _idlist( 'sasbdb' ) ) ,
		'chem'	=> count( _idlist( 'chem' ) ) ,
	] + $num, 
	'rel_date' => $rel_date = _rel_date() ,
];

_kvtable( $data[ 'datacount' ] );
_m( "公開日: $rel_date" );
_comp_save( FN_OUT, $data );
_end();
