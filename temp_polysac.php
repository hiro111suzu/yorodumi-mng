<?php
require_once( "commonlib.php" );

//. coopy
_mkdir( DN_DATA. '/chem/snfg_icon/' );
$from = DN_PREP. '/snfg_icon/<name>.jpg';
$to = DN_DATA. '/chem/snfg_icon/<id>.jpg';

foreach ( _idlist( 'chem' ) as $id ) {
	$json = json_decode( _ezsqlite([
		'dbname' => 'chem' ,
		'select' => 'json' ,
		'where'  => [ 'id', $id ] ,
	]));
//	_pause([ $id => $json ]);
	if ( ! $json->snfg_sym ) continue;
	$fn_from = strtr( $from, [ '<name>' => $json->snfg_sym ] );
	if ( file_exists( $fn_from ) )
		_m( "$id => ". $json->snfg_sym );
	else
		_m( 'No file: '. $fn_from, -1 );
	copy(
		$fn_from ,
		strtr( $to  , [ '<id>'   => $id ] )
	);
}

//. download img
/*
$url = "https://www.ncbi.nlm.nih.gov/glycans/images/symbolnomenclature-Image<num>.jpg";
_mkdir( DN_PREP. '/snfg_icon/' );
$fn = DN_PREP. '/snfg_icon/<name>.jpg';


foreach ( [
	'Hexose'	=> '004' ,
	'Glc'	=> '005' ,
	'Man'	=> '006' ,
	'Gal'	=> '007' ,
	'Gul'	=> '008' ,
	'Alt'	=> '009' ,
	'All'	=> '010' ,
	'Tal'	=> '011' ,
	'Ido'	=> '012' ,
	'HexNAc'	=> '013' ,
	'GlcNAc'	=> '014' ,
	'ManNAc'	=> '015' ,
	'GalNAc'	=> '016' ,
	'GulNAc'	=> '017' ,
	'AltNAc'	=> '018' ,
	'AllNAc'	=> '019' ,
	'TalNAc'	=> '020' ,
	'IdoNAc'	=> '021' ,
	'Hexosamine'	=> '022' ,
	'GlcN'	=> '023' ,
	'ManN'	=> '024' ,
	'GalN'	=> '025' ,
	'GulN'	=> '026' ,
	'AltN'	=> '027' ,
	'AllN'	=> '028' ,
	'TalN'	=> '029' ,
	'IdoN'	=> '030' ,
	'Hexuronate'	=> '031' ,
	'GlcA'	=> '032' ,
	'ManA'	=> '033' ,
	'GalA'	=> '034' ,
	'GulA'	=> '035' ,
	'AltA'	=> '036' ,
	'AllA'	=> '037' ,
	'TalA'	=> '038' ,
	'IdoA'	=> '039' ,
	'Deoxyhexose'	=> '040' ,
	'Qui'	=> '041' ,
	'Rha'	=> '042' ,
	'6dGul'	=> '043' ,
	'6dAlt'	=> '044' ,
	'6dTal'	=> '045' ,
	'Fuc'	=> '046' ,
	'DeoxyhexNAc'	=> '047' ,
	'QuiNAc'	=> '048' ,
	'RhaNAc'	=> '049' ,
	'6dAltNAc'	=> '050' ,
	'6dTalNAc'	=> '051' ,
	'FucNAc'	=> '052' ,
	'Di-deoxyhexose'	=> '053' ,
	'Oli'	=> '054' ,
	'Tyv'	=> '055' ,
	'Abe'	=> '056' ,
	'Par'	=> '057' ,
	'Dig'	=> '058' ,
	'Col'	=> '059' ,
	'Pentose'	=> '060' ,
	'Ara'	=> '061' ,
	'Lyx'	=> '062' ,
	'Xyl'	=> '063' ,
	'Rib'	=> '064' ,
	'Deoxynonulosonate'	=> '065' ,
	'Kdn'	=> '066' ,
	'Neu5Ac'	=> '067' ,
	'Neu5Gc'	=> '068' ,
	'Neu'	=> '069' ,
	'Sia'	=> '070' ,
	'Di-deoxynonulosonate'	=> '071' ,
	'Pse'	=> '072' ,
	'Leg'	=> '073' ,
	'Aci'	=> '074' ,
	'4eLeg'	=> '075' ,
	'Bac'	=> '077' ,
	'LDmanHep'	=> '078' ,
	'Kdo'	=> '079' ,
	'Dha'	=> '080' ,
	'DDmanHep'	=> '081' ,
	'MurNAc'	=> '082' ,
	'MurNGc'	=> '083' ,
	'Mur'	=> '084' ,
	'Api'	=> '086' ,
	'Fru'	=> '087' ,
	'Tag'	=> '088' ,
	'Sor'	=> '089' ,
	'Psi'	=> '090' ,
] as $name => $num ) {
	copy(
		strtr( $url, [ '<num>' => $num ]  ),
		strtr( $fn,  [ '<name>' => $name ] )
	);
}
*/
