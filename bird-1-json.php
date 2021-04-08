<?php
require_once( "bird-common.php" );

//define( 'CHEMID2PDBID', _json_load( DN_PREP . '/ccid2pdbid.json' ) );

define( 'NG_CATEG', [
	'citation_author' ,
	'bond' ,
	'atom' ,
	'pdbx_reference_entity_poly_link'
]);

define( 'SP_MARK', '__<sp>__' );

//. family

$tsv = '';
foreach ( _idloop( 'fam_cif' ) as $fn_cif ) {
	_count(100);
	$json_orig = _cif2json( $fn_cif );
	if ( ! $json_orig ) _die( 'cannot convert: '. $fn_cif );
	$fam_id = basename( $fn_cif, '.cif' );

	$ids = [];
	foreach ( $json_orig[ 'pdbx_reference_molecule_list' ] as $c ) {
		$ids[] = strtr( $c[ 'prd_id' ], [ 'PRD_' => '' ] );
	}
	$tsv .= "$fam_id\t". _imp( $ids ). "\n";

//	_m( $fam_id );
	foreach ( $ids as $id ) {
		$fn_out = _fn( 'fam_json', $id );
		if ( FLG_REDO ) _del( $fn_out );
		if ( _newer( $fn_out, $fn_cif ) ) continue;
		$json_split = [];
		foreach ( $json_orig as $categ_name => $categ ) {
			if ( ! $categ[0][ 'prd_id' ] 
				|| $categ_name == 'pdbx_reference_molecule_list'
			){
				//- 全メンバーに持たせる共通部
				$json_split[ $categ_name ] = $categ;

			} else foreach ( $categ as $item_num => $item ) {
				//- 各メンバーに関係あることのみ
				if ( $item[ 'prd_id' ] != "PRD_$id" ) continue;
				$json_split[ $categ_name ][] = $item;

			}
		}
		_comp_save( $fn_out, $json_split, 'nomsg' );
	}
}
_comp_save( DN_PREP. '/prd/prd_fam.tsv', $tsv );
//die();

//. cif & cifcc
_count();
$ids = [];
foreach ( _idloop( 'prd_cif' ) as $fn )
	$ids[ _fn2id( $fn ) ] = true;
foreach ( _idloop( 'prd_cifcc' ) as $fn )
	$ids[ _fn2id( $fn ) ] = true;

_line( 'まとめjson' );
foreach ( array_keys( $ids ) as $id ) {
	_count( 100 );

	$fn_json  = _fn( 'bird_json', $id );
	$fn_cif   = _fn( 'prd_cif'  , $id );
	$fn_cifcc = _fn( 'prd_cifcc', $id );
	$fn_fam   = _fn( 'fam_json' , $id );

	if ( FLG_REDO ) _del( $fn_json );
	if (
		_newer( $fn_json, $fn_cif   ) &&
		_newer( $fn_json, $fn_cifcc ) &&
		_newer( $fn_json, $fn_fam   ) 
	) continue;

	$json = array_merge(
		[ '_id' => $id ] , 
		_cif2json( $fn_cif ) ,
		_cif2json( $fn_cifcc ) ,
		(array)_json_load( $fn_fam )
	);

	ksort( $json );
	_comp_save( $fn_json, $json, 'nomsg' );
//	break;
}

//. info file
_count();
$info = [];
$chem = [];
foreach ( _idloop( 'bird_json', 'info' ) as $fn ) {
	$id = _fn2id( $fn );
	$json = _json_load2( $fn )->pdbx_reference_molecule[0];

	$info[ "PRD_$id" ] = [ _imp( $json->class,  $json->type ), $json->name ];
	if ( $json->chem_comp_id )
		$chem[ $json->chem_comp_id ] = "PRD_$id";
}
_comp_save( DN_PREP. '/prd/prd_info.json.gz', $info );
_comp_save( DN_PREP. '/prd/chemid2prdid.json.gz', $chem );


//. function
//.. _cif2json
function _cif2json( $fn_cif ) {
		//.. カテゴリごとループ
	if ( ! file_exists( $fn_cif ) ) return [];
	$json = [];
	$id_fn = basename( $fn_cif, '.cif' );

	//- ";"で始まる行対策
	$even = true;
	$file = [];
	foreach ( preg_split( "/\n;/", _gzload( $fn_cif ) ) as $str ) {
		$file[]= $even 
			? ( substr( $str, 0, 1 ) == ' ' ? trim( $str ) : "\n". trim( $str ) )
			: trim( strtr( $str, [ "\n" => SP_MARK, ' ' => SP_MARK ]) )
		;
		$even = ! $even;
	}
	$file = preg_replace( '/[ \t]+/', ' ', implode( ' ', $file ) );
//	if ( $id_fn == 'PRDCC_000156' )
//		_pause( $file );

	foreach ( preg_split( "/\n# ?\n/", $file ) as $str ) { //- カテゴリごとループ
		$str = trim( $str );

		$lines = array_values( array_filter( explode( "\n", $str ) ) );
		if ( $lines[0] == 'data_'. basename( $fn_cif, '.cif' ) ) {
			continue; //- 一行目無視
		}

		if ( trim( $lines[0] ) != "loop_" ) {
			//... key-value
			$lines2 = [];
			$num = 0;
			foreach ( $lines as $line ) {
				if ( substr( $line, 0, 1 ) == '_' ) {
					++ $num;
					$lines2[ $num ] = $line;
				} else {
					$lines2[ $num ] .= ' '. trim( $line );
				}
			}

			foreach ( $lines2 as $line ) {
				$categ = $key = $val = '';
				extract( _prep_line( $line ) );
				$v = _val_rep( $val );
				if ( $v == '' ) continue;
				$json[ $categ ][0][ $key ] = $v;
			}
		} else {
			//... loop (table)
			//- get keys
			$keys = [];
			foreach ( $lines as $line ) {
				if ( substr( $line, 0, 1 ) != '_' ) continue;
				$categ = $key = $val = '';
				extract( _prep_line( $line ) );
				if ( in_array( $categ, NG_CATEG ) ) {
					$lines = [];
					break;
				}
				$keys[] = $key;
			}
//			if ( $categ == 'pdbx_reference_molecule_features' )
//				_pause( $keys );
			//- values, 1次元配列にする
			$val_array = [];
			foreach ( $lines as $line ) {
				$line = trim( $line );
				if (
					substr( $line, 0, 1 ) == '_' ||
					trim( $line ) == 'loop_' ||
					$line == '#' //- 最終行
				) continue;

				//- ダブルクオート、シングルクオート判断
				$first = substr( $line, 0, 1 );
				$qtype = '';
				$test_sq = explode( " '", $line, 2 );
				$test_dq = explode( ' "', $line, 2 );
				if ( $test_sq[1] || $test_dq[1] || $first == '"' || $first == "'" ) {
					//- クオートあり
					$qmark = $first == "'" || strlen( $test_sq[0] ) < strlen( $test_dq[0] )
						? "'" : '"' ;
					$line2 = '';
					$even = true;
					foreach ( explode( $qmark, $line ) as $s ) {
						$line2 .= $even ? $s : strtr( $s, [ ' ' => SP_MARK ] );
						$even = ! $even;
					} 
					$line = $line2;
				}
				$val_array = array_merge( $val_array, preg_split( '/ +/', $line ) );
			}

			//- データ
			while ( $val_array ) {
				$out = [];
				foreach ( $keys as $key )
					$out[ $key ] = _val_rep( array_shift( $val_array ) );
				$json[ $categ ][] = array_filter( $out );
			}
		}
	}
	return $json;
}

//.. _prep_line
function _prep_line( $str ) {
//	_m( "$str\n => " );
	list( $left, $val ) = preg_split( '/ +/', trim( $str ), 2 );
	list( $categ, $key ) = explode( '.', $left, 2 );
	$categ = trim( strtr( $categ, [ '_chem_comp' => '' ] ), '_' ) ?: 'chem_comp';
//	_pause( compact( 'categ', 'key', 'val' ) );
	return compact( 'categ', 'key', 'val' );
}

//.. _val
function _val_rep( $val ) {
	$val = trim( strtr( $val, [ SP_MARK => ' ' ] ), ' "\'' );
	if ( $val != '?' ) return $val;
}

