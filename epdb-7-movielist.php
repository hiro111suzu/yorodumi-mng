-----
pdbmovinfo.json作成
入力：
	- EMDB movieinfo
	- assembly.json
-----
<?php
//. misc init
require_once( "commonlib.php" );
$data = [];

//. EMDB movinfo
$fitmov = [];
foreach ( _idloop( 'movinfo', 'EMDB movinfo 取得' ) as $fn ) {
	_count( 'emdb' );
	$id = _fn2id( $fn );
	foreach ( (array)_json_load( $fn ) as $num => $v ) {
		if ( ! is_array( $v ) ) continue;
		if ( $v[ 'mode' ] != 'pdb' ) continue;
		if ( count( $v[ 'fittedpdb' ] ) == 0 ) {
			_problem( "emdb-$id: movie-#$num PDB-IDが不明" );
			continue;
		}
		$f = $v[ 'fittedpdb' ];
		foreach ( $f as $i ) {
			if ( count( $f ) > 1 ) {
				//- 複数: 一緒にフィットしたPDB-IDを記録
				$a = [];
				foreach ( $f as $i2 ) {
					if ( $i != $i2 )
						$a[] = $i2;
				}
				$fitmov[ $i ][] = [
					'id'	=> $id,
					'num'	=> $num,
					'cofit'	=> implode( ',', $a ) 
				];
			} else {
				//- 単独
				$fitmov[ $i ][] = [
					'id'	=> $id,
					'num'	=> $num 
				];
			}
		}
	}
}
_m( '完了' );

//. main loop
_line( 'メイン' );

$j = _json_load( FN_ASB_JSON );
if ( $j == '' )
	die( 'assembly.jsonがない' );
_count();
foreach ( _idlist( 'epdb' ) as $id ) {
	_count( 'epdb' );
	$cnt = 1;
	$ar = [];

	//- PDBムービー
	foreach ( glob( _fn( 'pdb_mp4', $id, 's*' ) ) as $fn ) {
		$s = strtr( basename( $fn, '.mp4' ), [ 'movies' => '' ] );
		if ( $s == 'p' or $s == 'p2' ) continue; //- snapssp snapssp2 対策

		$ar[ $cnt ][ 'name' ] = $s;
		if ( $s == 'sp' ) {
			//- split
			$ar[ $cnt ][ 'ids' ] = $j[ $id ][ 'sp' ];
		} else if ( $s == 'sp2' ) {
			//- split
			$ar[ $cnt ][ 'ids' ] = $j[ $id ][ 'sp2' ];
		} else if ( substr( $s, 0, 2 ) == 'jm' ) {
			//- jmol fit
			$ar[ $cnt ][ 'id' ] = _numonly( $s );
		} else if ( $s != 'dep' ) {
			//- depsited以外
			$ar[ $cnt ][ 'type' ] = $j[ $id ][ $s ][ 'type' ];
		}
		++ $cnt;
	}

	//- フィットムービー
	foreach ( (array)$fitmov[ $id ] as $a ) {
		$ar[ $cnt ][ 'name'  ] = "emdb";
		$ar[ $cnt ][ 'id'    ] = $a[ 'id' ];
		$ar[ $cnt ][ 'num'   ] = $a[ 'num' ];
		$ar[ $cnt ][ 'cofit' ] = $a[ 'cofit' ];
		++ $cnt;
	}
	$data[ $id ] = $ar;
}


//. end

_comp_save( FN_PDB_MOVINFO, $data );
_end();

