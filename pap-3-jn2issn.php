jn=>issnデータ作成、多数決、小さい数字優先

<?php
require_once( "pap-common.php" );

$data = [];

//. main

_line( '取り出し' );
foreach ( [ 'sas', 'pdb', 'emdb' ] as $db ) {
	foreach ( _idloop( $db . '_pap' ) as $fn ) {
		_count( 5000, 0 );
		$json = _json_load2( $fn );
		$jn = $json->journal;
		$issn = $json->issn;
		if ( $jn == '' or $issn == '' ) continue;
		++ $data[ $jn ][ $issn ];
	}
}

//. 解析
_line( '解析' );

$out = [];
$uk =[];
foreach ( $data as $jn => $c ) {
	_cnt('total');
	ksort( $c );
	arsort( $c );
	
	foreach ( array_keys( $c ) as $id ) {
		$if = _is2if( $id );
		if ( $if == '' ) {
			$uk[ $jn ][] = $id;
			_cnt('unknown');
			continue;
		}
		_cnt('got IF');
		$out[ $jn ] = $id;
		break;
	}
}
_cnt();
_comp_save( FN_JN2ISSN, $out );

$tsv = '';
foreach ( $uk as $jn => $ids ) {
	$tsv .= "$jn\t" . _imp( $ids ) . "\n";
}
_comp_save( DN_PREP. '/pap/unknown.tsv', $tsv );
