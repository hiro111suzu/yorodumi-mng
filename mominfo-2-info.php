<?php
//. init
require_once( "commonlib.php" );
_mkdir( DN_PREP. '/mom' );
_mkdir( DN_PREP. '/mom/html' );
_mkdir( DN_PREP. '/mom/info' );
define( 'TSV_INFO', _tsv_load2( DN_EDIT. '/mom_info.tsv' ) );
define( 'SCORE_PDB', TSV_INFO['all']['score_pdb'] );
define( 'SCORE_UNP', TSV_INFO['all']['score_unp'] );
define( 'SCORE_KW' , TSV_INFO['all']['score_kw'] );



$_filenames += [
	'mom_html' => DN_PREP. '/mom/html/<id>.html' ,
//	'mom_info' => DN_PREP. '/mom/info/<id>.json' ,
];
/*
"ct:2.40.155.10" white list

*/
$o_info = new cls_sqlw([
	'fn'		=> 'mominfo' ,
	'cols' => [
		'id INTEGER UNIQUE' ,
		'en COLLATE NOCASE' ,
		'ja' ,
		'month' ,
		'pdb' ,
		'fh COLLATE NOCASE' ,
	] ,
	'indexcols' => [ 'id', 'en' ],
]);

$o_id2mom = new cls_sqlw([
	'fn'		=> 'id2mom' ,
	'cols' => [
		'dbid UNIQUE' ,
		'mom' ,
	] ,
	'indexcols' => [ 'dbid' ],
]);

//. main

//.. mom data
$data = $id2mom = $no_pdbid = [];
foreach ( _idloop( 'mom_html' ) as $fn ) {
	$mom_id = _fn2id( $fn );
	$html = file_get_contents( $fn );

	//... PDB-ID
	$pdb_ids = _tsv_info( $mom_id, 'add_pdb' );
	foreach ( _match( '/pdbj\.org\/pdb\/([0-9a-z]+)/', $html ) as $m ) {
		$pdb_ids[] = _rep_pdbid( $m[1] );
	}
	$pdb_ids = _uniqfilt( $pdb_ids );

	//... fh item
	$fh_items = [];
	foreach ( $pdb_ids as $pdb_id ) {
		if ( in_array( $pdb_id, _tsv_info( $mom_id, 'ng_pdb' ) ) ) continue;
		if (  ! _inlist( $pdb_id, 'pdb' ) ) {
			$no_pdbid[ $mom_id ][] = $pdb_id;
			continue;
		}
		
		foreach ( array_merge(
			_tsv_info( $mom_id, 'add_item' ) ,
			explode( '|', _ezsqlite([
				'dbname' => 'strid2dbids',
				'select' => 'dbids',
				'where'  => [ 'strid', $pdb_id ] 
			]))
		) as $fh_id ) {
			if ( in_array( $fh_id, _tsv_info( $mom_id, 'ng_item' ) ) ) continue;
			if ( in_array( $fh_id, _tsv_info( 'all', 'ng_item' ) ) ) continue;
			list( $type, $id ) = explode( ':', $fh_id, 2 );
			
			//- tsv登録は無条件で追加
			if ( ! in_array( $fh_id, _tsv_info( $mom_id, 'add_item' ) ) ) {
				if ( $type == 'go' || $type == 'ct' ) continue; //- GO/ CATHは無し
				if ( $type == 'ec' && substr( $id, -1 ) == '-' ) continue; //- ecグループ無し
				if ( $type == 'polysac' ) continue;
			}

			//- 構成要素ヒット
			if ( $type == 'un' || $type == 'gb' || $type == 'nor' ) {
				$ret[] = $fh_id;
				$fh_items[ $fh_id  ] += SCORE_UNP;
				continue;
			}

			//- その他は数に応じて
			$num = _ezsqlite([
				'dbname' => 'dbid' ,
				'select' => 'num' ,
				'where'  => [ 'db_id', $fh_id ] ,
			]);
			if ( $num == 0 ) {
				_m( "$fh_id: num = 0", -1 );
				continue;
			}
			$fh_items[ $fh_id  ] += floor( 1000 / $num )/ 10;
		}
	}

	//... DB書き込み
	$d = _match( '/>(20[0-9][0-9])年([0-9]{1,2})月の記事</', $html )[0];
	$date = $d[1]. '-'. substr( '0'. $d[2], -2 );

	_m( "#$mom_id / $date :\t". count( $fh_items ). ' items' );
	$title = _match( '/<title>(.+?)<\/title>/', $html )[0][1];

	list( $dummy, $title_j, $title_e ) = _match( '/[0-9]+: (.+?)（(.+?)）/', $title )[0];
	$o_info->set([
		$mom_id , 
		$title_e ,
		$title_j ,
		$date ,
		implode( '|', $pdb_ids ) ,
		implode( '|', array_filter( array_keys( $fh_items )  ) ) ,
	]);

	//... score
	$ent_score = TSV_INFO[ $mom_id ][ 'score' ] ?: 1;
	//- PDB
	foreach ( $pdb_ids as $pdb_id )
		$id2mom[ "pdb:$pdb_id" ][ $mom_id ] += SCORE_PDB * $ent_score ;

	//- fh info
	foreach ( $fh_items as $fh_id => $score )
		$id2mom[ $fh_id  ][ $mom_id ] += $score * $ent_score;

	//... keyword
	$kw = array_merge(
		[ $title_e ] ,
		(array)explode( ' and ', $title_e )
	);
	//- 複数形対応
	foreach ( $kw as $k ) {
		$kw[] = _reg_rep( $k, [ '/s$/'  => '' ] );
		$kw[] = _reg_rep( $k, [ '/es$/' => '' ] );
		$kw[] = _reg_rep( $k, [ '/ies$/' => 'y' ] );
	}
	$s = $ent_score * 10;
	foreach ( _uniqfilt( array_merge(
		$kw ,
		_tsv_info( $mom_id, 'add_kw' )
	)) as $k ) {
		$k = trim( strtolower( $k ) );
		if ( in_array( $k, _tsv_info( 'all', 'ng_kw' ) ) ) continue;
		$id2mom[ "kw:$k" ][ $mom_id ] += $s;
	}
}
$o_info->end();

//.. fh_id -> mom_id
foreach ( $id2mom as $id => $mom ) {
	$o_id2mom->set([
		$id,
		json_encode( $mom, JSON_NUMERIC_CHECK )
	]);
}
$o_id2mom->end();

//.. no PDB-ID
if ( $no_pdbid ) {
	_line( 'なくなったPDB-ID' );
	foreach ( $no_pdbid as $mom_id => $pdb_ids ) {
		_m( "$mom_id:\t". _imp( $pdb_ids ) );
	}
}

//. func
//.. _match
function _match( $ptn, $subj ){
	preg_match_all( $ptn, $subj, $match, PREG_SET_ORDER );
	return $match ?: [];
}

//.. _tsv_info
function _tsv_info( $id, $key ) {
	return explode( '|', TSV_INFO[ $id ][ $key ] ) ?: [];
}

