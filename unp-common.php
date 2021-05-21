<?php
require_once( "commonlib.php" );
//- define( 'DN_UNP_PREP', DN_PREP . '/unp' ); -> commonlib
//- define( 'FN_DBDATA_PDB'       , DN_UNP_PREP. '/dbdata_pdb.json.gz' );  -> commonlib
//- define( 'FN_DBDATA_EMDB'      , DN_UNP_PREP. '/dbdata_emdb.json.gz' );  -> commonlib
//- define( 'FN_DBDATA_SASBDB'    , DN_UNP_PREP. '/dbdata_sasbdb.json.gz' );  -> commonlib
//- _mkdir( DN_UNP_PREP );

define( 'FN_PDBID2UNPID'  , DN_UNP_PREP. '/pdbid2unpid.json.gz' );
define( 'FN_EMDB_DATA'    , DN_UNP_PREP. '/emdb_data.json.gz' );
define( 'FN_EMDB_REFTYPES', DN_UNP_PREP. '/emdb_reftypes.tsv' );
define( 'FN_SASID2UNPID'  , DN_UNP_PREP. '/sasid2unpid.json.gz' );

define( 'FN_EMDB_UNPIDS_ANNOT', DN_EDIT. '/unpid_emdb_annot.tsv' );

//define( 'FN_DBDATA'       , DN_UNP_PREP. '/dbdata.json.gz' );

define( 'FN_IDS_NOT_FOUND' , DN_UNP_PREP. '/ids_not_found.tsv' );

define( 'FN_DATEINFO',    DN_UNP_PREP. '/dateinfo.json.gz' );
define( 'FN_ALL_UNPIDS',  DN_UNP_PREP. '/all_unpids.txt.gz' );
define( 'DN_UNPXML', DN_FDATA . '/unp' );
_mkdir( DN_UNPXML );
$_filenames += [
	'unp_xml' => DN_UNPXML. '/<id>.xml' ,
	'unp_json' => DN_DATA. '/unp/<id>.json.gz'
];

define( 'URL_UNIPROT_XML', 'http://www.uniprot.org/uniprot/<id>.xml' );

function _db_name2categ( $name ) {
	return [
		'ec' => 'f' ,
		'go' => 'f' ,
		'rt' => 'f' ,
		'pf' => 'h' ,
		'in' => 'h' ,
		'pr' => 'h' ,
		'ct' => 'h' ,
	][ $name ] ?: 'c';
}

