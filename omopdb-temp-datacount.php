omo-pdb-3: DBへロード

<?php

//. init
require_once( "commonlib.php" );
require_once( "omo-common.php" );

//. main
foreach ( [ 's', 'ss' ] as $mode ) {
	_line( $mode );
	_count();
	foreach ( glob( _fn( 'prof_pdb', '*' ) ) as $fn ) {
		if ( _count( 10, 10 ) ) break;
		_readload( $fn, $mode );
	}
}

//. func
//.. _readload
function _readload( $fn, $mode ) {
	//... 読み取り
	foreach ( _file( $fn ) as $num => $line ) {
		_m( "$num: " . _sh( $line, $mode ) );
	}
}

//.. _sh: シュリンク
function _sh( $in, $mode) {
//	if ( MODE == 'full' )
//		return $in;

	$in = explode( ',', $in );
	$cnt = count( $in );

	//- 何個に一個残すか
	$step = 3;
	if ( $cnt > 400  ) $step = 4;
	if ( $cnt > 1200 ) $step = 12;
	if ( $mode == 'ss' ) $step *= 2;

	$out = [];
	$sum = 0;
	foreach ( $in as $i => $val ) {
		$sum += $val;
		if ( $i % $step > 0 ) continue;
		$out[] = $sum / $step;
		$sum = 0;
	}
	return count( $out );
}
