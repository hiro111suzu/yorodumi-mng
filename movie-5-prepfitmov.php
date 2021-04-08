-----
あてはめムービーの作成支援管理
fit.py作成
PDBデータのシンボリックリンク作成
-----
<?php

//. init
require_once( "commonlib.php" );

define( 'FITDB'   , _json_load( DN_PREP. '/emn/fitdb.json.gz' ) );
define( 'FITCONF' , _json_load( DN_PREP. '/fit_confirmed.json.gz' ) );

define( 'PY_REP', [
	"/'show_caps': 1,/"
		=> "'show_caps': False," ,
	"/'transparency_factor': 0.0,/"
		=> "'transparency_factor': 0.5," ,
	"/('surface_colors': \[[\n\r]* *\().+?\)/"
		=> '$1 0.95, 0.95, 0.95, 1.0, )' ,
	"/('coloring_table': {).+?([\n\r]+ +'geometry')/s"
		=> '$1},$2'
]);

$unreadble = new cls_blist( 'cif_chimera_cannot_read', 'Chimeraで読めないmmCIFリスト' );

$_filenames += [
	'bundle' => DN_FDATA . '/pdb_bundle/<s1>/<id>/<id>-pdb-bundle.tar.gz'
];

//. main
foreach ( _idlist( 'emdb' ) as $id ) {
	$dn_work = _fn( 'emdb_med', $id );
	$did = "emdb-$id";

	//- fitdb
	$fitted_pdb_list = array_unique( array_merge(
		(array)FITDB[ $did ],
		(array)FITCONF[ $did ]
	));
	if ( ! $fitted_pdb_list ) continue;

	//.. PDBファイルのシンボリックリンクを作成
	foreach ( $fitted_pdb_list as $pdb_id ) {
		$pdb_id = substr( $pdb_id, -4 );
		$fn_orig  = _fn( 'pdb_pdb', $pdb_id );
		$fn_link = "$dn_work/$pdb_id.ent.gz";

		//- PDB形式のファイルがない
		if ( ! file_exists( $fn_orig ) ) {
			$fn_link = "$dn_work/$pdb_id.cif.gz";
			$fn_orig = _fn( 'pdb_mmcif', $pdb_id );
		}

		//- bundle ファイルを利用パターン
		if ( $unreadble->inc( $pdb_id ) ) {
			_del( $fn_link );
			$fn_orig = _fn( 'bundle', $pdb_id, substr( $pdb_id, 1, 2 ) );
			$fn_link = "$dn_work/$pdb_id-pdb-bundle.tar.gz";
		}

		if ( _newer( $fn_link, $fn_orig ) ) continue;
		exec( "rm -f $fn_link; ln -s $fn_orig $fn_link" );
		_log( "$did: 作業ディレクトリに PDB-{$pdb_id} シンボリックリンク作成" );
	}

	//.. Chimeraセッションファイル作成
	$fn_in  = "$dn_work/s2.py";
	$fn_out = "$dn_work/fit.py";
	if ( ! _newer( $fn_out, $fn_in ) ) {
		file_put_contents( $fn_out, _reg_rep( 
			file_get_contents( $fn_in ) ,
			PY_REP
		) );
		_log( "$did: フィッティングセッション準備ファイル作成" );
	}
}

_end();
