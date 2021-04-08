PubMed-IDをチェック

<?php
require_once( "commonlib.php" );

$repin = [
//	'/([0-9]+)([a-zA-Z]+)/' ,
	'/angstroms?|Å/' ,
	'/\b(three|3)[ -]?(d|dimensional)\b/' ,
	'/\b(the|a|and|or|an)\b/',
	'/[\t\n\r,\.\-\/ :;\(\)\<\>]/',
	'/releasefactor2|rf2|escherichiacoli|ecoli|cryoem|cryoelectronmicroscopy/',
	'/-\./',
	'/doublestranded /',
	'/\.$/',
	'/[^a-zA-Z0-09]/' ,
];
$repout = [
//	'\1 \2' ,
	'a' ,
	'3d',
	'' ,
	'' ,
	'--',
	'', 
	'ds',
	'',
	'' ,
];

define( 'FN_LIST', DN_EDIT. '/pubmedid_whitelist.txt' );
$white_list = _file( FN_LIST );

$count = 0;
$white_list_used = [];
$list_tobe_checked = '';
$json_tobe_checked = [];

//. tsv data
if ( ! file_exists( $fn = DN_EDIT . '/pubmed_id.tsv' ) ) {
	_problem( "ファイルがない: $fn" );
	_end();
}
define( 'TSV_DATA', _tsv_load2( $fn ) );

//. EMDB
foreach ( TSV_DATA[ 'emdb' ] as $id => $pmid ) {
	if ( $pmid == '' ) continue;
	if ( _prep_white_list( "$id-$pmid" ) ) continue;
	if ( !_load_pubmed_json( $pmid ) ) continue;

	$main_json = _json_load2([ 'emdb_new_json', $id ])
		->crossreferences->primary_citation->journal_citation;
	$title_xml = $main_json->title;
	if ( _is_same( $title_xml ) ) continue;
	_out1( $title_xml, $main_json->author );

	++ $count;
}

//. PDB
foreach ( TSV_DATA[ 'pdb' ] as $id => $pmid ) {
	if ( $pmid == '' ) continue;
	if ( _prep_white_list( "$id-$pmid" ) ) continue;
	if ( !_load_pubmed_json( $pmid ) ) continue;

	//- title
	$main_json = _json_load2( _fn( 'pdb_json', $id ) );
	foreach ( $main_json->citation as $x ) {
		if ( $x->id != 'primary' ) continue;
		$title_xml = $x->title;
		break;
	}
	if ( _is_same( $title_xml ) ) continue;

	//- author
	$a = [];
	foreach( $main_json->citation_author as $x ) {
		if ( $x->citation_id != 'primary' ) continue;
		$a[] = $x->name;
	}
	_out1( $title_xml, _imp( $a ) );
	++ $count;
}

if ( $count == 0 ) {
	_m( "全てチェック、問題なし、ホワイトリスト数: " . count( $white_list_used  ) );
} else {
	_m( "$count data needed to be checked", -1 );
	_m( $list_tobe_checked );
}

_comp_save( FN_LIST, $white_list_used );
if ( $list_tobe_checked )
	file_put_contents( DN_PREP. '/pubmed_tobe_checked.txt', $list_tobe_checked );

_comp_save( DN_PREP. '/pubmed_tobe_checked.json', $json_tobe_checked );


//. end
_end();

//. func
//.. _is_same
function _is_same( $t1 ) {
	global $pubmed_json;
/*
	if ( _f1( $t1 ) == _f1( $pubmed_json->title ) ) {
		_m( _f1( $t1 ) );
		_m( _f1( $pubmed_json->title ) );
	}
*/	
	return _f1( $t1 ) == _f1( $pubmed_json->title );
}


//.. _f1
function _f1( $s ) {
	global $repin, $repout;
	return preg_replace( $repin, $repout, strtolower( $s ) );
}

//.. _out1
function _out1( $title_xml, $auth_xml ) {
	global $id, $pmid, $pubmed_json, $list_tobe_checked, $json_tobe_checked;

	_m( LINE
		. "$id-$pmid"
		. "\n[pubmed] " . $pubmed_json->title
		. "\n[str-db] " . $title_xml
		. "\n"
		. "\n[pubmed] " . _imp( $pubmed_json->auth )
		. "\n[str-db] " . _imp( $auth_xml )
		. "\n"
	);
	_problem( "$id-$pmid: pubmed-ID 要チェック" );
	$json_tobe_checked[ "$id-$pmid" ] = [
		'title-PubMed'	=> $pubmed_json->title ,
		'title-str-DB'	=> $title_xml ,
		'auth-PubMed'	=> implode( ', ',  $pubmed_json->auth ) ,
		'auth-str-DB'	=> $auth_xml ,
	];
	$list_tobe_checked .= "$id-$pmid\n";
}

//.. _white_list
function _prep_white_list( $s ) {
	global $white_list, $white_list_used;
	if ( in_array( $s, $white_list ) ) {
		$white_list_used[] = $s;
		return true;
	}
	return false;
}

//.. _load_pubmed_json
function _load_pubmed_json( $pmid ) {
	global $pubmed_json;
	$pubmed_json = _json_load2( _fn( 'pubmed_json', $pmid ) );
	if ( $pubmed_json )
		return true;
	_problem( "$pmid: pubmed-jsonファイルがない" );
	return false;
}
