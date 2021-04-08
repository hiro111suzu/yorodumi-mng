<?php
//. init
require_once( "commonlib.php" );

ini_set("pcrh.backtrack_limit", 10000000); // デフォルトは100000  
ini_set("pcrh.recursion_limit", 10000000); // デフォルトは100000

define( 'MODE_CODE', [
	"'representation': 'solid'"	=> 'solid' ,
	"'Volume_Color_State'"		=> 'vol' ,
	"'Cylinder_Color_State'"	=> 'cyl' ,
	"'Height_Color_State'"		=> 'hei' ,
	"'Radial_Color_State'"		=> 'rad' ,
	"'pdbHeaders': [{"			=> 'pdb'
]);

$ids_noslice = new cls_blist( 'mov_no_slice', 'スライスモーション無視リスト' );

define( 'IDCONV_SPLIT2LARGE', _json_load( DN_PREP. '/split2large.json' ) );
define( 'ID_CONV_REPLACED'  , _json_load( DN_DATA. '/pdb/ids_replaced.json.gz' ) );

//. pdb-jmol movie prep
//- Jmolによる当てはめムービー
//-  => EMDBエントリの情報としても保存
$jmol_fitmov_info = [];
foreach ( _json_load( FN_PDB_MOVINFO ) as $pid => $v ) {
	foreach ( $v as $n => $v2 ) {
		$s = $v2[ 'name' ];
		if ( substr( $s, 0, 2 ) != 'jm' ) continue;
		$jmol_fitmov_info[ _numonly( $s ) ][] = $pid;
	}
}

//. start main loop
foreach( _idlist( 'emdb' ) as $emdb_id ) {
	_count('emdb');
	if ( ! file_exists( _fn( 'mapgz', $emdb_id ) ) )
		continue;
	$fn_movinfo = _fn( 'movinfo', $emdb_id );

	//- やりなおし？
	if ( FLG_REDO ) _del( $fn_movinfo );

	$data = file_exists( $fn_movinfo )
		? _json_load( $fn_movinfo )
		: []
	;
	$movnum_list = [];

//.. chimeraセッション毎のループ
	foreach ( range( 1, 20 ) as $mov_num ) {
		//... 事前確認
		//- セッションファイル名
		$fn_py = _fn( 'session', $emdb_id, $mov_num );
		if ( ! file_exists( $fn_py ) )
			$fn_py = _fn( 'session-old', $emdb_id, $mov_num );

		//- ファイルが無い、
		if ( ! file_exists( $fn_py ) ) {
			unset( $data[ $mov_num ] );
			continue;
		}

		$movnum_list[] = $mov_num;
		$mov_id = "$emdb_id-$mov_num";

		//- タイムスタンプチェック
		if ( $data[ $mov_num ][ 'filetime' ] == filemtime( $fn_py ) ) {
			$flg = false;
			//- 不明点があれば変更なしでも再実行
			if ( $data[ $mov_num ][ 'no_slice' ] ) $flg = true;
			if ( $data[ $mov_num ][ 'mode' ] == 'unk' ) $flg = true;
			if (
				$data[ $mov_num ][ 'mode' ] == 'pdb' &&
				! $data[ $mov_num ][ 'fittedpdb' ]
			) $flg = true;
			if ( ! $flg ) continue;
		}

		//... セッション情報取得
		_line( 'セッション情報取得', "$emdb_id-$mov_num" );

		//... マップ ファイル名フルパス -> 相対パス
		$path = _fn( 'emdb_med', $emdb_id ) . '/';
		$marem_path = strtr( $path, [ 'novdisk2' => 'mardisk2' ] );
//		$zbox2_path = strtr( $novem_path, [ '/novdisk2' => 'n:', '/' => '\\\\' ] );
		$pdbbundle_path = '/mardisk2/db/emnavi/data/_pdbbundle/';

		$in = file_get_contents( $fn_py );
		$str_session = strtr( $in, [
			$marem_path => '',
//			$zbox2_path => '',
			$pdbbundle_path => ''
		]);

		if ( $in != $str_session ) {
			file_put_contents( $fn_py, $str_session );
			_log( "$emdb_id-$mov_num: マップファイル名を相対パスに修正" );
		}
		unset( $in );

		//... データ書き込み開始
		$data[ $mov_num ] = [ 'filetime' => filemtime( $fn_py ) ];
		$mode = 'unk'; //- モード

		//... voxel size
		preg_match_all( '/\'xyz_step\': \( ([0-9\.]+), ([0-9\.]+), ([0-9\.]+), \)/' ,
			$str_session, $out );
		if ( $out[1][0] != '' or $out[2][0] != '' or $out[3][0] != '' ) {
			$data[ $mov_num ][ 'apix x' ] = $out[1][0];
			$data[ $mov_num ][ 'apix y' ] = $out[2][0];
			$data[ $mov_num ][ 'apix z' ] = $out[3][0];
		}

		//... 当てはめ PDBモデル情報
		$out = [];
		$str_pdbmatch = $str_session;
		$num_base64 = 0;
		while ( true ) {
			if ( ! _instr( 'base64.b64decode', $str_pdbmatch ) ) break;
			list( $s1, $s2 ) = explode( "base64.b64decode('", $str_pdbmatch, 2 );
			list( $b, $s3 ) = explode( "'", $s2, 2 );
			$str_pdbmatch = $s1. "\n". base64_decode( $b ). "\n". $s3;
			++ $num_base64;
		}
		if ( 0 < $num_base64 )
			_m( 'base64テキストあり: ' . $num_base64 );

		$ids = [];
		foreach ([
			'/\'([1-9][0-9a-zA-Z]{3})\', \'PDBID\'/' ,	//- '1abc, 'PDBID'
			'/\'([1-9][0-9a-zA-Z]{3})\.pdb1[\.|\']/' ,	//- '1abc.pdb1.
			'/\'DBREF  ([1-9][0-9a-zA-Z]{3}) /' ,		//- 'BDREF 1abc 
			'/\'([1-9][0-9a-zA-Z]{3})\.ent\'/' ,		//- '1abc.ent'
			'/([1-9][0-9a-zA-Z]{3})\.ent\.gz/' ,		//- 1abc.ent.gz'
			'/([1-9][0-9a-zA-Z]{3})\.cif\.gz/' ,		//- 1abc.cif.gz'
			'/\'([1-9][0-9a-zA-Z]{3})\-pdb-bundle/' ,			//- 1abc-pdb-bundle'
			'/\'name\': \(1, \'([1-9][0-9a-zA-Z]{3})\', /' ,	//- 'name': (1, '1abc.pdb'
			'/\'name\': \(1, \'([1-9][0-9a-zA-Z]{3})\.pdb\', /' , //- 
			'/\'name\': \(1, \'([1-9][0-9a-zA-Z]{3})\.cif\', /' ,
		] as $pattern ) {
			$match = _reg_match( $pattern, $str_pdbmatch )[1];
			if ( ! $match ) continue;
			foreach ( $match as $s ) {
				$s = strtolower( $s );
				$ids[] = IDCONV_SPLIT2LARGE[ $s ] ?: ID_CONV_REPLACED[ $s ][0] ?: $s;
			}
		}
		unset( $str_pdbmatch );

		//- マニュアルで指定
		$conf = _mng_conf( 'mov_fitpdb', $mov_id );
		if ( $conf )
			$ids = array_merge( $ids, explode( ' ', $conf ) );
		
		if ( $ids ) {
			sort( $ids );
			$ids = array_unique( $ids );
			$data[ $mov_num ][ 'fittedpdb' ] = $ids;
			$mode = 'pdb';
			_m( "fitted PDB-ID: " . implode( ',', $ids ) );
		}

		//... モード
		$mode = _mng_conf( 'mov_mode', $mov_id ) ?: $mode;
		if ( $mode == 'unk' ) {
			foreach ( MODE_CODE as $s => $n ) {
				if ( ! _instr( $s, $str_session ) ) continue;
				$mode = $n;
				break;
			}
		}
		if ( $mode == 'unk' )
			_problem(  "$emdb_id: movie-#$mov_num: モード不明" );
		if ( $mode == 'pdb' && ! $ids )
			_problem(  "$emdb_id: movie-#$mov_num: 当てはめPDBIDが不明" );

		//... 断面のモーションの無いムービーチェック
		if ( $mode != 'solid' && 
			! _instr( 'session', $fn_py ) && 
			! $ids_noslice->inc( $mov_id )
		) {
			if ( ! _instr( "'pos1'", $str_session ) ) {
				_problem( "$emdb_id: movie-#$mov_num: 断面モーションがない" );
				$data[ $mov_num ][ 'no_slice' ] = 1;
			}
		}

		//... データ整理、書き込み
		//- mode
		$data[ $mov_num ][ 'mode' ] = $mode;

		//- threshold
		preg_match_all( '/\'surface_levels\': \[ *([e0-9\.\-]+),? *\]/' , $str_session, $out );
		if ( $out[1][0] != '' && $mode != 'solid' )
			$data[ $mov_num ][ 'threshold' ] = round( (float)$out[1][0], 9 );

		//- map name
		preg_match( "/'path': '(.+?)'/" , $str_session, $hit );
		$data[ $mov_num ][ 'map name' ] = strtr( $hit[1], [
			_fn( 'emdb_med', $emdb_id ) . '/' => '' ,
			'./'=>'' 
		]);

		//- output
		_log( "$emdb_id-$mov_num: movjson情報取得" );
	}

	//... チェック
	if ( $movnum_list ) {
		if ( $movnum_list != range( 1, count( $movnum_list ) ) ) {
			_problem(
				"emdb-$emdb_id: セッション番号に空きがある " 
				. _imp( $movnum_list  )
			);
		}
	} else {
		_problem( "emdb-$emdb_id: セッションがない" );
	}

//.. Jmol fit ムービーのループ
	if ( $jmol_fitmov_info[ $emdb_id ] )
		$data[ 'jmfit' ] = $jmol_fitmov_info[ $emdb_id ];
	else
		unset( $data[ 'jmfit' ] );

//.. end of loop
	if ( count( $data ) > 0 ) {
		ksort( $data );
		_comp_save( $fn_movinfo, $data, 'nomsg' );
	} else {
		_del( $fn_movinfo );
	}
}

_end();

