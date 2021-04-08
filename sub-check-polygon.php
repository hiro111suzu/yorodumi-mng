ポリゴンデータがあるかチェック
<?php
include 'commonlib.php';
$nopg = 0;
$blacklist = [ 2489, 2708, 6042, 6471, 6472, 6473 ];

foreach ( $emdbidlist as $id ) {
	if ( in_array( $id, $blacklist ) ) continue;
	$dn = DN_EMDB_MED . "/$id";
	if ( ! is_dir( "$dn/mapi" ) ) continue;
	if ( file_exists( "$dn/ym/o1.zip" ) ) {
		continue;
	}
	_problem( "$id: ポリゴンデータが無い" );
	++ $nopg;
}

_end();
