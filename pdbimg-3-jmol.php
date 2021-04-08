PDB画像

<?php
//. init
require_once( "commonlib.php" );
require_once( "pdbimg-common.php" );
$blacklist = [];

//- その日の画像
$newimgdn = DN_PREP . '/img_que/newimgs' . date( 'Y-m-d' );
_mkdir( $newimgdn );

//- 古いディレクトリを削除
while ( true ) {
	$dirs = glob( DN_PREP . '/img_que/newimgs*' );
	if ( count( $dirs ) < 10 ) break;
	exec( "rm -rf {$dirs[0]}" );
}

//. ID指定
//.. 強制的にやるID
$is_doid = [];
$s = $argar[ 'id' ];
if ( $s != '' ) {
	foreach ( explode( ',', $s ) as $i ) {
		$is_doid[ $i ] = true;
		//- キューがdoneにあれば戻しておく
		foreach ( glob( _fn( 'que_done', "$i*" ) ) as $n ) {
			rename( $n, strtr( $n, [ 'done/' => 'todo/' ] ) );
			_m( basename( $n, '.json' ) . ": キューを再登録", 1 );
		}
	}
	_line(
		'指定データのみ実行', 
		_imp( array_keys( $is_doid ) ) . ' (' . count( $is_doid ) . '個) '
	);
}

//.. omokage search の結果から
if ( $argar[ 'list' ] != '' ) {
	$id = $argar[ 'list' ];
	$flist = glob( "../emnavi/omocache/$id*.json.gz" );
	$c = count( $flist );
	if ( $c > 10 )
		die( "キャッシュファイルが多すぎ: $c" );
	if ( $c == 0 )
		die( "キャッシュファイルがない" );

	foreach ( $flist as $fn ) {
		$j = _json_load( $fn );
		foreach ( array_slice( $j[ 's' ], 0, 500 ) as $i => $s ) {
			$i = strtr( $i, [ '-d' => '-dep' ] );
			$is_doid[ $i ] = true;
		}
	}

	_line( ''
		. "$id に似たもののみ実行\nデータ数: "
		. count( $is_doid ) . "\n"
		. _imp( array_keys( array_slice( $is_doid, 0, 50 ) ) )
		. ' ...'
	);
}

//. main
//$out = [];
_count();

foreach ( _idloop( 'que_todo' ) as $fn_json ) {
//	if ( _count( 10, 50 ) ) break;
//	_count( 1000 );
	
	if ( ! file_exists( $fn_json ) ) continue; //- 別プロセスでなくなっている可能性がある
	$name = _fn2id( $fn_json );

	list( $pdb_id, $asb_id ) = explode( '-', $name );

	//.. やるかやらないか
	//- ブラックリスト
	if ( in_array( $name, $blacklist ) ) {
		_m( "$name: ブラックリスト", -1 );
		continue;
	}

	//- id指定されている？
	if ( count( $is_doid ) > 0 ) {
		if (
			( ! $is_doid[ substr( $name, 0, 4 ) ] ) and
			( ! $is_doid[ $name ] )
		) continue;
	}

	if ( _proc( "PDB-image-$name" ) ) continue;

	//.. 実行
	_line( '画像作成開始', $name );

	$json = _json_load( $fn_json );

	if (
		!array_key_exists( 'id', $json ) ||
		!array_key_exists( 'cmd', $json )
	) {
		_problem( "$pdb_id: #$asb_id: jsonがおかしい" );
		_del( $fn_json );
		print_r( $json);
		_proc();
		continue;
	}

	$id = $type = $cmd = $ori6 = '';
	extract( $json ); //- $id, $type, $cmd, $ori6
//	_pause( $json );

	//- temp ファイル名
	$fn_tmpimg = _tempfn( 'jpg' );
	$fn_list = [];
	if ( $ori6 ) {
		$fn_list[1] = $fn_tmpimg;
		foreach ( [ 2, 3, 4, 5, 6 ] as $n )
			$fn_list[ $n ] = _tempfn( 'jpg' );
	}

	//- Jmol
	$cmd = strtr( $cmd, [
		'<tmpimg>'	=> $fn_tmpimg ,
		'<img1>'	=> $fn_list[1] ,
		'<img2>'	=> $fn_list[2] ,
		'<img3>'	=> $fn_list[3] ,
		'<img4>'	=> $fn_list[4] ,
		'<img5>'	=> $fn_list[5] ,
		'<img6>'	=> $fn_list[6] 
	]);

	//- 2回トライ
	foreach ( [ 1, 2 ] as $i ) {
		_jmol( $cmd, 200, 500 );
		if ( filesize( $fn_tmpimg ) > 0 ) break;
		_m( "$name 画像作成失敗 $i 回目", -1 );
	}

	if ( $ori6 )
		$fn_tmpimg = _img_largest( $fn_list );

	//- 画像ファイル名
	$fn_img = $type == 'dep'
		? _fn( 'img_dep', $id )
		: _fn( 'img_asb', $id .'_'. $type )
	;

	//.. ファイルができていたら、コピー
	if ( filesize( $fn_tmpimg ) > 0 ) {
		_del( $fn_img );
		_imgres( $fn_tmpimg, $fn_img, 100 );

		if ( ! file_exists( $fn_img ) ) {
			_problem( "$pdb_id: #$asb_id: 画像コピー失敗" );
		} else {
			//- 代表画像を消しておく
			_del( _fn( 'img_rep', $id ) );
			copy( $fn_img, "$newimgdn/" . basename( $fn_img ) );
			
			//- キューファイルを移動
			$fn_done = _fn( 'que_done', $name );
			_del( $fn_done );
			rename( $fn_json, $fn_done );
			_log( "画像作成 成功", 'blue' );
		}
	} else {
		copy( DN_PREP . '/blank.jpg', $fn_img );
		_problem( "$pdb_id: #$asb_id: 画像作成失敗、空白画像を利用" );
	}

	//- temp画像を消す
	_del( $fn_tmpimg );
//	foreach ( $fn as $f )
//		_del( $f );

	_proc();
}

//. 無くなったエントリ消去
_delobs_pdb( 'img_dep' );
_delobs_pdb( 'img_asb' ); 

$cnt = 0;
_count();
foreach ( _idloop( 'img_asb', '同一集合体' ) as $fn ) {
	_count( 5000 );
	$i = strtr( _fn2id( $fn ), '-', '_' );
	if ( ! _inlist( $i, 'identasb' ) ) continue;
	_del( $fn );
	_m( "同一構造なので消去: $i" );
}
_end();

