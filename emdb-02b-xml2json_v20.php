emdb xml => json

<?php
include "commonlib.php";
define( 'DB_TYPE', 'emdb' );
include "common-xml2json.php";

//. init
//.. pre変換文字列
define( 'REP_PRE', _rep_prep( <<<'EOD'

>[\n\r ]+<					|	><			//- 空白改行だけの要素
[\n\r\t]+					|	' '			//- 改行などを空白に
  +							|	' '			//- 複数連続の空白は1個にする
<\?.+?\?>					|				//- xml宣言消す
units=".+?"(\/?>)			|	$1			//- 単位を消す(EMDBのみ)

 xmlns:xsi=".+?"			|				//- xsiへのリンクは削除
 xsi:type=".+?"				|				//- xsiへのリンクは削除
 xsi:schemaLocation="(.+?)"	|	

// <\/?macromolecule_list>		|			//- macromolecule_listタグは無し
// <\/?supramolecule_list>		|			//- 同上
<\/?citation_list>			|			//- 同上
<\/?pdb_list>				|			//- 同上
<\/?emdb_list>				|			//- 同上
<\/?authors_list>			|			//- 同上

<\/?structure_determination_list>	|
<\/?specimen_preparation_list>	|
<\/?microscopy_list>		|	
<\/?image_recording_list>	|	
<\/?software_list>			|	
<\/?contour_list>			|	
<\/?modelling_list>			|	



 published="(.+?)">			|	><published>$1</published>
<author order="[0-9]+">		|	<author>	//- authorの番号外し、順番を信用する
<pdb_reference id=".+?">	|	<pdb_reference>
<contour primary="(.+?)">	|	<contour><primary>$1</primary>

<external_references type="(.+?)">(.+?)<\/external_references>	|	<ref_$1>$2</ref_$1>

<organism ncbi="(.+?)">		|	<ncbi_tax_id>$1</ncbi_tax_id><organism>

<(contourLevel) source="(.+?)">(.+?)<	|	<$1_source>$2</$1_source><$1>$3<
_source database="(.+?)">	|	_source><database>$1</database>



">([^<]+?)<					|	"><_v>$1</_v><	//- 属性がある要素の<_v>タグ付け


}					|	_|_			//- }を変えておく

>(na|NA|n\/a)<	|	><	
EOD
));

/*
define( 'REP_PRE', _rep_prep( <<<'EOD'

>[\n\r ]+<					|	><			//- 空白改行だけの要素
[\n\r\t]+					|	' '			//- 改行などを空白に
  +							|	' '			//- 複数連続の空白は1個にする
<\?.+?\?>					|				//- xml宣言消す
units=".+?"(\/?>)			|	$1			//- 単位を消す(EMDBのみ)



<file (.+?)>(.+?)<\/file>	|	<file $1 name="$2" />

<(sciSpeciesName|expSystem|hostSpecies) ncbiTaxId="(.+?)">(.+?)<	|	<ncbiTaxId>$2</ncbiTaxId><$1>$3<

<externalReference type="(.+?)">(.+?)<\/externalReference>	|	<ref_$1>$2</ref_$1>
<externalReference type="pubmed"\/>							|	

<(contourLevel) source="(.+?)">(.+?)<	|	<$1_source>$2</$1_source><$1>$3<

">([^<]+?)<			|	"><_v>$1</_v><	//- 属性がある要素の<_v>タグ付け
<\/?sampleComponentList>	|			//- sampleComponentListタグは無し
<\/?fittedPDBEntryIdList>	|			//- 同上
<\/?pdbEntryIdList>			|			//- 同上

<\/?(mask|figure|slice|fsc)Set\/?>				|

}					|	_|_			//- }を変えておく

>(na|NA|n\/a)<	|	><	
EOD
));
*/

//.. 個別のpre変換
/*
$ptcl_rep_pre = [
	5118 => _rep_prep('
		 Harrison, SC<				|	 Harrison SC<
	') ,

	//- 3434: 不等号がうまくパースされない
	3434 => _rep_prep('
		&lt;		|	 &lt; 
	')
];
*/
//.. 数値しなかいタグ (未使用)
/*
$s = '';
foreach ( _file( DN_PREP . '/emdb_xml_numtags.txt' ) as $l ) {
	$s .= "(\"$l\":)\"(-?[0-9\\.]+?)\.?\"	|	$1$2\n";
}
*/
//.. post変換文字列
define( 'REP_POST', _rep_prep( <<<'EOD'
":(\.[0-9])				|	":0$1
EOD
. STR_REP_POST
));

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
'saccharide' ,
'tomography_processing',
'startup_model' ,
'grant_reference' ,
];
sort( $tags );
define( 'MULTI_TAG', $tags );

_prep_multitag( MULTI_TAG );

//.. 属性値をタグに
$atr_rep = [];
foreach ([
	'name' => 'synonym' ,
	'code' => 'supersedes' ,
	'film_or_detector_model' => 'category' ,
	'recombinant_organism'	=> 'ncbi' ,
	'sci_species_name'		=> 'ncbi' ,
] as $tag => $atr ){
	$atr_rep[ "/<$tag $atr=\"(.+?)\">(.+?)<\/$tag>/" ]
		= "<$tag>$2</$tag><$tag.$atr>$1</$tag.$atr>"
	;
}
//_kvtable( $atr_rep );

//. convert
$bad_atr = [];
_count();
foreach ( _idlist( 'emdb' ) as $id ) {
	if ( _count( 'emdb', 0 ) ) _pause();
	_cnt( 'total' );
	$fn_in  = _fn( 'emdb_xml3', $id );
	$fn_out = _fn( 'emdb_new_json', $id );
	if ( FLG_REDO )
		_del( $fn_out );
	if ( _same_time( $fn_in, $fn_out ) ) continue;

	//.. プレプレ処理
	$cont = _reg_rep(
		file_get_contents( $fn_in ) ,
		$atr_rep
	);

	//.. プレ処理
	$cont = _pre_conv(
		$cont ,
		$ptcl_rep_pre[ $id ]
	);
//	_pause();
	//.. ポスト処理
	_post_conv( compact( 'cont', 'id', 'fn_in', 'fn_out' ) );
}

//.. 集計
_line( '集計' );
_cnt();

//. なくなったのを削除
_delobs_emdb( 'emdb_new_json' );

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
foreach ( _idloop( 'emdb_new_json', '複数値タグのチェック' ) as $fn ) {
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

_json_save( DN_PREP. '/emdbxml3_tagtypes.json', $tag_types );

//.. 全ID分あるかチェック
$no_v3 =[];
foreach ( _idlist( 'emdb' ) as $id ) {
	if ( file_exists( _fn( 'emdb_new_json', $id ) ) ) continue;
	$no_v3[] = $id;
}
$no_v3
	? _m( 'V3-jsonがないエントリ: '. _imp( $no_v3 ), 'red' )
	: _m( 'すべてv3-jsonがある', 'blue' )
;

//. end
_end();
