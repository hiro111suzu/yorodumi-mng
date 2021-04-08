<?php
//. init
require_once( "commonlib.php" );
define( 'URL_SRC', 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=pubmed&term=(' );

define( 'TODAY', date( 'Y-m-d', time() - 90 * 3600 * 24 ) ); //- 90日
define( 'TSVDATA', _tsv_load2( DN_EDIT. '/pubmed_id.tsv' ) );

//. blacklist
$ign_id = array_fill_keys([
	20909 ,
	1906 ,
	'3dg0' ,
	'3dg2' ,
	'3dg4' ,
	'3dg5' ,
], true );

$ign_title = array_change_key_case( array_fill_keys([
	'bovine glutamate dehydrogenase (18apr21a)' ,
	'a bifunctional kinase' ,
	'to be published' ,
	'To be published later' ,
	'cryoem of groel' ,
	'membrane protein' ,
	'Structure of a Protein Complex' ,
	'RNA-DNA-protein complex' ,
	'Structure of Human Apoferritin' ,

], true ));

//. ID一覧取得

$num = 0;

//. 全取得
_line( 'データ取得' );

//.. EMDB
$data = [];
foreach( TSVDATA[ 'emdb' ] as $id => $pmid ) {
	if ( $ign_id[ $id ] || $pmid ) continue;
	$main_json = _json_load2([ 'emdb_new_json', $id ])
		->crossreferences->primary_citation->journal_citation;
	$data[ "e$id" ] = [
		't' => $main_json->title ,
		'a' => $main_json->author
	];
}
//_time( 'emdb' );

//.. PDB
foreach( TSVDATA[ 'pdb' ] as $id => $pmid ) {
	if ( $ign_id[ $id ] || $pmid ) continue;
	$json = _json_load2( _fn( 'epdb_json', $id ) );
	//- title
	foreach ( (array)$json->citation as $j ) {
		if ( $j->id != 'primary' ) continue;
		$data[ $id ][ 't' ] = $j->title;
		break;
	}

	//- auth
	$data[ $id ][ 'a' ] = [];
	foreach ( (array)$json->citation_author as $j ) {
		if ( $j->citation_id != 'primary' ) continue;
		$data[ $id ][ 'a' ][] = trim( $j->name );
	}
}
//.. 集計
$title2ids = [];
$title2auth = [];
foreach ( $data as $id => $c ) {
	if ( $ign_id[ $id ] ) continue;

	extract( $c ); //- $t, $a
	$t = trim( $t, '.' );
	if ( !$t || $ign_title[ strtolower( $t ) ] || strlen( $t ) < 20 ) continue;	

	$title2ids[ $t ][] = $id;
	if ( $a && ! $title2auth[ $t ] )
		$title2auth[ $t ] = $a;
}
$total = count( $title2ids );
_m( "$total 件のデータ取得" );

//. test
_line( '検索開始' );
$num = 0;
$data = [];
foreach ( $title2ids as $title => $ids ) {
	++ $num;
	_m( "$num/$total: ". _imp( $ids ), 1 );

	$pmids_t = _pubmed_url( $title );
	sleep( 1 );
	$a = _uniqfilt( $title2auth[ $title ] ) ;
	$pmids_a = 1 < count( (array)$a ) ? _pubmed_url( implode( ' ', $a ) ) : '';

	if ( $pmids_t || $pmids_a ) {
		$data[ $title ] = [
			'ids' => $ids ,
			'auth' => $title2auth[ $title ] ,
			'pmids_t' => $pmids_t ,
			'pmids_a' => $pmids_a ,
		];
		if ( $pmids_t )
			_m( 'title: '. _imp( $pmids_t ), 'blue' );
		if ( $pmids_a )
			_m( 'auth: '. _imp( $pmids_a ), 'green' );
	} else {
		_m( 'not found' );
	}

	_m( '[sleep]' );
	sleep( 2 );
//	if ( $num == 100 ) break;
}
_json_save( DN_PREP. '/pubmed_found.json.gz', $data );


//. function _pubmed_url
function _pubmed_url( $str ) {
	$u = URL_SRC
		. urlencode( _reg_rep( $str, [
			'/[^a-zA-Z0-9]/' => ' ',
			'/\b(and|or|the|of|as|an|for|to|in|on|with|by)\b/i' => ' ' ,
			'/  +/' => ' ' ,
		]) )
		. ")%20AND%20(%22". TODAY. "%22%5BDate%20-%20Publication%5D%20%3A%20%223000%22%5BDate%20-%20Publication%5D)"
	;
	return (array)simplexml_load_string( file_get_contents( $u ) )
		->IdList->Id;
}
