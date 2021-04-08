omo-pdb-2: vq作成

<?php

//. init
require_once( "commonlib.php" );

$depdn = '/novdisk2/db/omopdb/dep/';
$asbdn = '/novdisk2/db/omopdb/asb/';
$vqdn  = '/novdisk2/db/omopdb/vq';
$errdn = '/novdisk2/db/omopdb/error';

//_initlog( "pdb-9: make vq pdb" );
//define( BLIST, 'blacklist_make_pdb_vq' );

//. コマンドファイル
$cmdfn[ 30 ] = 'template/make30vq.txt';
$cmdfn[ 50 ] = 'template/make50vq.txt';
_prepfile( $cmdfn[ 30 ], ''
	. "2\n"		//- 水を除外? -> yes
	. "1\n"		//- B-factor threshold? No
	. "30\n"	//- cvの数
	. "1\n"		//- コネクティビティ計算するか？
	. "1\n"		//- No
);
_prepfile( $cmdfn[ 50 ], ''
	. "2\n"		//- 水を除外? -> yes
	. "1\n"		//- B-factor threshold? No
	. "50\n"	//- cvの数
	. "1\n"		//- コネクティビティ計算するか？
	. "1\n"		//- No
);

//. job抽出
$jobs = array();

foreach ( scandir( $depdn ) as $fn ) {
	if ( is_dir( $fp = "$depdn$fn" ) ) continue;
	$jobs[] = array(
		'name'	=> substr( $fn, 3, 4 ) . '-d' ,
		'pdbgz'	=> $fp
	);
}

foreach ( scandir( $asbdn ) as $fn ) {
	if ( is_dir( $fp = "$asbdn$fn" ) ) continue;
	$num = preg_replace( '/.+pdb([0-9]+).+/', '$1', $fn );
	$jobs[] = array(
		'name'	=> substr( $fn, 0, 4 ) . "$id-$num" ,
		'pdbgz'	=> $fp ,
	);
}
shuffle( $jobs );
//die( count( $jobs ) . ' hoge' );

//. main
foreach ( $jobs as $job ) {
	extract( $job ); //- $pdbgz, $name

	$vq[30] = "$vqdn/$name-30.pdb";
	$vq[50] = "$vqdn/$name-50.pdb";
	if ( _newer( $vq[30], $pdbgz ) and _newer( $vq[50], $pdbgz ) ) continue;

	//- エラー？
	$errfn 		= "$errdn/$name.txt";
	if ( _errcnt( $errfn ) > 10 ) continue; //- 10回以上失敗したデータはやらない

	if ( _proc( "omopdb-vq-$name" ) ) continue;

	//- unzip
	$pdbfn = _tempfn( 'pdb' );
	copy( $pdbgz, "$pdbfn.gz" );
	exec( "gunzip -f $pdbfn.gz" );
//	die( file_get_contents( $pdbfn ) );

	foreach ( array( 30, 50 ) as $vqnum ) {
		_m( "vq作成: $name - $vqnum" );
		$vqfn = $vq[ $vqnum ];
		exec( "qpdb $pdbfn $vqfn <" . $cmdfn[ $vqnum ] );

		//- エラーチェック
		$err = 1;
		if ( file_exists( $vqfn ) )
			if ( filesize( $vqfn ) > 100 )
				$err = 0;

		if ( $err ) {
			_m( "失敗", -1 );
			_del( $vqfn );
			file_put_contents( $errfn, _errcnt( $errfn ) + 1 );
		} else {
			_m( "成功" );
			_del( $errfn );
		}
	}
	_del( $pdbfn ); //- tempfile削除
	_proc();
}


//. func
function _errcnt( $fn ) {
	if ( file_exists( $fn ) )
		return file_get_contents( $fn );
}
