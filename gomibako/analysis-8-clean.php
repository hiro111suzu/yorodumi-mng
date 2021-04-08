<?php
die( 'ŒÃ‚¢' );
require_once( "commonlib.php" );

foreach ( $emdbidlist as $id ) {

	$did = "emdb-$id";
	$ddn = DN_EMDB_MED . "/$id";
	
	$mapfn = "$ddn/emd_{$id}.map";
	$situs1 = "$ddn/emd_{$id}.situs";
	$situs2 = "$ddn/emd_{$id}_r.situs";
	$histl = "$ddn/hist.jpg";
	$hists = "$ddn/hists.jpg";

	
	$vqfn1 = "$_omokagedn/$did-vq.pdb";
	$vqfn2 = "$_omokagedn/$did-vq2.pdb";
	
	$porf0 = "$_omokagedn/$did-prof0.txt";
	$porf1 = "$_omokagedn/$did-prof1.txt";
	$porf2 = "$_omokagedn/$did-prof2.txt";
	$porf3 = "$_omokagedn/$did-prof3.txt";
	$porf4 = "$_omokagedn/$did-prof4.txt";
	$porf5 = "$_omokagedn/$did-prof5.txt";

	$listfn = "$_omokagedn/$did-list.txt";
	$imgfn = "$_omokagedn/$did.gif";

	echo '.';
	//- situsƒ}ƒbƒv‚ªŒÃ‚¢
	_delold( $mapfn, $situs1 );
	_delold( $mapfn, $situs2 );
	_delold( $mapfn, $histl );
	_delold( $mapfn, $hists );

	$sfn = file_exists( $situs2 ) ? $situs2 : $situs1 ;
	_delold( $sfn, $vqfn1 );
	_delold( $sfn, $vqfn2 );
	
	$vfn = file_exists( $vqfn2 ) ? $vqfn2 : $vqfn1 ;
	_delold( $vfn, $prof0 );
	_delold( $vfn, $prof1 );
	_delold( $vfn, $prof2 );
	_delold( $vfn, $prof3 );
	_delold( $vfn, $prof4 );
	_delold( $vfn, $prof5 );

	_delold( $vfn, $imgfn );
	_delold( $vfn, $listfn );


/*	
	if ( ! file_exists( $situs1 ) and ! file_exists( $situs2 )  ) {
//		echo( $did );
		_hoge( $vqfn1, $vqfn2, $porf0, $porf1, $porf2, $porf3, $porf4, $porf5, $imgfn );
	}
*/

/*


	if ( file_exists( "$_omokagedn/$did-list.txt" ) ) 
		"!!! $id list.txt\n";
	if ( file_exists( "$_omokagedn/$did-list.txt" ) ) 
		"!!! $id list.txt\n";
*/	
}


function _hoge() {
	foreach ( func_get_args() as $s ) {
		if ( file_exists( $s )  ) $i = 0;
//			echo "$s\n";
	}
}
function _newer( $f1, $f2 ) {
	global $id;
	if ( filemtime( $f1 ) > filemtime( $f2 ) )
	 	echo "$id: " . _d2s( $f1 )  . '>' . _d2s( $f2 ) . "\n"; 
	 
	return ( filemtime( $f1 ) > filemtime( $f2 ) );
}

function _d2s( $s ) {
	return date( 'Y-m-d', filemtime( $s ) ) ;
	
}

