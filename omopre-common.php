<?php
//. init
ini_set( "memory_limit", "512M" );
require_once( "commonlib.php" );
require_once( "omo-common.php" );
define( 'DN_OMOPRE', DN_PREP. '/omopre' );
define( 'FN_OMOPRE_IDLIST', DN_OMOPRE. '/idlist.txt' );

_mkdir( DN_OMOPRE );
_mkdir( DN_OMOPRE. '/list' );

$_filenames += [
	'omolist_old_emdb'	=> DN_PREP. '/omolist/emdb-<id>.txt' ,
	'omolist_old_pdb'	=> DN_PREP. '/omolist/pdb-<id>.txt' ,
	'omolist'			=> DN_OMOPRE. '/list/<id>.json' ,
];

$dn = '/yorodumi/sqlite/omokage_pre';
define( 'FN_DB_S',  $dn. '/profdb_s.sqlite' );
define( 'FN_DB_SS', $dn. '/profdb_ss.sqlite' );
