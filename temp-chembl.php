<?php
//. misc. init
require_once( "commonlib.php" );

//. main

foreach ( _idlist('chem') as $id ) {
	//- 準備
	if ( _count( 1000, 0 ) ) break;
	$j = json_decode( _ezsqlite([
		'dbname' => 'chem' ,
		'select' => 'idmap' ,
		'where'  => [ 'id', $id ] ,
	]) );
	foreach ( [ 'ChEMBL', 'ChEBI', 'DrugBank' ] as $n )
		_cnt( "$n:". count( (array)$j->$n ) );
}

_cnt();
