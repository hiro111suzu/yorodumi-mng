<?php
require_once( "commonlib.php" );
$_filenames += [
	'unp_json' => DN_DATA. '/unp/<id>.json.gz'
];
define( 'DN_WORK', DN_PREP. '/vapros' );
_mkdir(  DN_WORK );
define( 'FN_UNP2REACTOME'	, DN_WORK. '/unp2reactome.json.gz' );
define( 'FN_PDB2UNP'		, DN_WORK. '/pdb2unp.json.gz' );
define( 'FN_PDBCHAIN2COMP'	, DN_WORK. '/pdbchain2comp.json.gz' );

