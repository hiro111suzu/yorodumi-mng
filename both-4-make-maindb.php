メインDB書き込み準備
----------
<?php

//. init
require_once( "commonlib.php" );
//define( 'MODE_DB', 'MODE' ); //- 片方しかやらない場合、emdbとかにする
define( 'MODE_DB', null );
define( 'DO_MAKE_DB', true ); //- テストのときは falseにする
define( 'DN', DN_PREP. '/maindbjson' );

/*
define( 'STRID2DBID', [
	'emdb' => _json_load( FN_DBDATA_EMDB ) ,
	'pdb'  => _json_load( FN_DBDATA_PDB ) ,
]);
*/
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
$o_empty = new stdClass;
$data_count = [];
foreach ( _joblist( MODE_DB ) as $job ) {
	_count( 500 );
	extract( $job ); //- $db, $id,  $did
	if ( $id == '0144' ) continue;

	//.. ファイル名 loadfile
	if ( $db == 'emdb' ) {
		$emdb_old_json  = _json_load2([ 'emdb_old_json', $id ]);
		$emdb_json3 = _emdb_json3_rep( _json_load2([ 'emdb_json3', $id ]) );
		$pdb_json   = $o_empty;
	} else {
		$emdb_old_json  = $o_empty;
		$emdb_json3 = $o_empty;
		$pdb_json   = _json_load2([ 'epdb_json', $id ]);
	}
	$add  = _json_load2([ $db. '_add', $id ]);
	$pubmed_id = $add->pmid;

	//.. data
	$data = [];

	//.. data - entry
	_data( 'db_id' 		, $did );
	_data( 'database'	, strtoupper( $db ) );
	_data( 'id' 		, $id );
	_data( 'empiar'		, implode( '|', (array)json_decode( _ezsqlite([
		'dbname' => 'empiar' ,
		'select' => 'data' ,
		'where'  => [ 'id', $did ] ,
	]), true ) ) );

	_data( 'method' 	, $add->met );
	_data( 'release'	, $add->rdate );
	_data( 'submit'		, $add->ddate );
	_data( 'submit_year' , substr( $add->ddate, 0, 4 ) );
	_data( 'authors'	, implode( '|', $add->author ) );

	if ( $db == 'emdb') {
		_data( 'proc_site'	, $emdb_json3->admin->current_status->processing_site );
		_data( 'title'		, $emdb_json3->admin->title );
		$udate = $emdb_json3->admin->current_status->date;
	} else {
		_data( 'proc_site'	, $pdb_json->pdbx_database_status[0]->process_site  );
		_data( 'title'		, $pdb_json->struct[0]->title );
		$d = [];
		foreach( (array)$pdb_json->pdbx_audit_revision_history as $v )
			$d[] = $v->revision_date;
		$udate = max( $d );
	}
	_data( 'udate', $udate ?: $add->rdate ); //- update 最新の日付

	//.. data - aritlce
	_data( 'pmid', $pubmed_id );
	$pubmed_json = _json_load2([ 'pubmed_json', $pubmed_id ]);
	if ( $pubmed_json ) {
		_data( 'journal', $pubmed_json->journal );
		_data( 'country', $pubmed_json->affi->auth1 );
	} else if ( $db == 'pdb' ) {
		//- PDB
		foreach ( (array)$pdb_json->citation as $c ) {
			if ( $c->id != 'primary' ) continue;
			_data( 'journal', $c->journal_abbrev );
			break;
		}
	} else {
		//- EMDB
		$j = $emdb_json3->crossreferences->primary_citation->journal_citation;
		_data( 'journal', $j->journal ?: $j->journal_abbreviation );
	}

	//..  data - sample
	$j = $pdb_json->em_entity_assembly_molwt[0];
	$molw = ( $emdb_old_json->sample->molWtExp ?: $emdb_old_json->sample->molWtTheo )
		. ( $j->units == 'MEGADALTONS' ? $j->value : '' )
	;
	_data( 'olig_state'	, $emdb_old_json->sample->compDegree  );

	_data( 'molw'		, $molw );

	_data( 'agg_state'	,
		$emdb_old_json->experiment->specimenPreparation->specimenState
		?:
		$pdbj_json->em_experiment[0]->aggregation_state
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

	//.. data - component / taxo
	$compo = $spec = [];
	//... emdb
	foreach ( (array)$emdb_old_json->sample->sampleComponent as $key1 => $val1 ) {
		if ( ! is_object( $val1 ) ) continue;
		$compo[] = $val1->entry;
		foreach ( $val1 as $key2 => $val2 ) {
			$spec[] = $val2->natSpeciesName ?: $val2->sciSpeciesName ;
		}
	}

	//... pdb
	foreach ( (array)$pdb_json->em_entity_assembly as $c )
		$compo[] = $c->type;
	foreach ( (array)$pdb_json->entity_src_nat as $c )
		$spec[] = $c->pdbx_organism_scientific;
	foreach ( (array)$pdb_json->entity_src_gen as $c )
		$spec[] = $c->pdbx_gene_src_scientific_name;
	foreach ( (array)$pdb_json->pdbx_entity_src_syn as $c )
		$spec[] = $c->organism_scientific;

	//... まとめ
	sort( $compo );
	sort( $spec );
	_data( 'compo', _uniq_implode( $compo ) );
	_data( 'spec' , _uniq_implode( $spec  ) );

	//.. data - Experiment
	$temprt = 0;
	if ( $db == 'emdb' ) {
		//... emdb
		$e_em = $emdb_json3->structure_determination[0]->microscopy[0];
		$e_vit = $emdb_json3->structure_determination[0]->preparation[0]->vitrification;
		$temprt = $e_em->temperature->temperature_average
			?: ( $e_em->temperature_min + $e_em->temperature_max ) /2
		;
		_data( 'cryogen_name'	, $e_vit->cryogen_name );
		_data( 'inst_vitr'		, $e_vit->instrument );
		_data( 'spec_holder'	, $e_em->specimen_holder_model );
		_data( 'spec_temp'		, $temprt ? (float)$temprt : null );
		_data( 'microscope'		, $e_em->microscope );
		_data( 'elec_source'	, $e_em->electron_source );
		_data( 'acc_vol'		, $e_em->acceleration_voltage );
		_data( 'detector'		, $e_em->image_recording[0]->film_or_detector_model );
	} else {
		//... PDB
		$p_img = $pdb_json->em_imaging[0];
		$p_vit = $pdb_json->em_vitrification[0];
		$temprt = $p_img->temperature;
		_data( 'cryogen_name'	, $p_vit->cryogen_name );
		_data( 'inst_vitr'		, $p_vit->instrument );
		_data( 'spec_holder'	, $p_img->specimen_holder_type );
		_data( 'spec_temp'		, $temprt ? (float)$temprt : null );
		_data( 'microscope'		, $p_img->microscope_model ?: $pdb_json->diffrn_source[0]->type );
		_data( 'elec_source'	, $p_img->electron_source );
		_data( 'acc_vol'		, $p_img->accelerating_voltage );
		_data( 'detector'		, $pdb_json->em_image_recording[0]->film_or_detector_model );
	}

	//... 試料温度セグメント
	$seg = $int = 2;
	if ( 0 < $temprt ) while ( 1 ) {
		if ( $temprt < $int ) {
			_data( 'temp_seg', $int );
			break;
		}
		$seg *= 1.5;
		$int = round( $seg );
	}

	//.. data Processing
	_data( 'resolution'		, $add->reso );
	if ( $db == 'emdb' ) {
		$e_rec = $emdb_old_json->processing->reconstruction[0];
		_data( 'reso_method', $e_rec->resolutionMethod );
		_data( 'ctf_corr' 	, $e_rec->ctfCorrection );
		_data( 'rec_algo'	, $e_rec->algorithm );
		_data( 'rec_soft'	, $e_rec->software );
	} else {
		$p_rec = $pdb_json->em_3d_reconstruction[0];
		_data( 'reso_method', $p_rec->resolution_method );
		_data( 'ctf_corr' 	, $p_rec->ctf_correction_method );
		_data( 'rec_algo'	, $p_rec->method );
		_data( 'rec_soft'	, _imp( $p_rec->software ) );
	}
	
	//... fitting
	$i = $s = [];
	if ( $db == 'emdb' ) {
		foreach ( (array)$emdb_old_json->experiment->fitting as $c1 ) {
			if ( is_array( $c1->pdbEntryId ) )
				$i = array_merge( $i, $c1->pdbEntryId );
			$s[] = _x( $c1->software );
		}
		$j = $emdb_old_json->deposition->fittedPDBEntryId;
		if ( $j != '' )
			$i = array_merge( $i, $j );

	} else {
		foreach ( (array)$pdb_json->em_3d_fitting as $c1 )
			$s[] = _x( $c1->software_name );
		foreach ( (array)$pdb_json->em_3d_fitting_List as $c1 )
			$i[] = $c1->pdb_entry_id;
	}
	_data( 'fit_pdbid',  _uniq_implode( $i ) );
	_data( 'fit_soft' ,  _uniq_implode( $s ) );

	//... resolution segment
	$seg = $int = 2;
	if ( 0 < $add->reso ) while ( 1 ) {
		if ( $add->reso < $int ) {
			_data( 'reso_seg', $int );
			break;
		}
		$seg *= 1.5;
		$int = round( $seg );
	}

	//.. data - seach_words
	//- 空白で挟んで単語検索できるようにする
	$_vals = [];
	_json_vals( $emdb_old_json );
	_json_vals( $pdb_json );
	_json_vals( $add  );
	_json_vals( $pubmed_json );
	
	//- dbidデータを追加
	$_vals = array_merge(
		$_vals,
		explode( '|', _ezsqlite([
			'dbname'	=> 'strid2dbids' ,
			'where'		=> [ 'strid', $db == 'emdb' ? "e$id" : $id ] ,
			'select'	=> 'dbids'
		]))
	);

	//- metデータを追加
	foreach ( array_keys( (array)_json_load( _fn( $db. '_metjson', $id ) ) ) as $k ) {
		$_vals[] =  'm:'. $k;
	}

	_data( 'search_words', implode( ' | ' , array_unique( $_vals ) ) );

	//.. data - search_authors
	_data( 'search_authors', implode( ' | ', _uniqfilt( array_map( 'strtolower',
		array_merge(
			(array)$pubmed_json->auth ,
			(array)$add->author , 
			(array)$add->sauthor
		)
	))));

	//.. save json
	_comp_save( _fn( 'maindb_json', $did ), $data, 'nomsg' );
//	_pause( "$id" );
}
_cnt2();

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
if ( DO_MAKE_DB ) {
	_php( 'both-sub4-maindbload' );
	_php( 'both-sub5-latest-ent' );
}

//. function
//.. data
function _data( $key, $val, $type = 'str' ) {
	global $db, $data, $data_count, $non_countries;

	//- 出てくる変数: $ename, $jname, $categ, $mode, $multi, $page', $count, 
	extract( (array)TABLE_DATA[ $key ] );

	if ( is_array( $val ) )
//		_pause( "aray: $key". _imp( $val ) );
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
	if ( ! $j ) return;
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
