<?php
//. init
require_once( "commonlib.php" );
require_once( 'marem-common.php' );

while ( true ) {
	_main();
	sleep( 60 );
}

function _main() {
	//- 生きてるフラグ
	if ( ! file_exists( FN_MINUTELY_LIVING ) )
		touch( FN_MINUTELY_LIVING );

	if ( file_exists( FN_MAREM_STOP ) ) {
		_marem_log( 'stopファイル' );
		return;
	}

	if ( file_exists( DN_MAREM_WORK. '/job.txt' ) ) {
		list( $cmd, $opt )  = explode( ' ', file_get_contents( DN_MAREM_WORK. '/job.txt' ) );
		_marem_log( 'job.txt - '. "$cmd $opt" );
		_php( $cmd, $opt . ' &' );
	} else {
		if ( file_exists( FN_RECORDING ) ) {
			_marem_log( 'rec 実行中' );
			return;
		} else {
			_marem_log( 'rec 起動' );
			_php( 'marem-1', ' &'  );
			_marem_log( 'rec 終了' );
			return;
		}
	}
}

