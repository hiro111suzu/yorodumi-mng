<?php 
require_once dirname(__FILE__) . '/commonlib.php';

$_filenames += [
//	'emdb_pap'	=> "$dn/emdb/<id>.json" ,
//	'pdb_pap'	=> "$dn/pdb/<id>.json" ,
//	'sas_pap'	=> "$dn/sas/<name>.json" ,
//	'emdb_kw'	=> "$dn_kw/emdb/<id>.txt" ,
//	'pdb_kw'	=> "$dn_kw/pdb/<id>.txt" ,
//	'pdb_auth'	=> "$dn_kw/pdb_auth/<id>.txt" ,
//	'sas_kw'	=> "$dn_kw/sas/<name>.txt" ,
	'pap_info'	=> "$dn/info/<name>.json.gz"
];

define( 'MET_NAME', [
	'x-ray' => 'X-ray' ,
	'nmr'	=> 'NMR' ,
	'em'	=> 'EM'
]);

$db = new cls_sqlite( 'pap' );

$data = [];
foreach ( $db->qar([
	'select' => [ 'method', 'data' ] ,
]) as $read ) {
	_count( 1000 );
	foreach ( explode( '|', $read[ 'method' ] ) as $met ) {
//		_m( $met );
		$met = MET_NAME[ trim( $met ) ] ?: 'others';
//		_m( $met );
//		_pause( '?' );
		$cnt = 0;
		foreach ( json_decode( $read[ 'data' ] )->ids as $id ) {
			if ( explode( '-', $id, 2 )[0] != 'pdb' ) continue;
			++ $cnt;
		}
		if ( 50 < $cnt )
			$cnt = 50;
		++ $data[ $met ][ $cnt ];
	}
}
//print_r( $data );

//. 集計
$met_list = [ 'X-ray', 'NMR', 'EM', 'others' ];
$out = [ "Count\t". implode( "\t", $met_list ) ];
foreach ( range( 1, 50 ) as $cnt ) {
	$add = [ $cnt ];
	foreach ( $met_list as $met ) {
		$add[] = $data[ $met ][ $cnt ];
	}
	$out[] = implode( "\t", $add );
}
$out = implode( "\n", $out );
_m( $out );
file_put_contents( 'stat_pap_num.tsv', $out );

