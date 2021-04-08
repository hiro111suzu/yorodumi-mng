<?php
//echo( realpath( '../emnavi/common-web.php' ) );
//. json作成
require_once( "commonlib.php" );
$fn_json = DN_PREP. '/doc.json';
$fn_db = DN_EMNAVI. '/doc.sqlite';

_php( '../emnavi/_doc_prep.php', $fn_json ) ;

if( _newer( $fn_db, $fn_json ) ) {
	die( "\njson 更新していない、DB作成省略" );
}

//. sqlite準備
$sqlite = new cls_sqlw([
	'fn'		=> $fn_db ,
	'cols' => [
		'id UNIQUE COLLATE NOCASE' ,
		'num INTEGER' , 
		'kw' ,
		'type' ,
		'tag' ,
		'json'
	] ,
	'indexcols' => [ 'id' ],
	'new'		=> true
]);

//. 書き込み
$num = 0;
foreach ( _json_load( $fn_json ) as $id => $c ) {
	//- キーワード
	$kw = [];
	foreach ( [ 'e', 'j' ] as $l ) {
		foreach ( (array)$c[ $l ] as $s ) {
			foreach ( preg_split( '/(<br>|<p>|<li>|<td>|<tr>)/', $s ) as $s2 ) {
				$kw[] = strip_tags( $s2 );
			}
		}
	}
	$sqlite->set([
		$id,
		$num ,
		implode( '|', array_unique( array_filter( $kw ) ) ) , //- キーワード
		$c[ 'type' ] ,
		'|' . implode( '|', (array)$c[ 'tag' ] ) . '|' , //- タグ
		json_encode( $c, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE )
	]);
	++ $num;
}
$sqlite->end();

