PDBe-chemimgをリストアップ
==========

<?php
require_once( "commonlib.php" );
$imgdn = "$_ddn/pdbechemimg";
$xmldn = '/novdisk2/db/pdbmlplus/nc';

//. loop
$data = [];

$l = glob( "$xmldn/*" );
_count100( $l );

$count = [];
foreach ( $l as $fn ) {
//	if( _count100() == 500 ) break;
	_count100();

	preg_match_all( '/<chem_comp id="(.+?)">/', file_get_contents( $fn ), $ar );
	foreach ( $ar[1] as $id ) {
		if ( $id == 'HOH' ) continue;
		++ $count[ $id ];
	}

/*
	$xml = _load_pdbml( $fn );
	$x = $xml->chem_compCategory->chem_comp;
	if ( count( $x ) > 0 ) foreach( $x as $c ) {
		$id = (string)$c[ 'id' ];
		if ( $id == 'HOH' ) continue;
		++ $count[ $id ];
	}
*/

}

arsort( $count );
_tsv_save( "$imgdn/count.tsv", $count );
