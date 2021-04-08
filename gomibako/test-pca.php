PCAやってみる
<?php
//. init
require_once( "commonlib.php" );

$rcmd = <<<EOF
library(Rcmdr)
t<-read.csv("<infn>")
pc<-princomp(t,cor=FALSE)
sink("<outfn>")
pc
sink()
EOF;

$id = $argv[1];
$vqfn = _fn( 'vq50', $id );
if ( ! file_exists( $vqfn ) )
	die( "No ID: $id" );

//. 座標ファイル読み込む
$csvfn = 'test.csv';
$s = "x,y,z\n";
foreach ( file( $vqfn ) as $l )
	$s .= _num( $l, 30 ) .','. _num( $l, 38 ) .','. _num( $l, 46 ) . "\n" ;

function _num( $str, $num ) {
	return trim( substr( $str, $num, 8 ) );
}

file_put_contents( $csvfn, $s );
//_m( $s );
//_m( $vqfn );

//. R
_del( $outfn = 'out.txt' );
_Rrun( $s = strtr( $rcmd, array( '<infn>' => $csvfn, '<outfn>' => $outfn ) ) );
//_m( $s );
$a = _file( 'out.txt' );
$out = preg_replace( '/ +/', "\n", trim( $a[4] ) );
_m( $out );

