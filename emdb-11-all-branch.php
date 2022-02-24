<?php
include "commonlib.php";

//. mainloop
$ids = [];
$ids_multi = [];
$data = [];
$num_ent = 0;
foreach ( _idloop( 'emdb_json' ) as $pn ) {
	if ( _count( 'emdb', 0 ) ) break;
	$id = _fn2id( $pn );
	$json = _emdb_json3_rep( _json_load2( $pn ) );
	_f( $json, [] );
	++ $num_ent;
}

//. tsv
ksort( $data );
$out = '';
foreach ( $data as $k => $v ) {
	$data[ $k ] = $v = ''
		. ( $v['val'] ? 'val' : '' )
		. ( $v['obj'] ? 'obj' : '' )
		. ( $v['single'] && ! $v['multi'] ? 'single' : '' )
		. ( $v['multi'] ? 'multi' : '')
	;
	$o = trim( $k, '!' ). "\t$v\n";
//	_m( $o );
	$out .= $o;
}
_comp_save( DN_PREP. '/emdb_all_tags.tsv', $out );

//. json
ksort( $ids );
$out = [];
foreach ( $ids as $k => $v ){
	$v = array_unique( (array)$v );
	$c = count( $v );
	$m = array_unique( (array)$ids_multi[ $k ] );
	$m = count( $m ) < 5000 ? array_slice( $m, 0, 20 ) : [];
	$out[ trim( $k, '!' ) ?: '/' ] = [
		'type'	=> $data[ $k ] ,
		'num'	=> $c , // == $num_ent ? 'all' : $c,
		'ids'	=> 10000 < $c ? [] : array_slice( $v, 0, 50 ) ,
		'ids_multi' => $m,
	];
}
$out['_info']['all'] = $num_ent;
_json_save( DN_PREP. '/emdb_all_tags.json', $out );

//. function (main)
function _f( $o, $ar_k ) {
	global $data, $ids, $id, $ids_multi;
//	if ( ! is_array( $o ) && ! is_object( $o ) ) return;
	$ar_k_i = implode( '<~>', $ar_k ). '!';
	$data[ $ar_k_i ]['all'] = true;
	$ids[ $ar_k_i ][] = $id;
	if ( is_array( $o ) ) {
		if ( 1 < count( $o ) ) {
			$data[ $ar_k_i ][ 'multi' ] = true;
			$ids_multi[ $ar_k_i ][] = $id;
		} else {
			$data[ $ar_k_i ][ 'single' ] = true;
		}
	} else if ( is_object( $o ) ) {
		$data[ $ar_k_i ]['obj'] = true;		
	} else {
		$data[ $ar_k_i ]['val'] = true;
		return;
	}
	foreach ( $o as $k => $v ){
		_f( $v, array_merge( $ar_k, is_array( $o ) ? [ '[~]'  ]: [ $k ] ) );
	}
}

