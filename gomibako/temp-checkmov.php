<?php
//. init
require_once( "commonlib.php" );

$mode_ar = [
	"'representation': 'solid'"	=> 'solid' ,
	"'Volume_Color_State'"		=> 'vol' ,
	"'Cylinder_Color_State'"	=> 'cyl' ,
	"'Height_Color_State'"		=> 'hei' ,
	"'Radial_Color_State'"		=> 'rad' ,
	"'pdbHeaders': [{"			=> 'pdb'
];

$smalldb = _json_load( DN_DATA . '/db-small.json.gz' );

//. old session
/*
_line( 'old session' );
foreach( $emdbidlist as $id ) {
	if ( ! file_exists( _fn( 'session-old', $id, 1 ) ) ) continue;
	_problem( "$id: 古いセッションファイル" );
}
*/
//. new session

_line( 'new session' );
foreach( $emdbidlist as $id ) {
	$data = $smalldb[ "emdb-$id" ];
	if ( ! $data[ 'map' ] ) continue;
	for ( $i = 1; $i <= 20; $i++ ) {
		$pyfn = _fn( 'session', $id, $i );

		//- 無い？
		if ( ! $data[ "mov$i" ] ) {
			if ( 2 < $i ) continue;
			if ( $data[ "met" ] == 't' ) continue;
			_problem( "$id-$i: ムービーがない" );
			continue;
		}
		if ( ! file_exists( $fn ) ) continue; //- 古いセッション

		$py = file_get_contents( $pyfn );
		//- solid?
		if ( _instr( "'representation': 'solid'", $py ) ) continue;

		//- 断面のモーションの無いムービーチェック
		if ( 
			( ! _instr( "'pos1'", $py ) ) or 
			( ! _instr( "'pos2'", $py ) )
		) {
//			_m( "movie-$id: 断面モーションがない" );
			_problem( "$id-$i: 断面モーションがない" );
		}
	}
}

_problem();
