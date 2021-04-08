<?php
include "commonlib.php";
include "sas-common.php";

define( 'PLOT_PARAM', [
	'scan_intensity' => [
		'xlabel' => 'momentum transfer' ,
		'ylabel' => 'intensity' ,
		'opt'	=> 'set logscale y;set format y "10^{%L}";' ,
	] ,
	'p_of_R' => [
		'xlabel' => 'r' ,
		'ylabel' => 'P' ,
		'opt'	=> 'set yrange [0:];' ,
	],
	'p_of_R_extrapolated_intensity' => [
		'xlabel' => 'momentum transfer' ,
		'ylabel' => 'intensity reg' ,
		'opt'	=> 'set logscale y;set format y "10^{%L}";' ,
	],
	'model_fitting' => [
		'xlabel' => 'momentum transfer' ,
		'ylabel' => 'intensity' ,
		'opt'	=> 'set logscale y;set format y "10^{%L}";' ,
	]
]);

define( 'PLOT_ID_COL', [
	'scan_intensity'	=> 'scan_id' ,
	'p_of_R' 			=> 'id' ,
	'p_of_R_extrapolated_intensity' => 'id' ,
	'model_fitting'		=> 'id'
]);

define( 'PLOT_CMD', <<<EOF
set xlabel '<xlabel>';
set ylabel '<ylabel>';
set grid;
<opt>
set term svg font 'Helvetica,14';
set bars small;
set size 1,1;
set output '<fn_img>';
plot <plot>;
EOF
);

define( 'DN_PLOT', DN_DATA. '/sas/plot' );
_mkdir( DN_PLOT );

//. mainloop
foreach ( _idloop( 'sas_cif' ) as $fn_cif ) {
	_count( 'sas' );
	$id = _fn2id( $fn_cif );
//	if ( $id != 'SASDBC3' ) continue; //----------
	$res_cif = fopen( $fn_cif, 'r' );

	$mode = 'search';

	$line_num = 0;
	while ( true ) {
		$line = fgets( $res_cif );
		if ( $line === false ) {
			fclose( $res_cif );
			break;
		}
		++ $line_num;
		list( $categ, $item ) = explode( '.', $line );
		$categ = strtr( $categ, [ '_sas_' => '' ] );
		$item = trim( $item );

		//.. search
		if ( $mode == 'search' ) {
			if ( ! PLOT_PARAM[ $categ ] ) continue;
			$mode = 'header';
			_m( "$id: $categ" );
			$cur_categ = $categ;
			$data = [];
			$subid = '';
			$subid_col = 0;
			$col_num = 0;
		}

		//.. header
		if ( $mode == 'header' ) {
			if ( $categ == $cur_categ ) {
				if ( $item == PLOT_ID_COL[ $categ ] )
					$subid_col = $col_num;
				++ $col_num;
			} else {
				$mode = 'data';
			}
		}

		//.. data
		if ( $mode == 'data' ) {
			$split = preg_split( '/ +/', trim( $line ) );
			if ( count( $split ) != $col_num ) {
				$mode = 'plot';
			} else {
				if ( ! $subid ) {
					$subid = $split[ $subid_col ];
				}
				$data[] = trim( $line );
			}
		}

		//.. plot
		if ( $mode == 'plot' ) {
			$fn_data = _tempfn( 'txt' );
			file_put_contents( $fn_data, implode( "\n", $data ). "\n" );
			$fn_img = DN_PLOT. "/$cur_categ-$subid.svg";
			_m( $fn_img );
			$curves = [];
			if ( $cur_categ == 'scan_intensity' ) {
				$curves = "'$fn_data' using 2:3:4 "
					. "with yerrorbars pointtype 5 pointsize 0.15 lw 0.2 "
					. "title 'scan intensity #$subid'"
				;

			} else if ( $cur_categ == 'p_of_R' ) {
				$curves = "'$fn_data' using 3:4:5 "
					. "with boxerrorbars lw 0.5 "
					. "title 'P(R) #$subid'"
				;

			} else if ( $cur_categ == 'p_of_R_extrapolated_intensity' ) {
				$curves = "'$fn_data' using 3:4 "
					. "with lines lw 2 "
					. "title 'P(R) extrapolated intensity' "
				;
				
			} else if ( $cur_categ == 'model_fitting' ) {
				$curves = "'$fn_data' using 3:4 "
					. "with points pointtype 5 pointsize 0.15 "
					. "title 'scan intensity', "
					. "'$fn_data' using 3:5 "
					. "with lines lw 2.5 "
					. "title 'model fitting #$subid'"
				;

			}
			_gnuplot( strtr( PLOT_CMD, [
				'<xlabel>' => PLOT_PARAM[ $cur_categ ][ 'xlabel' ] ,
				'<ylabel>' => PLOT_PARAM[ $cur_categ ][ 'ylabel' ] ,
				'<fn_img>' => $fn_img ,
				'<opt>'		=> PLOT_PARAM[ $cur_categ ][ 'opt' ] ,
				'<fn_data>'	=> $fn_data ,
				'<plot>'   => $curves,
			]) );
			_del( $fn_data );
			$mode = 'search';
		}
	}
}


