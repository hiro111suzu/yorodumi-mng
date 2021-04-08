サイトマップxml作成スクリプト
<?php
//. init
require_once( "commonlib.php" );

$fn = DN_EMNAVI. '/Sitemap.xml';
$xml = <<< EOD
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
EOD;

//$smap = new SimpleXMLElement( $x );

//. top, 
_add( 'index.php' );
_add( 'stat.php' );
_add( 'omo-search.php' );
_add( 'pap.php' );
_add( 'taxo.php' );
//_add( 'emnavi_all.php?kw=&db=both&colnum=9&col0=agg_state&col1=authors&col2=journal&col3=pmid&col4=fit_soft&col5=method&col6=inst_vitr&col7=microscope&col8=rec_soft&col9=release' );
_add( 'doc.php' );
_add( 'doc.php?type=faq' );
_add( 'doc.php?type=news' );
_add( 'doc.php?type=info' );


//. list 
_add( 'esearch.php' );

foreach ( range(2,50) as $i )
	_add( "esearch.php?pagen=$i" );

//. detail
foreach( _idlist( 'emdb' ) as $id )
	_add( "quick.php?id=$id" );
foreach( _idlist( 'epdb' ) as $id )
	_add( "quick.php?id=pdb-$id" );

//. write xml
$xml .= "</urlset>\n";
_comp_save( $fn, $xml );
_end();

//. func _add
function _add( $u ) {
	global $xml;
	$r = 'http://pdbj.org/emnavi/';
	$u = htmlspecialchars( $u );
	$xml .= ''
		. "\t<url>\n"
		. "\t\t<loc>$r$u</loc>\n"
		. "\t</url>\n";
}
