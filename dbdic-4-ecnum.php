<?php
require 'commonlib.php';
$json_wikipe = _json_load( DN_DATA. '/wikipe.json.gz' );
_m( count( $json ) );
/*
url
テキストをコピペ
https://en.wikipedia.org/wiki/List_of_EC_numbers_(EC_1)
https://en.wikipedia.org/wiki/List_of_EC_numbers_(EC_2)
https://en.wikipedia.org/wiki/List_of_EC_numbers_(EC_3)
https://en.wikipedia.org/wiki/List_of_EC_numbers_(EC_4)
https://en.wikipedia.org/wiki/List_of_EC_numbers_(EC_5)
https://en.wikipedia.org/wiki/List_of_EC_numbers_(EC_6)

*/
//define( 'E2J', _json_load( DN_DATA . '/term_e2j.json.gz' ) );
define( 'TOP', [
	'1' => 'oxidoreductases' ,
	'2' => 'transferases' ,
	'3' => 'hydrolases' ,
	'4' => 'lyases' ,
	'5' => 'isomerases' ,
	'6' => 'ligases' ,
]);


if ( ! E2J ) _die( 'no data');
//. main
$out =[
	'1' => 'oxidoreductases' ,
	'2' => 'transferases' ,
	'3' => 'hydrolases' ,
	'4' => 'lyases' ,
	'5' => 'isomerases' ,
	'6' => 'ligases' ,
];
$out_j = [
	'1' => '酸化還元酵素' ,
	'2' => '転移酵素' ,
	'3' => '加水分解酵素' ,
	'4' => '脱離酵素' ,
	'5' => '異性化酵素' ,
	'6' => '合成酵素' ,
];

foreach ( glob( DN_FDATA . '/ec_num/*.txt' ) as $fn_in )  {
	$fn = basename( $fn_in, '.txt' );
	_line( basename( $fn ) );
	foreach ( _file( $fn_in ) as $line ) {
//		_pause( $line );
		if ( substr( $line, 0, 3 ) != 'EC ' ) {
//			_pause( substr( $line, 0, 3 )  );
			continue;
		}
		list( $dummy, $id, $name ) = explode( ' ', $line, 3 );
		$name = trim( $name );
		if ( $name == '' ) continue;
		$i = strtr( $id, [ 'EC ' => '', ':' => '' ] );
		$out[ $i ] = $name;
		$j = $json_wikipe[ strtolower($name) ]['jt'];
		_m( "$name: $j" );
		$out_j[ $i ] = _nn( $j, $name );
		if ( $j ) {
			_m( "$name  => $j" );
			_cnt( 'JP' );
		}
		_cnt( 'total' );
		_cnt( $fn );
	}
}
_cnt();
_comp_save( DN_DATA . '/pdb/ecnum2name.json.gz', $out );
_comp_save( DN_DATA . '/pdb/ecnum2namej.json.gz', $out_j );

