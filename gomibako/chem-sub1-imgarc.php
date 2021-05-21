chem_comp_cifから画像

<?php
require_once( "commonlib.php" );

$imgdn = DN_DATA . '/chem/img';
_del( $fn = DN_DATA . '/chem/imgs.phar' );

$phar = new Phar( $fn, 0 );
foreach ( glob( "$imgdn/*.gif" ) as $pn ) {
	if ( _count( 100, 500 ) ) break;
	$phar->addFile( $pn, basename( $pn, '.gif' ) );
}

