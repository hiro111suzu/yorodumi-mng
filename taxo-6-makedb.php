<?php
require_once( "commonlib.php" );
require_once( 'taxo-common.php' );

define( 'ANNOT', _tsv_load2( FN_TAXO_ANNOT ) );
define( 'HOST_INFO', _json_load( DN_PREP. '/taxo/virus2host.json.gz' ) );
$o_id2parent = new cls_sqlite( FN_ID2PARENT );
$o_id2name   = new cls_sqlite( FN_ID2NAME );
$o_taxostr   = new cls_sqlite( 'taxostr' );


define( 'JNAME', _json_load( DN_DATA. '/taxo/taxojname.json.gz' ) );
$d = [];
foreach ( _json_load2( DN_DATA. '/taxo/taxoicon.json' ) as $k => $v ) {
	$d[ $k ] = explode( ',', $v->j )[0] ;
}
define( 'ICON_JNAME', $d );

//. prep DB

$sqlite = new cls_sqlw([
	'fn' => 'taxo', 
	'cols' => [
		'key UNIQUE' ,
		'name' ,
		'json1' ,
		'json2' ,
		'line COLLATE NOCASE' ,
		'kw COLLATE NOCASE' ,
		'emdb INTEGER' ,
		'pdb INTEGER' ,
		'sasbdb INTEGER' ,
	],
	'new' => true ,
	'indexcols' => [ 'name', 'emdb', 'pdb', 'sasbdb' ],
]);

//. main

_count();
foreach ( _json_load( FN_ID2NAME_JSON ) as $key => $val ) {
	$id = '';
	$name = '';
	if ( _numonly( $key ) == $key ){
		$id = $key; 
		$name = _id2name( $id );
	}
	if ( ! $name )
		$name = $val[0];

	//.. 名称
	$name_ = strtr( $name, [ ' ' => '_', '/' => '_' ] );
	$name_low = strtolower( $name );

	//.. 種名、属名、id
	$name_shu = '';
	$name_zoku = '';
	if ( ! _instr( 'virus', $name_low ) && ! _instr( 'phage', $name_low ) ) {
		list( $a, $b ) = explode( ' ', $name, 3 );
		$name_shu ="$a $b";
		$name_zoku = $a;
	}

	//.. lines
	$lines = [];
	if ( $id )
		$lines = _get_lineage( $id );
	if ( ! $lines && $name_shu ) {
		$lines = _get_lineage( _name2id( $name_shu ) );
	}
	if ( ! $lines && $name_zoku ) {
		$lines = _get_lineage( _name2id( $name_zoku ) );
	}
//	_pause( "$name: " . implode(  ' > ', $lines ) );

	//.. other names
	$oname = _id2name_all( $id );
//	_pause( "$name: " . json_encode( $oname ) );

	//.. type
	$type = 'unknown';
	if ( _instr( 'virus', $name ) ){
		$type = 'virus';
	} else foreach ( ANNOT['linematch'] as $w => $tp ) {
		if ( ! in_array( $w, (array)$lines ) ) continue;
		$type = $tp;
		break;
	}

	//.. thermophil
	$thermo = '';
	foreach ( ANNOT['thermo'] as $k => $v ) {
		if ( ! _name_match( $k, $name ) ) continue;
		$thermo = 1;
	}

	//.. short 一言説明
	$annot_e = [];
	$annot_j = [];
	foreach ( ANNOT[ $type == 'virus' ? 'short_virus' : 'short' ] as $k => $v ) {
		if ( ! _name_match( $k, $name ) ) continue;
		list( $j, $e ) = explode( '|', $v );
		$annot_e[] = $e;
		$annot_j[] = $j;
	}

	//... 部分一致 (現状はウイルスのみ)
	foreach ( (array)ANNOT[ $type == 'virus' ? 'short_part_virus' : 'short_part' ] as $k => $v ) {
		if ( ! _instr( $k, $name ) ) continue;
		list( $j, $e ) = explode( '|', $v );
		$annot_e[] = $e;
		$annot_j[] = $j;
		break;
	}

	//... line match shortだけで利用, wikipe_nameより優先度下
	$short_e2 = '';
	$short_j2 = '';
	foreach ( ANNOT['linematch_comment'] as $w => $tp ) {
		if ( ! in_array( $w, (array)$lines ) ) continue;
		list( $j, $e ) = explode( '|', $tp );
		if ( !$short_e2 ) $short_e2 = $e;
		if ( !$short_j2 ) $short_j2 = $j;
	}	

	//... 日英辞書から
	$ja_name = ANNOT['ng_jname'][$name] ? [] :
		_uniqfilt( explode( ', ', JNAME[ $name ] ?: JNAME[ $name_shu ] ) );

	//... short一つ選ぶ、英語
	$short_en = '';
	foreach ( _uniqfilt( array_merge(
		(array)$oname['gc'], (array)$oname['c'], $annot_e
	)) as $n ) {
		if ( strtolower( $n ) == strtolower( $name ) ) continue;
		$short_en = $n;
		break;		
	}

	//... short一つ選ぶ、日本語
	$short_ja = '';
	foreach ( _uniqfilt( array_merge(
		$annot_j, $ja_name, [ ICON_JNAME[ $name ] ]
	)) as $n ) {
		if ( ANNOT['ng_jshort'][ $n ] ) continue; //- 一般名の代表としてふさわしくない名前
		$a = explode( '・', $n );
		if ( count( $a ) == 2
			&& preg_match("/^[ァ-ヶー]+$/u", $a[0] )
			&& preg_match("/^[ァ-ヶー]+$/u", $a[1] )
		) continue;
		$a = explode( '. ', $n );
		if ( count( $a ) == 2
			&& preg_match("/^[A-Za-z]+$/u", $a[0] )
			&& preg_match("/^[A-Za-z]+$/u", $a[1] )
		) continue;

		$short_ja = $n;
		break;
	}
//	_pause( "$name: " . _imp( $en ) . ' / ' . _imp( $ja ) );

	//.. icon
	$icon = ANNOT['icon'][ $name_ ]
		? $name_
		: ANNOT['icon_zoku'][ $name_zoku ]
	;

	//.. kw
	$kw = array_merge( $annot_e, $annot_j, $ja_name ) ;
	foreach ( (array)$oname as $n )
		$kw = array_merge( $kw, $n );

	//.. host
	$host = [];
	if ( $type == 'virus' ) {
		$host = HOST_INFO[ $key ];
		foreach ([ 'n', 'c', 'gc', 's', 'gs', 'eq' ] as $t ) {
			foreach( (array)$oname[$t] as $n ) {
				$n = strtolower( $n );
				foreach ([
					'human'			=> 'Homo sapiens' ,
					'escherichia'	=> 'Escherichia coli' ,
					'salmonella'	=> 'Salmonella sp' ,
					'porcine'		=> 'Sus scrofa' ,
					'bovine'		=> 'Bos taurus' ,
					'canine'		=> 'Canis lupus' ,
					'feline'		=> 'Felis catus' ,

				] as $k => $v ) {
					if ( strpos( $n, "$k " ) !== 0 ) continue;
					 $host[] = $v;
					 break 3;
				}
			}
		}
		$host = _uniqfilt( $host );
//		if ( $host )  _m( "$name: ". _imp( $host ) );
	}

	//.. set
	$sqlite->set([
		$key ,
		$name ,
		_as_json([
			'ty' => $type, //- タイプ
			'en' => $short_en , //- 英語
			'e2' => $short_e2 ,
			'ja' => $short_ja, //- 日本語
			'j2' => $short_j2 ,
			'ic' => $icon, 	//- アイコン
			'ei' => ANNOT['emdb_img'][$name] , //- emdbイメージ
			'wi' => ANNOT['wikipe_img'][$name] , //- wikipeイメージ
			'th' => $thermo, //- 好熱
			'id' => $id,
			'ho' => $host ,
//			'hoge' => 'fuga' ,
		]) ,
		_as_json([
			'jn' => $ja_name , 
			'aj' => $annot_j ,
			'ae' => $annot_e ,
			'on' => $oname,
		]) ,
		__imp( array_reverse( (array)$lines ) ),
		__imp( _uniqfilt( $kw ) ),
		$o_taxostr->cnt( "db='e' AND taxo=". _quote( $key ) ) ?: 0 ,
		$o_taxostr->cnt( "db='p' AND taxo=". _quote( $key ) ) ?: 0 ,
		$o_taxostr->cnt( "db='s' AND taxo=". _quote( $key ) ) ?: 0 ,
	]);
	if ( _count( 1000, 0 ) ) break;
}

//- DB終了
$sqlite->end();
_cnt();
