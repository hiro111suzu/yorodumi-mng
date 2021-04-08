<?php
require_once( "unp-common.php" );

//. unpid-list
_line( '集計' );
_count();
$allids = [];

//. pdb
_line( 'pdb' );
foreach ( _json_load( FN_PDBID2UNPID ) as $pdbid => $unpid ) {
//	if ( _count( 'pdb', 0 ) ) break;
	foreach ( $unpid as $i ) {
		++ $allids[ $i ];
	}
}
_m( count( $allids ) );

//. emdb
_line( 'emdb' );
foreach ( _json_load( FN_EMDB_DATA ) as $emdbid => $c ) {
	if ( ! $c[ 'uniprot' ]  ) continue;
	foreach ( (array)$c[ 'uniprot' ] as $i ) {
		++ $allids[ $i ];
	}
}
_m( count( $allids ) );


//. sas
_line( 'sas' );
foreach ( _json_load( FN_SASID2UNPID ) as $sasid => $unpid ) {
//	if ( _count( 'pdb', 0 ) ) break;
	foreach ( $unpid as $i ) {
		++ $allids[ $i ];
//		_m( $i );
	}
}
_m( count( $allids ) );

arsort( $allids );
_comp_save( FN_ALL_UNPIDS, implode( "\n", array_keys( $allids ) ) );
_m( count( $allids ) . '個のUniProt-IDを保存' );

