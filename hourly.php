<?php

//. init
chdir( __DIR__ );
include 'commonlib.php';

$youbi = date( 'D' );
$hour = date( 'H' );
if ( $argv[1] != '' )
	list( $youbi, $hour ) = explode( '-', $argv[1], 2 );

_m( "hourly: $youbi-$hour" );


//. job list
define( 'JOBLIST', [
	'everyday' => [
		'03' => 'filelog' ,
		'09' => 'wikipe-4-download 2000' ,
		'15' => 'check_iw1log' ,
	] ,

	'Mon' => [
		'07' => 'unp-5-get_xml 1000' ,
	] ,

	'Tue' => [
		'07' => 'unp-5-get_xml 1000' ,
		'12' => 'mng sas' ,
	] ,

	'Wed' => [
		'02' => 'mng tue' ,
		'07' => 'unp-5-get_xml 1000' ,
		'08' => 'mng final' ,
//		'09' => 'emn_upload do',   //------------------
		'10' => 'mng final' ,
		'12' => 'mng final' ,
		'14' => 'mng final' ,
		'13' => 'hourly_test' ,
	] ,

	'Thu' => [
		'07' => 'unp-5-get_xml 1000' ,
	] ,

	'Fri' => [
		'07' => 'unp-5-get_xml 1000' ,
//		'17' => [ 'emdb-03', 'emdb-04'] ,
	] ,

	'Sat' => [
		'07' => 'unp-5-get_xml 10000' ,
		'11' => 'mng startud' ,
//		'09' => 'mng startud' ,
	] ,

	'Sun' => [
//		'06' => 'mng startud' ,
		'07' => 'unp-5-get_xml 10000' ,
		'17' => 'both-3-get-pubmedxml redo' ,
//		'11' => 'mng startud' , //----------------------
		'19' => 'mng startud' , //----------------------
	] ,
]);

//. main
$job1 = (array)JOBLIST[ $youbi ][ $hour ];
$job2 = (array)JOBLIST[ 'everyday' ][ $hour ];

$jobs = array_merge( $job1, $job2 );

if ( $jobs == [] )
	die( "no job for now: $youbi-$hour" );

_del( FN_HOURLY_MSG );

$log = [];
foreach ( $jobs as $job ) {
	$log[] = "task: $job";

	$fn_err = _tempfn( 'txt' );
	_php( $job, "2>$fn_err" );

	//- エラー
	$log[] = '[Error message]';
	$err = [];
	foreach ( _file( $fn_err ) as $line ) {
		$line = trim( $line );
		if ( $line == '**** SPIDER NORMAL STOP ****' ) continue;
		if ( _instr( '...qvol> ', $line ) ) continue;
		$err[] = $line;
	}

	if ( $err != [] ) {
		$log = array_merge( $log, $err );
	} else {
		$log[] = 'none';
	}
	_del( $fn_err );
	
	//- ログ保存
	if ( file_exists( FN_HOURLY_MSG ) ) {
		$msg = file_get_contents( FN_HOURLY_MSG );
		$log[] = "\n[Script message]";
		$log[] = $msg;
		_del( FN_HOURLY_MSG );
	}
}

_mkdir( $dn = DN_PREP . '/hourly_log' );
$msg = implode( "\n", $log );
_mail( gethostname() . ' emn-mng hourly task', $msg );
file_put_contents( "$dn/$youbi-$hour.txt", $msg );

