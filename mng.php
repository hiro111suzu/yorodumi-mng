EMNマネージメインスクリプト
<?php
require_once( "commonlib.php" );
define( 'ARG', $argv[1] );
define( 'START_TIME_MNG', time() );
define( 'TP_FN_TASK_RUNNING', DN_TEMP. '/hist/running-' );
define( 'FN_STOP', DN_PROC. '/stop' );

//. 単発スクリプト
if ( substr( ARG, -4 ) == '.php' and file_exists( __DIR__ . '/' . ARG ) ) {
	_php( ARG );
	die();
}

//. task list
//.. catalog

define( 'TASK_CATALOG', [
'startud' => [
	'mng emdb' ,
	'mng allpdb' ,
	'mng epdb' ,
	'mng both' ,
//	'mng unp' ,
	
//	'sendlog' ,
	
	'mng pdbimg' ,
//	'mng nmr' ,
	'mng mov' ,
	'mng pmo' ,
	'mng postm' ,
	'mng ab' ,
	'mng chem' ,
	'mng unichem' ,
	'mng bird' ,
	'mng branch' ,
	'mng wikipe' ,
	'mng unp' ,
	'mng met' ,
	'mng pap',
	'mng dbdic' ,

	'mng vapros' ,

	'mng final'  ,
],

'mov' => [
	'movie-1' ,
	'movie-b' ,
	'movie-1' ,
	'movie-2' ,
	'movie-3' ,
	'movie-4' ,

	'polygon-1' ,
	'polygon-2' ,
	'polygon-3' ,

	'movie-5' ,
	'movie-6' ,
//	'movie-7' ,
	'both-1' ,
	'hwork-1' ,
],

'polygon' => [
	'polygon-1' ,
	'polygon-2' ,
	'polygon-3' ,
],


'ana' => [
	'analysis-1' ,
	'analysis-3' ,
],

'pub' => [
	'emdb-03' ,
	'epdb-2' , //- add xml
	'both-3' , //- download pubmed
	'both-4' , //- maindb
	'both-5' , //- category
//	'both-7' , //- pubmedid2id' ,
	'both-sub1-pubmed-json' ,
	'sub-check-pubmedid' ,
],
'pmo' => [
	'both-8-make-fitlist' ,
	'epdb-3-assembly' ,
	'epdb-4-makeimage' ,
	'epdb-5-movie-rec' ,
	'epdb-6-movie-enc' ,
	'epdb-7-movielist' ,
	'both-1' ,
],
'pmo2' => [
	'both-8-make-fitlist' ,
	'epdb-3-assembly' ,
	'epdb-4-makeimage' ,
//	'epdb-5-movie-rec' ,
//	'epdb-6-movie-enc' ,
	'epdb-7-movielist' ,
	'both-1' ,
],

'chem' => [
	'chem-1' ,
	'chem-2' ,
	'chem-3' ,
	'chem-4' ,
	'chem-5' ,
	'chem-6' ,
	'chem-7' ,
	'chem-8' ,
],
'unichem' => [
	'unichem-1' ,
	'unichem-2' ,
],

'allpdb' => [
	'allpdb-01' ,
	'allpdb-02-' ,
	'allpdb-02b' ,
	'allpdb-03' ,
	'allpdb-04' ,
	'allpdb-05' ,
	'allpdb-06' ,
//	'allpdb-07' ,
	'allpdb-08' ,
	'allpdb-10' ,
	'plus-01',
],
'pdbimg' => [
	'pdbimg-1' ,
	'pdbimg-2' ,
	'pdbimg-3' ,
	'pdbimg-4' ,
],
'omopdb' => [
	'omopdb-1' ,
	'omopdb-2' ,
	'omopdb-3' ,
	'omopdb-4' ,
],
'omopre' => [
	'omopre-1' ,
	'omopre-2' ,
	'omopre-3' ,
],

//'nmr' => [
//	'nmr-1-fitrobo' ,
//],

'emdb' => [
	'emdb-01' ,
	'emdb-02-' ,
	'emdb-02b' ,
	'emdb-03' ,
	'emdb-04' ,
	'emdb-05' ,
	'emdb-06' ,
	'emdb-07' ,
	'emdb-10' ,
],
'emdb_omo' => [
	'emdb-08' ,
	'emdb-09' ,
],

'epdb' => [
	'epdb-1' ,
	'epdb-2' ,
	'epdb-3' ,
	'epdb-4' ,
	'epdb-5' ,
	'epdb-6' ,
	'epdb-7' ,
],
'nmepdb' => [
	'epdb-1' ,
	'epdb-2' ,
	'epdb-3' ,
	'epdb-4' ,
//	'epdb-5' ,
	'epdb-6' ,
	'epdb-7' ,
],

'both' => [
	'both-1' ,
	'both-2' ,
	'both-3' ,
	'both-4' ,
	'both-5' ,
	'both-6' ,
//	'both-7' ,
	'both-8' ,
	'both-9' ,
] ,

'taxo' => [
	'taxo-1' ,
	'taxo-2' ,
	'taxo-3' ,
	'taxo-4' ,
	'taxo-5' ,
	'taxo-6' ,
	'taxo-7' ,
] ,

'postm' => [
	'emdb-06' , 
	'emdb-07' , 
	'emdb-08' , 
	'emdb-09' , 
	'mng omopdb' , 
//	'mng ana' , 
] ,

'pap' => [
	'keyword-01' ,
	'pap-1' ,
	'pap-2' ,
	'pap-3' ,
	'pap-4' ,
	'pap-5' ,
	'pap-6' ,
],

'branch' => [
	'branch-1' ,
	'branch-2' ,
] ,

'tue' => [
	'keyword-02' ,
//	'keyword-03' ,
	'mng wikipedb' ,
	'mng wikipe' ,
	'mng taxo' ,
	'mng met' ,
	'mng unichem' ,
	'mng empiar' ,
	'mng dbid' ,

	'mng pap' ,
	'mng unp' ,
	'both-4' ,
	'both-9' ,	
	'allpdb-10' ,
	'mng final' ,
	
] ,

'empiar' => [
	'emp-1' ,	
	'emp-2' ,	
	'emp-3' ,	
] ,

'dbid' => [
	'dbid-1' ,
	'dbid-2' ,
	'cath-1' ,
	'cath-2' ,
],

'sas' => [
	'sas-1' ,
	'sas-2' ,
	'sas-3' ,
	'sas-9' ,
	'sas-4' ,
	'sas-5' ,
	'sas-6' ,
	'sas-7' ,
	'sas-8' ,
	'sas-9' ,
],

//'oldmov' => [
//	'movie-7' ,
//	'both-1' ,
//	'movie-7'
//] ,

'dbdic' => [
	'dbdic-1' ,
	'dbdic-2' ,
	'dbdic-3' ,
//	'dbdic-4' ,
] ,

'wikipedb' =>[
	'wikipedb-1',
	'wikipedb-2',
],

'wikipe' => [
	'wikipe-1' ,
	'wikipe-2' ,
	'wikipe-3' ,
	'wikipe-4' ,
	'wikipe-5' ,
],

'final' => [
	'both-9' ,
	'final-t1' ,
	'final-1' ,
	'final-2' ,
],

'unp' => [
	'unp-1' ,
	'unp-2' ,
	'unp-3' ,
	'unp-4' ,
	'unp-5' ,
	'unp-6' ,
	'unp-7' ,
	'unp-8' ,
	'unp-9' ,
],
'met' => [
	'met-1' ,
	'met-2' ,
	'met-3' ,
	'met-4' ,
],
'bird' => [
	'bird-1' ,
	'bird-2' ,
	'bird-3' ,
	'bird-4' ,
] ,

'ab' => [
	'ab-1' ,
	'ab-2' ,
] ,

'vapros' => [
	'vapros-1' ,
	'vapros-2' ,
	'vapros-3' ,
	'vapros-4' ,
] ,

]);

//.. 実行
$n = trim( ARG, ' -' );
if ( TASK_CATALOG[ $n ] )
	_tasklist( $n );

/*
foreach ( array_keys( TASK_CATALOG ) as $k ) {
	if ( ! _instr( $k, ARG ) ) continue;
	_tasklist( $k );
}
*/

//.. function _tasklist
function _tasklist( $name ) {
	define( 'FN_TASK_RUNNING', TP_FN_TASK_RUNNING. "$name-". date( 'Ymd-His' ) );
	touch( FN_TASK_RUNNING );
	
	foreach ( TASK_CATALOG[ $name ] as $t )
		_php( trim( $t ) );

	_line(
		"タスクリスト終了",
		"$name (開始時刻: " . date( 'Y-m-d H:i:s', START_TIME ) . ')' ,
		'#' 
	);
	_del( FN_TASK_RUNNING );
	die();
}

//. proc
if ( ARG == 'stop' ) {
	touch( FN_STOP );
	_m( "停止モード ON", 1 );
	die();
}

if ( ARG == 'start' ) {
	_del( FN_STOP );
	_m( "停止モード 解除", 1 );
	die();
}

if ( ARG == 'proc' ) {
	$d = DN_PROC . '/';
	_m( strtr( implode( "\n", glob( $d . '*' ) ), [ $d => '' ] ) ?: 'no proc' );
	die();
}

//. ヒットなし
//.. エラーメッセージ
if ( ARG ){
	_die( '不明なコマンド: '. ARG );
}

//.. タスク一覧
$out = [];
foreach( TASK_CATALOG as $n => $v ) {
	$out[ $n ] = _imp( $v );
}

_line( 'タスクリスト一覧' );
_kvtable( $out );

//.. チェック
$out = [];
foreach ( TASK_CATALOG as $list_name => $task_list ) {
	foreach ( $task_list as $line ) {
		list( $cmd, $opt ) = explode( ' ', $line, 2 );
		if ( $cmd == 'mng' ) {
			if ( ! TASK_CATALOG[ $opt ] )
				$out[ "$list_name - $opt" ] =  '存在しないタスク名';
			continue;
		} else {
			if ( file_exists( "$cmd.php" ) ) continue;
			$cnt = count( glob( "$cmd*.php" ) );
			if ( ! $cnt )
				$out[ "$list_name - $cmd" ] =  '存在しないphpスクリプト';
			if ( 1 < $cnt )
				$out[ "$list_name - $cmd" ] =  '複数該当する';
		}
	}
}
if ( $out ) {
	_line( 'タスクリストに問題' );
	_kvtable( $out );
}

//.. 実行中
$out = [];
foreach ( glob( TP_FN_TASK_RUNNING. '*' ) as $fn ) {
	$out[ START_TIME - filemtime( $fn ) ] = explode( '-', basename( $fn ), 3 )[1];
}
krsort( $out );
foreach ( $out as $t => $name ) {
	unset( $out[ $t ] );
	$d = floor( $t / 86400 );
	$out[ trim(
		( $d ? "$d days" : '' )
		. date( ' H:i:s', ( $t - 32400 ) % 86400 ) //- 日本時間
	)] = $name;
}
_line( '実行中' );
$out ? _kvtable( $out ) : _m( 'なし' );
