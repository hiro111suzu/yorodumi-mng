<?php 
include "commonlib.php";
include "../emnavi/taxo-common.php";

$s = implode( "\n", $taxo_type );

$ok = [];
foreach ( explode( "\n", $s ) as $w ) {
	if ( trim( $w ) == '' ) continue;
	$w = strtolower( trim( $w ) );
//	_m( "word: $w" );
	$ok[ $w ] = true;
}

$cnt = [];
$g = glob( _fn( 'pdb_json', '*' ) );
shuffle( $g );

foreach ( $g as $fn ) {
//	if ( _count( 100, 1000 ) ) break;
	$json = _json_load2( $fn );

	$tid = [];
	foreach ( array_merge(
		(array)$json->entity_src_gen ,
		(array)$json->entity_src_nat ,
		(array)$json->pdbx_entity_src_syn
	) as $j ) {
//		$i = $j->pdbx_gene_src_ncbi_taxonomy_id . $j->pdbx_ncbi_taxonomy_id;
		$i =  $j->pdbx_gene_src_scientific_name
			. $j->pdbx_organism_scientific
			. $j->organism_scientific
		;
		if ( $i == '' ) continue;
		foreach ( explode( ',', $i ) as $j )
			$tid[ trim( $j ) ] = true;
	}
	foreach ( array_keys( $tid ) as $i ) {
		$i = strtolower( $i );
		$a = explode( ' ', $i );
		
		if ( _instr( 'virus', $i ) or _instr( 'phage ', $i ) ) continue;
		if ( $ok[ $a[0] ] ) {
//			_m( $a[0] );
			continue;
		}
		++ $cnt[ $i ];
//
//		foreach ( array_keys( $tid[ $n ] ) as $i ) {
//			++$name2tid[ $n ][ $i ];
//		}
	}
}
//print_r( $name2tid );
//die();

arsort( $cnt );
$out = '';
foreach ( $cnt as $id => $num ) {
	 if ( $num == 1 ) 
	 	break;
	 _m( "$num\t: $id" );
	 $out .= "$num\t: $id\n";
}
file_put_contents( DN_PREP . '/taxo/count.txt', $out );

//- dummy
function _ej( $a, $b ) {
	return $a;
}
