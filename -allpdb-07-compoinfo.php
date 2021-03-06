<?php
require_once( "commonlib.php" );
_mkdir( DN_PREP. '/pdb_compo' );
$_filenames += [
	'compo_json' => DN_PREP. '/pdb_compo/<id>.json.gz' ,
];

//. main loop
foreach ( _idloop( 'pdb_json' ) as $fn ) {
	if ( _count( 'pdb', 0 ) ) _pause();
	$id = _fn2id( $fn );
	$fn_out = _fn( 'compo_json', $id );
	if ( FLG_REDO )
		_del( $fn_out );
	if ( _newer( $fn_out, $fn ) ) continue;

	$json = _json_load2( $fn );

	//- name
	$name = $name_com = $type = $ref = [];
	foreach ( (array)$json->entity as $c ) {
		$name[ $c->id ] = $json->pdbx_description;
	}
	foreach ( (array)$json->entity_poly as $c ) {
		$type[ $c->entity_id ] = $c->type;
	}
	foreach ( (array)$json->pdbx_entity_nonpoly as $c ) {
		$
	}


	$out = [
		'title'		=> $json->struct[0]->title ,
		'method'	=> _method() ,
		'reso'		=> _reso() ,
		'related'	=> _related() ,
		'grp_dep'	=> $json->pdbx_deposit_group
			? $json->pdbx_deposit_group[0]->group_id : ''
		,
		'pmid'		=> _pubmedid() ,
//		'ddate'		=> $json->database_PDB_rev[0]->date_original ,
		'ddate'		=> $json->pdbx_database_status[0]->recvd_initial_deposition_date ,
		'rdate'		=> $json->pdbx_audit_revision_history[0]->revision_date ,
		'repid'		=> _replaced() ,
		'src'		=> _src() ,
		'chemid'	=> _chemid() ,
		'sym'		=> _sym() ,
		'num_chain'	=> _numchain() ,
		'num_atom'	=> _numatom() ,
		'identasb'	=> _identasb() ,
		'ribosome'	=> _ribosome() ,
		'asbid'		=> _asbid() ,
		'ref'	=> _ref(),
	];
	if ( $out[ 'ddate' ] == '' )
		_problem( "$id: no ddate"  );
	_json_save( $fn_out, array_filter( $out ) );
//	_m( json_encode( array_filter( $out ), JSON_PRETTY_PRINT ) );
//	if ( $out[ 'ribosome' ] )
//		_m( "$id: ribosome!" );
}

_delobs_pdb( 'qinfo' );

//. データ抽出用func
//.. method
function _method() {
	global $json;
	$ret = [];
	foreach ( $json->exptl as $j )
		$ret[] = $j->method;
	return $ret;
}

//.. reso
function _reso() {
	global $json;
	return floatval(
		$json->em_3d_reconstruction[0]->resolution ?:
		$json->refine[0]->ls_d_res_high ?:
		$json->reflns[0]->d_resolution_high
	);
}


//.. related
function _related() {
	global $json, $id;
	$ret = [];
	foreach ( (array)$json->pdbx_database_related as $v ) {
		$d = $v->db_name;
		$D = strtoupper( $d );
		$i = $v->db_id;
		if ( $d == 'EMDB' ) {
			$ret[ 'EMDB' ][] = strtr( $i, [ 'EMD-' => '' ] );
		} else if ( $D == 'PDB' ) {
			if ( $i != $id )
				$ret[ 'PDB' ][] = strtolower( $i );
		} else {
			$ret[ $d ][] = $i;
		}
//		_m( "$d-$i" );
	}
	return $ret;
}
//.. pubmedid
function _pubmedid() {
	global $json;
	$ret = '';
	foreach ( (array)$json->citation as $v ) {
		if ( $v->id != 'primary' ) continue;
		$ret = $v->pdbx_database_id_PubMed;
		break;
	}
	if ( 3 < strlen( $ret ) )
		return $ret;
}

//.. replace
function _replaced() {
	global $json;
	$ret = [];
	foreach ( (array)$json->pdbx_database_PDB_obs_spr as $c )
		foreach( (array)explode( ' ', strtolower( $c->replace_pdb_id ) ) as $oldid )
			$ret[] = $oldid;
	return $ret;
}

//.. src
function _src() {
	global $json;
	$ret = [];
	foreach ( array_merge(
		(array)$json->entity_src_gen ,
		(array)$json->entity_src_nat ,
		(array)$json->pdbx_entity_src_syn
	) as $j ) {
		$i = strtolower( implode( ',',  [
			$j->pdbx_gene_src_scientific_name ,
			$j->pdbx_organism_scientific ,
			$j->organism_scientific
		] ) );
		if ( $i == '' ) continue;
		
		foreach ( explode( ',', preg_replace( '/, ([0-9])/', '__comma__$1', $i ) ) as $j )
			$ret[ strtr( trim( $j ), [ '__comma__' => ', ' ]) ] = 1;
	}
	return array_filter( array_keys( $ret ) );
}

//.. chemid
function _chemid() {
	global $json;
	$ret = [];
	foreach ( (array)$json->pdbx_entity_nonpoly as $c ) {
//		if ( $c->comp_id == 'HOH' ) continue;
		$ret[] = $c->comp_id;
	}
	return $ret;
}

//.. sym
//- icos / helical
function _sym() {
	global $json;
	$ret = '';
	foreach ( (array)$json->pdbx_struct_assembly as $j ) {
		if ( _instr( 'icosahedral', $j->details ) )
			$ret = 'icos';
		if ( _instr( 'helical', $j->details ) )
			$ret = 'helical';
	}
	return $ret;
}

//.. _asbid
function _asbid() {
	global $json;
	$ret = [];
	foreach ( (array)$json->pdbx_struct_assembly as $j ) {
		$ret[] = $j->id;
	}
	return $ret;
}

//.. num_chain
function _numchain() {
	global $json;
	$ret = 0;
	foreach ( (array)$json->entity_poly as $j )
		$ret += count( explode( ',', $j->pdbx_strand_id ) );
	return $ret;
}

//.. num_atom
function _numatom() {
	global $id;
	$ret = 0;
	$fn = _fn( 'pdb_mmcif', $id );
//	if ( ! file_exists( $fn ) )
//		$fn = _fn( 'pdb_mmcif_large', $id );

	foreach ( gzfile( $fn ) as $l ) {
		$a = explode( ' ', $l, 2 );
		if ( $a[0] != 'ATOM' and $a[0] != 'HETATM' ) continue;
		++ $ret;
	}
	return $ret;
}

//.. ribosome
//- entity_poly 20個以上
//- entity->pdbx_description 'riboso'が10個以上
//- RNAがある
function _ribosome() {
	global $json;
	if ( count( (array)$json->entity_poly ) < 20 ) return;

	//- ribosome--- のエンティティの数
	$num = 0;
	foreach ( (array)$json->entity as $j ) {
		if ( _instr( 'riboso', $j->pdbx_description ) )
			++ $num;
	}
	if ( $num < 10 ) return;

	//- RNAがあるかどうか
	foreach ( (array)$json->entity_poly as $j ) {
		if ( $j->type == 'polyribonucleotide' )
			return 1;
	}
}

//.. _identasb
function _identasb() {
	global $json;
	$ret = [];

//... ji (json_reid)
	$ji = [];
	foreach ([
		'struct_asym'				=> [ 'asym'	] ,
		'pdbx_struct_assembly_gen'	=> [ 'asbgen',	'assembly_id' ],
		'pdbx_struct_oper_list'		=> [ 'oprlist' ] ,
	] as $tag => $a ) {
		foreach ( (array)$json->$tag as $c ) {
			$idn = $a[1] ?: 'id' ;
			$i = $c->$idn;
			if ( $i == '' ) continue;
			$ji[ $a[0] ][ $i ] = array_merge( (array)$ji[ $a[0] ][ $i ], (array)$c ) ;
		}
	}

//... assembly
	//- 全asym-id
	$asymidlist = _sort( array_keys( $ji[ 'asym' ] ) );
	foreach ( (array)$ji[ 'asbgen' ] as $abid => $g ) {
		if ( $g == '' ) continue;
		//- 登録構造と同じ？
		if (
			_sort( explode( ',', (string)$g[ 'asym_id_list' ] ) ) != $asymidlist 
		) continue;

		if (
			$ji[ 'oprlist' ][ $g[ 'oper_expression' ] ][ 'type' ] != 'identity operation'
		) continue;
		$ret[] = $abid;
	}
	return $ret;
}

//.. _seqref
/*
UniProt	pdbx_db_accessionにID、db_codeに別のID
GenBank	pdbx_db_accessionでOk
EMBL	pdbx_db_accessionでgenbankと同一視でOKぽい
NOR		pdbx_db_accessionとdb_code 同じ、ページは見つけられない
PIR		pdbx_db_accessionとdb_code 同じ、ページは見つけられない
*/
function _ref() {
	global $json;
	$ret = [];
	foreach ( (array)$json->struct_ref as $c ) {
		$d = strtoupper( trim( $c->db_name ) ); 
		if ( $d == 'PDB' ) continue;
		$ret[] = [ $d, trim( $c->pdbx_db_accession ) ];
	}

	//- bird
	foreach ( (array)$json->pdbx_molecule_features as $c ) {
		$ret[] = [ 'BIRD', $c->prd_id ];
	}

	return array_values( array_unique( $ret, SORT_REGULAR  ) );
}

//. func: _sort
function _sort( $a ) {
	if ( ! is_array( $a ) ) return;
	sort( $a );
	return implode( ',', $a );
}
