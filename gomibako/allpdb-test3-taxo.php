全PDBデータの生物種を数える
taxonomy IDもget
==========

<?php
require_once( "commonlib.php" );

//. loop
$name2num = [];
$name2tid = [];

_count();
foreach ( glob( _fn( 'pdb_json', '*' ) ) as $fn ) {
	if ( _count( 1000, 0 ) ) break;
	$json = _json_load2( $fn );

	$names = [];
	//- gen
	$x = $json->entity_src_gen;
	if ( count( $x ) > 0 ) foreach ( $x as $c ) {
		$names[] = $name = _taxoname( $c->pdbx_gene_src_scientific_name, 'n' );
		$tid = $c->pdbx_gene_src_ncbi_taxonomy_id;
		if ( $tid != '' )
			++ $name2tid[ $name ][ $tid ];
	}
	//- nat
	$x = $json->entity_src_nat;
	if ( count( $x ) > 0 ) foreach ( $x as $c ) {
		$names[] = $name = _taxoname( $c->pdbx_organism_scientific, 'n' );
		$tid = $c->pdbx_ncbi_taxonomy_id;
		if ( $tid != '' )
			++ $name2tid[ $name ][ $tid ];
	}
	//- calc
	$names = array_unique( $names );
	if ( count( $names ) > 0 ) foreach ( $names as $n ) {
		++ $name2num[ $n ];
	}
}

//. 書き込み
//- ランキングデータ
arsort( $name2num );
_tsv_save( DN_DATA . '/taxo/taxorank.tsv', $name2num );

//- 数が少ないIDはあやしいので削除
$name2tid2 = [];
foreach ( $name2tid as $name => $tids ) {
	$sum = array_sum( $tids );
	$a = [];
	foreach ( $tids as $id => $num ) {
		if ( $num * 10 > $sum ) continue;
		unset( $tids[ $id ] );
	}
	arsort( $tids );
	$name2tid2[ $name ] = array_keys( $tids );
}
_json_save( DN_DATA . '/taxo/name2tid.json', $name2tid2 );
