<?php
//. misc init
require_once( "commonlib.php" );
$cnt = [];

foreach ( glob( DN_PREP . '/maindbjson/*.json' ) as $fn ) {
	$j = _json_load( $fn );
	foreach ( explode( '|', trim( $j[ 'spec' ], '|' ) ) as $s ) {
		if ( $s == '' ) continue;
		++ $cnt[ strtolower( $s ) ];
	}
}

arsort( $cnt );
_comp_save( DN_PREP . '/ranking_taxo_em.json', $cnt );
_tsv_save( DN_PREP . '/ranking_taxo_em.tsv', $cnt );

