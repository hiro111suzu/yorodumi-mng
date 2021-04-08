<?php
require_once( "commonlib.php" );
/*
key	taxo(sci_name) chem(chemid) other(title)

date
en [ key, abst ] ,
ja [ key, abst ]
*/
//. define

define( 'QUOTE_MARK'     , '__<<q>>__' );
define( 'TIME_OLD', time() - 60*60*24*100 ); //- 100日
define( 'SQ', "'" );

define( 'MAXNUM_OLD', _nn( $argv[1], 100 ) );
_m( '古いファイルを再ダウンロードする数: '. MAXNUM_OLD );
define( 'OLDER_THAN_30_DAYS', time() - 30 * 24 * 60 * 60  );
$cnt_old = 0;


//.. url
define( 'URL_JA_STUB',
	'https://dumps.wikimedia.org/jawiki/latest/jawiki-latest-stub-articles.xml.gz'
);
define( 'URL_EN_STUB',
	'https://dumps.wikimedia.org/enwiki/latest/enwiki-latest-stub-articles.xml.gz'
);

define( 'URL_LANGLINKS',
	'https://dumps.wikimedia.org/jawiki/latest/jawiki-latest-langlinks.sql.gz'
);
//.. fdata
define( 'DN_F_WIKIPE'    , DN_FDATA. '/wikipe' );
define( 'FN_LANGLINKS'   , DN_F_WIKIPE. '/jawiki-latest-langlinks.sql.gz' );
//define( 'FN_REDIRECT'    , DN_F_WIKIPE. '/enwiki-latest-redirect.sql.gz' );

define( 'FN_JA_STUB_XML' , DN_F_WIKIPE. '/jawiki-latest-stub-articles.xml.gz' );
define( 'FN_EN_STUB_XML' , DN_F_WIKIPE. '/enwiki-latest-stub-articles.xml.gz' );




//.. prep

define( 'DN_WIKIPE', DN_PREP . '/wikipe' );
_mkdir( DN_WIKIPE );
define( 'DN_JSON', DN_WIKIPE. '/json' );
_mkdir( DN_JSON );


define( 'DN_CHEM_JSON', DN_WIKIPE . '/chem' );
_mkdir( DN_CHEM_JSON );
define( 'DN_TAXO_JSON', DN_WIKIPE . '/taxo' );
_mkdir( DN_TAXO_JSON );
define( 'DN_MISC_JSON', DN_WIKIPE . '/misc' );
_mkdir( DN_MISC_JSON );

define( 'TSV_ANNOT', _tsv_load2( DN_WIKIPE . '/annot.tsv' ) );

$_filenames += [
	'wikipe_chem' => DN_CHEM_JSON . '/<id>.json.gz' ,
	'wikipe_taxo' => DN_TAXO_JSON . '/<id>.json.gz' ,
	'wikipe_misc' => DN_MISC_JSON . '/<id>.json.gz'
];

define( 'FN_CHEM_LIST', DN_WIKIPE . '/chem_list.json.gz' );
define( 'FN_TAXO_LIST', DN_WIKIPE . '/taxo_list.json.gz' );
define( 'FN_MISC_LIST', DN_WIKIPE . '/misc_list.json.gz' );
define( 'FN_NG_LIST'  , DN_WIKIPE . '/ng_list.json.gz' );


//.. sqlite
//define( 'FN_DB_ID2EN'    , DN_WIKIPE. '/id2en.sqlite' );
//define( 'FN_DB_ID_RDCT'   , DN_WIKIPE. '/i2_rdct.sqlite' );
define( 'DN_DB', '/ssd/sqlite/wikipe' );
_mkdir( DN_DB );
define( 'FN_DB_ID2EN'    , DN_DB. '/id2en.sqlite' );
define( 'FN_DB_E2J'      , DN_DB. '/e2j.sqlite' );

define( 'FN_DB_EN_TITLE' , DN_DB. '/en_title.sqlite' );
define( 'FN_DB_EN_TITLE_NC' , DN_DB. '/en_title_nc.sqlite' );
//define( 'FN_DB_E2J'      , DN_DB. '/e2j.sqlite' );


//$_ng_list =[];

//. func
//.. _cpt キャピタライズ
function _cpt( $s ) {
	return ucfirst( strtolower( $s ) );
}

//.. _have_ng_term
function _have_ng_term( $term ) {
	foreach ( NG_TERMS as $ng_term ) {
		if ( _instr( $ng_term, $term ) ) return true;
//		_m( "$ng_term 含まれない $term" );
	}
}

//.. _define_annot
function _define_annot( $type ) {
	define( 'ANNOT', array_merge( TSV_ANNOT[ $type ], (array)TSV_ANNOT[ 'all' ]  ) );
}

//.. _regist
function _regist( $key, $name_list ) {
	$list = [];
	foreach ( _uniqfilt( $name_list ) as $n ) {
		if ( strlen( $n ) < 3 ) continue;
		if ( _have_ng_term( $n ) ) continue;
		$list[] = $n;
	}
	foreach ( $list as $n ) {
		if ( _regist_term( $key, $n ) ) {
			_cnt( 'hit case' );
			return;
		}
	}
	foreach ( $list as $n ) {
		if ( _regist_term( $key, $n, true ) ) {
			_cnt( 'hit no case' );
			return;
		}
	}
}

//.. _regist_term
$regist = [];
function _regist_term( $key, $term, $flg_nc = false ) {
	global $regist;
	if ( !$key || !$term ) return;
	if ( _have_ng_term( $term ) ) return;
	
	$en = _title_en( $term, $flg_nc );
	if ( TSV_ANNOT['ng_title'][strtolower( $en )] )
//		$en = '(^_^)';
		$en = '';

	if ( !$en ) return;
	$en = $key == $en ? '@' : $en;

	//- conflict?
	if ( $regist[ $key ] && strtolower( $regist[ $key ] ) != strtolower( $en ) ) {
		_problem( "conflict $key => ". $regist[ $key ]. " vs. $en" );
		_cnt( 'conflict' );
		return;
	}
	$regist[ $key ] = $en;
//	if ( _instr( 'vitamin d', $en ) )
//		_pause( "$key => $en" );
	return true;
}

//.. _regist_annot_term
function _regist_annot_term( $key, $term ) {
	if ( $term == 'x' ) return;
	if ( ! _regist_term( $key, $term ) )
		_problem( "マニュアル指定したtermが存在しない - $key -> $term " );
}

//.. _regist_save
function _regist_save() {
	global $regist;
	_cnt();
	$fn_regist = DN_WIKIPE. '/regist_'. TYPE .'.json.gz';
	$old = _json_load( $fn_regist );

	foreach ( (array)$old as $key => $val ) {
		if ( ! $regist[ $key ] )
			_cnt( 'lost' );
		if ( $regist[ $key ] != $val )
			_cnt( 'changed' );
	}
	foreach ( $regist as  $key => $val ) {
		_cnt( 'total' );
		if ( ! $old[ $key ] )
			_cnt( 'new' );
	}
	_comp_save( $fn_regist, $regist );
	_cnt();
}

//.. _get_from_db
$obj_sqldb = [];
function _get_from_db( $mode, $term ) {
	global $obj_sqldb;

	$t = _quote( $term );
	$a = [
		'en_title' => [
			'fn_db' => FN_DB_EN_TITLE ,
			'select' => 'rd_to' ,
			'where' => "title=$t" ,
		],
		'en_title_nc' => [
			'fn_db' => FN_DB_EN_TITLE_NC ,
			'select' => 'rd_to' ,
			'where' => "title=$t" ,
		],
		'en_title_nc_self' => [
			'fn_db' => FN_DB_EN_TITLE_NC ,
			'select' => 'title' ,
			'where' => "title=$t" ,
		],
		'e2j' => [
			'fn_db' => FN_DB_E2J ,
			'select' => 'ja', 
			'where' => "en=$t",
		],

	][ $mode ];
	extract( $a );
	if ( ! $obj_sqldb[ $fn_db ] ) {
		$obj_sqldb[ $fn_db ] = new cls_sqlite( $fn_db );
	}
	return $obj_sqldb[ $fn_db ]->qcol([
		'select' => $select ,
		'where' => $where
	])[0];
}

//.. _title_en
$term_cache = [];
function _title_en( $term, $flg_nc = false ) {
	global $term_cache;
/*
	if ( $term_cache[ $term ] == 'none' ) return false;
	if ( $term_cache[ $term ] )
		return $term_cache[ $term ];
*/
	$ret = false;

	$en = _get_from_db( $flg_nc ? 'en_title_nc' : 'en_title' , $term );
//	if ( $term == 'vitamin d' )
//		_m( "$term => $en " . ($flg_nc ? 'nc' : 'c') );
	$ret = $en == '@' ? (
		$flg_nc 
			? _get_from_db( 'en_title_nc_self', $term )
			: $term 
	): $en;
//	if ( $term == 'vitamin d' )
//		_pause( "$term ==> $ret " . ($flg_nc ? 'nc' : 'c') );


	if ( strlen( $ret ) < 3 )
		$ret = '';
//	if ( _instr( 'yoda', strtolower( $en ) ) ) _pause( "$en ->" );
//	if ( _instr( 'yoda', strtolower( $term ) ) ) _pause( "$term ->" );
	if ( TSV_ANNOT['ng_title'][ $ret ] )
		$ret = '';
//	if ( _instr( 'yoda', strtolower( $en ) ) )_pause( "-> $en" );
//	if ( _instr( 'yoda', strtolower( $term ) ) ) _pause( "$term -> $en" );
//	if ( _instr( 'yoda', strtolower( $en ) ) )_pause( "$term -> $en" );
//	_pause( "hoge: $en" );
//	$term_cache[ $term ] = $ret ? $ret : 'none';
	return $ret;
}

//.. _fn_wkp_json
function _fn_wkp_json( $et ) {
	return DN_JSON. '/'. urlencode( $et ) . '.json.gz';
}
//. legacy

//.. _fn_wikipe_json
function _fn_wikipe_json( $type, $name ) {
	return _fn( "wikipe_$type",
		$type == 'chem' ? $name : strtolower( _fn_rep( $name ) )
	);
}
function _fn_rep( $name ) {
	return strtr( $name, [ ' '=>'_', '/'=>'_', '.' => '_' ] );
}


//.. _get_title_en
$o_sqlite_en_title = null;
function _get_title_en( $term ) {
	global $o_sqlite_en_title;
	if ( ! $o_sqlite_en_title ) {
		$o_sqlite_en_title = new cls_sqlite( FN_DB_EN_TITLE );
	}
	$en = _get_sqlite( $o_sqlite_en_title, 'rd_to', 'title', $term );
//	if ( ! $en ) return;
	if ( strlen( $en ) < 3 ) return;
	return $en == '@'
		? $term
		: ( strlen( $en ) < 3 ? '' : $en )
	;
}

//.. _get_title_ja
$o_sqlite_ja_title = null;
function _get_title_ja( $en ) {
	global $o_sqlite_ja_title;
	//- マニュアル指定
	if ( TSV_ANNOT['e2j'][$en] ) {
		return TSV_ANNOT['e2j'][$en];
	}

	//- wikipedbから
	if ( ! $o_sqlite_ja_title ) {
		$o_sqlite_ja_title = new cls_sqlite( FN_DB_E2J );
	}
	return _get_sqlite( $o_sqlite_ja_title, 'ja', 'en', $en );
}

//.. _get_sqlite
function _get_sqlite( $o_db, $sel, $key, $val ) {
	return $o_db->qcol([
		'select' => $sel ,
		'where' => "$key=". _quote( $val )
	])[0];
}


//.. _get_wikipe_abst
function _get_wikipe_abst( $word, $lang = 'en' ) {
	if ( !$word ) return;
	$u =  $lang == 'en'
		? 'https://en.wikipedia.org/w/api.php'
		: 'https://ja.wikipedia.org/w/api.php'
	;
	$data[ 'format' ] = 'json';
	$q = http_build_query([
		'format'  => 'json' ,
		'action'  => 'query' ,
		'titles'  => $word,
		'prop'    => 'extracts',
		'exintro' => '1'
	]);
	$j = json_decode( file_get_contents( "$u?$q" ) )->query->pages;
	foreach ( (object)$j as $c ) {
		if ( $c->extract == '' ) continue;
		return trim( strtr( $c->extract, [ '<p></p>' => '', "\n\n" => "\n" ] ) );
	}
	_m( "$word: couldn't get data", 'red' );
}

//.. _download_save
function _download_save( $key, $en ) {
	global $cnt_old;
	if ( ! $en ) return;
	$fn = _fn_wikipe_json( TYPE, $key );
//	_pause( "$key => $en" );
	if ( $en == 'x' ) {
		if ( file_exists( $fn ) ) {
			_del( $fn );
			_m( "削除: $key" );
		} else {
			_m( "無視: $key" );
		}
		return;
	}
	

	$add = 1;
	while ( true ) {
		if ( ! file_exists( $fn ) ) break;
		$j = _json_load2( $fn );
		if ( strtolower( $j->key ) == strtolower( $key ) ) break;
		++ $add;
		$fn = _fn_wikipe_json( TYPE, $key .'-((alt))-'. $add );
	}

	$ja = _get_title_ja( $en );
	$j = _json_load2( $fn );

	if ( $j->et == $en && $j->jt == $ja ) {
		_m2( "$key: not changed" );
		$time_out = filemtime( $fn ); 
		if ( $time_out < OLDER_THAN_30_DAYS && $cnt_old < MAXNUM_OLD ) {
			_m( "$key: " . date( 'Y-m-d', $time_out ). " old file ($cnt_old)", 'green' );
			++ $cnt_old;
		} else {
			//_pause();
			return;
		}
	}
	$a = array_filter([
		'key' => $key,
		'et'  => $en,
		'ea'  => _get_wikipe_abst( $en ),
		'jt'  => $ja,
		'ja'  => _get_wikipe_abst( $ja, 'ja' ),
	]);
	
	_kvtable( $a );
//	_pause();

	if ( !_json_save( $fn, $a ) )
		_m( "$fn: 書き込み失敗", 'red' );
//	_pause();
}
//.. _check_title
function _check_title( $key, $en ) {
	$fn = _fn_wikipe_json( TYPE, $key );
	if ( $en == 'x' && file_exists( $fn ) ) {
		_m( "新しい削除指定: $key" );
		return;
	}
	$add = 1;
	while ( true ) {
		if ( ! file_exists( $fn ) ) break;
		$j = _json_load2( $fn );
		if ( strtolower( $j->key ) == strtolower( $key ) ) break;
		++ $add;
		$fn = _fn_wikipe_json( TYPE, $key .'-((alt))-'. $add );
	}
	$json = _json_load2( $fn );

	if ( ! $json->et && ! $en ) return;
	$msg = '';
	$ja = _get_title_ja( $en );
	$flg_pause = false;
	if ( $json->et != $en )  {
		$msg = '[EN] changed';
		if ( $json->et && ! $en ) $msg = '[EN] lost';
		if ( ! $json->et && $en ) $msg = '[EN] new';
		_m( "$key - $msg: ". ( $json->et ) ." => $en", 'green' );
//		if ( $msg == '[EN] lost' )
			$flg_pause = true;
	}

	if (  $json->jt != $ja ) {
		$msg = '[JA] changed';
		if ( $json->jt && ! $ja ) $msg = '[JA] lost';
		if ( ! $json->jt && $ja ) $msg = '[JA] new';
		_m( "$key - $msg: ". ( $json->jt ) ." => $ja", 'green' );
//		if ( $msg == '[JA] lost' )
			$flg_pause = true;
	}
	if ( $flg_pause ) {
		_pause('#');
	} else {
		_m2( 'not changed' );
	}
	
//	_pause();
}



//.. _m2
function _m2( $s, $s2 = '' ) {
	if ( SHOW_DETAIL )
		_m( $s, $s2 );

}

//. gomibako

//.. not found list
$not_found_list = [];
function _add_not_found_list( $term ) {
	global $not_found_list;
	if ( count( $not_found_list ) < 200 )
		$not_found_list[] = $term;
}
function _save_not_found_list() {
	global $not_found_list;
	file_put_contents(
		DN_WIKIPE . '/not_found_list_' .TYPE. '.txt',
		implode( "\n", $not_found_list )
	);
}

//.. ng_list
//... _in_ng_list
function _in_ng_list( $term ) {
	global $_ng_list;
	if ( ! $_ng_list )
		_load_ng_list();
	return $_ng_list[ $term ] > 0;
}

//... _add_ng_list
function _add_ng_list( $term ) {
	global $_ng_list;
	if ( ! $ng_list ) _load_ng_list();
	return $_ng_list[ $term ] = time();
}

//... _load_ng_list
function _load_ng_list() {
	global $_ng_list;
	foreach ( (array)_json_load( FN_NG_LIST ) as $term => $time ) {
		if ( $time < TIME_OLD ) continue; //- 古い
		$_ng_list[ $term ] = $time;
	}
}

//... _save_ng_list
function _save_ng_list() {
	global $_ng_list;
	_json_save( FN_NG_LIST, $_ng_list );
}

//.. file_is_news
//- ファイルが既にあって新しい
function _file_is_new( $fn ) {
	if ( ! file_exists( $fn ) ) {
//		_m( basename( $fn, '.json.gz' ) . ': 無い' );
		return false;
	}
	if ( filemtime( $fn ) < TIME_OLD ) {
		_m( basename( $fn, '.json.gz' ) . ': 古い' );
		return false;
	}
	if ( filesize( $fn ) < 50 ) {
		_m( basename( $fn, '.json.gz' ) . ': 内容がない' );
		_del( $fn );
		return false;
	}
	return true;
}
