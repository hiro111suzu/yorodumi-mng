代理構造作成（複数モデル用、巨大構造用)

<?php

//. init
require_once( "commonlib.php" );
require_once( "omo-common.php" );

ini_set( "memory_limit", "8192M" );

define( 'LIM', 100000 ); //- 10万行以下にする

$blist = new cls_blist( 'omopdb_altmodel' );

//. joblist 作成
$req = [];
$jobs = [];

//.. dep
foreach ( _idloop( 'pdb_pdb', 'ジョブリスト作成' ) as $fp ) {
	$name = _fn2id( $fp ) . '-d';
	if ( $blist->inc( $name ) ) continue;	
	$jobs[] = [
		'dep'	=> true ,
		'name'	=> $name ,
		'fn_coord'	=> $fp
	];
	$req[ $name ] = true;
	_cnt( 'total', 'dep' );
}


//.. アッセンブリデータのみ
if ( $argv[1] == 'asb' )
	$jobs =[];

foreach ( glob( DN_FDATA . '/pdb/asb/*.pdb*.gz' ) as $fp ) {
	//- ファイル名は"100d.pdb1.gz"
	$name = strtr( basename( $fp, '.gz' ), [ '.pdb' => '-' ] );
	if ( _instr( '--', $name ) ) continue; //- "6eu1.pdb.pdb.gz"のようなファイルがある
	if ( _inlist( $name, 'identasb' ) ) continue;
	if ( $blist->inc( $name ) ) continue;

	$jobs[] = [
		'dep'	=> false ,
		'name'	=> $name ,
		'fn_coord'	=> $fp ,
	];
	$req[ $name ] = true;
	_cnt( 'total', 'asb' );
}

//.. large dep
foreach ( _idlist( 'large' ) as $id ) {
	$name = "$id-d";
	$jobs[] = [
		'dep'	=> true ,
		'name'	=> $name ,
		'fn_coord'	=> _fn( 'pdb_mmcif', $id ) ,
		'cif'	=> true
	];
	$req[ $name ] = true;
	_cnt( 'total', 'large' );
}

//.. large asb
$cnt = 0;
foreach ( glob( DN_FDATA . '/large_structures_asb/*.cif.gz' ) as $fp ) {
	$name = strtr( basename( $fp, '.cif.gz' ), [ 'assembly' => '' ] );
	if ( _inlist( $name, 'identasb' ) ) continue;
	if ( $blist->inc( $name ) ) continue;

	$jobs[] = [
		'dep'	=> false ,
		'name'	=> $name ,
		'fn_coord'	=> $fp ,
		'cif'	=> true
	];
//	_del( _fn( 'altmodel', $name ) );
//	_m( $name );
	$req[ $name ] = true;
	_cnt( 'total', 'large-asb' );

}
_cnt();

//. onlyモード?
$only = [];
if ( $argar[ 'only' ] != '' ) {
	_line( 'only for' . $argar[ 'only' ]  );
	foreach ( explode( ',', $argar[ 'only' ] ) as $id ) {
		$id = trim( $id );
		$only[] = $id;
		if ( ! _instr( '-', $id ) ) {
			$only[] = "$id-d";
			foreach ( range( 1, 20 ) as $i )
				$only[] = "$id-$i";
		}
	}
//	_m( "only: " . _imp( $only ) );
	$j = [];
	foreach ( $jobs as $job ) {
		if ( ! in_array( $job[ 'name' ], $only ) ) continue;
		_m( "Do only for " . $job[ 'name' ] );
		$j[] = $job;
	}
	if ( count( $j ) == 0 )
		die( "no job for " . $argar[ 'only' ] );
	else
		$jobs = $j;
}

//. main
_line( 'main' );

foreach ( $jobs as $job ) {
	if ( _count( 5000, 0 ) ) break;

	$fn_coord = $name = $dep = $cif = '';
	extract( $job );
	$fn_out = _fn( 'altmodel', $name );

	if ( FLG_REDO )
		_del( $fn_out );
	if ( _newer( $fn_out, $fn_coord ) ) continue; 
	_log( "$name: 代理構造を作成" );

	$data = gzfile( $fn_coord );
	if ( $cif ) {
		$data = _cif2pdb( $data, [ 'allmodel' => !$dep ] );
		_m( "cif -> pdb :" . count( $data ) . ' atoms' );
	}

	//.. モデル数
	if ( $dep and ( ! $cif ) ) {
		$a = preg_split( "/[\n\r]MODEL +[0-9]+/", implode( '', $data ), 3 );
		if ( count( $a ) > 2 ) {
			_gzsave( $fn_out, $a[1] );
			_log( "$name: 複数モデル、データ保存" );
			continue;
		}
	}

	//.. 大きい構造じゃない
	//- CIFはヘッダがつかないので小さめにする
	if ( count( $data ) < ( $cif ? LIM - 5000 : LIM ) ) {
		if ( $cif )
			_gzsave( $fn_out, implode( '', $data ) );
		else
			touch( $fn_out );
		continue;
	}

	//.. 大きい構造
	$out = [];
	foreach ( $data as $line ) {
		//- ペプチドはCA、RNA,DNAは 3個
		$a = preg_split( '/ +/', $line, 5 );

		//- 0:ATOM 2:CA 3:ALA
		if ( $a[0] != 'ATOM' ) continue;
		if ( in_array( $a[3], [ 'A', 'T', 'G', 'C', 'DA', 'DT', 'DG', 'DC' ] ) ) {
			//- DNA/RNA
			if ( ! in_array( $a[2], [ 'P', 'N1', "C1'" ] ) ) continue;
		} else {
			if ( $a[2] != 'CA' ) continue;
		}
		$out[] = $line;
	}
	
	//- 充分に小さくなるまで間引く
	$cnt0 = count( $out );
	while ( count( $out ) > LIM ) {
		$flg = false;
		$out2 = [];
		foreach ( $out as $l ) {
			if ( $flg )
				$out2[] = $l;
			$flg = ! $flg;
		}
		$out = $out2;
	}

	_gzsave( $fn_out, implode( '', $out ) ); //- $lineは末尾に改行文字が付いている
	_log( "$name: 単純化モデル、データ保存" );
	
	$cnt1 = count( $out );
	if ( $cnt0 != $cnt1 )
		_m( "さらに縮小 $cnt0 => $cnt1" );
	
//	break;
}

//. 掃除
_delobs_misc( 'altmodel', $req );
_end();
