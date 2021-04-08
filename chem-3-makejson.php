cifからjson

<?php
require_once( "commonlib.php" );

//define( 'CHEMID2PDBID', _json_load( DN_PREP . '/ccid2pdbid.json' ) );

//. main
_count();
foreach ( _idloop( 'chem_cif' ) as $fn_cif ) {
	_count( 'chem' );
	$id = _fn2id( $fn_cif );
	$fn_json = _fn( 'chem_json', $id );
	if ( _newer( $fn_json, $fn_cif ) ) continue;

	$json = [
		'_id' => $id ,
		'datablockName' => $id
	];

//	if ( count( $i = CHEMID2PDBID[ $id ] ) > 0 )
//		$json[ 'pdb_entries' ] = $i;

//	_m( "$id: " . json_encode( $i ) );
//	continue;

	//.. カテゴリごとループ
	foreach ( preg_split( "/\n#[# ]*\n/", _gzload( $fn_cif ) ) as $str ) {
		//- ";"で始まる行対策 ダブルクオートで処理
		$str = preg_replace(
			[ "/\n;(.+)\n/", "/\n;\n/", "/\n\"/" ] ,
			[ "\"$1\n"     , "\"\n"   , "\"" ] ,
			$str
		);
		$lines = explode( "\n", $str );

		if ( $lines[0] == "data_$id" ) continue;

		if ( trim( $lines[0] ) != "loop_" ) {
			//... key-value
			foreach ( $lines as $line ) {
				_prep_line( $line );
				$v = _val( $val );
				if ( $v == '' ) continue;
				$json[ $categ ][ $key ] = $v;
			}
		} else {
			//... loop
			//- get keys
			$keys = [];
			foreach ( $lines as $line ) {
				if ( substr( $line, 0, 1 ) != '_' ) continue;
				_prep_line( $line );
				$keys[] = $key;
			}
			//- values
			foreach ( $lines as $line ) {
				if ( substr( $line, 0, 1 ) == '_' || trim( $line ) == 'loop_' ) continue;

				//- 行の分割（ダブルクオート対応）
				$vals = [];
				$i = 0;
				$inq = false;
				foreach ( preg_split( '/ +/', $line ) as $term ) {
					if ( $inq ) { //- ダブルクオート中
						$vals[ $i ] .= " $term";
						if ( substr( $term, -1 ) == '"' ) { //- ダブルクオート終わり
							$inq = false;
							++ $i;
						}
					} else {
						$vals[ $i ] = $term;
						if ( substr( $term, 0, 1 ) == '"' and substr( $term, -1 ) != '"' ) {
							$inq = true;
						} else {
							++ $i;
						}
					}
				}

				//- 行データ
				$arval = [];
				foreach ( range( 0, count( $keys ) - 1 ) as $i ) {
					$v = _val( $vals[ $i ] );
					if ( $v == '' ) continue;
					$arval[ $keys[ $i] ] = $v;
				}
				if ( count( $arval ) > 0 )
					$json[ $categ ][] = $arval;
			}
		}
	}

	ksort( $json );
	_comp_save( $fn_json, $json );
}

//. function
function _prep_line( $str ) {
	global $categ, $key, $val;
	list( $left, $val ) = preg_split( '/ +/', trim( $str ), 2 );
	list( $categ, $key ) = explode( '.', $left, 2 );
	$categ = trim( strtr( $categ, [ '_chem_comp' => '' ] ), '_' ) ?: 'chem_comp';
}

function _val( $val ) {
	$v = trim( $val, ' "' );
	if ( $v != '?' ) return $v;
}

