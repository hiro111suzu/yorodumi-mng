<?php
require( "commonlib.php" );
$data = [];
foreach ( $emdbidlist as $id ) {
	$o = ( new cls_entid() )->set_emdb( $id )->mainjson()->experiment->vitrification;
	$num = count( $o );
	++$data[ $num ];
	if ( $num > 1 )
		_m( "$id:$num" );
}
print_r( $data );
