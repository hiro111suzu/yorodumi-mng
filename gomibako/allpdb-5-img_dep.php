PDB画像

<?php
require_once( "commonlib.php" );
//_initlog( "emdb-0: rsync" );
$blacklist = [
	'1kga', '1pgi', '1pyk', '2pgk'
];

$imgdn = DN_DATA  . '/pdb/img_dep';
$pdbdn = DN_FDATA . '/pdb/dep';


//. jmol コマンド
$cmd = <<<'EOF'
set background white;
set ambientPercent 20;
set diffusePercent 40;
set specular ON;
set specularPower 80;
set specularExponent 2;
set specularPercent 70;
set antialiasDisplay ON;
set ribbonBorder ON;
set imageState OFF;
frank OFF;
load <fn> filter "![HOH]";
select all;cartoon ONLY;
<style>
select hetero; wireframe 0.5; spacefill 50%; color CPK;
slab off; set zshade on; set zshadepower 1; slab 60;
wireframe 0.25;
select (unk and !sidechain); wireframe 0.3;cpk 0.3; backbone 200;
select all;
rotate best; rotate z 90;
write image jpeg "<tmpimg>";
EOF;

$jm_st		= "color chain;";			//- チェーンごとの色
$jm_mono	= "color monomer;";			//- モノマー用

//. main
//$out = [];
_count();

foreach ( glob( "$pdbdn/*.gz" ) as $pdbfn ) {
	$id = substr( basename( $pdbfn, '.ent.gz' ), -4 );
	if ( in_array( $id, $blacklist ) ) continue;

	$imgfn = "$imgdn/{$id}.jpg";
	if ( _redo_id( $id ) )
		_del( $imgfn );
	if ( _sametime( $pdbfn, $imgfn ) ) continue;

	_m( $id, 1 );
	$st = $jm_st;
	$msg = [ $id ];

	if ( file_exists( $n = _fn( 'pdb_json', $id ) ) ) {
		$json = _json_load2( $n );

		//- monomer ?
		$chcnt = 0;
		foreach ( (array)$json->entity_poly as $j ) {
			$chcnt += count( explode( ',', $j->pdbx_strand_id ) );
		}

		if ( $chcnt < 2 ) {
			$st = $jm_mono;
			$msg[] = 'モノマー';
		} else {
			$msg[] = 'オリゴマー';
		}
	} else {
		$msg[] = 'jsonがない';
	}

	//- temp ファイル名
	$tmpimg = _tempfn( 'jpg' );

	//- Jmol
	_jmol( strtr( $cmd, [
			'<fn>'		=> $pdbfn ,
			'<style>'	=> $st ,
			'<tmpimg>'	=> $tmpimg
		] ), 200
	);
	_m( implode( ' / ', $msg ) );

	if ( file_exists( $tmpimg ) ) {
		_del( $imgfn );
		_imgres( $tmpimg, $imgfn, 100 );

		if ( ! file_exists( $imgfn ) ) {
			_problem( "[$id] 画像コピー失敗" );
		} else {
			touch( $imgfn, filemtime( $pdbfn ) );
		}
	} else {
		_problem( "[$id] Jmolエラー", -1 );
	}

	//- temp画像を消す
//	_del( $tmpimg );

//	if ( _count( 1000, 1 ) ) _pause();
}

//. 無くなったエントリ消去
_delobs( "$imgdn/*.jpg" );
_problem();

