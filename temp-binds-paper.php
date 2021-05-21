<?php
require_once( "commonlib.php" );
$sqlite = new cls_sqlite( 'main' );
$out = '';
foreach ( _file( 'temp/binds-paper-list2.tsv' ) as $line ) {
	$doi = explode( "\t", $line )[11];
	$doi = trim( strtr( $doi, [ '"' => '', 'doi:' => '' ] ) );
	if ( strlen( $doi ) < 5 ) continue;
	$did = $sqlite->qcol([
		'select' => 'db_id',
		'where' => "search_words LIKE  \"%$doi%\"" 
	])[0];
//	_m( $doi );
//	_m( $did );
//	_m( $doi );
//	$did = _ezsqlite([
//		'dbname' => 'main' ,
//		'select' => 'db_id' ,
//		'where'  => [ 'doi', $doi ] ,
//	]);
	if ( ! $did ) continue;
	_m( "$doi => $did" );
	$out .= "$doi\t$did\n";
//	if ( $doi == '10.1038/s41598-017-06698-8' ) break;
}

file_put_contents( 'temp/binds-em-papers.tsv', $out );

