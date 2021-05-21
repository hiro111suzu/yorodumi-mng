<?php
include "commonlib.php";

$ddn = "../roc_work/data";
_mkdir( $aucdn = "../roc_work/auc" );
/*
$cmd = <<<EOF
library(ROCR)
rocdata<-read.table("<infn>")
pred<-prediction(rocdata[,1],rocdata[,2])
pref<-performance(pred,"tpr","fpr" )

auc.tmp <- performance(pred,"auc")
auc <- as.numeric(auc.tmp@y.values)
sink("<outfn>")
auc
sink()
EOF;


foreach ( glob( "$ddn/*.txt" ) as $pn ) {
	$bn = basename( $pn, '.txt' );
	_m( $bn );
	_Rrun( strtr( $cmd, [ '<infn>' => $pn, '<outfn>' => "$aucdn/$bn.txt" ] )  );
}
*/

$data = [];
foreach ( glob( "$aucdn/*.txt" ) as $pn ) {
	$bn = basename( $pn, '.txt' );
	$a = explode( '-', $bn );
	$meth = $a[0];
	$categ = $a[1];
	$s = preg_replace( '/^.+ ([0-9\.]+)[\n\t]+$/', '$1', file_get_contents( $pn ) );
	$data[ $meth ][ $categ ] = trim( $s );
	_m( "$meth - $categ : $s" );
}

$tsv = "\tchap\tcomp\tribo\n";
$m = [
	'gmfit10'	=> 'gmfit 10' ,
	'gmfit20'	=> 'gmfit 20' ,
	'gmfit40'	=> 'gmfit 40' ,
	'omo_all'	=> 'omokage' ,
	'omo_30nd'	=> 'omokage 30(nd)' ,
	'omo_50nd'	=> 'omokage 50(nd)' ,
	'omo_30'	=> 'omokage 30' ,
	'omo_50'	=> 'omokage 50' ,
	'omo_out'	=> 'omokage outer25' ,
	'omo_pca'	=> 'omokage PCA'
];

foreach ( $m as $meth => $name ) {
	$tsv .= "$name\t"
		. $data[ $meth ][ 'chap' ] . "\t"
		. $data[ $meth ][ 'comp' ] . "\t"
		. $data[ $meth ][ 'ribo' ] . "\n"
	;
}
file_put_contents( "$aucdn/auc.tsv", $tsv );
