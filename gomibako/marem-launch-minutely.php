<?php
//. init
require_once( "commonlib.php" );
require_once( 'marem-common.php' );

while ( true ) {
	_main();
	sleep( 60 );
}

function _main() {
	if ( file_exists( FN_MAREM_STOP ) ) {
		_m( 'launch - stop file', 'green' );
		return;
	}
	if ( file_exists( FN_MINUTELY_LIVING ) ) {
		_m( 'launch - mimutely: living', 'green' );
		return;
	}
	_php( 'marem-minutely', '&' );
}

