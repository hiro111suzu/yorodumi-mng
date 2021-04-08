アップデートをチェック
==========

<?php
require_once( "commonlib.php" );

//. 水曜日？


$youbi = date( 'w' );
if ( $youbi != 3 ) {
	die( "$youbi: 水曜じゃない" );
}

_m( "youbi: $youbi / ji: $ji" );


//. DB: キャッシュ
_m( file_get_contents( 'http://pdbj.org/emnavi/mng-dbclear.php' ) );

//. アップデートされているかチェック

$js = file_get_contents( "http://pdbj.org/emnavi/data/emn/newdata.json" );
_m( $js );

$jl = file_get_contents( DN_DATA . '/emn/newdata.json' );
_m( $jl );

if ( $js != $jl ) {
	_m( 'アップデートされていない' );
	_mail( 'update failed', 'アップデートできていない' );
	_php( 'mng', 'final' );
/*
	chdir( __DIR__ );
	_rsync( [
		'from'	=> DN_DATA . '/' ,
		'to'	=> 'pdbjkf1:/var/PDBj/ftp/pdbj/emnavi/data/' ,
		'opt'	=> '--exclude-from=exclude_upload_data.txt'
	]);
	_rsync( [
		'from'	=> DN_DATA . '/' ,
		'to'	=> 'pdbjkf1:/var/PDBj/ftp/pdbj-pre/emnavi/data/' ,
		'opt'	=> '--exclude-from=exclude_upload_data.txt'
	]);
	_mail( 'rsync', 'rsync完了' );
*/
} else {
	_m( 'アップデートされている' );
	_mail( ' update OK', 'アップデート成功している' );
}
