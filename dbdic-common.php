<?php
require 'commonlib.php';

//. file name

define( 'FN_MMCIF_DIC'  , DN_DATA. '/pdb/mmcif_pdbx_v50.dic.gz' );
define( 'FN_SAS_DIC'    , DN_DATA. '/sas/mmcif_sas.dic.gz' );
define( 'FN_MMCIF_JSON' , DN_DATA. '/pdb/mmcif_dic.json.gz' );
define( 'FN_SAS_JSON'   , DN_DATA. '/sas/sascif_dic.json.gz' );

define( 'U_MMCIF' , 'http://mmcif.wwpdb.org/dictionaries/ascii/mmcif_pdbx_v50.dic' );
define( 'U_SAS'   , 'http://mmcif.wwpdb.org/dictionaries/ascii/mmcif_sas.dic' );

//. function
function _split_cifdic( $str ) {
//	_m( $str );
	preg_match_all( '/\nsave_(.+?)\n(.+?)save_\n/s', $str, $match, PREG_SET_ORDER );
	return $match;
}
