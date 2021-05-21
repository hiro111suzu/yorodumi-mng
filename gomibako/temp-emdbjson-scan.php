<?php
include "commonlib.php";
$as = [];
foreach ( $emdbidlist as $id ) {
	$j = _json_load2( $n = _fn( 'emdb_json', $id ) );
	if ( count( $j->experiment->imaging ) > 1 )
		_m( $id );
}
