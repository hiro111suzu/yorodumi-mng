<?php
//. init

require_once( "commonlib.php" );
define( 'DN_HWORK', DN_FDATA. '/hwork' );
if ( ! is_dir( DN_HWORK ) )
	_mkdir( DN_HWORK );
if ( FLG_REDO ) {
	exec( "rm -rf ". DN_HWORK. '/*' );
	_m( 'ディレクトリクリーンアップ', 'red' );
}
$_filenames += [
	'hwork'		=> DN_HWORK. '/<id>' ,

	'map_to'	=> DN_HWORK. '/<id>/emd_<id>.map' ,
	'start_to'	=> DN_HWORK. '/<id>/start.py' ,
	'fit_to'	=> DN_HWORK. '/<id>/fit.py' ,

	'pdb_to'	=> DN_HWORK. '/<id>/<s1>.ent.gz' ,
	'mmcif_to'	=> DN_HWORK. '/<id>/<s1>.cif.gz' ,

	'map_from'  => '/data/unzipped_maps/<s1>/emd_<id>.map' ,
	'start_from' => DN_DATA. '/emdb/media/<id>/start.py' ,
	'fit_from' => DN_DATA. '/emdb/media/<id>/fit.py' ,
];

define( 'DN_UNZIP_MAP', '/data/unzipped_maps' );
define( 'FLG_DRY_RUN',  $argv[1] != 'do' );


//. ID収集
$emdb_ids = [];
$emdb2pdb = [];
$fitjson = _json_load( DN_PREP. '/fit_confirmed.json.gz' );

foreach ( _file( DN_PREP. '/problem/movie-1-movieinfo.txt' ) as $line ) {
	$emdb_ids[] = _numonly( explode( ':', $line )[0] );
}
$from_list = [];
foreach ( _file( DN_EDIT. '/hwork_emdb_id.txt' ) as $line ) {
	$id = trim( _numonly( $line ) );
	if ( ! _inlist( $id, 'emdb' ) ) {
		_m( "$id: non EMDB-ID" );
		continue;
	}
	$emdb_ids[] = $id;
	$from_list[] = $id;
	foreach ( (array)$fitjson["emdb-$id"] as $i ) {
		$emdb2pdb[ $id ][] = explode( '-', $line )[1];
	}
}
_m( 'リストでID指定: '. ( _imp( $from_list ) ?: 'なし' ) );

foreach ( _file( DN_PREP. '/problem/movie-6-check.txt' ) as $line ) {
	$emdb_id = _numonly( explode( ':', $line )[0] );
	$emdb_ids[] = $emdb_id;
	$emdb2pdb[ $emdb_id ][] = explode( '-', $line )[1];
}
$emdb_ids = array_unique( $emdb_ids );

//. main
foreach ( $emdb_ids as $id ) {
	_mkdir( DN_HWORK. "/$id" );

	$num = 0;
	if ( strlen( $id ) == 5 )
		$num = substr( $id, 0, 1 );
	_copy_job( 
		"$id: map",
		_fn( 'map_from', $id, $num ),
		_fn( 'map_to', $id )
	);

	_copy_job( 
		"$id: start-py",
		_fn( 'start_from', $id ),
		_fn( 'start_to', $id )
	);

	_copy_job( 
		"$id: fit-py",
		_fn( 'fit_from', $id ),
		_fn( 'fit_to', $id )
	);

	//- pdb
	foreach ( (array)$emdb2pdb[ $id ] as $pdb_id ) {
		if ( file_exists( _fn( 'pdb_pdb', $pdb_id ) ) ) {
			_copy_job(
				"$id: pdb-$pdb_id (PDB形式)" ,
				_fn( 'pdb_pdb', $pdb_id ) ,
				_fn( 'pdb_to', $id, $pdb_id )
			);
		} else if ( file_exists( _fn( 'pdb_mmcif', $pdb_id ) ) ) {
			_copy_job(
				"$id: pdb-$pdb_id (mmCIF形式)" ,
				_fn( 'pdb_mmcif', $pdb_id ) ,
				_fn( 'mmcif_to', $id, $pdb_id ) 
			);
		} else {
			_m( "No PDB data for $pdb_id (for $id)", 'red' );
		}
	}
}
_cnt();
//. upload
//_line( 'hwork data upload' );
/*
_line( FLG_DRY_RUN ? 'テストモードで実行 (本番は"do"をオプションで)' : '本番アップロード' );

$dry_run = FLG_DRY_RUN ? '--dry-run' : ''; 
passthru( "rsync $dry_run -avz --copy-links --exclude-from=exclude_upload_emn.txt --delete -e ssh ../emnavi/data/hwork hirofumi@pdbjiw1-p:/home/web/html/emnavi/" );
passthru(
	"rsync $dry_run -avz --delete -e ssh "
	. '../emnavi/data/hwork/ '
	. 'pdbj@pdbjif1-p:/home/archive/ftp/pdbj/emnavi/data/hwork/' 
);
*/
//. func
function _copy_job( $name, $from, $to )  {
	_cnt('all');
	if  ( file_exists( $to ) ) {
		if  ( filemtime( $to ) < filemtime( $from ) ) {
			_cnt('New file');
			_del( $to );
		} else {
			_cnt('already copied');
			return;
		}
	}
	if ( ! file_exists( $from ) ) {
//		_m( "ファイルがない: $from", -1 );
		_cnt('No source');
		return;
	}
	_m( "$name: $from => $to" ) ;
	copy( $from, $to );
	touch( $to, filemtime( $to ) );
	_cnt('copied');
	_m( 'ok' );
}

