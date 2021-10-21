<?php
//. init
require_once( 'commonlib.php' );
require_once( 'omo-common.php' );

$_filenames += [
	'omofh_data' => DN_PREP. '/omofh/data_<id>.json.gz' ,
	'omofh_doing' => DN_PREP. '/omofh/doing/<id>' ,
];

//. prep
$dbw = new cls_sqlw([
	'fn'		=> 'omofh' ,
	'cols' => [
		'id UNIQUE' ,
		'shape REAL' ,
		'func REAL' ,
		'hom REAL' ,
		'compos REAL' ,
		'size INTEGER' ,
	] ,
	'indexcols' => [ 'id', 'shape', 'func', 'hom', 'compos', 'size' ],
]);

//. main
foreach ( glob( DN_PREP. '/omofh/*' ) as $dn ) {
	if ( ! is_dir( $dn ) ) continue;
	$size = basename( $dn );
//	if ( $size == '0' ) continue;
	if ( _numonly( $size ) != $size ) continue;
	_line( 'size', $size );
	_count();
	foreach ( glob( "$dn/*.json.gz" ) as $pn ) {
		_count( 100 );
		$data = _json_load( $pn );
		if ( ! $data ) continue;
		foreach ( $data as $id => $vals ) {
			$shape = $func = $hom = 0;
			extract( $vals );
			if ( $shape < 0.75 ) continue;
			if ( $func * 10 < $hom ) continue;
			if ( $func < 0.001 ) continue;
			if ( 0.01 < $hom && 0.8 < $compos ) continue;
			$dbw->set([ $id, $shape, $func, $hom, $compos, $size ]);
			_cnt( $size );
		}
	}
}
_cnt();
$dbw->end();

