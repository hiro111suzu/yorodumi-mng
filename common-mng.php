<?php
/*
共通スクリプト
	+ cgi
	+ mng
*/

//error_reporting(E_ERROR | E_WARNING | E_PARSE);
//. 設定
if ( ! defined( 'FLG_MNG' ) ) 
	define( 'FLG_MNG', false ); //- webサービスからならfalse

//. ディレクトリ定義
define( 'DN_EMDB_MED'	, DN_DATA . '/emdb/media' );
define( 'DN_PDB_MED'	, DN_DATA . '/epdb/media' );


//. _fn関数用データ
$_filenames = [
	//.. EMDB
	'emdb_json'		=> DN_DATA      . '/emdb/json/<id>.json.gz' ,
	'emdb_json3'	=> DN_DATA      . '/emdb/json3/<id>.json.gz' ,
	'emdb_add' 		=> DN_DATA		. '/emdb/add/emd-<id>-add.json' ,
	'emdb_med'		=> DN_EMDB_MED 	. '/<id>' ,
	'movinfo'		=> DN_EMDB_MED	. '/<id>/movieinfo.json' ,
	'mapinfo'		=> DN_EMDB_MED	. '/<id>/mapinfo.json' ,

	'emdb_snap'		=> DN_EMDB_MED	. '/<id>/snap<s1>.jpg' ,	// s1:size+num 
	'emdb_mp4'		=> DN_EMDB_MED	. '/<id>/movie<s1>.mp4' ,	// s1:size+num
	'emdb_webm'		=> DN_EMDB_MED	. '/<id>/movie<s1>.webm' ,	// s1:size+num
	'jvxl'			=> DN_EMDB_MED	. '/<id>/ym/o1.zip' ,
	'session'		=> DN_EMDB_MED	. '/<id>/s<s1>.py' ,
	'session-old'	=> DN_EMDB_MED	. '/<id>/session<s1>.py' ,

	//.. epdb
	'epdb_json'		=> DN_DATA		. '/epdb/json/<id>.json.gz' ,
	'pdb_add'		=> DN_DATA      . '/epdb/add/<id>-add.json' ,
	'pdb_med'		=> DN_PDB_MED	. '/<id>' ,
	'pdb_snap'		=> DN_PDB_MED	. '/<id>/snap<s1>.jpg' ,	// size+num 
	'pdb_mp4'		=> DN_PDB_MED	. '/<id>/movie<s1>.mp4' ,	// size+num
	'pdb_webm'		=> DN_PDB_MED	. '/<id>/movie<s1>.webm' ,	// size+num

	//.. allpdb
	'pdb_json'		=> DN_DATA 		. '/pdb/json/<id>.json.gz' ,
	'pdb_plus'		=> DN_DATA 		. '/pdb/plus/<id>.json.gz' ,
	'pdb_img'		=> DN_DATA 		. '/pdb/img/<id>.jpg' ,
	'pdb_imgasb'	=> DN_DATA 		. '/pdb/img_asb/<id>_<s1>.jpg' ,
	'pdb_imgdep'	=> DN_DATA 		. '/pdb/img_dep/<id>.jpg' ,


	'qinfo'			=> DN_DATA		. '/pdb/qinfo/<id>.json' ,

	//.. chem
	'chem_cif'		=> DN_DATA		. "/chem/cif/<id>.cif.gz" ,
	'chem_json'		=> DN_DATA		. "/chem/json/<id>.json.gz" ,
	'chem_img'		=> DN_DATA		. "/chem/img/<id>.gif" ,
	'chem_img2'		=> DN_DATA		. "/chem/img/<id>.svg" ,
	
	//.. sas
	'sas_json'		=> DN_DATA		. '/sas/json/<id>.json.gz' ,
	'sas_img'		=> DN_DATA		. '/sas/img/<id>.jpg' ,
];

//. ファイル読み書き系
//.. _json save / load
function _json_save( $fn, $data ) {
	return _gzsave(
		_prepfn( $fn ) ,
		json_encode( $data, JSON_UNESCAPED_SLASHES )
	);
}

function _json_load( $fn, $opt = true ) {
	$fn = _prepfn( $fn ); 
	if ( ! file_exists( $fn ) ) return;
	return json_decode( _gzload( $fn ), $opt );
}

//- オブジェクトで返す
function _json_load2( $fn ) {
	return _json_load( $fn, false );
}

function _to_json( $data ) {
	return json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
}

function _json_pretty( $data ) {
	return json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES 
		| JSON_PRETTY_PRINT);
}

//.. _json_cache: 使ったらキャッシュしておくjson load
$json_cache = [];
function _json_cache( $fn ) {
	global $json_cache;
	$fn = realpath( $fn );
	if ( $json_cache[ $fn ] == '' )
		$json_cache[ $fn ] = _json_load2( $fn );
	return $json_cache[ $fn ];
}

//.. _data save /load: serializeデータの読み書き
function _data_save( $fn, $data ) {
	return _gzsave( $fn, serialize( $data ) );
}

function _data_load( $fn ) {
	if ( file_exists( $fn ) )
		return unserialize( _gzload( $fn ) );
}

//.. _tsv_load2; 2階層になるバージョン
function _tsv_load2( $fn ) {
	if ( ! file_exists( $fn ) ) {
		_problem( "ファイルがない: $fn" );
		return;
	}
	$ret = [];
	$current_categ = 'undefined';
	foreach ( _file( $fn ) as $l ) {
		if ( substr( $l, 0, 2 ) ==  '//' ) continue;
		$l = preg_replace( '/[ \t]\/\/.*$/', '', $l ); //- コメント消し
		list( $key, $val ) = explode( "\t", $l, 3 );
		$key = trim( $key );
		if ( $key == '' || $key == '..' || $key == '...' ) continue;
		$val = substr( $val, 0, 1 ) == '"' ? trim( $val, '"' ) : trim( $val );
		if ( $key == '.' )
			$current_categ = $val;
		else
			$ret[ $current_categ ][ $key] = $val;
	}
	return $ret;
}

//.. _tsv_load3; 3階層になるバージョン
function _tsv_load3( $fn ) {
	if ( ! file_exists( $fn ) ) {
		_problem( "ファイルがない: $fn" );
		return;
	}
	$ret = [];
	$current_categ1 = 'undefined';
	$current_categ2 = 'undefined';
	foreach ( _file( $fn ) as $l ) {
		if ( substr( $l, 0, 2 ) ==  '//' ) continue;
		$l = preg_replace( '/[ \t]\/\/.*$/', '', $l ); //- コメント消し
		list( $key, $val ) = explode( "\t", $l, 3 );
		$key = trim( $key );
		if ( $key == '' || $key == '...' ) continue;
		$val = substr( $val, 0, 1 ) == '"' ? trim( $val, '"' ) : trim( $val );
		if ( $key == '.' )
			$current_categ1 = $val;
		else if ( $key == '..' )
			$current_categ2 = $val;
		else
			$ret[ $current_categ1 ][ $current_categ2 ][ $key] = $val;
	}
	return $ret;
}

//.. _tsv_save 
function _tsv_save( $fn, $data ) {
	$out = '';
	foreach ( $data as $name => $val )
		$out .= "$name\t$val\n";
	return file_put_contents( $fn, $out );
}

//.. _tsv_save2 階層式
function _tsv_save2( $fn, $data ) {
	$out = '';
	foreach ( $data as $section => $child ) {
		$out .= ".\t$section\n";
		foreach ( $child as $name => $val )
			$out .= "$name\t$val\n";
		$out .= "\n";
	}
	return file_put_contents( $fn, $out );
}

//.. _prepfn: arrayなら_fn、それ以外はそのまま
//- 例 _json_load([ 'emdb_json', '1003' ]) 
function _prepfn( $s ) {
	return is_array( $s ) ? _fn( $s[0], $s[1] ) : $s;
}

//.. _file
//- file()のラッパー: 改行文字・空行を消す、配列に読み込む
//- 配列として返す
function _file( $s ) {
	return file_exists( $s )
		? file( $s, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES )
		: []
	;
}

//.. _is_gz: 拡張子が .gz ?
function _is_gz( $fn ) {
	return substr( $fn, -3 ) == '.gz';
}

//.. _gzload : 拡張子が.gzなら圧縮解除して読み込み
function _gzload( $fn ) {
	return _is_gz( $fn )
		? implode( '', gzfile( $fn ) )
		: file_get_contents( $fn )
	;
}

//.. _gzsave 
function _gzsave( $fn, $cont ) {
	return file_put_contents( $fn, _is_gz( $fn ) ? gzencode( $cont ) : $cont );
}

//.. _del
//- ファイル削除
function _del() {
	$num = 0;
	foreach ( func_get_args() as $fn ) {
		if ( ! file_exists( $fn ) ) continue;
		unlink( $fn );
		++ $num;
	}
	return $num;
}


//. その他
//.. _reg_rep
function _reg_rep( $in, $array ) {
	return preg_replace(
		array_keys( (array)$array ) ,
		array_values( (array)$array ) ,
		$in
	);
}

//.. _cif_rep: tsvデータによる置換
//- mode: [ lc: 小文字化, reg: 正規表現 ]
function _cif_rep( &$json ){
	foreach ( _tsv_load2( DN_DATA. '/pdb/cif_rep.tsv' ) as $cat_item => $rep ) {
		list( $cat, $item, $mode ) = explode( '.', $cat_item );
		foreach ( (array)$json->$cat as $c ) {
			if ( $c->$item == '' ) continue;
			if ( $mode == 'lc' )
				$c->$item = strtolower( $c->$item );
			$c->$item = $mode == 'reg'
				? _reg_rep( $c->$item, $rep )
				: strtr( $c->$item, $rep )
			;
		}
	}
}

//.. _add_fn: そのスクリプト内だけで使う _fnを追加
/*
function _add_fn( $a ) {
	global $_filenames;
	$_filenames = $a + $_filenames ;
}
*/
//.. _imp: implodeのラッパー
//- コンマ区切りにして返す
//- 配列で受け取っても、引数の羅列で受け取ってもOK
function _imp() {
	return implode( ', ', _armix( func_get_args() ) );
}

//.. _armix: 配列やら変数やらの集まりを一つの配列へ
function _armix( $in ) {
	$ret = [];
	foreach ( array_filter( (array)$in ) as $a ) {
		if ( is_array( $a ) || is_object( $a ) ) {
			$ret = array_merge( $ret, _armix( $a ) );
		} else {
			$ret[] = $a;
		}
	}
	return array_filter( $ret );
}

//.. _uniqfilt: array_unique + array_filter
function _uniqfilt( $in ) {
	return array_unique( array_filter( (array)$in ), SORT_REGULAR );
}

//.. _nn: 最初のnullでない文字列を返す
function _nn() {
	foreach ( func_get_args() as $s )
		if ( $s != '' ) return $s;
}

//.. _ifnn: $s1がnullでなかったら、$s2を返す
function _ifnn( $s1, $s2, $s3 = '' ) {
	return $s1 != '' ? strtr( $s2, [ '\1' => $s1 ] ) : $s3 ;
}

//.. _inlist
//- json.gz形式 リストファイルにあるか
function _inlist( $id, $list ) {
	global $_idlist;
	if ( ! is_array( $_idlist[ $list ] ) ) {
		if ( file_exists( $fn = DN_DATA . "/ids/$list.txt" ) )
			$_idlist[ $list ] = array_fill_keys( _file( $fn ), true );
	}
	//- リストファイルがない場合、テストサーバー上では、die
	if ( TESTSV && !is_array( $_idlist[ $list ] ) )
		die( "No list file: ids/$list.txt" );
	return $_idlist[ $list ][ $id ];
}

//.. _idlist
//- テキストのIDリストからid一覧を得る
function _idlist( $name ) {
	$fn = DN_DATA . "/ids/$name.txt";
	if ( file_exists( $fn ) )
		return _file( $fn );
}

//.. _cif2pdb
//- 簡易型 mmcifコンバータ、水は消す、atomセクションのみ
//- 改行付きのarrayとして返す (file関数で開いたのと同じ状態)
//- $opt: allmodel 
function _cif2pdb( $in, $opt = [] ) {
	_m( 'cif2pdb' );
	//- atomセクション取り出し
	$sec = [];
	$hit = false;
	foreach ( (array)$in as $line ) {
		if ( trim( $line ) == '#' ) {
			if ( $hit )
				break;
			else
				$sec = [];
			continue;
		}
		if ( strpos( $line, '_atom_site.' ) === 0 )
			$hit = true;
		$sec[] = $line;
	}

	//- セクションが見つからない?
	if ( ! $hit ) {
		_m( 'atom_site セクションが見つからない' );
		return [];
	} else {
		_m( 'atom_site セクション発見: ' . count( $sec ) . '行' );
	}
	
	$cname2num = []; //- カラム名
	$num = 0;
	$ret = [];
	$spc = str_repeat( ' ', 20 );
	foreach ( $sec as $line ) {
		//- カラム情報収集
		if ( strpos( $line, '_atom_site.' ) === 0 ) {
			$cname2num[ strtr( trim( $line ), [ '_atom_site.' => '' ] ) ] = $num;
			++ $num;
			continue;
		}
		
		if ( _instr( 'HOH', $line ) ) continue; //- 水は消す

		$d = preg_split( '/ +/', $line );
		if ( count( $d ) < 10 ) continue;
		
		//- モデル1のみ
		if (
			$d[ $cname2num[ 'pdbx_PDB_model_num' ] ] > 1 &&
			! $opt[ 'allmodel' ]
		) 
			continue;

		$out = '';
		foreach ([
			[  1,  6, 'group_PDB'		] , //- ATOM / HETATM
			[  7, 11, 'id'				] , //- atom id
			[ 13, 16, 'label_atom_id'	] , //- type_symbol
			[ 17, 17, 'label_alt_id'	] , //- altloc
			[ 18, 20, 'label_comp_id'	] , //- compid
			[ 22, 22, 'label_asym_id'	] , //- chain-id; (chain-id)
			[ 23, 26, 'label_seq_id'	] , //- seq-id
			[ 27, 27, 'pdbx_PDB_ins_code' ] , //- ins
			[ 31, 38, 'Cartn_x'			] , //- x
			[ 39, 46, 'Cartn_y'			] , //- y
			[ 47, 54, 'Cartn_z'			] , //- z
			[ 55, 60, 'occupancy'		] , //- ocup
			[ 61, 66, 'B_iso_or_equiv'	] , //- temp
			[ 77, 78, 'type_symbol'		] , //- elem
			[ 79, 80, 'pdbx_formal_charge' ] , //- charge
		] as $a ) {
			$v = $d[ $cname2num[ $a[2] ] ];
			if ( $v == '?' || $v == '.' ) continue;
			$len = $a[1] - $a[0] + 1;
			$out = substr( $out . $spc, 0, $a[0] - 1 )
				. substr( $v, 0, $len );
		}
		$ret[] = $out . "\n";
	}
//	print_r( $cname2num );
	return $ret;
}

//.. _numonly 数字だけ取り出す
function _numonly( $s ) {
	return preg_replace( '/[^0-9]/', '', $s );
}

//.. _same_str: 大文字小文字関係なし比較
function _same_str( $s1, $s2 ) {
	return strtolower( $s1 ) == strtolower( $s2 );
}

//.. _quote
function _quote( $s, $q = "'" ) {
	if ( $q == 2 )
		$q = '"';
	return $q. strtr( $s, [ $q => $q.$q ] ). $q ;
}

//.. _paper_id: pubmed-idの代わりのIDを作る
function _paper_id( $title, $journal ) {
	return $title . $journal == ''
			|| strtolower( $title ) == 'to be published'
			|| strtolower( $title ) == 'suppressed'
			|| strtolower( $journal ) == 'suppressed'
		? ''
		: '_' . md5(
			$title .'|'
			. preg_replace( '/[^a-z]/', '', strtolower( $journal ) )
		)
	;
}

//.. _flg_emdb_cryo
function _flg_emdb( $type, $json ) {
	$e = $json->experiment;
	if ( $type == 'cryo' ) {
		$s = _clean_emdb_val( $e->vitrification[0]->cryogenName );
		return $s !=  '' && $s != 'none' && ! _instr( 'stain', $s );
	} else if ( $type == 'stain' ) {
		$s = _clean_emdb_val( $e->specimenPreparation->staining );
		return $s !=  '' && $s != 'none' && ! _instr( 'cryo', $s );
	}
}
function _flg_pdb( $type, $json ) {
	$s = $json->em_specimen[0];
	if ( ! $s ) return false;
	if ( $type == 'cryo' )
		return $s->vitrification_applied == 'YES';
	if ( $type == 'stain' )
		return $s->staining_applied == 'YES';
}

function _clean_emdb_val( $s ) {
	if ( in_array( strtolower( $s ), [ '-', 'none', 'na', 'n/a' ] ) )
		return;
	return trim( $s );
	
}

//.. _reps_wikipe_terms
function _reps_wikipe_terms() {
	return [[
		'/^putative /i' => '' ,
		'/ ,putative/i' => '', 
		'/([0-9]+s |)ribosomal/i' => 'ribosome' ,
		'/ribosome protein( [a-z]+[0-9]+|)/i' => 'ribosomal protein' ,
		'/ribosome rna/i' => 'ribosomal rna'  ,

	], [
		'/ (light|heavy) chain$/i' => '' ,
		'/^(human|mouse|yeast) +/i' => '',
		'/ (from|in|at) .+$/' => '' ,
		'/ holoenzyme.*$/' => '' ,
		'/(large|small|) (component|subunit).*/' => '' ,
		'/^.*type ([iv]+) (protein |)secretion.*$/' => 'type $1 secretion' ,
//		'/\-/' => '', 
		'/^regulation of /' => '',
		'/-(like)$/' => '',
		'/^.*photosystem ([iv]+).*$/' => 'photosystem $1' ,
	], [
		'/ (complex|activity|assembly|binding|domain|family|maintenance|signature.|family signature.process)$/' => '',
		'/ protein.*/' => '' ,
		'/ system.+$/' => ' system'  ,
		'/, .+$/' => '' ,
		'/( |\-)([0-9]{1,2}|i{1,3}|iv|vi?)$/' => ''
	]];
}

//.. _load_trep_tsv
function _load_trep_tsv() {
	$data = [];
	$categ = '_';
	foreach ( _file( '../emnavi/trep.tsv' ) as $line ) {
		list( $key, $ja, $en ) = explode( "\t", $line, 4 );
//		$data[ 'exp' ][] = explode( "\t", $line, 4 );
		$key = trim( $key );
		if ( substr( $key, 0, 2 ) == '//' || $key == '' || $key == '..' ) continue;
		if ( $key == '.' ) {
			$categ = $ja;
			continue;
		}
		if ( $ja )
			$data['ja'][ $categ ][ $key ] = $ja;
		if ( $en )
			$data['en'][ $categ ][ $key ] = $en;
	}
	return $data;
}

//.. _emdb_json3_rep
function _emdb_json3_rep( &$json ) {
	$json->preparation = [];
	$json->microscopy = [];
//	$json->recording = [];
	$json->processing = [];
	foreach ( $json->structure_determination as $c ) {
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
				$json->$categ = array_merge(
					$json->$categ,
					$c->$tag
				);
				unset( $c->$tag );
			}
		}
	}
}

//. abst-class abs_entid
abstract class abs_entid {
	public
		$db, $DB, $id, $did, $DID,
		$cache ,
		$title , 
		$is_em = 'uk'
	;
	
	function __construct( $s = '' ) {
		if ( $s != '' )
			$this->set( $s );
		return $this;
	}
	function __toString() { return (string)$this->did; }

	//.. set
	function set( $str ) {
		//- arrayでセット
		if ( is_array( $str ) ) {
			foreach ( $str as $k => $v )
				$this->$k = $v;
			return $this;
		}
		
		//- get/postから？
		if ( $str == 'get' ) {
			$str = _getpost( 'id' ) . _getpost( 'i' );
//			if ( $str == '' && defined( 'DEFAULT_ID' ) )
//				$str = DEFAULT_ID;
		}
		//- a: 全角英数->半角
		$str = strtolower( mb_convert_kana( $str, 'a' ) );

		//- SAS
		if ( substr( $str, 0, 3 ) == 'sas' ) {
			if ( _instr( '-', $str ) ) {
				$this->set_sasmodel( $str );
			} else {
				$this->set_sas( $str );
			}
			return $this;
		}

		//- 4桁以上の数字
		if ( ctype_digit( $str ) && strlen( $str ) > 3 ) {
			$this->set_emdb( $str );
			return $this;
		}

		//- emdb-xxxx, pdb-xxxx 
		$a = explode( '-', $str );
		if ( $a[1] != '' ) {
			if ( $a[0] == 'pdb'  ) {
				$this->set_pdb( $str );
				return $this;
			}
			if ( $a[0] == 'emdb' || $a[0] == 'emd' ) {
				$this->set_emdb( $str );
				return $this;
			}
			if ( $a[0] == 'chem' || $a[0] == 'chemcomp' ) {
				$this->set_chem( $str );
				return $this;
			}
		}

		//- 先頭の文字から判断 
		$len = strlen( $str );
		if ( $len > 4 ) {
			$id1 = substr( $str, 0, 1 );
			if ( $id1 == 'e' ) {
				$this->set_emdb( $str );
				return $this;
			}
			if ( $id1 == 'p' ) {
				$this->set_pdb( $str );
				return $this;
			}
			if ( $id1 == 'c' ) {
				$this->set_chem( $str );
				return $this;
			}
		}

		//- chemcomp
		if ( $len < 4 ) {
			$this->set_chem( $str );
			return $this;
		}

		//- emdbに直接ヒット
//		if ( _inlist( $str, 'emdb' ) ) {
//			$this->set_emdb( $str );
//			return $this;
//		}

		//- PDB
		$this->set_pdb( $str );
		return $this;
	}

	//.. set_xxxx
	function set_chem( $i ) {
		$i = strtoupper( preg_replace( '/^.*\-/', '', $i ) );
		$this->DB	= 'ChemComp';
		$this->db	= 'chem';
		$this->id	= $i;
		$this->did	= "Chem-$i";
		$this->DID	= "ChemComp-$i";
		return $this;
	}
	function set_emdb( $i ) {
		$i = _numonly( $i ) ?: '?';
		$this->DB	= 'EMDB';
		$this->db	= 'emdb';
		$this->id	= $i;
		$this->did	= "emdb-$i";
		$this->DID	= "EMDB-$i";
		return $this;
	}
	function set_sas( $i ) {
		$i = strtoupper( $i );
		$this->DB	= 'SASBDB';
		$this->db	= 'sasbdb';
		$this->id	= $i;
		$this->did	= $i;
		$this->DID	= $i;
		return $this;
	}
	function set_sasmodel( $i ) {
		$i = _numonly( $i );
		$this->DB	= 'SASBDB-Model';
		$this->db	= 'sasbdb-model';
		$this->id	= $i;
		$this->did	= "sas-$i";
		$this->DID	= "SAS-$i";
		return $this;
	}
	function set_pdb( $i ) {
		$i = substr( $i, -4 );
		$this->DB	= 'PDB';
		$this->db	= 'pdb';
		$this->id	= $i;
		$this->did	= "pdb-$i";
		$this->DID	= "PDB-$i";
		return $this;
	}

	//.. ex: 存在するか
	function ex() {
		if ( $this->db . $this->id == '' )
			return false;
		if ( $this->is_prerel() )
			return true;
		if ( $this->db == 'sasbdb-model' )
			return file_exists( _fn( 'sasbdb_json', _sas_info( 'mid2id', $this->id ) ) );
		if ( $this->db == 'sasbdb' )
			return file_exists( _fn( 'sasbdb_json', $this->id ) );
		if ( $this->db == 'chem' )
			return file_exists( _fn( 'chem_img', $this->id ) );

		return _inlist( $this->id , $this->db );
	}

	//.. get: _idarray の代用
	function get() {
		return [
			'db'  => $this->db,
			'DB'  => $this->DB,
			'id'  => $this->id,
			'did' => $this->did,
			'DID' => $this->DID
		];
	}
	
	//.. imgfile: 画像ファイル
	function imgfile( $size = 's', $type = '' ) {
		$id = $this->id;

		//... EMDB
		if ( $this->db == 'emdb' ) {
			if ( ! $this->smalldb()->mov0 )
				return 'img/nomap.gif';
			$m = _fn( 'emdb_med', $id ) . '/mapi';
			$a = [
				'0' 		=> 'img/gray.gif' ,
				'p' 		=> DN_EMDB_MED . "/$id/mapi/proj0.jpg" ,
				'1' 		=> _fn( 'emdb_snap', $id, $size . '1' ) ,
				'2' 		=> _fn( 'emdb_snap', $id, $size . '2' ) ,
				'3' 		=> _fn( 'emdb_snap', $id, $size . '3' ) ,
				'_' 		=> 'img/gray.gif' ,
				'surf_x'	=> "$m/surf_x.jpg" ,
				'surf_y'	=> "$m/surf_y.jpg" ,
				'surf_z'	=> "$m/surf_z.jpg" ,
				'proj0'		=> "$m/proj0.jpg" ,
				'proj2'		=> "$m/proj2.jpg" ,
				'proj3'		=> "$m/proj3.jpg" ,
			];
			//- タイプ指定なし
			$auto = $a[ $this->smalldb()->img ?: '0' ];
			return $type == ''
				? $auto
				: ( file_exists( $f = $a[ $type ] ) ? $f : $auto )
			;

		//... PDB
		} else if ( $this->db == 'pdb' ) {
			//- 未公開データ？
			if ( $this->is_prerel() ) {
				return 'img/pdb_nr.gif';
			//- EM-PDB
			} else if ( defined( 'IMG_MODE' ) && IMG_MODE == 'em' && _inlist( $id, 'epdb' ) ) {
				//- PDB-EM
				$s = $this->smalldb()->snap;
				$auto = ( $s == '' )
					? _fn( 'pdb_img', $id )
					: _fn( 'pdb_snap', $id, $size . $s )
				;
				return $type == ''
					? $auto
					: ( file_exists( $f = _fn( 'pdb_snap', $id, "$size$type" ) )
						? $f 
						: $auto
					)
				;
			} else {
				return  file_exists( $f = _fn( 'pdb_img', $id ) )
					? $f
					: _url( 'pdbjimg', $id )
				;
			}

		//... SASBDB
		} else if ( $this->db == 'sasbdb' || $this->db == 'sasbdb-model' ) {
			$fn = _fn( 'sas_img', $id );
			return file_exists( $fn ) ? $fn : 'img/nosas.gif';

		//... chem
		} else {
			return _fn( 'chem_img', strtoupper( $id ) );
		}
	}
	
	//.. その他、主要な情報の取得
	//... is_em
	function is_em() {
		if ( $this->is_em == 'uk' )
			$this->is_em = $this->db == 'emdb' || _inlist( $this->id, 'epdb' ) ;
		return $this->is_em;
	}

	//... is_prerel
	function is_prerel() {
		if ( $this->cache[ 'is_prerel' ] == '' )
			$this->cache[ 'is_prerel' ] = $this->db == 'pdb' && _inlist( $this->id, 'prerel' );
		return $this->cache[ 'is_prerel' ];
	}

	//... title
	function title( $set = '___null___' ) {
		//- set
		if ( $set != '___null___' ) {
			$this->title = $set;
			return $this;
		}

		//- 既に定義されていたら返すだけ
		if ( $this->title != '' )
			return $this->title;

		//- 定義
		$t = '';
		if ( $this->is_em() ) {
			$t = $this->smalldb()->title ;
			if ( $t == '' ) { //- 取り消しエントリ
				$t = isset( _json_cache( DN_DATA . '/emdb/emdb-obs.json.gz' )->{$this->id} )
					? _json_cache( DN_DATA . '/emdb/emdb-obs.json.gz' )->{$this->id}->title
						. '(obsolete entry) \1'
					: 'unknown entry'
				;
			}

		} else if ( $this->db == 'pdb' ) {
			$t = $this->is_prerel()
				? _json_cache( DN_DATA . '/pdb/prerel.json.gz' )->{$this->id}->title
//				: _quicksqlite( 'pdbtitle', $this->id )
				: _quicksqlite( 'pdb_title', $this->id )
			;
		} else if ( $this->db == 'sasbdb' || $this->db == 'sasbdb-model' ) {
			$t = _sas_info( 'title', $this->id );
		} else {
			$t = $this->mainjson()->chem_comp->name;
		}

		return $this->title = $t;
	}
	
	//... method
	function method() {
		if ( $this->db == 'emdb' )
			return 'EM';
		if ( $this->db == 'sasdbd' || $this->db == 'sasdbd-model' )
			return 'SAXS/SANS';
		if ( $this->db == 'pdb' )
			return implode( ' + ', $this->qinfo()->method );
	}
	//... replaced
	function replaced() {
		if ( $this->db == 'pdb' )
			return _json_cache( DN_DATA . '/pdb/ids_replaced.json.gz' )->{ $this->id };
	}

	//.. 各種 json
	//... mainjson
	function mainjson() {
		return _json_load2( $this->db == 'sasbdb-model'
			? _fn( 'sas_json', _sas_info( 'mid2id', $this->id ) )
			: _fn( $this->db . '_json', $this->id )
		);
	}

	//... epdbjson
	function epdbjson() {
		return _json_cache( _fn( 'epdb_json', $this->id ) );
	}

	//... qinfo
	function qinfo() {
		return _json_cache( _fn( 'qinfo', $this->id ) );
	}

	//... add
	function add() {
		return _json_cache( _fn( $this->db . '_add', $this->id ) );
	}

	//... movjson
	function movjson() {
		if ( ! $this->is_em() ) return [];
		return $this->db == 'emdb'
			? _json_cache( _fn( 'movinfo', $this->id ) )
			: _json_cache( DN_DATA . '/epdb/pdbmovinfo.json' )->{ $this->id }
		;
	}

	//... mapjson
	function mapjson() {
		return _json_cache( _fn( 'mapinfo', $this->id ) );
	}

	//... flistjson
	function flistjson() {
		return _json_cache( _fn( 'flist', $this->id ) );
	}

	//... smalldb
	function smalldb() {
		if ( ! defined( 'SMALLDB_JSON' ) ) {
			define( 'SMALLDB_JSON' ,
				_json_load( DN_DATA . "/db-small.json.gz" )
			);
		}
		return (object)SMALLDB_JSON[ $this->did ];
	}

	//.. ex_* 存在確認系
	//... ex_map
	function ex_map() {
		return $this->smalldb()->mov0;
	}

	//... ex_mov
	function ex_mov() {
		return $this->smalldb()->mov1 || $this->smalldb()->movdep;
	}
	
	//... ex_polygon
	function ex_polygon() {
		return $this->smalldb()->pg1;
	}

	//... ex_bufile: mmcif版 bu 未対応
	function ex_bufile( $n ) {
		if ( $this->db != 'pdb' ) return false;
		return file_exists( 
			( TESTSV ? DN_FDATA . '/pdb/asb' : DN_KF1BU )
			. "/{$this->id}.pdb$n.gz" 
		);
	}

	//... ex_vq: vqがあるかどうか
	//- $num はPDB-assembly-IDまたは、sas-model-id
	function ex_vq( $num = '' ) {
		if ( $this->db == 'emdb' ) {
			$fn = DN_DATA . "/omokage-data/emdb-{$this->id}-vq50.pdb";
		} else if ( $this->db == 'sasbdb-model' ) {
			$fn = DN_DATA . "/sas/vq/{$this->id}-vq50.pdb";
		} else if ( $this->db == 'sasbdb' ) {
			$i = $num ?: _sas_info( 'id2mid', $this->id );
			$fn = DN_DATA . "/sas/vq/{$i}-vq50.pdb";
		} else {
			$fn = DN_DATA . "/vq/{$this->id}-$num-50.pdb";
		}
		return file_exists( $fn );
	}

	//.. リンク文字列生成系
	//... link_ym
	function link_ym( $str = '' ) {
		return _ab( _url( 'quick', $this->did ), $str ?: $this->DID );
	}

	//.. movinfo
	function movinfo() {
		return $this->db == 'emdb'
			? $this->movinfo_emdb()
			: $this->movinfo_pdb()
		;
	}

	//... EMDBデータ
	function movinfo_emdb() {
		if ( ! $this->ex_mov() ) return;
		$id = $this->id;
		$movcap_ini = parse_ini_file( DN_DATA . "/movie_caption.ini" );
//		$ini = $ini[ $id . '_' . $i ];
		$ret = [];
		foreach ( (array)$this->movjson() as $mov_num => $js ) {
			if ( $mov_num == 'jmfit' ) continue;
			$caps = [];

			//- キャプション ini
			$c = $movcap_ini[ "{$id}_$mov_num"];
			$caps[] = $this->movcap_str( $c ) ?: $c ;

			//- キャプションその他
			$caps[] = $this->movcap_str( $js->mode, $mov_num );

			//- キャプション pdbあてはめ
			$a = [];
			foreach ( (array)$js->fittedpdb as $p )
				$a[] = _ab( _url( 'quick', $p ),  "PDB-$p" );
			if ( $a )
				$caps[] = $this->movcap_str( 'atomic' ) .': '. _imp( $a );


			//- キャプション表面レベル
			if ( $js->threshold != '' )
				$caps[] = $this->movcap_str( 'surflev' ) . $js->threshold;

			$caps[] = $this->movcap_str( 'chimera' );

			//- 返り値
			$d = DN_EMDB_MED . "/$id";
			$ret[ $mov_num ] = [
				'cap'	=> array_filter( $caps ) ,
				'file'	=> "$d/movie$mov_num" ,
				'img'	=> "$d/snapl$mov_num.jpg" ,
				'imgs'	=> "$d/snaps$mov_num.jpg" ,
				'imgss'	=> "$d/snapss$mov_num.jpg" ,
				'dl'	=> "$d/movie$mov_num" ,
				'files' => $this->movurl( $mov_num ) ,
				'type'	=> $mov_num
			];
			$mov_num_last = $mov_num;
		}
		$mov_num = $mov_num_last;

		//- jmfit Jmolのムービー
		foreach ( (array)$this->movjson()->jmfit as $pid ) {
			++ $mov_num;
			$d = DN_PDB_MED . "/$pid";
			$ret[ $mov_num ] =  [
			 	'cap'	=> [ 
			 		$this->movcap_str( 'simpfit' ) ,
			 		$this->movcap_str( 'atomic' ) . _ab( _url( 'quick', $pid ), "PDB-$pid" ) ,
			 		$this->movcap_str( 'jmol' )
				] ,
				'file'	=> "$d/moviejm$id" ,
			 	'img'	=> "$d/snapljm$id.jpg" ,
			 	'imgs'	=> "$d/snapsjm$id.jpg" ,
			 	'imgss'	=> "$d/snapssjm$id.jpg" ,
				'dl'	=> "$d/moviejm$id" ,
				'files' => $this->movurl( "jm$id", $d ) ,
				'type'	=> "jm$id"
			];
		}
		
		//- トモグラムのポリゴンムービー
		$d = DN_EMDB_MED . "/$id";
		if ( file_exists( "$d/moviejm.webm" ) ) {
			++ $mov_num;
			$ret[ $mov_num ] = [
			 	'cap'	=> [
			 		$this->movcap_str( 'simpsurf' ) ,
			 		$this->movcap_str( 'jmol' )
				] ,
				'file'	=> "$d/moviejm" ,
			 	'img'	=> "$d/snapljm.jpg" ,
			 	'imgs'	=> "$d/snapsjm.jpg" ,
			 	'imgss'	=> "$d/snapssjm.jpg" ,
				'dl'	=> "$d/moviejm" ,
				'files' => $this->movurl( 'jm', $d ) ,
				'type'	=> 'jm'
			];
		} 
		return $ret;
	}

	//... PDBデータ
	function movinfo_pdb() {
		//- 集合体情報
		$ret = [];
		foreach ( (array)$this->movjson() as $cnt => $js ) {
			$caps = [];
			if ( $js->name == 'emdb' ) {
				//- EMDB movie
				$caps = [
					$this->movcap_str( 'on_emdb' ) ,
					$this->movcap_str( 'emdbid' )
						. _ab( _url( 'quick', $js->id ), "EMDB-{$js->id}" ) 
				];
				//- 一緒にあてはめたPDB
				if ( $js->cofit != '' ) {
					$a = [];
					foreach ( explode( ',', $js->cofit ) as $i2 )
						$a[] = _ab( _url( 'quick', $i2 ), "PDB-$i2" );
					$caps[] = '+ ' . _imp( $a );
				}

				//- 返り値
				$d = DN_EMDB_MED . "/{$js->id}";
				$caps[] = $this->movcap_str( 'chimera' );
				$ret[ $cnt ] =  [
					'cap'	=> array_filter( $caps ) ,
					'img'	=> "$d/snapl{$js->num}.jpg" ,
					'imgs'	=> "$d/snaps{$js->num}.jpg" ,
					'imgss'	=> "$d/snapss{$js->num}.jpg" ,
					'file'	=> "$d/movie{$js->num}" ,
					'dl'	=> "$d/movie{$js->num}" ,
					'files' => $this->movurl( $js->num, $d ) ,
					'type'	=> $js->num
				];
			} else {
				//- Jmol movie
				if ( $js->name == 'dep' ) {
					//- 登録構造
					$caps[] = $this->movcap_str( 'depo' );

				} else if ( $js->name == 'sp' || $js->name == 'sp2' ) {
					//- 分割エントリ
					$a = [];
					foreach ( (array)$js->ids as $i )
						$a[] = _ab( _url( 'quick', $i ), "PDB-$i" );
					$i = _imp( $a );
					$caps[] = _ej( "With $i", "$i との合成表示" );

				} else if ( substr( $js->name, 0, 2 ) == 'jm' ) {
					//- Jmol-fit
					$caps = [
						$this->movcap_str( 'simpfit' ) ,
						$this->movcap_str( 'emdbid' )
							. _ab( _url( 'quick', $js->id ), 'EMDB-' . $js->id )
					];
				} else {
					//- その他 BM
					$caps[] = $this->movcap_str( 'bu' )
						. (
							$this->movcap_str( $js->type ) ?:
							$js->type ?:
							_ej( 'assembly', '集合体' ) 
						);
				}
				$caps[] = $this->movcap_str( 'jmol' );

				//- 返り値
				$d = DN_PDB_MED . "/{$this->id}";
				$ret[ $cnt ] = [
					'cap'	=> array_filter( $caps ) ,
					'img'	=> "$d/snapl{$js->name}.jpg" ,
					'imgs'	=> "$d/snaps{$js->name}.jpg" ,
					'imgss'	=> "$d/snapss{$js->name}.jpg" ,
					'file'	=> "$d/movie{$js->name}" ,
					'dl'	=> "$d/movie{$js->name}" ,
					'files' => $this->movurl( $js->name ) ,
					'type'	=> $js->name
				];
			}
		}
		return $ret;
	}
	//... movcap_str
	function movcap_str( $s, $s2 = 'dummy' ) {
		$chimera = _met_pop( 'UCSF Chimera', 's' );
		$jmol = _ab( _url( 'jmol' ), 'Jmol' );
		$a = _ej([
			'chimera' 	=> "Imaged by $chimera" ,
			'jmol'		=> "Imaged by $jmol" ,
			'solid'		=> 'Solid view (volume rendering)' ,
			'vol'		=> 'Surface view with section colored by density value' ,
			'cyl'		=> 'Surface view colored by cylindrical radius' ,
			'hei'		=> 'Surface view colored by height' ,
			'rad'		=> 'Surface view colored by radius' ,
			'pdb'		=> 'Surface view with fitted model' ,
			'0'			=> 'Surface view', 
			'1'			=> 'Colored surface',
			'2'			=> 'Map surface with fitted models',
			'3'			=> 'Map surface with fitted models',
			'4'			=> 'Map surface with fitted models',

			'on_emdb'	=> 'Superimposition on EM map' ,
			'mapdata'	=> 'EMDB-ID: ' ,
			'mapid'		=> '' ,
			'zoom'		=> 'Close-up',
			'mask'		=> 'Mask data',
			'simpfit'	=> 'Simplified surface model + fitted atomic model' ,
			'simpsurf'	=> "Simplified surface model" ,
			'atomic'	=> 'Atomic models: ' ,
			'surflev'	=> 'Surface level: ' ,
			'icos'		=> 'complete icosahedral assembly',
			'icos5'		=> 'icosahedral pentamer',
			'icos6'		=> 'icosahedral 23 hexamer',
			'helix' 	=> 'representative helical assembly',
			'comp' 		=> 'complete point assembly',
			'dimeric'	=> 'dimeric',
			'trimeric'	=> 'trimeric',
			'tetrameric' => 'tetrameric',
			'pentameric' => 'pentameric',
			'hexameric'	=> 'hexameric',
			'octameric'	=> 'octameric',
			'depo'		=> 'Deposited structure unit', 
			'bu'		=> 'Biological unit as ',

		], [
			'chimera'	=> "{$chimera}による作画" ,
			'jmol'		=> "{$jmol}による作画" ,
			'solid'		=> 'ソリッド表示（ボリュームレンダリング）' ,
			'vol'		=> '表面図（断面を密度値に従い着色）' ,
			'cyl'		=> '表面図（円筒半径に従い着色）' ,
			'rad'		=> '表面図（半径に従い着色）' ,
			'hei'		=> '表面図（高さに従い着色）' ,
			'pdb'		=> 'あてはめたモデルとの重ね合わせ' ,
			'0'			=> '表面図', 
			'1'			=> '着色した表面図',
			'2'			=> 'あてはめたモデルとの重ね合わせ',
			'3'			=> 'あてはめたモデルとの重ね合わせ',
			'4'			=> 'あてはめたモデルとの重ね合わせ',
			'zoom'		=> '拡大図' ,
			'mask'		=> 'マスクデータ' ,
			'simpfit'	=> '単純化した表面モデル + あてはめた原子モデル' ,
			'simpsurf'	=> "単純化した表面モデル" ,
			'atomic'	=> '原子モデル: ' ,
			'surflev'	=> '表面レベル: ' ,
			
			'on_emdb'	=> 'EMマップとの重ね合わせ' ,
			'emdbid'	=> 'マップデータ: ' ,

			'icos'		=> '完全な正20面体対称集合体',
			'icos5'		=> '正20面体対称構造中の5量体部位',
			'icos6'		=> '正20面体対称構造中の6量体部位',
			'helix' 	=> 'らせん対称集合体',
			'comp' 		=> '全体構造',
			'dimeric'	=> '2量体',
			'trimeric'	=> '3量体',
			'tetrameric'=> '4量体',
			'pentameric'=> '5量体',
			'hexameric'	=> '6量体',
			'octameric'	=> '8量体',
			'nonameric' => '9量体',
			'depo'		=> '登録構造単位' ,
			'bu'		=> '生物学的単位 - ' ,
		]);
		return $a[ $s ] ?: $a[ $s2 ];
	}
	//... movurl: ムービーファイル名を返す
	function movurl( $num, $s = '' ) {
		if ( $s == '' )
			$s = ( $this->db == 'emdb' ? DN_EMDB_MED : DN_PDB_MED ). '/' . $this->id;
		return [
			'l' => [
				'webmv'  => "$s/movie$num.webm" ,
				'm4v'    => "$s/movie$num.mp4" ,
				'poster' => "$s/snapl$num.jpg"
			] ,
			's' => [
				'webmv'  => "$s/movies$num.webm" ,
				'm4v'    => "$s/movies$num.mp4" ,
				'poster' => "$s/snapl$num.jpg"
			]
		];
	}
}

//. class sqlite
class cls_sqlite {
	protected $pdo, $wh, $sql, $fn_db, $log, $flg_mng, $flg_persistent;
	function __construct( $s = 'main', $flg = false ) { //- $flg: manageモードか？
		$this->set( $s );
		$this->flg_mng = $flg;
		if ( FLG_MNG )
			_m( 'SQLite database file: ' . $this->fn_db );
		return $this;
	}

	//.. set
	function set( $db_name = 'main', $flg_persistent = false ) {
		//- sqliteファイルをローカルにコピー
		//- $flg_persistent: ATTR_PERSISTENT フラグ
		$this->flg_persistent = $flg_persistent;

		//... フルパス指定
		if ( _instr( '/', $db_name ) ) {
			$this->log( basename( $db_name, '.sqlite' ), 'direct', $fn );
			return $this->set_PDO( $db_name );
		}
		
		//... doc: docだけはemnavi/docにある
		if ( $db_name == 'doc' ) {
			$fn = ( FLG_MNG ? DN_EMNAVI. '/' : '' ) . 'doc/doc.sqlite';
			$this->log( 'doc', '-', $fn );
			return $this->set_PDO( $fn );
		}
		
		$fn = DN_DATA. "/$db_name.sqlite";

		//... テストサーバー: そのまま使う
		if ( TESTSV || FLG_MNG ) {
			$this->log( $db_name, 'local', $fn );
			return $this->set_PDO( $fn );
		}

		//... 本番サーバー: DBファイルをコピーする
		$dn = '/dev/shm/emnavi';
		if ( ! is_dir( $dn ) )
			mkdir( $dn );
		$dest = "$dn/$db_name.sqlite";
//		$flg_persistent = false;
		if ( ! file_exists( $dest ) ) {
			copy( $fn, $dest );
			touch( $dest, filemtime( $fn ) );
			$this->log( $fn, 'new, copied', "$fn -> $dest" );
		} else if ( filemtime( $fn ) != filemtime( $dest ) ) {
			copy( $fn, $dest );
			touch( $dest, filemtime( $fn ) );
			$this->log( $fn, 'changed, copied', "$fn -> $dest" );
		} else {
			$this->log( $fn, 'same', $fn );
		}
		return $this->set_PDO( $dest );
	}

	//.. set_PDO
	function set_PDO( $fn_db ) {
		if ( ! file_exists( $fn_db ) ) {
			die( TESTSV || FLG_MNG
				? "no db file: $fn_db"
				: 'Database error'
			);
		}
		$this->pdo = new PDO(
			'sqlite:' . realpath( $fn_db ),
			'', '',
			[ PDO::ATTR_PERSISTENT => $this->flg_persistent ] 
		);
		$this->fn_db = $fn_db;
		return $this;
	}

	//.. getsql()
	function getsql() {
		return $this->sql;
	}

	//.. setsql
	function setsql( $q ) {
		if ( is_array( $q ) ) {
			$q = array_change_key_case( $q );
			//- デフォルト値設定、select, from, の順番になるようにする
			$q = array_merge([
				'select'	=> $q[ 'select' ] ?: 'count(*)' ,
				'from'		=> $q[ 'from' ]   ?: 'main' ,
				'where'		=> $this->wh
			], $q );

			$qa = [];
			foreach ( $q as $k => $v ) {
				if ( $v == '' || $v == [] ) continue;
				$qa[] = strtoupper( $k );
				if ( is_array( $v ) )
					$v = implode( $k == 'where' ? ' and ' : ',', $v );
				$qa[] = $v;
			}
			$q = implode( ' ', $qa );
		}
		$this->sql = $q;
		return $this;
	}

	//.. where
	function where( $wh ) {
		$this->wh = is_array( $wh ) ? implode( ' and ', $wh ) : $wh;
		return $this;
	}

	//.. cnt
	function cnt( $wh = '' ) {
		if ( $wh != '' )
			$this->where( $wh );
		return $this->q([ 'where' => $this->wh ])->fetchColumn();
	}

	//.. q クエリ実行メイン
	function q( $ar ) {
		$this->setsql( $ar );

		//... mngシステム版
		if ( FLG_MNG ) {
			$res = $this->pdo->query( $this->sql );
			$er = $this->pdo->errorInfo();
			if ( $er[0] == '00000' ) {
				return $res;
			} else {
				//- エラー
				_kvtable([
					'DB file' => $this->fn_db ,
					'query'   => $this->sql ,
					'error message' => print_r( $er, 1 )
				], 'DB error');
			}
		} else {

		//... WEB版
			//- エラーが出なくなるまで繰り返す
			foreach ( range( 1, 20 ) as $cnt ) {
				$res = $this->pdo->query( $this->sql );
				$er = $this->pdo->errorInfo();
				if ( $er[0] == '00000' ) return $res;
				usleep( 500000 ); //- 0.5秒
			}

			die( TEST 
				? _p( "DB error\n" ) . _t( 'pre', ''
					. "\nDB file: {$this->fn_db}"
					. "\nquery: {$this->sql}"
					. "\nerror message\n"
					. print_r( $er, 1 )
				)
				: 'Database process is busy. Please, retry later.'
			);
		}
	}
	
	//.. qcol, qar, qobj
	function qcol( $ar ) {
//		if ( _instr( 'wikipe', $this->fn_db ) )
//			$this->log( 'query', $this->fn_db,_kv( $ar ) );
		return $this->q( $ar )->fetchAll( PDO::FETCH_COLUMN, 0 );
	}
	function qar( $ar ) {
//		if ( _instr( 'wikipe', $this->fn_db ) )
//			$this->log( 'query', $this->fn_db,_kv( $ar ) );
		return $this->q( $ar )->fetchAll( PDO::FETCH_ASSOC );
	}
	function qobj( $ar ) {
		return $this->q( $ar )->fetchAll( PDO::FETCH_OBJ );
	}

	//.. log
	function log( $a, $b, $c ) {
		global $_sqlite_log;
		if ( FLG_MNG || ! TEST ) return;
		$_sqlite_log[] = [ $a, $b, $c ];
	}
}
