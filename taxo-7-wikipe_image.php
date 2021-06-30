<?php
require_once( "commonlib.php" );
require_once( 'taxo-common.php' );

//https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/Streptococcus_pneumoniae.jpg/150px-Streptococcus_pneumoniae.jpg
_mkdir( DN_DATA. "/taxo/wicon" );

foreach ( _tsv_load2( FN_TAXO_ANNOT )[ 'wikipe_img' ] as $taxo => $name ) {
	$bname = preg_replace( '/.+\//', '', $name );
	$fn_out = DN_DATA. "/taxo/wicon/$bname";
	_m( $taxo );
	if ( file_exists( $fn_out ) ) {
		_m( 'ok' );
		continue;
	}
	$url = ''
		. 'https://upload.wikimedia.org/wikipedia/commons/thumb/'
		. $name
		. '/150px-'
		. $bname
	;
	$fn_temp = _tempfn( 'jpg' );
	copy( $url, $fn_temp );
	if ( filesize( $fn_temp ) < 10 ) {
		_m( "$bname: ダウンロード失敗", -1 );
		_pause();
		continue;
	}
	rename( $fn_temp, $fn_out );
	_m( "Downloaded icon - $bname" );
//	_pause();
}



//https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/Streptococcus_pneumoniae.jpg/150px-Streptococcus_pneumoniae.jpg

//https://upload.wikimedia.org/wikipedia/commons/thumb/7/78/SARS-CoV-2_49534865371.jpg/150px-SARS-CoV-2_49534865371.jpg
//https://upload.wikimedia.org/wikipedia/commons/thumb/8/8c/Vaccinia_virus_PHIL_2143_lores.jpg/150px-Vaccinia_virus_PHIL_2143_lores.jpg
