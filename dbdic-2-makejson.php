<?php
require 'dbdic-common.php';

//. main loop
foreach ([
	[ 'mmCIF'    , FN_MMCIF_JSON, FN_MMCIF_DIC ],
	[ 'SASCIF'   , FN_SAS_JSON  , FN_SAS_DIC ],
] as $a ) {
	_line( 'mmCIF辞書', $a[0] );
	_comp_save( $a[1], _get( $a[2] ) );
}
_end();

//. function
function _get( $fn ) {
	$data = [];
	$file = _gzload( $fn );
	//- version
	preg_match( '/_dictionary.version +([0-9\.]+)/', $file, $a );
	$data[ 'ver' ] = $a[1];

	foreach ( _split_cifdic( $file ) as $a ) {
		$name = trim( strtr( $a[1], [ '[' => '', ']' => '' ] ) , '_' );
		$cont = trim( $a[2] );
		$cont = preg_replace( '/examples\.case.+$/s', '', $cont );
		if ( _instr( '.description  ', $cont ) ) {
			//.. description
			$cont = preg_replace( '/^.+?\.description +(.+?)\n.+$/s', '\1', $cont );
			$cont = trim( $cont, '"\' \n\r' );
		} else {
			//.. others
			$cont = trim( preg_replace(
				[ 
					'/^.+?\.description\n;?/s',
					'/\n;.+/s' ,
					'/\n {3,15}/s',
				], [ 
					'',
					'',
					"\n",
				],
				$cont
			) ) ;
			
			if ( _instr( '   ', $cont ) || _instr( '|', $cont ) ) {
				$cont = "<pre>$cont</pre>";
			} else {
				$cont = trim( preg_replace(
					[ 
						'/\n\n+/s' ,
						'/\n */m',
					], [ 
						"<br>",
						' ',
					],
					$cont
				) ) ;
			}
		}
		$data[ $name ] = $cont;
	}
	return $data;
}

