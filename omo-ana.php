<?php
require_once dirname(__FILE__) . '/commonlib.php';
$ids = _file( "http://ipr.pdbj.org/emnavi/omo-ana.php" );


define( 'CMD', <<<EOD
set term png large size 800,800

set output "<f>_rank.png"
set title "<id> rank"
set xrange[0:200]
set yrange[0:200]
set ylabel "GMM rank"
set xlabel "FP rank"
set datafile separator ","
plot "<f>.csv" using 2:3 notitle with points pointtype 1 pointsize 2 lw 2 lt rgb "black"

set term png large size 800,800
set output "<f>_rank-label.png"
set title "<id> rank"
set xrange[0:200]
set yrange[0:200]
set ylabel "GMM rank"
set xlabel "FP rank"
set datafile separator ","
plot "<f>.csv" using 2:3 notitle with points pointtype 1 pointsize 2 lw 2 lt rgb "black", "<f>.csv" using 2:3:1 notitle with labels offset 1,0.7 tc rgb "blue"

set term png large size 800,800
set output "<f>_score.png"
set title "<id> score/cc"
set xrange[0.4:1]
set yrange[0.4:1]
set ylabel "GMM cc"
set xlabel "FP score"
set datafile separator ","
plot "<f>.csv" using 4:5 notitle with points pointtype 1 pointsize 2 lw 2 lt rgb "black"

set term png large size 800,800
set output "<f>_score-label.png"
set title "<id> score/cc"
set xrange[0.4:1]
set yrange[0.4:1]
set ylabel "GMM cc"
set xlabel "FP score"
set datafile separator ","
plot "<f>.csv" using 4:5 notitle with points pointtype 1 pointsize 2 lw 2 lt rgb "black",  "<f>.csv" using 4:5:1 notitle with labels offset 1,0.7 tc rgb "blue"

EOD
);

foreach ( $ids as $id ) {
	_m( $id );
	$f = "../test/omo-ana/$id";
	$fn_csv = "$f.csv";

	$csv = file_get_contents( "http://ipr.pdbj.org/emnavi/omo-ana.php?id=$id" );
	if ( $csv == '' )
		die( "no data for $id\n" );

	file_put_contents( $fn_csv, $csv );

	_gnuplot( strtr( CMD, [ '<f>' => $f, '<id>' => $id ] ) );
}
