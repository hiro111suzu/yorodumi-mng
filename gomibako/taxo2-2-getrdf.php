<?php
include "commonlib.php";
_mkdir( $dn = DN_PREP . '/taxo' );
_mkdir( $rdfdn = "$dn/rdf" );
$json = _json_load( DN_DATA . '/taxo/count.json.gz' );
foreach ( $json as $id => $num ) {
	if ( _count( 10, 10 ) ) break;
	$outfn = "$rdfdn/$id.rdf";
	if ( file_exists( $outfn ) ) continue;
	$rdf = file_get_contents( "http://www.uniprot.org/taxonomy/$id.rdf" );
	if ( $rdf != '' )
		file_put_contents( $outfn, $rdf );
	break;
}
