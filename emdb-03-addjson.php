addjson を作成
<?php
//. init
require_once( "commonlib.php" );
//- pubmed-ID
$o_pubmedid_tsv = new cls_pubmedid_tsv( 'emdb' );

$dif_count = [];
$sym_only_v1 = [];

//. main loop
foreach ( _idloop( 'emdb_new_json' ) as $fn ) {
	_count( 'emdb' );
	$id = _fn2id( $fn );
	$json = _emdb_json3_rep( _json_load2( $fn ) );

//.. 準備
	//- auth_ref
	$auth_ref = array_map( 'trim' ,
		$json->crossreferences->primary_citation->journal_citation->author
	);

	//- reso
	$reso = $sym = [];
	foreach ( (array)$json->structure_determination as $c ) {
		foreach( $c->processing as $c2 ) {
			$reso[] = (float)$c2->final_reconstruction->resolution;
			$sym[] = $c2->final_reconstruction->applied_symmetry->point_group;
		}
	}

	//- sym
	$sym = _imp( _uniqfilt( $sym ) );
	if ( $sym == '' ) {
		$j = _json_load2([ 'emdb_old_json', $id ])->processing;
		$sym = $j->singleParticle->appliedSymmetry ?:
			$j->subtomogramAveraging->appliedSymmetry
		;
		if ( $sym ) {
			$sym_only_v1[ $id ] = $sym;
			_cnt2( 'only v1', 'sym' );
		} else {
			_cnt2( 'no sym', 'sym' );
		}
	} else {
		_cnt2( 'in v3', 'sym' );
	}

	//- $clev_src
	$clev_src = $json->map->contour[0]->level != ''
		? $json->map->contour[0]->source
		: ''
	;

	//- stain / cryo
	$conf_cryo    = _mng_conf( 'sample_cryo', $id );
	$conf_stained = _mng_conf( 'sample_stained', $id );

	$xml_stained = $xml_cryo = '';
	foreach ( $json->structure_determination as $c ) {
		foreach ( $c->preparation as $c2 ) {
			$n = $c2->vitrification->cryogen_name;
			if ( $n != '' && $n != 'NONE' )
				$xml_cryo = 1;
			if ( $c2->staining->type )
				$xml_stained = 1;
		}
	}

	$cryo = $conf_cryo
		? ( $conf_cryo == 'yes' ? 1 : '' )
		: $xml_cryo
	;
	$stained = $conf_stained 
		? ( $conf_stained == 'yes' ? 1 : '' )
		: $xml_stained
	;

	if ( $cryo && $stained )
		_cnt2( 'cryo+stained', 'stain/cryo' );
	else if ( $cryo )
		_cnt2( 'cryo', 'stain/cryo' );
	else if ( $stained )
		_cnt2( 'stained', 'stain/cryo' );
	else
		_cnt2( 'no cryo + no stain', 'stain/cryo' );

/*
	foreach ( $json->structure_determination->preparation as $c ) {
		if ( $c->vitrification->cryogen_name
	}
	温度がある
		200ケルビン以下だったらクライオ
	温度がない
		cryogen_nameが、ヌルでもNONEでもなかったら、クライオ
*/

//.. output
	_comp_save( _fn( 'emdb_add', $id ), array_filter([
//	_comp_save_test( _fn( 'emdb_add', $id ), [
		'rdate'		=> $json->admin->current_status->code == 'REL'
			? $json->admin->key_dates->map_release
			: ''
		,
		'ddate'		=> $json->admin->key_dates->deposition,
		'reso'		=> min( $reso ) ,
		'sauthor'	=> $auth_ref ,
		'author'	=> array_map( 'trim', $json->admin->author ) ?: $auth_ref ,
		'met'		=> _met_code( $json->structure_determination[0]->method )
		,
		'pmid'		=> $o_pubmedid_tsv->get(
			$id,
			$json->crossreferences->primary_citation->journal_citation->ref_PUBMED
		) ,
		'non_auth_clev' => $clev_src && $clev_src != 'AUTHOR' ,
		'sym'		=> $sym ,
		'stained'	=> $stained ,
		'cryo'		=> $cryo ,
	]), 'nomsg' );
}


//. end
_delobs_emdb( 'emdb_add' );
$o_pubmedid_tsv->save();
_json_save( DN_PREP. '/sym_only_v1.json', $sym_only_v1 );
_cnt2();
_end();

//. function

//.. _comp_save_test テスト用
function _comp_save_test( $fn, $new, $dummy = '' ) {
	global $dif_count;
	return;
	$prev = _json_load( $fn );
	$new['sauthor'] = implode( '|', $new['sauthor'] );
	$new['author'] = implode( '|', $new['author'] );
	
	if ( $new == $prev ) return;

	$diff = [];
	foreach ( $new as $k => $v ) {
		if ( $prev[ $k ] == $v ) continue;
		if ( $k == 'met' && $prev[ $k ] == 'i' )
			continue;
		
		$diff[ $k ] = $prev[ $k ]. '->'. $v; 
		++ $dif_count[ "$k:". $prev[ $k ]. '->'. $v ];
		
	}
	if ( $diff ) {
		_m( basename( $fn ), 1 );
		print_r( $diff );
	}
//	_pause([
//		'prev' => $prev ,
//		'new' => $new ,
//	]);
}

//ksort( $dif_count );
//_kvtable( $dif_count );

 