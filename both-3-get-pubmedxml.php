<?php
//. init
require_once( "commonlib.php" );
define( 'URL_BASE', "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmed&mode=text&report=xml&id=" );

//. PMIDのリストアップ
$pmids = [];
//.. EMDB
_line( '集計', 'EMDB' );
foreach( _idlist( 'emdb' ) as $id ) {
	//- primary
	$pmids[] = _json_load([ 'emdb_add', $id ])[ 'pmid' ];

	//- secondary
	foreach (
		(array)_json_load2([ 'emdb_new_json', $id ])
			->crossreferences->secondary_citation
		as $c
	) {
		$pmids[] = $c->journal_citation->ref_PUBMED;
	}
}

//.. PDB
_line( '集計', 'PDB' );
foreach( _idlist( 'epdb' ) as $id ) {
	//- add
	$pmids[] = _json_load([ 'pdb_add', $id ])['pmid'];

	//- pdbjson
	foreach ( (array)_json_load2([ 'epdb_json', $id ])->citation as $v ) {
		$pmids[] = $v->pdbx_database_id_PubMed;
	}
}

//.. sasbdb
foreach ( _idloop( 'sas_json' ) as $fn ) {
	foreach ( (array)_json_load2( $fn )->citation as $v ) {
		$pmids[] = $v->pdbx_database_id_PubMed;
	}
}

$pmids = _uniqfilt( $pmids );
_m( 'PMIDの数: ' . count( $pmids ) );

//. 要らなくなったのを消す
_delobs_misc( 'pubmed_xml', array_fill_keys( $pmids, true ) );

//. main
_line( 'PubMed-XML 取得' );
$flg_fisrtload = true;
_m( "ダウンロードする個数: " . count( $pmids ) );

foreach ( $pmids as $pmid ) {
	$fn = _fn( 'pubmed_xml', $pmid );
	if ( !FLG_REDO && file_exists( $fn ) ) continue;

	//- スリープ（初回のみスリープ無し）
	if ( ! $flg_fisrtload ) {
		echo '[sleep]' ;
		sleep( rand( 5, 10 ) );
	}
	$flg_fisrtload = false;

	//- 読み込み
	$file = file_get_contents( URL_BASE. $pmid );

	//- 異常なファイルじゃないかチェック
	$x = simplexml_load_string( $file );
	if ( (string)$x->PubmedArticle->MedlineCitation->PMID != $pmid ) {
		_m( "$pmid: データが取得できない", -1 );
		continue;
	}

	//- 比較
	if ( FLG_REDO && file_exists( $fn ) ) {
		if ( $file == file_get_contents( $fn ) )
			continue;
	}

	file_put_contents( $fn, $file );
	_log( "$pmid: pubmed-xml ダウンロード" );
}

//. end

_end();
_php( 'both-sub1-pubmed-json' );

