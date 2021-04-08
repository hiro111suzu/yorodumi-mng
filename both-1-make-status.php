<?php
//- statusデータを作成

//. init
require_once( "commonlib.php" );
$alldata = [];

$_filenames += [
	'matrix' => DN_EMDB_MED. '/<id>/ym/matrix.txt' ,
	'matrix_pre' => DN_EMDB_MED. '/<id>/matrix.txt' ,
];

//. EMDB data
_line( "EMDB data" );
_count();
foreach( _idlist( 'emdb' ) as $id ) {
	$did = "emdb-$id";
	_count('emdb');
	$data = [];
	$fn_map = _fn( 'mapgz', $id );

//.. movie exists?
	$data[ 'map' ]  = file_exists( $fn_map );
	$data[ 'img' ]  = '0';

	//- ムービーの存在調査
	foreach ( glob( _fn( 'emdb_mp4', $id, 's*' ) ) as $fn  ) {
		$i = strtr( basename( $fn, '.mp4' ), [ 'movies' => '' ] );
		$data[ "mov$i" ] = true;
	}

	//- 代表画像を決める
	if ( $data[ "mov2" ] ) {
		$data[ 'img' ] = '2';
	} else if ( $data[ "mov1" ] ) {
		$data[ 'img' ] = '1';
	} else {
		//- ムービーが未作成の場合は表面モデル/投影像
		$m = _fn( 'emdb_med', $id ). '/mapi/';
		if ( file_exists( $f = "$m/surf_x.jpg" ) ) {
			if ( filesize( $f ) < filesize( $f2 = "$m/surf_y.jpg" ) ) $f = $f2;
			if ( filesize( $f ) < filesize( $f2 = "$m/surf_z.jpg" ) ) $f = $f2;
			$data[ 'img' ] = basename( $f, '.jpg' );
		} else if ( file_exists( $f = "$m/proj0.jpg" ) ) {
			if ( filesize( $f ) < filesize( $f2 = "$m/proj2.jpg"  ) ) $f = $f2;
			if ( filesize( $f ) < filesize( $f2 = "$m/proj3.jpg"  ) ) $f = $f2;
			$data[ 'img' ] = basename( $f, '.jpg' );
		}
	}
	
//.. polygon exists?
	$data[ 'pg1'    ] = file_exists( _fn( 'jvxl', $id ) );
	$data[ 'matrix' ] = ( 
		file_exists( _fn( 'matrix', $id, ) ) ||
		file_exists( _fn( 'matrix_pre', $id, ) )
	);

//.. old movie
	$fn_snapimg = _fn( 'snap', $id, 'ss1' );
	if ( file_exists( $fn_map ) && file_exists( $fn_snapimg ) ) {
		if ( filemtime( $fn_map ) > filemtime( $fn_snapimg ) )
			$data[ 'oldmov' ] = 1;
	}

//.. count
	++ $alldata[ 'count-emdb' ][ 'total' ];
	++ $alldata[ 'count-emdb' ][ $data[ 'met' ] ];

	$alldata[ $did ] = array_filter( $data );
}

//. pdb data
_line( "PDB data" );
_count();
foreach( _idlist( 'epdb' ) as $id ) {
	$did = "pdb-$id";
	_count( 'epdb' );
	$data = [];

//.. movie
	$m = [];
	foreach ( glob( _fn( 'pdb_mp4', $id, 's*' ) ) as $fn ) {
		$s = strtr( basename( $fn, '.mp4' ), [ 'movies' => '' ] );
		if ( $s == 'p' or $s == 'p2' ) continue; //- snapssp snapssp2 対策
		$m[ "mov$s" ] = 1;
	}
	
	//- 代表ムービー決定
	$m[ 'snap' ] = '';
	if ( $m[ 'movdep' ] == 1 ) $m[ 'snap' ] = 'dep';
	if ( $m[ 'movsp'  ] == 1 ) $m[ 'snap' ] = 'sp';
	if ( $m[ 'mov4'   ] == 1 ) $m[ 'snap' ] = '4';
	if ( $m[ 'mov3'   ] == 1 ) $m[ 'snap' ] = '3';
	if ( $m[ 'mov2'   ] == 1 ) $m[ 'snap' ] = '2';
	if ( $m[ 'mov1'   ] == 1 ) $m[ 'snap' ] = '1';
	if ( $m[ 'movsp2' ] == 1 ) $m[ 'snap' ] = 'sp2';

	$m[ 'snap' ] = _mng_conf( 'pdb_main_mov', $id ) ?: $m[ 'snap' ];
	foreach ( $m as $k => $v )
		$data[ $k ] = $v;

//.. count
	$alldata[ 'count-pdb' ][ 'total' ] += 1;
	$alldata[ 'count-pdb' ][ $data[ 'met' ] ] += 1;
	$alldata[ $did ] = array_filter( $data );
}

$alldata[ 'rep-id' ] = _json_load( DN_PREP. '/replacedid.json' );

//- db-small保存
_comp_save( FN_DBSTATUS, $alldata );
_end();
