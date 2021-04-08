テスト

<?php
//. init

require_once( "commonlib.php" );
$out = array();

//. start main loop
foreach( $pdbidlist as $id ) {
	_m();
	$xml = _loadxml( $id );
//	$state = (string);

	$rec_met = _f( $xml->em_experimentCategory->em_experiment->reconstruction_method );
	++ $out[ 'recmet' ][ $rec_met ];

	$met = _f( $xml->exptlCategory->exptl[ 'method' ]  );
	++ $out[ 'met' ][ $met ];

//	$cr_met = _f( $xml->em_3d_reconstructionCategory->em_3d_reconstruction->method );
//	++ $out[ 'cr_met' ][ $cr_met ];

	++ $out[ "rec-met $rec_met" ][ "met $met" ];
	++ $out[ "met $met" ][ "rec-met $rec_met" ];


	if ( $met == 'electron crystallography' and $rec_met == 'tomography' )
		$out[ 'ids' ][ 'ec-tomo' ] .= "$id ";
//	if ( $met == 'electron crystallography' and $rec_met == 'tomography' )
//		$[ 'ids' ][ 'ec-tomo' ] .= "$id ";
/*	
	$fmet = _f( $xml->em_3d_fittingCategory->em_3d_fitting->method );
	++ $out[ 'fmet' ][ $fmet ];
	$fpro = _f( $xml->em_3d_fittingCategory->em_3d_fitting->ref_protocol );
	++ $out[ 'fpro' ][ $fpro ];
*/

}

$a = array();
ksort( $out );
foreach ( $out as $n1 => $v1 ) {
	_m( $n1, 1 );
	$a[] = "\n. $n1";
	ksort( $v1 );
	foreach ( $v1 as $n2 => $v2 ) {
		_m( $a[] = substr( "                                $n2", -40 ) . ": $v2" );
	}
}

file_put_contents( "out.txt", implode( "\n", $a ) );

function _f( $s ) {
	return _nn( _x( strtolower( (string)$s ) ), '_null' );
}
