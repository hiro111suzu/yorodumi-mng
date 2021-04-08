<?php
//. ini
include "omo-common.php";

$nums = array();
for ( $i = 0.7; $i < 1; $i += 0.005 )
	$nums[] = $i . ' ';
$avg = array();
$dev = array();

//. main
echo "histogram ---\n";
foreach ( scandir( $compdn ) as $fn ) {
	//- ヒストグラム
	if ( substr( $fn, -4 ) != '.dat' ) continue;
	$mode = substr( $fn, 0, -4 );
	$outfn = "$statdn/hist-$mode.txt";
//	if ( $_redo ) _del( $outfn );
//	if ( file_exists( $outfn ) ) continue;

	//- 
	$vals = explode( ' ', file_get_contents( "$compdn/$fn" ) );
	$a = array();
	foreach ( $vals as $v ) {
		$a[ ( floor( $v * 200 ) / 200 ) . ' ' ] += 1;
	}
	$out = '';
	$cnt = count( $vals );
	foreach ( $nums as $n ) {
		$out .= $n . ( $a[ $n ] / $cnt ) . "\n";
//		echo $n . ( $a[ $n ] / $cnt ) . "\n";
	}

	file_put_contents( $outfn, $out );
//	echo "$outfn: $cnt data\n";
//	break;

	//- 統計値

	$avg[ $mode ] = array_sum( $vals ) / $cnt;
	$sum = 0;
	foreach ( $vals as $v )
		$sum += abs( $avg[ $mode ] - $v );
	$dev[ $mode ] = $sum / $cnt;
	echo '.';	
//	echo "$mode\n";
}

//. plot
echo "\nplot ---\n";

$met = array();
foreach ( $vqnums as $v ) foreach ( $filtwid as $f )
	$met[] = "$v-$f";

foreach ( $mrglist as $mrg )
	$met[] = "mrg$mrg";

foreach ( $ovqnums as $ovqn )
	$met[] = "outer-$ovqn";

define( 'PLOT', <<<EOF
set xlabel 'Score'
set ylabel 'Frequency'
set xrange [0.7:1.0]
set key left top
set term png large
set output "{$statdn}/plot-<fn>.png"
plot
EOF
);
define ( 'PLOT2', " \"{$statdn}/hist-<fn>.txt\" ti \"<ti>\" with lines lw 3" );

$comps = array(
	'ribo1' => '70s-80s 70s-70s 80s-80s' ,
	'ribo2' => '70s-80s ribo-same' ,
	'chap1' => 'c1-c2 c1-c1 c2-c2' ,
	'chap2' => 'c1-c2 chap-same' ,
	'sub1'  => '30s-50s 30s-30s 50s-50s' ,
	'sub2'  => '30s-50s sub-same' ,
	'far'	=> 'c1-c1 c1-70s'
);

foreach ( $met as $m ) foreach ( $comps as $comp => $str ) {
	$a = array();
	foreach ( explode( ' ', $str ) as $s ) {
		$mr = preg_replace( array( '/-/', '/$/' ), array( 'dots / ', '% wd' ), $m );
		$a[] = strtr( PLOT2, array( '<fn>' => "$m-$s", '<ti>' => "$s ($mr)" ) );
	}
	file_put_contents( 'plot.txt',
		strtr( PLOT, array( '<fn>' => "$comp-$m" ) ) . implode( ',', $a )
	);
	exec( "gnuplot plot.txt" );
	echo '.';
}

//. stat
echo 'stat';

$out = "mode\tribosome\tsubunits\tchaperonin\tfar\tmean\n";
foreach ( $met as $m ) {
	echo "$m\t";
	$ribo = _sep( $m, '70s-80s', 'ribo-same' );
	$sub  = _sep( $m, '30s-50s', 'sub-same' );
	$chap = _sep( $m, 'c1-c2', 'chap-same' );
	$far = _sep( $m, 'c1-c1', 'c1-70s' );
//	$mean = ( $ribo + $sub + $chap + $far ) / 4;
	$mean = ( $ribo + $sub + $chap ) / 3;
	$out .= "$m\t$ribo\t$sub\t$chap\t$far\t$mean\n";
}

echo $out;
file_put_contents( "$omodn/sepscore.tsv", $out );

function _sep( $m, $x, $y ) {
	global $avg, $dev;
	$x = "$m-$x";
	$y = "$m-$y";
	return abs(
		( $avg[ $x ] - $avg[ $y ] ) / ( ( $dev[ $x ] + $dev[ $y ] ) / 2 )
	);
}
	
