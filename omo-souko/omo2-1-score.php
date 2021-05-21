<?php
//. init
require_once( "commonlib.php" );
$categs = array( 
	'subu' => array( '30s', '50s' ) ,
	'ribo' => array( '70s', '80s' ) ,
	'chap' => array( 'c1',  'c2'  )
);

$datadn = DN_PREP . "/omo_categ";

//. idlist作成
$idlist = array();
$id2cat = array();
foreach ( $categs as $c => $a ) foreach ( $a as $cat ){
	$dn = "$datadn/_$cat";
	foreach ( scandir( $dn ) as $fn ) {
		if ( is_dir( $fn ) ) continue;
		$did = _id2did2( substr( $fn, 0, 4 ) );
		$idlist[ $cat ][] = $idlist[ $c ][] = $did;
		$id2cat[ $did ] = $cat;
	}
}

foreach ( $idlist as $n => $a ) {
	_m( "$n:\t" . count( $a ) );
}

_comp_save( "$datadn/cat2id.json", $idlist );
_comp_save( "$datadn/categs.json", $categs );
_comp_save( "$datadn/id2cat.json", $id2cat );

//. id2type
$cat2num = array(
	'70s' => 0, 
	'80s' => 1, 
	'30s' => 0,
	'40s' => 1,
	'c1'  => 0,
	'c2'  => 1
);
$s = "id,num\n";
foreach ( $id2cat as $id => $cat ) {
	$s .= "$id,"
		. ( $cat2num[ $cat ] ? 1 : 2 )
		. "\n"
	;
}
_comp_save( "$datadn/id2num.csv", $s );

//. scores
_m( 'テーブルデータ読み込み' );
$allt = _json_load( DN_PREP . "/simtable8.json" );
_m( '完了' );

$sctable = array();
foreach ( $categs as $name => $a ) {
	$ids = $idlist[ $name ];
	foreach ( $ids as $id1 ) foreach ( $ids as $id2 ) {
		$sctable[ $name ][ $id1 ][ $id2 ]
			= $allt[ _did( $id1 ) ][ _did( $id2 ) ];
	}
}
_comp_save( "$datadn/scoretable.json", $sctable ) ;


function _did( $i ) {
	return ( substr( $i, 0, 1 ) == 'E' ? 'emdb' : 'pdb' )
		. '-'
		. substr( $i, -4 )
	;
}
