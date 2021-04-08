集合体情報、あてはめ情報など
assembly.jsonに保存

<?php
//. misc init
require_once( "commonlib.php" );


$ngid = [ 'PAU', 'HAU' ];
$alldata = [];

//. emdb- mov
//- 複数PDBを当てはめたムービーがある場合、それらをSplitと同等にする
$spj = [];
$cnt = 0;
foreach ( _idloop( 'movinfo', '同じマップに当てはめたIDを抽出' ) as $fn ) {
	_count( 'emdb' );
	foreach ( _json_load( $fn ) as $num => $v ) {
		if ( ! is_array( $v ) ) continue;
		if ( $v[ 'mode' ] != 'pdb' ) continue;
		$ar = $v[ 'fittedpdb' ];
		if ( count( $ar ) < 2 ) continue;
		foreach ( $ar as $i1 ) foreach ( $ar as $i2 ) {
			if ( $i1 == $i2 ) continue;
			$spj[ $i1 ][] = $i2;
			$spj[ $i2 ][] = $i1;
		}
	}
}
$cnt = count( $spj );
_m( "$cnt 個の複数モデル当てはめデータ読み込み" );

//. mng-confから
_line( 'tsvデータ追加' );
$cnt = 0;
foreach ( _mng_conf( 'split2' ) as $k => $v ) {
	$k = _repid( $k );
	$v = _repid( $v );
	if ( in_array( $v, (array)$spj[ $k ] ) ) continue;
	$spj[ $k ][] = $v;
	$spj[ $v ][] = $k;
	++ $cnt;
}
_m( "$cnt 個のデータをtsvから追加" );

foreach ( $spj as $id => $ar ) {
	$spj[ $id ] = array_unique( $ar );
	sort( $spj[ $id ] );
}

//. mainloop
_line( 'メイン' );
_count();
foreach ( _idloop( 'epdb_json' ) as $fn ) {
	_count( 'epdb' );
	$id =  _fn2id( $fn );
	$json = _json_load2( $fn );

	//.. assembly
	$out = [];
	//- assembly 読み込み
	foreach ( (array)$json->pdbx_struct_assembly as $v  ) {
		$asb_id = $v->id;
		if ( _inlist( "$id-$asb_id", 'identasb' ) ) continue;
		if ( in_array( $asb_id, $ngid ) ) continue;
		if ( _instr( 'asymmetric unit', $v->details ) ) continue;

		//- num
		$num = $v->oligomeric_count;
		if ( $num == '' )
			_problem( "$id-$asb_id: 分子数が不明" );
		$out[ $asb_id ][ 'num'  ] = $num;

		//- type
		$type = $v->details ?: $v->oligomeric_details ;
		$out[ $asb_id ][ 'type' ] = $type;
		_cnt( $type );
	}

	//.. split
	$sp = $split[ $id ];
	if ( count( (array)$sp ) > 0 ) {
		sort( $sp );
		$out[ 'sp' ] = array_unique( $sp );
	}
	//- sp2
	$j = $spj[ $id ];
	if ( count( (array)$j ) > 0 ) {
		if ( is_array( $sp ) )
			$j = array_unique( array_merge( $j, $sp ) );
		sort( $j );		
		if ( $j != $out[ 'sp' ] )
			$out[ 'sp2' ] = $j;
	}

	//.. 書き出し
	if ( count( $out ) == 0 ) continue;
	$alldata[ $id ] = $out;
}

//. 保存
_comp_save( FN_ASB_JSON, $alldata );

//- asssembly details項目
_cnt();
_end();

//_m( json_encode( $split, JSON_PRETTY_PRINT ) );
