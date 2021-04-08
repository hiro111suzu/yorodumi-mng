<?php

include "commonlib.php";
$html_chr = [];

define( 'FLG_DOWNLOAD', true );
//define( 'FLG_DOWNLOAD', false );

define( 'URL_EC_XML', 'https://www.enzyme-database.org/downloads/enzyme-data.xml.gz' );
define( 'FN_EC_XML', DN_FDATA. '/dbid/enzyme-data.xml.gz' );

define( 'REP_HTML', [
	'&#151;'	=> '-' ,
	'&#8217;'	=> "'" ,
	'&#8242;'	=> "'" ,
	'&#945;'	=> 'alpha' ,
	'&#946;'	=> 'beta' ,
	'&alpha;'	=> 'alpha' ,
	'&beta;'	=> 'beta' ,
	'&delta;'	=> 'delta' ,
	'&epsilon;'	=> 'epsilon' ,
	'&gamma;'	=> 'gamma' ,
	'&gt;'		=> '>' ,
	'&nbsp;'	=> ' ' ,
	'&ndash;'	=> '-' ,
	'&prime;'	=> "'" ,
	'---'		=> '-' ,
	'--'		=> '-' ,
]);

//. download
_line( 'EC', 'download' );
_flg_copy( URL_EC_XML, FN_EC_XML );

//. convert
_line( 'EC', 'conversion' );

$out = [];
$xml = simplexml_load_string( implode( "\n",  gzfile( FN_EC_XML ) ) );
foreach ( $xml->database->table_data as $x ) {

	//.. class
	if ( $x['name'] == 'class' ) {
		$pre = [];
		foreach ( $x->row as $x2 ) {
			$v = [];
			foreach ( $x2->field as $x3 )
				$v[ (string)$x3['name'] ] = (string)$x3;
			$a = array_filter([ $v['class'], $v['subclass'], $v['subsubclass'] ]);
			$pre[ implode( '.', $a ) ] = [
				'heading' => _str( $v['heading'] ) ,
				'ids' => $a
			];
		}
		foreach ( $pre as $id => $v ) {
			$heading = $ids = '';
			extract( $v );
			$name = [];
			if ( 1 < count( $ids ) )
				$name[] = $pre[ $ids[0] ][ 'heading' ]  ;
			if ( 2 < count( $ids ) )
				$name[] = $pre[ $ids[0]. '.'. $ids[1] ][ 'heading' ];
			if ( ! _instr( '(only sub-subclass identified to date)', $heading ) )
				$name[] = $heading;
			$data[ $id ] = implode( '; ', $name );
		}
	}
	
	//.. entry

	if ( $x['name'] == 'entry' ) {
		foreach ( $x->row as $x2 ) {
			$v = [];
			foreach ( $x2->field as $x3 )
				$v[ (string)$x3['name'] ] = _str( $x3 );
			if ( $v['ec_num'] && $v['accepted_name'] )
				$data[ $v['ec_num'] ] = $v['accepted_name'];
		}
	}

}
ksort( $data );
//print_r( $data );
_comp_save( FN_EC_NAME, $data );
//_m( $data[ '6.1.1.6' ] );
sort( $html_chr );
_m( implode( "\n", $html_chr ) );

_end();

//. func
//.. _flg_do
function _flg_copy( $in, $out ) {
	if ( FLG_DOWNLOAD )
		_download( $in, $out );
	else
		_m( 'Downloading is canceled' );
}

//.. _str
function _str( $s ) {
	global $html_chr;
	$r = strip_tags( html_entity_decode( strtr( $s, REP_HTML ) ) );
//	if ( $s != $r )
//		_m( "$s => $r" );
//	foreach (
//	preg_match_all( '/&.{1,10};/', $s, $match, PREG_PATTERN_ORDER );
//	$html_chr = array_unique( array_merge( $html_chr, $match[0] ) );
	return (string)$r;
}

