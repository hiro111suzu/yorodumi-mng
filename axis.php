
<?php
$cd = getcwd();
require_once( "commonlib.php" );

//- get xyz
$file = _file( "$cd/". $argv[1] );
list( $ax, $ay, $az ) = _get_xyz( $file[1] );
list( $bx, $by, $bz ) = _get_xyz( $file[2] );
print_r( $xyz );

//- center
_out(
	'center'  ,
	( $ax + $bx ) /  2 ,
	( $ay + $by ) /  2 ,
	( $az + $bz ) /  2
);
//- axis
$len = sqrt(
	pow( $ax - $bx, 2 ) +
	pow( $ay - $by, 2 ) +
	pow( $az - $bz, 2 )
);
_out(
	'axis' ,
	( $ax - $bx ) / $len ,
	( $ay - $by ) / $len ,
	( $az - $bz ) / $len
);

//. func
function _get_xyz( $line ) {
	return array_slice(
		preg_split( '/ +/', $line ) ,
		5, 3
	);
}

function _out( $name, $x, $y, $z ) {
	_m( "$name\n$x $y $z" );
}
