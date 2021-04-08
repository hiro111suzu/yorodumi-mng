<?php
require_once( "commonlib.php" );
//. init
$sql = new cls_sqlite( 'wikipe' );

//. wikipe search


//. annotation
$out = [];
foreach ( _file( DN_DATA. '/ids/chem.txt' ) as $id ) {
	_count( 'chem');
	$en_abst = '';
	$title = _db( "c:$id" )['en_title'] ;
	$str = $title. "\n". _db( $title )['en_abst'];
	if ( ! $str ) {
		
		continue;	
	}
	foreach ([
		'medication' ,
		'chemotherapy' ,
		'anticancer' ,
//		'/drag .{,50} cancer/|anticancer',
//		'/treat .{,50} cancer/|anticancer',
		
		'therapy for cancer|anticancer' ,
		'antitumor' ,
		'bacteriocin' ,
		'antineoplastic' ,
		'antiinflammatory' ,
		'NSAID|antiinflammatory' ,
		'Anticarcinogenic' ,

		'antiparasi|antiparasitic' ,
		'antifungal' ,
		'anticoagulant' ,
		'antipsychotic' ,
		'antiarrhythmic' ,
		
		'immunosuppressant' ,
		'channel blocker' ,
		'antibiotic' ,
		'antibacterial|antibiotic' ,
		'antimicrob|Antimicrobial' ,
		'antivir|antivirus' ,
		'antiretroviral' ,
		'antidepressant' ,

		'neurotransmitter' ,
		'detergent' ,
		'redox reagent' ,
//		'lipid' ,
		'buffer|pH buffer' ,
		[
			'protease inhibitor' ,
			'protease-inhibitor|protease inhibitor' ,
			'inhibitor' ,
		] ,
		[
			'antagonist' ,
			'agonist' ,
		],
		'hormone' ,
		'alkaloid' ,
		[
			'neurotoxin' ,
			'toxin' ,
		] ,
	] as $k ) {
		foreach ( is_array( $k ) ? $k : [ $k ] as $key ) {
			list( $key, $val ) = explode( '|', $key, 2 );
			$key = trim( $key );
			$val = trim( $val ?: $key );
/*
			if ( substr( $key, 0, 1 ) == '/' ) {
				//- 正規表現
				if ( preg_match( $key, $str ) === 0 ) continue;
				_m( "$id: $key" );
				
			} else
*/
			if ( ! _instr( $key, $str ) ) {
				if ( ! _instr( strtr( $key, [ 'anti' => 'anti-' ] ), $str ) )
					continue;
			}
			$out[] = "$val\t$id";
			$test[ $id ][] = $val;
			break;
		}
	}
}
sort( $out );
$out = implode( "\n", $out );
file_put_contents( DN_PREP. '/chem/type_by_wikipe.tsv', $out );
_comp_save( DN_PREP. '/chem/annot_wikipe.json.gz', $test );

//. function
//.. _db

function _db( $key ) {
	global $sql;
	return (array)$sql->qar([
		'where'  => "key=\"$key\"" ,
		'select' => [ 'en_title', 'en_abst' ],
	])[0];
}
