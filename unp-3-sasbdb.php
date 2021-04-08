<?php
require_once( "unp-common.php" );

$out = [];
$flg_changed = false;
foreach ( _idloop( 'sas_json', 'SASBDBからUniprotID収集' ) as $fn ) {
	if ( _count( 100,  0 ) ) break;
	$id = _fn2id( $fn );
	foreach ( (array)_json_load2( $fn )->struct_ref as $c ) {
		if ( $c->db_name != 'UniProt' || ! $c->db_code ) continue;
		if ( in_array( $c->db_code, (array)$out[ $id ] ) ) continue;
		$out[ $id ][] = $c->db_code;
	}
	if ( $out[ $id ] )
		_m( "$id: ". _imp(  $out[ $id ] ) );
}
_comp_save( FN_SASID2UNPID, $out );
