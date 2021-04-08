<?php
/*
PDBML共通スクリプト
	+ PDBML
	+ PDBML plus
*/
//. init

define( 'FN_MUTLITAG_TXT', DN_PREP. '/multival_tags_' .DB_TYPE. '.txt' );

//.. スキーマ
/*
スキーマファイルのダウンロードアドレス
{http://pdbml.pdb.org/schema/pdbml-downloads.html
*/

if ( DB_TYPE != 'emdb' ) {
	if ( ! file_exists( FN_PDBML_SCHEMA ) )
		_problem( "schemaファイルがない - ". FN_PDBML_SCHEMA );
}

//..  変換文字列 pre pdb用
define( 'STR_REP_PRE_PDB', <<<'EOD'

>[\n\r ]+<			|	><			//- 空白改行だけの要素
[\n\r\t]+			|	' '			//- 改行などを空白に
  +					|	' '			//- 複数連続の空白は1個にする
<\?.+?\?>			|				//- xml宣言消す
auth_validate="N"	|				//- 属性と要素の重複原因、mmcifにないのでいらない
update_id="[0-9]+"	|				//- 属性と要素の重複原因、mmcifにないのでいらない

(<\/?)PDBx:			|	\1			//- PDBx: を消す
<\/?[^>]+Category>	|				//- fooCategory => 消去
<\/?[^>]+Category >	|				//- fooCategory => 消去
}					|	_|_			//- }を変えておく

">([^<]+?)<			|	"><_v>$1</_v><	//- 属性がある要素の<_v>タグ付け

EOD
);

//.. 変換文字列 post
define( 'STR_REP_POST', <<<'EOD'

"@attributes":{(.+?)}	|	$1			//- 属性を普通の値にする
_\|_					|	}			//- }をもとに戻す
^{						|	{"_id":"__id__",	//- 先頭に_idを付加

,"[a-zA-Z0-9_]+":{}		|				//- 空要素消し	空要素があるとエラーが出る
,"[a-zA-Z0-9_]+":{}		|				//- 空要素消し
,"[a-zA-Z0-9_]+":{}		|				//- 空要素消し
"[a-zA-Z0-9_]+":{},		|				//- 空要素消し
"[a-zA-Z0-9_]+":{},		|				//- 空要素消し
"[a-zA-Z0-9_]+":{},		|				//- 空要素消し
"__dummy__",?			|				//- ダミー要素消し

EOD
);

//. func
//.. _prep_multitag: 複数値タグ準備
//- 複数ありうるタグは、複数個にして、必ず配列になるようにする
function _prep_multitag( $tags = [] ) {
	if ( DB_TYPE != 'emdb' ) {
		copy( 'http://pdbml.pdb.org/schema/pdbx-v50.xsd', FN_PDBML_SCHEMA );
		_line( 'PDBMLスキーマ解析' );
		foreach ( _file( FN_PDBML_SCHEMA ) as $line ) {
			if ( ! _instr( 'maxOccurs="unbounded"', $line ) ) continue;
			$tags[] = preg_replace(
				[ '/^.+name="/'	, '/".+$/' ] ,
				[ ''			, ''	   ] ,
				$line
			);
		}
	}
	$tags = _uniqfilt( $tags );
	sort( $tags );
	_comp_save( FN_MUTLITAG_TXT, implode( "\n", $tags ) );
	_m( '強制的に配列にするタグの数: ' . count( $tags ) );


	//... 変換用配列作成 (strtr用)
	$a = [];
	foreach ( array_unique( $tags ) as $t ) {
		if ( $t == '' ) continue;
		$a[ "<$t>" ] = "<$t>__dummy__</$t><$t>";
		$a[ "<$t " ] = "<$t>__dummy__</$t><$t ";
	}
	define( 'REP_MULTITAG', $a );
}

//.. _rep_prep: preg_replaceで使うarrayを作る
//- \t|\tがデリミタ
//- スペースは意味があるので注意
//- inの'/'は省略可 
//- outの'はシングルクオートは消える
//- ヒアドキュメント(<<<EOD)じゃなくて、Nowdoc(<<<'EOD')
//- '//' 以降はコメント

function _rep_prep( $s ) {
	$rep = [ '\n' => "\n", '\r' => "\r", '\t' => "\t" ];
	$ret = [];
	foreach ( explode( "\n", $s ) as $line ) {
		//- '//'をコメントアウト
		$line = preg_replace( '/\/\/.+$/', '',
			trim( $line, "\n\r\t" ) 
		);
		list( $in, $out ) = explode( "\t|", $line );
		$in = trim( strtr( $in, $rep ), "\t" );
		if ( $in == '' ) continue;
		$ret[ 'in'  ][] = substr( $in, 0, 1 ) != '/' ? "/$in/" : $in;
		$ret[ 'out' ][] = strtr( trim( $out, "\t'" ), $rep );
	}
//	_m( json_encode( $ret, JSON_PRETTY_PRINT ) );
//	_pause();
	return $ret;
}

//.. _pre_conv
function _pre_conv( $cont, $add = [] ) {
	return preg_replace(
		array_merge(
			(array)$add[ 'in' ] ,
			REP_PRE[ 'in' ]
		),
		array_merge(
			(array)$add[ 'out' ] ,
			REP_PRE[ 'out' ]
		),
		$cont
	);
}

//.. _post_conv
function _post_conv( $a ) {
	extract( $a ); //- $cont, $id, $fn_in, $fn_out
	$msg = DB_TYPE . "-$id:";

	//... str => XML
	$x = simplexml_load_string( strtr( $cont, REP_MULTITAG ) );
	if ( $x === false ) {
		_problem( DB_TYPE . "$msg XMLパースできない" );
		_cnt( 'error: XML persing' );
		return;
	}

	//... XML => JSON => post replace
	$cont = preg_replace(
		REP_POST[ 'in'  ] ,
		REP_POST[ 'out' ] ,
		json_encode( $x, JSON_UNESCAPED_SLASHES )
	);

	//... post prep
	if ( function_exists( '_post_prep' ) ) {
		$cont = json_encode(
			_post_prep( json_decode( $cont ) ) ,
			JSON_UNESCAPED_SLASHES
		);
	}

	//... save
	_del( $fn_out );
	_gzsave( $fn_out , strtr( $cont, [ '__id__' => $id ] ) );

	//... check
	if ( ! file_exists( $fn_out ) ) {
		//- 失敗
		_problem( "$msg JSONファイル保存失敗" );
		_cnt( 'error: file save' );

	} else if ( filesize( $fn_out ) < 30 ) {
		//- 小さすぎ
		_problem( "$msg ファイルが小さすぎ" );
		_cnt( 'error: too small file' );
		_del( $fn_out );

	} else {
		//- 成功
		touch( $fn_out, filemtime( $fn_in ) );
		_log( "$msg XML->JSON" );
		_cnt( 'converted' );
	}
}

