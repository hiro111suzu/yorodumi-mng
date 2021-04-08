<?php
require_once( "wikipe-common.php" );
//. init
$o_sqlite_ja_title = new cls_sqlite( FN_DB_E2J );

define( 'MAXNUM_OLD', $argv[1] ?: 100 );
_m( '古いファイルを再ダウンロードする数: '. MAXNUM_OLD );
define( 'OLDER_THAN_30_DAYS', time() - 30 * 24 * 60 * 60  );


//. term 一覧取得
$terms = [];
foreach ([ 'chem', 'taxo', 'misc' ] as $type ) {
	foreach ( _json_load( DN_WIKIPE. "/regist_$type.json.gz" ) as $k => $v ) {
		$t = $v == '@' ? $k : $v ;
		if ( TSV_ANNOT['ng_title'][$t] ) {
			_problem( "NG title \"$t\" from \"$type\"" );
		}
		$terms[ $t ] = true;
	}
}
//_pause( $terms[ 'Euarchontoglires' ] ? 'hoge': 'fuga');
//. ダウンロードループ
_m( count( $terms ) . ' terms' );
$fn_required = [];
$cnt_old = 0;
$rest_old = 0;

foreach ( array_keys( $terms ) as $et ) {
	_count( 1000 );
	_cnt( 'total' );

	$jt = TSV_ANNOT['e2j'][$et] ? TSV_ANNOT['e2j'][$et] : _get_from_db( 'e2j', $et );
	$fn = _fn_wkp_json( $et );
	$fn_required[ $fn ] = true;
	$j = _json_load2( $fn );

	//.. ダウンロードするかどうか
	if ( !$j ) {
		//- 新しい
		_cnt( 'new' );
		_m2( "new: $et" );
	} else if ( $et == $j->et && $jt == $j->jt ) {
		//- タイトルは変わっていない
		$time_out = filemtime( $fn ); 
		if ( $time_out < OLDER_THAN_30_DAYS ) {
			_cnt( 'not changed (old)' );
			//- 古い
			if ( $cnt_old < MAXNUM_OLD ) {
				//- まだ制限内
				_m( "$et: " . date( 'Y-m-d', $time_out ). " old file ($cnt_old)", 'green' );
				++ $cnt_old;
			} else {
				//- 制限を超えた
				++ $rest_old;
				continue;
			}
		} else {
			_cnt( 'not changed' );
			continue;
		}
	} else {
		//- タイトルが変更
		_m( 'changed'. $et );
		if ( $et != $j->et )
			_m2( "$et => ". $j->et );
		if ( $jt != $j->jt )
			_m2( "$jt => ". $j->jt );
		_cnt( 'changed' );
	}

	//.. ダウンロード
	$a = array_filter([
		'et'  => $et,
		'ea'  => _get_wikipe_abst( $et ),
		'jt'  => $jt,
		'ja'  => _get_wikipe_abst( $jt, 'ja' ),
	]);
	if ( $a == [] ) {
		_problem( 'ダウンロードエラー'. $et );
		_cnt('error'); 
	} else {
		_json_save( $fn, $a );
		_cnt( 'downloaded' );
		_kvtable( $a );
	}

	//- stop?
	if ( file_exists( $fn = DN_PROC . '/stop' ) ) {
		_m( 'stopファイルがあるので中止'. $fn, 'green' );
		break;
	}
}
_cnt();
	if ( file_exists( $fn = DN_PROC . '/stop' ) ) die();


//. チェック
_line( 'jsonファイルチェック' );
foreach ( glob( DN_WIKIPE. '/json/*.json.gz' ) as $fn ) {
	_count( 1000 );
	_cnt('total');
	if ( ! $fn_required[ $fn ] ) {
		_m( "$fn: いらないjson?", 'red' );
		rename( $fn, strtr( $fn, [ '/json/' => '/obs/' ] ) );
		_cnt('obs');
		continue;
	}

	$json = _json_load2( $fn );
	if ( ! $json->et && ! $json->jt ) {
		_del( $fn );
		_m( '中身なし: '. basename( $fn, '.json.gz' ) );
		_cnt('empty');
		continue;
	}
	if ( ! $json->ea && ! $json->ja ) {
//		_del( $fn );
		_m( '概要がない: '. basename( $fn, '.json.gz' ), '.blue' );
		_del( $fn );
//		_pause( '消した' );
		_cnt('no eng abst');
		continue;
	}
	if ( TSV_ANNOT['ng_title'][ $json->et ] ) {
		_del( $fn );
		_m( 'NGタイトル: "'. $json->et. '"'. basename( $fn, '.json.gz' ) );
		_cnt('NG title');
		continue;
	}

}
_cnt();
