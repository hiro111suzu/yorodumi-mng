<?php

$_filenames += [
	'sas_cif'		=> DN_FDATA . "/sasbdb/<name>.sascif" ,
	'sas_json'		=> DN_DATA . '/sas/json/<name>.json.gz' ,
	'sas_split_cif'	=> DN_DATA . '/sas/splitcif/<name>.cif' ,
	'sas_img'		=> DN_DATA . '/sas/img/<name>.jpg' ,
	'sas_vq30'		=> DN_DATA . '/sas/vq/<name>-vq30.pdb' ,
	'sas_vq50'		=> DN_DATA . '/sas/vq/<name>-vq50.pdb' ,
	'sas_vqerror'	=> DN_PREP . '/sas/vq_error/<name>.txt' ,
];

define( 'FN_SASIDS',		DN_DATA . '/ids/sasbdb.txt' );
define( 'FN_SUBDATA_PRE',	DN_PREP . '/sas/subdata_pre.json' );
define( 'FN_SUBDATA',		DN_DATA . '/sas/subdata.json.gz' );
define( 'FN_IDTABLE',		DN_DATA . '/sas/idtable.tsv' );
define( 'FN_SAS_MID',		DN_PREP . '/sas/sas_mid.json' );

//define( 'FN_MID2ID', DN_DATA . '/sas/sas_mid2id.json' );
//define( 'FN_ID2MID', DN_DATA . '/sas/sas_id2mid.json' );

