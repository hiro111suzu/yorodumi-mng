<?php
//. init
define( 'TYPE', 'chem' );
require_once( "wikipe-common.php" );
define( 'IDMAP'			, _json_load( DN_PREP. '/chem/chemid_map.json.gz' ) );
define( 'CHEM2PRD'		, _json_load( DN_PREP. '/prd/chemid2prdid.json.gz' ) );
define( 'PRD_INFO'		, _json_load( DN_PREP. '/prd/prd_info.json.gz' ) );
define( 'INCHIKEY_HIT'	, _tsv_load( DN_PREP. '/chem/wikipe_found.tsv' ) );

_define_annot( 'chem' );

define( 'NG_TERMS', [
	'unknown' ,
	'synthetic' ,
	'artificial' ,
	'[' ,
]);

define( 'SHOW_DETAIL', false );

//. main 
$out = [];
foreach ( _idlist( 'chem' ) as $id ) {
	_count( 1000 );

	$json = _json_load2( _fn( 'chem_json', $id ) )->chem_comp;
	if ( ! $json ) continue;

	//.. 手動で指定
	if ( ANNOT[ $id ] ) {
		_regist_annot_term( $id, ANNOT[ $id ] );
		continue;
	}

	//.. inchikey検索でヒットしたもの
	if ( INCHIKEY_HIT[ $id ] ) {
		_regist_annot_term( $id, INCHIKEY_HIT[ $id ] );
		continue;
	}

	//.. 名前
	$name = $json->name;
	$name_list = [ $name, _cpt( $name ) ];

	//.. synnonyms
	$name_list[] = $json->pdbx_synonyms;
	foreach ( (array)explode( ';', $json->pdbx_synonyms ) as $s ) {
		$s = trim( $s );
		if ( strlen( $s ) < 4 ) continue;
		$name_list[] = _cpt( $s );
		$name_list[] = $s;
	}

	//.. synnonyms コンマ区切り
	foreach ( (array)explode( ', ', $json->pdbx_synonyms ) as $s ) {
		$s = trim( $s );
		if ( strlen( $s ) < 4 ) continue;
		if (
			$s == 'hydrolyzed' ||
			$s == 'phosphorylated' ||
			_instr( ' form', $s )
		)
			continue;
		$name_list[] = _cpt( $s );
		$name_list[] = $s;
	}

	//.. 名前っぽいID
	foreach( [ 'DailyMed', 'GeneExp_Atlas', 'Selleck' ] as $k ) {
		foreach ( (array)IDMAP[ $id ][ $k ] as $s ) {
			if ( strlen( $s ) < 4 ) continue;
			$name_list[] = _cpt( $s );
			$name_list[] = $s;
		}
	}

	//.. bird
	if ( CHEM2PRD[ $id ] ) {
		$name_list[] = PRD_INFO[ CHEM2PRD[ $id ] ][1];
//		_m( PRD_INFO[ CHEM2PRD[ $id ] ][1] );
	}
	
	//.. "ion" 削除してみる
	if ( strtolower( substr( $name, -4 ) ) == ' ion' ) {
		$s = preg_replace(
			[ '/ Ion$/i', '/ \([IiVv]+\)/' ] ,
			[ '', '' ],
			$name
		);
		$name_list[] = _cpt( $s );
		$name_list[] = $s;
	}

	//.. ハイフン削除してみる
	if ( _instr( '-', $name ) ) {
		$s = strtr( $name, ['-' => ' ' ] );
		$name_list[] = _cpt( $s );
		$name_list[] = $s;
	}

	//.. alha- beta- gamma- D- L- を消してみる
	$n = trim( _reg_rep( $name, [
		'/\b-?(D-|L-|Alpha-|Beta-|Gamma-|[0-9\',]+-)/' => ' ' ,
		'/ +/' => ' ' 
	]), ' ,-' );
	$name_list[] = _cpt( $n );
	$name_list[] = $n;

	//.. まとめ
	_regist( $id, $name_list );
//	if ( $id == 'MG' ) {
//		_pause([ $id, $regist[$id] ]);
//		_pause( $name_list );
//	}
}
_regist_save();

