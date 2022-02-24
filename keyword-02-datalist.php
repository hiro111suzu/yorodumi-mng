自動補完用キーワードファイル作成
emdbとpdb
sasは、sas-7で

<?php
require_once( "commonlib.php" );

$_kw = [];
$_kw_hot = [];
$_kw_em = [];
$_kw_em_hot = [];

$_an = [];
$_an_em = [];

define( 'NEW_LIM', time() - 3600*24*200 );

//. emdb data
//_line( 'emdb data' );
foreach ( _idloop( 'emdb_new_json', 'EMDB' ) as $fn ) {
	if ( _count( 1000, 0 ) ) break;
	$id = _fn2id( $fn );
	$json = _emdb_json3_rep( _json_load2( $fn ) );

	$kw = explode( ',', $json->admin->keywords );
	foreach ( (array)$json->experiment->vitrification as $c )
		$kw[] = $c->instrument;
	foreach ( (array)$json->experiment->imaging as $c )
		$kw[] = $c->microscope;
	foreach ( (array)$json->processing->reconstruction as $c )
		$kw[] = $c->software;

	$kw[] = _short( $json->sample->name );

	foreach ( (array)$json->sampleComponent as $c ) {
		$kw[] = _short( $c->sciName );
		$kw[] = _short( $c->synName );
	}

	_kw( $kw, $json->deposition->depositionDate, true );

	_an( _json_load2([ 'emdb_add', $id ])->author );
}


//. pdb data
_count();
foreach ( _idloop( 'pdb_json', 'PDB' ) as $fn )  {
	if ( _count( 5000, 0 ) ) break;
	$json = _json_load2( $fn );
	$kw = [];

	$m = $json->exptl[0]->method;
	$emf =  _instr( 'ELECTRON MICROSCOPY', $m ) || _instr( 'ELECTRON CRYSTALLOGRAPHY', $m );

	$kw[] = _short( $json->struct[0]->title );

	foreach ( (array)$json->struct_keywords as $c ) {
		$kw[] = $c->pdbx_keywords;
		$kw = array_merge( $kw, explode( ',', $c->text ) );
	}

	foreach ( (array)$json->entity as $c )
		$kw[] = _short( $c->pdbx_description );
	foreach ( (array)$json->entity_name_com as $c )
		$kw[] = _short( $c->name );

	_kw( $kw, $json->database_PDB_rev[0]->date_original, $emf );
	
	$an = [];
	foreach ( (array)$json->audit_author as $c )
		$an[] = $c->name;
	_an( $an, $emf );
}

//. test
_line( 'data prep' );
foreach ( $_kw as $k => $c ) {
	if ( $c < 3 )
		unset( $_kw[ $k ] );
}

foreach ( $_kw_em as $k => $c ) {
	if ( $c < 2 )
		unset( $_kw[ $k ] );
}

foreach ( $_an as $k => $c ) {
	if ( $c < 3 )
		unset( $_an[ $k ] );
}
foreach ( $_an_em as $k => $c ) {
	if ( $c < 3 )
		unset( $_an[ $k ] );
}

arsort( $_kw_hot );
$_kw_hot = array_slice( $_kw_hot, 0, 100 );

arsort( $_kw_em_hot );
$_kw_em_hot = array_slice( $_kw_em_hot, 0, 100 );


_json_save( DN_PREP . '/keyword/kwinfo.json.gz', [
	'kw'		=> $_kw ,
	'kw_em'		=> $_kw_em ,
	'kw_hot'	=> $_kw_hot ,
	'kw_em_hot'	=> $_kw_em_hot ,
	'an'		=> $_an ,
	'an_em'		=> $_an_em
]);

_testout( 'kw', $_kw );
_testout( 'kw hot', $_kw_hot );
_testout( 'kw em', $_kw_em );
_testout( 'kw em hot', $_kw_em_hot );

_testout( 'an', $_an );
_testout( 'an_em', $_an_em );


//. functions
//.. _kw キーワード
function _kw( $a, $date, $emflg = true ) {
	global $_kw, $_kw_em, $_kw_hot, $_kw_em_hot;
	if ( is_string( $a ) )
		$a = [ $a ];

	$new = NEW_LIM < strtotime( $date );
	$sw = [];

	foreach ( (array)$a as $w ) {
		$w = trim( strtoupper( $w ) );
		if ( _ig( $w ) ) continue;

		++ $_kw[ $w ];
		if ( $emflg )
			++ $_kw_em[ $w ];

		if ( $new ) {
			foreach ( explode( ' ', $w ) as $s )
				$sw[ $s ] = 1;
		}
	}

	foreach ( (array)$sw as $s => $dummy ) {
		if ( _ig( $s ) ) continue;
		++ $_kw_hot[ $s ];
		if ( $emflg )
			++ $_kw_em_hot[ $s ];
	}
	
}

//.. author name
function _an( $a, $emflg = true ) {
	global $_an, $_an_em;
	if ( is_string( $a ) )
		$a = [ $a ];

	foreach ( (array)$a as $w ) {
		$w = strtoupper( trim( $w ) );
		if ( _ig( $w ) ) continue;

		++ $_an[ $w ];
		if ( $emflg )
			++ $_an_em[ $w ];
	}
}

//.. ig 無視する文字列

function _ig( $w ) {
	if ( strlen( $w ) < 3 ) return true;
	if ( in_array( strtolower( $w ), [ 'none', 'n/a' ] ) ) return true;
	if ( is_numeric( $w ) && strlen( $w ) < 5 ) return true;
}


//. test output
function _testout( $name, $a ) {
	arsort( $a );
	_line( "$name - #of items:" . count( $a ) );
	foreach ( array_slice( $a, 0, 20 ) as $k => $v ) {
		_m( "$v\t$k" );
	}
	return;
}

function _short( $s ) {
	return strtr( substr( implode( ' ', array_slice( explode( ' ', $s ), 0, 5 ) ), 0, 30 ),
		[ '"' => '', "'" => '', '(' => '', ')' => '' ]
	);
}
