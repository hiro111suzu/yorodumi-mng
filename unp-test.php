<?php
require_once( "uniprot-common.php" );

//. pdbid2unpid
$flg_changed = false;
foreach ( _idloop( 'unp_xml', 'json作成' ) as $fn_in ) {
	if ( _count( 5000, 0 ) ) {
//		_cnt( '_temp_' );
	}
	$unp_id = _fn2id( $fn_in );

	if ( filesize( $fn_in ) == 0 ) continue;
	$xml = simplexml_load_file( $fn_in )->entry;

	//.. feature
	$a =[];
	foreach ( (object)$xml->feature as $c ) {
		$keys = [];
		foreach ( $c->location->children() as $key => $val ) {
			$keys[] = $key;
		}
		$a[ _imp( $keys ) ] = true;
	}
	foreach ( array_keys( $a ) as $t ) {
		_cnt( $t );
	}



/*
	_cnt( 'total' );
	foreach ( (object)$xml->feature as $c ) {
		$type = (string)$c[ 'type' ];
		if ( ! $type ) continue;
		$a[ $type ] = true;
	}
	foreach ( array_keys( $a ) as $t ) {
		_cnt( $t );
	}


/*
	$ar = [];
	foreach ( $xml->protein as $k1 => $c1 ) {
		foreach ( $c1 as $k2 => $c2 ) {
			$ar[ "$k1.$k2" ] = true;
		}
	}
	_cnt( 'total' );
	foreach ( array_keys( $ar ) as $k )
		_cnt( $k );
*/
}
_cnt();
