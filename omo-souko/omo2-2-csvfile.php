<?php
//. init
require_once( "commonlib.php" );
$datadn = DN_PREP . "/omo_categ";
$sctable	= _json_load( "$datadn/scoretable.json" );
$idlist		= _json_load( "$datadn/cat2id.json" );
$categs 	= _json_load( "$datadn/categs.json" );
$id2cat 	= _json_load( "$datadn/id2cat.json" );

$cat2num = array(
	'70s' => 1,
	'80s' => 2,
	'30s' => 1,
	'40s' => 2,
	'c1'  => 1,
	'c2'  => 2
);

define( 'POW', 1 );

$rcmd = <<<EOF
cs<-cmdscale(read.csv("<infn>"))
write.csv(cs,"<outfn>")
EOF;

//. idlist作成

foreach ( $sctable as $cname => $ar ) {
	$ids = array_keys( $ar );
	$csv = implode( ',', $ids ) . "\n";
	foreach ( $ids as $id1 ) {
		$csv .= $id1;
		foreach ( $ids as $id2 ) {
			$csv .= ',' . pow( 1 - $ar[ $id1 ][ $id2 ], POW );
		}
		$csv .= "\n";
	}
	
	_comp_save( $csvfn = "$datadn/ctable-$cname.csv", $csv );
	$plotfn = "$datadn/plot-$cname.csv";
	_Rrun( strtr( $rcmd, array( '<infn>' => $csvfn, '<outfn>' => $plotfn ) ) );

	//- csvファイルにカテゴリを付け足す
	$out = '';
	foreach ( _file( $plotfn ) as $line ) {
		$out .= $line .','. $id2cat[ substr( $line, 1, 5 ) ] . "\n";
//		_m( substr( $line, 1, 5 ) );
	}
	_comp_save( $plotfn, $out );
}
//print_r($id2cat);
