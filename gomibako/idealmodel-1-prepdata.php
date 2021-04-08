ideal-model
モデルでテスト

<?php

//. init
require_once( "commonlib.php" );
require_once( "lib-omo.php" );

define( 'FLTWD', 0.3 ); //- ウインドウの割合


//- 作業ディレクトリ
define( 'DN', realpath( DN_SCRIPT . '/../idealmodel' )  );
_mkdir( $outdn = DN . '/out' );

//- gnuplot
//define( 'PLOTTMP', "/tmp/emn/plot.png" );
define( 'PLOTCMD', <<<EOF
set term png
set output "<outfn>"
set yrange [ 0: ]
plot "<infn>" with lines lw 4
EOF
);


//. main
foreach ( glob( DN . '/*.spi' ) as $pn ) {
	$bn = basename( $pn, '.spi' );
	if ( _instr( 'test', $pn ) ) continue;
	if ( _instr( 'LOG', $pn ) ) continue;

	$mrcfn		= "$outdn/$bn.mrc";
	$vqprefn	= "$outdn/$bn-pre.pdb";
	$vqfn		= "$outdn/$bn.pdb";
	$proffn		= "$outdn/$bn.txt";
	$plotfn		= "$outdn/$bn.png";
	
	_line( "$bn: 開始" );
	//.. mrc作成
	if ( _newer( $pn, $mrcfn ) ) {
		_del( $mrcfn );
		_exec( "proc3d $pn $mrcfn apix=1 lp=20" );
	}

	//.. vq作成
	if ( _newer( $mrcfn, $vqfn ) ) {
		_del( $vqprefn, $vpfn );
		_m( "$bn: vq作成" );
		_m( "vqpre作成: " . _checkvqfile( $vqprefn ) );
		_qvol([ 'mapfn' => $mrcfn, 'vqfn' => $vqprefn ]);

		if ( $args[ 'refine' ] )
			_qvol([ 'mapfn' => $mrcfn, 'vqprefn' => $vqprefn , 'vqfn' => $vqfn ]);
		else
			copy( $vqprefn, $vqfn );
		_m( "vq作成: " . _checkvqfile( $vqfn ) );
	}
	if ( ! file_exists( $vqfn ) ) continue;

	//.. profile / plot
	if ( _newer( $vqfn, $plotfn ) ) {
		_del( $proffn, $plotfn );
		_m( "$bn: profile作成" );
		file_put_contents( $proffn, _getprof( _getcrd( $vqfn ) ) . "\n" );
		_gnuplot( strtr( PLOTCMD, [ '<infn>' => $proffn, '<outfn>' => $plotfn ] ) );
	}

	_line( "$bn: 完了" );

}
