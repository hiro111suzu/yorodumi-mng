omo-pdb-3: DBへロード

<?php

//. init
require_once( "commonlib.php" );
require_once( "omo-common.php" );

$mode = 's';
//if ( in_array( 's' , $argv ) ) $mode = 's';
if ( in_array( 'ss', $argv ) ) $mode = 'ss';
if ( in_array( 'k' , $argv ) ) $mode = 'k';

define( 'MODE', $mode );
_line( "mode", MODE );
	
$fn_data_time	= DN_OMODATA . "/datatime_$mode.json.gz";
if ( FLG_REDO )
	_del( $fn_data_time );
$data_time	= _json_load( $fn_data_time );

$ent_exists = [];

//- sas_mid2id
$sas_mid2id = _json_load2( DN_DATA. '/sas/subdata.json.gz' )->mid;

//. prep DB
$sqlite = new cls_sqlw([
	'fn'	=> "profdb_$mode", 
	'new'	=> FLG_REDO ,
	'cols'	=> [
		's' => [
			'id	UNIQUE' ,
			'data	BLOB'
		] ,
		'ss' => [
			'id	UNIQUE' ,
			'data	BLOB' ,
			'pca1	REAL' ,
			'pca2	REAL' ,
			'pca3	REAL'
		],
		'k' => [
			'id	UNIQUE' ,
			'data	BLOB' ,
			'pca1	REAL' ,
			'pca2	REAL' ,
			'pca3	REAL' ,
			'db' ,
			'kw	COLLATE NOCASE' ,
			'compos BLOB'
		]
	][ MODE ] ,
	'indexcols' => [
		's'  => [ 'id' ] ,
		'ss' => [ 'id', 'pca1', 'pca2', 'pca3' ] ,
		'k'  => [ 'id', 'pca1', 'pca2', 'pca3', 'db' ]
	][MODE]
]);

//. load
//.. sas

foreach ( _idloop( 'prof_sas' ) as $fn ) {
	$id = _fn2id( $fn );
	_readload( $fn, "s$id",  's',
		MODE == 'k' ? _fn( 'sas_kw', $sas_mid2id->$id ) : ''
	);
}

//.. PDB
foreach ( _idloop( 'prof_pdb' ) as $fn ) {
	$id_asb = _fn2id( $fn );
	$fn_kw = $cmp = '';
	if ( MODE == 'k' ) {
		list( $id, $asb ) = explode( '-', $id_asb );
		$fn_kw = _fn( 'pdb_kw', $id );
		$cmp = _json_load2( _fn( 'compinfo', $id ) )->$asb;
	}
	_readload( $fn, $id_asb, 'p', $fn_kw, $cmp );
}

//.. EMDB
foreach ( _idloop( 'prof_emdb' ) as $fn ) {
	$id = _fn2id( $fn );
	_readload( $fn, "e$id", 'e',
		MODE == 'k' ? _fn( 'emdb_kw', $id ) : ''
	);
}

//. 廃止データ 消去
_line( '廃止データ 消去' );

//- DB中のID一覧取得
$ids = $sqlite->getlist( 'id' );

//- 無いデータは削除
foreach ( $ids as $id ) {
	if ( $ent_exists[ $id ] ) continue;
	$sqlite->del( "id='$id'" );
	_log( "$id: 廃止データ削除" );
}

//- 登録構造と同じアセンブリー構造を削除
foreach ( _idlist( 'identasb' ) as $id ) {
	if ( ! $ent_exists[ $id ] ) continue;
	$sqlite->del( "id='$id'" );
	_log( "$id: 同一アセンブリデータ削除" );
}

//. DB後処理
_line( '後処理' );
$sqlite->end();

//. data time保存
_m( 'タイムスタンプファイル保存開始' );
_json_save( $fn_data_time, $data_time );
_end();

//. func: _readload
function _readload( $fn, $id, $db, $fn_kw = '', $compos = [] ) {
	global $sqlite, $data_time, $ent_exists;
	_count( 5000 );
	$ent_exists[ $id ] = true;
	$time = filemtime( $fn );

	//- タイムスタンプ比較
	if ( $data_time[ $id ] == $time ) return;

	//.. プロファイルデータ読み込んでバイナリ化
	$bin ='';
	$json = _json_load( $fn );
	foreach ( $json as $prof ) {
		if ( is_array( $prof ) ) {
			//- porf
			foreach ( _sh( $prof ) as $val ) {
				$bin .= pack( 'f', $val );
			}
		} else {
			//- pca
			$bin .= pack( 'f', $prof );
		}
	}

	//.. set
	$data = [ $id, $bin ];
//	$data = [ $id, 'bin' ];
	if ( MODE != 's' ) {
		$data[] = $json[3];
		$data[] = $json[4];
		$data[] = $json[5];
	}
	if ( MODE == 'k' ) {
		$data[] = $db;
		$data[] = _kwfile2str( $fn_kw );
		$b = '';
		if ( $compos != [] ) {
			foreach ( [ 'a', 'b', 'n', '1', '2' ] as $type ) {
				$b .= pack( 'S', round( $compos->$type * 65535 ) );
//				_pause( "$id-$type: ". dechex( round( $compos->$type * 65535 ) ) );
			}
		}
		$data[] = $b;
	}
//	_die( $data );
	$sqlite->set( $data );
//	_log( "$id: データ書き込み" );
	$data_time[ $id ] = $time; 
}

//. func: _sh: シュリンク
function _sh( $in ) {
	if ( MODE == 'full' )
		return $in;

	$cnt = count( $in );

	//- 何個に一個残すか
	$step = 3;
	if ( $cnt > 400  ) $step = 4;
	if ( $cnt > 1200 ) $step = 12;
	if ( MODE != 's' ) $step *= 2;

	$out = [];
	$sum = 0;
	foreach ( $in as $i => $val ) {
		$sum += $val;
		if ( $i % $step > 0 ) continue;
		$out[] = $sum / $step;
		$sum = 0;
	}
	return $out;
}
