ccp4head
Situsのmap2mapを使って、ccp4のヘッダを読み込む
input: mapファイルのみ
output: mapinfo.json
situsのエラーメッセージは必ず出る。!!!
"lib_vio> Error: Can't open file! /  [e.c. 71420]"

<?php

//. init
require_once( "commonlib.php" );

//. start main loop
foreach( _idlist( 'emdb' ) as $id ) {
	_count( 'emdb' );
	$fn_map  = _fn( 'map', $id );
	$fn_data = _fn( 'mapinfo', $id );
//	_pause( "$fn_map: " . ( file_exists( $fn_map ) ? 'o' : 'x' ) );
	if ( _same_time( $fn_map, $fn_data ) ) continue;
	$olddata = _json_load( $fn_data );

	//.. run map2map
	_m( $id, 1 );

	//- 出力読み込み
	foreach ( _map2map( $fn_map ) as $s ) {
		if ( preg_match_all( '/> +(.+?) = +(.+?)\(/', $s, $v ) > 0 )
			$data[ trim( $v[1][0] ) ] = trim( $v[2][0] );
	}
	
	//- エラー？
	if ( count( $data ) < 1 ) {
		_problem( "$id: マップヘッダが読めない" );
		continue;
	}

	//- apix
	$data[ 'APIX X' ] = ( $data[ 'MX' ] != 0 ) 
		? $data[ 'X length' ] / $data[ 'MX' ]
		: 'ERROR'
	;
	$data[ 'APIX Y' ] = ( $data[ 'MY' ] != 0 ) 
		? $data[ 'Y length' ] / $data[ 'MY' ]
		: 'ERROR'
	;
	$data[ 'APIX Z' ] = ( $data[ 'MZ' ] != 0 )
		? $data[ 'Z length' ] / $data[ 'MZ' ]
		: 'ERROR'
	;

	//- 新旧の比較
	if ( $olddata ) {
		$c =[];
		foreach ( $data as $name => $value ) {
			$ov = $olddata[ $name ];
			if ( (string)$ov == (string)$value ) continue;
			$c[] = "$name: [$ov => $value]";
		}
		if ( $c != [] )
			_log( "$id: map header changed: " . _imp( $c ) );
	}
	
//.. end of loop
	_save_touch([
		'fn_in'  => $fn_map ,
		'fn_out' => $fn_data ,
		'data'   => $data , 
		'name'   => 'mapinfo'
	]);

} // end of main loop (foreach)

//. end
_end();
