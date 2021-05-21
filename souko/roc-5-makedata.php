<?php
include "commonlib.php";
define( 'WDN', DN_ROOT . '/roc_work' );
define( 'SCDN', WDN . '/scores' );

define( 'OUTDN', WDN . '/data' );
_mkdir( OUTDN );

//. 

$pairs = _json_load( WDN . '/pairs.json' );

foreach ( glob( SCDN . '/*.json' ) as $pn ) {
	$meth = basename( $pn, '.json' );
	$sc = _json_load( $pn );
	$out = [];
	foreach ( $sc as $ids => $s ) {
		$p = $pairs[ $ids ];
		$out[ $p[ 'grp' ] ] .= "$s\t" . ( $p[ 'same' ] ? '1' : '0' ) . "\n";
	}
	foreach ( $out as $g => $txt ) {
		_comp_save( OUTDN . "/$meth-$g.txt", $txt );
	}
}


