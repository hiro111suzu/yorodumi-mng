<?php
require_once( "wikipe-common.php" );
define( 'TYPE', 'chem' );
define( 'IDS', array_keys( _json_load( DN_PREP. '/ranking_chem.json' ) ) );
_define_annot( 'chem' );

define( 'NG_TERMS', [
	'unknown' ,
	'unknown' ,
	'synthetic' ,
	'artificial' ,
	'[' ,
]);

define( 'SHOW_DETAIL', false );

//. main 
$out = [];
foreach ( array_merge( array_keys( ANNOT), IDS ) as $id ) {
//	if ( $id == 'GBM' )
//
//	else 
//		continue;

	_count( 5000 );
	$fn = _fn_wikipe_json( 'chem', $id );
	$json = _json_load2( _fn( 'chem_json', $id ) )->chem_comp;
	if ( ! $json ) continue;


	//.. 手動で指定
	if ( ANNOT[ $id ] ) {
		$name_list = ANNOT[ $id ];
		_download_save( $id, ANNOT[ $id ] );
	//	_check_title( $id, ANNOT[ $id ] );
		continue;
	}

	//.. 名前取り出し大文字化
	$name_list = [];
	$name = ucwords( strtolower( $json->name ), ' -[]()\'' );

	$name_list[] = $name;

	//.. synnonyms
	foreach ( (array)explode( ';', $json->pdbx_synonyms ) as $s ) {
		$s = trim( $s );
		$name_list[] = $s;
		$name_list[] = ucfirst( $s );
		$name_list[] = ucfirst( strtolower( $s ) );
	}

	//.. synnonyms コンマ区切り
	foreach ( (array)explode( ', ', $json->pdbx_synonyms ) as $s ) {
		$s = trim( $s );
		if ( strlen( $s ) < 4 ) continue;
		if (
//			_instr( '[', $s ) ||
//			_instr( ']', $s ) ||
//			_instr( '(', $s ) ||
//			_instr( ')', $s ) ||
			$s == 'hydrolyzed' ||
			$s == 'phosphorylated' ||
			_instr( ' form', $s )
		)
			continue;

		$name_list[] = ucfirst( $s );
		$name_list[] = ucfirst( strtolower( $s ) );
	}
	
	//.. "ion" 削除してみる
	if ( substr( $name, -4 ) == ' Ion' ) {
		$name_list[] = preg_replace(
			[ '/ Ion$/', '/ \([IiVv]+\)/' ] ,
			[ '', '' ],
			$name
		);
	}

	//.. alha- beta- gamma- D- L- を消してみる
	$name2 = trim( preg_replace(
		[ '/\b(D-|L-|Alpha-|Beta-|Gamma-|[0-9\',]+-)/' ] ,
		[ '' ],
		$name
	), ' ,-' );
	$name_list[] = $name2;
	$name_list[] = ucfirst( strtolower( $name2 ) );
	$name_list[] = strtr( ucfirst( strtolower( $name2 ) ), [ '-' => ' ' ] ) ;


	//.. まとめ
	$en = '';
	foreach ( _uniqfilt( $name_list ) as $w ) {
//		_m( $w );
		if ( _have_ng_term( $w ) ) {
			continue;
		}
		if ( strlen( $w ) < 3 ) continue;
		$en = _get_title_en( $w );
		if ( strlen( $en ) < 3 )
			$en = '';

		if ( $en ) {
			_m2( "$id -> $w: found" );
			break;
		} else {
			_m2( "$id -> $w: not found" );
		}
	}
	_download_save( $id, $en );
//	_check_title( $id, $en );
}
