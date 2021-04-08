<?php
require_once( "commonlib.php" );
//. init
$sql = new cls_sqlite( 'wikipe' );

define( 'URL_BASE', 'https://en.wikipedia.org/w/api.php?format=json&action=query&list=search&srsearch=' );
define( 'FN_FOUND', DN_PREP. '/chem/wikipe_found.tsv' );
define( 'FN_NOT'  , DN_PREP. '/chem/wikipe_not_found.tsv' );
touch( FN_FOUND );
touch( FN_NOT );

//. annotation
$out = [];
$num = 0;
$ids_done = array_merge(
	(array)_tsv_load( FN_NOT ) ,
	(array)_tsv_load( FN_FOUND )
);

foreach ( _file( DN_DATA. '/ids/chem.txt' ) as $id ) {
	if ( $ids_done[ $id ] ) continue;
	++ $num;
//	if ( 100 < $num ) break;

	if ( file_exists( DN_PROC . '/stop' ) ) {
		_m( 'stopファイルがある、停止' );
		break;
	}


//	if ( $id != 'JGD') continue;


	//- wikipe もうあったらやらない
	if ( _ezsqlite([
		'dbname'	=> 'wikipe' ,
		'where'		=> [ 'key', "c:$id" ] ,
		'select'	=> 'en_title'
	])) continue;

	$inchikey = _ezsqlite([
		'dbname'	=> 'chem' ,
		'where'		=> [ 'id', $id ] ,
		'select'	=> 'inchikey'
	]);
	if ( ! $inchikey ) continue;

	//- 検索
	$en_title = json_decode( file_get_contents( URL_BASE. $inchikey ) )
		->query->search[0]->title;

	if ( $en_title ) {
		_m( "$id: Found [$en_title]", 1 );
		file_put_contents( FN_FOUND, "$id\t$en_title\n", FILE_APPEND );
	} else {
		_m( "$id: Not found" );
		file_put_contents( FN_NOT, "$id\t". time(). "\n", FILE_APPEND );
	}
	
	usleep( 300000 ); //- 0.3秒
	
}

