Scientific term解析

<?php
//. init
require_once( "commonlib.php" );
$s = <<<EOF
形容的な働きをする言葉
homo
EOF;

$blacklist = explode( "\n", $s );

$dn_data = DN_DATA . "/ids";

//- ダウンロードは手動
$dfn = DN_FDATA . "/scientific_term.ja_vs_en.utf8.txt";

//- load
if ( ! file_exists( $dfn ) )
	die( '用語データがない!!!!!' );

$e2j = [];
foreach ( _file( $dfn ) as $l ) {
	$a = explode( "\t", $l );
	if ( $a[0] == $a[1] ) continue;

	if ( in_array( $a[0], $blacklist ) !== false ) {
		_m( 'blacklist: ' . $a[0] );
		continue;
	}
	if ( in_array( strtolower( $a[1] ), $blacklist  ) !== false ) {
		_m( 'blacklist: ' . $a[1] );
		 continue;
	}

	$j2e[ $a[0] ][ $a[1] ] = 1;
	$e2j[ $a[1] ][ $a[0] ] = 1;
}

//. proc
$_e2j = [];
foreach ( $e2j as $n => $v ) {
	if ( strlen( $n ) < 4 ) continue;
//	$_e2j[ $n ] = array_keys( $v );
	$_e2j[ strtolower( $n ) ] = array_keys( $v );
}

$_j2e = [];
foreach ( $j2e as $n => $v ) {
	$_j2e[ $n ] = array_keys( $v );
}

_m( 'データ数 e2j:' . count( $_e2j )  . ' / j2e: ' . count( $_j2e ) );

//. save

_comp_save( "$dn_data/term_e2j.json.gz", $_e2j );
_comp_save( "$dn_data/term_j2e.json.gz", $_j2e );

//$le2jfn = "$dn_data/le2j.data";
//_comp_save( $le2jfn, $_le2j );
/*
//. db

//- db作成
$dbfn = "$tdn/term.sqlite";
$sqlite = new PDO( "sqlite:$dbfn", '', '' );
$sqlite->beginTransaction();

$res = $sqlite->query( "CREATE TABLE main( \"term\"\tTEXT,\"sterm\"\tTEXT,\"cont\"\tTEXT )" );
$maxwords = 1;

_m( 'DB: e2j' );
_count100( $_e2j );
foreach ( $_e2j as $e => $j ) {
	_count100();
	_dbload( $e, strtolower( $e ), implode( '|', $j ) ); 
}

_m( 'DB: j2e' );
_count100( $_j2e );
foreach ( $_j2e as $j => $e ) {
	_count100();
	_dbload( $j, $j, implode( '|', $e ) ); 
}

$sqlite->commit();
_m( '完了' );

function _dbload( $term, $sterm, $cont ) {
	global $sqlite, $maxwords;
	if ( $sqlite->query( 
		"REPLACE INTO main( \"term\", \"sterm\", \"cont\" ) "
			. "VALUES ( \"$term\", \"$sterm\", \"$cont\" )" 
	) === false )
		_m( "$term: 失敗", -1 ) ;

//	if ( _instr( ' ', $term ) ) die( "$term: " . count( explode( ' ', $term ) ) );
	$cnt = count( explode( ' ', $term ) );
	if ( $cnt > $maxwords ) {
		_m( "$cnt > $maxwords : $term" );
		$maxwords = $cnt;
	}
}
_m( "最大単語数: $maxwords" );
*/