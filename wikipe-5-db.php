<?php
//. db prep
require_once( "wikipe-common.php" );
$sqlite = new cls_sqlw([
	'fn' => 'wikipe' , 
	'cols' => [
		'key UNIQUE' ,
		'en_title COLLATE NOCASE' ,
		'en_abst COLLATE NOCASE' ,
		'ja_title COLLATE NOCASE' ,
		'ja_abst COLLATE NOCASE' ,
	],
	'new' => true ,
	'indexcols' => [ 'key' ] ,
]);

$sqlite_large = new cls_sqlw([
	'fn' => DN_SQLITE. '/wikipe/large.sqlite' , 
	'cols' => [
		'key UNIQUE' ,
		'en_title COLLATE NOCASE' ,
		'en_abst COLLATE NOCASE' ,
		'ja_title COLLATE NOCASE' ,
		'ja_abst COLLATE NOCASE' ,
	],
	'new' => true ,
	'indexcols' => [ 'key' ] ,
]);

//. wikipe-json
_line( 'データ' );
$upper = [];
$err_terms = [];
foreach ( glob( DN_WIKIPE. '/json/*.json.gz' ) as $fn ) {
	_count( 5000 );
	$key = $et = $ea = $jt = $ja = '';
	extract( _json_load( $fn ) );

	if ( ! $et && ! $jt ) {
		_del( $fn );
		_m( '中身なし: '. basename( $fn, '.json.gz' ) );
		continue;
	}

//	$key = $cat == 'm' ? strtolower( $key ) : $key;
	$sqlite_large->set([
		$et ,
		'@',
		$ea ,
		$jt ,
		$ja ,
	]);
	$sqlite->set([
		$et ,
		'@',
		_abst_rep( $ea ) ,
		$jt ,
		_abst_rep( $ja, true ) ,
	]);
	_cnt( 'total' );
	_cnt( $cat );
	
	//- 大文字キー
	$u = strtoupper( $et );
	if ( $u != $et )
		$upper[ $u ] = $et;
}

//. 変換 (regist json)
_line( '変換リスト' );
_count();
foreach ([
	'c' => 'chem', 
	't' => 'taxo', 
	'm' => 'misc' 

] as $symbol => $type ) {
	foreach ( _json_load( DN_WIKIPE. '/regist_'. $type .'.json.gz' ) as $k => $v ) {
	_count( 5000 );

		if ( $symbol != 'm' ) {
			if ( $v == '@' )
				$v = $k;
			$k = "$symbol:$k";
		} else {
			//- 大文字キー
			$u = strtoupper( $k );
			if ( $u != $et )
				$upper[ $u ] = $v;
		}
		if ( $v == '@' ) continue;

		$sqlite_large->set([
			$k ,
			$v ,
			'' ,
			'' ,
			'' ,
		]);
		$sqlite->set([
			$k ,
			$v ,
			'' ,
			'' ,
			'' ,
		]);
	}
}

//. 大文字キー
_line( '大文字キー' );
foreach ( $upper as $k => $v ) {
	$sqlite_large->set([
		$k ,
		$v ,
		'' ,
		'' ,
		'' ,
	]);
	$sqlite->set([
		$k ,
		$v ,
		'' ,
		'' ,
		'' ,
	]);
}

//. end
file_put_contents( DN_WIKIPE. '/load_error_terms.tsv', $sqlite->get_err_vals() );
file_put_contents( DN_WIKIPE. '/load_error_terms_large.tsv', $sqlite_large->get_err_vals() );

$sqlite->end();
$sqlite_large->end();
_cnt();

//_save( DN_WIKIPE. '/load_error_terms.txt', $err_terms );

//. func _abst_rep
function _abst_rep( $in, $lng_ja = false ) {
	$del = $lng_ja ? '。' : '. ';
	$lim = 
	$ret = _reg_rep( strtr( $in,
		[
			//- string
			'{\displaystyle \rightleftharpoons }' => '&hArr;' ,
			'{\displaystyle \longrightarrow }'	=> '&rarr;' ,
			'{\displaystyle {\ce {' => '' ,
			'}}}'		=> '' ,
			'<p><\/p>'	=> ' ' ,
			'<br>'		=> ' ' ,
			'()'		=> ' ' ,
		]
	) ,
		[
			//- reg rep
			'/<dl>.+?<\/dl>/' => '',
			'/ style=".+">/' => '>' ,
			'/ id=".+">/'	 => '' ,
			'/<\/?span.*?>/' => '' ,
			'/>[ \n\r\t]+</' => '> <' ,
			'/^<p>/'		 => '',
	//		'/<\/p>.*$/'	 => '',
			'/<ul>.+?<\/ul>/' => ' ... ',
			'/\(<.+?>\)/'	 => '',

			'/ +/'			 => ' ' ,
		]
	);
	if ( strlen( $ret ) > 300 ) {
		$a = explode( $del, $ret, 4 );
		$ret =implode( $del, array_slice( $a, 0, 2 ) ) . '...';
//		if ( strlen( $ret ) > 300 )
//			$ret = $a[0] . '...';
	}
	return $ret;
}
