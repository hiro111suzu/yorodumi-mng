<?php

//. init
chdir( __DIR__ );
include 'commonlib.php';
_line( 'auto run', '待機開始 - '. date( 'Y-m-d H:i:s' ) );
while ( true ) {
	if ( file_exists( $fn = DN_PROC . '/stop' ) )
		die( "##### 強制終了、解除するには： \nrm $fn\n\nまたは\nmng start" );

	$list = (array)glob( DN_PREP. '/auto_run/*' );
	if ( $list ) {
		_line( 'auto run', count( $list ). " タスク" );
		foreach ( $list as $fn ) {
			if ( ! file_exists( $fn ) ) continue;
			_del( $fn );
			_php( 'mng', basename( $fn ) );
		}
		_line( 'auto run', '終了/待機開始 - '. date( 'Y-m-d H:i:s' ) );
	}
	sleep( 10 );
}

