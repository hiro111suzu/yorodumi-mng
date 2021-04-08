<?php
include "commonlib.php";
include "sas-common.php";

//. init
$ign_cat = [
	'sas_scan_intensity' => true ,
	'sas_p_of_R_extrapolated_intensity' => true ,
	'sas_p_of_R_extrapolated' => true ,
	'sas_p_of_R' => true ,
	'sas_model_fitting' => true ,
	'atom_site' => true
];

//. sascifファイル取得
foreach ( _idloop( 'sas_cif' ) as $fn_in ) {
	_count( 'sas' );
	$id = _fn2id( $fn_in );
	$fn_out = _fn( 'sas_json', $id );
	if ( _newer( $fn_out, $fn_in ) ) continue;

	$cif = file( $fn_in, FILE_IGNORE_NEW_LINES );
//	_m( $id );

	//.. 複数行の対応
	$indata = [];
	$multi_line = [];
	$mlt_id = 0;
	while (true) {
		//- 一行取り出し
		if ( count( $cif ) == 0 ) break;
		$line = trim( array_shift( $cif ) );

		//- 普通の行なら、$indataにそのまま追加
		if ( _fst( $line ) != ';' ) {
			$indata[] = $line;
			continue;
		}

		//- 複数行処理開始		
		++ $mlt_id;
		$name = "multi_line_@$mlt_id";

		//- 最後の行にマークを付け足す
		$indata[] = array_pop( $indata ) . " $name";

		//- 複数行、取得開始
		$mlt = [ substr( $line, 1 ) ]; //- 先頭の;を消す
		while (true) {
			if ( count( $cif ) == 0 ) break;
			$line = array_shift( $cif );

			if ( $line != ';' ) {
				$mlt[] = $line;
				continue;
			}

			//- 複数行おしまい
			//- 次の行は、まだ続きなので
			$line = array_shift( $cif );
			if ( $x != '' )
				$indata[] = array_pop( $indata ) .' '. $x;
			break;
		}
		$multi_line[ $name ] = implode( "\n", $mlt );
	}

	//.. 解析
	$json = [];
//	$block = 'xxx';
	$categ = 'xxx';

	$ign = false;
	$ign_cnt = 0;

	$loop = false;
	$key = [];
	$idx = 0;
	$loop_data = [];

	$kv = false;
	$kv_data = [];

	$kv_loop_data = [];
	$kv_loop_name = '';

	foreach ( $indata as $line ) {
		//- データブロックの区切り
		if ( stripos( $line, "data_$id" ) === 0 ) {
//			$block = strtr( $line, [ "data_{$id}_" => '' ] );
			$ign = false;
			continue;
		}

		//- ループ開始
		if ( $line == 'loop_' ) {
			$loop = true;
			if ( $kv ) {
				//- kvカテゴリの中のループ
				$kv_loop_data = [];
				$kv_loop_name = '';
			} else {
				$loop_data = [];
				$key = [];
				$idx = 0;
				$categ = '';
			}
			continue;
		}

		//- カテゴリ区切り、ループおしまい
		if ( $line == '#' ) {
			if ( $ign ) {
				//- jsonに含めないデータ
				if ( $ign_cnt > 0 )
					$json[ $categ ][] = "--- $ign_cnt lines ---";
				$ign = false;
				$ign_cnt = 0;
			}
			if ( $loop ) {
				//- ループデータ
				if ( count( $loop_data ) > 0 )
					$json[ $categ ] = $loop_data;
				$loop = false;
			}
			if ( $kv ) {
				//- key-valueデータ
				$json[ $categ ][] = $kv_data;
				$kv_data = [];
				$kv = false;
			}
			continue;
		}
		
		//- 無視するカテゴリの場合
		if ( $ign ) {
			++ $ign_cnt;
			continue;
		}

		//- データ取得
		if ( $loop and $kv ) {
			if ( _fst( $line ) == '_' ) {
				$a = explode( '.', trim( substr( $line, 1 ) ), 2 );
				$kv_loop_name = trim( $a[1] );
			} else {
				$kv_data[ $kv_loop_name ][] = trim( $line );
			}
		} else if ( $loop ) {
			//- キー取得
			if ( _fst( $line ) == '_' ) {
				$a = explode( '.', trim( substr( $line, 1 ) ), 2 );
				$categ = $a[0];
				if ( $ign_cat[ $categ ] ) {
					$ign = true;
					$loop = false;
				}
				$key[ $idx ] = trim( $a[1] );
				++ $idx;
				continue;
			}
			//- 値取得
			$d = [];
			foreach ( _split( $line ) as $num => $val ) {
				$val = _val( $val );
				if ( $val != '' )
					$d[ $key[ $num ] ] = $val;
			}
			$loop_data[] = $d;
			continue;

		} else {
			//- ループじゃないデータ
			if ( _fst( $line ) != '_' ) {
				if ( $line != '' )
					_m( "kvでもloopでもない [$line]" );
				continue;
			}
			$kv = true;
			$w = _split( $line, 2 );
			$a = explode( '.', substr( $w[0], 1 ), 2 );
			$categ = $a[0];
			$val = _val( $w[1] );
			if ( $val != '' )
				$kv_data[ $a[1] ] = $val;
		}
	}

	_comp_save( $fn_out, $json );
//	file_put_contents( $fn_out, json_encode( $json, JSON_PRETTY_PRINT ) );
//	_pause();
	
}

//. end
_delobs_misc( 'sas_json', 'sasbdb' );
_end();


//. function
//.. _fst: 最初の文字
function _fst( $s ) {
	return substr( $s, 0, 1 );
}

//.. _split: ダブルクオート対応のsplit
function _split( $line, $limit = -1 ) {
//	global $multi_line;
	$word = [];
	$in_quote = false;

	foreach ( explode( '"', $line ) as $w ) {
		if ( $in_quote ) //- クオートの中?
			$word[] = trim( $w );
		else
			$word = array_merge( $word, preg_split( '/ +/', $w, $limit ) );
		$in_quote = ! $in_quote;
	}
//	$ret = [];
//	foreach ( $word as $w )
//		$ret[] = $multi_line[ $w ] ?: $w ;
	return array_values( array_filter( $word ) );
}

//.. _val: 入力値のフィルタリング
function _val( $val ) {
	global $multi_line;
	$val = trim( $val );
	if ( in_array( $val, [ 'na', 'NA', 'n/a', 'N/A', '?', '.' ] ) )
		return;
	return $multi_line[ $val ] ?: $val;
}
