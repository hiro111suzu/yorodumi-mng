<?php
include "commonlib.php";
include "sas-common.php";

//. メイン
$data = [];
$mids = '';

foreach ( _idloop( 'sas_cif' ) as $ciffn ) {
	_count(100);
	$id = _fn2id( $ciffn );
	foreach ( explode( "data_{$id}_MODEL_", file_get_contents( $ciffn ) ) as $blk ) {
		$a = explode( "\n", $blk, 2 );
		$mid = trim( $a[0] );
		if ( $mid == '' or ! ctype_digit( $mid ) ) continue;

		$data[ 'mid2id' ][ $mid ] = $id;
		$data[ 'id2mid' ][ $id ][] = $mid;
		$mids .= "$mid\n";
		
		$spfn = _fn( 'sas_split_cif', $mid );
		if ( _newer( $spfn, $ciffn ) ) continue;
		file_put_contents( $spfn, "data_{$id}_MODEL_$mid\n" . $a[1] );
		_log( "$id - $mid: split cif 保存" );
	}
}
_comp_save( FN_SAS_MID, $data );
_comp_save( DN_DATA . '/ids/sasmodel.txt', $mids );

//. なくなったデータ対応
_delobs_misc( 'sas_split_cif', 'sasmodel' );
_end();
