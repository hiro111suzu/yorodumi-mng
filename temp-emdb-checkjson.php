<?php
require( "commonlib.php" );
$data = [];
foreach ( $emdbidlist as $id ) {
	$o = ( new cls_entid() )->set_emdb( $id )->mainjson()->processing->reconstruction;
	++ $data[ gettype( $o ) ];
}
print_r( $data );
