<?php
/*
key	taxo(sci_name) chem(chemid) other(title)

date
en [ key, abst ] ,
ja [ key, abst ]
*/

require_once( "wikipe-common.php" );
define ( 'FN_OUT', DN_DATA . '/wikipe.json.gz' );


//. main
$data = [];
foreach ( array_merge(
	(array)_idloop( 'wikipe_chem' ) ,
	(array)_idloop( 'wikipe_taxo' ) ,
	(array)_idloop( 'wikipe_misc' ) 
) as $fn ) {
	_count( 1000 );
	$cat = substr( basename( pathinfo( $fn, PATHINFO_DIRNAME ) ), 0, 1 );
	$json = _json_load2( $fn );
	if ( ! $json->et && ! $json->jt ) continue;
	$key = $cat == 'm' ? strtolower( $json->key ) : $json->key;

	$data[ $key ] = [
		'et' => $json->et ,
		'ea' => _abst_rep( $json->ea ) ,
		'jt' => $json->jt ,
		'ja' => _abst_rep( $json->ja ) ,
		'c' => $cat
	];
	_cnt( 'total' );
	_cnt( $cat );
}
_comp_save( FN_OUT, $data );
_cnt();

//. func _abst_rep
function _abst_rep( $in ) {
	return preg_replace([
		'/ style=".+">/' ,
		'/ id=".+">/' ,
		'/<\/?span.*?>/' ,
		'/>[ \n\r\t]+</' ,
		'/<p><\/p>/' ,
		'/<br>/' ,
		'/^<p>/' ,
		'/<\/p>.*$/' ,
	], [
		'', '', '', '><' ,'', '', '', '', 
	],
		$in
	);
}
