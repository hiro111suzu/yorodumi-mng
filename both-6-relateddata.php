relatedをまとめる
出力: data/emn/related.json

<?php
//. init
require_once( "commonlib.php" );
$ent = [];

$rep_ids = _json_load( DN_DATA. '/pdb/ids_replaced.json.gz' );
$rel_p2e = _json_load( DN_PREP. '/ids_related_pdb2emdb.json.gz' );
if ( count( $rel_p2e ) == 0 ) die();

//. emdb-emdb
_m( 'EMDB', 1 );
foreach( _idlist( 'emdb' ) as $id ) {
	_count( 'emdb' );
	foreach ( (array)$rel_p2e[ $id ] as $i ) {
		$ent[ "emdb-$id" ][] = "pdb-$i";
		$ent[ "pdb-$i" ][] = "emdb-$id";
	}
}

//. pdb-other
$otherdata = [];
//_m( 'PDB', 1 );
_count();
foreach( _idloop( 'epdb_json' ) as $fn ) {
	$id = _fn2id( $fn );
	_count( 'epdb' );

	foreach ( (array)_json_load2( $fn )->pdbx_database_related as $c ) {
		if ( $c == '' ) continue;
		$dbname = strtolower( substr( $c->db_name, 0, 3 ) );
		if ( $dbname == 'emd' ) {
			$i = _numonly( $c->db_id );
			if ( _inlist( $i, 'emdb' ) ) {
				$ent[ "pdb-$id" ][] = "emdb-$i";
				$ent[ "emdb-$i" ][] = "pdb-$id";
			}
		} else if ( $dbname == 'pdb' ) {
			$i = strtolower( $c->db_id );
			$i = substr( $rep_ids[ $i ][0], 0, 4 ) ?: $i ;
			$ent[ "pdb-$id" ][] = "pdb-$i";
			$ent[ "pdb-$i"  ][] = "pdb-$id";
		} else {
			$otherdata[ $c->db_name ][] = $id;
		}
	}
}
_kvtable( $otherdata, "その他のDB" );

//. 整頓・チェック

$newdata = [];
$fn_data = DN_PREP. '/emn/related.json';
$old_data = _json_load( $fn_data );

$idlist = [];
foreach ( _idlist( 'emdb' ) as $i )
	$idlist[] = "emdb-$i";
foreach ( _idlist( 'epdb' ) as $i )
	$idlist[] = "pdb-$i";

foreach ( $idlist as $i ) {
	$old = $old_data[ $i ];
	$new = '';

	//- データが無くても比較はするけど、データファイルには書き込まない
	if ( $ent[ $i ] ) {
		$new = array_unique( array_values( $ent[ $i ] ) );
		sort( $new );
		$newdata[ $i ] = $new;
	}

	if ( $old != $new )
		_log( "$i: " . _ar2str( $old ) . " => " . _ar2str( $new ) );
}


function _ar2str( $a ) {
	return is_array( $a )
		? implode( ', ', $a )
		: '[none]'
	;
}

_comp_save( $fn_data, $newdata );

//. end
_end();
