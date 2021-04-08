bird_comp_cifから画像

<?php
require_once( "bird-common.php" );

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
load CIF <fn_cif>;
select all;
wireframe 0.25;
spacefill 33%;
rotate best;
write image jpeg "<fn_img>";
EOD;

//. main
//$out = [];
_count();
foreach ( _idloop( 'prd_cifcc' ) as $fn_cif ) {
	if ( _count( 100, 0 ) ) break;
	$id = _fn2id( $fn_cif );
	$fn_img = _fn( 'bird_img', $id );
	if ( _newer( $fn_img, $fn_cif ) ) continue;
	_m( "bird-$id: 画像作成", 1 );

	//- temp ファイル名
	$fn_temp = _tempfn( 'jpg' );

	//- Jmol
	_jmol(strtr( $cmd, [
		'<fn_cif>'	=> $fn_cif ,
		'<fn_img>'	=> $fn_temp
	]), 200 );

	if ( file_exists( $fn_temp ) ) {
		_del( $fn_img );
		_imgres( $fn_temp, $fn_img, 100 );
		if ( ! file_exists( $fn_img ) ) {
			_problem( "$id: 画像コピー失敗" );
		}
	} else {
		_problem( "$id: Jmolエラー" );
	}

	//- temp画像を消す
	_del( $fn_temp );
}
//. end
_end();
