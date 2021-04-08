<?php
require_once( "met-common.php" );
define( 'SAS_MET', 'solution scattering' );
define( 'STR_REP', []);

//. main
foreach ( _idloop( 'sas_json', 'PDBからmet収集' ) as $fn ) {
	if ( _count( 'sas', 0 ) ) break;
	$id = _fn2id( $fn );
//	if ( rand(0,1000) != 0 ) continue; //----------
//	if ( $id != '5xsg' ) continue; //------
	$fn_out = _fn( 'sas_metjson', $id );
//	if ( _newer( $fn_out, $fn ) ) continue;

	$json = _json_load2( $fn );
	$year = explode( '-', $json->sas_scan[0]->measurement_date )[0];

	
//	unset( $json->pdbx_poly_seq_scheme );
	$data = [];

	//.. method
	_add_data( 'm', SAS_MET, 'structure determination', '-' );

	_get_from_json(
		'm', 'sas_model.type_of_model' , 'SAS model type'
	);

	//.. sortware 
	_get_from_json(
		's', 'sas_model.software' , 'SAS modeling'
	);
	
	_get_from_json(
		's', 'sas_p_of_R_details.software_p_of_R', 'P(R) calculation'
	);

	//.. radiation source
	_get_from_json(
		'e', 'sas_beam.type_of_source', 'radiation source'
	);

	//.. diffrn_source
	$for = _instr( 'synchrotron', strtolower( $json->sas_beam[0]->type_of_source ) )
		? 'synchrotron site' : 'radiation source';
	_get_from_json(
		'e', 'sas_beam.instrument_name', $for
	);

	//.. test
	_clean_data();
	_json_save( $fn_out, $data );
//	_line( $id );
//	_m( json_encode( $data, JSON_PRETTY_PRINT ) );
//	_pause();
}

_delobs_misc( 'sas_metjson', 'sasbdb' );

//. function
//.. _get_from_json
function _get_from_json( $mcat, $cat_item, $for = '' ) {
	global $json;
	list( $cat, $item ) = explode( '.', $cat_item );

	//- cif category loop
	foreach ( (array)$json->$cat as $c ) {
		foreach ( explode( '@|@', $c->$item ) as $v ) {
			_add_data( $mcat, $v, $for, SAS_MET );
		}
	}
}
