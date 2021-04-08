<?php
include "commonlib.php";
$count = [];
foreach ( _idlist( 'pdb' ) as $id ) {
	if ( _count( 'pdb', 10000 ) ) break;
	foreach ( (array)_json_load2( _fn( 'pdb_json', $id ) )
		->ndb_struct_na_base_pair_step as $c 
	) {
		++ $count[ floor( $c->helical_twist ) ?: '?' ];
	}
}
ksort( $count );
_kvtable( $count );
_json_save( DN_PREP. '/nuc_twist_stat.json', $count );

/*
foreach ( _idlist( 'pdb' ) as $id ) {
	if ( _count( 'pdb', 0 ) ) break;
	foreach ( (array)_json_load2( _fn( 'pdb_json', $id ) )
		->ndb_struct_na_base_pair as $c 
	) {
		++ $b12[ $c->hbond_type_12 ?: '-' ];
		++ $b28[ $c->hbond_type_28 ?: '-' ];
	}
}

arsort( $b12 );
arsort( $b28 );

_kvtable( $b12, 'hbond_type_12' );
_kvtable( $b28, 'hbond_type_28' );

_json_save( DN_PREP. '/na_hbond_type.json', [
	'hbond_type_12' => $b12 ,
	'hbond_type_28' => $b28
]);
*/