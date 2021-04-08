<?php
include 'commonlib.php';

foreach ( _json_load( DN_DATA . '/pdb/ids_replaced.json.gz' ) as $i => $j ) {
	if ( count( $j ) >1 )
		_m( "$i: " . _imp( $j ) );
}

