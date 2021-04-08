<?php
require_once( "commonlib.php" );
/*
$_filenames += [
	'compo_json' => DN_PREP. '/pdb_compo/<id>.json.gz' ,
];
*/
$_filenames += [
	'snfg_img' => DN_PREP. '/chem/polysac_img/<id>_SNFG_<s1>.svg'
];

$sqlite = new cls_sqlw([
	'fn' => 'polysac', 
	'cols' => [
		'desc UNIQUE' ,
		'pdb' ,
		'comp' ,
		'svg' ,
	] ,
	'new' => true ,
	'indexcols' => [ 'desc' ] ,
]);

$flg_icon = [];
foreach ( glob( DN_DATA. '/chem/snfg_icon/*.jpg' ) as $pn ) {
	$flg_icon[ basename( $pn, '.jpg' ) ] = true;
}

//. main loop
$done = [];
$no_icon = [];
$no_svg = [];
$icon_parent = [];
$no_gmml = [];
foreach ( _idloop( 'qinfo' ) as $fn ) {
	if ( _count( 'pdb', 0 ) ) _pause();
	$pdb_id = _fn2id( $fn );
	$ref = _json_load2( $fn )->ref;
	foreach ( (array)$ref as $c ) {
		list( $db, $desc, $ent_id ) = $c;
		$id = "$pdb_id-$ent_id";
		if ( $db != 'polysac' ) continue;
		if ( $done[ $desc ] ) continue;
		if ( _instr( $pdb_id, $desc ) )
			$no_gmml[] = $id;

		//.. comp
		$comp = [];
		foreach ( (array)_json_load2([ 'pdb_json', $pdb_id ])->pdbx_entity_branch_list as $c ) {
			if ( $c->entity_id != $ent_id ) continue;
			$cid = $c->comp_id;
			$comp[] = $cid;
			if ( ! $flg_icon[ $cid ] ) {
				$parent = _json_load2([ 'chem_json', $cid ])
					->chem_comp->mon_nstd_parent_comp_id;
				if ( $flg_icon[ $parent ] ) {
					$icon_parent[ $cid ] = $parent;
					$flg_icon[ $cid ] = true;
				} else {
					$no_icon[ $cid ][] = $id;
				}
			}
		}

		//.. svg
		$fn_img = _fn( 'snfg_img', $pdb_id, $ent_id );
		if ( ! file_exists( $fn_img ) ) {
			$no_svg[ $desc ][] = $id;
			continue;
		}
		$svg = file_get_contents( $fn_img );
		preg_match( '/width="([0-9]+)" height="([0-9]+)"/', $svg, $m );
		list( $dummy, $w, $h ) = $m;
		$w -= 40;
		$h -= 40;
		$vbox = _instr( 'viewBox', $svg ) ? '' :  " viewBox=\"20 20 $w $h\"";
		$svg = strtr( $svg, [
			' id="'		=> " id=\"$id-" ,
			' ID="'		=> " id=\"$id-" ,
			'#clipPath'	=> "#$id-clipPath" ,
			'<svg '		=> "<svg$vbox width=\"$w\" heigt=\"$h\" " ,
		]);

		//.. 書き込み
		$sqlite->set([ $desc, "$id", implode( '|', $comp ), $svg ]);
		$done[ $desc ] = true;
		unset( $no_svg[ $desc ] );
	}
}
$sqlite->end();
_end();

_kvtable( $no_icon, 'no icon comp' );
_kvtable( $no_svg,  'no svg polysac' );

_json_save( DN_PREP. '/chem/polysac_noimg.json.gz', [
	'no icon' => $no_icon ,
	'no svg' => $no_svg ,
	'no gmml' => $no_gmml ,
	'parent' => $icon_parent ,
]);
_tsv_save( DN_PREP. '/chem/icon_parent.tsv', $icon_parent );
