<?php
require_once( "met-common.php" );
_comp_save( DN_DATA. '/met_syn.json.gz', MET_SYN );

$sqlite = new cls_sqlw([
	'fn' => 'met' , 
	'cols' => [
		'key UNIQUE COLLATE NOCASE' ,
		'name COLLATE NOCASE' ,
		'for COLLATE NOCASE' ,
		'num integer' ,
		'yearly' ,
		'data' ,
	],
	'new' => true ,
	'indexcols' => [ 'key', 'num' ] ,
]);

//. count
define( 'MAIN_MET', [
	'X-ray diffraction' ,
	'neutron diffraction' ,
	'fiber diffraction' ,
	'electron crystallography' ,
	'electron microscopy' ,
	'solution NMR' ,
	'solid-state NMR' ,
	'solution scattering' ,
	'powder diffraction' ,
	'infrared spectroscopy' ,
	'EPR' ,
	'fluorescence transfer' ,
	'theoretical model' ,
]);  

//.. PDB
_line( 'PDB data' );
$data = [];
$met2ids = [];
$flg_changed = false;
$stat = []; //- 種類を数える
foreach ( array_merge(
	_idloop( 'pdb_metjson' ) ,
	_idloop( 'emdb_metjson' ) ,
	_idloop( 'sas_metjson' ) 
) as $fn ) {
	if ( _count( 'pdb', 0 ) ) break;
	$json = _json_load2( $fn );
	//- パス名からDB-ID文字列作成
	$id = strtr( $fn, [ DN_PREP. '/met/' => '', '.json' => '', '/' => '-' ] );
	foreach ( $json as $met_key => $c ) {
		//- 多数順でならべるためにカウント
		foreach ( $c->name as $n ) {
			++ $data[ $met_key ][ 'name' ][ $n ];
		}
		foreach ( $c->for as $n ) {
			++ $data[ $met_key ][ 'for' ][ $n ];
			$stat[ $n ][ $met_key ] = 1;
		}
		//- メイン手法は後回し
		foreach ( $c->met as $n ) {
			$data[ $met_key ][ 'for' ][ $n ] += 0.000001;
		}
		++ $data[ $met_key ][ 'num' ];
		++ $data[ $met_key ][ 'year' ][ $c->year ];
		$met2ids[ $met_key ][] = $id;
	}
}
ksort( $data );
//_json_save( DN_MET. '/met2ids.json.gz', $met2ids );

//. stat 
//- 種類多すぎカテゴリ対策

foreach ( $stat as $k => $v ) {
	$stat[ $k ] = count( $v );
}
arsort( $stat );
$out = '';
foreach ( $stat as $key => $num ) {
	$out .= "$key\t$num\n";
}
file_put_contents( DN_PREP. '/met/stat.tsv', $out );

$mcount = [];
foreach ( $data as $c ){
//	_m( array_keys( $c['for'] ) );
//	_pause();
	if ( ! in_array( 'experiment type', array_keys( $c['for'] ) ) ) continue;
	arsort( $c['name'] );
	$name = array_keys( $c['name'] )[0]; 
//	print_r( $name );
//	_m( gettype( $name ) );
//	_pause(  );
	foreach ( explode( ' ', strtr( $name, [ '-' => ' ', '_' => ' ' ] ) ) as $word ) {
		$word = trim( $word, ";, " );
		if ( strlen( $word ) < 4 ) continue;
		if ( in_array( $word, [ 'AND', 'FOR', 'filtered', 'edited' ] ) ) continue;
		if ( preg_match( '/[0-9]/', $word ) ) continue;
		++ $mcount[ $word ];
	}
}
arsort( $mcount );
$out = '';
foreach ( $mcount as $key => $num ) {
	if ( $num < 5 ) continue;
	$out .= "$key\t$num\n";
}

file_put_contents( DN_PREP. '/met/stat_exp_type.tsv', $out );


//. data
$this_year = date('Y');
$tsv = _tsv_load2( DN_EDIT. '/met_annot.tsv' );

foreach ( $data as $met_key => $c ) {
	$load = [];
	$num = 0;
	foreach ( $c as $k => $c2 ) { //- $k: 'name', 'for', 'num', 'year'
		if ( $k == 'num' ) {
			$num = $c2;
			continue; 
		}
		if ( $k == $year ) continue;

		//- レアなfor nameは入れない
		$out = [];
		$max = max( $c2 );
		arsort( $c2 );
		foreach ( $c2 as $k2 => $n ) {
			if ( in_array( $k2, MAIN_MET ) || $max < $n * 50 ) 
				$out[] = $k2;
		}
		$load[ $k ] = implode( '|', $out );
	}
	$yearly = array_fill( 0, 30, 0 );
	foreach ( $c['year'] as $year => $n ) {
		if ( is_numeric( $year ) )
			$y = $this_year - $year;
		else
			_m( "non numeric: $met_key -> $year" );
//		_pause( "$this_year, $year, $y" );
		if ( 29 < $y ) continue;
		$yearly[ $y ] = $n;
	}

	foreach ( ['url', 'wikipe', 'comment'] as $t ) {
		if ( $tsv[ $t ][ $met_key ] ) {
			$tsv[ $met_key ][ $t ] = $tsv[ $t ][ $met_key ];
		}
	}

//	_pause( "$met_key: " . implode( '|', array_reverse( $yearly ) ) );
	$sqlite->set([
		$met_key,
		$load['name'],
		$load['for'],
		$num ,
		implode( '|', array_reverse( $yearly ) ) ,
		$tsv[ $met_key ] ? json_encode( $tsv[ $met_key ] ) : '' //- アノテーション
	]);
}
$sqlite->end();

