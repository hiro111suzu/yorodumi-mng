xmlのunitsを取得
数値しか入っていないタグを抽出

<?php
include "commonlib.php";


//. main

$data = [];
$nutag = [];

$numtag = [];
$strtag = [];

foreach ( _idlist( 'emdb' ) as $id ) {
	_count( 500 );
	_f( simplexml_load_file( _fn( 'emdb_xml', $id ) ) );
}

function _f( $xml ) {
	global $data, $nutag, $numtag, $strtag;
	if ( count( $x = $xml->children() ) > 0 ) foreach ( $x as $n => $v ) {
		if ( $v[ 'units' ] != '' )
			$data[ $n ][ (string)$v[ 'units' ] ] = 1;
		else
			$nutag[ $n ] = 1;
		if ( count( $v->children() ) > 0 )
			_f( $v );
		else {
			$v = trim( $v );
			if ( $v != '' and $v != 'na' and $v != 'NA' and $v != 'n/a' ) {
				if ( is_numeric( (string)$v ) ) {
					$numtag[ $n ] = 1;
				}
				else {
					$strtag[ $n ] = 1;
				}
			}
		}
	}
}

//. 評価

//.. 単位の抽出
_line( "タグ名: 単位" ); 
ksort( $data );
foreach ( $data as $n => $d ) {
	$data[ $n ] = $s = implode( ',', array_keys( $d ) );
	_m( "$n : $s" );
}
_comp_save( DN_DATA . '/emdb/xmlunit.json', $data );

//echo implode( ', ', array_keys( $nutag ) );

//.. 数値タグの抽出
_line( "数値タグ" ); 
$tags = [];
foreach ( array_keys( $numtag ) as $t ) {
	if ( $strtag[ $t ] != 1 )
		$tags[] = $t;
}
sort( $tags );
_m( _imp( $tags ) );
_comp_save( DN_PREP . '/emdb_xml_numtags.txt', implode( "\n", $tags ) );
