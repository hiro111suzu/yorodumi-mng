<?php
require_once( "commonlib.php" );

//. ディレクトリ
_mkdir( $dn = DN_PREP . '/pap' );
_mkdir( "$dn/emdb" );
_mkdir( "$dn/pdb" );
_mkdir( "$dn/sas" );
_mkdir( "$dn/info" );

//. ファイル名

$dn_kw = DN_PREP . '/keyword';
$_filenames += [
	'emdb_pap'	=> "$dn/emdb/<id>.json" ,
	'pdb_pap'	=> "$dn/pdb/<id>.json" ,
	'sas_pap'	=> "$dn/sas/<name>.json" ,
	'emdb_kw'	=> "$dn_kw/emdb/<id>.txt" ,
	'pdb_kw'	=> "$dn_kw/pdb/<id>.txt" ,
	'pdb_auth'	=> "$dn_kw/pdb_auth/<id>.txt" ,
	'sas_kw'	=> "$dn_kw/sas/<name>.txt" ,
	'pap_info'	=> "$dn/info/<name>.json.gz"
];


define( 'FN_JN2ISSN'	, "$dn/jn2issn.json" );

//- ダウンロードしたcsvファイル
define( 'FN_JOURNAL_CSV', DN_FDATA . '/pap/JournalHomeGrid.csv' );
if ( ! file_exists( FN_JOURNAL_CSV ) )
	_problem( 'ファイルがない - ' . FN_JOURNAL_CSV );

define( 'FN_IS2IF',  DN_PREP . '/pap/is2if.json.gz' );

//- 管理用、なくなったIDを消しやすいように
define( 'FN_DID2PMID', "$dn/did2pmid.json.gz" );

//- 本番用、毎回作る
define( 'FN_PMID2DID', "$dn/pmid2did.json.gz" );

//. function
function _is2if( $is ) {
	global $_is2if;
	if ( ! defined( 'IS2IF' ) )
		define( 'IS2IF', _json_load( FN_IS2IF ) );
	return IS2IF[ $is ];
}

/*
- pmid
- doi
- title
- journal
- issue
- author
- date
- src

- [追加] method EM, x-ray dif, nmr etc
- [追加] method2 "single particle" "solid state nmr"
- [追加] reso
- [追加] issn
- [追加] kw表示用
- kw

emdb
	method
	issn
	kw

pdb
	


*/
