addjson を作成
<?php
//. init
require_once( "commonlib.php" );

//- pubmed-ID
$pubmedid_tsv = new cls_pubmedid_tsv(
	'emdb' ,
	[ 1920, 1921, 20618 ] //- whitelist: Pubmedid tsvのほうが正解のID
);

//.. author rep
//define( 'AUTH_REP', [
//	'/\r|\n|\t/'	=> ' ',
//	'/\./'			=> '. ',
//	'/[, ]+and +/'	=> ',',
//	'/,([^a-z]+,)/'	=> '\1',
//	'/,([^a-z]+$)/'	=> '\1',
//	'/[, ]+$/'		=> '',
//	'/^ +/'			=> '',
//	'/  +/'			=> ' ',
//	'/ *, */'		=> '|'
//]);

//. main loop
foreach ( _idloop( 'emdb_new_json' ) as $fn ) {
	_count( 'emdb' );
	$id = _fn2id( $fn );
	$json = _emdb_( _json_load2( $fn );

//.. output
	$auth_ref = (string)$json->crossreferences->primary_citation->journal_citation->author;

	$clev_src = $json->map->contourLevel_source ;

	_comp_save( _fn( 'emdb_add', $id ), array_filter([
		'rdate'		=> $json->admin->current_status->code == 'REL'
			? $json->admin->key_dates->map_release
			: ''
		,
		'ddate'		=> $json->admin->key_dates->deposition ,
		'reso'		=> $json->processing->reconstruction[0]->resolutionByAuthor, 'num' ),
		'sauthor'	=> $auth ,
		'author'	=> _reg_rep( (string)$json->deposition->authors, AUTH_REP ) ?: $auth ,
		'met'		=> $json->processing->singleParticle->appliedSymmetry == 'I'
			? 'i'
			: _metcode( $json->processing->method )
		,
		'pmid'		=> $pubmedid_tsv->get(
			$id,
			_x( $json->deposition->primaryReference->journalArticle->ref_pubmed )
		) ,
		'non_auth_clev' => $clev_src && $clev_src != 'author' ,

//		'ref_doi'	=> $doi[ $id ] , 
	]), 'nomsg' );
}


//. end
_delobs_emdb( 'emdb_add' );
$pubmedid_tsv->save();

_end();

