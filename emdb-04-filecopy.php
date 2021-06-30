各種ファイルをFTPディレクトリからデータディレクトリコピー
- ディレクトリ作成
- マップ解凍、コピー
- chimera session ファイル作成
- 画像ファイルコピー
- fsc画像

<?php
//. init
require_once( "commonlib.php" );
_envset( 'eman' );

//- agg stateごとの色
define( 'METCOLOR', [
	't' => '0.700, 0.700, 0.700, 1.0,' ,
	'a' => '0.700, 0.700, 0.700, 1.0,' ,
	's' => '0.704, 0.960, 0.640, 1.0,' ,
	'h' => '0.896, 0.640, 0.960, 1.0,' ,
	'2' => '0.640, 0.768, 0.960, 1.0,' ,
	'i' => '0.954, 0.960, 0.640, 1.0,'
]);

_mkdir( DN_PREP . '/fsc_imgs' );
_mkdir( DN_PREP . '/emdb_dirinfo' );

define( 'DN_UNZIP_MAP', '/data/unzipped_maps' );
$_filenames += [
	'unzipped_map' => DN_UNZIP_MAP. '/emd_<id>.map' ,
	'fsc_check' => DN_PREP. '/fsc_imgs/<id>.png' ,
	'dir_info'  => DN_PREP. '/emdb_dirinfo/<id>-<s1>.json' ,
];

//. start main loop
$_dn_ext_ids = [];
$_dn_ext_cnt = [];
foreach( _idlist( 'emdb' ) as $id ) {
	_count( 'emdb' );
	$did = "emdb-$id";

	$dn_src  = DN_EMDB_MR . "/structures/EMD-$id";
	$dn_dest = DN_EMDB_MED . "/$id";
	if ( ! is_dir( $dn_dest ) ) {
		exec( "mkdir $dn_dest; chmod 777 $dn_dest" );
	}

	//.. file list
	//- 詳細ｰページのダウンロードテーブル用
	$list = [];
	foreach ( glob( "$dn_src/*", GLOB_ONLYDIR ) as $dn ) {
		foreach ( glob( "$dn/*" ) as $fn ) {
			$bdn = basename( $dn );
			$list[ $bdn ][] = [
				'name' => basename( $fn ) ,
				'size' => filesize( $fn )
			];

			//- 変わったタイプのファイルを記録
			$ext = _ext( $fn ) ?: 'none';
			++ $_dn_ext_cnt[ $bdn ][ $ext ];
			if ( ! in_array(
				"$bdn-$ext", 
				[ 'header-xml', 'map-map.gz', 'images-gif', 'images-png', 'images-tif',
					'fsc-xml'
				] 
			)) {
				$_dn_ext_ids[ $bdn ][ $ext ][] = $id;
			}
		}
	}
	_comp_save( _fn( 'filelist', $id ), $list, 'nomsg' );

	//.. copy map
	$fn_map_orig  = _fn( 'mapgz', $id );
	$fn_symlink = _fn( 'map', $id );
	$fn_unzipped_map = _fn( 'unzipped_map', $id );
	
	//- 実体
	$flag = false;
	if ( file_exists( $fn_map_orig ) ) {
		$time_orig = filemtime( $fn_map_orig );
		if ( ! file_exists( $fn_unzipped_map ) ) {
			$flag = true;
			$msg = "$id: new map";
		} else if ( $time_orig != filemtime( $fn_unzipped_map ) ) {
			$flag = true;
			$msg = "$id: changed map";
		}
	}

	if ( $flag ) {
		_log( $msg );
		$fn_temp_gz = "$fn_unzipped_map.gz";
		_del( $fn_unzipped_map, $fn_temp_gz ); //- 一応消しておく
		copy( $fn_map_orig, $fn_temp_gz );
		exec( "gunzip $fn_temp_gz" );
		exec( "rm -rf $dn_dest/mapi" );
		touch( $fn_unzipped_map, $time_orig );
		exec( "ln -fs $fn_unzipped_map $fn_symlink" ); //- シンボリックリンク
	}

	//.. session ファイル作成
	$fn_start_session = "$dn_dest/start.py";
	if ( file_exists( $fn_unzipped_map ) && ! file_exists( $fn_start_session ) ) {
		_m( "セッションファイル作成 proc method: $met" );

		//- threshold
		$thr = (string)_json_load2([ 'emdb_new_json', $id ])->map->contour[0]->level
			?: 1 
		; //- '0'でもtrueにする

		$met_color = METCOLOR[ _json_load2([ 'emdb_add', $id ])->met ];

		if ( $met_color == '' ) {
			_problem( "$id: 手法が未定で色を決められない" );
		} else {
			_m( "thr: $thr" );
			//- 書き込み
			file_put_contents( $fn_start_session, 
				strtr( file_get_contents( 'template/start_template.py' ), [
					'%id%'			=> $id ,
					'%surf_color%'	=> $met_color ,
					'%thr%'			=> $thr
				])
			);
			_log( "$id: session file" );
		}
	}

//.. masks
	$flg = 0;
	$list = [];
	$info = [];

	foreach ( _checkdir( 'masks' ) as $fn ) {
		$bn = basename( $fn );
		if ( _ext( $bn ) != 'map' ) {
			//- 情報ファイルをコピー
			$info[] = file_get_contents( $fn );
		} else {
			//- マスクのリストを作成
			$n = (integer)strtr( $bn, [ "emd_{$id}_msk" => '', '_' => '', '.map' => '' ] );
			$list[ $n ] = $bn;
		}
		$flg = 1;
	}

	if ( $flg ) {
		_json_save( "$dn_dest/masks/list.json", $list );
		if ( count( $info ) > 0 )
			file_put_contents( "$dn_dest/masks/info.txt", implode( "\n", $info ) );
	}

//.. 'other'
	$text = [];
	$other = [];
	$ddn = "$dn_dest/other";

	foreach ( _checkdir( 'other' ) as $fn ) {
		$bn = basename( $fn );
		if ( is_dir( $fn ) ) continue; //- 3683のみ、多分間違い
		$ext = _ext( $fn );
		_m( "拡張子: $ext" );

		if ( $ext == '' || $ext == 'txt' ) {
			$text[] = "[$bn]\n" . file_get_contents( "$fn" );
		} else if ( $ext == 'tif' or $ext == 'tiff' ) {
			_imgconv( $fn, "$ddn/$bn.jpg" );
			_imgres( $fn, "$ddn/$bn.thumb.jpg" );
			_log( "$id - $bn: 画像をコピー" );

		} else if ( $ext == 'jpg' ) {
			_imgres( $fn, "$ddn/$bn.thumb.jpg" );
			copy( $fn, "$ddn/$bn" );
			_log( "$id - $bn: 画像をコピー" );

		} else if ( $ext == 'map' || $ext == 'mrc' ) {
			exec( "ln -s $fn $ddn/$bn" ); //- シンボリックリンク
			_log( "$id - $bn: otherマップ シンボリックリンク作成" );

		} else if ( $ext == 'map.gz' || $ext == 'mrc.gz' ) {
			copy( $fn, "$ddn/$bn" );
			_exec( "gunzip $ddn/$bn" );
			_m( "$id - $bn: マップアーカイブをコピー、展開" );

		} else {
			$other[ $bn ] = filesize( $fn );
		}
	}

	if ( count( $text ) > 0 )
		file_put_contents( "$ddn/info.txt", implode( "\n", $text ) );
	if ( count( $other ) > 0 )
		_json_save( "$ddn/other.json", $other );

	//.. images
	foreach ( _checkdir( 'images' ) as $fn ) {
		$bn = basename( $fn );
		if ( substr( $bn, 0, 3 ) == '80_' ) continue;

		$ext = _ext( $fn );
		if ( $ext == 'safe' ) continue; //- 変な画像データ

		//- 元画像
		if ( $ext == 'tif' or $ext =='tiff' )
 			_imgconv( $fn, "$dn_dest/images/$bn.jpg" );
		else
			copy( $fn, "$dn_dest/images/$bn" ); 

 		//- サムネイル
		_imgres( $fn, "$dn_dest/images/$bn.thumb.jpg" );
	}

	//.. slices
	foreach ( _checkdir( 'slices' ) as $fn ) {
		$bn = basename( $fn );
		$dfn = "$dn_dest/slices/$bn";
		exec( PROC2D . " $fn $dfn.png png" );
		_imgres( "$dfn.png", "$dfn.jpg" );
	}

	//.. fsc
	$curves = $xlabels = $ylabels = $fn_data = [];
	foreach ( _checkdir( 'fsc' ) as $fn ) {
		if ( _ext( $fn ) != 'xml' ) continue; //- ないけど一応

		//- 名前
		$name = strtr(
			basename( $fn, '.xml' ),
			[ "emd_{$id}_" => '', 'fsc' => 'FSC' ]
		 );
		_m( "$id - $name: xmlからFSCプロット作成" );

		//- コマンド作成
		$fscxml = simplexml_load_file( $fn );
		$xlabels[] = trim( strtr( $fscxml[ 'xaxis' ], [ '(A-1)' => '(A)' ] ) );
		$ylabels[] = trim( $fscxml[ 'yaxis' ] );
		$name = $fscxml[ 'title' ] ?: $name ;

		$fn = _tempfn( 'dat' );
		$fn_data[] = $fn;
		$curves[] = "\"$fn\" with linespoints lw 2 title \"$name\" ";

		//- データファイル作成
		$s = '';
		foreach ( $fscxml->children() as $c ) {
			$s .= "{$c->x} {$c->y}\n";
		}
		file_put_contents( $fn, $s );
	}

	if ( $curves != [] ) {
		//- 分解能を取得
		$j = _json_load([ 'add' ]);
		$reso = $j[ 'reso' ];
		if ( $reso != '' ) {
			$curves[] = "const1,t lt rgb \"#0000d0\" lw 2 "
				. "title \"resolution by author ($reso A)\"";
		}
		//- 軸
		$x = implode( ', ', _uniqfilt( $xlabels ) );
		$y = implode( ', ', _uniqfilt( $ylabels ) );
		
		$ar = [];
		foreach( [ 30, 20, 15, 12, 10, 8, 7, 6, 5, 4, 3, 2, 1 ] as $a )
			$ar[] = "\"$a\" " . ( 1 / $a ) . ' ';
		$r = implode( ',', $ar );

		$fn_tmpimg = _tempfn( 'png' );
		//- プロット
		_gnuplot( ''
			. 'set parametric;'
			. "set xlabel '$x';"
			. "set ylabel '$y';"
			. "set yrange [0:1];"
			. ( 1 < count( $curves ) == '' ? "set nokey;" : '' )
			. "set ytics (0,0.143,0.333,0.5,1);"
			. "set mytics 5;"
			. "set xtics ( $r );"
			. "set grid;"
			. "set term png font 'Helvetica,14';"
			. "set size 1.05,1;"
			. "set output \"$fn_tmpimg\";"
			. "set trange [0:1];"
			. ( $reso == '' ? '' : 'const1=' . ( 1/ $reso ) . ';' )
			. 'plot ' . implode( ', ', $curves ) . ';'
		);

		_imgconv( $fn_tmpimg, "$dn_dest/fsc/fscl.png" );
		_imgres(  $fn_tmpimg, "$dn_dest/fsc/fscs.jpg", 'x150' );
		foreach ( $fn_data as $fn )
			_del( $fn );
		rename( $fn_tmpimg, _fn( 'fsc_check', $id ) );
	}

	//.. end of main loop
}
$o = [];
foreach ( $_dn_ext_cnt as $dn => $c ) {
	foreach ( $c as $ext => $cnt ) {
		$o[ "$dn > $ext" ] = $cnt;
	}
}
_kvtable( $o );

_end();

//. func

//.. _checkdir: ディレクトリ更新をチェック
//- 元ディレクトリのファイルリストを返す

function _checkdir( $dn ) {
	global $id;
	$dn_dest = DN_EMDB_MED . "/$id/$dn";
	$dn_src  = DN_EMDB_MR . "/structures/EMD-$id/$dn";
	
	//- 元 チェック
	$slist = [];
	foreach ( glob( "$dn_src/*" ) as $f )
		$slist[ basename( $f ) ] = filemtime( $f );

	//- 元 なし？
	if ( count( $slist ) == 0 ) {
		if ( ! is_dir( $dn_dest ) ) //- 先もなし
			return [];

		exec( "rm -rf $dn_dest" );
		_log( "$id-$dn: データが無くなった" );
		return [];
	}

	//- 元 あり？
	$fn_filelist = _fn( 'dir_info', $id, $dn );
//	_pause( $fn_filelist . ': ' .( file_exists( $fn_filelist ) ? 'あるよ' : 'ないよ'  ) );
	if ( is_dir( $dn_dest ) ) {

		//- 変わっていない？
		if ( _json_load( $fn_filelist ) == $slist ) return [];
		
		//- 変わった
		exec( "rm -rf $dn_dest" );
		_log( "$id-$dn: 更新データ" );

	} else {
		//- 新規
		_log( "$id-$dn: 新規データ" );
	}

	//- コピー/作り直し
	mkdir( $dn_dest );
	_json_save( $fn_filelist, $slist );
	return glob( "$dn_src/*" );
}
