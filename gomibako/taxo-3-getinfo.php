<?php
//. misc init
require_once( "commonlib.php" );
require_once( 'taxo-common.php' );

define( 'DN_OUT', DN_DATA . '/taxo/json' );

/*
_m('id2node 読み込み');
$id2node =_json_load( DN_PREP. '/taxo/id2node.json.gz' );

if ( $id2node[ 10366 ] == [] )
	die( 'hoge' );

_m('name2id 読み込み');
$name2id =_json_load( DN_PREP. '/taxo/name2id.json.gz' );
_m('id2name 読み込み');
$id2name =_json_load( DN_PREP. '/taxo/id2name.json.gz' );
_m( '完了' );
*/


define( 'ANNOT', _tsv_load2( DN_PREP. '/taxo/taxo.tsv' ) );

$o_id2parent = new cls_sqlite( FN_ID2PARENT );
$o_id2name   = new cls_sqlite( FN_ID2NAME );

//.. emdb
_line( 'EMDB data' );
foreach ( _json_load( DN_PREP . '/ranking_taxo_em.json' ) as $name => $num ) {
	if ( _main( $name, $num ) ) break;
}

//.. pdb
_line( 'PDB data' );
foreach ( _json_load( DN_PREP . '/ranking_taxo.json' ) as $name => $num ) {
	if ( _main( $name, $num ) ) break;
}

//.. sas
_line( 'SAS data' );
foreach ( _json_load2( DN_PREP . '/sas/subdata_pre.json' )->src as  $name => $ids ) {
	_main( $name );
}

//. main func
function _main( $name, $num = 'x' ) {
	global $name2id, $id2name;
	$name = ucfirst( strtolower( $name ) );
	$name_ = strtr( $name, [ ' ' => '_', '/' => '_' ] );
	$name_low = strtolower( $name );
	$name_shu = '';
	$name_zoku = '';

	$fn_json = DN_OUT . "/$name_.json";
//	if ( file_exists( $fn_json ) ) return;
//	_m( "$name - $num entries" );

	$id = _name2id( $name );

	//- 種名、属名
	if ( ! _instr( 'virus', $name_low ) && ! _instr( 'phage', $name_low ) ) {
		list( $a, $b ) = explode( ' ', $name, 3 );
		$name_shu ="$a $b";
		$name_zoku = $a;
	}

	//.. lines
	$lines = [];
	if ( $id )
		$lines = _get_lineage( $id );
	if ( ! $lines && $name_shu ) {
		$lines = _get_lineage( _name2id( $name_shu ) );
	}
	if ( ! $lines && $name_zoku ) {
		$lines = _get_lineage( _name2id( $name_zoku ) );
	}
//	_pause( "$name: " . implode(  ' > ', $lines ) );

	//.. other names
	$oname = _id2name_all( $id );

	//.. category
	$type = 'unknown';
	if ( _instr( 'virus', $name ) ){
		$type = 'virus';
	} else foreach ( ANNOT['linematch'] as $w => $tp ) {
		if ( ! in_array( $w, (array)$lines ) ) continue;
		$type = $tp;
		break;
	}

	//.. thermophil
	$thermo = '';
	foreach ( ANNOT['thermo'] as $k => $v ) {
		if ( ! _name_match( $k, $name ) ) continue;
		$thermo = 1;
	}

	//.. short
	$annot_e = [];
	$annot_j = [];
	foreach (  ANNOT[ $categ == 'virus' ? 'short_virus' : 'short' ] as $k => $v ) {
		if ( ! _name_match( $k, $name ) ) continue;
		list( $j, $e ) = explode( '|', $v );
		$annot_e[] = $e;
		$annot_j[] = $j;
	}
	$ja_name = explode( ', ', _nn( JNAME[ $name ], JNAME[ $name_shu ] ) );
	$en = array_slice( _uniqfilt( array_merge(
		(array)$oname['gc'], (array)$oname['c'], $annot_e
	)), 0, 2 ); 
	$ja = array_slice( _uniqfilt( array_merge(
		$annot_j, $ja_name, $en 
	)), 0, 2 ); 
	_pause( "$name: " . _imp( $en ) . ' / ' . _imp( $ja ) );

	//.. icon




	//.. save data
	$data = array_filter([
		'name'	=> $name ,
		'tid'	=> $name2id[ strtolower( $name ) ] ,
		'line'	=> array_reverse( (array)$lines ) ,
		'oname'	=> $oname ,

	]);

	//- 書き込み
//	_json_save( $fn_json, $data );
}

//. func
//.. _name2id_all
function _name2id_all( $name ) {
	global $o_id2name;
	$ret = [];
	foreach ( (array)$o_id2name->qobj([
		'select' => 'type,id',
		'where' => 'name=' . _quote( $name )
	]) as $o) {
		$ret[ $o->type ][] = $o->id;
	}
	return $ret;
}

//.. _id2name_all
function _id2name_all( $id ) {
	global $o_id2name;
	$ret = [];
	foreach ( (array)$o_id2name->qobj([
		'select' => 'type,name',
		'where' => "id='$id'"
	]) as $o) {
		$ret[ $o->type ][] = $o->name;
	}
	return $ret;	
}
//.. _name2id
function _name2id( $name ) {
	return _select_first( _name2id_all( $name ) );
}
//.. _id2name
function _id2name( $name ) {
	return _select_first( _id2name_all( $name ) );
}
//.. _parent
function _parent( $id ) {
	global $o_id2parent;
	return $o_id2parent->qcol([
		'select' => 'parent',
		'where' => "id='$id'" 
	])[0];
}

//.. _select_first
function _select_first( $a ) {
	foreach ([ 'n', 'gs', 'gc', 'c', 's', 'eq' ] as $i ) {
		if ( ! $a[$i] ) continue;
		return is_array( $a[$i] ) ? $a[$i][0] : $a[$i];
	}
}

//.. _get_lineage
function _get_lineage( $id ) {
	if ( ! $id ) return;
	$lines = [];
	$p = $id;
	while ( true ) {
		$n = _parent( $p );
		if ( $n == $p || $n == '' || $n == 1 ) break;
		$lines[] = _id2name( $n );
		$p = $n;
	}
	return $lines;
}

//.. _name_match
function _name_match( $a, $b ) {
	$a = trim( $a );
	$b = trim( $b );
	if ( ! _instr( ' ', $a ) ) $a .= ' ';
	if ( ! _instr( ' ', $b ) ) $b .= ' ';	
	return stripos( $b, $a ) === 0;
}
