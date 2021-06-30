<?php
require_once( "commonlib.php" );
define( 'PATH_XML'		, DN_FDATA. '/empiar/empiar.pdbj.org/pub/empiar/archive/*/*.xml' );
//- EBI: '/ftp.ebi.ac.uk/pub/databases/empiar/archive/*/*.xml'
//- EBI: ftp://ftp.ebi.ac.uk/pub/databases/empiar/archive/

define( 'FN_ID_TABLE', DN_PREP. '/empiar_id_table.json.gz' );

//. 
$data = [];
$empiar_id_2_str_id = [];
foreach ( glob( PATH_XML ) as $pn ) {
	_count( 100 );
	$empiar_id = basename( $pn, '.xml' );
	$xml = simplexml_load_file( $pn );

	$d = [];
	$data[ $empiar_id ] = [
		'title'		=> (string)$xml->admin->title ,
		'data size' => $xml->admin->datasetSize .' '. $xml->admin->datasetSize[ 'units' ] ,
	];
	$num = 0;
	foreach ( $xml as $k => $x ) {
		if ( $k != 'imageSet' ) continue;
		++ $num;
//		print_r( $x );
//		_pause();
		$data[ $empiar_id ][ 'Data #'. $num ] =
//			'name' => (string)$x->name ,
//			'category' => (string)$x->category
			(string)$x->name . ' [' . (string)$x->category . ']'
		;
	}
	_cnt('EMPIAR');

	$x = $xml->crossReferences;
	if ( count( $x ) > 0 ) foreach ( $x->children() as $c ) {
		foreach ( $c->children() as $key => $val ) {
			$db = $id = '';
			if ( $key == 'emdbEntry' ) {
				_cnt('EMDB');
				$db = 'emdb';
				$id = _numonly( $val );
			} else if ( $key == 'pdbEntry' ) {
				_cnt('PDB');
				$db = 'emdb';
				$id = $val;
			} else {
				_cnt( $key );
//				_m( 'unknown key: '. $key );
				continue;
			}
			if ( $val != '' ) {
				$data[ "$db-$id" ][] = $empiar_id;
//				_m( "$val -- $empiar_id" );
			if ( $db )
				$empiar_id_2_str_id[ $empiar_id ][ $db ][] = $id;
			}
		}
	}
}
_cnt();
//ksort( $data );
//_comp_save( DN_DATA . '/emdb/empiar.json.gz', $data );

//. PDB無理やり足す
$fitdb = _json_load( DN_PREP. '/emn/fitdb.json.gz' );
foreach ( $data as $key => $val ) {
	$pdb_id_set = $fitdb[ $key ];
	if ( ! $pdb_id_set ) continue;
	foreach ( $pdb_id_set as $pdb_id ) {
		foreach ( $val as $empiar_id ) {
			if ( in_array( $empiar_id, (array)$data[ $pdb_id ] ) ) continue;
			$data[ $pdb_id ][] = $empiar_id;
			$empiar_id_2_str_id[ $empiar_id ]['pdb'][] = explode( '-', $pdb_id )[1];
		}
	}
}

_json_save( FN_ID_TABLE, $empiar_id_2_str_id );

//. db prep
$sqlite = new cls_sqlw([
	'fn' => 'empiar', 
	'cols' => [
		'id UNIQUE' ,
		'data'
	],
	'new' => true ,
	'indexcols' => [ 'id' ] ,
]);
foreach ( $data as $key => $val ) {
	$sqlite->set([
		$key,
		json_encode( $val ),
	]);
}
$sqlite->end();
