<?php
require_once( "unp-common.php" );

$out = [];
$types = [];
//. v1.9
foreach ( _idloop( 'emdb_old_json', 'EMDBからUniprotID収集' ) as $fn ) {
	if ( _count( 'emdb', 0 ) ) break;
	$id = _fn2id( $fn );

	$json = (array)_json_load2( $fn )->sample->sampleComponent;
	foreach ( (array)$json as $c ) {
		if ( ! is_object( $c->protein ) ) continue;
		if ( ! is_object( $c->protein->externalReferences ) ) continue;
		foreach ( $c->protein->externalReferences as $k => $v ) {
			$k = strtr( strtolower( $k ), [ 'ref' => '' ] );
			foreach ( $v as $i ) {
				if ( ! $i ) continue;
				if ( in_array( $v, (array)$out[ $id ][ $k ] ) ) continue;
				$out[ $id ][ $k ][] = $i;
				++ $types[ $k ];
			}
		}
	}
}

//. v3.0
$_filenames += [
	'emdb_xmlv20'	=> DN_EMDB_MR	. '/structures/EMD-<id>/header/emd-<id>-v30.xml' ,
];
_line( 'EMDB-XML v2.0' );
_count();
foreach ( _idlist( 'emdb' ) as $id ) {
	if ( _count( 'emdb', 0 ) ) break;
	$fn = _fn( 'emdb_xmlv20', $id );
	if ( ! file_exists( $fn ) ) continue;

	$xml = simplexml_load_file( $fn )->sample;
	if ( ! is_object( $xml->macromolecule_list ) ) continue;
	foreach ( (object)$xml->macromolecule_list->macromolecule as $c ) {
		if ( ! is_object( $c->sequence ) ) continue;
		if ( ! is_object( $c->sequence->external_references ) ) continue;
		
		foreach ( (object)$c->sequence->external_references as $c2 ) {
			$type = (string)$c2[ 'type' ];
			if ( $type = 'UNITPROTKB' )
				$type = 'uniprot';
			$v = trim( (string)$c2 );
			if ( in_array( $v, (array)$out[ $id ][ $type ] ) ) continue;
			$out[ $id ][ $type ][] = $v;
			++ $types[ $type ];
		}
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
arsort( $types );
_tsv_save( FN_EMDB_REFTYPES, $types );
