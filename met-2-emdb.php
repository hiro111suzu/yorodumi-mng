<?php
require_once( "met-common.php" );

//. main loop
$flg_changed = false;
foreach ( _idloop( 'emdb_old_json', 'EMDBからmet収集' ) as $fn_json ) {
	if ( _count( 'emdb', 0 ) ) break;
	$id = _fn2id( $fn_json );
	$fn_add = _fn( 'emdb_add', $id );
	$fn_out = _fn( 'emdb_metjson', $id );
	if (
		_newer( $fn_out, $fn_json ) 
		&& _newer( $fn_out, $fn_add ) 
	) continue;

	$json = _json_load2( $fn_json );
	$json_add = _json_load2( $fn_add );
	$year = explode( '-', $json->deposition->depositionDate )[0];

	$data = [];

	//.. method
	$met_code = $json_add->met;
	$met = $met_code == 2
		? 'electron crystallography'
		: 'electron microscopy'
	;
	_add_data( 'm', $met, 'structure determination', '-' );

	//.. reconstruction method
	_add_data_emdb( 'm', _met_code( $met_code, 'code2name' ), '3D reconstruction' );

	//.. cryo / stain
	if ( $json_add->staied, $json ) )
		_add_data( 'm', 'negative staining', 'EM sample preparation', $met );

	if ( $json_add->cryo, $json ) )
		_add_data( 'm', 'cryo EM', 'EM sample preparation', $met );

	//.. vitrification 
	foreach ( (array)$json->experiment->vitrification as $c ) {
		_add_data_emdb( 'e', $c->instrument, 'vitrification' );
	}

	//.. imaging
	foreach ( (array)$json->experiment->imaging as $c ) {
		_add_data_emdb( 'e', $c->microscope, 'microscope model' );
		_add_data_emdb( 'e', $c->detector, 'detector' );
		_add_data_emdb( 'e', $c->energyFilter, 'energy filter' );
	}

	//.. em software
	foreach ( (array)$json->processing->reconstruction as $c ) {
		_add_data_emdb( 's', $c->software, '3D reconstruction', true );
	}

	foreach ( (array)$json->experiment->fitting as $c ) {
		_add_data_emdb( 's', $c->software, 'modeling', true );
	}

	//.. test
	_clean_data();
	_json_save( $fn_out, $data );
/*
	_line( $id );
	_m( json_encode( $data, JSON_PRETTY_PRINT ) );
	_pause();
*/
}
_delobs_emdb( 'emdb_metjson' );

//. function
//.. _add_data_emdb
function _add_data_emdb( $categ, $name, $for, $flg_split = false ) {
	global $met;
	if (
		100 < strlen( $name ) ||
		in_array( strtolower( $name ), [
			'eye', 'na', 'n-a', 'n/a', 'none', '-', 'other'
		]) 
	) return;

	foreach ( _split( $name, $flg_split ) as $n ) {
		_add_data( $categ, $n, $for, $met );
	}
}
//.. _split
function _split( $name, $flg ) {
	return $flg ? explode( ',', strtr( $name, [
			' and ' => ',' ,
			'/' => ',' ,
			//- 個別系
			'TOM, Toolbox' => 'TOM Toolbox' ,
		]))
		: [ $name ]
	;
}
