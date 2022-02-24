<?php
require_once( "unp-common.php" );

$out = [];
$types = [];
//. emdb-xmlから
foreach ( _idloop( 'emdb_new_json', 'EMDBからUniprotID収集' ) as $fn ) {
	if ( _count( 'emdb', 0 ) ) break;
	$id = _fn2id( $fn );
	$json = _emdb_json3_rep( _json_load2([ 'emdb_json', $id ]) );
	foreach ([
		'uniprot'	=> 'sample->macromolecule[*]->sequence->ref_UNIPROTKB' ,
		'interpro'	=> 'sample->macromolecule[*]->sequence->ref_INTERPRO[*]' ,
		'go'		=> 'sample->macromolecule[*]->sequence->ref_GO[*]' ,
		'ec'		=> 'sample->macromolecule[*]->ec_number' ,
	] as $key => $branch ) {
		$b = array_values( _uniqfilt( _branch( $json, $branch ) ) );
		if ( ! $b ) continue;
//		_m( "$id-$key: ". _json_pretty( $b ) );
//		_pause();
		$out[ $id ][ $key ] = $b;
	}
}

//. tsv
$annot = _tsv_load( FN_EMDB_UNPIDS_ANNOT );
foreach ( $annot as $emdb_id =>$str ) {
	foreach ( explode( ' ', $str ) as $unp_id ) {
		if ( strlen( $unp_id ) < 5 ) continue;
		if ( in_array( $unp_id, (array)$out[$emdb_id]['uniprot'] )) continue;
		$out[$emdb_id]['uniprot'][] = $unp_id;
	}
}
_comp_save( FN_EMDB_DATA, $out );
