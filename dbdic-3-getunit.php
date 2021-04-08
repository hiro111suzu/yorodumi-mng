<?php
require 'dbdic-common.php';

//. main
$out = array_merge(
	_get( FN_MMCIF_DIC, 'mmCIF' ) ,
//	_get( FN_V5_DIC ) ,
	_get( FN_SAS_DIC, 'SASCIF' ) 
);
_comp_save( DN_DATA . '/cif_unit.json.gz', $out );
_line( "単位の種類" );
_m( implode( "\n", _uniqfilt( array_values( $out ) ) ) );
_end();

//. func
function _get( $fn, $dicname ) {
	global $types;
	$data = [];
	_line( "単位取得", $dicname );
//	_m( $fn );
	foreach ( _split_cifdic( _gzload( $fn ) )as $a ) {
		$name = trim( $a[1], '_' );
		$name = preg_replace( '/^.+?\.|\[|\]/', '', $name );
		$cont = trim( $a[2] );
		if ( ! _instr( '_item_units.code', $cont ) ) continue;
		
		$cont = preg_replace( 
			[ '/^.+_item_units.code +/s', '/[ \n].*$/s' ],
			[ '', '' ], $cont
		);
		$cont = trim( $cont, ' \'' );
		if ( $cont == 'arbitrary' ) continue;

		$cont = strtr( $cont, [
			'pascals'		=> 'Pa' ,
			'tera'			=> 'T' ,
			'giga'			=> 'G' ,
			'mega'			=> 'M' ,
			'kilo'			=> 'k' ,
			'centi' 		=> 'c' ,
			'millimetres' 	=> 'mm' ,
			'milli' 		=> 'm' ,
			'microns'		=> '&micro;m' ,
			'micro'			=> '&micro;' ,
			'nanometers' 	=> 'nm' ,
			'nano' 			=> 'n' ,
			'pico' 			=> 'p' ,
			'femto'			=> 'f' ,
			'celsius'		=> '&deg;C',
			'hertz'			=> 'Hz' ,
			'joules'		=> 'J' ,

			'amperes'		=> 'A' ,
			'watts'			=> 'W' ,
			'minutes'		=> 'min.' ,
			'minute'		=> 'min.' ,
			'seconds'		=> 'sec.' ,
			'degrees'		=> '&deg;' ,
			'metres'		=> 'm' ,
//			'metre'			=> 'm' ,
			'volts'			=> 'V' ,
			'liters'		=> 'L' ,
			'electrons_angstrom' => 'e/&Aring;' ,
			'electrons'		=> 'e' ,
			'electron_'		=> 'e' ,
			'electron'		=> 'e' ,
			'percent'		=> '%' ,
			'grams'			=> 'g' ,
			'daltons'		=> 'Da' ,
			'angstroms' 	=> '&Aring;' ,
			'angstrom'		=> '&Aring;' ,
			'kelvins'		=> 'K' ,
			'kelvin'		=> 'K' ,

			'_squared'		=> '<sup>2</sup>' ,
			'_cubed'		=> '<sup>3</sup>' ,
			'8pi2'			=> '8&pi;<sup>2</sup>' ,

			'_per_'			=> '/',
			'reciprocal_'	=> '/' ,
			'cubic_metre'	=> 'm<sup>3</sup>' ,
			'_' => ' ' ,
//			'metre', 'm' ,
//			'meter' => 'm' ,
//			'mete' => 'm' ,
		]);

//		_pause( "$name: $cont" );
		$data[ $name ] = $cont;
	}
	$c = count( $data );
	if ( $c == 0 ) {
		_problem( "$dicname: 単位取得失敗" );
	} else {
		_m( "$c 個の単位情報を取得" );
	}
	return $data;
}

