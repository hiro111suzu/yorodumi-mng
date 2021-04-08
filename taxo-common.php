<?php

//. file / directory
define( 'FN_ID2PARENT', DN_PREP. '/taxo/id2parent.sqlite' );
define( 'FN_ID2NAME'  , DN_PREP. '/taxo/id2name.sqlite' );

define( 'NAME_TYPE', [
	'n'	=>	'scientific name' ,
	'c'	=>	'common name' ,
	's'	=>	'synonym' ,
	'gs'=>	'genbank synonym' ,
	'gc'=>	'genbank common name' ,
	'eq'=>	'equivalent name' ,
	'm'	=>	'misspelling' ,
	'i'	=>	'includes' ,
]);

_mkdir( DN_PREP. '/taxo' );
define( 'FN_PDB_NAME2TAXID', DN_PREP. '/taxo/pdb_name2id.json.gz' );
define( 'FN_LIST_PDB', 		 DN_PREP. '/taxo/list_pdb.json.gz' );
define( 'FN_LIST_ALL', 		 DN_PREP. '/taxo/list_all.json.gz' );
define( 'FN_ID2NAME_JSON', 	 DN_PREP. '/taxo/id2name.json.gz' );
define( 'FN_PDB_ID2TAXNAME', DN_PREP. '/taxo/pdb_id2taxname.json.gz' );
define( 'FN_OTHERS_ID2TAXNAME', DN_PREP. '/taxo/others_id2taxname.json.gz' );
define( 'FN_VIRUS2HOSTS',	 DN_PREP. '/taxo/virus2host.json.gz' );

define( 'FN_TSV_REP',		DN_EDIT. '/taxo_name_rep.tsv' );
define( 'FN_TAXO_ANNOT',	DN_EDIT. '/taxo_annot.tsv' );

$_filenames += [
	'unp_json' => DN_DATA. '/unp/<id>.json.gz'
];


//. function
//.. _name_prep
function _name_prep( $n ) {
	global $names, $flg_name;
	$n = trim( $n );
	if ( $n == strtoupper( $n ) || $n == strtolower( $n ) ) {
		$n = ucfirst( strtolower( $n ) );
	}
	if ( $flg_name )
		$names[] = $n;
	return $n ;
}

//.. do
function _do( $a1, $a2 ) {
	global $data, $id;
	if ( !$a1 && !$a2 ) return;
	if ( in_array( $a1, [ 'Suppressed', 'na', 'n/a' ] ) ) return;

	$ret = [];
	$names = array_filter( explode( ',', $a1 ) );
	$ids   = array_filter( explode( ',', $a2 ) );
	$c_names = count( $names );
	$c_ids   = count( $ids );

	//... IDと同じ数
	if ( $c_names == $c_ids ) {
		foreach ( $names as $i => $n ) {
			$data[] = _name_prep( $n ) . '|' . trim( $ids[$i] );
		}
		return;
	}

	//... 名前一個だけ
	if ( $_cnames == 1 ) {
		$data[] = _name_prep( $a1 );
		return;
	}

	//... 名前複数、IDは無視
	//- 中に空白のない文字列があったら、分割をしない
	foreach ( $names as $n ) {
		if ( _instr( ' ', trim( $n ) ) ) continue;
		$data[] = _name_prep( $a1 );
		return;
	}
	foreach ( $names as $i => $n ) {
		$data[] = _name_prep( $n );
	}
	return;
}

//.. __imp
function __imp( $a, $sep = '|' ) {
	return $a ? $sep . implode( $sep, (array)$a ) . $sep : '';
}

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
	foreach ([ 'n', 'gs', 'gc', 'c', 's', 'eq', 'x' ] as $i ) {
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
