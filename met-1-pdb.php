<?php
require_once( "met-common.php" );
define( 'NMR_EXPMET',  _file( DN_DATA. '/pdb/nmr_exptype.txt' ) );

//. main
foreach ( _idloop( 'pdb_json', 'PDBからmet収集' ) as $fn ) {
	if ( _count( 'pdb', 0 ) ) break;
	$id = _fn2id( $fn );
	$fn_out = _fn( 'pdb_metjson', $id );
	if ( _newer( $fn_out, $fn ) && !FLG_REDO ) continue;

	$json = _json_load2( $fn );
	_cif_rep( $json );
	$year = explode( '-' ,
		$json->pdbx_database_status[0]->recvd_initial_deposition_date )[0];

//	unset( $json->pdbx_poly_seq_scheme );
	$data = [];

	//.. method
	$mets = [];
	foreach ( (array)$json->exptl as $c ) {
		$m = strtr( strtolower( $c->method ), [
			'nmr' => 'NMR' ,
			'x-ray' => 'X-ray' ,
			'epr' => 'EPR' ,
		]);
		_add_data( 'm', $m, 'structure determination', '-' );
		$mets[] = $m;
	}
	$met =count( $mets ) == 1 ? $mets[0] : '';
//	if ( $met == 'X-ray diffraction' ) continue;
//	if ( $met != 'solution NMR' ) continue;


	//.. sortware 
	_get_from_json(
		's', 'software.name', '->classification'
	);

	//.. assembly
	_get_from_json(
		's', 'pdbx_struct_assembly.method_details', 'assembly computation'
	);

	_get_from_json(
		'm', 'pdbx_struct_assembly_auth_evidence.experimental_support', 'assembly experiment'
	);


	//.. em
	//... sample - cryo/stain
	if ( $add = _json_load2([ 'pdb_add', $id ]) ) {
		if ( $add->stained )
			_add_data( 'm', 'negative staining', 'EM sample preparation', $met );

		if ( $add->cryo )
			_add_data( 'm', 'cryo EM', 'EM sample preparation', $met );
	}

	//... reconstruction method
	_get_from_json(
		'm', 'em_experiment.reconstruction_method', '3D reconstruction'
	);

	//... em 
	_get_from_json(
		'e', 'em_imaging.microscope_model' 
	);

	_get_from_json(
		'e', 'em_imaging.electron_source'
	);

	_get_from_json(
		'e', 'em_sample_support.grid_type'
	);
	_get_from_json(
		'e', 'em_sample_support.grid_material'
	);

	_get_from_json(
		'e', 'em_imaging_optics.phase_plate'
	);

	//... em_software
	_get_from_json(
		's', 'em_software.name', '->category'
	);

	//... vitrification 
	_get_from_json(
		'e', 'em_vitrification.instrument', 'vitrification'
	);

	//... detector
	_get_from_json(
		'e', 'em_image_recording.film_or_detector_model', 'detector'
	);

	//... energy filter
	_get_from_json(
		'e', 'em_imaging_optics.energyfilter_name', 'energy filter'
	);

	//... em_image_recording / アイテム: detector_mode
	_get_from_json(
		'm', 'em_image_recording.detector_mode'
	);
	//... em_3d_fitting / アイテム: ref_protocol
	_get_from_json(
		'm', 'em_3d_fitting.ref_protocol', 'refine protocol'
	);

	//.. x-ray
	//... crystalization method
	_get_from_json(
		'm', 'exptl_crystal_grow.method', 'crystalization'
	);

	//... radiation source
	_get_from_json(
		'e', 'diffrn_source.source', 'radiation source'
	);

	//... radiation type
	_get_from_json(
		'e', 'diffrn_source.type', 'radiation type' 
	);

	//... diffrn_detector  type
	_get_from_json(
		'e', 'diffrn_detector.type', 'detector type' 
	);

	//... diffrn_source
	_get_from_json(
		'e', 'diffrn_source.pdbx_synchrotron_site'
	);

	//... data col - detector
	_get_from_json(
		'e', 'diffrn_detector.detector'
	);

	//... refine method
	_get_from_json(
		'm', 'refine.pdbx_method_to_determine_struct', 'refinement'
	);

	//... phasing.method
	_get_from_json(
		'm', 'phasing.method', 'phasing'
	);

	//.. NMR
	//... NMRスペクトロメーター (2通りある)
	foreach ( (array)$json->pdbx_nmr_spectrometer as $c ) {
		if ( $c->manufacturer . $c->model == '' ) continue;
		$s = stripos( $c->manufacturer, $c->model ) === 0 
			? $c->model
			: implode( ' ', [ $c->manufacturer, $c->model ] )
		;
		_add_data(
			'e', $s, 'spectrometer', $met
		);
	}
	_get_from_json(
		'e', 'pdbx_nmr_spectrometer.type', 'spectrometer'
	);

	//... refine
	_get_from_json(
		'm', 'pdbx_nmr_refine.method', 'NMR refinement'
	);
	//... software
	_get_from_json(
		's', 'pdbx_nmr_software.name', '->classification'
	);
	//... datacol
	foreach ( (array)$json->pdbx_nmr_exptl as $c ) {
		if ( ! $c->type ) continue;
		foreach ( NMR_EXPMET as $m ) {
			if ( preg_match( '/\b'. $m . '\b/i', $c->type ) )
			_add_data(
				'm', $m, 'NMR experiment type', $met
			);
//			_m( "$id: $m" );
		}
	}
/*
	_get_from_json(
		'm', 'pdbx_nmr_exptl.type', 'experiment type', 'NMR experiment type'
	);
*/
	//.. test
	_clean_data();
	_json_save( $fn_out, $data );
//	_line( $id );
//	_m( json_encode( $data, JSON_PRETTY_PRINT ) );
//	_pause();
}

_delobs_pdb( 'pdb_metjson' );

//. function
//.. _get_from_json
function _get_from_json( $mcat, $cat_item, $for = '' ) {
	global $json, $met;
	list( $cat, $item ) = explode( '.', $cat_item );
	
	$for = $for ?: strtr( $item, [ 'pdbx_' => '', '_' => ' ' ] ) ;

	//- '->'指定
	$key = '';
	list( $k, $v ) = explode( '>', $for, 2 );
	if ( $k == '-' ) { 
		$key = $v;
		$for = '';
	}

	//- cif category loop
	foreach ( (array)$json->$cat as $c ) {
		$f = $for != ''
			? $for
			: $c->$key
		;
		foreach ( explode( '@|@', $c->$item ) as $v ) {
			_add_data( $mcat, $v, $f, $met  );
		}
	}
}

