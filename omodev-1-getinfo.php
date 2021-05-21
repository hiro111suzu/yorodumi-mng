<?php

//. init
require_once( "commonlib.php" );
require_once( "omo-common.php" );
_mkdir( DN_PREP. '/omodev' );
_mkdir( DN_PREP. '/omodev/compinfo' );

//. main

foreach ( _idloop( 'pdb_json' ) as $fn_json ) {
	//.. 準備
	$pdb_id = _fn2id( $fn_json );
	if ( _count( 'pdb', 0 ) ) break;
	$fn_out = _fn( 'compinfo', $pdb_id );
	if ( FLG_REDO ) _del( $fn_out );
	if ( _newer( $fn_out, $fn_json ) ) continue;
	$json = _json_load2( $fn_json );

	//.. entity
	$ent_mw = $ent_len = $ent_type = [];
	//- エンティティごとの分子量
	foreach ( (array)$json->entity as $e ) {
		$ent_mw[ $e->id ] = $e->formula_weight;
	}
	foreach ( (array)$json->entity_poly as $e ) {
		$i = $e->entity_id;
		$ent_len[$i] = strlen( strtr(
			$e->pdbx_seq_one_letter_code_can ,
			[ "\n" => '', "\r" => '', ' ' => '' ]
		));

		$ent_type[$i] = '';
		if ( _instr( 'polypeptide', $e->type ) )
			$ent_type[$i] = 'pep';
		else if ( _instr( 'nucleotide', $e->type ) )
			$ent_type[$i] = 'nuc';
	}

	//.. asym
	$aids = $asym_mw = $asym_len = $asym_type = [];
	foreach ( (array)$json->struct_asym as $c ) {
		$eid = $c->entity_id;
		if ( $ent_type[ $eid ] == '' ) continue;
		$i = $c->id;
		$asym_mw[$i]   = $ent_mw[ $eid ];
		$asym_len[$i]  = $ent_len[ $eid ];
		$asym_type[$i] = $ent_type[ $eid ];
		$aids[] = $i;
		if ( $asym_mw[$i] == 0 ) {
			_prpblem( "$pdb_id-asym#$i (entity #$eid): mw = 0" );
		}
	}

	//.. structure
	//- 長さ合計
	$flg_alpha = $flg_beta = [];
	foreach ( (array)$json->struct_conf as $c ) {
		if ( $c->conf_type_id == 'TURN_P' ) continue;
		foreach ( range( $c->beg_label_seq_id, $c->end_label_seq_id ) as $i ) {
			$flg_alpha[ $c->beg_label_asym_id ][$i] = true;
		}
	}
	foreach ( (array)$json->struct_sheet_range as $c ) {
		foreach ( range( $c->beg_label_seq_id, $c->end_label_seq_id ) as $i ) {
			$flg_beta[ $c->beg_label_asym_id ][$i] = true;
		}
	}

	//- 割合から分子量へ
	$asym_alpha = $asym_beta = [];
	foreach ( $flg_alpha as $i => $ar ) {
		$asym_alpha[$i] = count( $ar ) / $asym_len[$i] * $asym_mw[$i];
//		_kvtable([
//			'asym-id' => $i ,
//			'len'	  => $asym_len[$i] ,
//			'mw'	=> $asym_mw[$i] ,
//			'alpha_res' => count( $ar )
//		], 'alpha');
	}
	foreach ( $flg_beta as $i => $ar ) {
		$asym_beta[$i]  = count( $ar ) / $asym_len[$i] * $asym_mw[$i];
//		_kvtable([
//			'asym-id' => $i ,
//			'len'	  => $asym_len[$i] ,
//			'mw'	=> $asym_mw[$i] ,
//			'alpha_res' => count( $ar )
//		], 'beta');
	}

	//.. assembly
	$asb2asym =[
		'd' => $aids
	];
	foreach ( (array)$json->pdbx_struct_assembly_gen as $c ) {
		foreach ( (array)explode( ',', $c->oper_expression ) as $dummy ) {
			foreach ( (array)explode( ',', $c->asym_id_list ) as $aid ) {
				if ( $asym_type[ $aid ] == '' ) continue;
				$asb2asym[ $c->assembly_id ][] = $aid;
			}
		}
	}

	//.. sum
	$data = [];
//	_kvtable( $asb2asym, 'asb'  );
	foreach ( $asb2asym as $abid => $asymids ) {
		if ( _inlist( "$pdb_id-$abid", 'identasb' ) ) continue;
		$sum = $alpha = $beta = $nuc = 0;
		$vals = [];
		foreach ( $asymids as $i ) {
			$w = $asym_mw[$i];
			$sum += $w;
			$vals[] = $w;
			if ( $asym_type[$i] == 'nuc' )
				$nuc += $w;
			$alpha += $asym_alpha[$i];
			$beta  += $asym_beta[$i];
		}
		if ( $sum == 0 ) { //- peptide/nuc がないエントリ
//			_m( "$pdb_id-$abid: ws = 0", 'red' );
			$sum = 1000000;
		}
		rsort( $vals );
		$data[ $abid ] = [
			'a' => $alpha   / $sum ,
			'b' => $beta    / $sum ,
			'n' => $nuc     / $sum ,
			'1' => $vals[0] / $sum ,
			'2' => $vals[1] / $sum ,
			'3' => $vals[2] / $sum ,
//			'2' => $sum - $vals[0] == 0
//				? 0 : $vals[1] / ( $sum - $vals[0] ),
//			'3' => $sum - $vals[0] - $val[1] == 0
//				? 0 : $vals[2] / ( $sum - $vals[0] - $val[1]  ),
		];
	}

	//.. test display
//	_m( json_encode( $data , JSON_PRETTY_PRINT) );
//	_pause( 'pause' );

	_json_save( $fn_out, $data ); 
}

//. 
_delobs_pdb( 'compinfo' );
_end();


