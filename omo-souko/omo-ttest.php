<?php
//. ini
include "omo-common.php";
$compdn = "$omodn/comp";
$statdn = "$omodn/stat";

define( 'CMD', <<< EOF
t=capture.output(t.test(scan("<f1>"),scan("<f2>")))
write(t[5],file="out.txt")
EOF
);

$met = [];
foreach ( $vqnums as $v ) foreach ( $filtwid as $f )
	$met[] = "$v-$f";

//. main
$out = '';
_out( "Method\t70s-80s\t30s-50s\tc1-c2\tmean\n" );

foreach ( $met as $m ) {
//	echo "$m\t" .
	$ribo = (float)_get_tv( "$m-ribo-same", "$m-70s-80s" );
	$sub  = (float)_get_tv( "$m-sub-same",  "$m-30s-50s" );
	$chap = (float)_get_tv( "$m-chap-same", "$m-c1-c2" );
	$avg = ( $ribo + $sub + $chap ) / 3;
	_out( "{$m}\t{$ribo}\t{$sub}\t{$chap}\t{$avg}\n" );

//	echo "hoge: $m\n";
}
file_put_contents( "ttest.tsv", $out );

//. 
function _get_tv( $f1, $f2 ) {
	global $compdn;
	file_put_contents( 'cmd.r',
		strtr( CMD, [ '<f1>' => "$compdn/$f1.dat", '<f2>' => "$compdn/$f2.dat" ] )
	);
	exec( "R CMD BATCH --vanilla cmd.r" );
	$file = file_get_contents( 'out.txt' );
	return preg_replace( '/^t = (.*?),.+$/', '$1', $file );
}

function _out( $s ) {
	global $out;
	echo $s;
	$out .= $s;
}
//. 
