データを削除

<?php


//. init
require_once( "commonlib.php" );
require_once( "omo-common.php" );

$filetypes = [
	'altmodel' ,
	'pdb_vq30' ,
	'pdb_vq50' ,
	'prof_pdb' 
];


//.. json file
$fn_json = [
	'k'  => DN_OMODATA. '/datatime_k.json.gz',
	's'  => DN_OMODATA. '/datatime_s.json.gz',
	'ss' => DN_OMODATA. '/datatime_ss.json.gz'
];

$json = [];
foreach ( $fn_json as $type => $fn ) {
	$json[ $type ] = _json_load( $fn );
}

//.. ID指定
$delids = explode( "\n", "

"
);

//. main
$flg_json_rewrite = false;
foreach ( $delids as $id ) {
	$id = trim( $id );
	if ( $id == '' ) continue;
	if ( _instr( '.php', $id ) ) continue;
	_line( "$id: 削除する" );

	//.. file 
	foreach ( $filetypes as $ft ) {
		$fn = _fn( $ft, $id );
		if ( file_exists( $fn ) ) {
			_del( $fn );
			_m( "$fn: 削除完了" );
//			_m( "$fn: 削除するよ" );
		} else {
			_m( "$fn: 無い", -1 );
		}
	}
	//.. json data
	foreach ( array_keys( $json ) as $type ) {
		if ( $json[ $type ][ $id ] ) {
			unset( $json[ $type ][ $id ] );
			$flg_json_rewrite = true;
			_m( "$id - JSON file $type 値削除" );
		} else {
			_m( "$id - JSON file $type 値が入っていない", -1 );
		}
	}
}

//. JSON 上書き
if ( $flg_json_rewrite ) {
	_m( "jsonファイル書き込み開始" );
	foreach ( $fn_json as $type => $fn ) {
		_json_save( $fn, $json[ $type ] );
	}
	_m( "jsonファイル書き込み完了" );
}

