chem_comp_cifから画像

<?php
//. init
require_once( "commonlib.php" );
$blist = new cls_blist( 'chem_img' );

$cmd = <<<'EOD'
set background white;
set ambientPercent 20;
set diffusePercent 40;
set specular ON;
set specularPower 80;
set specularExponent 2;
set specularPercent 70;
set antialiasDisplay ON;
set imageState OFF;
frank OFF;
load CIF <ciffn>;
select all;
wireframe 0.25;
spacefill 33%;
rotate best;
write image jpeg "<imgfn>";
EOD;


//. main
//$out = [];
_count();
foreach ( _idloop( 'chem_cif' ) as $fn_cif ) {
	if ( _count( 1000, 0 ) ) die;
	$id = _fn2id( $fn_cif );
	if ( $blist->inc( $id ) ) continue;

	$fn_img = _fn( 'chem_img', $id );
	if ( _same_time( $fn_cif, $fn_img ) ) continue;
	_m( "chem-$id: 画像作成", 1 );

	//- temp ファイル名
	$fn_temp = _tempfn( 'jpg' );

	//- Jmol
	_jmol( strtr( $cmd, [
			'<ciffn>'	=> $fn_cif ,
			'<imgfn>'	=> $fn_temp
		]), 
		200
	);

	if ( file_exists( $fn_temp ) ) {
		_del( $fn_img );
		_imgres( $fn_temp, $fn_img, 100 );
		if ( ! file_exists( $fn_img ) ) {
			_problem( "$id: 画像コピー失敗" );
		} else {
			touch( $fn_img, filemtime( $fn_cif ) );
		}
	} else {
		_problem( "$id: Jmolエラー" );
	}

	//- temp画像を消す
	_del( $fn_temp );
}
//. end
_end();
