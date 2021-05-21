<?php

//. 新規データのみ、変更データは放置
//- remediation 直後など大量のデータの更新があった場合はtrueにする
define( 'DO_ONLY_NEW', false );

if ( DO_ONLY_NEW )
	_m( '変更エントリは無視して、新規エントリのみ対応するモード' );

//. ファイル名
$_filenames += [
	'que_todo'	=> DN_PREP . '/img_que/todo/<name>.json' ,
	'que_done'	=> DN_PREP . '/img_que/done/<name>.json' ,
	'img_dep'	=> DN_DATA . "/pdb/img_dep/<id>.jpg" ,
	'img_asb'	=> DN_DATA . "/pdb/img_asb/<name>.jpg" ,
	'img_rep'	=> DN_DATA . "/pdb/img/<id>.jpg" ,
];

//. jmol コマンド

define( 'J_BASE', <<<'EOD'
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
slab off;
set zshade on;
set zshadepower 1;
slab 60;
frank OFF;

load "<fn>" FILTER "![HOH]<filt>";

hide water;
select !unk; cartoon ONLY;

<style>
<ccolor>
select hetero; wireframe 0.5; spacefill 50%; color CPK;
select (unk and !sidechain); cpk 50%; backbone 200;color chain;
select connected(0,0) and (!hetero);cpk 70%; backbone 200; color chain;
<cmd2>
select all; center all;

EOD
);

define( 'J_BEST',  <<<EOD
rotate best; rotate z 90;
write image jpeg "<tmpimg>";
EOD
);

define( 'J_RIBOSOME', <<<EOD
select rna; rotate best; rotate z 90;
write image jpeg "<tmpimg>";
EOD
);

define( 'J_LARGEST', <<<EOD
write image jpeg "<img1>";
reset; rotate x 180; write image jpeg "<img2>";
reset; rotate x  90; write image jpeg "<img3>";
reset; rotate x -90; write image jpeg "<img4>";
reset; rotate y  90; write image jpeg "<img5>";
reset; rotate y -90; write image jpeg "<img6>";
EOD
);

define( 'J_IMG_ORIG', <<<EOD
write image jpeg "<tmpimg>";
EOD
);

define( 'J_COL_CHAIN',	'color chain;' );		//- チェーンごとの色
define( 'J_COL_MONO',	'color monomer;' );		//- モノマー用
define( 'J_COL_MOLEC',	'color molecule;' );	//- モノマーのアセンブリ用
define( 'J_FILT_BB',	',*.CA,*.P,![ca],![HETATOM]' );

//. function
//.. _qok
//- やらなくていいならtrueを返す、trueでcontinue
function _qok( $fn_coord, $id, $type ) {
	if ( _redo_id( $id ) ) {
		_m( "$id: to redo", 'blue' );
		return;
	}

	$flg = false;
	foreach ([
		_fn( 'que_todo', "$id-$type" ) ,
		_fn( 'que_done', "$id-$type" ) ,
	] as $fn_que ) {
		if ( ! file_exists( $fn_que ) ) {
			continue;
		}

		//- ONLY_NEWモードだったら、タイムスタンプ見ない
		if ( DO_ONLY_NEW )
			return true;

		$flg  = ( filemtime( $fn_que ) == filemtime( $fn_coord ) );
		break;
	}
	return $flg;
}

//.. _save_que
function _save_que( $name, $fn_coord, $data ) {
	_del( _fn( 'que_done', $name ) );
	$fn_todo = _fn( 'que_todo', $name );
	_json_save( $fn_todo, $data );
	_log( "$name: made que file", 'blue' );
	touch( $fn_todo, filemtime( $fn_coord ) );
}
