addjson を作成
<?php
//. init
require_once( "commonlib.php" );
$out = [ "id\tXML v1\tXML v3" ];

//. main loop
foreach ( _idloop( 'emdb_new_json' ) as $fn ) {
	_count( 'emdb' );
	$id = _fn2id( $fn );
	$json = _emdb_json3_rep( _json_load2( $fn ) );

//.. 準備
	_cnt( 'all' );
	$sym = [];
	foreach ( (array)$json->structure_determination as $c ) {
		foreach( $c->processing as $c2 ) {
			$sym[] = $c2->final_reconstruction->applied_symmetry->point_group;
		}
	}
	$sym3 = _imp( _uniqfilt( $sym ) );
	$p = _json_load2([ 'emdb_old_json', $id ])->processing;
	$sym1 = _imp(
		$p->singleParticle->appliedSymmetry ,
		$p->subtomogramAveraging->appliedSymmetry ,
	);
	if ( $sym1 != $sym3 ) {
		$s = "$id\t$sym1\t$sym3";
		$out[] = $s;
		_m( $s );
//		_cnt( 'dif' );
		if ( ! $sym1 )
			_cnt( 'sym1 empty' );
		else if ( ! $sym3 ) 
			_cnt( 'sym3 empty' );
		else
			_cnt( 'diff' );
//		if ( ! $sym1 && ! $sym3 )
//		_m( 'diff', -1 );
	} else {
		_cnt( 'same' );
	}
}
file_put_contents( DN_PREP. '/xml-v2-v3-sym.tsv', implode( "\n", $out ) );
_cnt();

