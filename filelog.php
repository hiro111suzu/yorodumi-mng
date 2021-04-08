<?php
//. init
require_once( "commonlib.php" );
$now = time();
//.. 時間
$day = 24 * 3600 ;
$six = strtotime( "today" ) + ( 6 * 3600 );
if ( $argv[1] != '' and ctype_digit( $argv[1] ) )
	$six -= $argv[1] * $day;

if ( $six < $now ) {
	//- 午前5時以降
	define( 'NEXT', $six + $day );
	define( 'LAST', $six );

} else {
	//- 午前5時以前
	define( 'NEXT', $six );
	define( 'LAST', $six - $day );
}
define( 'TODAY', date( 'Y-m-d', LAST ) ) ;
define( 'MONTH', date( 'Y-m', LAST ) ) ;

_m( ''
	. TODAY
	. ': '
	. date( DATE_ATOM, LAST )
	. ' - ' 
	. date( DATE_ATOM, NEXT )
);

//.. ディレクトリ
define( 'DN_BKUP', realpath( __DIR__ . '/../backup' ) );
_mkdir( DN_BKUP );

define( 'DN_LOG', DN_PREP . '/dev_log' );
_mkdir( DN_LOG  );

//.. データ
$files = [
	'cgi' => [
		DN_EMNAVI . '/*.php' ,
		DN_EMNAVI . '/*.tsv' ,
	] ,
	'javascript' => [
		DN_EMNAVI . '/jsprec/*.js' ,
	] ,
	'manager' => [
		__DIR__ . '/*.php' ,
		__DIR__ . '/*.ini' ,
	] ,
	'img' => [
		DN_EMNAVI . '/img/*.gif' ,
		DN_EMNAVI . '/img/*.png' ,
		DN_EMNAVI . '/img/*.jpg' ,
	],
	'edit' => [
		DN_EDIT. '/*' ,
	],
];

//. メイン
$data = [];
foreach ( $files as $type => $c1 ) {
	foreach ( $c1 as $pathstr ) {
		$g = glob( $pathstr );
		foreach ( $g as $pn ) {
			if ( ! _newfile( $pn ) ) continue;
			$fn = basename( $pn );
			$data[ $type ][] = $fn;
			$a = explode( '.', $fn, 2 );
			_mkdir( $dn = DN_BKUP . "/$type" );
			copy( $pn, "$dn/" . $a[0] .'-'. TODAY . '.' . $a[1] );
		}
	}
}
foreach ( _idlist( 'emdb' ) as $id ) {
	foreach ( range( 1, 20 ) as $i ) {
		$pn = DN_EMDB_MED . "/$id/s$i.py";
		if ( ! file_exists( $pn ) ) continue;
		if ( ! _newfile( $pn ) ) continue;
		$data[ 'movie' ][] = "$id-$i";
	}
}

_m( json_encode( $data, JSON_PRETTY_PRINT ) );

//. データ保存
if ( $data != [] ) {
	_json_save( DN_LOG . '/' . TODAY . '.json', $data );

	$out = '';
	foreach ( $data as $type => $files ) {
		if ( count( $files ) == 0 ) continue;
		$out .= "\n[" . $type . "]\n" . implode( "\n", $files ) . "\n";
	}

	file_put_contents( DN_LOG . '/' . TODAY . '.txt', $out );
}

//. func
function _newfile( $pn ) {
	$t = filemtime( $pn );
//	_pause( $pn );
	return LAST < $t and $t < NEXT;
}
