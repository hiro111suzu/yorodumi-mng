<?php
//. init
/*
スライスファイル作成

*/

require_once( "commonlib.php" );
$ini = parse_ini_file( "data.ini", true );
$spider = "/novdisk2/softwares-64/spider15.10/bin/spider_linux_mpfftw_opt64";


//. start main loop
foreach( $emdbidlist as $id ) {
	$did = "emdb-$id";

	$dn = "$_ddn/jdata/$id";
	$mapfn 		= "$dn/emd_$id.map";
	$mapinfofn	= "$dn/mapinfo.json";

	//.. projections


	if ( file_exists( $dest ) and ! file_exists( "$destdn/proj3.jpg" ) ) {
		$thr = shell_exec( "iminfo $dest | grep [0-9]*x[0-9]*x[0-9]*" );
		preg_match( '/([0-9]+)x([0-9]+)x([0-9]+)/', $thr, $d );

		$dx = $d[1];
		if ( $dx < $d[2] ) $dx = $d[2];
		if ( $dx < $d[3] ) $dx = $d[3];
		print "=== {$dx[1]} - {$dx[2]} - {$dx[3]} ===\n";
		$opt = @$ini[ 'proj_option' ][ $id ];
		$r = shell_exec( "proc3d $dest map.spi norm spidersingle $opt" );
		print "=== $r ===\n";
		file_put_contents( 'proj.spi', ''
			. " pj 3 \n map \n $dx,$dx \n pj0 \n 0,0,0 \n"
			. " pj 3 \n map \n $dx,$dx \n pj1 \n 270,45,90 \n"
			. " pj 3 \n map \n $dx,$dx \n pj2 \n 270,90,90 \n"
			. " pj 3 \n map \n $dx,$dx \n pj3 \n 0,90,90 \n en \n"
		);
		exec( "$spider spi @proj", $res );
		for( $i = 0; $i < 4; $i ++ ) {
			exec( "proc2d pj$i.spi $destdn/proj$i.png png" );
			_imgres( "$destdn/proj$i.png",  "$destdn/proj$i.jpg" );
		}
		exec( 'rm *.spi *.spi.*' );
		_print( "$id: made projections" );
	}

	} // =====
	//.. make session file 
	$pyfn = "$destdn/start.py";
	if ( file_exists( $dest ) and 
		! file_exists( $pyfn ) and
		! file_exists( "$destdn/sessin1.py" ) 
	) {
		//- xmlからデータ読み込み
		$f = "$srcdn/header/emd-{$id}.xml";
		if ( ! file_exists( $f ) )
			$f = "$srcdn/structures/EMD-$id/header/emd-{$id}.xml";
		$xml = simplexml_load_file( $f );
		$agg_state = (string)$xml->sample->aggregationState;
		echo "\nagg-state: $agg_state\n";

		//- threshold
		$thr = (string)$xml->map->contourLevel;
		if ( ( $thr == '' ) and
			( $agg_state == 'singleParticle' or $agg_state == 'icosahedral' ) ) {
			$molwt = trim( @$xml->sample->molWtTheo );
			if ( $molwt == '' );
				$molwt = trim( @$xml->sample->molWExp );
			$molwt = $molwt * 1000;
			$pix = trim( @$xml->map->pixelSpacing->pixelX );
			if ( $molwt != 0 and $pix != '' ) {
				$thr = shell_exec( "volume $dest $pix calc=$molwt | tail -n 1" );
				$thr = preg_replace( '/^([0-9\.]*).*$/', '\1', $thr );
				print "surface level: $thr\n";
			}
		}
		if ( $thr == '' or $thr <= 0) $thr = 1;

		//- 書き込み
		file_put_contents( $pyfn, 
			strtr( file_get_contents( 'template/start_template.py' ) ,
				array(
					'%id%' => $id ,
					'%surf_color%' => $agcolor[ $agg_state ] ,
					'%thr%' => $thr 
				)
			)
		);
		_print( "$id: made session file" );
//		copy( 'window.cmd', "$destdn/window.cmd" );
	}

	//.. copy image files
	//- 画像のコピー
	$idn = "$srcdn/images";
	if ( is_dir( $idn ) ) foreach ( scandir( $idn ) as $fn ) {
		if ( substr( $fn, 0, 3 ) == '80_' ) continue;
		if ( $fn == 'emd_1004.tif' ) continue; //- mpeg ムービーファイル
		$srcfn = "$idn/$fn";
		if ( is_dir( $srcfn ) ) continue;

		$ext = strtolower( substr( $fn, -3 ) );
		$copy = ( $ext == 'jpg' or $ext == 'gif' );
		$destfn = "$destdn/" . ( $copy ? "fig_$fn" : "fig_$fn.jpg" );
		$thfn = "$destdn/thumb_$fn.jpg";

		//- check time stamp
		$srctime = filemtime( $srcfn );
		$flag = 0;
		if ( ! file_exists( $thfn ) ) {
			$flag = 1;
			$msg = "$id: new figure - $fn";
		} else if ( $srctime != filemtime( $thfn ) ) {
			$flag = 1;
			$msg = "$id: changed figure - $fn";
		}
		if ( ! $flag ) continue;

		//- copy
		if ( $copy )
			copy( $srcfn, $destfn );
		else
			_imgconv( $srcfn, $destfn );
		touch( $destfn, $srctime );
		_imgres( $srcfn, $thfn ); //- thumb
		touch( $thfn, $srctime );
		_print( $msg );

	}

	//- 元が無くなったファイルがないかチェック
	foreach ( scandir( $destdn ) as $fn ) {
		if ( substr( $fn, 0, 6 ) != 'thumb_' ) continue;
		$ofn = preg_replace( '/^thumb_(.+?)\.jpg$/', '\1', $fn );
		if ( ! file_exists( "$idn/$ofn" ) ) {
			rename( "$destdn/$fn", "$destdn/del_$ofn.jpg" );
			_print( "$id: figure removed - $ofn" );
		}
	}

	//.. copy slices
	$dir = "$srcdn/slices";
	if ( is_dir( $dir ) ) foreach( scandir( $dir ) as $fn ) {
		$in   = "$dir/$fn";
		if ( is_dir( $in ) ) continue;
		$srctime = _filetime( $in );

		//- check file time
		$outl = "$destdn/slc_{$fn}_l.png";
		$outs = "$destdn/slc_{$fn}_s.jpg";
		$flag = 0;
		if ( ! file_exists( $outs ) ) {
			$flag = 1;
			$msg = "$id: new slice - $fn";
		} else if ( $srctime != filemtime( $outs ) ) {
			$flag = 1;
			$msg = "$id: changed slice - $fn";
		}

		//- convet image
		if ( $flag ) {
			exec( "proc2d $in $outl png" );
			_imgres( $outl, $outs );
			touch( $outl, $srctime );
			touch( $outs, $srctime );
			_print( $msg );
			_del( "$srcdn/slices/.emanlog" );
		}
	}

	//.. fsc
	$dir = "$srcdn/fsc";
	if ( is_dir( $dir ) ) foreach( scandir( $dir ) as $fn ) {
		$in   = "$dir/$fn";
		if ( is_dir( $in ) ) continue;
		$srctime = _filetime( $in );

		//- check file time
		$outl = "$destdn/fscl.gif";
		$outs = "$destdn/fscs.gif";
		$flag = 0;
		if ( ! file_exists( $outs ) ) {
			$flag = 1;
			$msg = "$id: new fsc - $fn";
		} else if ( $srctime != filemtime( $outs ) ) {
			$flag = 1;
			$msg = "$id: changed fsc - $fn";
		}
		//- convert
		if ( $flag ) {
			if ( stristr( $in, '.xml' ) ) {
				echo "\n$id: $in\n";
				$in = _plotfsc( $in );
			}
			_imgconv( $in, $outl );
			_imgres( $in, $outs );
			touch( $outl, $srctime );
			touch( $outs, $srctime );
			_del( 'plot.png' );
			_print( "$id: copied fsc data - $fn" );
		}
	}

	//.. end of main loop
//	if ( $id == "1015" ) break;

} // end of main loop (foreach)

//_savexml( $datefn, $datexml );
_writelog();
return;


//. func plot fsc
function _plotfsc( $fn ) {
	$fscxml = simplexml_load_file( $fn );
	$t = trim( $fscxml[ 'title' ] );
	$x = trim( str_replace( '(A-1)', '(A)', $fscxml[ 'xaxis' ] ) );
	$y = trim( $fscxml[ 'yaxis' ] );

	$array = array( 50, 30, 20, 15, 12, 10, 8, 7, 6, 5, 4, 3, 2, 1 );
	$r = '';
	foreach( $array as $a ) {
		$b = 1 / $a;
		$r .= "\"$a\" $b, ";
	}
	$r = trim( $r, ', ' );
	$s  = "
		set title '$t'
		set xlabel '$x'
		set ylabel '$y'
		set yrange [0:1]
		set nokey
		set ytics 0,0.5
		set mytics 5
		set xtics ( $r )
		set grid
		set term png
		set output \"plot.png\"
		set size 0.8,0.8
		plot \"-\" with linespoints
	";
	for ( $i = 0; true; $i++ ) {
		$x = trim( @$fscxml->coordinate[ $i ]->x );
		$y = trim( @$fscxml->coordinate[ $i ]->y );
		if ( $x == '' ) break;
		$s .= "$x $y \n";
	}
	$s .= "end \n";
	file_put_contents( 'plot.txt', $s );
	exec( "gnuplot plot.txt" );
	return "plot.png";
}

