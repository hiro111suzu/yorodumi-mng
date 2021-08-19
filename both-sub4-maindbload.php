メインDB書き込み
----------
<?php
//. misc. init
require_once( "commonlib.php" );
define( 'ID2CATEG', _json_load( DN_DATA. '/emn/id2categ.json' ) );
define( 'DID2PMID', _json_load( DN_PREP. '/pap/did2pmid.json.gz' ) );

//.. DBカラム情報準備
$tabledata = _json_load( DN_DATA. "/emn/tabledata.json" );
$columns = [];
$colnames = [];
$indexcols = []; //- インデックス作成するカラム

foreach ( $tabledata as $col => $ar ) {
	$m = $ar[ 'mode' ];	//- モード、UNIQUE / INTEGER / REAL
	if ( $ar[ 'count' ] ) //- 統計に使うなら、大文字小文字関係なしにする
		$m .= ' COLLATE NOCASE';

	$colnames[] = $col;
	$columns[] = $col . ( $m == '' ? '' : " $m" );

	//- 統計に使うカラムかつ、複数値を持たないカラム、インデックス作成
	if ( $ar[ 'count' ] and !$ar[ 'multi' ] )
		$indexcols[] = $col;
}

//.. DB準備
$sqlite = new cls_sqlw([
	'fn'		=> 'main' ,
	'cols'		=> $columns ,
	'indexcols' => $indexcols ,
	'new'		=> true
]);

//.. latest
//. main loop
$ex = [];
//$dbtime = 0;
foreach ( _idloop( 'maindb_json' ) as $fn ) {
	_count( 500 );
	$ex[ basename( $fn, '.json' ) ] = true;
//	if ( ( filemtime( $fn ) < $dbtime ) && ! FLG_REDO ) continue;

	$j = _json_load2( $fn );
	$v = [];
	$auth = array_filter( explode( '|', strtolower( $j->authors ) ) );
	sort( $auth );
	foreach ( $colnames as $c ) {
		if ( $c == 'categ' )
			$v[] = ID2CATEG[ $j->id ];
		else if ( $c == 'sort_sub' )
			$v[] = $j->pmid ?:
				'00'. ( DID2PMID[ $j->db_id ] ?: implode( '|', $auth ) )
			;
		else 
			$v[] = $j->$c;
	}

	$sqlite->set( $v );
}

foreach ( $sqlite->getlist( 'db_id' ) as $did ) {
	if ( $ex[ $did ] ) continue;
	$sqlite->del( "db_id = \"$did\"" );
	_m( "$did: 取り消しデータを削除" );
}

//. end
$sqlite->end();
