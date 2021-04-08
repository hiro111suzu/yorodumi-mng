<?php
require_once( "commonlib.php" );
require_once( "pdbimg-common.php" );
$blacklist = [];

define( 'TSV_CMD', _tsv_load( DN_EDIT. '/pdbimg.tsv' ) );

//. 特定のIDのみ実行?
$_onlydoids = [];

/*
only=xxxx,xxxxで、そのIDのエントリだけ作成

まとめて再作成するときはこのスクリプト
foreach ( ['2n7c','2n84','2n87','2n8j','2n9m','2n9n','4xhq','4yqm','4yqu','4yqv','4z96','4z97','4z9c'] as $id ) {
	$_onlydoids[ $id ] = true;
}
*/

//foreach ( explode( ',', $argar[ 'only' ] ) as $id ) {
foreach ( explode( ',', $argv[1] ) as $id ) {
	if ( $id )
		$_onlydoids[ $id ] = true;
//	if ( strlen( $id ) == 4 )
//	else
//		die( "$id:4文字じゃない" );
}

if ( $_onlydoids != []) {
	_m( '再実行するID: ' . _imp( array_keys( $_onlydoids ) ) );
	foreach ( $_onlydoids as $id => $f ) {
		exec( 'rm -f ' . _fn( 'que_done', "$id*" ) ); //- AU/BU両方消す
	}
}

define( 'ONLYDOID', count( $_onlydoids ) > 0 );
if ( ONLYDOID )
	_m(  'Do only for:'. _imp( array_keys( $_onlydoids ) ) );


//. dep
_line( 'que情報作成', 'dep' );
_count();

foreach ( _idloop( 'pdb_pdb' ) as $fn_pdb ) {
	if ( _count( 5000, 0 ) ) break;

	$id = _fn2id( $fn_pdb );

	if ( ONLYDOID ) {
		if ( ! $_onlydoids[ $id ] && ! $_onlydoids[ "$id-d" ] ) continue;
		_line( '強制実行 dep', $id );
	} else {
		if ( _qok( $fn_pdb, $id, 'dep' ) ) continue;
	}

	$cmd_img = _inlist( $id, 'ribosome' )
		? J_RIBOSOME //- リボソーム
		: J_BEST; //- ベストアングル
	;

	_save_que( "$id-dep", $fn_pdb, [
		'id'	=> $id ,
		'type'	=> 'dep' ,
		'cmd'	=> strtr( J_BASE . $cmd_img, [
			'<fn>'		=> $fn_pdb ,
			'<style>'	=> '' ,
			'<ccolor>'	=> _inlist( $id, 'multic' ) ? J_COL_CHAIN : J_COL_MONO ,
			'<filt>'	=> '' ,
			'<cmd2>'	=> TSV_CMD[ "$id-d" ] ,
			"\r"		=> '' ,
			"\n"		=> ''
		])
	]); 
}

//. asb
_line( 'que情報作成', '集合体' );

_count();
foreach ( _idloop( 'pdb_json' ) as $pn ) {
	_count( 5000 );
	$id = _fn2id( $pn );
	$fn_mmcif = _fn( 'pdb_mmcif', $id );

	if ( ONLYDOID ) {
		if ( ! $_onlydoids[ $id ] ) continue;
		_line( '強制実行 asb', $id );
	}

	//- 色、登録構造がマルチ -> チェーン毎、
	$col_by = _inlist( $id, 'multic' ) ? J_COL_CHAIN : J_COL_MOLEC ;

	//.. assemblyごと
	foreach ( (array)_json_load2( $pn )->pdbx_struct_assembly as $json_asb ) {
		$asb_id = $json_asb->id;
		
		//... やるかやらないか
		if ( !ONLYDOID && _qok( $fn_mmcif, $id, $asb_id ) )
			continue; //- もうあるならやらない

//		if ( in_array( "$id-$asb_id", $blacklist ) ) continue; //- ブラックリスト
		if ( _instr( $asb_id, 'PAU|XAU|HAU' ) ) continue; //- 別のアシメならやらない
		if ( _inlist( "$id-$asb_id", 'identasb' ) ) continue;//- おなじならやらない

		$det = strtr( $json_asb->details, '_', ' ' );
		if ( in_array( $det, [
			'icosahedral asymmetric unit' ,
			'helical asymmetric unit' ,
			'point asymmetric unit'
		] ) ) continue;

//		_m( "$id-$asb_id" );
//		_pause( "$id-$asb_id: やることになったが" );

		//... 対称性、変わった奴?
		$sym = '';
		if ( $det == 'complete icosahedral assembly' )
			$sym = 'icos';
		if ( $det == 'representative helical assembly' )
			$sym = 'helical';

		//... チェーン数多い （100以上か、icosで数が不明）
		$bb = '';
		$st2  = '';
		$n = $json_asb->oligomeric_count;
		if ( ( $n > 100 ) || ( $n == '' and _instr( 'icosa', $det ) ) ) {
			$bb = J_FILT_BB;
			$st2  = 'select dna or rna; backbone 250;';
		}

		//... 方向
		$cmd_img = J_BEST; //- ベストアングル
		$ori6 = false;
		if ( _inlist( $id, 'ribosome' ) ) {
			$cmd_img = J_RIBOSOME;
		} else if ( $sym == 'icos' ) {
			$cmd_img = J_IMG_ORIG; //- そのままの方向
		} else if ( $sym == 'helical' ) {
			$cmd_img = J_LARGEST; //- サイズが大きいjpeg
			$ori6 = true;
		}

		if ( $cmd_img == '' )
			_problem( "$id: #$asb_id bad script" );

		//... 保存
		_save_que( "$id-$asb_id", $fn_mmcif, [
			'id'	=> $id ,
			'type'	=> $asb_id ,
			'ori6'	=> $ori6 ,
			'cmd'	=> strtr( J_BASE . $cmd_img, [
				'<fn>'		=> $fn_mmcif ,
				'<filt>'	=> "$bb,biomolecule $asb_id" ,
				'<style>'	=> $st2 ,
				'<ccolor>'	=> $col_by ,
				'<cmd2>'	=> TSV_CMD[ "$id-$asb_id" ] ,
				"\r" => '' ,
				"\n" => ''
			])
		]); 
	}
}


//. 無くなったエントリ消去
_delobs_pdb( 'que_todo' );
_delobs_pdb( 'que_done' );
_end();
