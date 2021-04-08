chem_comp_cifから画像

<?php
require_once( "bird-common.php" );

//. main
$num = 0;
foreach ( _idloop( 'bird_json' ) as $fn_json ) {
	if ( _count( 100, 0 ) ) die;
	$id = _fn2id( $fn_json );
	$fn1  = _fn( 'bird_img', $id );
	$fn2 = _fn( 'bird_img2', $id );
	if ( file_exists( $fn1 ) && file_exists( $fn2 ) ) continue;

	++ $num;
	$chem_id = _json_load2( $fn_json )->pdbx_reference_molecule[0]->chem_comp_id;
	if ( ! $chem_id ) continue;
	_cp( _fn( 'chem_img' , $chem_id ), $fn1, "$num\t$id-1" );
	_cp( _fn( 'chem_img2', $chem_id ), $fn2, "$num\t$id-2" );
}

//. end
_end();

function _cp( $in, $out, $name ) {
	if ( ! file_exists( $in ) ) return;
	if ( file_exists( $out ) ) return;
//	_m( "$name$in => $out" );
	_m( "$name: コピー". ( copy( $in, $out ) ? '成功' : '失敗' ) );
}

