SASBDB の SQlite DBを作成
keywrod ファイルも作成

<?php
include "commonlib.php";
include "sas-common.php";


define( 'STRID2DBID', _json_load( FN_DBDATA_SASBDB ) );
define( 'SUBDATA'   , _json_load( FN_SUBDATA_PRE ) );

//. prep DB
$sqlite = new cls_sqlw([
	'fn' => 'sas', 
	'cols' => [
		'id UNIQUE COLLATE NOCASE' ,
		'info' ,
		'kw COLLATE NOCASE' , 
		'sauth COLLATE NOCASE'
	],
	'new' => true
]);

//. main
_count();
foreach ( _idloop( 'sas_json' ) as $pn ) {
	_count( 100 );
	$id = _fn2id( $pn );
	$json = _json_load2( $pn );
	$kw = [];

	//.. キーワード
	foreach ( $json as $c ) {
		foreach ( (array)$c as $c2 ) {
			foreach ( (array)$c2 as $c3 ) {
				if ( _instr( 'lines ---', $c3 ) ) continue;
				$kw[] = $c3;
			}
		}
	}

	//- met
	$met = [];
	foreach ( (array)array_keys( _json_load( _fn( 'sas_metjson', $id ) ) ) as $k ) {
		$met[] =  'm:' . $k;
	}

	$kw = array_unique( array_filter( array_merge(
		$kw,
		[$id],
		(array)SUBDATA[$id]['src'] ,
		(array)STRID2DBID[$id] ,
		$met
	)));

	file_put_contents( _fn( 'sas_kw', $id ), implode( "\n", $kw ) );

	//.. author
	$con_auth = [
		$json->pdbx_contact_author[0]->name_first ,
		$json->pdbx_contact_author[0]->name_last
	];
	$cite_auth = [];
	foreach ( (array)$json->citation_author as $c )
		$cite_auth[] = $c->name;

	//.. 書き込み
	$sqlite->set([
		$id ,

		//- info (タイトルと著者)
		json_encode( [
			'title' => $id2title[ $id ] ,
			'auth' => count( $cite_auth ) == 0 ? $con_auth : $cite_auth
		], JSON_UNESCAPED_SLASHES ) ,
		
		//- キーワード
		implode( '|', $kw  ) ,

		//- author検索
		implode( ' | ', _uniqfilt( array_merge( $cite_auth, $con_auth ) ) )
	]);
}

//. DB終了
$sqlite->end();
_end();
