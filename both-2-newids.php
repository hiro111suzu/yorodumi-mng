new ID list をダウンロード
データを作成
出力: data/emn/newdata.json , data/emn/latestdata.json

<?php
die('実行停止');
//- samlldbを作成
//. init
require_once( "commonlib.php" );

define( 'FN_FOR_NEWS'   , DN_DATA. '/emn/newdata.json' );
define( 'FN_FOR_SEARCH' , DN_DATA. '/emn/latestdata.json' );

define( 'DN_DEST', DN_PREP . '/newids' );
_mkdir( DN_DEST );

//. 新規IDリスト rsync

file_put_contents( $fn_list = _tempfn( 'txt' ), <<<EOD
all_obsolete_pdbid.txt
latest_new_pdbid.txt
latest_updated_pdbid.txt

latest_new_header.txt
latest_new_map.txt
latest_updated_map.txt
EOD
);

_rsync([
	'title' => '新規・更新データID一覧取得' ,
	'from'	=> [ 'XML/pdbmlplus/', 'lvh2' ] ,
	'to'	=> DN_DEST ,
	'opt'	=> "--files-from=$fn_list" ,
//	'dryrun' => true ,
]);
_del( $fn_list );

//. 集計
_line( '集計' );

$data = [];
//.. EMDB

$data = [
	'newxml' => _a2a( $ids_newxml = _file( DN_DEST. '/latest_new_header.txt'  ) ) ,
	'newstr' => _a2a( $ids_newmap = _file( DN_DEST. '/latest_new_map.txt'     ) ) ,
	'upd'    => _a2a( $ids_update = _file( DN_DEST. '/latest_updated_map.txt' ) )
];

function _a2a( $ar ) {
	$ret = [];
	foreach ( (array)$ar as $i )
		$ret[] = "emdb-$i";
	return $ret;
}

//.. PDB
//- PDB EMデータのみ抽出
$newpdb = _file( DN_DEST. "/latest_new_pdbid.txt" );
$updpdb = _file( DN_DEST. "/latest_updated_pdbid.txt" );
$count_new_epdb = 0;
$count_update_pdb = 0;
foreach ( _idlist( 'epdb' ) as $id ) {
	if ( in_array( $id, $newpdb ) ) {
		$data[ 'newstr' ][] = "pdb-$id";
		++ $count_new_epdb;
	}
	if ( in_array( $id, $updpdb ) ) {
		$data[ 'upd' ][] = "pdb-$id";
		++ $count_update_pdb;
	}
}

//.. 表示
_m( $msg = ''
	. 'EMDB 新規マップ  : ' . count( $ids_newmap ) . " 件\n"
	. 'EMDB 新規xml     : ' . count( $ids_newxml ) . " 件\n"
	. 'EMDB 更新        : ' . count( $ids_update ) . " 件\n"
	. 'PDB新規 (EM/all) : ' . $count_new_epdb   .' / '. count( $newpdb ) . " 件\n"
	. 'PDB更新 (EM/all) : ' . $count_update_pdb .' / '. count( $updpdb ) . " 件\n"
);

//. 前回データ
//.. 次の水曜（木曜日と金曜日は、前の水曜）
$daysec = 60 * 60 * 24; //- 一日の秒数
$w = _youbi();
$t = time();
if ( $w == '木' or $w == '金' )
	$t = $t - $daysec * 3;

while ( date( 'w', $t ) != 3 )
	$t += $daysec;

$next_wed = date( 'Y-m-d', $t );

_m( "次の水曜: $next_wed" );

//.. 前回データ
$data_for_news = _json_load( FN_FOR_NEWS );
//- 前回のと比較
$last_newstr = [];
foreach ( array_reverse( $data_for_news ) as $k => $v ) {
	if ( $k == $next_wed ) continue;
	$last_newstr = $v['newstr'];
	break;
}


//. newstr 並べ直し
$ar = [];
foreach ( $data[ 'newstr' ] as $did ) {
	if ( in_array( $did, $last_newstr ) ) {
		_problem( "$did: not new" );
		continue;
	}
	list( $db, $id ) = explode( '-', $did );
	$pmid = _json_load2([ $db. '_add', $id ] )->pmid;

	if ( ! $pmid ) {
		if ( _mng_conf( 'pap_same_as', $id ) ) {
			$pmid = 'zzzz'. _mng_conf( 'pap_same_as', $id );
		} else {
			$o = new cls_entid( $did );
			$pmid = 'zzz' . md5( $db == 'pdb'
				? _json_load2([ 'epdb_json', $id ])->citation[0]->title
				: _json_load2([ 'emdb_json', $id ])
					->crossreferences->primary_citation->journal_citation->title
			);
		}
	}
	$ar[ $did ] = $pmid . $did;
}
asort( $ar );
$data[ 'newstr' ] = array_keys( $ar );
//print_r( $ar );

//. 保存
_line( '保存' );

//.. 検索用データ
_comp_save( FN_FOR_SEARCH, $data );

//.. ニュース用データ
print_r( $data[ 'newstr' ] ); //[ 'newstr' ];

$data_for_news[ $next_wed ] = $data;
if ( _comp_save( FN_FOR_NEWS, array_slice( $data_for_news, -10 ) ) ) {
	_log( $msg );
	foreach ( $data as $n => $v )
		_log( "$n: " . implode( ', ', $v ) );
}
_end();

