<?php
require_once( "pap-common.php" );

//. jnl2IF
$issn2impact_f = [];
foreach ( _file( FN_JOURNAL_CSV ) as $line ) {
	//- 0:順位 1:ジャーナル名, 2:ISSN, 3:IF
	list( $order, $journal_name, $issn, $impact_f ) = explode( ",", $line );
	if ( ! is_numeric( $order ) ) continue;
	if ( $impact_f == '' ) continue;

	$journal_name = strtolower( $journal_name );
	if ( is_numeric( $impact_f ) ) {
		$issn2impact_f[ $issn ] = $impact_f;
	}
}
_comp_save( FN_IS2IF, $issn2impact_f );


