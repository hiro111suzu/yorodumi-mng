ポリゴンデータがあるかチェック
<?php
include 'commonlib.php';
$nopg = 0;

$blist = new cls_blist( 'ignore_polygon' );
foreach ( _idlist( 'emdb' ) as $id ) {
	if ( $blist->inc( $id ) ) continue;
	if ( _json_load2( _fn( 'emdb_add', $id ) )->met == 't' ) continue;
	$dn = DN_EMDB_MED . "/$id";
	if ( ! is_dir( "$dn/mapi" ) ) continue;
	if ( file_exists( "$dn/ym/o1.zip" ) ) {
		continue;
	}
	_problem( "$id: ポリゴンデータが無い" );
	++ $nopg;
}

_end();
