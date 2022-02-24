emdb xml => json

<?php
include "commonlib.php";
define( 'DB_TYPE', 'emdb' );
include "common-xml2json.php";

//. init
//.. pre変換文字列
define( 'REP_PRE',
array_merge_recursive( _rep_prep_attr([
	'xmlns:xsi'				=> '' , //- xsiへのリンク
	'xsi:type'				=> '' , //- xsiへのリンク
	'xsi:schemaLocation'	=> '' , //- xml schemaへのリンク
	'units'					=> '$1.units' ,
	'resolution res_type'	=> '$1.type' ,
	'ncbi'					=> '$1.ncbi' ,
//	'external_references type' => 'ref_$3' , これじゃだめ
	'published'				=> [ '$1' ] ,
	'contour primary'		=> 'primary' ,
	'database' 				=> [ 'database' ] ,

	'name synonym' 			=> '$1.synonym',
	'code supersedes' 		=> 'supersedes' ,
	'film_or_detector_model category' => 'category' ,
])
,
_rep_prep( <<<'EOD'

>[\n\r ]+<					|	><			//- 空白改行だけの要素
[\n\r\t]+					|	' '			//- 改行などを空白に
  +							|	' '			//- 複数連続の空白は1個にする
<\?.+?\?>					|				//- xml宣言消す
<external_references type="(.+?)">(.+?)<\/external_references>	|	<ref_$1>$2</ref_$1>
//- published="(.+?)">			|	><published>$1</published>

<author(.*?)>([^<]+?)<			|	<author$1><name>$2</name><
">([^<]+?)<					|	"><_v>$1</_v><	//- 属性がある要素の<_v>タグ付け

}					|	_|_			//- }を変えておく

>(na|NA|n\/a)<	|	><	
EOD
))
);

//.. post変換文字列
define( 'REP_POST', _rep_prep( <<<'EOD'
":(\.[0-9])				|	":0$1
EOD
. STR_REP_POST
));

//.. 削除するタグ
_prep_deltag( <<<EOD
citation_list
pdb_list
emdb_list
authors_list
structure_determination_list
specimen_preparation_list
microscopy_list
image_recording_list
software_list
contour_list
modelling_list
EOD
);

//.. 削除する属性
/*
_prep_delatr( <<<EOD
xmlns:xsi
xsi:type
xsi:schemaLocation
EOD
);
*/

//.. 複数値タグ

$tags = [
'additional_map' ,
'angle' ,
'component' ,
'half_map' ,
'initial_model' ,
'natural_host' ,
'natural_source' ,
'recombinant_expression' ,
'secondary_citation' ,
'segmentation' ,
'shell' ,
'support_film' ,
'virus_shell' ,
'macromolecule' ,
'pdb_reference' ,
'emdb_reference' ,
'structure_determination' ,
'image_recording' ,
'software' ,
'contour' ,
'modelling' ,
'author' ,
'cell_supramolecule' ,
'chain' ,
'chain_id' ,
'complex_supramolecule' ,
'dna' ,
'fiducial_marker' ,
'figure' ,
'fsc_curve' ,
'helical_microscopy' ,
'ligand' ,
'organelle_or_cellular_component_supramolecule' ,
'other_macromolecule' ,
'protein_or_peptide' ,
'ref_GO' ,
'ref_INTERPRO' ,
'rna' ,
'single_particle_microscopy' ,
'single_particle_preparation' ,
'singleparticle_processing' ,
'slice' ,
'subtomogram_averaging_microscopy' ,
'subtomogram_averaging_processing' ,
'tomography_microscopy' ,
'virus_supramolecule' ,
'tomography_processing',
'startup_model' ,
'grant_reference' ,
];
sort( $tags );
define( 'MULTI_TAG', $tags );

_prep_multitag( MULTI_TAG );

//.. 属性値をタグに
/*
$atr_rep = [];
foreach ([
	'name' => 'synonym' ,
	'code' => 'supersedes' ,
	'film_or_detector_model' => 'category' ,
] as $tag => $atr ){
	$atr_rep[ "/<$tag $atr=\"(.+?)\">(.+?)<\/$tag>/" ]
		= "<$tag>$2</$tag><$tag.$atr>$1</$tag.$atr>"
	;
}
*/
//_kvtable( $atr_rep );

//. convert
$bad_atr = [];
_count();
foreach ( _idlist( 'emdb' ) as $id ) {
//	if ( substr( $id, -2 ) != '00' ) continue;
//	if ( ! in_array( $id, [ 13936 ] ) ) continue;
//	if ( _count( 'emdb', 0 ) ) _pause();
	_cnt( 'total' );
	$fn_in  = _fn( 'emdb_xml', $id );
//	$fn_in_nv = _fn( 'emdb_xml', $id );
//	if ( ! file_exists( $fn_in ) && file_exists( $fn_in_nv ) ) 
//		$fn_in = $fn_in_nv;

	$fn_out = _fn( 'emdb_json', $id );
	if ( FLG_REDO )
		_del( $fn_out );
	if ( _same_time( $fn_in, $fn_out ) ) continue;

	//.. プレプレ処理
//	$cont = _reg_rep(
//		$atr_rep
//	);

	//.. プレ処理
	$cont = _pre_conv(
		file_get_contents( $fn_in ) ,
		$ptcl_rep_pre[ $id ]
	);

	//.. ポスト処理
	_post_conv( compact( 'cont', 'id', 'fn_in', 'fn_out' ) );
}

//.. 集計
_line( '集計' );
_cnt();

//. なくなったのを削除
_delobs_emdb( 'emdb_json' );

//. 複数値タグのチェック

//.. 全 jsonチェック
/*
$tag_types = [
	'pdbChainId'=> [],
	'pdbEntryId'=> [],
	'refGo'=> [],
	'refInterpro'=> [],
	'refUniProt'=> [],
	'shell'=> [],
];
*/

$tag_types = [];
_count();
$multi_tag_found = [];
foreach ( _idloop( 'emdb_json', '複数値タグのチェック' ) as $fn ) {
	$id = _fn2id( $fn );
	if ( _count( 'emdb', 0 ) ) break;
	if ( ! ctype_digit( $id ) ) {
		_problem( "数字じゃないID: $id: $fn" );
	}
	_mt( _json_load2( $fn ) );
}

//.. 多重走査するための関数
function _mt( $obj, $name = 'root' ) {
	global $multi_tag_found, $id, $tag_types, $bad_atr;
//	if ( count( $obj ) == 0 ) {
//		_problem( "$id - \"$name\" 値なし"  );
//		return;
//	}

	foreach ( $obj as $key => $val ) {
		if ( $key == '_v' ) {
			if ( ! $bad_atr[ $name ] ) {
				_problem( "$id: $name -> _v: 属性の処理が必要" );
				$bad_atr[ $name ] = true;
			}
		}
		
		if ( is_array( $val ) ) {
			$multi_tag_found[ $key ] = true;
			if ( ! in_array( $key, MULTI_TAG ) ) {
				_problem( "$id: タグ名 \"$key\" が配列になっている（複数要素）" );
			}
		}

		//- 集計
		$type = gettype( $val );
		if ( $type == 'integer' || $type == 'double' )
			$type = 'numeric';
		++ $tag_types[ $key ][ $type ];

		//... より深い階層
		if ( $type == 'object' )
			_mt( $val, $key );

		if ( $type == 'array' ) foreach( $val as $child ) {
			if ( is_object( $child ) )
				_mt( $child, $key );
		}
	}
}

//.. 集計

$multi_tag_found = array_keys( $multi_tag_found );
sort( $multi_tag_found );

if ( MULTI_TAG != $multi_tag_found ) {
	foreach ( $multi_tag_found as $t ) {
		if ( ! in_array( $t, MULTI_TAG ) )
			_problem( "$t - 新しい、複数値タグ!!!" );
	}

	foreach ( MULTI_TAG as $t ) {
		if ( ! in_array( $t, $multi_tag_found ) )
			_problem( "$t - 無くなった、複数値タグ？？？" );
		if ( ! $tag_types[ $t ] )
			_problem( "$t - 存在しないタグ!!!" );
	}

} else {
	_m( "複数値タグ処理、問題なし" );
}

ksort( $tag_types );
foreach ( $tag_types as $k => $v ) {
	$cnt = count( $v );
	if ( $cnt != 1 )
		_m( "$k: $cnt types" );
}

_json_save( DN_REPORT. '/emdbxml3_tagtypes.json', $tag_types );

//.. 全ID分あるかチェック
/*
$no_v3 =[];
foreach ( _idlist( 'emdb' ) as $id ) {
	if ( file_exists( _fn( 'emdb_json', $id ) ) ) continue;
	$no_v3[] = $id;
}
$no_v3
	? _m( 'V3-jsonがないエントリ: '. _imp( $no_v3 ), 'red' )
	: _m( 'すべてv3-jsonがある', 'blue' )
;
*/
//. end
_end();


//. func
//.. _json_rep: s_emdb_json3_repのテスト -> 正式採用
function _json_rep( &$json ) {
	//- 試しに変更してみる、確定したらxml2jsonで採用

	//... structure_determination
	foreach ( (array)$json->structure_determination as $c ) {
		foreach ([
			'preparation', 'microscopy', 'processing'
		] as $categ ) foreach ([
			'crystallography_' ,
			'helical_' ,
			'single_particle_' ,
			'singleparticle_' ,
			'subtomogram_averaging_' ,
			'tomography_' ,
		] as $type ) {
			$tag = $type. $categ;
			if ( $c->$tag ) {
				$c->$categ = is_array( $c->$tag ) ? $c->$tag : [ $c->$tag ];
				unset( $c->$tag );
			}
		}
	}

	//... supramolecule_list
	if ( $json->sample->supramolecule_list ) {
		$json->sample->supramolecule = [];
		foreach ( $json->sample->supramolecule_list as $tag => $sup_a ) {
			foreach ( is_array( $sup_a ) ? $sup_a : [ $sup_a ] as $sup ) {
				$sup->supmol_type = strtr( $tag, [ '_supramolecule' => '' ] );
				$json->sample->supramolecule[] = $sup;
			}
		}
		unset( $json->sample->supramolecule_list );
	}

	//... macromolecule_list
	if ( $json->sample->macromolecule_list ) {
		$json->sample->macromolecule = [];
		foreach ( $json->sample->macromolecule_list as $tag => $mac_a ) {
			foreach ( is_array( $mac_a ) ? $mac_a : [ $mac_a ] as $mac ) {
				$mac->macmol_type = strtr( $tag, [ '_macromolecule' => '' ] );
				$json->sample->macromolecule[] = $mac;
			}
		}
		unset( $json->sample->macromolecule_list );
	}

	//... tilt_list を消す
	foreach ( $json->structure_determination[0]->microscopy as &$c ) {
		if ( ! $c->tilt_list ) continue;
		$c->tilt_angle = implode( ', ', $c->tilt_list->angle );
		unset( $c->tilt_list );
	}
	unset( $c );

	//... fiducial_markers_list を消す
	foreach ( $json->structure_determination[0]->preparation as &$c ) {
		if ( ! $c->fiducial_markers_list ) continue;
		$c->fiducial_marker = $c->fiducial_markers_list->fiducial_marker;
		unset( $c->fiducial_markers_list );
	}
	unset( $c );

	//... shell_listを消す
	foreach ( $json->structure_determination[0]->processing as &$c ) {
		if ( $c->crystallography_statistics->shell_list ) {
			$c->crystallography_statistics->shell
				= $c->crystallography_statistics->shell_list->shell;
			unset( $c->crystallography_statistics->shell_list );
		}
	}
	unset( $c );

	//... recombinant_expressionの冗長を解消
	foreach ( $json->sample->supramolecule as &$c1 ) {
		if ( ! $c1->recombinant_expression
			|| count( $c1->recombinant_expression ) < 2
		) continue;
		foreach ( array_slice( $c1->recombinant_expression, 1 ) as $num => $c2 ) {
			if ( $c2 != $c1->recombinant_expression[0] ) continue;
			unset( $c1->recombinant_expression[ $num + 1 ] );
		}
	}
	unset( $c1 );

	//... end
	return $json;
}


