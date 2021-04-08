メインDB書き込み
----------
<?php
//. misc. init
require_once( "commonlib.php" );
define( 'NAME_SARS_COV_2', 'Severe acute respiratory syndrome coronavirus 2' );
define( 'CATEG_KEYS', _tsv_load3( '../emnavi/subdata.tsv' )['categ']['keys'] );

//.. 日付データ
$youbi = date( 'N' );
//- 月: 1, 水: 3, 金:  5, 土:  6, 日:  7
//- 月: 2, 水: 0, 金: -2, 土: +4, 日: +3
//- 土日なら10-youbi
$dif = ( $youbi < 6 ? 3 : 10 ) - $youbi;
//$rel_date = date( 'Y-m-d', time() + $dif * 3600 * 24 );
define( 'REL_DATE', time() + $dif * 3600 * 24 );

//.. DB準備
$sqlite = new cls_sqlw([
	'fn'		=> 'newent' ,
	'cols'		=> array_merge(
		[ 'date UNIQUE', 'num', 'Allent', 'Covid19' ],
		array_keys( CATEG_KEYS )
	),
	'indexcols' => [ 'date' ] ,
	'new'		=> true
]);

$o_main = new cls_sqlite( 'main' );


//. main loop
foreach ( range( 0, 10 ) as $i => $week ) {
	$date = _rel_date( $week );
	$sql_date = _sql_eq( 'release', $date );
	_m( "#$i: $date" );

	//- where
	$categ_where = [
		'All'		=> '' ,
		'Covid-19'	=> _sql_like( 'spec', NAME_SARS_COV_2, '|' )
	];
	foreach ( CATEG_KEYS as $name => $keys )
		$categ_where[ $name ] = _sql_eq( 'categ', explode( ',', $keys ) );

	//- count
	$num = [];
	foreach ( array_merge( $categ_where, [
		'EMDB'		=> _sql_eq( 'database', 'EMDB' ) ,
		'PDB'		=> _sql_eq( 'database', 'PDB' ) ,
	]) as $key => $where )
		$num[ $key ] = $o_main->where([ $sql_date, $where])->cnt();
	_m( 'EMDB: '. $num['EMDB']. ', PDB: '. $num['PDB'] );

	$data = [ $date, json_encode( array_filter( $num ), JSON_NUMERIC_CHECK ) ];

	//- data
	foreach ( $categ_where as $key => $where ) {
		$data[] = implode( ',', $o_main->qcol([
			'select'	=> [ 'db_id' ] ,
			'where'		=> [ $sql_date, $where ] ,
			'order by'	=> 'release DESC, sort_sub DESC, db_id' ,
//			'order by'	=> 'release DESC, CASE WHEN pmid IS NULL THEN "000" || authors ELSE pmid END DESC, db_id' ,

		]));
	}
	$sqlite->set( $data );
}


//. end
$sqlite->end();
