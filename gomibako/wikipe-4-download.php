<?php
/*
key	taxo(sci_name) chem(chemid) other(title)

date
en [ key, abst ] ,
ja [ key, abst ]
*/
require_once( "wikipe-common.php" );
define( 'FN_JSON_LIST', [
	'chem' => FN_CHEM_LIST ,
	'taxo' => FN_TAXO_LIST ,
	'misc' => FN_MISC_LIST ,
]);

//. main
_get_data( 'chem' );
_get_data( 'taxo' );
_get_data( 'misc' );
//_get_data( 'chem' );
//_get_data( FN_TAXO_LIST, 'taxo' );


//. func _get_data
function _get_data( $type ) { 
	$words = _json_load( FN_JSON_LIST[ $type ] );
	foreach ( (array)$words as $key => $terms ) {
		$fn = _fn_wikipe_json( $type, $key );
		if ( file_exists( $fn ) ) continue;

		if ( $terms == '' ) {
			$terms = [ $key ];
		}
		if ( is_string( $terms ) ) {
			$terms = [ $terms ];
		}

		_line( 'key', $key );

		//.. 
		$et = $ea = $jt = $ja = '';
		foreach ( (array)$terms as $term ) {
			if ( ! $term ) continue;
			if ( _in_ng_list( $term ) ) continue;

			_m( "Search for: \"$term\"" );
			//- 英語
			sleep( 1 );
			list( $et, $ea ) = _get_en( $term );
			if ( $et == '' ) {
				_add_ng_list( $term );
				continue;
			}
			
			//- 日本語
			list( $jt, $ja ) = _get_ja( $et );
			break;
		}
		$json = array_filter([
			'key' => $key ,
			'et' => $et ,
			'ea' => $ea ,
			'jt' => $jt ,
			'ja' => $ja ,
		]);
		if ( $et . $jt ) {
			_kvtable( $json );
			_m( "got $et, $jt", 1 );
			_json_save( $fn, $json );

		} else {
			_m( 'not found', -1 );
		}
		if ( file_exists( DN_TEMP . '/proc/stop' ) ) break;
	}
}
_save_ng_list();

//. func 
//.. _get_wikipedia:
function _get_wikipedia( $data, $lang = 'en' ) {
	$u =  $lang == 'en'
		? 'https://en.wikipedia.org/w/api.php'
		: 'https://ja.wikipedia.org/w/api.php'
	;
	$data[ 'format' ] = 'json';
	if ( $data[ 'action' ] == '' )
		$data[ 'action' ] = 'query';
	return json_decode( file_get_contents( "$u?" . http_build_query( $data ) ) )->query;
}

//.. _get_en
function _get_en( $word ) {
	return _title_abst( _get_wikipedia([
		'titles' => $word, 'prop' => 'extracts', 'exintro' => '1'
	]));
}

//.. _title_abst
function _title_abst( $j ) {
	foreach ( $j->pages as $c ) {
		if ( $c->extract == '' ) continue;
		return [
			$c->title,
			trim( strtr( $c->extract, [ '<p></p>' => '', "\n\n" => "\n" ] ) )
		];
	}
	return false;
}

//.. _get_ja
function _get_ja( $word ) {
	$j = _get_wikipedia([
		'titles' => $word, 'prop' => 'langlinks',  'lllang' => 'ja'
	]);
	$n = false;
	foreach ( $j->pages as $c ) {
		if ( ! is_object( $c->langlinks[0] ) ) continue;
		$n = $c->langlinks[0]->{'*'};
		break;
	}
	if ( !$n ) return false;
	return _title_abst( _get_wikipedia([
		'titles' => trim( $n ), 'prop' => 'extracts', 'exintro' => '1'
	], 'ja' ) );
}

//.. testout
function _testout( $json, $title ) {
	_m( $title, 1 );
	_m( json_encode( $json, JSON_PRETTY_PRINT ) );
}
