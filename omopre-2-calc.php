<?php
//. init
require_once( "omopre-common.php" );
ini_set( "memory_limit", "512M" );
$obj_search = new cls_omo_small_search( 'pre' );

//. main
foreach ( _file( FN_OMOPRE_IDLIST ) as $id_query ) {
	$fn_out =  _fn( 'omolist', $id_query );
	if ( file_exists( $fn_out ) ) continue;
	if ( _proc( "omopre-calc-$id_query" ) ) continue;
	_line( $id_query );
	print_r( $list2 = $obj_search->do( $id_query ) );
	_json_save( $fn_out, $list2 );
	_proc();
}
