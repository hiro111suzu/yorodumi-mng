<?php
//. init
define( 'DN_MAREM_WORK' , DN_PREP. '/marem' );
define( 'FN_RECORDING'  , DN_MAREM_WORK. '/recording' );
define( 'FN_MAREM_STOP' ,  DN_MAREM_WORK. '/stop' );
define( 'FN_MINUTELY_LIVING', DN_MAREM_WORK. '/living' );

_m( FN_RECORDING );
define( 'FN_MAREM_LOG',
	DN_MAREM_WORK. '/log/'
	. date( 'Y-m-d' )
	. '-'
	. strtr( basename( $argv[0], '.php' ), [ 'marem-' => '' ] )
	. '.tsv' 
);

//. func
function _marem_log( $in ) {
	_m( "marem-log: $in" );
	file_put_contents(
		FN_MAREM_LOG,
		date( 'H:i:s' ). "\t". $in. "\n",
		FILE_APPEND
	);
}
