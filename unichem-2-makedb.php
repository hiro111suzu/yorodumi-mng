<?php
include "commonlib.php";

//. init
define( 'DN', DN_FDATA. '/unichem' );
define( 'NUM2DB', [
1	=> 'ChEMBL' ,
2	=> 'DrugBank' ,
4	=> 'GtoPharmacology' ,
5	=> 'PubChem_DOTF' ,
6	=> 'KEGG_Ligand' ,
7	=> 'ChEBI' ,
8	=> 'NIH_NCC' ,
9	=> 'ZINC' ,
//10	=> 'eMolecules' ,
//11	=> 'IBM' ,
12	=> 'GeneExp_Atlas' ,
14	=> 'FDA_SRS' ,
15	=> 'SureChEMBL' ,
17	=> 'PharmGKB' ,
18	=> 'HMDB' ,
20	=> 'Selleck' ,
21	=> 'PubChem_TPharma' ,
22	=> 'PubChem' ,
23	=> 'Mcule' ,
24	=> 'NMRShiftDB' ,
25	=> 'LINCS' ,
26	=> 'ACToR' ,
27	=> 'Recon' ,
//	28	=> 'MolPort' ,
29	=> 'Nikkaji' ,
31	=> 'BindingDB' ,
32	=> 'CompTox' ,
33	=> 'LipidMaps' ,
34	=> 'DrugCentral' ,
35	=> 'CarotenoidDB' ,
36	=> 'Metabolights' ,
37	=> 'Brenda' ,
38	=> 'Rhea' ,
39	=> 'ChemicalBook' ,
40	=> 'DailyMed' ,
41	=> 'SwissLipids'
]);

//. src*src3
$data = [];
$db2chemid = [];
foreach ( glob( DN. '/src*src3.txt.gz' ) as $pn ) {
	$dbnum = strtr( basename( $pn, '3.txt.gz' ), [ 'src' => '' ] );
	$db = NUM2DB[ $dbnum ];
	if ( ! $db ) continue;
	foreach ( gzfile( $pn ) as $line ) {
		list( $dbid, $chemid ) = explode( "\t", trim( $line ) );
		if ( _instr( ':', $chemid ) ) continue;
		$data[ $chemid ][ $db ][] = $dbid;
		$db2chemid[ $db ][] = $chemid;
	}
}


//. src3src*
foreach ( glob( DN. '/src3src*.txt.gz' ) as $pn ) {
	$dbnum = strtr( basename( $pn, '.txt.gz' ), [ 'src3src' => '' ] );
	$db = NUM2DB[ $dbnum ];
	if ( ! $db ) continue;
	foreach ( gzfile( $pn ) as $line ) {
		list( $chemid, $dbid ) = explode( "\t", trim( $line ) );
		if ( _instr( ':', $chemid ) ) continue;
		$data[ $chemid ][ $db ][] = $dbid;
		$db2chemid[ $db ][] = $chemid;
	}
}

_comp_save( DN_PREP. '/chem/chemid_map.json.gz', $data );

//. count
$num = [];
$many = [];
foreach ( $data as $chemid => $c1 ) {
	foreach ( $c1 as $db => $c2 ) {
		$count = count( $c2 );
		++ $num[ $count ];
		if ( 5 < $count ) {
			if ( $db == 'Brenda' ) continue;
			_m( "$chemid => $db: $count" );
		}
	}
}

ksort( $num );
_kvtable( $num );

//. db2chemid
$out = '';
foreach ( $db2chemid as $db => $ids ) {
	$out .= "$db\t". _imp( array_slice( $ids, -20 ) ). "\n";
}
_comp_save( DN_PREP. '/chem/db2chemid.tsv', $out );

