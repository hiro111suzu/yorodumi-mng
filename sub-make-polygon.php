ポリゴンデータ作成

<?php
//. init
require_once( "commonlib.php" );

$objrep_in = [ 
	'/\bf ([0-9\.]+)\/\/[0-9\.]+ ([0-9\.]+)\/\/[0-9\.]+ ([0-9\.]+)\/\/[0-9\.]+/' ,
	'/\b(vn|g|usemtl|mtllib) .+\n/' ,
	'/^# .+\n/' ,
	'/\n# .+\n/' ,
	'/\n\n+/'
];

$objrep_out = [
	'f \1 \2 \3' ,
	'' ,
	'' ,
	"\n" ,
	"\n"
];

$jmol = DISPLAY . 'java -jar ' . DN_TOOLS . '/jmol/Jmol.jar ';

//- スクリプト
$scrfn = _tempfn( 'cmd' );
file_put_contents( $scrfn, <<< EOF
isosurface obj "o1.obj";
write isosurface "o1.jvxl"
EOF
);

//. start main loop
foreach( $emdbidlist as $id ) {
	chdir( DN_EMDB_MED . "/$id/" );
	_count( 100 );

	//- ディレクトリ作成
	if ( file_exists( '1.obj' ) and ! is_dir( 'ym' ) )
		_mkdir( 'ym' );
	if ( ! is_dir( 'ym' ) ) continue;
	chdir( 'ym' );

//.. polygon files
	foreach ( range( 1, 10 ) as $i ) { //- 1以外に無いが、今後のため
		$prefn 	= "$i.obj";
		$objfn 	= "o$i.obj";
		$pdbfn 	= "pg$i.pdb";
		$jvxlfn = "o$i.jvxl";
		$zipfn  = "o$i.zip";

		//- 表裏で表示？
		$f = "insideout$i";
		if ( $dataini[ 'pg_insideout' ][ "$id-$i" ] ) {
			if ( ! file_exists( $f ) ) {
				_m( "$id-$i: 裏表データ" );
				touch( $f );
			}
		} else {
			if ( file_exists( $f ) ) {
				_m( "$id-$i: 裏表データ取り消し" );
				unlink( $f );
			}
		}

		//- 親ディレクトリにあるファイルを移動
		if ( file_exists( "../$prefn" ) ) {
			rename( "../$prefn", $prefn );
			rename( "../$i.mtl", "$i.mtl" );
		}

		if ( ! file_exists( $prefn ) ) continue;
		if ( _newer( $zipfn, $prefn ) ) continue;

		_del( $jvxlfn, $zipfn, $outfn, $pdbfn );

		//- convert obj file
		$s = preg_replace( $objrep_in , $objrep_out ,
					file_get_contents( $prefn ) );
		file_put_contents( $objfn , $s );

		//- make dummy pdb
		preg_match_all( '/v ([0-9\.\-]+) ([0-9\.\-]+) ([0-9\.\-]+)/', $s, $ar );
		file_put_contents( $pdbfn ,
			strtr(
				"ATOM      1                   abc\n" .
				"ATOM      1                   def\n" ,
				[
					'a' => _cdnt( max( $ar[1] ) ) ,
					'b' => _cdnt( max( $ar[2] ) ) , 
					'c' => _cdnt( max( $ar[3] ) ) ,
					'd' => _cdnt( min( $ar[1] ) ) ,
					'e' => _cdnt( min( $ar[2] ) ) ,
					'f' => _cdnt( min( $ar[3] ) ) 
				]
			)
		);
		_log( "$id - made polygon data" );

		//- jvxl
		exec( "$jmol -ionx -s $scrfn" );
		_log( ( file_exists( $jvxlfn ) )
			? "$id - made JVXL data"
			: "$id - ERROR !!! - couldn't make jvxl data"
		);

		if ( file_exists( $jvxlfn ) and ( ! file_exists( $zipfn ) ) ) {
			exec( "zip o1.zip o1.jvxl" );
			_log( "$id - made zip file" );
		}
	}

} //- main loopの終わり

//. end
_del( $scrfn );

_end();

//- func
function _cdnt( $s ) {
	//- 8桁にして返す
	if ( strlen( $s ) > 8 )
		return substr( $s, 0, 8 );
	return substr( "        " . $s , -8 );
}
_php( 'sub-check-polygon' );
