<?php 
require_once dirname(__FILE__) . '/commonlib.php';

//$metname
//'X-RAY DIFFRACTION'

define( 'REGULAR_TYPE', [
	'RESIDUE' ,
]);

$stat = [];
$data = [];
foreach ( _idloop( 'pdb_json' ) as $fn ) {
	if ( _count( 1000, 0 ) ) break;
	$id = _fn2id( $fn );
	foreach ( (array)_json_load2( $fn )->struct_site as $c ) {
		$det = strtoupper( $c->details );
		if ( ! _instr( 'BINDING SITE FOR ', $det ) ) continue;
		$type = explode( ' ', strtr( $det, [ 'BINDING SITE FOR ' => '' ]) )[0];
		++ $stat[ $type ];
		if ( ! in_array( $type, REGULAR_TYPE  ) )
			$data[ $type ][] = $id;
	}
}
arsort( $stat );
_kvtable( $stat );
_comp_save( DN_PREP. '/site_stat.json.gz', $data );
