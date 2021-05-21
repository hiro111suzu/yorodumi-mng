データをロード
mongoimport -d pdb -c pdb --file json-pdb/100d.json --upsert

<?php
include "commonlib.php";

$dn = "../json_test";
$ejsondn = "$dn/json-emdb";

foreach ( $emdbidlist as $id ) {
	$fn = "$ejsondn/$id.json";
	exec( "mongoimport -d emdb -c main --file " . _fn( 'emdb_json', $id ) );
	_m( "$id: loaded" );
}

