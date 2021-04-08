メインDB書き込み
----------
<?php
//. misc. init
require_once( "commonlib.php" );
_initlog( "both-4: make main database" );
$j2k = [];

//- 統計カウントルーチンを切り離すまで、redoモード
$_redo = 1;

//- ロードしたファイルのタイムスタンプデータ
if ( $_redo )
	_del( $_maindbfn, $filetimefn );

//$filetimefn = "datatime.json";
//$filetime = _json_load( $filetimefn );

//. 国
$nation_in  = [
	'/^.+, (.+)$/' ,
	'/Electronic address:.+$/' ,
	'/ [^ ]+@[^ ]+/' ,
	'/\[.+\]/' ,
	'/[\.; ]+$/' ,
	'/[0-9]+/' ,
	'/^ +/' ,
	'/( +| and)$/' ,
	'/Unit.+King.+|London.*|U\.K/' ,
	'/.+USA$/' ,
	'/P\..+China/' ,
	'/Danish.+$/' ,
	'/Canada.+/' ,
	'/.+France/'
];

$nation_out = [
	'$1' ,
	'' ,
	'' ,
	'' ,
	'' ,
	'' ,
	'' ,
	'' ,
	'UK' ,
	'USA' ,
	'Taiwan' ,
	'Denmark' ,
	'Canada' ,
	'France',
];

$nation_rep = [
	'United States of America'	=> 'USA' ,
	'United States'				=> 'USA' ,
	'United Kingdom'			=> 'UK' ,
	'Republic of Korea'			=> 'Korea' ,
	"People's Republic of China"	=> 'Taiwan' ,
	'NY'						=> 'USA',
	'MA'						=> 'USA',
	'CA'						=> 'USA',
	'CT'						=> 'USA',
	'MD'						=> 'USA',
	'PA'						=> 'USA',
	'OH'						=> 'USA',
	'TX'						=> 'USA',
	'IN'						=> 'USA',
	'Indiana'					=> 'USA' ,
	'Japana'					=> 'Japan' ,
	'JST'						=> 'Japan' ,
];

//. init
$tabledata = [];
$columns = '';
$indexcols = []; //- インデックス作成するカラム

foreach ( _file( DN_PREP . '/tableprep.tsv' ) as $line ) {
	$ar = explode( "\t", $line );
	if ( $ar[0] == '.' ) continue;
	if ( $ar[0] == ''  ) continue;
	$tabledata[ $ar[ 0 ] ] = [
		'ename' => $ar[ 1 ] , //- 英語名
		'jname' => $ar[ 2 ] , //- 日本語名
		'categ' => $ar[ 3 ] , //- カテゴリ
		'mode'  => $ar[ 4 ] , //- カラムのモード
		'multi' => $ar[ 5 ] , //- 複数の値を持つカラム |区切り
		'page'  => $ar[ 6 ] , //- 表ページで使うか
		'count' => $ar[ 7 ] , //- 統計ページ用にカウントするか
	];

	$m = '';	
	if ( $ar[7] ) //- 統計に使うなら、大文字小文字関係なしにする
		$m = 'COLLATE NOCASE';
	if ( $ar[4] !='' )
		$m = $ar[4]; //- モード、UNIQUE / INTEGER / REAL

	$columns[] = $ar[0] . ( $m == '' ? '' : "\t$m" );

	//- 統計に使うカラムて複数値をもつカラムでなければインデックス作成
	if ( $ar[ 7 ] and !$ar[ 5 ] )
		$indexcols[] = $ar[ 0 ];
}
$columns = implode( ',', $columns );
//die( $columns );
_comp_save( DN_DATA . '/tabledata.json', $tabledata );

//.. データの置き換えデータ
$datarep = [];
$cn = 'dummy';
foreach ( _file( DN_PREP . '/datarep.tsv' ) as $l ) {
	$a = explode( "\t", $l );
	if ( $a[0] == '' ) continue;
	if ( $a[0] == '.' ) {
		$cn = $a[1];
		continue;
	}

	$in = strtr( $a[0], [ '~' => '[ \-]?', '[]' => '[A-Z]+' ] );
	$datarep[ $cn ][ 'in' ][] = "/$in/i";
	$datarep[ $cn ][ 'out' ][] = $a[1];
}

$datacount = [];

//.. DB準備

$maindb = new PDO( "sqlite:$_maindbfn", '', '' );
$maindb->beginTransaction();

//.. テーブルがなければ作成
$res = $maindb->query( 
	"select name from 'sqlite_master' where type='table' and name='main'" ) ;

if ( $res->fetch() == '' ) {
	$res = $maindb->query( "CREATE TABLE main( $columns )" );
	_log( "New DB file created" );
	
	//- インデックス作成
	foreach ( $indexcols as $c ) {
		$res = $maindb->query( "CREATE INDEX i$c ON main($c COLLATE NOCASE)" );
		$er = $maindb->errorInfo();
	//	if ( $er[0] === '00000' )
	//		_m( "インデックス作成: $c" );
	//	else
	//		_m( "$did - ERROR !: " . implode( ' / ', $er ) );
	//	_m( "index $c: " . implode( ' / ', $er ) );
	}
}

//. main loop
foreach ( _joblist() as $job ) {
	$id = $job[ 'id' ];
	$db = $job[ 'db' ];
	$did = "$db-$id";

	//.. ファイル名 loadfile
//	$xmlfn = _fn( 'xml' );

	//- PDBのjsonファイルには、pubmed-IDは入れていない
	//- PDBの場合はPubmedIDを参照するので、読み込んでおく
//	if ( $db == 'pdb' )
//		$xml = simplexml_load_file( $xmlfn );

	$json = _json( $id );
//	$addfn = _fn( 'add' );
	$add = _json_load([ 'add' ]);

	$pricite = [];
	if ( $db == 'pdb' ) {
		foreach ( $json->citation as $c ) {
			if ( $c->id != 'primary' ) continue;
			$pricite = $c;
			$pmid = $c->pdbx_database_id_PubMed;
		}
	} else {
		$pmid = $add[ 'pmid' ];
		$pricite = $c->deposition->primaryReference;
	}

//		_x( $json->citation[0]->pdbx_database_id_PubMed ) );
//	$pubmedfn = "$_pubmeddir/$pmid.xml";

	//.. ファイルのタイムスタンプが変わっていないなら、やらない
//	$ft = 'x ' . _filetime( $xmlfn ) 
//		. 'j ' . _filetime( $addfn )
//		. 'p ' . _filetime( $pubmedfn )
//	;

//	if ( $filetime[ $did ] == $ft ) continue;
//	$filetime[ $did ] = $ft;

	//.. data
//	//- EMDBデータだったら、この時点でまだ読み込んでいない
//	if ( $db == 'emdb' ) 
//		$xml = simplexml_load_file( $xmlfn );

	$data = [];

	//... entry
	_data( 'db_id' 		, $did );
	_data( 'database'	, strtoupper( $db ) );
	_data( 'id' 		, $id );
	_data( 'proc_site'	, _x( $json->deposition->processingSite )
						. _x( $json->pdbx_database_status[0]->process_site )
	);

	_data( 'method' 	, $add[ 'met'    ] );
	_data( 'release'	, $add[ 'rdate'  ] );
	_data( 'submit'		, $add[ 'ddate'  ] );
	_data( 'submit_year' , substr( $add[ 'ddate' ], 0, 4 ) );
	_data( 'authors'	, $add[ 'author' ] );
	_data( 'title'		, _nn( _x( $json->deposition->title ), _x( $json->sample->name ) )
						. _x( $json->struct[0]->title ) );

	_data( 'kw' 		, $json->deposition->keywords
						. $json->struct_keywords[0]->text );

	//- update
	$u = '';
	$x = $json->database_PDB_rev;
	if ( $x != '' ) foreach ( $x as $v ) {
		$z = $v->date;
		if ( $z > $u ) 
			$u = $z;
	}
	_data( 'udate', _nn(
		$u ,
		$json->admin->lastUpdate ,
		$add[ 'rdate'  ]
	));

//	_m( "^^^^^ $id: $u ^^^^^" );

	//...  aritlce
	_data( 'pmid', $pmid );
//	$pubmed = file_exists( $pubmedfn )
//		? simplexml_load_file( $pubmedfn ) : '';
	$pubmed = _json_load2([ 'pubmed_json', $pmid ]);

	if ( $pubmed == '' ) {
		_data( 'journal', _x( $pricite->journalArticle->journal . $pricite->journal_abbrev ) );
		_data( 'country'	, $pricite->country );
		_data( 'doi'	, $pricite->pdbx_database_id_DOI . $pricite->ref_doi );

	} else {
//		$xa = $pubmed->PubmedArticle;
		_data( 'journal', $pubmed->journal );
		_data( 'doi'	, $pubmed->id->doi );
//			break;

		//- 国
		$n = preg_replace( $nation_in, $nation_out, $pubmed->affi );
		if ( $n != '' )
			_data( 'country', _nn( $nation_rep[ $n ], $n ) );
	}

	//...  sample
	_data( 'olig_state'	, $json->sample->compDegree );
	_data( 'num_comp'	, $json->sample->numComponents . $json->em_assembly[0]->num_components );
	_data( 'exp_mw'		, $json->sample->molWtExp );
	_data( 'theo_mw'	, $json->sample->molWtTheo );

	_data( 'agg_state'	,
		$json->experiment->specimenPreparation->specimenState
		. $json->em_assembly[0]->aggregation_state
	);

	//- component / species
	$compo = [];
	$spec = [];

	//- emdb
	$x = $json->sample->sampleComponent;
	if ( count( $x ) >  0 ) foreach ( $x as $c ) {
		$compo[] = $cp = $c->entry;
		$spec[]  = $c->{$cp}->sciSpeciesName;
		;
	}

	//- pdb
	$x = $json->em_entity_assembly;
	if ( count( $x ) >  0 ) foreach ( $x as $c )
		$compo[] = $c->type;

	$x = $json->entity_src_nat;
	if ( count( $x ) > 0 ) foreach ( $x as $c )
		$spec[] = $c->pdbx_organism_scientific;

	sort( $compo );
	sort( $spec );
	_data( 'compo', _imp( array_unique( $compo ) ) );
	_data( 'spec' , _imp( array_unique( $spec  ) ) );
	

	//... Experiment

	$eimg = $json->experiment->imaging;
	$evit = $json->experiment->vitrification;
	$pimg = $json->em_imaging[0];
	$pvit = $json->em_vitrification[0];

	//- 試料温度
	$tmpr = _nn( $eimg->temperature, $pimg->temperature );
	if ( $tmpr == '' and $eimg->temperatureMin != '' and $eimg->temperatureMin != '' )
		$tmpr = ( $eimg->temperatureMin + $eimg->temperatureMin ) / 2;

	$tmpr = preg_replace( '/\.0+$/', '', $tmpr );
	_data( 'spec_temp'		, $tmpr );

	//- 温度セグメント
	$seg = 2;
	$int = 2;
	if ( $tmpr  > 0 ) while ( 1 ) {
		if ( $tmpr < $int ) {
			_data( 'temp_seg', $int );
			break;
		}
		$seg *= 1.5;
		$int = round( $seg );
	}

	//- その他

	_data( 'cryogen_name'	, $evit->cryogenName	. $pvit->cryogen_name );
	_data( 'inst_vitr'		, $evit->instrument		. $pvit->instrument );
	_data( 'spec_holder'	, $eimg->specimenHolder	. $pimg->specimen_holder_type );
	_data( 'microscope'		, _nn( $pimg->microscope_model, $eimg->microscope ,
								$xml->diffrn_sourceCategory->diffrn_source->type )
	);
	_data( 'elec_source'	, $eimg->electronSource	. $pimg->electron_source );
	_data( 'acc_vol'		, $eimg->acceleratingVoltage . $pimg->accelerating_voltage );

	_data( 'detector'		, $eimg->detector . $json->em_detector[0]->type );

	//... Processing
	//- 分解能
	$reso = $add[ 'reso' ];
	_data( 'resolution', $reso );

	//- セグメント
	$seg = 2;
	$int = 2;
	if ( $reso > 0 ) while ( 1 ) {
		if ( $reso < $int ) {
			_data( 'reso_seg', $int );
			break;
		}
		$seg *= 1.5;
		$int = round( $seg );
	}

	//- その他
	$erec = $json->processing->reconstruction[0];
	$prec = $json->em_3d_reconstruction[0];

	_data( 'reso_method'	, $erec->resolutionMethod	. $prec->resolution_method );
	_data( 'ctf_corr' 		, $erec->ctfCorrection		. $prec->ctf_correction_method );
	_data( 'rec_algo'		, $erec->algorithm 			. $prec->method );
	_data( 'rec_soft'		, $erec->software			. _imp( $prec->software ) );
	
	$i = [];
	$s = [];
	if ( $db == 'emdb' ) {
		foreach ( $json->experiment->fitting as $c1 ) {
			$i[] = $c1->pdbEntryId;
			$s[] = _x( $c1->software );
		}
		foreach ( $json->deposition->fittedPDBEntryId as $i ) {
			$i[] = $i;
		}
	} else {
		$x = $json->em_3d_fitting;
		if ( count( $x ) > 0 ) foreach ( $x as $c1 ) {
			$s[] = _x( $c1->software_name );
			$i[] = $c1->pdb_entry_id;
		}
	}
	_data( 'fit_pdbid',  _uniq_implode( $i ) );
	_data( 'fit_soft' ,  _uniq_implode( $s ) );


	//... seach_words
	//- 空白で挟んで単語検索できるようにする
	$s = ' '
		. _xml2kw( $xmlfn )
		. " \n "
		. _xml2kw( $pubmedfn )
		. " \n "
		. implode( ' | ', array_values( $add ) )
		. ' '
	;
	_data( 'search_words', " $s " );
//	file_put_contents( "./test/$did.txt", $s );

	//... search_authors

	$s = '';
	if ( $pubmed != '' ) {
		$al = $pubmed->auth->MedlineCitation->Article->AuthorList;
		if ( $al != '' ) 
			foreach( $al->children() as $c1 )
				$s .= "{$c1->ForeName} {$c1->FirstName} {$c1->LastName} |";
		else
			_m(  "No Author", -1 );
			
	}
	$s = strtolower( $s . $add[ 'author' ] . '|' . $add[ 'sauthor' ] );
	$s = implode( ' | ', array_unique( explode( '|', $s ) ) );
	_data( 'search_authors', " $s " );

	//.. data load

	$cols = '';
	$vals = '';

	foreach( $data as $key => $val ){
		if ( $val == '' ) continue;
		$cols .= "\"$key\",";
		$vals .= "\"$val\",";
	}
	$cols = trim( $cols, ',' );
	$vals = trim( $vals, ',' );

	$res = $maindb->query( "REPLACE INTO main( $cols ) VALUES ( $vals )" );
	$er = $maindb->errorInfo();
	if ( $er[0] === '00000' ) {
		if ( $_redo )
			_count( 100 );
		else
			_log( "$did - ロード成功" );
	} else {
		_log( "$did - ERROR !: " . implode( ' / ', $er ) );
	}
}

//. end
//_comp_save( $filetimefn, $filetime );
_writelog();

//- バキューム
if ( ! $_redo ) {
	echo "\nDBバキューム開始 ... ";
	$bac = $maindb->exec( "VACUUM" );
	echo "完了\n";
}
_comp_save( DN_DATA . '/datacount.json', $datacount );


$maindb->commit();

$out = '';
$t = "\t";
foreach ( $datacount as $n1 => $v1 ) {
	$out .= ". $n1\n";
	ksort( $v1 );
	foreach ( $v1 as $n2 => $v2 ) {
//		$out .= "$v2\t$n2\n";
		$out .= ''
			. $v2[ 'b' ] . $t
			. $v2[ 'e' ] . $t
			. $v2[ 'p' ] . $t
			. "$n2\n"
		;
	}
}
_comp_save( "datacount.tsv", $out );

//. function

//.. data
function _data( $k, $s, $mode = 'str' ) {
	global $db, $data, $datarep, $datacount, $tabledata;
	$td = $tabledata[ $k ];
	$s = strtr( $s, [ '"' => '""' ] );

	//- 検索用カラムじゃなかったら、改行とかを消す
	if ( substr( $k, 0, 7 ) != 'search_' )
		$s = preg_replace( "/[\n\r\t ]+/m", ' ', $s );

	//- 変換
	$r = $datarep[ $k ];
	if ( is_array( $r ) ) {
		$s = preg_replace( $r[ 'in' ], $r[ 'out' ], $s );
		$s = trim( $s, " ,.-" ); //- 自動変換で残ったゴミを消す
	}

	//- 数値カラムだったら、一番右の.を消す
	if ( $td[ 'mode' ] == 'INTEGER' or $td[ 'mode' ] == 'REAL' )
		$s = preg_replace( '/\.0*$/', '', $s );

	//- 複数の値を持つカラム
	if ( $td[ 'multi' ] and $s != '' )
		$s = '|' . preg_replace( '/,+ */', '|', $s ) . '|';

	//- 登録
	$s = _x( $s, $mode );
	if ( $s != '' )
		$data[ $k ] = $s;

	//... 集計
	if ( ! $td[ 'count' ] ) return $s;

	//- 単独の値
	$ar = [ $s ];
	if ( $td[ 'multi' ] )
		$ar = explode( '|', $s );

	if ( $s == '' or $s == '||' )
		$ar = [ 'n/a' ];

	foreach ( array_unique( $ar ) as $e ) {
		$e = strtoupper( trim( $e ) );
		if ( $e == '' ) continue;
		++ $datacount[ $k ][ $e ][ 'b' ];
		if ( $db == 'emdb' )
			++ $datacount[ $k ][ $e ][ 'e' ];
		else
			++ $datacount[ $k ][ $e ][ 'p' ];
	}
	return $s;
}

//.. _json2kw
function _json2kw( $json ) {
	global $j2k;
	$j2k = [];
	_json2kw_main( $json );
	return implode( "\n", array_filter( array_unique( $j2k ) ) );
}

function _json2lw_main( $json ) {
	global $j2k;
	$igtag = [
		'pdbx_seq_one_letter_code' ,
		'pdbx_seq_one_letter_code_can' ,
		'version' ,
		'axisOrder' ,
		'dataType' ,
		'id' ,
	];

	foreach ( $json as $k => $v ) {
		if ( in_array( $k, $igtag ) ) continue;
		if ( is_array( $v ) or is_object( $v ) )
			_json2lw_main( $v );
		if ( strlen( $v ) < 3 ) continue;
		$j2k[] = trim( $v );
	}
}

//.. xml2kw
function _xml2kw( $fn ) {
	if ( ! file_exists( $fn ) ) return;
	$s = strtolower( file_get_contents( $fn ) );
	$s = preg_replace( '/[\r\n\t]+/', ' ', $s );

	while(1) { //- xmlの属性値を外に出す
		$s = preg_replace( '/<[^>=]+="(.+?)"/', '<>\1<>< ', $s, -1, $cnt );
		if ( $cnt == 0 ) break;
	}

	$in = [
		'/<pdbx_seq_one_letter_code>.+?<\/pdbx_seq_one_letter_code>/' ,
		'/<pdbx_seq_one_letter_code_can>.+?<\/pdbx_seq_one_letter_code_can>/' ,
		'/\[\[|\]\]/' ,
		'/  +/' ,
		'/<.+?>/' ,
		'/(<>){2,}/' 
	];

	$out = [
		'<>' ,
		'<>' ,
		'<>' ,
		' ' ,
		"<>" ,
		"<>"
	];

	$s = preg_replace( $in, $out, $s );
	$s = implode( "\n", array_unique( explode( "<>", $s ) ) );

	return $s;
}


//.. _uniq_implode:
//-  ユニーク、空データ削除、コンマ区切り
function _uniq_implode( $ar ) {
	return implode( ', ', array_filter( array_unique( $ar ) ) );
}

//.. 
