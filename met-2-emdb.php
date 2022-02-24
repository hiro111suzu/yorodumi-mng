<?php
require_once( "met-common.php" );
//. define
$d = [];
foreach ( explode( "---", <<<EOD
e
crystal formation
structure_determination[0]->preparation[*]->crystal_formation->instrument
---
e
high pressure freezing
structure_determination[0]->preparation[*]->high_pressure_freezing->instrument
---
e
focused ion beam
structure_determination[0]->preparation[*]->sectioning->focused_ion_beam->instrument
---
e
ultramicrotomy
structure_determination[0]->preparation[*]->sectioning->ultramicrotomy->instrument
---
e
vitrification
structure_determination[0]->preparation[*]->vitrification->instrument
---
e
microscope model
structure_determination[0]->microscopy[*]->microscope
---
e
detector
structure_determination[0]->microscopy[*]->image_recording[*]->film_or_detector_model
---
e
energy filter
structure_determination[0]->microscopy[*]->specialist_optics->energy_filter->name
---
e
specimen holder
structure_determination[0]->microscopy[*]->specimen_holder
---
e
specimen holder
structure_determination[0]->microscopy[*]->specimen_holder_model
---
e
aberration corrector
structure_determination[0]->microscopy[*]->specialist_optics->sph_aberration_corrector
---
e
phase plate
structure_determination[0]->microscopy[*]->specialist_optics->phase_plate
---
e
grid type
structure_determination[0]->preparation[*]->grid->model
---
m
detector mode
structure_determination[0]->microscopy[*]->image_recording[*]->detector_mode
---
s
3D reconstruction
structure_determination[0]->processing[*]->final_three_d_classification->software[*]->name
---
s
3D reconstruction
structure_determination[0]->processing[*]->final_three_d_classification->software[*]->name
---
s
3D reconstruction
structure_determination[0]->processing[*]->initial_angle_assignment->software[*]->name
---
s
3D reconstruction
structure_determination[0]->processing[*]->final_angle_assignment->software[*]->name
---
s
3D reconstruction
structure_determination[0]->processing[*]->final_reconstruction->software[*]->name
---
s
3D reconstruction
structure_determination[0]->processing[*]->final_three_d_classification->software[*]->name
---
s
3D reconstruction
structure_determination[0]->processing[*]->initial_angle_assignment->software[*]->name
---
s
CTF correction
structure_determination[0]->processing[*]->ctf_correction->software[*]->name
---
s
EM volume extraction
structure_determination[0]->processing[*]->extraction->software[*]->name
---
s
Merging
structure_determination[0]->processing[*]->merging_software_list->software[0]->name
---
s
molecular replacement
structure_determination[0]->processing[*]->molecular_replacement->software[0]->name
---
s
segment selection
structure_determination[0]->processing[*]->segment_selection->software[*]->name
---
s
symmetry_determination
structure_determination[0]->processing[*]->symmetry_determination_software_list->software[0]->name
EOD
) as $block ) {
	$a = explode( "\n", trim( $block ) );
	$d[ trim( $a[1] ) ] = [
		'categ'  => trim( $a[0] ) ,
		'branch' => trim( $a[2] )
	];
}
define( 'EXT_AR', $d );

//. main loop
$flg_changed = false;
foreach ( _idloop( 'emdb_old_json', 'EMDBからmet収集' ) as $fn_json ) {
	if ( _count( 'emdb', 0 ) ) break;
	$id = _fn2id( $fn_json );
	$fn_add = _fn( 'emdb_add', $id );
	$fn_out = _fn( 'emdb_metjson', $id );
	if ( FLG_REDO )
		_del( $fn_out );
	if (
		_newer( $fn_out, $fn_json ) 
		&& _newer( $fn_out, $fn_add ) 
	) continue;

	$json = _emdb_json3_rep( _json_load2( $fn_json ) );
	$json_add = _json_load2( $fn_add );
	$year = explode( '-', $json_add->ddate )[0];

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
	if ( $json_add->staied )
		_add_data( 'm', 'negative staining', 'EM sample preparation', $met );

	if ( $json_add->cryo )
		_add_data( 'm', 'cryo EM', 'EM sample preparation', $met );

	//.. others
	foreach ( EXT_AR as $for => $c ) {
		extract( $c ); //- $categ, $branch
		foreach ( _branch( $json, $branch ) as $name ) {
			_add_data_emdb( $categ, $name, $for );
		}
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
