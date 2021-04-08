omo-pdb-2: vq作成

<?php

//. init
require_once( "commonlib.php" );
require_once( "omo-common.php" );
_envset( 'situs' );

define( 'IG_ERROR', in_array( 'igerror', $argv ) );
if ( IG_ERROR ) {
	_m( 'エラー履歴をを無視して実行', 1 );
}
$errdn = DN_OMODATA. '/error';

//.. 新規データのみ、変更データは放置
//- remediation 直後など大量のデータの更新があった場合はtrueにする
define( 'DO_ONLY_NEW', $argv[1] != 'all' );
if ( DO_ONLY_NEW )
	_m( '変更エントリは無視して、新規エントリのみ対応するモード' );

//. job抽出
_line( 'リスト作成開始' );

//.. normal dep
$req = [];
$jobs = [];

foreach ( _idloop( 'pdb_pdb' ) as $fp ) {
	$name = _fn2id( $fp ). '-d';
	$jobs[] = [
		'name'	=> $name,
		'fn_gz'	=> $fp
	];
	$req[ $name ] = true;
	_cnt( 'dep' );
}

//.. normal asb
foreach ( glob( DN_FDATA. '/pdb/asb/*.gz' ) as $fp ) {
	//- ファイル名は"100d.pdb1.gz"
	$name = strtr( basename( $fp, '.gz' ), [ '.pdb' => '-' ] );
	if ( _inlist( $name, 'identasb' ) ) continue;

	$jobs[] = [
		'name'	=> $name ,
		'fn_gz'	=> $fp ,
	];
	$req[ $name ] = true;
	_cnt( 'asb' );
}

//.. large dep
foreach ( _idlist( 'large' ) as $id ) {
	$name = "$id-d";
	$jobs[] = [
		'name'	=> $name ,
		'fn_gz'	=> _fn( 'pdb_mmcif', $id ) ,
	];
	$req[ $name ] = true;
	_cnt( 'large-dep' );
}

//.. large asb
foreach ( glob( DN_FDATA. '/large_structures_asb/*.cif.gz' ) as $fp ) {
	$name = strtr( basename( $fp, '.cif.gz' ), [ 'assembly' => '' ] );
	if ( _inlist( $name, 'identasb' ) ) continue;
	$jobs[] = [
		'name'	=> $name ,
		'fn_gz'	=> $fp ,
	];
	$req[ $name ] = true;
	_cnt( 'large-asb' );
}
_cnt();

shuffle( $jobs );
_m( 'リスト作成完了 全データ数: '. count( $jobs ) );
//die( count( $jobs ). ' hoge' );


//. main
_line( 'main' );
foreach ( $jobs as $job ) {
	$fn_gz = $name = '';
	extract( $job );
	_count( 5000 );

	//.. 代用ファイル
	$flg_altfile = false;
	if ( file_exists( $f = _fn( 'altmodel', $name ) ) ) {
		if ( filesize( $f ) > 100 ) {
			$flg_altfile = true;
			$fn_gz = $f;
		}
	}

	//.. ファイル名
	$fn_vq = [
		'30' => _fn( 'pdb_vq30', $name ),
		'50' => _fn( 'pdb_vq50', $name )
	];
	
	//- ONLY_NEWモードだったら新旧関係なし、ファイルが存在したらやらない
	if ( DO_ONLY_NEW && file_exists( $fn_vq[30] ) && file_exists( $fn_vq[50] ) ) continue; 

	if ( _newer( $fn_vq[30], $fn_gz ) && _newer( $fn_vq[50], $fn_gz ) ) continue;
	_del( $fn_vq[30], $fn_vq[50] );

	//.. エラー？
	$fn_err = "$errdn/$name.txt";
	if ( file_exists( $fn_err ) && ! DO_ONLY_NEW )
		if ( filemtime( $fn_gz ) > filemtime( $fn_err ) )
			_del( $fn_err ); //- 古いエラー消去

	if ( ! IG_ERROR ) {
		$errcnt = file_exists( $fn_err )
			? file_get_contents( $fn_err )
			: 0
		;
		if ( $errcnt > 5 ) continue; //- 5回以上失敗したデータはやらない
	}

	if ( _proc( "omopdb-vq-$name" ) ) continue;

	//.. unzip
	$fn_pdb = _tempfn( 'pdb' );
	copy( $fn_gz, "$fn_pdb.gz" );
	exec( "gunzip -f $fn_pdb.gz" );

	_m( $name, 1 );
	if ( $flg_altfile )
		_m( "代用ファイル使用" );

	foreach ( $fn_vq as $num => $fn_out ) {
		_m( "vq作成開始: $num" );
//		exec( "qpdb $fn_pdb $fn_out <". $cmdfn[ $num ] );

		_situs( "qpdb $fn_pdb $fn_out", [ 2, 1, $num, 1, 1 ] );
		if ( ! file_exists( $fn_out ) ) //- 1と答えるとうまくいくパターン?
			_situs( "qpdb $fn_pdb $fn_out", [ 1, 1, $num, 1, 1 ] );


		//- エラーチェック
		$err = true;
		if ( file_exists( $fn_out ) )
			if ( filesize( $fn_out ) > 100 )
				$err = false;

		if ( $err ) {
			if ( IG_ERROR ) {
				_m( "失敗", -1 );
			} else {
				++ $errcnt;
				_m( "失敗 ( $errcnt 回目)", -1 );
				file_put_contents( $fn_err, $errcnt );
			}
			_del( $fn_vq[30], $fn_vq[50] );
			break; //- 30が失敗なら、50はやらない
		}
	}

	if ( file_exists( $fn_vq[30] ) && file_exists( $fn_vq[50] ) ) {
		_log( "$name: VQ作成 成功" );
		_del( $fn_err );
	}

	_del( $fn_pdb ); //- tempfile削除
	_proc();
}

//. 取り消しデータ消去
_delobs_misc( 'pdb_vq30', $req );
_delobs_misc( 'pdb_vq50', $req );
_end();
