メインDB書き込み準備
----------
<?php

//. init
require_once( "commonlib.php" );
define( 'DN', DN_PREP. '/maindbjson' );
define( 'STRID2DBID', [
	'emdb' => _json_load( FN_DBDATA_EMDB ) ,
	'pdb'  => _json_load( FN_DBDATA_PDB ) ,
]);

if ( FLG_REDO ) {
	_del( FN_DBMAIN );
	foreach ( _idloop( 'maindb_json' ) as $pn )
		_del( $pn );
}
_mkdir( DN );

//.. tabledata.json 作成
_line( 'tabledata.json作成' );
$fn_in  = DN_EDIT. '/table_prep.tsv';
$fn_out = DN_DATA. "/emn/tabledata.json";

$out = [];
foreach ( _file( $fn_in ) as $line ) {
	$line = explode( "\t", $line );
	if ( $line[0] == '.' ) continue;
	if ( $line[0] == '' ) continue;
	$out[ $line[ 0 ] ] = [
		'ename' => $line[ 1 ] , //- 英語名
		'jname' => $line[ 2 ] , //- 日本語名
		'categ' => $line[ 3 ] , //- カテゴリ
		'mode'  => $line[ 4 ] , //- カラムのモード
		'multi' => $line[ 5 ] , //- 複数の値を持つカラム |区切り
		'page'  => $line[ 6 ] , //- 表ページで使うか
		'count' => $line[ 7 ] , //- 統計ページ用にカウントするか
	];
}
_comp_save( $fn_out, $out );
define( 'TABLE_DATA', $out );

//.. データ文字列の置き換えデータ
$datarep = [];
$a =[];
foreach ( _tsv_load2( DN_EDIT. '/data_rep.tsv', true ) as $categ => $c ) {
	foreach ( $c as $in => $out ) {
		$in = strtr( $in, [ '~' => '[ \-]?', '[]' => '[A-Z]+' ] );
		$a[ $categ ][ "/$in/i" ] = $out;
	}
}
define( 'DATA_REP', $a );
//_pause( DATA_REP );


//.. 国 country
$a = [];
foreach ( _file( DN_EDIT. '/country_names.txt' ) as $c )
	$a[ strtoupper( $c ) ] = true;
define( 'COUNTRY_NAMES', $a );
//_pause( COUNTRY_NAMES );

//. main loop

$data_count = [];
foreach ( _joblist() as $job ) {
	_count( 500 );
	extract( $job ); //- $db, $id,  $did

	//.. ファイル名 loadfile
	$json = _json_load2([ $db == 'emdb' ? 'emdb_old_json' : 'pdb_json', $id ]);
	$add  = _json_load2([ $db == 'emdb' ? 'emdb_add'  : 'pdb_add' , $id ]);
	$pmid = $add->pmid;

	//.. data
	$data = [];

	//... entry
	_data( 'db_id' 		, $did );
	_data( 'database'	, strtoupper( $db ) );
	_data( 'id' 		, $id );
	_data( 'proc_site'	, _x( $json->deposition->processingSite )
						. _x( $json->pdbx_database_status[0]->process_site ) );

	_data( 'method' 	, $add->met );
	_data( 'release'	, $add->rdate );
	_data( 'submit'		, $add->ddate );
	_data( 'submit_year' , substr( $add->ddate, 0, 4 ) );
	_data( 'authors'	, $add->author );
	_data( 'title'		, _x( $json->deposition->title ?: $json->sample->name
							?: $json->struct[0]->title ) );
	_data( 'kw' 		, $json->deposition->keywords. $json->struct_keywords[0]->text );

	//- update
	$u = '';
	$z = [ '' ];
	foreach( (array)$json->pdbx_audit_revision_history as $v ) {
		$z[] = (string)$v->revision_date;
	}
	_data( 'udate', 
		max( $z ) ?:
		$json->admin->lastUpdate ?:
		$add->rdate
	);

	//...  aritlce
	_data( 'pmid', $pmid );
	$pmjson = _json_load2([ 'pubmed_json', $pmid ]);

	$pricite = [];
	if ( $db == 'pdb' ) {
		foreach ( $json->citation as $c ) {
			if ( $c->id != 'primary' ) continue;
			$pricite = $c;
			$pmid = $c->pdbx_database_id_PubMed;
			break;
		}
	} else {
		$pricite = $json->deposition->primaryReference;
	}

	if ( $pmjson == '' ) {
		_data( 'journal', $pricite->journalArticle->journal. $pricite->journal_abbrev );
	} else {
		_data( 'journal', $pmjson->journal );

		//- 国
		_data( 'country', $pmjson->affi->auth1 );
	}

	//...  sample
	$j = $json->em_entity_assembly_molwt[0];
	$molw = ( $json->sample->molWtExp ?: $json->sample->molWtTheo )
		. ( $j->units == 'MEGADALTONS' ? $j->value : '' )
	;
	_data( 'olig_state'	, $json->sample->compDegree  );

	_data( 'molw'		, $molw );

	_data( 'agg_state'	,
		$json->experiment->specimenPreparation->specimenState
		. $json->em_experiment[0]->aggregation_state
	);
	$seg = '';
	if ( $molw > 0 ) foreach ([
		0.05, 0.1, 0.2   ,
		0.5 , 1    , 2   ,
		5   , 10   , 20  ,
		50  , 100  , 200  ,
		500 , 1000 , 2000 ,
		5000, 10000, 20000
	] as $seg ) {
		if ( $seg < $molw ) continue;
		break;
	}
	_data( 'molw_seg', $seg );

	//... component / taxo
	$compo = $spec = [];
	//- emdb
	foreach ( (array)$json->sample->sampleComponent as $key1 => $val1 ) {
		if ( ! is_object( $val1 ) ) continue;
		$compo[] = $val1->entry;
		foreach ( $val1 as $key2 => $val2 ) {
			$spec[] = $val2->natSpeciesName ?: $val2->sciSpeciesName ;
		}
	}

//	if ( $id == 22221 )	_pause( $spec );

	//- pdb
	foreach ( (array)$json->em_entity_assembly as $c )
		$compo[] = $c->type;
	foreach ( (array)$json->entity_src_nat as $c )
		$spec[] = $c->pdbx_organism_scientific;
	foreach ( (array)$json->entity_src_gen as $c )
		$spec[] = $c->pdbx_gene_src_scientific_name;
	foreach ( (array)$json->pdbx_entity_src_syn as $c )
		$spec[] = $c->organism_scientific;

	sort( $compo );
	sort( $spec );
	_data( 'compo', _uniq_implode( $compo ) );
	_data( 'spec' , _uniq_implode( $spec  ) );

//	if ( $id == 22221 )	_pause( $data[ 'spec' ] );


	//... Experiment
	$eimg = $json->experiment->imaging[0];
	$evit = $json->experiment->vitrification[0];
	$pimg = $json->em_imaging[0];
	$pvit = $json->em_vitrification[0];

	$tmpr = $eimg->temperature ?:
		$pimg->temperature ?:
		( $eimg->temperatureMin + $eimg->temperatureMin ) /2
	;
	$tmpr = preg_replace( '/\.0+$/', '', $tmpr );

	_data( 'cryogen_name'	, $evit->cryogenName	. $pvit->cryogen_name );
	_data( 'inst_vitr'		, $evit->instrument		. $pvit->instrument );
	_data( 'spec_holder'	, $eimg->specimenHolder	. $pimg->specimen_holder_type );
	_data( 'spec_temp'		, $tmpr );
	_data( 'microscope'		, $pimg->microscope_model ?: $eimg->microscope ?:
								$json->diffrn_source[0]->type );
	_data( 'elec_source'	, $eimg->electronSource	. $pimg->electron_source );
	_data( 'acc_vol'		, $eimg->acceleratingVoltage. $pimg->accelerating_voltage );

	_data( 'detector'		, $eimg->detector. $json->em_image_recording[0]->film_or_detector_model );


	//- 試料温度セグメント
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

	//... Processing
	$erec = $json->processing->reconstruction[0];
	$prec = $json->em_3d_reconstruction[0];
	$reso = $add->reso;

	_data( 'resolution'		, $add->reso );
	_data( 'reso_method'	, $erec->resolutionMethod	. $prec->resolution_method );
	_data( 'ctf_corr' 		, $erec->ctfCorrection		. $prec->ctf_correction_method );
	_data( 'rec_algo'		, $erec->algorithm 			. $prec->method );
	_data( 'rec_soft'		, $erec->software			. _imp( $prec->software ) );
	
	$i = [];
	$s = [];
	
	//- fitting
	if ( $db == 'emdb' ) {
		foreach ( (array)$json->experiment->fitting as $c1 ) {
			if ( is_array( $c1->pdbEntryId ) )
				$i = array_merge( $i, $c1->pdbEntryId );
			$s[] = _x( $c1->software );
		}
		$j = $json->deposition->fittedPDBEntryId;
		if ( $j != '' )
			$i = array_merge( $i, $j );

	} else {
		foreach ( (array)$json->em_3d_fitting as $c1 )
			$s[] = _x( $c1->software_name );
		foreach ( (array)$json->em_3d_fitting_List as $c1 )
			$i[] = $c1->pdb_entry_id;
	}
	_data( 'fit_pdbid',  _uniq_implode( $i ) );
	_data( 'fit_soft' ,  _uniq_implode( $s ) );

	//- resolution segment
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

	//... seach_words
	//- 空白で挟んで単語検索できるようにする
	$_vals = [];
	_json_vals( $json );
	_json_vals( $add  );
	_json_vals( $pmjson );
	
	//- dbidデータを追加
	$_vals = array_merge( (array)$_vals, (array)STRID2DBID[ $db ][ $id ] );

	//- metデータを追加
	foreach ( array_keys( (array)_json_load( _fn( $db. '_metjson', $id ) ) ) as $k ) {
		$_vals[] =  'm:'. $k;
	}

	_data( 'search_words', implode( ' | ' , array_unique( $_vals ) ) );

	//... search_authors

	$s = strtolower(
		( $pmjson->auth
			? implode( '|', $pmjson->auth ). '|'
			: ''
		)
		. $add->author. '|'. $add->sauthor
	);
	$s = implode( ' | ', array_unique( explode( '|', $s ) ) );
	_data( 'search_authors', $s );

	//.. save json
	_comp_save( _fn( 'maindb_json', $did ), $data, 'nomsg' );
//	_pause( "$id" );
}

//. 取り消しデータを削除
$ex = [];
foreach ( _idlist( 'emdb' ) as $i )
	$ex[ "emdb-$i" ] = true;
foreach ( _idlist( 'epdb' ) as $i )
	$ex[ "pdb-$i" ] = true;
_delobs_misc( 'maindb_json', $ex );

//. カウントデータ保存
_comp_save( DN_DATA. "/emn/datacount.json.gz", $data_count );

//- カウントtsv作成（確認用、他では使わない）
$out = [];
foreach ( $data_count as $key1 => $val1 ) {
	$out[] = ". $key1";
	ksort( $val1 );
	foreach ( $val1 as $key2 => $val2 ) {
		$out[] = implode( "\t", [
			$val2[ 'b' ] ,
			$val2[ 'e' ] ,
			$val2[ 'p' ] ,
			$key2
		]);
	}
}
_comp_save( DN_PREP. "/emn/data_count.tsv", implode( "\n", $out ). "\n" );

//. 国名じゃない
_line( '国名じゃない文字列' );
_m( implode( "\n", array_unique( $non_countries ) ) );

//. end
_end();
_php( 'both-sub4-maindbload' );
_php( 'both-sub5-latest-ent' );

//. function
//.. data
function _data( $key, $val, $type = 'str' ) {
	global $db, $data, $data_count, $non_countries;

	//- 出てくる変数: $ename, $jname, $categ, $mode, $multi, $page', $count, 
	extract( (array)TABLE_DATA[ $key ] );
	
	$val = strtr( $val, [ '"' => '""' ] );

	//- 検索用カラムじゃなかったら、改行とかを消す
	if ( substr( $key, 0, 7 ) != 'search_' )
		$val = preg_replace( "/[\n\r\t ]+/m", ' ', $val );

	//- 変換
	if ( DATA_REP[ $key ] ) {
		$val = trim(
			_reg_rep( $val, DATA_REP[ $key ] ) ,
			" ,.-" 
		); 
	}
//	_pause( "============ $key ==============" );

	//- country: 決まった文字列以外、ナシにする
	if ( $key == 'country'  ) {
		$val = trim( $val, ' ;' );
		if ( $val && ! COUNTRY_NAMES[ strtoupper( $val ) ] ) {
			$non_countries[] = $val;
			$val = '';
		}
	}

	//- 数値カラムだったら、一番右の.を消す
	if ( $mode == 'INTEGER' or $mode == 'REAL' )
		$val = preg_replace( '/\.0*$/', '', $val );

	//- 複数の値を持つカラム
	if ( $multi && $val != '' )
		$val = '|'. preg_replace( '/,+ */', '|', $val ). '|';

	//- 登録
	$val = _x( $val, $type );

	//- 検索カラムだったら空白で挟む
	if ( substr( $key, 0, 7 ) == 'search_' )
		$val = " $val ";

	if ( $val != '' )
		$data[ $key ] = $val;

	//... 統計用 カウント
	if ( ! $count ) return $val;

	//- 単独の値？
	$ar = $multi ? explode( '|', $val ) : [ $val ];
	if ( $val == '' || $val == '||' )
		$ar = [ 'n/a' ];

	foreach ( array_unique( $ar ) as $e ) {
		$e = strtoupper( trim( $e ) );
		if ( $e == '' ) continue;
		++ $data_count[ $key ][ $e ][ 'b' ];
		if ( $db == 'emdb' )
			++ $data_count[ $key ][ $e ][ 'e' ];
		else
			++ $data_count[ $key ][ $e ][ 'p' ];
	}
	return $val;
}

//.. _uniq_implode
//- ユニーク、空データ削除、コンマ区切り
function _uniq_implode( $ar ) {
	return implode( ', ', _uniqfilt( $ar ) );
}

//.. _json_vals:
function _json_vals( $j ) {
	global $_vals;
	if ( $j == '' ) return;
	foreach ( $j as $k => $v ) {
		if ( is_array( $v ) or is_object( $v ) ) {
			_json_vals( $v );
		} else {
			$v = substr( $v, 0, 5000 );
			if ( strlen( $v ) < 2 ) continue;
			if ( _instr( $v, 'na|n/a|NA|N/A' ) ) continue;
			$_vals[] = $v;
		}
	}
}

