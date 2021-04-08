PDBMLnoatom => json

<?php
include "commonlib.php";
define( 'DB_TYPE', 'plus' );
include "common-xml2json.php";
define( 'PFAM_DESC', _json_load( FN_PFAM_JSON ) );

//. init
//.. pre変換文字列
define( 'REP_PRE',  _rep_prep( STR_REP_PRE_PDB ) );
define( 'REP_POST', _rep_prep( STR_REP_POST ) );

//.. 複数値タグ
_prep_multitag([ 'struct_ref_src' ]);

//. 実行
_line( '変換開始' );

foreach ( _idloop( 'mlplus' ) as $fn_in ) {
	if ( _count( 'pdb', 0 ) ) break;
	_cnt( 'total' );
	$id = _fn2id( $fn_in );
	$fn_out = _fn( 'pdb_plus', $id );
	if ( _same_time( $fn_in, $fn_out ) ) continue;

	//.. プレ処理
	$cont = _pre_conv(
		strtr( _gzload( $fn_in ), [
			'_pdbmlplus' => '' ,
			'auth_validate="N" ' => ' ' ,
		]),
		[
			'in' => [ "/entry_id=\"$id\"/i", "/pdbx_PDB_id_code=\"$id\"/i" ] ,
			'out' => [ '', '' ]
		]
	);

	//.. ポスト処理
	_post_conv( compact( 'cont', 'id', 'fn_in', 'fn_out' ) );
}

//.. 集計
_line( '集計' );
_cnt();

//. 無くなったデータを消す
_delobs_pdb( 'pdb_plus' );
_end();
