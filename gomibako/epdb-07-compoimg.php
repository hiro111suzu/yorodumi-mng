PDB構成要素ごとの画像を作成

<?php
//. misc init
require_once( "commonlib.php" );

$rotate = array(
	0 => '',
	4 => 'rotate y 90;',
	3 => 'rotate y 180;',
	5 => 'rotate y 270;',
	2 => 'rotate x 90;',
	1 => 'rotate x 270;',
);


//. main loop
foreach ( $pdbidlist as $id ) {
	_count( 100 );
	$did = "pdb-$id";
	$iddn 	= DN_PDB_MED . "/$id";
	$dn		= "$iddn/compoimg";
	$depdn	= "$iddn/pre_dep";
	$json = _json( $id );

	if ( _proc( "compoimg-$id" ) ) continue;

	if ( $_redo )
		exec( "rm -rf $dn" );

	//- dep画像が更新されてたら、作り直し
	if ( is_dir( $dn ) and file_exists( "$iddn/snapssdep.jpg" ) ) {
		if ( filemtime( $dn ) < filemtime( "$iddn/snapssdep.jpg" ) )
			exec( "rm -rf $dn" );
	}
	_mkdir( $dn );

	//.. エンティティ毎のループ
	foreach( $json->entity as $c1 ) {
		$eid = $c1->id;

		$sfn    = "$dn/c{$eid}s.jpg";
		$fn		= "$dn/c{$eid}.jpg";
		$msfn   = "$dn/m{$eid}s.jpg";
		$mfn	= "$dn/m{$eid}.jpg";
		$scrfn	= "$depdn/script.txt";
		$orifn	= "$depdn/ori.txt";

		if ( _newer( $sfn, $scrfn ) ) continue;

		//... chain ID
		$csel = '';
		$x = $json->entity_poly;
		if ( count( $x ) > 0 ) foreach ( $x as $v ) {
			if ( $v->entity_id != $eid ) continue;
//			_m( "$eid-chain " );
			$csel = '*:' . substr( $v->pdbx_strand_id, 0, 1 )
				. ' and (protein or dna or rna)'
			;
		}

		$nsel = '';
		$x = $json->pdbx_entity_nonpoly;
		if ( count( $x ) > 0 ) foreach ( $x as $v ) {
			if ( $v->entity_id != $eid ) continue;
//			_m( "$eid-non-chain " );
			$nsel = '[' . $v->comp_id . ']';
		}
		$sel = $csel . $nsel;
		if ( $sel == '' ) continue;

		//... 方向
		$f = file_exists( $orifn )
			? file_get_contents( $orifn )
			: ''
		;
		$r = ( strlen( $f ) > 5 )
			? $f . 'slab off;set zshade on;slab 60'
			: $rotate[ (integer)$f ]
		;
		
		//... jmol実行
		_jmol( file_get_contents( $scrfn ) 
			. "$r;"
			. 'set chainCaseSensitive TRUE;'
			. "select $sel; select !selected; color white; color translucent 0.9;"
			. "select !selected;selectionhalo on; display displayed OR selected;"
			. "write image jpg 90 \"$fn\";"
			. "display selected; selectionhalo off;"
			. "write image jpg 90 \"$mfn\";"
		);

		//... 画像
		_imgres( $fn,  $sfn,  '100x100' );
		_imgres( $mfn, $msfn, '100x100' );
		_m( "$id-$eid: 画像作成"
			. ( file_exists( $sfn ) ? "成功" : "失敗！！！！！" )
			. " [$sel]"
		);	
	}
	_proc();
}
