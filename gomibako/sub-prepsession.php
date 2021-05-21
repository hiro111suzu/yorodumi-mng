chimera sessionファイルからディレクトリ名消し

<?php
require_once( "commonlib.php" );
$_y = '\\\\\\\\';

$fns = [
	'session1.py',
	'session2.py',
	'session3.py',
	'session4.py',
	'session5.py',
	'session6.py',
	'session7.py',
	's1.py',
	's2.py',
	's3.py',
	's4.py',
	's5.py',
	's6.py',
	's7.py'
];

//. main loop
foreach ( $emdbidlist as $id ) {
	$dn = _fn( '_med' );
	_count( 100 );
	foreach ( $fns as $fn ) {
		$fp = "$dn/$fn";
		if ( ! file_exists( $fp ) ) continue;

		//- マップ ファイル名フルパス -> 相対パス
		$path = _fn( 'emdb_med', $id ) . '/';
		$novem_path = strtr( $path, [ 'mardisk2' => 'novdisk2' ] );
		$marem_path = strtr( $path, [ 'novdisk2' => 'mardisk2' ] );
		$zbox2_path = strtr( $novem_path, [ '/novdisk2' => 'n:', '/' => '\\' ] );

		$f_in = file_get_contents( $fp );
		$f_out = strtr( $f_in, [
			$novem_path => '' ,
			$marem_path => '' ,
			$zbox2_path => '' 
		] );

		if ( $f_in == $f_out )
			continue;

		$origtime = filemtime( $fp );
		file_put_contents( $fp, $out );
		touch( $fp, $origtime );
		_del( $fp . 'c' );

		_m( "$id-$fn" );

	}
}
