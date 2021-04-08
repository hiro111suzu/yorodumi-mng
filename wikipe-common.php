<?php
require_once( "commonlib.php" );
define( 'ISSUE_KEY', '___' );

/*
key	taxo(sci_name) chem(chemid) other(title)

date
en [ key, abst ] ,
ja [ key, abst ]
*/
//. define

define( 'QUOTE_MARK'     , '__<<q>>__' );
define( 'SQ', "'" );
define( 'TIME_OLD', time() - 60*60*24*100 ); //- 100日

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

define( 'TSV_ANNOT', _tsv_load2( DN_EDIT. '/wikipe_annot.tsv' ) );

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
_mkdir( DN_SQLITE. '/wikipe' );
define( 'FN_DB_ID2EN'    	, DN_SQLITE. '/wikipe/id2en.sqlite' );
define( 'FN_DB_E2J'      	, DN_SQLITE. '/wikipe/e2j.sqlite' );

define( 'FN_DB_EN_TITLE' 	, DN_SQLITE. '/wikipe/en_title.sqlite' );
define( 'FN_DB_EN_TITLE_NC' , DN_SQLITE. '/wikipe/en_title_nc.sqlite' );
//define( 'FN_DB_E2J'      	, DN_SQLITE. '/wikipe/e2j.sqlite' );


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
function _define_annot( $type, $flg_lowercase = false ) {
	$a = array_merge(
		TSV_ANNOT[ $type ],
		TSV_ANNOT[ 'all' ]
	);
	define( 'ANNOT', $flg_lowercase
		? array_change_key_case( $a )
		: $a 
	);
}

//.. _regist
function _regist( $key, $name_list ) {
	$list = [];
	foreach ( _uniqfilt( $name_list ) as $n ) {
		if ( strlen( $n ) < 3 ) continue;
		if ( _have_ng_term( $n ) ) continue;
		$list[] = $n;
	}
	if ( $key == ISSUE_KEY )
		_m( "問題キー: $key\n". _imp( $list ) );

	//- case
	foreach ( $list as $n ) {
		if ( _regist_term( $key, $n ) ) {
			_cnt( 'hit case' );
			if ( $key == ISSUE_KEY )
				_m( "ヒット $n" );
			return;
		}
	}
	
	//- no case
	foreach ( $list as $n ) {
		if ( _regist_term( $key, $n, true ) ) {
			_cnt( 'hit no case' );
			if ( $key == ISSUE_KEY )
				_m( "ヒット $n" );
			return;
		}
	}
	if ( $key == ISSUE_KEY )
		_m( "ヒットなし" );
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
	$ret = false;

	$en = _get_from_db( $flg_nc ? 'en_title_nc' : 'en_title' , $term );
	$ret = $en == '@'
		? ( $flg_nc 
			? _get_from_db( 'en_title_nc_self', $term )
			: $term 
		)
		: $en
	;

	//- ダメヒット
	if ( strlen( $ret ) < 3 || TSV_ANNOT['ng_title'][ $ret ] )
		return false;

	foreach ( array_keys( TSV_ANNOT['ng_term'] ) as $term ) {
		if ( _instr( $term, $en ) ) return false;
	}

	return $ret;
}

//.. _fn_wkp_json
function _fn_wkp_json( $et ) {
	return DN_JSON. '/'. urlencode( $et ) . '.json.gz';
}
//.. _m2
function _m2( $s, $s2 = '' ) {
	if ( defined( 'SHOW_DETAIL' ) && SHOW_DETAIL )
		_m( $s, $s2 );
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
	$j = json_decode( $reply = file_get_contents( "$u?$q" ) )->query->pages;
	foreach ( (object)$j as $c ) {
		if ( $c->extract == '' ) continue;
		return trim( strtr( $c->extract, [ '<p></p>' => '', "\n\n" => "\n" ] ) );
	}
	_m( "$word: couldn't get data\n$reply", 'red' );
}

