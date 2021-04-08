<?php
require_once( "commonlib.php" );

$data = [];
foreach ( _idloop( 'pdb_plus' ) as $fn ) {
	$id = _fn2id( $fn );
	if ( substr( $id, 0, 1 ) != 5 ) continue;
	foreach ( (array)_json_load2( $fn )->struct_site as $c ) {
		$type = $c->info_type;
		if ( in_array( $type, [ 'prosite', 'Swiss-Prot' ] ) )
			continue;
		$data[ $type ][] = $id;
		_m( "$id: $type" );
	}
}

_json_save( DN_PREP. '/temp_plus_db_type.json.gz', $data );
