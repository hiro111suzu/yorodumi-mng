<?php
require_once( "commonlib.php" );

$all = [];
foreach ( glob( '../backup/manager/*' ) as $pn ) {
//	$ext = _ext( $pn );
//	if ( $ext != 'php' )
//		_pause( $pn );

	$bn = basename( $pn, '.php' );

	$date = substr( $bn, -10 );
	$to = strtr( $bn, [ "-$date" => '' ] ) . '.php';
	$all[ $to ][] = $date;
}

$tsv = '';
foreach ( $all as $to => $date_set ) {
	$latest = max( $date_set );
//	$tsv .= "$to\t$latest\n";
//	_m( "$to\t$latest" );
	
	if ( file_exists( $to ) ) {
		_m( "exists - $to" ) ;
		continue;
	}
	$from = "../backup/manager/". strtr( $to, [ '.php' => "-$latest.php" ] );
//	if ( ! file_exists( $from ) )
//		_m( 'ない！！！！！！！！！！！！！！！！！！！！！' );
//	else
//		_m( 'ある(^_^)' );
	_pause( "$from => $to" );
	copy( $from, $to );
	
}

//file_put_contents( 'file_list.tsv', $tsv );
