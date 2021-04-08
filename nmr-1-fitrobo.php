NMRエントリ、fit_robot実行
==========

<?php
require_once( "commonlib.php" );
define( FITROBO,
	'cp ' . DN_SCRIPT . '/fit_robot/pref.txt .;' .
	DN_SCRIPT . '/fit_robot/fit_robot.exe <fn> ' .
	DN_SCRIPT . '/fit_robot/cya_v2_cor.lib' 
);

$dn_out = DN_DATA . '/pdb/fit_robo';
_mkdir( $dn_out );

//- NMRのID抽出
$ids = _file( DN_DATA . '/ids/nmr.txt' );

//. idを指定？

$i = [];
foreach ( $argv as $a ) {
	if ( strlen( $a ) != 4 ) continue;
	if ( $a == 'redo' ) continue;
	$i[] = $a;
}

define( 'FLG_IDS_SELECTED', $i != [] );
if ( FLG_IDS_SELECTED )
	$ids = $i;

//. main
foreach ( $ids as $id ) {
//	if ( $id != '2k2x' ) continue;
	_cnt( 'total' );
	//- モデルが1個しか無いデータは、無視
	$json = _json_load2( _fn( 'pdb_json', $id ) );
	if ( $json->pdbx_nmr_ensemble[0]->conformers_submitted_total_number < 2 ) {
//		_m( "$id: モデルが1個だけ" );
		_cnt( 'single' );
		continue;
	}

	//- ポリマーなし
	if ( count( $json->entity_poly ) == 0 ) {
//		_m( "$id: ポリマーなし" );
		_cnt( 'no polymer' );
		continue;
	}

	//- ペプチドなし
	$flg = false;
	foreach ( $json->entity_poly as $child ) {
		if ( ! _instr( 'polypeptide', $child->type ) ) continue;
		$flg = true;
		break;
	}

	if ( ! $flg ) {
//		_m( "$id: ペプチドなし" );
		_cnt( 'no peptide' );
		continue;
	}

	//- やるかやらないか
	$fn_pdb = _fn( 'pdb_pdb', $id );
	$fn_out = "$dn_out/$id.json";
	if ( FLG_REDO || FLG_IDS_SELECTED )
		_del( $fn_out );
	if ( _newer( $fn_out, $fn_pdb ) ) continue;

	//- main
	_line( '作成開始', $id );
	$dn = DN_TEMP . "/$id";
	exec( "rm -rf $dn" );
	_mkdir( $dn );
	chdir( $dn );

	$fn = "$dn/$id.pdb";
	$gz = "$fn.gz";

	copy( $fn_pdb, $gz );
	exec( "gunzip $gz" );
	_m( $result = _exec( strtr( FITROBO, [ '<fn>' => $fn ] ) ) );

	//- read data
	preg_match_all(
		'/Exported File +: (.+?)\n(.+?)\n\n/s' ,
//		'/##### target level: +([0-9]+) cluster: +([0-9]+) #####\n(.+)\n/',
		$result, $a , PREG_SET_ORDER
	);
	$data = [];
	_line( 'result', "$id - " . count( $a ) . ' data' );
	foreach ( $a as $p ) {
		$sel = [];
		foreach ( explode( "\n", $p[2] ) as $l ) {
			//- rms
			if ( substr( $l, 0, 1 ) == 'A' ) {
				$rms = preg_replace( '/^.+?  +/', '', $l );
				continue;
			}
			if ( substr( $l, -1 ) == ':' ) continue; //- そのチェーンには無い
			$sel[] = '(' . strtr( $l, [
				'Fit_resid (C,N,Ca) Chain_' => '*:' ,
				' : ' => ' and (' ,
				'_' => '-' ,
				',' => ' or '
			]) . '))';
		}

		$sel = implode( ' or ', $sel );
		
		$data[ $p[1] ] = [
			'sel' => $sel ,
			'rms' => $rms ,
			'det' => $p[2]
		];
		_m( $p[1] . ": sel($sel) rms($rms)"  );
	}
//	_pause();

	//- 結果
	_del( $fn );
	exec( "rm -f $dn_out/{$id}_*" );
	$list = glob( "$dn/*.pdb" );
	if ( count( $list ) > 0 ) {
		$json = [];
		foreach ( $list as $pn ) {
			$fn = basename( $pn );
			exec( "gzip $fn" );
			rename( "$fn.gz", "$dn_out/$fn.gz" );

//			$a = explode( '_', basename( $pn, '.pdb' ) );
			$json[ $fn ] = $data[ $fn ];
		}
		_json_save( $fn_out, $json );
		_m( "$id: " . count( $list ) . "個のデータを作成完了", 1 );
//		_pause();
	} else {
		_m( "$id: 作成失敗", -1 );
		_cnt( "failed" );
	}

	//- fin
	exec( "rm -rf $dn" );
//	if ( _count( 100, 100 ) ) break;
}
_cnt();
_end();
