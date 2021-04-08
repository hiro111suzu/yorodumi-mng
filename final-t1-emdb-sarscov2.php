<?php
include 'commonlib.php';

//. db
_line( 'sars-cov-2 data in EMDB' );
define( 'FN_OUT', DN_DATA. '/emdb-sars-cov2.json' );
define( 'NEW_IDS', _file( DN_PREP. '/newids/latest_new_map.txt' ) );

$ids = ( new cls_sqlite( 'main' ) )->qcol([
	'select' => 'id', 
	'where' => "database is \"EMDB\" AND"
		. " spec like \"%|SEVERE ACUTE RESPIRATORY SYNDROME CORONAVIRUS 2|%\""
]);

$latest = 0;
foreach ( $ids as $id ) {
	if ( in_array( $id, NEW_IDS ) )
		++ $latest;
}
$data = [
	'all' => count( $ids ) ,
	'latest' => $latest
];


_kvtable( $data );
_json_save( FN_OUT, $data );

