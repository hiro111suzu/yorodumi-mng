<?php
require_once( "commonlib.php" );
$fn_lastwget = DN_PREP . '/time_emp_last_wget.txt';
$dn = DN_FDATA . '/empiar';
$xmlpath = "$dn/ftp.ebi.ac.uk/pub/databases/empiar/archive/*/*.xml";

//. wget

$last = file_get_contents( $fn_lastwget );
if ( ( 3600 * 5 ) < time() - $last || $argv[1] == 'f' ) {
	$cl = "wget -qr -A .xml -P $dn ftp://ftp.ebi.ac.uk/pub/databases/empiar/archive/";
	_m( "wget開始、コマンドライン: $cl" );
	exec( $cl );
} else {
	_m( 'wgetは最後に実行してから5日以内なので、実行しない' );
}
file_put_contents( $fn_lastwget, time() );

//. 
$data = [];
foreach ( glob( $xmlpath ) as $pn ) {
	_count( 10 );
	$id = basename( $pn, '.xml' );
	$xml = simplexml_load_file( $pn );
	
	
	$d = [];
	$data[ $id ] = [
		'title'		=> (string)$xml->admin->title ,
		'data size' => $xml->admin->datasetSize .' '. $xml->admin->datasetSize[ 'units' ] ,
	];
	$num = 0;
	foreach ( $xml as $k => $x ) {
		if ( $k != 'imageSet' ) continue;
		++ $num;
//		print_r( $x );
//		_pause();
		$data[ $id ][ 'Data #' . $num ] =
//			'name' => (string)$x->name ,
//			'category' => (string)$x->category
			(string)$x->name . ' [' . (string)$x->category . ']'
		;
	}
	
	$x = $xml->crossReferences;
	if ( count( $x ) > 0 ) foreach ( $x->children() as $c ) {
		foreach ( $c->children() as $k => $c2 ) {
			if ( $k == 'emdbEntry' )
				$c2 = 'emdb-' . _numonly( $c2 );
			else if ( $k == 'pdbEntry' )
				$c2 = 'pdb-' . $c2;
			else
				continue;

			if ( $c2 != '' ) {
				$data[ $c2 ][] = $id;
//				_m( "$c2 -- $id" );
			}
		}
	}
}

ksort( $data );
_comp_save( DN_DATA . '/emdb/empiar.json.gz', $data );

