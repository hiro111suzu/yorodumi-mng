メインDB書き込み
----------
<?php
//. misc. init
require_once( "commonlib.php" );

$_filenames += [
	'omolist' => DN_PREP. '/omolist/<did>.txt'
];

//.. DB準備
$sqlite = new cls_sqlw([
	'fn'		=> 'emn' ,
	'cols' => [
		'did UNIQUE COLLATE NOCASE' ,
		'status' ,
		'addinfo' ,
		'movinfo' ,
		'mapinfo' ,
		'filelist' ,
		'omolist' ,
		'fit' ,
		'related' ,
	] ,
	'indexcols' => [ 'did' ],
	'new'		=> true
]);

$status  = _json_load2( FN_DBSTATUS );
$movinfo = _json_load2( FN_PDB_MOVINFO );
$fitdb   = _json_load2( DN_PREP. '/emn/fitdb.json.gz' );
$related = _json_load2( DN_PREP. '/emn/related.json' );
//$asb     = _json_load2( FN_ASB_JSON );

//. main loop

//.. emdb
_line( 'EMDB' );
foreach ( _idlist( 'emdb' ) as $id ) {
//	continue;
	_count( 'emdb' );
	$did = "emdb-$id";
	$sqlite->set([
//	_test([
		$did ,
		_j2s( $status->$did ) ,
		_get_json( 'emdb_add' ) ,
		_get_json( 'movinfo' ) ,
		_get_json( 'mapinfo' ) ,
		_get_json( 'filelist' ) ,
		_j2s( _file( _fn( 'omolist', "emdb-$id" ) ) ) ,
		_j2s( $fitdb->$did ) ,
		_j2s( $related->$did ) ,
	]);
}

//.. pdb
_line( 'PDB' );
_count();
foreach ( _idlist( 'epdb' ) as $id ) {
	_count( 'epdb' );
	$did = "pdb-$id";
	$sqlite->set([
//	_test([
		$did ,
		_j2s( $status->$did ) ,
		_get_json( 'pdb_add' ) ,
		_j2s( $movinfo->$id ) ,
		'' ,
		'' ,
		_j2s( _file( _fn( 'omolist', "pdb-$id" ) ) ) ,
		_j2s( $fitdb->$did ) ,
		_j2s( $related->$did ) ,
	]);
}

//. end
$sqlite->end();

//. function _get_json
function _get_json( $type ) {
	global $id;
	return _j2s( _json_load([ $type, $id, ] ) );
}
function _j2s( $in ) {
//	return $in ? gzencode( json_encode( $in, JSON_UNESCAPED_UNICODE ) ): '';
	return $in ? json_encode( $in, JSON_UNESCAPED_UNICODE ): '';
}

function _test( $in ) {
	global $did;
	_line( $did );
	_pause( $in );
}

