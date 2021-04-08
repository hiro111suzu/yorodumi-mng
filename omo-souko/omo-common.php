<?php
//. main
include "commonlib.php";
//- フォルダ名称

$omodn = "/novdisk2/db/omotune";
$omolistdn = "$omodn/idlist";
$compdn = "$omodn/comp";
$profdn = "$omodn/prof";
$statdn = "$omodn/stat";
$vqdn = "$omodn/vq";

//$datalistfn 	= "$omodn/datalist.txt";
//$datalistjson	= "$omodn/datalist.json";
//$idlistfn	 	= "$omodn/idlist.txt";

//$vqdn 			= "$omodn/vq";
//$profdn			= "$omodn/prof";
//$plotdn			= "$omodn/plot";
//$vqnums = array( 20, 50 );

$vqnums = array( 10, 20, 30, 40, 50 );
$ovqnums = array( 40, 35, 30, 25 );

/*
$profs = array(
	10 => array( 3, 5 ) , // 45
	20 => array( 10, 20 ) , // 190
	30 => array( 20, 40 ) , // 435
	40 => array( 50, 80 ) , // 780
	50 => array( 100, 150 ) // 1225
);
*/
//- フィルタの%値リスト
$filtwid = array( 00, 10, 20, 30, 40, 50, 60, 70 );

//- マージするデータのリスト
$mrglist = array(
	'20+50',
	'30+50',
	'40+50',
	'10+30+50',
	'20+30+50',
	'20+30+40+50',
	'o25+50',
	'o30+50',
	'o25+30+50',
	'o30+30+50',
	'o30+20+30+50',
	'o25+20+30+50'
);

//. function

function _ofn( $type ) {
	global $did, $omodn, $omolistdn, $compdn, $profdn, $statdn, $vqdn;
	$a = array(
		'vq10' 	=> "$vqdn/$did-10.pdb" ,
		'vq20' 	=> "$vqdn/$did-20.pdb" ,
		'vq30' 	=> "$vqdn/$did-30.pdb" ,
		'vq40' 	=> "$vqdn/$did-40.pdb" ,
		'vq50' 	=> "$vqdn/$did-50.pdb" ,
		'ovq'	=> "$vqdn/$did-ovq.pdb" ,
		'oprof40'	=> "$profdn/$did-o40.txt" ,
		'oprof35'	=> "$profdn/$did-o35.txt" ,
		'oprof30'	=> "$profdn/$did-o30.txt" ,
		'oprof25'	=> "$profdn/$did-o25.txt" ,
	);
	if ( $a[ $type ] == '' )
		die( "ファイル名が分かりません $did - $type" );
	return $a[ $type ];
}


//.
//. split list
/*
function _splist() {
	global $_ddn;
	foreach ( _file( "$_ddn/split.tsv" ) as $l ) {
		$a = explode( "\t", $l, 2 );
		$split[ $a[0] ] = explode( "\t", $a[1] );
	}
	return $split;
}


*/
