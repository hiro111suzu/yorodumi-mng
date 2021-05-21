データをロード
mongoimport -d pdb -c pdb --file json-pdb/100d.json --upsert

<?php
include "commonlib.php";

$dn = "../json_test";
$ejsondn = "$dn/json-emdb";
$pjsondn = "$dn/json-pdb";

$cnt = 0;
foreach ( glob( "$pjsondn/*.json" ) as $fn ) {
//	++ $cnt;
//	if ( $cnt < 2000 ) continue;

	$id = substr( basename( $fn ), 0, 4 );

	$out = [];
	exec( "mongoimport -d pdb -c main --file $fn --upsert", $out );
	$out = implode( "\n", $out );

	//- エラー
	if ( _instr( 'error', $out ) ) {
//		_m( "===== $id =====:\n$out" );
		_m( "$id - $out" );
		file_put_contents( "$dn/error/$id.txt", $out );
	}		
//	_m();
//	if ( $cnt > 3000 ) die();
}

