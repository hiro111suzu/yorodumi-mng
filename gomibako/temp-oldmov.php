古くなったムービーをチェック
<?php

//. init
require_once( "commonlib.php" );

$workdn = getcwd() . "/oldmov";
if ( ! is_dir( $workdn ) )
	die( 'No working dir' );
$oldmovdn = "$workdn/old";
$okmovdn = "$workdn/ok";
$donemovdn = "$workdn/done";

$sfns = array(
	's2.py',
	's1,py',
	'session2.py',
	'session1.py'
);

$smalldb = _json_load( DN_DATA . '/db-small.json.gz' );

//. main loop

foreach ( $emdbidlist as $id ) {
	$did = "emdb-$id";
	$ddn = DN_EMDB_MED . "/$id";
	$imgname = "$oldmovdn/$id.jpg";

	if ( in_array( $id, array( 5252, 2045, 2040, 2018, 1756, 2008, 2009, 2010 ) ) )
		continue;

	if ( file_exists( $imgname ) ) continue;


	$img = DN_EMDB_MED . "/$id/snapss1.jpg";
	if ( file_exists( $img ) )
		touch( $img );
}

