<?php
require 'commonlib.php';

$types = [];
//. main
$u =_get( DN_FDATA . '/mmcif_pdbx_v40.dic' );

$u .= _get( DN_FDATA . '/mmcif_sas.dic' );
file_put_contents( DN_PREP . '/cif_unit.txt', $u );

_m( implode( "\n", array_keys( $types ) ) );

//. func
function _get( $fn ) {
	global $types;
	$data = [];
	preg_match_all( '/\nsave_(.+?)\n(.+?)save_\n/s',
		file_get_contents( $fn ), $match, PREG_SET_ORDER );
	foreach ( $match as $a ) {
		$name = trim( $a[1], '_' );
		$name = preg_replace( '/^.+?\./', '', $name );
		$cont = trim( $a[2] );
		if ( ! _instr( '_item_units.code', $cont ) ) continue;
		
		$cont = preg_replace( 
			[ '/^.+_item_units.code +/s', '/[ \n].*$/s' ],
			[ '', '' ], $cont
		);
		$cont = trim( $cont, ' \'' );
		if ( $cont == 'arbitrary' ) continue;
//		_pause( "[$name]\n$cont" );
		
		
		$cont = strtr( $cont, [
			'pascals' => 'Pa' ,
			'giga'	=> 'g' ,
			'mega'	=> 'M' ,
			'kilo'	=> 'k' ,
			'centi' => 'c' ,
			'milli' => 'm' ,
			'nano' => 'n' ,
			'pico' => 'p' ,
			'femto' => 'f' ,

			'amperes' => 'A' ,
			'watts'	=> 'W' ,
			'minutes' => 'min.' ,
			'minute' => 'min.' ,
			'seconds' => 'sec.' ,
			'degrees' => 'deg.' ,
			'metres' => 'm' ,
			'metre' => 'm' ,
			'volts'	=> 'V' ,
			'liters' => 'L' ,
			'electrons' => 'e' ,
			'electron' => 'e' ,
			'percent'	=> '%' ,
			'grams'	=> 'g' ,
			'daltons' => 'Da' ,
			'angstroms' => 'A' ,
			'angstrom'	=> 'A' ,

			'_squared' => '^2' ,
			'_cubed' => '^3' ,
			'8pi2' => '8pi^2' ,

			'_per_'	=> '/',
			'reciprocal_' => '1/' ,
			'_' => ' ' ,
			'cubic m' => 'm^3' ,
		]);
		
		$types[ $cont ] = 1;
//		_pause( "$name: $cont" );
		$data[ $name ] = $cont;
	}
	$ret = '';
	foreach ( $data as $t => $u ) {
		$ret .= "'$t' => '$u',\n";
	}
	return $ret;

}

