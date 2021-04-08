
smalldbを作成
ムービーリスト作成（スロット用）
movdate作成（ムービーヒストリ）

<?php
//- samlldbを作成

//. init
require_once( "commonlib.php" );
$alldata = [];

//. EMDB data
_line( "EMDB data" );
_count();
foreach( _idlist( 'emdb' ) as $id ) {
	$did = "emdb-$id";
	_count('emdb');
	$data = [];

	$json = _json_load2( _fn( 'emdb_json', $id ) );
	$j = _json_load( _fn( 'add', $id ) );

	$data[ 'title' ] = _x( $json->deposition->title ) ?: _x( $json->sample->name );
	$data[ 'met'   ] = $j[ 'met' ];
	if ( $j[ 'reso' ] != '' )
		$data[ 'reso' ] = $j[ 'reso' ];
	$data[ 'img' ] = '0';

	$fn_map = _fn( 'mapgz', $id );

//.. movie exists?
	$data[ 'mov0' ] = file_exists( $fn_map );
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
		$m = _fn( 'emdb_med' ) . '/mapi/';
		if ( file_exists( $f = "$m/surf_x.jpg" ) ) {
			if ( filesize( $f ) < filesize( $f2 = "$m/surf_y.jpg" ) ) $f = $f2;
			if ( filesize( $f ) < filesize( $f2 = "$m/surf_z.jpg" ) ) $f = $f2;
			$data[ 'img' ] = basename( $f, '.jpg' );
		} else if ( file_exists( $f = "$m/proj0.jpg" ) ) {
			if ( filesize( $f ) < filesize( $f2 = "$m/proj2.jpg"  ) ) $f = $f2;
			if ( filesize( $f ) < filesize( $f2 = "$m/proj3.jpg"  ) ) $f = $f2;
			$data[ 'img' ] = basename( $f, '.jpg' );
		}
		if ( $json->map->contourLevel_source != 'author' )
			$data['clev'] = 1;
	}
	
//.. polygon exists?
	$data[ 'pg1' ] = file_exists( _fn( 'jvxl' ) );

//.. old movie
	$fn_snapimg = _fn( 'snap', $id, 'ss1' );
	if ( file_exists( $fn_map ) && file_exists( $fn_snapimg ) ) {
		if ( filemtime( $fn_map ) > filemtime( $fn_snapimg ) )
			$data[ 'oldmov' ] = 1;
	}

//.. count
	++ $alldata[ 'count-emdb' ][ 'total' ];
	++ $alldata[ 'count-emdb' ][ $data[ 'met' ] ];

	$alldata[ $did ] = $data;
}

//. pdb data
_line( "PDB data" );
_count();
foreach( _idlist( 'epdb' ) as $id ) {
	$did = "pdb-$id";
	_count( 'epdb' );

	$data = [];

	$json = _json_load2( _fn( 'epdb_json', $id ) );
	$j = _json_load( _fn( 'add' ) );

	$data[ 'title' ] = _x( $json->struct[0]->title );
	$data[ 'met' ] = $j[ 'met' ];
	if ( $j[ 'reso' ] != '' )
		$data[ 'reso' ] = $j[ 'reso' ];

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

	$m[ 'snap' ] = MNG_CONF[ 'pdbmainmov' ][ $id ] ?: $m[ 'snap' ];
	foreach ( $m as $k => $v )
		$data[ $k ] = $v;

//.. count
	$alldata[ 'count-pdb' ][ 'total' ] += 1;
	$alldata[ 'count-pdb' ][ $data[ 'met' ] ] += 1;
	$alldata[ $did ] = $data;
}

$alldata[ 'rep-id' ] = _json_load( DN_PREP. '/replacedid.json' );

//- db-small保存
_comp_save( FN_DBSMALL, $alldata );

_end();
