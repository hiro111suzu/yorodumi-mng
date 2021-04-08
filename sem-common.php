<?php
require_once( "commonlib.php" );
define( 'DN_SEMINAR', DN_PREP. '/seminar' );
_mkdir( DN_SEMINAR );

//. function
function _date_dif( $str1, $str2 ) {
	list( $y1, $m1, $d1 ) = explode( '-', $str1 );
	list( $y2, $m2, $d2 ) = explode( '-', $str2 );
	return floor(
		( mktime( 0, 0, 0, $m1, $d1, $y1 ) - mktime( 0, 0, 0, $m2, $d2, $y2 ) )
		/ 24 / 3600
	);
}
