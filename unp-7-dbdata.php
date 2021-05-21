-----
json file
pdb_id    => dbid-list
emdb_id   => dbid-list
sasbdb_id => dbid-list

-----
<?php
require_once( "unp-common.php" );
define( 'FITDB', _json_load( DN_PREP. '/emn/fitdb.json.gz' ) );
$data = [];
define( 'NUM_STOP', 0 );
define( 'PDBID2UNPID', _json_load( FN_PDBID2UNPID ) );

//. main
//.. emdb
$data = [];
_count();
_line( 'emdb' );
$annot = _tsv_load( FN_EMDB_UNPIDS_ANNOT );
$emdb_data = _json_load( FN_EMDB_DATA );
foreach ( _idlist( 'emdb' ) as $emdb_id ) {
	if ( _count( 'emdb', NUM_STOP ) ) break;
	$unp_ids = (array)$emdb_data[ $emdb_id ][ 'uniprot' ];
	//- fittedを追加
	foreach ( (array)FITDB[ "emdb-$emdb_id" ] as $pdbid ) {
		$pdbid = strtr( $pdbid, [ 'pdb-' => ''] );
		$unp_ids = array_merge( $unp_ids, (array)PDBID2UNPID[ $pdbid ] );
	}
//	_pause( "$emdb_id: " . _imp( $unp_ids ) );

	//- マニュアルアノテーション、追加
	$an_id = $annot[ $emdb_id ];
	if ( ! $unp_ids && ! $an_id ) {
		$pmid = _ezsqlite([
			'dbname' => 'main' ,
			'select' => 'pmid' ,
			'where'  => [ 'db_id', "emdb-$emdb_id" ] ,
		]);
		if ( $pmid )
			$an_id = $annot[ $pmid ];
	}

	if ( $an_id && $an_id != '_' ) {
		foreach ( explode( ' ', $an_id ) as $s ) { //- #エイリアス対応
			if ( substr( $s, 0, 1 ) == '#' ) {
				$unp_ids = array_merge( $unp_ids, explode( ' ', $annot[ $s ] ) );
			} else if ( substr( $s, 0, 4 ) == 'pdb-' ) {
				$pdbid = strtr( $s, [ 'pdb-' => ''] );
				$unp_ids = array_merge( $unp_ids, (array)PDBID2UNPID[ $pdbid ] );
			} else if ( strlen( $s ) == 4 ) {
				$unp_ids = array_merge( $unp_ids, (array)PDBID2UNPID[ $s ] );
			} else if ( 3 < strlen( $s ) ) {
				$unp_ids[] = $s;
			}
		}
//		_pause( "$emdb_id: " . _imp( $unp_ids ) );
/*
	} else {
		if ( !$unp_ids && !$an_id && file_exists( _fn('mapgz', $emdb_id) ) ) {
			$annot[ $emdb_id ] = '_';
//			_pause([ $emdb_id, 'new no-unpid'] );
		}
*/
	}

	//- UniProtIDから取得
	$a = _get_unp_data( $unp_ids );

	//- EMDB-XMLもともとの情報を追加
	foreach ( (array)$emdb_data[ $emdb_id ][ 'interpro' ] as $i )
		$a[] = "in:$i";
	foreach ( (array)$emdb_data[ $emdb_id ][ 'go' ] as $i )
		$a[] = 'go:' . strtr( $i, [ 'GO:' => '' ] );
	_unq_ids( $a );
	$data[ $emdb_id ] = $a;
}
ksort( $annot );
_tsv_save( FN_EMDB_UNPIDS_ANNOT, $annot );

_m( count( $data ) );
_comp_save( FN_DBDATA_EMDB, array_filter( $data ) );

//.. pdb
_count();
//... uniprot
_line( 'pdb-unp' );
$data = [];
foreach ( PDBID2UNPID as $pdbid => $unp_ids ) {
	if ( _count( 'pdb', NUM_STOP ) ) break;
	$data[ $pdbid ] = _get_unp_data( $unp_ids );
}
_count();

//... plus
//- plusからPfam情報
foreach ( _idloop( 'pdb_plus' ) as $fn ) {
	if ( _count( 'pdb', NUM_STOP ) ) break;
	$pdb_id = _fn2id( $fn );
	foreach ( (array)_json_load2( $fn )->struct_ref as $c ) {
		if ( $c->db_name != 'Pfam' ) continue;
		$data[ $pdb_id ][] = _set( 'pf', $c->pdbx_db_accession );
	}
}
_count();

//... cath
//- cath
_line( 'CATH' );
foreach ( _json_load2( FN_PDB2CATH ) as $pdb_id => $cath_id_set ) {
	if ( _count( 'pdb', NUM_STOP ) ) break;
	$out = $cath_id_set;
	foreach ( $cath_id_set as $cath_id ) {
		list( $c1, $c2, $c3, $c4 ) = explode( '.', $cath_id );
		$out[] = $c1;
		$out[] = "$c1.$c2";
		$out[] = "$c1.$c2.$c3";
	}
	foreach ( array_unique( $out ) as $i )
		$data[ $pdb_id ][] = "ct:$i";
}
_count();

//... qinfo
//- 化合物無視リスト
$tsv = _tsv_load2( DN_EDIT. '/chem_annot.tsv' );
$chem_ignroe = [];
foreach ( $tsv['dbid_ignore'] as $key => $dummy ) {
	$chem_ignroe[ $key ] = true;
}
foreach ( $tsv['class'] as $key => $val ) {
	if ( in_array( $val, [ 'buf', 'det', 'pre' ] ) ) {
		$chem_ignroe[ $key ] = true;
	}
}
_m( count( $chem_ignroe ). '個のchem無視リスト読み込み' );

//- ほかの配列データベースも入れる
foreach ( _idloop( 'qinfo' ) as $fn ) {
	if ( _count( 'pdb', NUM_STOP ) ) break;
	$pdb_id = _fn2id( $fn );
	$json = _json_load2( $fn );
	//- ref
	foreach ( (array)$json->ref as $a ) {
		if ( $a[0] == 'polysac' ) continue; //- polysac やめてみる
		$data[ $pdb_id ][] = _set( $a[0], $a[1] );
	}
	foreach ( (array)$json->chemid as $i ) {
		if ( $chem_ignroe[ $i ] ) continue;
		$data[ $pdb_id ][] = _set( 'chem', $i );
	}
	foreach ( (array)$json->poly_type as $i ) {
		$data[ $pdb_id ][] = _set( 'poly', $i );
	}
	_unq_ids( $data[ $pdbid ] );
}
_count();

//... end
_m( count( $data ) );
_comp_save( FN_DBDATA_PDB, array_filter( $data ) );

//.. sas
_count();
_line( 'sas' );
$data = [];
foreach ( _json_load( FN_SASID2UNPID ) as $sas_id => $unp_ids ) {
	if ( _count( 100, NUM_STOP ) ) break;
	$data[ $sas_id ] = _get_unp_data( $unp_ids );	
}
_m( count( $data ) );
_comp_save( FN_DBDATA_SASBDB, array_filter( $data ) );

//sort( $ipr_ids );
//file_put_contents( 'temp.txt', implode( "\n", array_unique( $ipr_ids ) ) );

//. function
//.. _get_unp_data
function _get_unp_data( $unp_ids ) {
	$ids = [];
	foreach ( array_unique( $unp_ids ) as $unp_id ) {
		$ids = array_merge( $ids, (array)_get_unp_data_main( $unp_id ) );
	}
	_unq_ids( $ids );
	return $ids;
}

//.. _get_unp_data_main
function _get_unp_data_main( $unp_id ) {
	if ( !$unp_id ) return;
	$ids = [ _set( 'un', $unp_id ) ];
	$json = _json_load2( _fn( 'unp_json', $unp_id ) );

	//... go
	foreach ( (object)$json->go as $c1 ) {
		foreach ( (array)$c1 as $c2 ) {
			if ( ! $c2 ) continue;
 			$ids[] = _set( 'go', $c2[0] );
		}
	}

	//... interpor
	foreach ( (object)$json->intp as $c1 ) {
		foreach ( (array)$c1 as $c2 ) {
			if ( ! $c2 ) continue;
			$ids[] = _set( 'in', $c2[0] );
		}
	}
	
	
	//... ec
	foreach ( (array)$json->ec as $c ){
		if ( ! $c ) continue;
		//- 何故かオブジェクトになるデータがあった
		if ( is_object( $c ) )
			_m( "object: $unp_id:". print_r( $id ) );
		else 
			$ids[] = _set( 'ec', $c );
	}

	//... pfam / prosite
	if ( (object)$json->dbref ) {
		foreach ( (array)$json->ref->Pfam as $c2 ) {
			$ids[] = _set( 'pf', $c2[0] );
		}
		foreach ( (array)$json->ref->PROSITE as $c2 ) {
			$ids[] = _set( 'pr', $c2[0] );
		}
		foreach ( (array)$json->ref->SMART as $c2 ) {
			$ids[] = _set( 'sm', $c2[0] );
		}
		foreach ( (array)$json->ref->Reactome as $c2 ) {
			$ids[] = _set( 'rt', $c2[0] );
		}
	}
	return $ids;
}
//.. _unq_ids
function _unq_ids( &$ids ) {
	$ids = array_values( array_unique( $ids ) );
	sort( $ids );
}

//.. _set
function _set( $db, $id ) {
//	global $ipr_ids;
	$db = strtolower( $db );
//	if ( $db == 'in' )
//		$ipr_ids[] = strtolower( trim( $id ) );
//		$id = 'IPR'. _numonly( $id );
//	if ( $db == 'in' || $db == 'pf' || $db == 'pr' )
//		$id = _numonly( $id );
//	if ( $db == 'in' || $db == 'pf' || $db == 'pr' )
//		$id = _numonly( $id );
	return ( [ 'unp' => 'un', 'bird' => 'bd' ][$db] ?: $db )
		. ':'
		. strtolower( trim( $id ) )
	;
}

/*
un: UniProt
go: GO
ec: ec
in: interpro
rt: Reactome
pf: Pfam
pr: prosite

*/
