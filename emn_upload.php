<?php
require_once( "commonlib.php" );

define( 'FLG_DRY_RUN',  $argv[1] != 'do' );
//. js compile
_php( 'jscomplie.php' );

//. subdata
_line( 'subdata保存' );

$sqlite = new cls_sqlw([
	'fn'		=> DN_EMNAVI. '/subdata.sqlite' ,
	'cols' => [
		'key UNIQUE COLLATE NOCASE' ,
		'data' ,
	] ,
	'indexcols' => [ 'key' ],
	'new'		=> true
]);

//.. js
foreach ( _json_load( DN_PREP. "/js.json.gz" ) as $name => $js ) {
	$sqlite->set([ "js|$name", $js ]);
}

//.. subdata
foreach ( _tsv_load3( '../emnavi/subdata.tsv' ) as $categ => $c1 ) {
	foreach ( $c1 as $name => $data ) {
		_set( [ $categ, $name ], $data );
	}
}

//.. e2j
foreach ( _tsv_load2( '../emnavi/e2j.tsv' ) as $name => $data ) {
	_set( [ 'e2j', $name ], $data );
}

//.. trep
foreach ( _load_trep_tsv() as $lang => $c1 ) {
	foreach ( $c1 as $name =>$data ) {
		_set( [ 'trep', $lang, $name ], $data );
	}
}

//.. end
$sqlite->end();

//.. function: set 
function _set( $key, $data ) {
	global $sqlite;
	$sqlite->set([
		implode( '|', $key ) ,
		json_encode( $data, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE )
	]);
}

//. sitemap
_line( 'emn web system upload' );

# rm -f ../emnavi/view_cache/*

_php( 'sub-make-sitemap' );

_del( '../emnavi/main.sqlite' ); //- もう必要ないか・・・
_del( '../emnavi/php_errors.log' );


//. rsync
_line( FLG_DRY_RUN ? 'テストモードで実行 (本番は"do"をオプションで)' : '本番アップロード' );

$dry_run = FLG_DRY_RUN ? '--dry-run' : ''; 
passthru( "rsync $dry_run -avz --copy-links --exclude-from=exclude_upload_emn.txt --delete -e ssh ../emnavi/ hirofumi@pdbjiw1-p:/home/web/html/emnavi/" );

if ( FLG_DRY_RUN )
	_m( 'アップロードしていません', 'red' );
