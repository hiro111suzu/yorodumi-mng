PDBMLnoatom => json

<?php
require_once( "v5-common.php" );


//. 変換文字列
$rep_pre = _rep_prep( <<<'EOF'

>[\n\r ]+<			|	><			//- 空白改行だけの要素
[\n\r\t]+			|	' '			//- 改行などを空白に
  +					|	' '			//- 複数連続の空白は1個にする
<\?.+?\?>			|				//- xml宣言消す
auth_validate="N"	|				//- 属性と要素の重複原因、mmcifにないのでいらない
update_id="[0-9]+"	|				//- 属性と要素の重複原因、mmcifにないのでいらない

(<\/?)PDBx:			|	\1			//- PDBx: を消す
<\/?[^>]+Category>	|				//- fooCategory => 消去
}					|	_|_			//- }を変えておく

">([^<]+?)<			|	"><_v>$1</_v><	//- 属性がある要素の<_v>タグ付け

EOF
);

$rep_post = _rep_prep( <<<'EOF'

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
EOF
);

/*
"__dummy__",?			| 				//- categor消し
*/
//. schema
//- 複数ありうるタグは、複数個にして、必ず配列になるようにする
_line( 'スキーマ解析' );
/*
スキーマファイルのダウンロードアドレス
http://pdbml.pdb.org/schema/pdbml-downloads.html
*/

//$schemafn = DN_FDATA . '/PDBMLplus_v40.xsd';
$schemafn = DN_FDATA . '/pdbx-v50.xsd';

$tags = [];
foreach ( _file( $schemafn ) as $line ) {
	if ( ! _instr( 'maxOccurs="unbounded"', $line ) ) continue;
	$tags[] = preg_replace(
		[ '/^.+name="/'	, '/".+$/' ] ,
		[ ''			, ''	   ] ,
		$line
	);
}

$array_tag = [];
foreach ( array_unique( $tags ) as $t ) {
	if ( $t == '' ) continue;
	$array_tag[ "<$t>" ] = "<$t>__dummy__</$t><$t>";
	$array_tag[ "<$t " ] = "<$t>__dummy__</$t><$t ";
}
//file_put_contents( 'ml.txt', implode( "\n", $tags ) );

//_die( $array_tag );
_m( '強制的に配列にするタグの数: ' . count( $tags ) );

//. 実行
_line( '変換開始' );
$cnt = 0;
_count();
$badids = [];
foreach ( _idloop( 'v5_xml' ) as $infn ) {
	if ( _count( 5000, 0 ) ) break;
	$id = _fn2id( $infn );
	$outfn = _fn( 'v5_json', $id );

	if ( _sametime( $infn, $outfn ) ) continue;

	//- entry_id="$id" などを追加
	$in = array_merge(
		[ "/entry_id=\"$id\"/i", "/pdbx_PDB_id_code=\"$id\"/i" ] ,
		$rep_pre[ 'in'  ]
	);

	$out = array_merge(
		[ '', '' ] ,
		$rep_pre[ 'out' ]
	);

	//- プレ処理
	$cont = preg_replace(
		$in ,
		$out ,
		_gzload( $infn )
	);

//	file_put_contents( "$outfn.xml", $cont );
	//- 複数の値を持ち得るタグ対策
	$cont = strtr( $cont, $array_tag );

	//- 変改 ポスト修正
	$cont = preg_replace(
		$rep_post[ 'in'  ] ,
		$rep_post[ 'out' ] ,
		json_encode( simplexml_load_string( $cont ), JSON_UNESCAPED_SLASHES )
	);

	//- 変換、書き込み
	_del( $outfn );
	_gzsave( $outfn , strtr( $cont, [ '__id__' => $id ] ) );

	if ( ! file_exists( $outfn ) ) {
		//- 失敗
		_problem( "[PDB-$id] v5 PDBjson 作成失敗" );
		$badids[] = $id;
	} else {
		if ( filesize( $outfn ) < 10 ) {
			_problem( "$id: ファイルが小さすぎ", -1 );
			_del( $outfn );
		} else {
			//- 成功
			touch( $outfn, filemtime( $infn ) );
		}
	}
	++ $cnt;
}

//.. 集計
_line( '集計' );
_m( "データ変換数: " . $cnt );
if ( count( $cnt ) > 0 ) {
	_m( count( $badids ) != 0 
		? '失敗データ: ' . _imp( $badids )
		: '全データ 変換成功' 
	);
}

//. 無くなったデータを消す
_delobs_pdb( 'pdb_json' );

_end();

