<?php
require_once( "commonlib.php" );
$pmid2id = _json_load( DN_DATA . '/pubmedid2id.json' );

//. high impact journals
$impactj = [];
foreach ( _file( DN_PREP . '/impactfactor_table.tsv' ) as $line ) {
	$a = explode( "\t", $line );
	if ( count( $a ) < 3 ) continue;
	//- 0:ジャーナル名, 1:ISSN, 3:IF
//	$jnl2if[ $a[0] ] = $a[3];
//	$jnl2if[ $a[1] ] = $a[3];
	if ( $a[3] < 10 ) continue;
	$impactj[ $a[1] ] = true;
	$impactj[ $a[0] ] = true;
}
_m( 'high-impact journals: ' . _imp( array_keys( $impactj ) ) );

//$impact = _json_load( DN_PREP . '/impactj.json' ); 

//. prep DB
$dbfn  = DN_DATA  . '/empapers.sqlite';
_del( $dbfn );
$columns = implode( ',', [
	'id UNIQUE' ,
	'auth' ,
	'title' ,
	'journal COLLATE NOCASE' ,
//	'issn' ,
	'impact INTEGER' ,
	'issue' ,
	'date' ,
//	'doi' ,
	'strids COLLATE NOCASE'
]);

$pdo = new PDO( "sqlite:$dbfn", '', '' );
$pdo->beginTransaction();
$res = $pdo->query( "CREATE TABLE main( $columns )" );

//. main
foreach ( glob( DN_DATA . '/pubmed/*.json' ) as $pn ) {
//	_count( 100 );
	$j = _json_load2( $pn );
	$i = $j->id->pubmed;
	if ( count( $pmid2id[ $i ] ) == 0 ) continue;
	$vals = implode( ',', [
		$i ,
		_q( implode( '|', $j->auth ) ),
		_q( $j->title ) ,
		_q( $j->journal ) ,
//		_q( $j->id->issn ) ,
		( $impactj[ $j->id->issn ] 
		or $impactj[ strtoupper( strtr( $j->journal, [ '.' => '' ] ) ) ] )
			? 1 : 0 ,
		_q( $j->vol . _ifnn( $j->page, ':\1' ) . _ifnn( $j->year, '(\1)' ) ) ,
		_q( $j->date ) ,
//		_q( $j->id->doi ) ,
		_q( implode( '|', $pmid2id[ $i ] ) )
	]) ;
	if ( $pdo->query( "REPLACE INTO main VALUES ( $vals )" ) === false )
		_m( "$vals: 失敗", -1 );
}

function _q( $s ) {
	return '"' . strtr( $s, [ '"' => '""' ] ) . '"';
}

//. 5件だけ保存
$n = $pdo->query( "SELECT id, auth, title, journal, issue, strids FROM main where impact is 1 ORDER BY date DESC LIMIT 5" );
_sqlite_chk_mng( $pdo );
_json_save( DN_DATA . '/empapers.json', $n->fetchAll( PDO::FETCH_ASSOC ) );

print_r( $hits );


//- DB終了
$pdo->commit();
