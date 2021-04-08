<?php
require 'commonlib.php';

//. main
_comp_save(
	DN_DATA . '/pdb/mmcif_dic.json.gz', 
	_get( DN_FDATA . '/mmcif_pdbx_v40.dic' )
);

_comp_save(
	DN_DATA . '/sas/sascif_dic.json.gz', 
	_get( DN_FDATA . '/mmcif_sas.dic' )
);


//. func
function _get( $fn ) {
	$data = [];
	preg_match_all( '/\nsave_(.+?)\n(.+?)save_\n/s',
		file_get_contents( $fn ), $match, PREG_SET_ORDER );
	foreach ( $match as $a ) {
		$name = trim( $a[1], '_' );
		$cont = trim( $a[2] );
		$cont = preg_replace( '/examples\.case.+$/s', '', $cont );
		if ( _instr( '.description  ', $cont ) ) {
			$cont = preg_replace( '/^.+?\.description +(.+?)\n.+$/s', '\1', $cont );
			$cont = trim( $cont, '"\' \n\r' );
		} else {
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
//		_pause( "[$name]\n$cont" );
		$data[ $name ] = $cont;
	}
	return $data;
}

