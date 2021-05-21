<?php 
include "commonlib.php";

$name2id = [];
$id2name = [];

$cnt = [];
$g = glob( _fn( 'pdb_json', '*' ) );
shuffle( $g );

foreach ( $g as $fn ) {
	if ( _count( 100, 1000 ) ) break;
	$json = _json_load2( $fn );

	$data = [];
	foreach ( array_merge(
		(array)$json->entity_src_gen ,
		(array)$json->entity_src_nat ,
		(array)$json->pdbx_entity_src_syn
	) as $j ) {
		$i = _nn(
			$j->pdbx_gene_src_ncbi_taxonomy_id ,
			$j->pdbx_ncbi_taxonomy_id ,
			'_'
		);
		$n = strtolower( _nn(
			$j->pdbx_gene_src_scientific_name ,
			$j->pdbx_organism_scientific ,
			$j->organism_scientific ,
			'_'
		));

		if ( $n . $i == '__' )
			continue;

		if ( _instr( ',', $i ) ) {
			foreach ( array_combine( explode( ',', $i ), explode( ',', $n ) ) as $i2 => $n2 ) {
	 			$data[] = trim( $i2 ) . '|' . trim( $n2 );
			}
		} else {
			$data[] = strtolower( "$i|$n" );
		}
	}
	foreach ( array_unique( $data ) as $s ) {
		$a = explode( '|', $s );
		$i = $a[0];
		$n = $a[1];
		++ $name2id[ $n ][ strtolower( $i ) ];
		++ $id2name[ $i ][ strtolower( $n ) ];
	}
}

ksort( $name2id );
$out = '';
foreach ( $name2id as $name => $c ) {
	$out .= "$name\t" . _f( $c );
}
file_put_contents( DN_PREP . '/taxo_name2id.tsv', $out );

ksort( $id2name );
$out = '';
foreach ( $id2name as $id => $c ) {
	$out .= "$id\t" . _f( $c );
}
file_put_contents( DN_PREP . '/taxo_id2name.tsv', $out );

function _f( $a ) {
	$ret = [];
	foreach ( $a as $k => $v )
		$ret[] = "$k ($v)";
	return implode( "\t", $ret ) . "\n";
}
