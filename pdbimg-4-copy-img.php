//- どの画像をエントリの代表画像にするか決定
//- assemblyの小さい番号優先、author-definedがあればそれ、なければ登録構造

<?php
require_once( "commonlib.php" );
require_once( "pdbimg-common.php" );

_mkdir( DN_DATA . '/pdb/img' );

//. main
foreach ( _idloop( 'pdb_json' ) as $fn_pdbjson ) {
	if ( _count( 5000, 0 ) ) break;
	$id = _fn2id( $fn_pdbjson );

	//- チェック
	$fn_dest = _fn( 'pdb_img', $id );
	if ( _same_time( $fn_pdbjson, $fn_dest ) ) continue;
	if ( _proc( "img_select-$id" ) ) continue;

	//- details収集
	$data = [];
	$auth_ex = false; //- author definedがあるかどうか
	foreach ( (array)_json_load2( $fn_pdbjson )->pdbx_struct_assembly as $j ) {
		$data[ $j->id ] = $j->details;
		if ( _instr( 'author', $j->details ) )
			$auth_ex = true;
	}
	ksort( $data );

	//- 選択
	$fn_selected = _fn( 'img_dep', $id );
	foreach ( $data as $i => $det ) {
		//- オーサーが決めたものがあるなら、ソフトは無視
		if ( $auth_ex && $det == 'software_defined_assembly' ) continue;

		$fn = _fn( 'img_asb', "{$id}_{$i}" );
		if ( ! file_exists( $fn ) ) continue;

		//- 空白か、グレー
		$sz = filesize( $fn );
		if ( $sz == 823 || $sz == 479 ) continue;

		$fn_selected = $fn;
		break; //- identical assemblyの場合はdep
	}
//	_pause( $fn_selected );

	exec( "rm -f $fn_dest" );
	copy( $fn_selected, $fn_dest );
	touch( $fn_dest, filemtime( $fn_pdbjson ) );
	_proc();
}

//. remove obsolete data
_delobs_pdb( 'pdb_img' );
_end();
