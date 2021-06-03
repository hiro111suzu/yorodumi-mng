論文DB用準備データを作成
3つのデータベース、共通
pmid2did毎回作る
did2pmid更新

<?php
//. init
require_once( "pap-common.php" );

$dn = DN_PREP . '/pap';

$did2pmid = _json_load( FN_DID2PMID );

$ex_id = [];
$_data = [];
$_kw = [];

define( 'FN_PMID_TSV'	, DN_EDIT. '/pubmed_id.tsv' );
define( 'FN_PAP_TITLE'	, DN_EDIT. '/pap_title.tsv' );
define( 'PUBMED_ID_TSV'	, _tsv_load2( FN_PMID_TSV ) );
define( 'PAP_TITLE'		, _tsv_load( FN_PAP_TITLE ) );

//$jn2issn_tsv = (array)_tsv_load( FN_JN2ISSN_TSV );
//$jn2issn = _json_load( FN_JN2ISSN );


//. EMDB
_line( 'EMDB data' );
_count();

$flg_pmid_from_tsv = [];
foreach ( PUBMED_ID_TSV[ 'emdb' ] as $id => $val ) {
	if ( ! $val ) continue;
	$flg_pmid_from_tsv[ $id ] = true;
	_cnt( 'Pubmed-ID from tsv file' );
}
_cnt();

//.. main loop
foreach ( _idlist( 'emdb' ) as $id ) {
	_cnt( 'total' );
	if ( _count( 1000, 0 ) ) break;

	$o_id = new cls_entid();
	$did = $o_id->set_emdb( $id )->did;
	$ex_id[ $did ] = true;

	$fn_json = _fn( 'emdb_old_json', $id );
	$fn_add  = _fn( 'emdb_add' , $id );
	$fn_out  = _fn( 'emdb_pap' , $id );

	$add  = _json_load2( $fn_add );
	$pmid = $add->pmid;
	$fn_pmjson = _fn( 'pubmed_json', $pmid );

	//.. やるかやらないか
	//- やらないフラグ
	$flg_continue = _newer( $fn_out, [ $fn_json, $fn_add ] );

	//- pubmed-jsonがあれば、そのタイムスタンプも参照
	if ( file_exists( $fn_pmjson ) && $flg_continue )
		$flg_continue = _newer( $fn_out, $fn_pmjson );

	//- tsv由来pubmed-ID
	if ( $flg_pmid_from_tsv[ $id ] && $flg_continue )
		$flg_continue = _newer( $fn_out, FN_PMID_TSV );

	//- タイトルアノテーション
	if ( PAP_TITLE[ $id ] && $flg_continue )
		$flg_continue = _newer( $fn_out, FN_PAP_TITLE );

	if ( $flg_continue ) continue;

	//.. main
	$json = _json_load2( $fn_json );
	_d( 'pmid', $pmid );

	//- EMDB jsonから
	$dep = $json->deposition;
	$jnl = $dep->primaryReference->journalArticle;
	
	_d( 'author'	, _exp( $jnl->authors ?: $dep->authors ) );
	_d( 'journal'	, $jnl->journal );
	_d( 'doi'		, $jnl->ref_doi );
	_d( 'title'		, PAP_TITLE[ $id ] ?: $jnl->articleTitle );
	_d( 'issue'		, _imp( array_filter([
		_ifnn( $jnl->volume		, 'Vol. \1'		) ,
		_ifnn( $jnl->issue		, 'Issue \1'	) ,
		_page( $jnl->firstPage, $jnl->lastPage ),
		_ifnn( $jnl->year		, 'Year \1'	)
	]) ) );
	_d( 'date'		, $dep->depositionDate );
	_d( 'date_type'	, 'str' );
	_d( 'method'	, [ 'em' ] );
	_d( 'method2'	, [ $add->met ] );
	_d( 'reso'		, $add->reso );

	$_kw = _exp( $dep->keywords );

	//- pubmed-jsonから
	if ( $pmid != '' ) {
		_pmj( $fn_pmjson );
	}
	_d( 'kw', '' ); 

	//- 生物種
	//- emdbの処理を整理したら、ここも修正、addjsonから取得
	$a = [];
	foreach ( (array)$json->sample->sampleComponent as $k1 => $v1 ) {
		if ( ! is_object( $v1 ) ) continue;
		foreach ( $v1 as $k2 => $v2 ) {
			$n = $v2->natSpeciesName ?: $v2->sciSpeciesName;
			if ( $n == '' ) continue;
			$a[ $n ] = true;
		}
	}
	_d( 'src', array_keys( $a ) );

	_sv( $fn_out );
	_cnt( 'saved' );
}

_cnt();
_delobs_emdb( 'emdb_pap' );

//. PDB
define( 'MET_CONV', [
	'X-RAY DIFFRACTION'			=> 'x-ray' ,
	'NEUTRON DIFFRACTION'		=> 'neutron',
	'FIBER DIFFRACTION'  		=> 'fiber',
	'ELECTRON CRYSTALLOGRAPHY'	=> 'em',
	'ELECTRON MICROSCOPY'		=> 'em',
	'SOLUTION NMR'				=> 'nmr',
	'SOLID-STATE NMR'			=> 'nmr',
	'SOLUTION SCATTERING'		=> 'sas',
	'POWDER DIFFRACTION'		=> 'powder',
	'INFRARED SPECTROSCOPY'		=> 'infrared',
	'EPR'						=> 'epr',
	'FLUORESCENCE TRANSFER'		=> 'fluorescence',
	'THEORETICAL MODEL'			=> 'theoretical',
]);
define( 'MET_CONV2', [
	'SOLUTION NMR'				=> 'solution',
	'SOLID-STATE NMR'			=> 'solid',
]);

$_pdbid2pmid = [
//	'5iw9' => 27193680 ,
//	'5ip7' => 27193681 ,
//	'5ip9' => 27193681 ,
];

_line( 'PDB tsv由来のpubmed-ID' );
$flg_pmid_from_tsv = [];
foreach ( PUBMED_ID_TSV[ 'pdb' ] as $id => $val ) {
	if ( $val == '' ) continue;
	$flg_pmid_from_tsv[ $id ] = true;
	_cnt( 'Pubmed-ID from tsv file' );
}
_cnt();

//.. main loop
_count();

foreach ( _idloop( 'pdb_json' ) as $fn_json ) {
	_cnt( 'total' );
	if ( _count( 5000, 0 ) ) break;
	$id = _fn2id( $fn_json );
	$did = "pdb-$id";
	$fn_out = _fn( 'pdb_pap' , $id );
	$ex_id[ "pdb-$id" ] = true;

	//.. やるかやらないか
	//- やらないフラグ
	$flg_continue = _newer( $fn_out, $fn_json );

	//- tsv由来pubmed-ID
	if ( $flg_pmid_from_tsv[ $id ] && $flg_continue )
		$flg_continue = _newer( $fn_out, FN_PMID_TSV );

	//- タイトルアノテーション
	if ( PAP_TITLE[ $id ] && $flg_continue )
		$flg_continue = _newer( $fn_out, FN_PAP_TITLE );

	if ( $flg_continue ) continue;

	//.. データ読み込み
	$fn_qinfo  = _fn( 'qinfo', $id );
	$qinfo = _json_load2( $fn_qinfo );
	$json = _json_load2( $fn_json );
	$add = _json_load2( _fn( 'pdb_add', $id ) ); //- em-pdbデータ
	$pmid = $add->pmid ?: $qinfo->pmid ?: $_pdbid2pmid[ $id ];
	_d( 'pmid', $pmid  );

	//- PDB jsonから
	//- primary citation 取り出し
	$jnl = [];
	foreach ( (array)$json->citation as $c ) {
		if ( $c->id != 'primary' ) continue;
		$jnl = $c;
		break;
	}
	if ( $jnl == '' ) continue;
	$a = [];
	foreach ( (array)$json->citation_author as $c ) {
		 if ( $c->citation_id != 'primary' ) continue;
		 $a[ $c->name ] = 1;
	}

	//.. 処理
	_d( 'author'	, array_keys( $a ) );
	_d( 'journal'	, $jnl->journal_abbrev );
	_d( 'issn'		, $jnl->journal_id_ISSN );
	_d( 'doi'		, $jnl->pdbx_database_id_DOI );
	_d( 'title'		, PAP_TITLE[ $id ] ?: $jnl->title );
	_d( 'issue'		, _imp( array_filter([
		_ifnn( $jnl->journal_volume	, 'Vol. \1'		) ,
		_ifnn( $jnl->journal_issue	, 'Issue \1'	) ,
		_ifnn( $jnl->page_first . $jnl->page_last,
					"Page {$jnl->page_first}-{$jnl->page_last}" ) ,
		_ifnn( $jnl->year		, 'Year \1'	)
	]) ) );

	_d( 'date'		, $qinfo->ddate );
	_d( 'date_type'	, 'str' );

	_d( 'src'       , $qinfo->src );
	_d( 'chemid'    , $qinfo->chemid );
	_d( 'reso'      , $add->reso ?: $qinfo->reso );
	
	//- keyword
	$k = $json->struct_keywords[0];
	$_kw = _exp( $k->pdbx_keywords .','. $k->text );

	//.. method
	$met = [];
	$met2 = [];
	foreach ( (array)$qinfo->method as $m ) {
		if ( _instr( 'ELECTRON', $m ) ) {
			$met[] = 'em';
			$met2[] = $add->met ?: strtolower( $m );
		} else {
			$met[] = MET_CONV[ $m ];
			$met2[] = MET_CONV2[ $m ] ?: MET_CONV[ $m ];
		}
	}
	_d( 'method', $met );
	_d( 'method2', $met2 );

	//.. pubmed-jsonから
	if ( file_exists( $fn = _fn( 'pubmed_json', $pmid ) ) ) {
		_pmj( $fn );
	}
	_d( 'kw', '' );

	//.. 書き込み
	_sv( $fn_out );
	_cnt( 'saved' );
}
_cnt();
_delobs_pdb( 'pdb_pap' );

//. SASBDB

_count();
_line( 'SASBDB data' );

//.. main
$subdata = _json_load2( DN_DATA . '/sas/subdata.json.gz' );
$ex_sasid = [];
foreach ( _idloop( 'sas_json' ) as $fn_json )  {
	_cnt( 'total' );
	$_kw = [];
	if ( _count( 100, 0 ) ) break;
	$id = $did = _fn2id( $fn_json );
	$ex_id[ $id ] = true;
	$fn_out = _fn( 'sas_pap' , $id );

	//.. やるかやらないか
	//- pubmed-jsonのファイル名
	$json  = _json_load2( $fn_json );
	$jnl = $json->citation[0];
	if ( $jnl == '' ) continue;
	$pmid = $jnl->pdbx_database_id_PubMed;
	$fn_pmjson = _fn( 'pubmed_json', $pmid );

	//- なくなったデータを消す用
	$ex_sasid[ $id ] = true;

	//- pubmed-jsonがあれば、両方のタイムスタンプを参照
	if ( file_exists( $fn_pmjson ) ) {
		if (
			_newer( $fn_out, $fn_json ) and
			_newer( $fn_out, $fn_pmjson )
		) continue;
	} else {
		if ( _newer( $fn_out, $fn_json ) ) continue;
	}

	//.. データ読み込み
	$sub = $subdata->$id;
	_d( 'pmid', $pmid  );

	//- author
	_m( $id ) ;
	$a = [];
	foreach ( (array)$json->citation_author as $c ) {
		//- arrayになっているデータがある、
		$name = $c->name;
		if ( is_string( $name ) )
			$name = [ $name ];
		foreach ( $name as $n ) {
			$a[ trim( $n, '"' ) ] = 1;
		}
	}

	//.. 処理
	_d( 'author'	, array_keys( $a ) );
	_d( 'journal'	, $jnl->journal_abbrev );
	_d( 'issn'		, $jnl->journal_id_ISSN );
	_d( 'doi'		, $jnl->pdbx_database_id_DOI );
	_d( 'title'		, $jnl->title );
	_d( 'issue'		, _imp( array_filter([
		_ifnn( $jnl->journal_volume	, 'Vol. \1'		) ,
		_ifnn( $jnl->journal_issue	, 'Issue \1'	) ,
		_page( $jnl->page_first, $jnl->page_last ) ,
		_ifnn( $jnl->year		, 'Year \1'	)
	]) ) );

	_d( 'date'		, $json->sas_scan[0]->measurement_date );
	_d( 'date_type'	, 'exp' );

	_d( 'src', $sub->src );

	//.. method
	$m = $json->sas_beam[0]->type_of_source;
	_d( 'method', [ 'sas' ] );
	_d( 'method2', [ 'SAS' . _ifnn( $m, ' (\1)' ) ] );

	//.. pubmed-jsonから
	if ( file_exists( $fn = _fn( 'pubmed_json', $pmid ) ) ) {
		_pmj( $fn );
	}
	_d( 'kw', '' );

	//.. 書き込み
	_sv( $fn_out );
	_cnt( 'saved' );
}
_cnt();

//- 廃止データを消去
_delobs_misc( 'sas_pap', $ex_sasid );

//. did2pmid 整理
_line( 'did2pmid 整理' );
$pmid2did = [];

foreach ( $did2pmid as $did => $pmid ) {
	_cnt( 'total' );
	if ( ! $ex_id[ $did ] ) {
		_m( "$did を消去" );
		unset( $did2pmid[ $did ] );
		_cnt( 'deleted' );
		_del( _fn( 'pap_info', $pmid ) );
	} else {
		$pmid2did[ $pmid ][] = $did;
	}
}
_cnt();
/*
_m( "データ数: $count / 消した数: $count_del" );
if ( $count_del == 0 ) {
	_m( "すべて元データあり、消すべきファイルなし" );
} else {
	_m( $count_del . '個のデータ消去完了', 'blue' );
}
*/
_comp_save( FN_DID2PMID, $did2pmid );
$flg = _comp_save( FN_PMID2DID, $pmid2did );
//_json_save( DN_DATA . '/pmid2did_small.json.gz', $pmid2did_small );

//.. sqlite形式データ書き込み
if ( $flg ) {
	_line( 'pmid2did sqlite形式データ作成' );
	$sqlite = new cls_sqlw([
		'fn' => 'pmid2did', 
		'cols' => [
			'pmid UNIQUE' ,
			'ids'
		],
		'indexcols' => [ 'pmid' ],
		'new' => true
	]);
	foreach ( $pmid2did as $pmid => $ids ) {
		$sqlite->set([
			$pmid ,
			strtr( implode( ',', $ids ), [ 'pdb-' => '', 'emdb-' => 'e' ] )
		]);
	}
	$sqlite->end();
}

//.. sqlite形式データ書き込み
if ( $flg || true ) {
	_line( 'pmid sqlite形式データ作成' );
	$sqlite = new cls_sqlw([
		'fn' => 'pmid', 
		'cols' => [
			'pmid' ,
			'strid UNIQUE'
		],
		'indexcols' => [ 'pmid', 'strid' ],
		'new' => true
	]);
	foreach ( $pmid2did as $pmid => $ids ) {
		foreach ( $ids as $id ) {
			$sqlite->set([
				$pmid ,
				strtr( $id, [ 'pdb-' => '', 'emdb-' => 'e' ] )
			]);
		}
	}
	$sqlite->end();
}

//. jn2issn整理
/*
//- 手書きのtsvにデータが有ればそれで上書き
foreach ( $jn2issn_tsv as $jn => $issn ) {
	$jn2issn[ $jn ] = [ $issn ];
}
_json_save( FN_JN2ISSN, $jn2issn );

//- コンフリクトを抽出、追加
$conf = (array)_file( FN_JN2ISSN_TSV );
foreach ( $jn2issn as $jn => $issn ) {
	if ( count( $issn ) > 1 ) {
		$conf[] = "$jn\t" . _imp( $issn );
	}
}
if ( count( $conf ) > 0 )
	file_put_contents( FN_JN2ISSN_CONF, implode( "\n", array_unique( $conf ) ) );
*/

//. end
_end();


//. function
//.. _d: データを$_dataへ入れる
function _d( $name, $val ) {
	global $_data, $_kw;

	if ( $name == 'kw' ) {
		//- キーワードは、グローバル変数に入れておいて、最後にまとめる
		$val = array_values( array_unique( array_filter( $_kw ) ) ); 
	}

	if ( $val == '' ) return;

	if ( $name == 'journal' ) {
		$val = trim( preg_replace( 
			[ '/\.([^ ])/', '/U\. S\. A\./' ], 
			[ '. \1', 'U.S.A.' ], 
			$val
		) );
	}
	$_data[ $name ] = $val;
}

//.. _sv: jsonを保存
function _sv( $fn ) {
	global $_data, $did, $did2pmid;//, $jn2issn_tsv, $jn2issn;

	//- pubmedIDがない場合は、適当な文字列のMD5ハッシュをタイトルとする
	if ( $_data[ 'pmid' ] == '' ) {
		$_data[ 'pmid' ] = _paper_id( $_data[ 'title' ], $_data[ 'journal' ] );
	}

	_json_save( $fn, $_data );
	$did2pmid[ $did ] = $_data[ 'pmid' ];
/*	
	//- jn2issnを控える
	$jn = $_data[ 'journal' ];
	$issn = $_data[ 'issn' ];
	if ( $jn != '' and $issn != '' ) {
		//- tsvに書いてあったらそれが優先
		if ( $jn2issn_tsv[ $jn ] == '' ) {
			$l = strtolower( $jn );
			if ( ! in_array( $issn, (array)$jn2issn[ $l ] ) )
				$jn2issn[ $l ][] = $issn;
		}
	}
*/
	$_data = [];
}

//.. _exp: コンマ区切り文字列を配列へ
function _exp( $str ) {
	$ret = [];
	foreach ( explode( ',', $str ) as $s ) {
		$s = trim( $s );
		if ( in_array( strtolower( $s ), [ 'na', 'n/a', '-' ] ) )
			continue;
		$ret[] = $s;
	}
	return array_values( array_unique( $ret ) );
}

//.. _pmj: pubmed-jsonから読み込み
function _pmj( $fn ) {
	global $_kw;
	
	$pj = _json_load2( $fn );
	_d( 'pmid'		, $id );
	_d( 'author'	, (array)$pj->auth );
	_d( 'journal'	, $pj->journal );
	_d( 'doi'		, $pj->id->doi );
	_d( 'pii'		, $pj->id->pii );
	_d( 'issn'		, $pj->id->issn );
	_d( 'title'		, $pj->title );
	_d( 'date'		, $pj->date );
	_d( 'date_type'	, 'pub' );
	_d( 'issue'		, $pj->issue );

	foreach ( (array)$pj->kw as $w ) {
		$_kw[] = $w->name;
	}
}

//.. _page
function _page( $p1, $p2 ) {
	$p = implode( '-', _uniqfilt([ $p1, $p2 ]) );
	return $p ? "Page $p" : ''; 
}
