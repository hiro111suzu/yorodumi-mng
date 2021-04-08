<?php
//. init
require_once( "commonlib.php" );

$datafn = DN_DATA . '/legacy/pubmedid2id.json';
$olddata = _json_load( $datafn );

//. make idlist
$newdata = array();
_line( 'IDリスト作成' );
//.. emdb
foreach( _idlist( 'emdb' ) as $id ) {
	$j = _json_load([ 'emdb_add', $id ]);

	//- PubMed ID
	if ( $j[ 'pmid' ] != '' ) {
		$newdata[ $j[ 'pmid' ] ][] = "emdb-$id";
		continue;
	}

	//- title hash
	$t = _x( _json( $id )->deposition->primaryReference->journalArticle->articleTitle );
	if ( $t != '' )
		$newdata[ '_' . md5( $t ) ][] = "emdb-$id";
}

//.. PDB emn-info
foreach ( _idlist( 'epdb' ) as $id ) {
	$j = _json_load([ 'pdb_add', $id ]);

	//- PubMed ID
	if ( $j[ 'pmid' ] != '' ) {
		$newdata[ $j[ 'pmid' ] ][] = "pdb-$id";
		continue;
	}

	//- title hash
	$t = '';
	foreach ( _json( $id )->citation as $v ) {
		if ( $v->id != 'primary' ) continue;
		$t = _x( $v->title );
		break;
	}
	if ( $t != '' )
		$newdata[ '_' . md5( $t ) ][] = "pdb-$id";
}

$idlist = array_keys( $newdata );
_m(  " Pubmed-IDの数: " . count( $idlist ) );

//. all PDB検索
_line( 'データ集計' );
$pm2pd = _json_load( DN_PREP . '/pmid2pdbid.json.gz' );
foreach ( $idlist as $pubmedid ) {
	if ( $pubmedid == '' ) continue;
	_count( 100 );
	foreach ( (array)$pm2pd[ $pubmedid ] as $i )
		$newdata[ $pubmedid ][] = "pdb-$i";

	//- Method-EMデータは既に入っているので、重複することになるので
	$newdata[ $pubmedid ] = array_unique( $newdata[ $pubmedid ] );
	sort( $newdata[ $pubmedid ] );

	//.. check
	if ( $olddata[ $pubmedid ] != $newdata[ $pubmedid ] ) {
		if ( $olddata[ $pubmedid ] == '' ) {
			_log( "$pubmedid: New => " . implode( ', ', $newdata[ $pubmedid ] ) );
		} else {
			_log( "$pubmedid: "
				. implode( ', ', $olddata[ $pubmedid ] )
				. " => "
				. implode( ', ', $newdata[ $pubmedid ] )
			);
		}
	}
}

//. fin
//_pause( $newdata[ 27043298 ] );

_comp_save( $datafn, $newdata );
_end();
