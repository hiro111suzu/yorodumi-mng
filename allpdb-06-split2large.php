large-str IDの整理

<?php
include "commonlib.php";
$out = [];
//$outtx = '';

//. large ID リスト
$data = [];

foreach ( _idlist( 'large' ) as $id ) {
	_count( 1000 );
	$json = _json_load2( _fn( 'pdb_json', $id ) );
	$rel = [];
	foreach ( (array)$json->pdbx_database_related as $ar ) {
		if ( $ar->content_type != 'split' ) continue;
		$i = strtolower( $ar->db_id );
		if ( $i == $id ) continue;
//		if ( $data[ $i ] != '' )
//			_problem( "複数の候補 {$data[$i]}, $i => $id" );
		$data[ $i ] = $id;
		$rel[] = $i;
	}

//	_m( "$id: " . _imp( $rel ) );
}


//- 保存
_comp_save( DN_PREP . '/split2large.json', $data );

//. end
_end();
