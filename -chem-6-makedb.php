chem_comp の SQlite DBを作成

<?php
//. init
require_once( "commonlib.php" );

$_filenames += [
	'bird_img'	=> DN_DATA. '/bird/img/<id>.jpg' ,
	'bird_img2'	=> DN_DATA. '/bird/img/<id>.svg' ,
];

define( 'SYN_MAXLEN', 60 );

//.. atom names
define( 'ATOM_NAME', [ 
	 'H'	=>	'Hydrogen' ,
	 'He'	=>	'Helium' ,
	 'Li'	=>	'Lithium' ,
	 'Be'	=>	'Beryllium' ,
	 'B'	=>	'Boron' ,
	 'C'	=>	'Carbon' ,
	 'N'	=>	'Nitrogen' ,
	 'O'	=>	'Oxygen' ,
	 'F'	=>	'Fluorine' ,
	 'Ne'	=>	'Neon' ,
	 'Na'	=>	'Sodium' ,
	 'Mg'	=>	'Magnesium' ,
	 'Al'	=>	'Aluminum' ,
	 'Si'	=>	'Silicon' ,
	 'P'	=>	'Phosphorus' ,
	 'S'	=>	'Sulfur' ,
	 'Cl'	=>	'Chlorine' ,
	 'Ar'	=>	'Argon' ,
	 'K'	=>	'Potassium' ,
	 'Ca'	=>	'Calcium' ,
	 'Sc'	=>	'Scandium' ,
	 'Ti'	=>	'Titanium' ,
	 'V'	=>	'Vanadium' ,
	 'Cr'	=>	'Chromium' ,
	 'Mn'	=>	'Manganese' ,
	 'Fe'	=>	'Iron' ,
	 'Co'	=>	'Cobalt' ,
	 'Ni'	=>	'Nickel' ,
	 'Cu'	=>	'Copper' ,
	 'Zn'	=>	'Zinc' ,
	 'Ga'	=>	'Gallium' ,
	 'Ge'	=>	'Germanium' ,
	 'As'	=>	'Arsenic' ,
	 'Se'	=>	'Selenium' ,
	 'Br'	=>	'Bromine' ,
	 'Kr'	=>	'Krypton' ,
	 'Rb'	=>	'Rubidium' ,
	 'Sr'	=>	'Strontium' ,
	 'Y'	=>	'Yttrium' ,
	 'Zr'	=>	'Zirconium' ,
	 'Nb'	=>	'Niobium' ,
	 'Mo'	=>	'Molybdenum' ,
	 'Tc'	=>	'Technetium' ,
	 'Ru'	=>	'Ruthenium' ,
	 'Rh'	=>	'Rhodium' ,
	 'Pd'	=>	'Palladium' ,
	 'Ag'	=>	'Silver' ,
	 'Cd'	=>	'Cadmium' ,
	 'In'	=>	'Indium' ,
	 'Sn'	=>	'Tin' ,
	 'Sb'	=>	'Antimony' ,
	 'Te'	=>	'Tellurium' ,
	 'I'	=>	'Iodine' ,
	 'Xe'	=>	'Xenon' ,
	 'Cs'	=>	'Cesium' ,
	 'Ba'	=>	'Barium' ,
	 'La'	=>	'Lanthanum' ,
	 'Ce'	=>	'Cerium' ,
	 'Pr'	=>	'Praseodymium' ,
	 'Nd'	=>	'Neodymium' ,
	 'Pm'	=>	'Promethium' ,
	 'Sm'	=>	'Samarium' ,
	 'Eu'	=>	'Europium' ,
	 'Gd'	=>	'Gadolinium' ,
	 'Tb'	=>	'Terbium' ,
	 'Dy'	=>	'Dysprosium' ,
	 'Ho'	=>	'Holmium' ,
	 'Er'	=>	'Erbium' ,
	 'Tm'	=>	'Thulium' ,
	 'Yb'	=>	'Ytterbium' ,
	 'Lu'	=>	'Lutetium' ,
	 'Hf'	=>	'Hafnium' ,
	 'Ta'	=>	'Tantalum' ,
	 'W'	=>	'Tungsten' ,
	 'Re'	=>	'Rhenium' ,
	 'Os'	=>	'Osmium' ,
	 'Ir'	=>	'Iridium' ,
	 'Pt'	=>	'Platinum' ,
	 'Au'	=>	'Gold' ,
	 'Hg'	=>	'Mercury' ,
	 'Tl'	=>	'Thallium' ,
	 'Pb'	=>	'Lead' ,
	 'Bi'	=>	'Bismuth' ,
	 'Po'	=>	'Polonium' ,
	 'At'	=>	'Astatine' ,
	 'Rn'	=>	'Radon' ,
	 'Fr'	=>	'Francium' ,
	 'Ra'	=>	'Radium' ,
	 'Ac'	=>	'Actinium' ,
	 'Th'	=>	'Thorium' ,
	 'Pa'	=>	'Protactinium' ,
	 'U'	=>	'Uranium' ,
]);

//.. data
define( 'CHEMID2PDBID', _json_load( DN_DATA. '/chem/chemid2pdbid.json.gz' ) );
define( 'ANNOT', _tsv_load2( DN_PREP. '/chem/chem_annot.tsv' ) );
define( 'IDMAP', _json_load( DN_PREP. '/chem/chemid_map.json.gz' ) );
define( 'PRD_INFO', _json_load( DN_PREP. '/prd/prd_info.json.gz' ) );
define( 'CHEM2PRD', _json_load( DN_PREP. '/prd/chemid2prdid.json.gz' ) );
define( 'WIKIPE_ANNOT', _json_load( DN_PREP. '/chem/annot_wikipe.json.gz' ) );

foreach ( CHEM2PRD as $chem_id => $prd_id ) {
	if ( ANNOT[ 'class' ][ $chem_id ] ) 
		_m( "重複情報 class: $chem_id: ". ANNOT[ 'class' ][ $chem_id ] );
	if ( ANNOT[ 'comment' ][ $chem_id ] ) 
		_m( "重複情報 comment: $chem_id: ". ANNOT[ 'comment' ][ $chem_id ] );
}


_comp_save( DN_DATA. '/chem/annot.json.gz', ANNOT );
//die();

//. prep DB
$sqlite = new cls_sqlw([
	'fn' => 'chem', 
	'cols' => [
		'id UNIQUE' ,
		'name COLLATE NOCASE' ,
		'pdbnum INTEGER' ,
		'weight REAL' ,
		'date' ,
		'inchikey' ,
		'idmap' ,
		'json' , //- syn, form, 
		'kw COLLATE NOCASE'
	],
	'new' => true ,
	'indexcols' => [ 'id', 'weight', 'date', 'pdbnum' ] ,
]);

//. bird
_count();

define( 'DBID_COUNT', _json_load( FN_DBID2STRCNT ) ); 

foreach ( _idloop( 'bird_json' ) as $pn ) {
	if ( _count( 500, 0 ) ) break;
	//.. init misc
	$id      = _numonly( _fn2id( $pn ) );
	$id_long = "PRD_$id";
	$json	 = _json_load2( $pn );
	$refmol = $json->pdbx_reference_molecule[0];
	$name = $refmol->name;
	$chem_id = $refmol->chem_comp_id;
	$json_chem = $chem_id ? _json_load2([ 'chem_json', $chem_id ]) : [];
	$comment = _comment( $chem_id );

	$date = [];
	foreach ( (array)$json->pdbx_prd_audit as $c )
		$date[] = $c->date;

	//.. シノニム
	$syn = [];
	foreach ( (array)$json->pdbx_reference_molecule_synonyms as $c ) {
		_add_syn( _reg_rep( $c->name, [
			'/ \[.+?\]/' => '' ,
			'/ \(.+?\)/' => '' ,
		]));
	}
	_names_form_dbs( $chem_id );
	$syn = _make_syn( $name );

	//.. type/class
	$cls = implode( '|', _uniqfilt( array_merge(
		explode( ', ', $refmol->class ) ,
		[ $json->chem_comp[0]->type, $refmol->type ],
	)));

	//.. img
	$img = '';
	if ( file_exists( _fn( 'bird_img2', $id ) ) )
		$img = 'svg';
	if ( file_exists( _fn( 'bird_img', $id ) ) )
		$img = 'jpg';

	//.. キーワード
	$kw = [
		$id_long ,
		$chem_id ,
		$name ,
		$syn ,
		$refmol->formula ,
		$cls ,
		implode( '|', $comment )
	];

	//- descriptor/identifier
	$inchikey = '';
	foreach ( (array)$json->pdbx_descriptor as $d ) {
		$kw[] = $d->descriptor;
		if ( $d->type == 'InChIKey' )
			$inchikey = $d->descriptor;
	}
	foreach ( (array)$json->pdbx_identifier as $d )
		$kw[] = $d->identifier;

	if ( $json_chem ) {
		foreach ( (array)$json_chem->pdbx_descriptor as $d ) {
			$kw[] = $d->descriptor;
			if ( $d->type == 'InChIKey' )
				$inchikey = $d->descriptor;
		}
		foreach ( (array)$json_chem->pdbx_identifier as $d )
			$kw[] = $d->identifier;
	}

	//- いろいろ
	foreach ([
		[ 'pdbx_reference_molecule_annotation', 'text' ] ,
		[ 'pdbx_reference_molecule_family', 'name'  ],
		[ 'pdbx_reference_entity_list', 'details' ] ,
		[ 'pdbx_reference_molecule', 'compound_details' ],
		[ 'pdbx_reference_molecule', 'description' ],
	] as $a ) {
		list( $categ, $key ) = $a;
		foreach ( (array)$json->$categ as $c ) {
			$kw[] = $c->$key;
		}
	}

	//.. nikkaji name

	//.. set
	$sqlite->set([
//	_pause([
		$id_long ,
		$name ,
		DBID_COUNT[ "bd:$id_long" ],
		$refmol->formula_weight ,
		min( $date ) ,
		$inchikey ,
		IDMAP[ $chem_id ] ? json_encode( IDMAP[ $chem_id ] ) : '' ,
		json_encode( array_filter([
			'syn'     => $syn ,
			'comment' => $comment ,
			'class'   => $cls ,
			'nikkaji' => _nikkaji_name( $chem_id ) ,
			'img'     => $img ,
		]) ) ,
		implode( '||', _uniqfilt( $kw ) ) //- search word
	]);
}

//. chem

_count();
foreach ( _idloop( 'chem_json' ) as $pn ) {
	//.. init
	$chem_id = _fn2id( $pn );
	$json	 = _json_load2( $pn );
	$name = $json->chem_comp->name;
	$comment = _comment( $chem_id );

	//.. bird
	$prd_id = CHEM2PRD[ $chem_id ];
	list( $prd_type, $prd_name ) = $prd_id ? PRD_INFO[ $prd_id ] : [ '', '' ];

	//.. syn追加
	$syn = [];
	foreach ( explode( '; ', $json->chem_comp->pdbx_synonyms ) as $s )
		_add_syn( $s );
	_names_form_dbs( $chem_id );
	_add_syn( $prd_name );
	$syn = _make_syn( $name );

	//.. キーワード
	$kw = [
		$name ,
		$syn ,
		$json->chem_comp->formula ,
		$prd_id ,
		$prd_type ,
		$prd_name ,
		$json->chem_comp->type ,
		implode( '|', $comment ) ,
		$cls ,
		ANNOT['e'][$cls]
	];

	//- 原子名
	foreach ( (array)$json->atom as $a )
		$kw[] = ATOM_NAME[ $a->type_symbol ];

	//- descriptor/identifier
	$inchikey = '';
	foreach ( (array)$json->pdbx_descriptor as $d ) {
		$kw[] = $d->descriptor;
		if ( $d->type == 'InChIKey' )
			$inchikey = $d->descriptor;
	}

	foreach ( (array)$json->pdbx_identifier as $d )
		$kw[] = $d->identifier;

	//.. set
	$sqlite->set([
		$chem_id ,
		$name ,
		count( (array)CHEMID2PDBID[ $chem_id ] ),
		$json->chem_comp->formula_weight ,
		$json->chem_comp->pdbx_initial_date ,
		$inchikey ,
		IDMAP[ $chem_id ] ? json_encode( IDMAP[ $chem_id ] ) : '' ,
		json_encode( array_filter([
			'syn'     => $syn ,
			'comment' => $comment ,
			'nikkaji' => _nikkaji_name( $chem_id ) ,
			'bird'	  => $prd_id ? [ $prd_id, $prd_name, $prd_type ] : ''
		]) ) ,
		implode( '||', _uniqfilt( $kw ) ) //- search word
	]);
	if ( _count( 1000, 0 ) ) break;
}

//- DB終了
$sqlite->end();

//. function
//.. _norm_str
function _norm_str( $s ) {
	return strtolower( _reg_rep( $s, [ '/[\-_ \.]/' => '' ] ) );
}

//.. _add_syn
function _add_syn( $name ) {
	global $syn;
	$name = trim( $name );
	if ( 5 < strlen( preg_replace( '/[^a-zA-Z0-9]/', '', $name ) ) ) return;
	if ( SYN_MAXLEN < strlen( $name ) ) return;
		$syn[ _norm_str( $name ) ] = $name;
}

//.. _make_syn
function _make_syn( $name = 'none' ) {
	global $syn;
	$name = _norm_str( $name );
	if ( $syn[ $name ] )
		unset( $syn[ $name ] ); //- 正式名称は消す
	return implode( '|', _uniqfilt( $syn ) );
}

//.. _names_form_dbs
function _names_form_dbs( $chem_id ) {
	foreach( [ 'DailyMed', 'GeneExp_Atlas', 'Selleck' ] as $k ) {
		foreach ( (array)IDMAP[ $chem_id ][ $k ] as $n ) {
			_add_syn( $n );
		}
	}
}

//.. _nikkaji_name
function _nikkaji_name( $chem_id ) {
	$i = IDMAP[ $chem_id ]['Nikkaji'][0];
	if ( ! $i ) return;
	$jn = _ezsqlite([
		'dbname' => DN_PREP. '/chem/nikkaji_name.sqlite' ,
		'where'  => [ 'id', $i ] ,
		'select' => 'name' ,
	]);
	return $jn ? [ $i, $jn ] : '';
}

//.. comment
function _comment( $chem_id ) {
	return _uniqfilt( array_merge(
		(array)WIKIPE_ANNOT[ $chem_id ],
		[ ANNOT[ 'comment' ][ $chem_id ], ANNOT['e'][ ANNOT[ 'class' ][ $chem_id ] ] ]
	));
}
