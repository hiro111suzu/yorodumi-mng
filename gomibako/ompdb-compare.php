<?php
//- スコアテーブル
ini_set( "memory_limit", "1024M" );

//. init
require_once( "commonlib.php" );

//- id
$err = '';
foreach ( $argv as $i => $n ) {
	if ( $i == 0 ) continue;
	$fn[ $i ] = "ompdb-data/$n-prof.txt";
	if ( file_exists( $fn[ $i ] ) ) continue;
	$fn[ $i ] = "$_omokagedn/emdb-$n-prof3.txt";
	if ( file_exists( $fn[ $i ] ) ) continue;
	$fn[ $i ] = "$_omokagedn/pdb-$n-prof3.txt";
	if ( file_exists( $fn[ $i ] ) ) continue;
	$err .= "#{$i}: データがない!!\n{$fn[$i]}\n";
}
if ( $err != '' ) die( $err );


$p1 = file( $fn[ 1 ], FILE_IGNORE_NEW_LINES );
$p2 = file( $fn[ 2 ], FILE_IGNORE_NEW_LINES );


//. main loop
$flag = 0;
$count = count( $p1 );

$sum = 0;
$wsum = 0;
for ( $i = 10; $i < $count; ++ $i ) {
	$sum += pow( $p1[ $i ] - $p2[ $i ] , 2 );
	$wsum += pow( $p1[ $i ] + $p2[ $i ] , 2 );
}
$s = 1 - sqrt( $sum / $wsum );

echo "\n {$argv[1]} vs. {$argv[2]}: score = $s";
