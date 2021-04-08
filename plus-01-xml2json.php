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

//. func: _post_prep
//- common-xml2jsonから呼ばれる
function _post_prep( &$json ) {
	//.. pfam detail
	foreach ( (array)$json->struct_ref as $num => $c ) {
		if ( !_instr( 'PFAM', $c->id ) ) continue;
		$d = PFAM_DESC[ (integer)_numonly( $c->pdbx_db_accession ) ];
//		if ( $c->details == $d ) continue;
//		$flg = true;
		$d = is_array( $d ) ? _imp( array_unique( $d ) ) : $d;
		$json->struct_ref[ $num ]->details = $d;
	}
	
	//.. prosite chain
	//- prositeの情報をチェーンごとに分ける
	$id_conv = [];
	foreach ( (array)$json->struct_site_gen as $num => $c ) {
		$site_id = $c->site_id;
		if ( substr( $site_id, 0, 2 ) != 'PS' ) continue;
		$chain_id = $c->auth_asym_id;
		$json->struct_site_gen[ $num ]->site_id = $site_id .'_'. $chain_id;
		$id_conv[ $site_id ][] = $chain_id;
	}

	foreach ( (array)$json->struct_site as $num => $c ) {
		$site_id = $c->id;
		$chain_ids = $id_conv[ $site_id ];
		if ( $chain_ids == '' ) continue;
		unset( $json->struct_site[ $num ] );
		foreach ( $chain_ids as $i ) {
			$c->id = $site_id .'_'. $i;
			$json->struct_site[] = clone $c;
		}
	}
	return $json;
}
