<?php
include "commonlib.php";
$ja = _tsv_load( DN_PREP. '/dbid/ecnum_ja.tsv' );
$data = [];
foreach ( range( 1, 10 ) as $n1 ) {
	foreach ( range( 0, 100 ) as $n2 ) {
		if ( $n2 == 0 ) 
			$n2 = '-';
		foreach ( range( 0, 100 ) as $n3 ) {
			if ( $n3 == 0 ) 
				$n3 = '-';
			$num = implode( '.', [ $n1, $n2, $n3 ] );

			$t = _ezsqlite([
				'dbname' => 'dbid' ,
				'select' => 'title' ,
				'where'  => [ 'db_id', "ec:$num.-" ]  ,
			]);
			if ( !$t ) continue;

			$data[ "$num.-" ] = implode( '; ', _uniqfilt([
				$ja[ $n1 ] ,
				$ja[ "$n1.$n2" ] ,
				$ja[ "$n1.$n2.$n3" ] ,
			])) ?: $t;
		}
	}
}

_comp_save( DN_DATA. '/ecnum_ja.json.gz', $data );


