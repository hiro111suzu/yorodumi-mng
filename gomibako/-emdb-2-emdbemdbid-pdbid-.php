<?php
//. init					
require_once( "commonlib.php" );
_initlog( "make related emdb-pdb data" );

$xmlfn = "$_ddn/emdb-pdb.xml";
if ( file_exists( $xmlfn ) )
    $xml = simplexml_load_file( $xmlfn );
else
	$xml = new SimpleXMLElement( "<data></data>" );

$ini = parse_ini_file( "$_ddn/pubmed_id.ini", false );

$convert	= 'convert -resize 100x100';
$imgdir		= "$_ddn/pdbimage_s";
$imgurl		= 'http://www.pdbj.org/pdb_images/';

if ( php_uname( 's' ) == 'Windows NT' )
	$convert = 'C:/tools/ImageMagick/convert.exe -resize 100x100';

$url = "http://pdbjs3.protein.osaka-u.ac.jp/xPSSS/xPSSSSearch?txtCollection=pdbMLplusF_Collection&position=1&search_type=keyword_search&xpsss_search.x=0&xpsss_search.y=0&query=";

$pdb  = array();	
$emdb = array();

//. start main loop										
foreach( $emdbidlist as $id ) {
//	if ( $id > 1020 ) break;
	$pubmedid = trim( $ini[ $id ] );
	if ( $pubmedid == '' ) continue;
	print '.';
	
	$s = file_get_contents( $url . $pubmedid );
	$n = preg_match_all( "/summaryP\('(.*?)'\)/", $s, $t );
	foreach( $t[1] as $pdbid ) {
		$pdbid = strtolower( $pdbid );
		$emdb[ $id ]	.= "$pdbid,";
		$pdb[ $pdbid ]	.= "$id,";

		if ( file_exists( "$imgdir/$pdbid.jpg" ) ) continue;
		foreach( array( '.jpg', '_x.jpg', '_y.jpg' ) as $j ) {
			copy( $imgurl . $pdbid . $j , $pdbid . $j );
			exec( "$convert $pdbid$j $imgdir/$pdbid$j" );
		}
		exec( 'rm *.jpg' );
		_print( "pdb-$pdbid : got image files" );
	}
}

//. make xml								
foreach( $emdb as $a => $b ) {
	$b = trim( $b, ',' );
	if ( $xml->{"emdb-$a"} != $b )
		_print( "new: emdb-$a - $b" );
	$xml->{"emdb-$a"} = $b;
}
foreach( $pdb as $a => $b ) {
	$xml->{"pdb-$a"} = trim( $b, ',' );
}

//. end					
_savexml( $xmlfn, $xml );
_writelog();

?>