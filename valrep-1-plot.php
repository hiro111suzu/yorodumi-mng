<?php
include "commonlib.php";
$_filenames += [
	'valrep_xml' = DN_EMDB_MR. '/validation_reports/EMD-<id>/emd_<id>_validation.xml.gz'
];
define( 'FN_XML_TIME', DN_PREP. '/valrep_xml_time.json.gz' );
$file_time = _json_load( FN_XML_TIME );

//. mainloop

foreach ( _idlist('emdb') as $id ) {
	$fn_in = _fn( 'valrep_xml', $id );
	if ( ! file_exists( $fn_in ) ) continue;
	if ( filemtime( $fn_in ) == $file_time[ $id ] ) continue;

	//- read xml
	$xml = simplexml_load_string( _gzload( $fn_in ) );
}

