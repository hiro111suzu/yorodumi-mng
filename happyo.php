<?php
//. init
include( 'commonlib.php' );

$keyconv = [
	'.'     => 'date',
	'..'    => 'date',
	'for'   => 'for',
	'place' => 'place',
	'categ' => 'categ',
	'auth'  => 'auth',
	'title' => 'title',
];

//. load
$dataset = [];
$index = 0;
foreach ( _file( '../../work/happyo.tsv' ) as $line ) {
	list( $key, $val ) = explode( "\t", $line, 2 );
	$key = $keyconv[ trim( $key ) ];
	$val = trim( $val );
	if ( $val == '' ) continue;
	if ( $key == 'date' ) {
		$index = $val;
		while ( $dataset[ $index ] != '' )
			$index .= '-';
	}
	$dataset[ $index ][ $key ] = $val;
}
krsort( $dataset );
//_kvtable( $dataset );

//. make
$out = [];
foreach ( $dataset as $data ) {
	$date = $for = $place = $categ = $auth = $title = '';
	extract( $data );
	$type = '';
	if ( $categ == 'ポスター発表' )
		$type .= '(ポスター発表)';
	if ( $categ == '口頭' )
		$type .= '(講演)';
	if ( $categ == '公開講座' )
		$type .= '(公開講座)';
	
//	if ( strtotime( $date ) < strtotime( '1012-04-04' ) ) continue;
	$d = $date;
	while(1) {
		if ( ! $out[ $d ] ) break;
		$d .= '-';
	}
	$out[ $d ] = $type . '「' . $title . "」\n" 
		. _authstr( $auth ) . "\n"
		. _imp( _datestr( $date ), $place, $for )
	;
//		$categ, 
//		$for ,
//		$place ,
//		'国内'
//	];

/*
	if ( $categ != '公開講座' ) continue;
	if ( strtotime( $date ) < strtotime( '2012-04-04' ) ) continue;
	$out[] = [
		$title,
		$categ, 
		_authstr( $auth ) ,
		$for ,
		$place ,
		_datestr( $date ) ,
		'国内'
	];
*/
}
$o = implode( "\n\n", $out );
krsort( $out );
_m( $o );

file_put_contents( "temp.txt", $o );
/*
$html = '';
foreach ( $out as $d ) {
	$html .= '<p>' . implode( '、', $d ) . '</p>';
}
echo $html;
file_put_contents( 'temp.html', $html );
*/
//. func
//.. _authstr
function _authstr( $in ) {
	$ret = [];
	foreach ( explode( '|', $in ) as $a ) {
//		$ret[] = $a == '鈴木博文' || $a == 'Hirofumi Suzuki'
//			? "<u>$a</u>" : $a;
		$ret[] = $a;
	}
	return implode( '、', $ret );
}

//.. _datestr
function _datestr( $in ) {
	$s = strtotime( $in ) ;
//	return date( 'Y年n月j日', $s );
//	return date( 'Y.n.j', $s );
	return date( 'Y.n', $s );
//	return $lang == 'e' ? date( 'M j, Y', $s ) : date( 'Y年n月j日', $s );
}


