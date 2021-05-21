<?php
include "commonlib.php";

foreach ( $emdbidlist as $id ) {
	$did = "emdb-$id";
	$vq1fn = "$_omokagedn/$did-vq.pdb";
	$vq2fn = "$_omokagedn/$did-vq2.pdb";
	$ddn = DN_EMDB_MED . "/$id";
	$movifn = "$ddn/movieinfo.json";
//	$ssnfn = "$ddn/session1.py";
//	if ( ! file_exists( $ssnfn ) )
	$ssnfn = "$ddn/s1.py";
	if ( ! file_exists( $ssnfn ) ) continue;
//	if ( ! file_exists( $vq1fn ) ) continue;
		
//	echo '.';
	if ( file_exists( $vq1fn ) and ! file_exists( $vq2fn ) )
		echo "$id: only vq1\n";
	if ( file_exists( $vq2fn ) and ! file_exists( $vq1fn ) )
		echo "$id: only vq2\n";
	if ( ! file_exists( $vq2fn ) ) continue;
	if ( ! file_exists( $movifn ) ) continue;
	if ( filemtime( $ssnfn ) > filemtime( $vq2fn ) )
		echo "$id: session file is newer!!\n";
}


