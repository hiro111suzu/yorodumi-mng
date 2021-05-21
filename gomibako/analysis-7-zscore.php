<?php
//. init

require_once( "commonlib.php" );

$mode = _omomode();
$json = _json_load( DN_PREP . "/simtable$mode.json.gz" );

//. main loop
$sc = [];

//$cnt = pow( count( $json ) , 2 );

$sum = 0;
$hist = [];

foreach ( $json as $id1 => $a ) foreach ( $a as $id2 => $v ) {
	if ( strcmp( $id1 , $id2 ) >= 0 )
		continue;
	$sc[ "$id1-$id2" ] = $v;
	$sum += $v;
	$hist[ (string)( floor( $v * 100 ) / 100 ) ] += 1;
}

$cnt = count( $sc );
$mean = $sum / $cnt;

$psum = 0;
foreach ( $sc as $v ) {
	$psum += pow( $mean - $v, 2 );	
}
$sigma = sqrt( $psum / $cnt );

$max = max( $sc );
$min = min( $sc );

_m( "max: $max\nmin: $min\n"
	. "sum: $sum\n"
	. "count: $cnt\n"
	. "mean: $mean\n"
	. "std: $sigma\n"
);

$histfile = '';

for ( $i = 0; $i <= 1.00; $i += 0.01 ) {
//foreach ( $hist as $i => $h ) {
	$v  = $hist[ (string)$i ];
	_m( "$i\t$v\t" . str_repeat( '=', $v / 1000 ) );
	$histfile .= "$i\t$v\n";
}

asort( $sc );
$f = '';
foreach ( $sc as $i => $s ) {
	if ( $s == 0 ) continue;
	$f .= "$i\t$s\n";
}
file_put_contents( 'scores.txt', $f );
file_put_contents( 'scorehist.txt', $histfile );
