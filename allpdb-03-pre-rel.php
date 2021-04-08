未公開データ準備

<?php
require_once( "commonlib.php" );

$m2num = [
	'jan' => '01',
	'feb' => '02',
	'mar' => '03',
	'apr' => '04',
	'may' => '05',
	'jun' => '06',
	'jul' => '07',
	'aug' => '08',
	'sep' => '09',
	'oct' => '10',
	'nov' => '11',
	'dec' => '12'
];

$info_rel = [
	'title'	=> 'title' ,
	'stat'	=> 'status_code',
	'ars'	=> 'author_release_sequence' ,
	'auth'	=> 'author_list',
	'ddep'	=> 'initial_deposition_date' ,
	'dhold'	=> 'date_hold_coordinates'
];

$ids = [];
$data = [];
$index = [];
$stat_c = [];
$ars_c = [];
$idlist = ''; //- チェック用

//. sequence
$id = 'dummy';
$edit = 'dummy';
$seq = [];
foreach ( _file( DN_FDATA . '/status_query.seq' ) as $line ) {
	if ( substr( $line, 0, 1 ) == '>' ) {
		$id = strtolower( substr( $line, 1, 4 ) );
		$eid = preg_replace( '/.+ /', '', $line );
	} else {
		$seq[ $id ][ $eid ] .= $line;
	}
}

//. status_query
foreach ( _file( DN_FDATA . '/status_query.csv' ) as $line ) {
	_count( 1000 );
	$ar = explode( "','", $line );

	//- 最初の行
	if ( count( $index ) == 0 ) {
		foreach ( $ar as $num => $name )
			$index[ _trim( $name ) ] = $num;
		continue;
	}

	//- 本体
	$id = strtolower( $ar[ $index[ 'pdb_id' ] ] );
	
	$ids[ $id ] = 1;
	$idlist .= "$id\n";
	foreach ( $info_rel as $key => $headerkey ) {
		$val = _trim( $ar[ $index[ $headerkey ] ] );
		if ( $val == 'n/a' or $val == '' ) continue;
		
		//- 日付修正
		if ( _instr( 'date', $headerkey ) )
			$val = _datestr( $val );

		//- オーサーリスト分割
		if ( $key == 'auth' ) {
			//- コンマがデリミタのみか、苗字と名前の分離にも使われているか
			$test = explode( ',', $val );
			$f = false;
			foreach ( $test as $s ) { 
				//- 小文字が含まれないか、1文字だけの語がある？
				if ( preg_match( '/[a-z]/', $s ) === 0 
					|| strlen( strtr( $s, [ ' ' => '', '.' => '' ] ) ) < 2
				) {
					$f = true;
//					_pause( "$s: 小文字なし" );
					break;
				} else {
//					_pause( "$s: 小文字もあるよ" . preg_match( '/[a-z]/', $s ) );
				}
			}
			
			$val = $f
				? explode( '|', strtr( $val, [ '.,' => '.|', ',' => '' ] ) )
				: $test
			;
			foreach ( $val as $n => $s )
				$val[ $n ] = trim( $s );
			
//			if ( ! $f && count( $val ) > 1 )
//				_pause( implode( '/', $val ) . ': コンマだけで区切る' );
		}

		$data[ $id ][ $key ] = $val;
	}
	if ( $seq[ $id ] != [] )
		$data[ $id ][ 'seq' ] = $seq[ $id ];

	$stat_c[ _trim( $ar[ $index[ 'status_code' ] ] ) ] = true;
	$ars_c[ _trim( $ar[ $index[ 'author_release_sequence' ] ] ) ] = true;

//	_m( $id, 1 );
//	print_r( $data[ $id ] );
//	if ( $id == '4gdq' ) break;
}
_kvtable([
	'status_code' => array_keys( $stat_c ) ,
	'author_release_sequence' => array_keys( $ars_c )
]);

//. obsolete
foreach ( _file( DN_FDATA . '/obsolete.dat' ) as $line ) {
	_count( 1000 );
	if ( substr( $line, 0, 1 ) == ' ' ) continue; //- ヘッダ行
	list( $status, $date, $id, $suc ) = preg_split( '/ +/', $line );
	$id = strtolower( $id );
	if ( $data[ $id ] ) continue;
	list( $m, $d, $y ) = explode( '-', $date );
	$date = ( $y < 70 ? 2000 : 1900 ) + $y .'-'. $m2num[ strtolower( $m ) ] .'-'. $d;
	$data[ $id ] = [
		'stat' => $status ,
		'date' => $date ,
	];
	$idlist .= "$id\n";
//	_m( "$id, $status, $date" );

}

//. end
_comp_save( DN_DATA . '/ids/prerel.txt', $idlist );
_comp_save( DN_DATA . '/pdb/prerel.json.gz', $data );
_end();

//. func
function _datestr( $s ) {
	global $m2num;
	list( $month, $day, $year ) = explode( ' ', strtr( $s, [ '  ' => ' 0' ] ) ) ;
	return $year .'-'. $m2num[ strtolower( $month ) ] .'-'. $day;
}

function _trim( $s ) {
	return trim( $s, " '" );
}
