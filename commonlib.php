<?php
//use PHPMailer\PHPMailer\PHPMailer;
//use PHPMailer\PHPMailer\Exception;

//. misc init
ini_set( "memory_limit", "8192M" );
ini_set( 'display_errors', "On" );
error_reporting(E_ERROR | E_WARNING | E_PARSE);
//error_reporting( E_ALL & ~E_NOTICE );

//define( 'DO_ONLY_NEW', true ); //- 画像とVQの作成、新規データのみ、変更データはやらない
//define( 'DO_ONLY_NEW', false );

define( 'FLG_FILESV3'	, gethostname() == 'filesv3' );
define( 'TESTSV', true );

//.. テキスト色
define( 'ES_BLK'	, "\033[1:30m" );
define( 'ES_RED'	, "\033[1:31m" );
define( 'ES_GRN'	, "\033[1:32m" );
define( 'ES_BLUE'	, "\033[1:34m" );
define( 'ES_CYAN'	, "\033[1:36m" );

define( 'ES_BGRED'	, "\033[41m" );
define( 'ES_BGBLUE'	, "\033[44m" );
define( 'ES_BGCYAN'	, "\033[0:37:46m" );

define( 'ES_REV'	, "\033[7m" );

define( 'ES_RESET'	, "\033[0m" );

//.. ファイル・ディレクトリパス
define( 'INIT_DIR', getcwd() );

//- このスクリプトのあるディレクトリに移動
chdir( __DIR__ );

//- スクリプト名
define( 'TASK_NAME'		, basename( $argv[0], '.php' ) );

//- ディレクトリ名など
define( 'DN_SCRIPT'		, __DIR__ );
define( 'DN_ROOT'		, realpath( __DIR__ . '/..' ) ); //- = /*disk2/db


define( 'DN_TOOLS'		, FLG_FILESV3 ? '/yorodumi/tools' : '/tools' );
//realpath( __DIR__ . '/../../softwares-64' ) );

define( 'DN_PREP'		, DN_ROOT . '/prepdata' );
define( 'DN_EDIT'		, DN_ROOT . '/edit' );
define( 'DN_EMNAVI'		, DN_ROOT . '/emnavi' );
define( 'DN_FDATA'		, DN_ROOT . '/fdata' );		//- fdataディレクトリ、外からのデータ
define( 'DN_OMOPDB'		, DN_ROOT . '/omopdb' );

define( 'DN_DATA'		, DN_EMNAVI. '/data' );
define( 'DN_EMDB_MR'	, DN_FDATA. '/emdb-mirror' );

//- epdb
define( 'DN_PDB_XML'	, DN_PREP. '/xml-pdb' );


define( 'DN_TEMP', FLG_FILESV3 ? '/dev/shm/yorodumi' : '/tmp/emn' );
define( 'DN_PROC', DN_TEMP . '/proc' );

//- ファイル名 EMN
define( 'FN_DBMAIN'     	, DN_DATA. '/main.sqlite' );
define( 'FN_DBSTATUS'		, DN_PREP. '/emn/status.json.gz' );
define( 'FN_ASB_JSON'		, DN_PREP. '/emn/assembly.json' );
define( 'FN_PDB_MOVINFO'	, DN_PREP. '/emn/pdbmovinfo.json' );

//- uniprot/ dbid2str系
define( 'DN_UNP_PREP'		, DN_PREP . '/unp' );
define( 'FN_DBDATA_PDB'		, DN_UNP_PREP. '/dbdata_pdb.json.gz' );
define( 'FN_DBDATA_EMDB'	, DN_UNP_PREP. '/dbdata_emdb.json.gz' );
define( 'FN_DBDATA_SASBDB'	, DN_UNP_PREP. '/dbdata_sasbdb.json.gz' );

define( 'FN_DBID2STRCNT'	, DN_PREP. '/dbid/dbid2strcnt.json.gz' );

//- DBID系
define( 'FN_EC_NAME'		, DN_PREP. '/dbid/ecnum2name.json.gz' );
define( 'FN_GO_JSON'		, DN_PREP. '/dbid/go_info.json.gz' );
define( 'FN_CATH_NAME_JSON' , DN_PREP. '/dbid/cath_name.json.gz' );
define( 'FN_PDB2CATH'		, DN_PREP. '/dbid/pdb2cath.json.gz' );
define( 'FN_REACT_JSON'		, DN_PREP. '/dbid/reactome.json.gz' );
define( 'FN_INTERPRO_JSON'	, DN_PREP. '/dbid/interpro_info.json.gz' );
define( 'FN_PFAM_JSON'		, DN_PREP. '/dbid/pfam_description.json.gz' );
define( 'FN_SMART_JSON'		, DN_PREP. '/dbid/smart.json.gz' );
define( 'FN_PROSITE_JSON'	, DN_PREP. '/dbid/prosite.json.gz' );
define( 'FN_PRORULE_JSON'	, DN_PREP. '/dbid/prorule.json.gz' );

//- PDBML schema
define( 'FN_PDBML_SCHEMA'	, DN_FDATA. '/pdbml_schema/pdbx-v50.xsd' );

//- sqlite file
define( 'DN_SQLITE', FLG_FILESV3 ? '/yorodumi/sqlite' : '/ssd/sqlite' );


//- DN_TEMP
if ( ! is_dir( DN_TEMP ) ) {
	mkdir( DN_TEMP );
	exec( 'chmod 777 '. DN_TEMP );
}
_mkdir( DN_PROC );

//- hourly のメッセージ出力用
define( 'FN_HOURLY_MSG',  DN_TEMP. '/msg_hourly.txt' );

//.. 別のスクリプト include
define( 'FLG_MNG', true ); //- manageフラグ

require_once( "../emnavi/common-mng-web.php" );
//_tsv_define( 'common' );
//if ( ! FLG_FILESV3 ) {
//	require_once( "Mail.php" );
//	require_once( "Mail/mime.php" );
//}

//.. misc
define( 'START_MS_HASH', crc32( microtime() ) );
define( 'START_TIME', time() );

$vqnums = [ 30, 50 ];

define( 'OMO_MODE'	, 8 );

define( 'DISPLAY'	, 'export DISPLAY=:0;' );
define( 'LINE'		, "\n" . str_repeat( '-', 80 ) . "\n" );

//.. コマンドライン引数
$flg_redo = false;
$_norsync = false;
$args = [];
$argar = [];

foreach ( (array)$argv as $s ) {
	//- misc
	$args[ $s ] = true;

	list( $k, $v ) = explode( '=', $s, 2 );
	if ( $v )
		$argar[ $k ] = $v;

	//- redo
	if ( $s == 'redo' ) {
		_m( "やり直しフラグ = On" );
		$flg_redo = true;
	}

	//- DB範囲
	if ( $s == 'pdb'  ) $_pdbonly  = true;
	if ( $s == 'emdb' ) $_emdbonly = true;

	//- ID範囲 ID
	if ( preg_match( '/^([0-9]{4})-([0-9]{4})$/', $s, $i ) ) {
		$_startid = $i[ 1 ];
		$_endid = $i[ 2 ];
		_m( "ID範囲  $_startid - $_endid" );
	}

	if ( preg_match( '/^([0-9]{4})-$/', $s, $i ) ) {
		$_startid = $i[ 1 ];
		_m( "ID範囲  $_startid -" );
	}

	if ( preg_match( '/^-([0-9]{4})$/', $s, $i ) ) {
		$_endid = $i[ 1 ];
		_m( "ID範囲 - $_endid" );
	}

	if ( preg_match( '/^[1-9]\/[1-9]$/', $s, $i ) ) {
		$_limitid = $s;
		_m( "ID範囲 - $_limitid" );
	}
	
	//- norsync
	if ( $s == 'norsync' )
		$_norsync = true;
	
}

define( 'FLG_REDO', $flg_redo );
define( 'NORSYNC', $_norsync );

//.. log
$_log = [];

//..  replace array
//- 普通のテキスト
//- 改行、連続空白、末尾のコンマ
$rep_in[  'str' ] = [ '/[\r\n ]+/', '/  +/', '/,$/' ];
$rep_out[ 'str' ] = [ ' '         , ' '    , ''     ];

//- 人名
//- 数字以外、末尾の小数点
$rep_in[  'person' ] = [ '/\r|\n/', '/,? ?and ?/', '/ ?(\.|,)/', '/  +/', '/,$/' ];
$rep_out[ 'person' ] = [ ' '      , ', '         ,  '\1 '      , ' '    , ''     ];

//- state
$rep_in[  'state' ] = [	'/ind.+/i', '/sin.+/i', '/ico.+/i', '/hel.+/i', '/two.+/i', '/Cry.+/i' ];
$rep_out[ 'state' ] = [
	'individual structure',
	'single particle',
	'icosahedral',
	'helical',
	'2D-crystal',
	'crystal'
];

//.. _add_fn: 運用
$_filenames += [
	//- emdb
	'map'		=> DN_EMDB_MED	. '/<id>/emd_<id>.map' ,
	'mapi'		=> DN_EMDB_MED	. '/<id>/mapi' , 
	'map4vq'	=> DN_PREP		. '/map4vq/<id>.mrc' ,

	'emdb_xml'	=> DN_EMDB_MR	. '/structures/EMD-<id>/header/emd-<id>.xml' ,
	'emdb_xml3' => DN_EMDB_MR	. '/structures/EMD-<id>/header/emd-<id>-v30.xml' ,

	'mapgz'		=> DN_EMDB_MR	. '/structures/EMD-<id>/map/emd_<id>.map.gz' ,

	//- allpdb
	'mlplus'		=> DN_FDATA	. '/pdbmlplus/<id>-add.xml.gz' ,
	'pdbml_noatom'	=> DN_FDATA	. '/pdbml_noatom/<id>-noatom.xml.gz' ,
	'pdb_pdb'		=> DN_FDATA	. '/pdb/dep/pdb<id>.ent.gz' ,
	'pdb_mmcif'		=> DN_FDATA	. '/mmcif/<id>.cif.gz' ,

	//- large
	'pdb_mmcif_large' => DN_FDATA	. '/large_structures/mmCIF/<id>.cif.gz' ,

	//- epdb
	'pdb_xml'	=> DN_PDB_XML	. '/<id>-s.xml' ,

	//- vq (emdb)
	'emdb_vq30'	=> DN_DATA. '/emdb/vq/<id>-30.pdb' ,
	'emdb_vq50'	=> DN_DATA. '/emdb/vq/<id>-50.pdb' ,
	'pre_vq30'	=> DN_PREP. '/vq_pre/<id>-30.pdb' ,
	'pre_vq50'	=> DN_PREP. '/vq_pre/<id>-50.pdb' ,

	//- pubmed
	'pubmed_xml'  => DN_FDATA . '/pubmed_xml/<id>.xml' ,
	'pubmed_json' => DN_DATA . '/pubmed/<id>.json' ,
	
	//- maindb
	'maindb_json' => DN_PREP . '/maindbjson/<name>.json' ,

	//- keyword file 
	'emdb_kw'	=> DN_PREP . "/keyword/emdb/<id>.txt" ,
	'pdb_kw'	=> DN_PREP . "/keyword/pdb/<id>.txt" ,
	'pdb_auth'	=> DN_PREP . "/keyword/pdb_auth/<id>.txt" ,
	'sas_kw'	=> DN_PREP . "/keyword/sas/<name>.txt" ,

	//- metjson
	'emdb_metjson' => DN_PREP. '/met/emdb/<id>.json' ,
	'pdb_metjson'  => DN_PREP. '/met/pdb/<id>.json' ,
	'sas_metjson'  => DN_PREP. '/met/sas/<name>.json' ,

];

//.. コマンドヒストリ
_mkdir( $dn = DN_TEMP . '/hist' );

$a = $argv;
$a[0] = basename( $a[0], '.php');
if ( $a[0] != 'hourly' ) {
	touch (
		$dn . '/' . date( 'Ymd-His-' ) .
		substr( preg_replace( '/[^0-9a-zA-Z\-]/', '_', implode( '_', $a ) ), 0, 30 )
	);

	//- 古いのを消す(ファイル名順)
	$s = "$dn/*";
	foreach ( glob( $s ) as $fn ) {
	//	_m( $fn );
		if ( count( glob( $s ) ) < 200 ) break;
		_del( $fn );
	}
}


//. functions: ファイル操作系
//.. _mpac_save
function _mpac_save( $fn, $data ) {
	return _gzsave(
		_prepfn( $fn ) ,
		msgpack_pack( $data )
	);
}
function _mpac_load( $fn, $opt = true ) {
	$fn = _prepfn( $fn ); 
	if ( ! file_exists( $fn ) ) return;
	$cont = file_get_contents( $fn );
	return msgpack_unpack( _is_gz( $fn ) ? gzdecode( $cont ) : $cont );
}


//.. _filetime
function _filetime( $fn ){
	return file_exists( $fn ) ? date( 'Y-m-d H:i:s', filemtime( $fn ) )  : 'nofile';
}


//.. _comp_save: 比較して変化してたら保存
//- nomsg: 成功したときは、メッセージ無し
function _comp_save( $fn, $data, $opt = '' ) {
	$nomsg = _instr( 'nomsg', $opt );
	$ext = strtr( _ext( $fn ), [ '.gz' => '' ] );
	if ( $ext == 'data' )
		$data = serialize( $data );
	if ( $ext == 'json' )
		$data = json_encode( $data, JSON_UNESCAPED_SLASHES );
	if ( $ext == 'txt' && is_array( $data ) )
		$data = implode( "\n", $data );

	$msg = "[比較・保存] - $fn -";
	//- チェック
	if ( file_exists( $fn ) ) {
		if ( $data == _gzload( $fn ) ) {
			if ( ! $nomsg )
				_m( "$msg 変更無し" );
			return false;
		}
		$msg .= " 変更あり -";

	} else {
		$msg .= " 新規データ -";
	}

	//- 書き込み
	if ( _gzsave( $fn, $data ) === false ) {
		_log( "$msg 書き込み失敗!!!!!!!!!!", 'red' );
		return false;
	} else {
		_log( "$msg 書き込み成功", 'blue' );
		return true;
	}
}

//.. _copy: ファイルをコピーして、タイムスタンプもコピー
//- f3が指定されてたら、f3のタイムスタンプにする
function _copy( $f1, $f2, $time = '' ) {
	_del( $f2 );
	$ret =  copy( $f1, $f2 );
	touch( $f2, $time ?: filemtime( $f1 ) );
	return $ret;
}

//.. _save_touch: データを保存、タイムスタンプ指定
/* 
_save_touch([
	'fn_in' =>
	'fn_out'=>
	'data'  =>
	'name'  =>
])
*/
function _save_touch( $a ) {
	extract( $a ); //- $fn_in, $fn_out, $data, $name
	_del( $fn_out );
	$e = _ext( $fn_out );
	if ( $e == 'json' || $e == 'json.gz' ) {
		//- json
		if ( is_string( $data ) ) {
			if ( $e == 'json.gz' )
				_gzsave( $fn_out, $data );
			else
				file_put_contents( $fn_out, $data );
		} else {
			_json_save( $fn_out, $data );
		}
	} else {
		//- 文字列
		file_put_contents( $fn_out, $data );
	}

	$n = $name ?: $fn_out;
	if ( file_exists( $fn_out ) ) {
		touch( $fn_out, filemtime( $fn_in ) );
		_log( "$n: 保存・タイムスタンプセット" );
		return true;
	} else {
		_problem( "$n: 保存失敗" );
		return false;
	}
}

//.. _save: _fileの反対
function _save( $fn, $ar ) {
	return file_put_contents( $fn, implode( "\n", (array)$ar ) );
}

//.. _newer
//- 「やらなくてもいい処理」なら trueを返す
//- outが「出力」ファイル
//- inが「もとになる」ファイル
//- 出力ファイルがない、古い => 作るべき => false
//- 入力ファイルがない、出力ファイルのほうが新しい、作らなくていい => true
//- if ( _newer( [out-file], [in-file] ) ) continue; というふうにつかう

function _newer( $fn_out, $fn_in ) {
	if ( is_string( $fn_in  ) ) {
		//- 元ファル 1個
		if ( ! file_exists( $fn_in ) ) return true; //- もとファイル(f2)がないなら true
		if ( ! file_exists( $fn_out ) ) return false; //- 出力ファイルがないなら false
		return filemtime( $fn_out ) >= filemtime( $fn_in ); //- 出力ファイルが新しいならtrue
	} else if ( is_array( $fn_in ) ) {
		//- 元ファル array
		$ts = [];
		foreach ( $fn_in as $fn ) {
			if ( file_exists( $fn ) )
				$ts[] = filemtime( $fn );
		}
		if ( count( $ts ) == 0 ) return true; //- 元ファル一つもない
		if ( ! file_exists( $fn_out ) ) return false; //- 出力ファイルがないなら false
		return filemtime( $fn_out ) >= max( $ts ); //- 出力ファイル新しいならtrue
	} else {
		_problem( '_newer関数: 出力ファイル名が異常' );
	}
}

//.. _sametime
//- 「タイムスタンプが違えば処理を実行」というような場合に呼ぶ
//- if ( _same_time( a, b ) ) continue; など

function _same_time( $fn_in, $fn_out ) {
	//- 入力ファイルがないならやらない
	if ( ! file_exists( $fn_in ) )
		return true;

	//- redoならやる
	if ( FLG_REDO )
		return;

	//- 出力ファイルがないならやる
	if ( ! file_exists( $fn_out ) )
		return;

	//- タイムスタンプが違うならやる
	return filemtime( $fn_in ) == filemtime( $fn_out );
}

//.. _mkdir: ディレクトリなければ作成
function _mkdir( $d ) {
	if ( is_dir( $d ) ) return true;
	if ( mkdir( $d ) ) {
		_m( "$d: ディレクトリを作成" );
		return true;
	} else {
		_m( "$d: ディレクトリ作成失敗", -1 );
		return false;
	}
}

//.. _checkf: ファイルができたか確認
function _checkf( $fn, $str = '' ) {
	if ( $s == '' )
		$s = $fn;
	file_exists( $fn )
		? _log( "$s: 作成成功" )
		: _log( "$s: 作成失敗 !!!!!", 'e' )
	;
}

//.. _ext: 拡張子、小文字で返す
//- hoge.fuga.gzの場合は、fuga.gzを返す
function _ext( $fn ) {
	$e = strtolower( pathinfo( $fn, PATHINFO_EXTENSION ) );
	return $e != 'gz'
		? $e 
		: strtolower(
			pathinfo( basename( $fn, ".$e" ), PATHINFO_EXTENSION ) . ".$e"
		)
	;
}
/*
//.. _prepfile: ファイルを準備（同じファイルを何度も作らないように）
function _prepfile( $fn, $cont ) {
	if ( ! file_exists( $fn ) )
		touch( $fn );
	if ( file_get_contents( $fn ) == $cont ) return;
	return "テンプレートファイル - $fn : 書き込み" .
		( file_put_contents( $fn, $cont ) === false
			? "失敗!!!!!"
			: "成功"
		)
	;
}
*/
//.. _copyrt
//- コピー 失敗してもやり直す
function _copyrt( $url, $fn ) {
	foreach ( range( 1, 10 ) as $i ) {
		$f = copy( $url, $fn );
		if ( $f ) break;
		_m( "コピーやりなおし #$i" );
	}
	if ( ! $f )
		die ( "\nコピー失敗!\nファイル: $url\n" );
	return $f;
}

//.. _ex_or_die: ファイルがないなら死ぬ
function _ex_or_die( $fn ) {
	if ( file_exists( $fn ) ) {
		_m( "存在確認-OK：$fn" );
		return;
	}
	die( "ファイルがない！！！！！\nファイル名: $fn" );
}

//.. _delold
//- f2 が f1 よりも「古かったら」消す
function  _delold( $f1, $f2, $test = 0 ) {
	if ( ! file_exists( $f2 ) ) return;
	if ( file_exists( $f1 ) )
		if ( filemtime( $f1 ) < filemtime( $f2 ) ) return;
	if ( $test ) {
		_m( "ファイル[$f2]を消去すべきです" );
	} else {
		unlink( $f2 );
		_m( "ファイル[$f2]を消去しました。" );
	}
}

//.. _fn: file name
//- mng版、web版と違う
function _fn( $type, $_id = '', $s1 = '', $s2 = '' ) {
	global $did, $id, $_filenames;

	$i = $_id ?: $id ?: $did ;
	if ( $i == '' )
		_problem( ":_fn関数エラー (type=$type): IDが空白" );

	$i = strtr( $i, [ 'emdb-' => '', 'pdb-'  => ''] );

	if ( in_array( $type, [ 'xml', 'json', 'add', 'snap', 'mp4', 'webm' ] ) )
		$type = ( _inlist( $i, 'epdb' ) ? 'pdb_' : 'emdb_' ) . $type;

//	if ( substr( $type, 0, 1 ) == '_' )
//		$type = ( _inlist( $i, 'epdb' ) ? 'pdb' : 'emdb' ) . $type;

	$s = strtr( $_filenames[ $type ], [
		'<did>'		=> _id2did( $i ) ,
		'<id>'		=> $i ,
		'<name>'	=> $_id ,
		'<s1>'		=> $s1 ,
		'<s2>'		=> $s2
	]);

	if ( $s == '' )
		_problem( ":_fn関数エラー: ファイルタイプが不明($type)" );

	return $s;
}

//.. _json: emdb_json, epdb_json専用 (廃止するか？)
//function _json( $id ) {
//	//- EMデータだけ
//	return _json_load( _fn( _inlist( $id, 'epdb' ) ? 'epdb_json' : 'emdb_json', $id ), false );
//}

//.. _tsv_load; key value形式
function _tsv_load( $fn ) {
	if ( ! file_exists( $fn ) ) {
		_problem( "ファイルがない: $fn" );
		return;
	}
	$ret = [];
	foreach ( _file( $fn ) as $line ) {
		list( $key, $val ) = explode( "\t", $line, 3 );
		$ret[ trim( $key ) ] = trim( $val );
	}
	return $ret;
}

//.. _download
function _download( $in, $out ) {
	$msg = basename( $out ). ': ダウンロード';
	_m( "$msg 開始..." );
	copy( $in, $out )
		? _log( $msg )
		: _problem( "$msg 失敗 ($in)" )
	;
}

//. function 外部ツール利用系
//.. _run: コマンドにスクリプトファイルを渡して実行
function _run( $cmd, $params ) {
	$p = '';
	foreach ( $params as $s )
		$p .= 'echo '	. ( is_numeric( $s ) ? $s : "\"$s\"" ) . '; ';

	exec( "($p) | timeout 3600 $cmd", $out );
	return $out;
}

//.. _exec: コマンド実行、コマンドラインをエコー
function _exec( $s ) {
	_m( "[exec]: $s" );
	exec( $s, $ret );
	return implode( "\n", $ret );
}

//.. _envset: EMAN/spider/situs用の環境設定
function _envset( $soft = '' ) {
	//- spider
	if ( $soft == 'spider' || $soft == '' ) {
		$s = DN_TOOLS. '/spider';
		putenv( "SPDIR=$s" );
		putenv( "SPMAN_DIR=$s/man/" );
		putenv( "SPBIN_DIR=$s/bin/" );
		putenv( "SPPROC_DIR=$s/proc/" );
		_soft_name( 'SPIDER', "$s/bin/spider_linux_mp_intel64" );
	}

	//- EMAN
	if ( $soft == 'eman' || $soft == '' ) {
//		$e = "/home/hirofumi/EMAN";
		$e = DN_TOOLS. '/EMAN';
		putenv( "EMANDIR=$e" );
		putenv( "LD_LIBRARY_PATH=$e/lib/" );
		putenv( "PYTHONPATH=${EMANDIR}/lib" );
		$b = "$e/bin";
		_soft_name( 'PROC2D', "$b/proc2d" );
		_soft_name( 'PROC3D', "$b/proc3d" );
		_soft_name( 'IMINFO', "$b/iminfo" );
	}

	//- situs/map2map
	if ( $soft == 'situs' || $soft == 'map2map' || $soft == '' ) {
		define( 'DN_SITUS_BIN', DN_TOOLS . '/Situs/bin' );
		_soft_name( 'MAP2MAP' , DN_SITUS_BIN . '/map2map' );
		_soft_name( 'QPDB'    , DN_SITUS_BIN . '/qpdb' );
		_soft_name( 'QVOL'    , DN_SITUS_BIN . '/qvol' );
	}
}

//.. _soft_name
//- ？
function _soft_name( $name, $path ) {
	define( $name, "$path " );
	if ( file_exists( $path ) ) return;
	die( "エラー：ソフトウェアがない\n $name \n $path" );
}


//.. _imgcomv / _imgres
//- 画像コンバート
function _imgconv( $in, $out, $opt = '' ) {
	if ( ! file_exists( $in ) ) {
		_m( "$fn: ファイルがない", -1 );
		return;
	}
	_del( $out );
	exec( "convert $opt $in $out" );
	return ( file_exists( $out ) );
}
//- 画像コンバート・リサイズ
function _imgres( $in, $out, $size = 'x100', $opt = '' ) {
	if ( ! file_exists( $in ) ) {
		_problem( "ファイルがない $in" );
		return;
	}
	_del( $out );
	exec( "convert -resize $size $opt $in $out" );
	return ( file_exists( $out ) );
}

//.. _gnupot: gnuplot実行
function _gnuplot( $cmd ) {
	_m( 'gnuplot実行' );
	$cmd = strtr( $cmd,  [ '"' => '\"' ] );
	exec( "echo \"$cmd\" | gnuplot" );
}

//.. _gnuplot2
function _gnuplot2( $in ) {
	$fn = $set = $curve = null;
	extract( $in );
	$cmd = '';

	//- set
	foreach ( array_merge([
		'output'	=> "'$fn'" ,
		'term'		=> "svg font 'Helvetica,14'" ,
	], $set ) as $key => $val ) {
		$cmd .= "set $key $val;\n";
	}
	
	//- curve
	$curve_set = [];
	foreach ( is_string( $curve ) ? [ $curve ] : $curve as $c ) {
		$fn = $using = $with = $title = null;
		extract( $c );
		$curve_set[] = "'$fn' using $using with $with title '$title'";
	}
	$cmd .= 'plot '. implode( ', ', $curve_set );
	exec( "echo \"$cmd\" | gnuplot" );
}

//.. _Rrun: R実行
function _Rrun( $cmd ) {
	file_put_contents( $fn = _tempfn( 'r' ), $cmd );
	passthru( "R CMD BATCH --vanilla $fn" );
	_del( $fn );
}

//.. _jmol: Jmol実行
//- x: Jmolのサイズ
//- 500x396で 512x512になる、なぜか

define( 'JMOL', 'java -Djava.awt.headless=true -Xmx16384m -Xss64m -jar ' . DN_TOOLS . '/jmol/JmolData.jar ' );

function _jmol( $str, $x = 500, $killtime = 0 ) {
	_m( '[Jmol開始 - ' . date( "H:i:s" ) . ']' );
	file_put_contents( $cfn = _tempfn( 'txt' ), $str );
	exec( ''
//		. DISPLAY
		. ( $killtime > 0 ? "timeout -s 9 $killtime " : '' )
		. JMOL
//		. '--geometry ' . ( $x - 12 ) . 'x' . ( $x - 116 ) . ' ' //- サイズ補正
		. '-g' . ( $x * 2  ) . 'x' . ( $x * 2 ) . ' ' //- サイズ補正
		. "-ionx --script \"$cfn\""
	);
	_m( '[完了]' );
	_del( $cfn );
}

//.. _tempfn: テンポラリファイル用の重複しないファイル名を返す
function _tempfn( $ext = 'txt' ) {
	$i = 0;
	while( 1 ) {
		++ $i;
		$n = DN_TEMP . '/tmp_' . START_MS_HASH . "_$i.$ext" ;
		if ( ! file_exists( $n ) ) break;
	}
//	_pause( "$n: not exists!" );
	touch( $n );
	return $n;
}

//.. _map2map
function _map2map( $fn1, $fn2 = 'dummy' ) {
	if ( ! defined( 'MAP2MAP' ) )
		_envset( 'situs' );

	$mode = 1;
	if ( $fn2 == 'dummy' ) {
		_m( '[e.c. 14010]のエラーは無視' ); //- 途中で止めているので
		$mode = 2;
	}
	exec( "echo $mode | " . MAP2MAP . " $fn1 $fn2", $out );
	return $out;
}

//.. _movenc: ムービーのエンコード
//- $in: 入力ファイル , $mov_id: ムービー名称（拡張子なし）
function _movie_encode( $dn_base, $mov_id ) {
	//- 入力ファイルテンプレート img/%4.jpgとか
	$template = "$dn_base/img$mov_id/img%5d.jpeg";

	$bps_s = 200;
	$bps_l = 1000;
	$flg_ok = [];
	$msg = basename( $dn_base ) . "-$mov_id:";

	foreach ( [ 'mp4', 'webm' ] as $ext ) {
		$vcodec = $ext == 'mp4' ? '-vcodec libx264' : '';
		_line( 'ムービーエンコード',  "$msg => $ext" );

		_del(
			$fn_l = "$dn_base/movie$mov_id.$ext" ,
			$fn_s = "$dn_base/movies$mov_id.$ext"
		);

		//- 大ムービー
		exec( "ffmpeg -i $template -f $ext $vcodec -g 15 -pix_fmt yuv420p "
			. "-b:v {$bps_l}k -s 500x500 -r 30 \"$fn_l\"" );
		//- 小ムービー
		exec( "ffmpeg -i $template -f $ext $vcodec -g 15 -pix_fmt yuv420p "
			. "-b:v {$bps_s}k -s 200x200 -r 30 \"$fn_s\"" );

		$flg_ok[ $ext ] = file_exists( $fn_l ) && file_exists( $fn_s );

	}
	if ( $flg_ok[ 'mp4' ] && $flg_ok[ 'webm' ] ) {
		_log( "$msg ムービーエンコード完了" );
		exec( "rm -rf $dn_base/img$mov_id" );
	} else {
		_problem( "$msg: ムービーエンコード失敗" );
	}
}

//.. _mov_snaps
//function _mov_snaps( $in, $l, $s, $ss ) {
function _mov_snaps( $dn_ent, $mov_id, $num_snap ) {
	$msg = basename( $dn_ent ) . "-$mov_id:";
	$in = "$dn_ent/img$mov_id/img$num_snap.jpeg";
	if (  
		_imgres( $in, "$dn_ent/snapl$mov_id.jpg",  '200x200'  ) &&
		_imgres( $in, "$dn_ent/snaps$mov_id.jpg",  '100x100'  ) &&
		_imgres( $in, "$dn_ent/snapss$mov_id.jpg", '75x75'  )
	)
		_m( "$msg スナップショット画像保存" );
	else
		_problem( "$msg スナップショット画像作成失敗" );
	
}

//.. _rsync: rsyncを実行
//- 引数 array: 'from', 'to', 'uname', 'opt'
function _rsync( $p ) {
	$from = $to = $uname = $opt = $tite = $dryrun = $exclude = $copylink = '';
	$uname = 'pdbj'; //- ユーザー名デフォルト（スクリプトアップロードのときだけhirofumi）
	extract( $p );

	$title = $title ?: ( is_array( $from ) ? _imp( $from ) : $from );
	_line(
		"rsync " .( is_array( $from ) ? '↓' : '↑' ),
		$title
	);

	//- opt(その他のオプション)
	if ( is_array( $opt ) )
		$opt = implode( ' ', $opt );
	if ( $dryrun )
		$opt .= ' --dry-run';
	if ( $copylink )
		$opt .= ' --copy-links';
	if ( $exclude ) {
		$fn_exclude = _tempfn( 'txt' );
		file_put_contents( $fn_exclude, $exclude );
		$opt .= " --exclude-from=$fn_exclude";
	}

	//- ログファイル名
	$fn_templog = _tempfn( 'txt' );

	//- 送信元がリモート？
	$from = _rsync_dir( $from );
	$to   = _rsync_dir( $to   );
	$from_to = _instr( ':', $from )
		? "$uname@$from $to"
		: "$from $uname@$to"
	;
	$l = "rsync -avz --log-file=$fn_templog $opt --delete -e ssh $from_to";
	_kvtable([
		'dryrun' => $dryrun ? 'YES' : 'no' ,
		'uname'	=> $uname ,
		'from'	=> $from ,
		'to'	=> $to ,
		'exclude' => $exclude ? count( explode( "\n", $exclude ) ). ' items' : 'none',
		'copylink' => $copylink ? 'YES' : 'no' ,
//		'opt'	=> $opt ,
		'command' => $l ,
	], 'rsync条件' );

	if ( NORSYNC ) {
		_m( 'no rsync mode: rsync cancelled', 'green' );
		_del( $fn_templog );
		_del( $fn_exclude );
		return;
	}

	passthru( $l );

	//- log
	$logfn = DN_PREP . '/rsync-log/log-' . date( 'Y-m-d' ) . '.txt';
	$log = file_get_contents( $fn_templog );
	
	$cnt = [];
	foreach ( (array)explode( "\n", $log ) as $line ) {
		if ( _instr( '+++++'    , $line ) ) ++ $cnt[ 'new' ];
		if ( _instr( '.....'    , $line ) ) ++ $cnt[ 'rep' ];
		if ( _instr( '*deleting', $line ) ) ++ $cnt[ 'del' ];
	}
	if ( $cnt != [] ) {
		file_put_contents( $logfn ,
			( file_exists( $logfn ) ? file_get_contents( $logfn ) : '' )
			. LINE
			. "[ rsync-log - $title - " . date( 'Y-m-d H:i:s' ) . " ]\n$msg\n"
			. $log
		);
		_log(
			"rsync: $title - new: " . (integer)$cnt['new']
			. ' / rep: ' . (integer)$cnt['rep']
			. ' / del: ' . (integer)$cnt['del']
			,
			'blue'
		);
	} else {
		_m( 'rsync完了 - 変更なし', 'blue' );
	}
	_del( $fn_templog );
	_del( $fn_exclude );

}

//... _rsync_dir: kf1のディレクトリアドレス
//- 曜日をみてディレクトリを判断
//- ディレクトリ名には"-pre"をつける
//- [ pdbj-pre/よりあとの発文字列, 'サーバー名' ]という感じ
function _rsync_dir( $path ) {
	//- 文字列ならそのまま返す、リモートなら配列なので
	if ( is_string( $path ) ) return $path;
	list( $dn, $server ) = $path;
	$server = [
		'if1'  => 'pdbjif1-p' , //- 廃止後に削除
		'iw1'  => 'pdbjiw1-p' , //- 廃止後に削除
		'lvh1' => 'pdbjlvh1' ,
//		'lvh2' => 'pdbjif1-p',
		'lvh2' => 'pdbjlvh2' ,
		'bk1'  => 'pdbjbk1' ,
	][ $server ] ?: $server ?: 'pdbjif1-p'; //- lvh2にする予定

	//- if1 削除予定
	if ( $server == 'pdbjif1-p' )
		$dn = '/home/archive/ftp/pdbj<>/'. $dn;
//		$dn = '/var/PDBj/ftp/pdbj<>/'. $dn;

	//- lvh2
	if ( $server == 'pdbjlvh2' )
		$dn = '/home/archive/ftp/pdbj<>/'. $dn;

	//- lvh1
//	if ( $server == 'pdbjlvh1' )
//		$dn = '/data/pdbj/data<>/ftp/pub/pdb/data/structures/all/'. $dn;

	//- 曜日対応
	if ( _instr( '<>', $dn ) ) {
		$w = _youbi();
		$h = date( 'H' );
		$s =  "現在: {$w}曜日 {$h}時 : 新規データ公開の";
		
		//- 水曜9時以降?
		if (
			( $w == '水' && 9 < date( 'H' ) ) || 
			$w == '木' || 
			$w == '金' ||
			( $w == '土' && date( 'H' ) < 3 )
		) {
			_m( $s . '後 -> 公開データにアクセス' );
			$dn = strtr( $dn, [ '<>' => '' ] );
		} else {
			_m( $s . '前 -> preデータにアクセス' );
			$dn = strtr( $dn, [ '<>' => '-pre' ] );
		}
	}
//	_pause( "$server:$dn" );
	return "$server:$dn";
}

//. functions: コンソール・ジョブ制御など
//.. _m: メッセージ出力 ドットだけの後は改行
//- $opt: -1: エラー, 1: 重要, 2: 改行無し

function _m( $s = '.', $opt = '' ) {
	global $_newline;
	if ( is_array( $s ) )
		$s = implode( "\n", $s );

	//- エラーメッセージ用
	if ( $opt == 'e' || $opt == 'err' || $opt == -1 )
		$s = "\n" .ES_BGRED. $s;

	//- 重要メッセージ用
	if ( $opt == 's' || $opt == 'strong' || $opt == 1 )
		$s = "\n" .ES_BGBLUE. $s;

	//- 色
	if ( $opt == 'red'   ) $s = ES_RED . $s;
	if ( $opt == 'blue'  ) $s = ES_BLUE . $s;
	if ( $opt == 'green' ) $s = ES_GRN . $s;

	if ( $s == '.' ) {
		//- ドットだけ
		echo '.';
		$_newline = 1;
	} else {
		echo ( $_newline ? "\n" : '' ) //- 前回ドットだけだったら、改行
			. $s //- 本文
			. ES_RESET
			. ( $opt == 2 ? '' : "\n" ); //- オプションが2だったら改行無し
		$_newline = 0;
	}
}

//.. _line: 出力の見出し、メッセージを線で挟む
function _line( $s, $s2 = '', $pat = '' ) {
	$line = $pat == '' ? LINE : "\n". str_repeat( $pat, 80 ) ."\n";
	if ( $s2 != '' ) {
		$s = "$s:" . ES_RESET. " $s2" .ES_CYAN;
	}
	_m( ES_CYAN . $line . $s . $line );
}

//.. _initlog
//- 使っていないっぽい
/*
function _initlog( $s = '' ) {
	global $_log ;
	if ( $s != '' )
		_line(  'ログ記録開始',  $s );
}
*/

//.. _log: ログ記録
function _log( $str, $color = '' ) {
	global $_log, $argv;
	if ( $_log[ 'name' ] == '' ) {
		$_log = [
			'time' => date( 'Y-m-d H:i:s', START_TIME ) ,
			'name' => TASK_NAME
		];
		if ( FLG_REDO )
			$_log[ 'job' ][] = 'redo';
	}

	$cnt = count( (array)$_log[ 'job' ] );
	//- 表示
	if ( $cnt == 10000 )
		_m( '-- 以下表示 省略 --' );
	if ( $cnt < 10000 )
		_m( $str, $color );


	if ( FLG_REDO ) return;
	//- ログを残す
	if ( $cnt > 500 )
		return;
	if ( $cnt == 500 )
		$str = 'ログ多数のため以下省略';
	$_log[ 'job' ][] = $str;
}

//.. _problem: 問題点保存
//- 使い方
//- _problem( 問題名 ) 問題発生時
//- 最後に _end()
$_problem = [];
$_problemfile = '';
function _problem( $str ) {
	global $_problem;

	//- データ記録
	$_problem[] = $str;
	_m( ES_BGRED. "問題点:" .ES_RESET. " $str" ); 
}

//.. _problem_quick
//	_line( "問題点集計 : $_problemfile" );
function _problem_quick( $str ) {
	$fn = DN_PREP . '/problem/_q.txt';
	file_put_contents( $fn, date('c'). " - $str\n", FILE_APPEND );
}

//.. _end: problem / log を保存
function _end() {
	global $_log, $_problemfile, $_problem;
	_line( 'スクリプト終了', TASK_NAME, '=' );

	//... log
	$cnt = count( (array)$_log[ 'job' ] );
	if ( $cnt > 0 ) {
		$fn = DN_PREP . '/mnglog/' . date( 'Y-m-d' ) . '.json';
		$j = _json_load( $fn );
		$j[] = $_log;
		_json_save( $fn, $j );
		_m( "$cnt 件のログを保存", 'green' );
	} else {
		_m( "ログ保存項目なし", 'blue' );
	}

	//... problem
//	_line( "問題点集計 : $_problemfile" );
	$fn = DN_PREP . '/problem/' . TASK_NAME . '.txt';
	$cnt = count( $_problem );
	if ( $cnt == 0 ) {
		//- 問題なし終了
		_del( $fn );
		_m( "問題なし", 'blue' );
		_save( $fn, '' );
	} else {
		//- ファイルに保存
		sort( $_problem );
		_save( $fn, $_problem );
		_m( "$cnt 個の問題", -1 );
		_m( implode( "\n", $_problem ), 'red' );
	}

}

//.. _joblist
//- EM-PDBとEMDBのIDのリストを返す
function _joblist( $mode = '' ) {
	if ( $mode != 'pdb' ) foreach ( _idlist( 'emdb' ) as $id ) {
		$joblist[] = [ 'db'=>'emdb', 'id'=>$id, 'did' => "emdb-$id" ];
		$did[] = "emdb-$id";
	}
	if ( $mode != 'emdb' ) 	foreach ( _idlist( 'epdb' ) as $id ) {
		$joblist[] = [ 'db'=>'pdb', 'id'=>$id, 'did' => "pdb-$id" ];
		$did[] = "pdb-$id";
	}
	return ( $mode == 'did' ) ? $did : $joblist;
}

//.. _hourly_msg: hourlyの結果出力、メールへメッセージ
function _hourly_msg( $str ) {
	file_put_contents( FN_HOURLY_MSG, 
		( file_exists( FN_HOURLY_MSG )
			? file_get_contents( FN_HOURLY_MSG ) ."\n\n"
			:  ''
		) . $str
	);
}

//.. _idfilter: ID範囲
//- IDが条件にあっているかを返す
function _idfilter( $id ) {
	global $_startid, $_endid, $_limitid;
	if ( $_startid == '' ) $_startid = 0;
	if ( $_endid   == '' ) $_endid = 10000000;
	$ret = ( $_startid <= $id && $id <= $_endid );

	//- 1/3 とか 3/5 とか
	if ( $_limitid != '' )
		if ( $id % substr( $_limitid, -1 ) + 1 != substr( $_limitid, 0, 1 ) ) {
			$ret = false;
		}
	return ! $ret;
}

//.. _php: 別のスクリプト実行
function _php( $v1, $val2 = ''  ) {
	chdir( DN_SCRIPT );
	
	//- コマンドラインオプション解釈
	list( $cmd, $opt ) = explode( ' ', $v1, 2 );
	$opt = trim( "$opt $val2" );
	_line( '外部スクリプト実行', "$cmd $opt" );

	//- .php 省略
	if ( file_exists( $f = "$cmd.php" ) )
		$cmd = $f;

	//- ファイル名の一部
	if ( ! file_exists( $cmd ) ) {
		$hits = [];
		foreach ( glob( "$cmd*.php" ) as $hit ) {
			if ( _instr( 'test', $hit ) ) continue;
			if ( _instr( 'bkup', $hit ) ) continue;
			$hits[] = $hit;
		}
		if ( count( $hits ) == 0 ) {
			_problem_quick( "php関数: ファイルがない - $v1" );
			return;
		}
		if ( count( $hits ) > 1 ) {
			_problem_quick( "php関数: 複数ヒット $v1\n ->" . _imp( $hits ) );
			return;
		}
		$cmd = $hits[0];
	}
	//- 実行
	_line( '外部スクリプト実行', "$cmd $opt" );
	passthru( "php $cmd $opt" );
}

//.. _jobjson
//- jobsディレクトリからjsonを読み込んで返す
//- dn: ディレクトリ名
/*
function _jobjson( $dn ) {
	$fns = _scandir( $dn, '.json' );
	if ( count( $fns ) == 0 ) return;
	$fn = "$dn/" . $fns[0];
	$j = _json_load( $fn );
	_del( $fn );
	_m( "$fn : deleted" );
	return $j;
}
*/

//.. _proc: 同じ処理を複数同時に走らせるのを防止
// $f: proc_id (undefinedなら、終了)
//- 実行中なら1を返す 
function _proc( $f = false ) {
	global $proc_id, $args;

	//- プロセス登録解除 (引数なしで呼ばれた)
	if ( ! $f ) {
		_del( DN_PROC . "/$proc_id" );
		return 0;
	}

	//- 強制終了
	if ( file_exists( $fn = DN_PROC . '/stop' ) )
		die( "##### 強制終了、解除するには： \nrm $fn\n\nまたは\nmng start" );

	//- noproc 強制実行
	if ( $args[ 'noproc' ] ) return;

	//- 問い合わせ、
	$proc_id = $f;
	$fn = DN_PROC . "/$proc_id";
	if ( file_exists( $fn ) ) {
		_m( "[proc] $proc_id 実行中", 1 );
		return 1;
	} else {
		touch( $fn );
		return 0;
	}
}

//.. _count: ループカウンター
//- $n いくつ置きにカウントするか
//- $n2 ブレークするカウント

function _count( $n = 0, $n2 = 0 ) {
	global $_loop_counter;
	if ( is_string( $n ) ) {
		$n = [
			'pdb'	=> 5000 ,
			'emdb'	=> 1000 ,
			'both'	=> 1000 ,
			'epdb'	=> 500  ,
			'chem'	=> 1000 ,
			'sas'   => 100
		][ $n ];
	}
	
	if ( $n == 0 ) {
		$_loop_counter = 0;
		return;
	}

	++ $_loop_counter;
	if ( $_loop_counter == 1 ) {
		_m( ES_BGBLUE. "count start" .ES_RESET . " (1/$n)" );
		return;
	}

	//- 経過時間も表示
	if ( $n > 5 && $_loop_counter % $n == 0 )
		_m( ES_BGBLUE.TASK_NAME.ES_RESET. ' '
			. substr( '        ' . number_format( $_loop_counter ), -11 ) 
			. ' / '
			. round( ( time() - START_TIME ) / 60, 1 )
			. ' min'
		);
	if ( $n2 > 0 && $n2 < $_loop_counter ) {
		_m( "# $_loop_counter / loop broken !", 1 );
		return 1;
	}
}

//.. _pause()
function _pause() {
	foreach ( func_get_args() as $v )
		_m( is_array( $v ) || is_object( $v )
			? print_r( $v, 1 )
			: ( trim( $v ) ?: 'キー入力待ち:' )
		);
	$line = trim( fgets( STDIN ) );
}

//.. _die:
function _die( $a = '' ) {
	_m( "強制終了", 1 );
	print_r( $a );
	die();
}

//.. _mail: メールを送る
function _mail( $sub, $str ) {
	$maxlen = 40960;
	if ( strlen( $str ) > $maxlen )
		$str = substr( $str, 0, $maxlen ) . "\n ===== 以下省略 =====";
	$fn = DN_PREP. '/mail/' . date( 'Y-m-d' ) . '.json.gz';
	
	$json = _json_load( $fn );
	$json[ date( 'H:i:s' ). " - $sub" ] = $str;
	_json_save( $fn, $json );
	_m( $sub. "\n-----\n". $str );
}
/*
function _mail( $sub, $str ) {
 	require 'vendor/autoload.php';
	$mail = new PHPMailer(true);

	if ( strlen( $str ) > $maxlen )
		$str = substr( $str, 0, $maxlen ) . "\n ===== 以下省略 =====";
	$from	= 'hirofumi<hirofumi@protein.osaka-u.ac.jp>';
	$smtp_user = 'hirofumi@protein.osaka-u.ac.jp';
	$to		= 'hirofumi<hiro111suzu@gmail.com>';
	$subj	= "[EMN-mng] $sub";
	
	$maxlen = 40960;
	try {
		$mail->isSMTP();
		$mail->Host = 'postman.protein.osaka-u.ac.jp';
		$mail->CharSet = 'utf-8';
		$mail->SMTPAuth = true;
		$mail->Username = $smtp_user;
		$mail->Password = 'bi1008BI1008';
		$mail->SMTPSecure = 'ssl';
		$mail->Port = 465;
	 
		$mail->setFrom($smtp_user, 'hirofumi');
		$mail->Subject = $sub;
		$mail->Body = $str;
		$mail->addAddress( 'hiro111suzu@gmail.com', 'hirofumi' );	 
		$mail->isHTML( false );
		$mail->send();
	 
		_m( 'Message has been sent' );
	} catch (Exception $e) {
		_m( 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo );
	}

}
*/
/*
function _mail( $sub, $str ) {
	$maxlen = 40960;
	if ( strlen( $str ) > $maxlen )
		$str = substr( $str, 0, $maxlen ) . "\n ===== 以下省略 =====";
	
	$from	= 'hirofumi<hirofumi@protein.osaka-u.ac.jp>';
	$to		= 'hirofumi<hiro111suzu@gmail.com>';
	$subj	= "[EMN-mng] $sub";

	$mime = new Mail_Mime();
	$mime->setTxtBody( mb_convert_encoding( $str, "ISO-2022-JP", "UTF-8") );
	$body = $mime->get(array(
		"head_charset" => 'ISO-2022-JP',
		"text_charset" => 'ISO-2022-JP',
	));

	$header = $mime->headers(array(
		"To" => $to,
		"From" => mb_convert_encoding( $from, "ISO-2022-JP", "UTF-8"),
		"Subject" => mb_convert_encoding( $subj, "ISO-2022-JP", "UTF-8"),
	));

	$mail = Mail::factory( 'smtp' ,
		array( 'host'	=> 'postman.protein.net' )
	);
	$ret = $mail->send( $to, $header, $body );

	if ( PEAR::isError( $ret ) )
		_m( "エラーメッセージ：" . $ret->getMessage() );
	else
		_m( 'メール送信完了' );
}
*/
/*
function _xxmail($to, $subject, $body, $headers) {
$smtp = stream_socket_client('tcp://postman.protein.net:465', $eno, $estr, 30);

$B = 8192;
$c = "\r\n";
$s = 'hirofumi<hirofumi@protein.osaka-u.ac.jp>';

fwrite($smtp, 'helo ' . $_ENV['HOSTNAME'] . $c);
  $junk = fgets($smtp, $B);
_m( $estr );

// Envelope
fwrite($smtp, 'mail from: ' . $s . $c);
  $junk = fgets($smtp, $B);
_m( $estr );
fwrite($smtp, 'rcpt to: ' . $to . $c);
  $junk = fgets($smtp, $B);
_m( $estr );
fwrite($smtp, 'data' . $c);
  $junk = fgets($smtp, $B);
_m( $estr );
// Header 
fwrite($smtp, 'To: ' . $to . $c); 
if(strlen($subject)) fwrite($smtp, 'Subject: ' . $subject . $c); 
_m( $estr );
if(strlen($headers)) fwrite($smtp, $headers); // Must be \r\n (delimited)
_m( $estr );
fwrite($smtp, $headers . $c);
_m( $estr );

// Body
if(strlen($body)) {
	fwrite($smtp, $body . $c);
	_m( $estr );
}

fwrite($smtp, $c . '.' . $c);
  $junk = fgets($smtp, $B);
_m( $estr );
// Close
fwrite($smtp, 'quit' . $c);
  $junk = fgets($smtp, $B);
 _m( $estr );
fclose($smtp);
}
*/
//.. _redoid
$_redo_ids = [];
function _redo_id( $id ) {
	global $_redo_ids, $argar;
	if ( count( $_redo_ids ) == 0 ) {
		if ( $argar[ 'id' ] != '' ) {
			foreach ( explode( ',', $argar[ 'id' ] ) as $s ) {
				$_redo_ids[ $s ] = true;
			}
		}
		if ( count( $_redo_ids ) == 0 )
			$_redo_ids[ '_' ] = false;
		else
			_m( 'やり直し候補: ' . _imp( array_keys( $_redo_ids ) ) );
	}
	
	if ( ! $_redo_ids[ $id ] ) return;
	_m( "$id: やりなおし", 1 );
	return true;
}

//.. _cnt: 色々数える
$_data_cnt = [];
function _cnt(){
	global $_data_cnt;
//	if ( func_get_args(0) == 'sort' ) {
//		
//	}

	if ( func_num_args() == 0 ) {
		if ( $_data_cnt == [] ) return;
		_kvtable( $_data_cnt, 'データ数集計' );
		$_data_cnt = [];
		return;
	}

	foreach ( func_get_args() as $val )
		++ $_data_cnt[ $val ];
}

//.. _cnt2: 色々数える、複数
$_data_cnt2 = [];
function _cnt2( $val = '__show', $categ = '' ){
	global $_data_cnt2;
	if ( $val == '__show' ) {
		foreach ( $_data_cnt2 as $categ => $cnt ) {
			if ( ! $cnt ) continue;
			_kvtable( $cnt, "データ数集計 - $categ" );
		}
		$_data_cnt2 = [];
		return;
	} else {
		++ $_data_cnt2[ $categ ][ $val ];
	}
}

//.. _kvtable: テーブルで表示
function _kvtable( $ar, $title = '' ) {
	$max = 0;
	if ( ! is_array( $ar ) ) {
		_m( "$title：配列じゃない", -1 );
		return;
	}
	foreach ( array_keys( $ar ) as $s )
		$max = max([ $max, strlen( $s ) ]);
	if ( $title )
		_m( ES_BGBLUE. $title );
	foreach ( $ar as $k => $v ) {
		if ( is_array( $v ) ) $v = _imp( $v );
		if ( is_numeric( $v ) ) $v = number_format( $v );
		_m(
			ES_BGBLUE. substr( str_repeat( ' ', 100 ). $k, -3 - $max )
			. ':'. ES_RESET. " $v"
		);
	}
	_m( "\n" );
}

//. functions: データエントリ
//.. id2did
//- 1001 => emdb-1001
function _id2did( $id ) {
	if ( _inlist( $id, 'pdb' ) )
		return "pdb-$id";
	if ( _inlist( $id, 'emdb' ) )
		return "emdb-$id";
	return $id;
}

//- E1001とかP1oelとかにする
function _id2did2( $id ) {
	$i = _id2did( $id );
	return strtoupper( substr( $i, 0, 1 ) ) . substr( $i, -4 );
}

//.. _id2db
function _id2db( $id ) {
	if ( strlen( $id ) < 5 )
		$id = _id2did( $id );
	return ( substr( $id, 0, 1 ) == 'e' ) ? 'emdb' : 'pdb';
}

//.. _repid: 置き換わったIDなら、新しいIDを返す
function _repid( $i ) {
	global $_repids;
	$i = strtolower( $i );
	if ( $_repids == '' )
		$_repids = _json_load( DN_PREP. '/replacedid.json' );
	return $_repids[ $i ] ?: $i;
}

//. functions: 文字列処理
//.. _x: xmlデータ用 トリムして、適当な変換をして返す
function _x( $s, $mode = 'str' ) {
	global $rep_in, $rep_out;
	$s = $mode == 'num'
		? floatval( $s )
		: preg_replace( $rep_in[ $mode ], $rep_out[ $mode ], trim( (string)$s ) )
	;
	if ( ! in_array( $s, [ 'n/a', 'na', 'N.A', 'n.a', 'na', 'NA' ] ) )
		return $s;
}

//.. _namerep
//- 人の名前、つながってる奴を分割して配列にして返す
function _namerep( $str ) {
	$str = preg_replace(
		array( '/\band\b/', '/,Jr,/' ) ,
		array( ', '       , ' Jr,' ) ,
		_repstr( $str )
	);
	$ret = [];
	foreach ( explode( ',', $str ) as $s ) {
		$ret[] = trim( preg_replace(
			array( '/\./', '/  +/' ),
			array( '. ', ' ' ) ,
			$s
		) );
	}
	return implode( '|', $ret );
}

//.. _taxoname: 生物種名、処理
/*
function _taxo_name( $s, $type = '' ) {
	$n = ucfirst( strtolower( trim( preg_replace( '/ ?\(.+?\) ?/', '', $s ) ) ) );
	$ret[ 'n' ] = $n;
	$ret[ '_' ] = strtr( $n, [ ' '=>'_', ':'=>'_', '/'=>'_'  ] );
	$ret[ '+' ] = strtr( $n, ' ', '+' );
	return ( $type == '' ) ? $ret : $ret[ $type ];
}
*/

//.. _instr: 文字列が含まれるか？
//- mng版、CGI版はarray対策がない
function _instr( $needle, $heystack ) {
	if ( is_array( $heystack) )
		$heystack = implode( '~~~', $heystack );
	return stripos( $heystack, $needle ) !== false;
}

//.. _youbi: 曜日を返す
function _youbi( $t = 'today' ) {
	return [ '日', '月', '火', '水', '木', '金', '土' ][
		date( 'w', $t == 'today' ? time() : $t ) 
	];
}

//.. _rel_date: リリース日
//- mng版 リリース日
function _rel_date( $weeks = 0 ) {
	$youbi = date( 'N' );
	//- 月: 1, 水: 3, 金:  5, 日: 7
	//- 月: 2, 水: 0, 金: -2, 日: +3
	//- 土日なら10-youbi
	$dif = ( $youbi < 6 ? 3 : 10 ) - $youbi;
	$rel = time() + $dif * 3600 * 24;

	if ( $weeks ) {
		$rel -= ( $weeks * 7 * 24 * 3600 );
	}
	return date( 'Y-m-d', $rel );
}


//.. _json_obj: jsonの objectをarrayに
function _json_obj( $o ) {
	return ( is_array( $o ) ) ? $o : [ $o ];
}

//. sqlite系
//.. _sqlitefile: sqliteファイルをローカルにコピー
/*
function _sqlite_file( $n = 'main', $opt = [] ) {
	if ( $n == 'doc' )
		$n = '../doc/doc';
	$fn = realpath( DN_DATA . "/$n.sqlite" );
	if ( ! file_exists( $fn ) && ! $opt[ 'new' ] )
		die( "no database file: $fn" );
	_m( "SQLite database file: $fn" ) ;
	return "sqlite:$fn";

}
//.. _makequery
//- クエリ文字列
function _make_query( $a ) {
	$ret = [];
//	echo _t( 'pre', print_r( $a ,1) );
	foreach ( $a as $k => $v ) {
		if ( $v == '' || $v == [] ) continue;

		$ret[] = strtoupper( $k );
		if ( is_array( $v ) )
			$v = implode( $k == 'where' ? ' and ' : ',', $v );
		$ret[] = $v;

		//- from がなければ付け足す
		if ( $k == 'select' && $a[ 'from' ] == '' )
			$ret[] = 'FROM main';
	}
	return implode( ' ', $ret );
}

//.. _sqlite_chk_mng
function _sqlite_chk_mng( $pdo ) {
	$er = $pdo->errorInfo();
	if ( $er[0] == '00000' ) return;
	die( "DB error\n" .  print_r( $er, 1 ) );
}
*/

//.. _kwfile2str
function _kwfile2str( $fn_list ) {
	$terms = [];
	foreach ( is_array( $fn_list ) ? $fn_list : [ $fn_list ] as $fn ) {
		$terms = array_merge( $terms, (array)_file( $fn ) );
	}
	return ' '
		. implode( ' | ', _uniqfilt( $terms ) )
		. ' '
	;
}

//.. _as_json
function _as_json( $data ) {
	$data = array_filter( $data );
	return $data ? _to_json( $data ) : '';
}

//.. _kvdb
function _kvdb( $fn, $key ) {
	return json_decode( _ezsqlite([
		'dbname' => $fn ,
		'select' => $val ,
		'where'  => [ 'key', $key ] ,
	]), true );
}

//. function その他
//.. _mng_conf: 設定ファイルから読み込み
function _mng_conf( $categ, $name = '' ) {
	if ( ! defined( 'MNG_CONF' ) )
		define( 'MNG_CONF', _tsv_load2( DN_EDIT. '/mng_config.tsv' ) );
	return $name == ''
		? MNG_CONF[ $categ ]
		: MNG_CONF[ $categ ][ $name ]
	;
}

//.. _met_code: メソッドコード
//- code 位置文字コードを返す
function _met_code( $str, $opt = false ) {
	if ( $opt == 'code2name' ) {
		return [
			'2' => 'electron crystallography' ,
			'h' => 'helical reconstruction' ,
			's' => 'single particle reconstruction' ,
			'a' => 'subtomogram averaging' ,
			't' => 'electron tomography' 
		][ $str ];
	}
	return [
		'tomography'			=> 't' ,
		'subtomogramaveraging'	=> 'a' ,
		'singleparticle' 		=> 's' ,
		'icos'					=> 'i' ,
		'helical'				=> 'h' ,
		'twodcrystal'			=> '2' ,
		'crystallography'		=> '2' ,
		'electroncrystallography' => '2' ,
		'electronmicroscopy'	=> 'e'
	][ trim( strtolower( strtr( $str, [ ' ' => '' ] ) ) ) ];
}

//.. _omomode: omokageモード決定
function _omomode(){
	global $argar;
	$mode = $argar[ 'mode' ] ?: OMO_MODE ;
	_m( "omokage mode = $mode" );
	return $mode;
}

//.. _delobs_pdb
//- なくなったIDのファイルを削除
function _delobs_pdb( $pat, $test = false ) {
	_count();
	if ( ! _instr( '*', $pat ) )
		$pat = _fn( $pat, '*' );

	$glob = glob( $pat );
	_line( 'PDB 取り消しデータの削除',
		$pat .' ('. number_format( count( $glob ) ) .')'
	);

	$cnt = 0;
	foreach ( $glob as $pn ) {
		_count( 20000 );
		$id = substr( basename( $pn ), 0, 4 );
		if ( _inlist( $id, 'pdb' ) ) continue;
		if ( $test ) {
		 	_m( ES_GRN. "$id: 無くなったエントリ、ファイル消去すべき" );
		} else {
			_del( $pn );
	 	 	_m( ES_GRN. "$id: 無くなったエントリ、ファイル消去" );
	 	 	++ $cnt;
		}
	}
	_m( $cnt == 0 ? ES_BLUE. '取り消しデータなし' : ES_GRN. "$cnt 個のファイルを削除" );
}

//.. _delobs_emdb
//- なくなったIDのファイルを削除 EMDB
function _delobs_emdb( $fntype, $test = false ) {

	$total = 0;
	$cnt = 0;
	foreach ( _idloop( $fntype, 'EMDB 取り消しデータの削除: ' . $fntype ) as $pn ) {
		++ $total;
		$id = _numonly( _fn2id( $pn ) );

//		_pause( $id );
		if ( _inlist( $id, 'emdb' ) ) continue;
		if ( $test ) {
		 	_m( "$id: 無くなったエントリ、ファイル消去すべき", 'green' );
		} else {
			_del( $pn );
	 	 	_log( "$id: 無くなったエントリ、ファイル消去", 'green' );
	 	 	++ $cnt;
		}
	}
	_m( "全 $total データをチェック" );
	_m( $cnt == 0 ? ES_BLUE.'取り消しデータなし' : ES_GRN."$cnt 個のファイルを削除");
}

//.. _delobs_misc
function _delobs_misc( $pat, $ids, $test = false ) {
	//- patは _fn() のパターン
	//- idsは _inlist() のファイル名か、ID-flag配列
	$total = 0;
	$cnt = 0;
	foreach ( _idloop( $pat, "廃止データ削除 - $pat" ) as $pn ) {
		++ $total;
		$id = _fn2id( $pn );
		if ( is_array( $ids ) ) {
			if ( $ids[ $id ] ) continue;
		} else {
			if ( _inlist( $id, $ids ) ) continue;
		}

		if ( $test ) {
		 	_m( "$id: 無くなったエントリ、ファイル消去すべき", 'green' );
		} else {
			_del( $pn );
	 	 	_log( "$id: 無くなったエントリ、ファイル消去", 'green' );
	 	 	++ $cnt;
		}
	}
	_m( "全 $total データをチェック" );
	_m( $cnt == 0 ? ES_BLUE.'取り消しデータなし' : ES_GRN."$cnt 個のファイルを削除");
}

//.. _img_largest
//- 一番ファイルサイズの大きい画像のファイル名を返す
function _img_largest( $files ) {
	//- 重み付け あまり違わないなら正面
	$wt = [
		1 => 1 		, //- reset
		2 => 0.95	, //- x 180
		3 => 0.925	, //- x 90
		4 => 0.90	, //- x -90
		5 => 0.92	, //- y 90
		6 => 0.89	, //- y -90
	];

	$top_size = 0;
	$top_file = '';
	foreach ( $files as $cnt => $fn ) {
		$size = filesize( $fn ) * $wt[ $cnt ];
		if ( $size <= $top_size ) continue;
		$top_file = $fn;
		$top_size = $size;
	}
	return $top_file;
}

//.. _idloop: ループで使うファイル名リスト作成
$_pattern_idconv = '';
function _idloop( $type, $title = '' ) {
	global $_pattern_idconv;
	$title = $title ?: $type;
	$p = _fn( $type, '*' );
	$_pattern_idconv = explode( '*', $p, 2 );
	$g = glob( $p );
	_line( 'ループ',  "$title (" . number_format( count( $g ) ). ')' );
	return $g;
}

function _fn2id( $fn ) {
	global $_pattern_idconv;
	return strtr( $fn, [ $_pattern_idconv[0] => '', $_pattern_idconv[1] => '' ] );
}

//.. _tsv_define 使っていない
function _tsv_define( $type ) {
	foreach ( _tsv_load2( 'define.tsv' )[ $type ] as $k => $v ) {
		if ( defined( $k ) )
			_problem( ":_tsv_define 定義済み: $k => $v" );
		else
			define( $k, trim( $v ) );
	}
}

//.. _reg_match
function _reg_match( $pattern, $subj ) {
//	$flg = ;
	return preg_match_all( $pattern, $subj, $ret )
		? $ret
		: []
	;
}

//. class cls_entid
class cls_entid extends abs_entid { }

//. class cls_sqlw
class cls_sqlw {
	protected $pdo, $col, $dbfn, $tablename, $newfile, $stmt_tplt;
	protected $vals = [], $cnt = [], $err_vals =[];
	function __construct( $a = '' ) {
		if ( $a != '' )
			$this->init( $a );
		return $this;
	}

	//.. init
	function init( $a ) {

		//- DBパラメータ
		$new = true;
		$fn = 'main';
		$tablename = 'main';
		$cols = [];
		$indexcols = [];
		extract( $a );

		_line( 'SQlite DB書き込み準備', $fn );

		//- ファイル名
		if ( ! _instr( '/', $fn ) ) {
			$fn =  $fn == 'doc'
				? 'doc/doc.sqlite'
				: realpath( DN_DATA ) . "/$fn.sqlite"
			;
		}
		//- 新規ファイルか
		if ( $new )
			_del( $fn );
		if ( !file_exists( $fn ) )
			$new = true;
		$this->pdo = new PDO( "sqlite:$fn", '', '' );
		$this->pdo->beginTransaction();
		$msg = [
			'file'       => $fn ,
			'new/remake' => $new ? 'yes' : 'no'
		];

		//- テーブル作成
		if ( $new ) {
			$c = implode( ',', $cols );
			$this->q( "CREATE TABLE $tablename($c)" );
			$msg[ 'columns' ] = strtr( $c, [ "\t" => ' ' ] ); 
			
			$i = [];
			foreach ( (array)$indexcols as $c ) {
				$this->q( "CREATE INDEX i$c ON $tablename($c)" );
				$i[] = "($c)";
			}
			$msg[ 'index cols' ] = $i == [] ? 'なし' : $i;
		}

		//- ステートメント用テンプレート
		$this->stmt_tplt = 'insert into main values ('
			. implode( ',', array_fill( 0, count( $cols ), '?' ) ) //- "?,?,?"文字列
			. ')'
		;
		$msg[ 'template' ] = $this->stmt_tplt;
		_kvtable( $msg );

		$this->dbfn = $fn; //- 確認用
		$this->tablename = $tablename; //- 未対応だけど
		$this->newfile = $new;
		return $this;
	}

	//.. set
	function set( $vals ) {
		$flg = true;
		$stmt = $this->pdo->prepare( $this->stmt_tplt );
		$this->err();
//		if ( $stmt == false )
//			die( "ステートメント作成失敗: {$this->stmt_tplt}" );
		foreach ( $vals as $n => $v )
			$stmt->bindValue( $n + 1, $v );
		$this->err();
		if ( ! $stmt->execute() ) {
//			_m( '書き込み失敗: ' . _imp( $vals ) );
			++ $this->cnt[ 'load error' ];
			$flg = false;
			$this->err_vals[] = implode( "\t", $vals );
		}  else {
			++ $this->cnt[ 'loaded' ];
		}
		return $flg;
	}

	//.. getlist
	function getlist( $key ) {
		return $this
			->q( "SELECT $key FROM {$this->tablename}" )
			->fetchAll( PDO::FETCH_COLUMN, 0 )
		;
	}
	
	//.. del
	function del( $where ) {
		++ $this->cnt[ 'deleted' ];
		return $this->q( "DELETE FROM {$this->tablename} WHERE $where" );
	}

	//.. q
	function q( $q ) {
		$ret = $this->pdo->query( $q );
		$this->err( $q );
		return $ret;
	}

	//.. err
	function err( $q = '' ) {
		$er = $this->pdo->errorInfo();
		if ( $er[0] != '00000' ) {
			die( "DB error"
				. "\nDB file: {$this->dbfn}"
				. "\nquery: $q"
				. "\nerror message\n"
				. print_r( $er, 1 )
			);
		}
		return $this;
	}

	//.. get_err_vals
	function get_err_vals() {
		return implode( "\n", $this->err_vals );
	}

	//.. end
	function end() {
		_line( 'Sqlite DB 終了処理', $this->dbfn );
		if ( ! $this->newfile ) {
			echo "\nDBバキューム開始 ... ";
			$this->pdo->exec( "VACUUM" );
			echo "完了\n";
		}
		_kvtable( $this->cnt );
		$this->pdo->commit();
		$ok = file_exists( $this->dbfn );
		if ( function_exists( 'log' ) ) {
			if ( $ok )
				_log( "DB書き込み完了 {$this->dbfn}" );
			else
				_problem( ":DB作成失敗 {$this->dbfn}", -1 );
		}
		unset( $this->pdo );
	}
}

//. class blist
class cls_blist {
	protected $black_list, $type;
	function __construct( $in, $type = 'ブラックリスト' ) {
		$list = is_array( $in ) ? $in : _tsv_load2( DN_EDIT. '/blist.tsv' )[ $in ];
		if ( ! $list || ! is_array( $list ) ) {
			_problem( 'ブラックリスト読み込み失敗' );
			$this->black_list = false;
		}
		$this->black_list = $list;
		$this->type = $type;
		_m( "$type 読み込み - ". count( $this->black_list ). '件', 'blue' );
	}
	function inc( $i ) {
		if ( ! $this->black_list ) return;
		if ( ! array_key_exists( $i, $this->black_list ) )
			return false;
		_m( "$i: ". ( $this->black_list[ $i ] ?: $this->type ), 'green' );
		return true;
	}
}

//. class cls_pubmedid_tsv
class cls_pubmedid_tsv {

	//.. param
	protected $data, $newdata, $db, $fn = DN_EDIT. '/pubmed_id.tsv', $whitelist;

	//.. コンストラクタ
	function __construct( $db, $white_list = [] ) {
		_line( 'Pubmed-ID tsv data' );
		$this->db = $db;
		if ( ! file_exists( $this->fn ) )
			_problem( ':ファイルがない: ' . $this->fn );
		$this->data = _tsv_load2( $this->fn );
		$this->whitelist  = explode( ' ', _mng_conf( 'pubmed_id_tsv_white_list', $db ) );
		_kvtable([
			'DB' => $db ,
			'whitelist' => _imp( $this->whitelist )
		], 'tsv data' );
	}

	//.. get
	function get( $id, $pmid_xml ) {
		$pmid_tsv = $this->data[ $this->db ][ $id ];
		//- tsvとの整合性チェック
		if ( $pmid_tsv == '' ) {
			//- tsvに記載なし
			if ( $pmid_xml != '' ) {
				//- xmlに記載あり -> そのまま採用、tsvには書き込まない
				$ret = $pmid_xml;
			} else {
				//- xmlに記載無し -> tsvに空データ書き込み
				$this->newdata[ $id ] = '';
			}
		} else {
			//- tsvに記載あり
			if ( $pmid_xml == $pmid_tsv ) {
				//- xmlと一致 -> そのまま採用、tsvには書き込まない
				$ret = $pmid_xml;
			} else {
				//- xmlと不一致 -> tsvを採用、tsvの内容は維持
				$ret = $pmid_tsv;
				$this->newdata[ $id ] = $pmid_tsv;
				if ( $pmid_xml != '' && ! in_array( $id, $this->whitelist ) ) {
					_problem( "$id: Pubmed-IDが一致しない:{$pmid_tsv} != {$pmid_xml}" );
				}
			}
		}
		return $ret;
	}
	//.. save
	//- pubmed情報を保存
	function save() {
		_kvtable([
			'new' => count( $this->newdata ) ,
			'old' => count( $this->data[ $this->db ] )
		], 'IDの数' );
		
		if ( $this->data[ $this->db ] === $this->newdata ) {
			_m( 'pubmed-tsv 変更なし' );
			return;
		}
			
		$this->data[ $this->db ] = $this->newdata;
		if ( _tsv_save2( $this->fn, $this->data ) )
			_log( 'ファイル変更: ' . $this->fn );
		else
			_problem( ':ファイル書き込み失敗: ' . $this->fn );
		return;
	}
}

//. class cls_kvdb
class cls_kvdb {
	protected $obj_db;

	//.. constructror
	function __construct( $fn_db ) {
		$this->obj_db = new cls_sqlw([
			'fn'	=> $fn_db ,
			'cols'	=> [
				'key UNIQUE COLLATE NOCASE' ,
				'val' ,
			] ,
			'indexcols' => [ 'key' ],
			'new'		=> true
		]);
	}

	//.. set
	function set( $key, $val )  {
		$this->obj_db->set([
			$key, 
			json_encode( $val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) 
		]);
	}

	//.. end
	function end() {
		$this->obj_db->end();
	}
}

