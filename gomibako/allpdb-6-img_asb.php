PDB assembly 画像

<?php
require_once( "commonlib.php" );

_mkdir( $imgdn  = DN_DATA  . '/pdb/img_asb' );
_mkdir( $infodn = DN_PREP  . '/info_asb' );

$pdbdn = DN_FDATA . '/pdb/dep';
$multic = _file( DN_DATA . '/multic.txt' );

define( 'NO_CHECK', in_array( 'no_check', $argv ) );

/*
$is_icos = [];
foreach ( _data_load( DN_DATA . '/ids_icos.txt' ) as $i )
	$is_icos[ $i ] = true;

$is_helic = []
foreach ( _data_load( DN_DATA . '/ids_helical.txt' ) as $i )
	$is_helic[ $i ] = true;
*/

//. jmol コマンド
$cmd_base = <<<'EOF'
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
load "<fn>" FILTER "![HOH],<filt>biomolecule <limit>";
select all;cartoon ONLY;
<style>
select hetero; wireframe 0.5; spacefill 50%; color CPK;
hide water;
slab off; set zshade on; set zshadepower 1; slab 60;
wireframe 0.25;
select (unk and !sidechain); wireframe 0.3;cpk 0.3; backbone 200;
select all;
EOF;


$img_best = <<<EOF
rotate best; rotate z 90;
write image jpeg "<tmpimg>";
EOF;

$img_orig = <<<EOF
write image jpeg "<tmpimg>";
EOF;

$img_largest = <<<EOF
write image jpeg "<img1>";
reset; rotate x 180; write image jpeg "<img2>";
reset; rotate x  90; write image jpeg "<img3>";
reset; rotate x -90; write image jpeg "<img4>";
reset; rotate y  90; write image jpeg "<img5>";
reset; rotate y -90; write image jpeg "<img6>";
EOF;


//$jm_st		= "color chain;";			//- 全部
//$jm_mono	= "color monomer;";			//- モノマー用
//$jm_asb 	= "color molecule;";		//- bm用
//$jm_trace	= "trace ONLY; trace 300;";
//$jm_grp		= "color group;";
$jm_bb		= '*.CA,*.P,![ca],![HETATOM],';

//. main
//$out = [];
_count();

foreach ( glob( _fn( 'pdb_json', '*' ) ) as $pn ) {
	_count( 2000 );

	$id = basename( $pn, '.json.gz' );
//	if ( substr( $id, 0, 3 ) != '4ag' ) continue;
	$pdbfn = _fn( 'pdb_mmcif', $id );
	$infofn = "$infodn/$id.json"; //- 画像を作るべきassembly_idのarrayが入る（今のところ）

	if ( _redo_id( $id ) )
		_del( $infofn );

	if ( file_exists( $infofn ) ) continue; //- test


	//- infoファイルのタイムスタンプが同じ、画像ファイルが全部あるならやらない
	if ( _sametime( $pdbfn, $infofn ) ) {
		if ( NO_CHECK ) continue; 
		$j = _json_load( $infofn );
		$f = true;
		foreach ( (array)$j as $i ) {
			if ( ! file_exists( _imgfn( $id, $i ) ) )
				$f = false;
		}
		if ( $f ) continue;
	}
	_proc( "pdb-img-asb-$id" );

	exec( "rm -f $imgdn/{$id}_*.jpg" );
	$info = [];
	$json = _json_load2( $pn );

	//.. asym_idの種類を数える
	$ar = [];
	foreach ( (array)$json->entity_poly as $c )
		$ar[] = $c->pdbx_strand_id; //- 複数IDがコンマ区切りで入っている

	foreach ( (array)$json->pdbx_nonpoly_scheme as $c )
		$ar[] = $c->asym_id;

	$num_mol = count( array_filter( array_unique( explode( ',', implode( ',', $ar ) ) ) ) );

	//.. operation list
	$optype = [];
	foreach ( (array)$json->pdbx_struct_oper_list as $c )
		$optype[ $c->id ] = $c->type;

	//.. gen
	$g = []; //- oper_expression-IDが入る
	$same = []; //- フラグ 登録構造と同じか？

	foreach ( (array)$json->pdbx_struct_assembly_gen as $c ) {
		$abid = $c->assembly_id;
		$ar = [];
		foreach ( explode( ',', $c->oper_expression ) as $n )
			$ar[] = $optype[ $n ];
		$ops = implode( ' + ', array_unique( $ar ) );

		//- 登録構造と同じか？ （identity operationのみ かつ 分子数が同じなら）
		$same[ $abid ] = (
			$ops == 'identity operation'
			and
			$num_mol == count( explode( ',', $c->asym_id_list ) ) 
		);

//		if ( $abid == 1 ) continue;

		//- point/cryst../helix.  asymmetric unit?
		if ( _instr( $abid, 'PAU|XAU|HAU' ) ) continue;

		$s = trim( $c->oper_expression, '()' );
		if ( $s != '' ) foreach ( explode( ',', $s ) as $t ) {
			//- ハイフンナシならそのまま、ありならその間を全部列挙
			if ( ! _instr( '-', $t ) ) {
				$g[ $abid ] .= '#' . (integer)$t . ',';
			} else {
				$u = explode( '-', $t );
				foreach ( range( $u[0], $u[1] ) as $i )
					$g[ $abid ] .= "#$i,";
			}
		}
		$g[ $abid ] = rtrim( $g[ $abid ], ',' );
	}

	//.. main
	//- 色、登録構造がマルチ -> チェン毎、
	$col_by = in_array( $id, $multic ) ? 'chain' : 'molecule';

	$msg = [];
	$sym = '';
	foreach ( (array)$json->pdbx_struct_assembly as $c ) {
		if ( $c == '' ) continue;  //- ?
		$abid = $c->id;
		if ( $same[ $abid ] ) continue;
		if ( in_array( $abid, [ 'PAU', 'XAU', 'HAU' ] ) ) continue;

		$det = strtr( $c->details, '_', ' ' );
		if ( in_array( $det, [
			'icosahedral asymmetric unit' ,
			'helical asymmetric unit' ,
			'point asymmetric unit'
		] ) ) continue;

		//... limit
		//- 対称性、変わった奴
		if ( $det == 'complete icosahedral assembly' )
			$sym = 'icos';
		if ( $det == 'representative helical assembly' )
			$sym = 'helical';

		$limit = $abid;

		//- icosなら
		if ( $sym != '' and $abid != '1' )  {
			if ( $g[ $abid  ] == '' ) continue;
			$limit = '1,' . $g[ $abid ];
		}
		
		//... チェーン数多い （100以上か、icosで数が不明）
		$filt = '';
		$st2  = '';
		$n = $c->oligomeric_count;
		if ( ( $n > 100 ) or ( $n == '' and _instr( 'icosa', $det ) ) ) {
			$filt = $jm_bb;
			$st2  = 'select dna or rna; backbone 250;';
		}

		//... 画像作成
		$imgfn = _imgfn( $id, $abid );

		if ( $sym == 'icos' )
			$cmd_img = $img_orig;
		else if ( $sym == 'helical' )
			$cmd_img = $img_largest;
		else
			$cmd_img = $img_best;

		//- temp ファイル名
		$tmpimg = _tempfn( 'jpg' );
		if ( $sym == 'helical' ) {
			$fn = [];
			foreach ( range( 2, 6 ) as $n )
				$fn[ $n ] = _tempfn( 'jpg' );
			$fn[1] = $tmpimg;
		}


		//- Jmol
		_m( "[$id-$abid] $det, limit:$limit, filt:$filt" );
		$cmd = strtr( $cmd_base . $cmd_img, [
			'<fn>'		=> $pdbfn,
			'<filt>'	=> $filt,
			'<limit>'	=> $limit,
			'<style>'	=> "color $col_by;$st2" ,
			'<tmpimg>'	=> $tmpimg ,
			'<img1>'	=> $fn[1] ,
			'<img2>'	=> $fn[2] ,
			'<img3>'	=> $fn[3] ,
			'<img4>'	=> $fn[4] ,
			'<img5>'	=> $fn[5] ,
			'<img6>'	=> $fn[6] 
		] );
		
		//- 3回トライ
		for ( $i = 0; $i < 3; $i ++ ) {
			_jmol( $cmd, 200 );
			if ( filesize( $tmpimg ) > 0 ) break;
			_m( "リトライ $i" );
		}

		if ( filesize( $tmpimg ) > 0 ) {
			_del( $imgfn );
			if ( $sym == 'helical' )
				$tmpimg = _img_largest( $fn );
			_imgres( $tmpimg, $imgfn, 100 );

			if ( ! file_exists( $imgfn ) ) {
				_problem( "[$id-$abid] 画像コピー失敗" );
			}
		} else {
			copy( DN_PREP . '/blank.jpg', $imgfn );
			_problem( "[$id-$abid] 画像作成失敗、空白画像を利用", -1 );
		}

		//- temp画像を消す
		_del( $tmpimg );
		if ( $sym == 'helical' ) foreach ( $fn as $f )
			_del( $f );
		

		//... ループ終わり
		$info[] = $abid;
	}

	_json_save( $infofn, $info );
	if ( file_exists( $infofn ) )
		touch( $infofn, filemtime( $pdbfn ) );

	_m( "$id: " . count( $info ) . "個画像作成完了" , 1 );

	_proc();
}

//. 無くなったエントリ消去
_delobs( "$imgdn/*.jpg" );
_problem();

function _imgfn( $id, $num ) {
	global $imgdn;
	return "$imgdn/{$id}_{$num}.jpg";
}

