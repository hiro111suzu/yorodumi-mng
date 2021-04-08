EM データの生物種をリストアップ
taxonomy IDもget
うまく動かない

==========

<?php
require_once( "commonlib.php" );
$names = [];
$tdn = DN_DATA . '/taxo';

$rep = [
	'E. coli'			=> 'Escherichia coli' ,
	'E.coli'			=> 'Escherichia coli' ,
	'Ecoli'				=> 'Escherichia coli' ,
	'Eschericia coli'	=> 'Escherichia coli' ,
	'S. cerevisiae'		=> 'Saccharomyces cerevisiae' ,
	'S. pombe'			=> 'Schizosaccharomyces pombe' ,
	'Homo spiens'		=> 'Homo sapiens' ,
	'P22'				=> 'Phage p22' ,
	'Phi29'				=> 'Phage Phi29' ,
	'Bacillus subtilis bacteriophage spp1' => 'Phage spp1' ,
	'N/a'				=> '_'
];

$rep_in  = [
	'/bacteriophage/i' ,
	'/virus type/i' ,
	'/ mature particle|Emptied | core$| virion$/i' ,
	'/[ \n\t]+/'
];

$rep_out = [
	'phage' ,
	'virus ' ,
	'' ,
	' '
];

//. em pdb
_m( 'PDB', 1 );
foreach ( $pdbidlist as $id ) {
	$did = "pdb-$id";
	$xml = _json_load2( _fn( 'epdb_json' ) );

	//- gen
	$x = $xml->entity_src_gen;
	if ( count( $x ) > 0 ) foreach ( $x as $c )
		_n( $c->pdbx_gene_src_scientific_name );

	//- nat
	$x = $ans->entity_src_nat;
	if ( count( $x ) > 0 ) foreach ( $x as $c )
		_n( $c->pdbx_organism_scientific );

}

//. emdb
_m( 'EMDB', 1 );
$nospecid = [];
foreach ( $emdbidlist as $id ) {
	$did = "emdb-$id";
	$json = _json_load2( _fn( 'emdb_json', $id ) );
	
	$x = $json->sample->sampleComponent;
//	_m( count( $x ) );
	$cnt = 0;
	if ( count( $x ) > 0 ) foreach ( $x as $c1 ) {
		$cnt += 0
			+ _n( $c1->sciSpeciesName )
			+ _n( $c1->hostSpecies )
		;
		foreach ( $c1 as $c2 ) {
			if ( is_object( $c2 ) )
				$cnt += 0
					+ _n( $c1->sciSpeciesName )
					+ _n( $c1->hostSpecies )
				;
		}
			+ _n( $c1->virus->sciSpeciesName )
			+ _n( $c1->virus->hostSpecies )
		;
		
	}
	if ( $cnt == 0 )
		$nospecid[] = $id;
}

//. 書き込み
arsort( $data );
_tsv_save( "$tdn/em_spec.tsv", $data );

_m(
	'No spec. data ' . count( $nospecid) . ' / ' . count( $emdbidlist ) . "\n"
	. implode( ', ', $nospecid )
);

//. function

function _n( $str ) {
	global $data, $rep, $rep_in, $rep_out;
	$flg = 0;
	foreach ( explode( ',', $str ) as $n ) {
		$n = trim( preg_replace( $rep_in, $rep_out, (string)$n ) );
		$n = _taxo_name( $n, 'n' );
		$n = _nn( $rep[ $n ], $n );
		if ( $n == '' or $n == '_' ) continue;
		++$data[ $n ];
		$flg = 1;
	}
	return $flg;
}
