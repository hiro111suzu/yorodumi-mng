<?php
/*
毎回作る
*/

//. init
require_once( "pap-common.php" );

define( 'FN_NGIDS',     DN_PREP. '/pap/ng_papids.txt' );
define( 'FN_EM_PAPERS', DN_DATA. '/emn/empapers.json' );

//. 先週版取得

if ( in_array( _youbi(), [ '土', '日', '月', '火' ] ) ) {
	_line( '公開データからNG-ID取得' );
	$ids = [];
	foreach ( json_decode(
		file_get_contents( 'https://pdbj.org/emnavi/data/emn/empapers.json' )
	)  as $j )
		$ids[] = $j->pmid;
	if ( ! $ids ) {
		_problem( '取得失敗' );
	} else {
		_m( '取得:'. _imp( $ids ) );
		//- もうマージしてある?
		$ngids = _file( FN_NGIDS );
		if ( in_array( $ids[0], $ngids ) ) {
			_m( 'すでにマージされている' );
		} else {
			$ngids = array_merge( $ids, $ngids  );
			sort( $ngids );
			_save( FN_NGIDS, array_slice( $ngids, -50 ) );
			_m( 'マージ完了' );
		}
	}
}

//. トップページに来ないように、マニュアル指定
$sqlite = new cls_sqlite('pap' );
$ngids = '';
$ng = [];
foreach( _file( FN_NGIDS ) as $i ) {
	$i = trim( $i );
	if ( ! $i ) continue;
//	_m( "$i: ". is_numeric( $i ) ? 'numeric' :'non numeric' );
//	_m( "無視するID: $i" );
	$ngids .= " AND NOT pmid = '$i'";
	$ng[] = $i;
}
$e = $sqlite->q(
	"SELECT pmid, journal, data FROM main "
//	. "where if >= 10 AND emflg is 1 $ngids ORDER BY score DESC LIMIT 5" 
	. "where if >= 1 AND emflg is 1 $ngids ORDER BY score DESC LIMIT 100" 
)->fetchAll( PDO::FETCH_ASSOC );

//. 選考
$oid = new cls_entid;
$out = [];
//- 非公開EMDBデータのみの文献は除く
foreach ( $e as $a ) {
	$i = trim( $a['pmid'] );
	if ( substr( $i, 0, 1 ) == '_' ) continue;
	$flg = false;
	foreach ( json_decode( $a['data'] )->ids as $id ) {
		$oid->set( $id );
		if ( $oid->db != 'pdb' && ! $oid->ex_mov() ) continue;
		$flg = true;
		break;
	}
	if ( !$flg ) continue;
	$out[] = $a;
	if ( count( $out ) == 5 ) break;
}

_comp_save( FN_EM_PAPERS, $out );

//. DB終了

_end();
