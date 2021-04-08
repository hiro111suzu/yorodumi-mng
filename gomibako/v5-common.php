<?php
require_once( "commonlib.php" );

//. directory
$dn = DN_FDATA . '/pdb_v5';
_mkdir( $dn );
define( 'DN_V5CIF', "$dn/mmcif" );
define( 'DN_V5XML', "$dn/xml" );
_mkdir( DN_V5CIF );
_mkdir( DN_V5XML );




//. fn
$_filenames += [
	'v5_xml'	=> DN_V5XML	. '/<id>-noatom.xml.gz' ,
	'v5_json'	=> DN_DATA . '/pdb/json_v5/<id>.json.gz'
];

