<?php
//- フィッティングの関係をまとめる
//. init
require_once( "commonlib.php" );

define( 'FN_DATA'     , DN_PREP. "/emn/fitdb.json.gz" );
define( 'FN_ANNOT'    , DN_DATA. "/emn/fit_annot.json.gz" );
define( 'FN_TSV'      , DN_EDIT. '/fit_annot.tsv' );
define( 'FN_CONFIRMED', DN_PREP. '/fit_confirmed.json.gz' );
define( 'FLG_DEL'	  , $argar[ 'delete' ] );

if ( FLG_REDO )
	_del( FN_DATA );

define( 'IDLIST_REPLACED', _json_load( DN_DATA. '/pdb/ids_replaced.json.gz' ) );
if ( count( IDLIST_REPLACED ) < 10 )
	die( "replacedデータがない" );

$dbdata = [];

//. emdb -> pdb
_line( 'EMDB情報から' );
foreach ( _idlist( 'emdb' ) as $emdb_id ) {
	_count( 1000 );

	//- json
	$pdbids = _json_load2([ 'emdb_old_json', $emdb_id ])->deposition->fittedPDBEntryId;
	foreach ( (array)$pdbids as $i ) {
		_set( $dbdata, $emdb_id, trim( strtolower( $i ) ) );
	}
}

//. pdb -> emdb
//- 'associated EM volume'
_count();
_line( 'PDB情報から' );
foreach ( _idlist( 'epdb' ) as $pdb_id ) {
	_count( 500 );
	foreach ( (array)_json_load2([ 'epdb_json', $pdb_id ])->pdbx_database_related as $c ) {
		if ( $c->content_type != 'associated EM volume' ) continue;
		$emdb_id = _numonly( $c->db_id );
		if ( $emdb_id == '0000' ) continue;
		_set( $dbdata, $emdb_id, $pdb_id );
	}
}

//. ym annotation
_line( 'ym annotation' );
$annot = [];
$type_name = [];
$current_categ = '-';
$confirmed = $dbdata;
$ignore_pair = [];
$del_line = [];

//.. main loop
foreach ( _tsv_load2( FN_TSV ) as $type => $c ) {
	$annot[ 'types' ][ $type ] = [
		'e' => $c['en'] ,
		'j' => $c['ja']
	];
	foreach ( $c as $key => $val ) {
		list( $emdb_id, $pdb_id ) = explode( '-', $key );
		if ( ! $pdb_id ) continue;
		$ignore_pair[ $key ] = true;
		$emdb_id = trim( $emdb_id );
		$pdb_id = trim( $pdb_id );
		$date = strtotime( $val );
		$msg = "$emdb_id: fit_annot.tsv ($key) - ";

		//... 取り消し
		if ( ! _inlist( $emdb_id, 'emdb' ) ) {
			_del_from_tsv( "$msg EMDB($emdb_id)エントリが取り消された" );
			continue;
		}
		if ( ! _inlist( $pdb_id, 'epdb' ) ) {
			_del_from_tsv( "$msg PDB($pdb_id)エントリがない" );
			continue;
		}

		//... 日付があって、元データより古い場合は処理しない
		if ( $date != '' ) {
			if ( $date < filemtime( _fn( 'mapgz', $emdb_id ) ) ) {
				_problem( "$msg 新しいマップが来てる" );
				continue;
			}
			if ( $date < filemtime( _fn( 'pdb_mmcif', $pdb_id ) ) ) {
				_problem( "$msg 新しいPDBが来てる" );
				continue;
			}
		}

		//... 元データと一致して、アノテーションが必要亡くなった？
		if ( $type == 'F' ) { //- fit
			if ( _known( $dbdata, $emdb_id, $pdb_id ) ) {
				_del_from_tsv( "$msg 元データに登録されたので不要になった" );
				continue;
			}
			_set( $confirmed, $emdb_id, $pdb_id );
		} else { //- unfit
			if ( !_known( $dbdata, $emdb_id, $pdb_id ) ) {
				_del_from_tsv( "$msg 元データから消されたので不要になった" );
				continue;
			}
			_unset( $confirmed, $emdb_id, $pdb_id );
		}
		_set( $annot[ $type ], $emdb_id, $pdb_id );
	}
}

//. tsvのいらない行を削除
if ( FLG_DEL ) {
	$out = [];
	foreach ( _file( FN_TSV ) as $line ) {
		foreach ( $del_line as $i ) {
			if ( ! _instr( $i, $line ) ) continue;
			$line = '';
			_log( "tsvからエントリ $i 削除" );
			break;
		}
		$out[] = $line;
	}
	_save( FN_TSV, array_filter( $out ) );
}

//. movinfo
_count();
_line( 'movinfo 検証' );
$emn_orig = [];
foreach ( _idlist( 'emdb' ) as $emdb_id ) {
	_count( 1000 );
	foreach ( (array)_json_load( _fn( 'movinfo', $emdb_id ) ) as $num => $a ) {
		if ( ! is_array( $a ) ) continue; 
		foreach ( (array)$a[ 'fittedpdb' ] as $pdb_id ) {
			if ( ! _inlist( $pdb_id, 'pdb' ) ) {
				_problem( "$emdb_id: mov-#$num $emdb_id-$pdb_id - 存在しないPDBのフィッティングムービー" );
			}
			if ( ! _inlist( $pdb_id, 'epdb' ) ) continue;
			if ( _known( $confirmed, $emdb_id, $pdb_id ) || _known( $dbdata, $emdb_id, $pdb_id ) ) {
				continue; //- 既知ペア
			}
			if ( $ignore_pair[ "$emdb_id-$pdb_id" ] ) continue;
			_problem( "$emdb_id: mov-#$num $emdb_id-$pdb_id - 未登録ペアのフィッティングムービー" );
		}
	}
}

//. まとめ
_reform( $dbdata );
_comp_save( FN_DATA, $dbdata );

_reform( $confirmed );
_comp_save( FN_CONFIRMED, $confirmed );

foreach ( $annot as $k => $v ) {
	if ( $k == 'types' ) continue;
	_reform( $annot[ $k ] );
}
_comp_save( FN_ANNOT, $annot );
_end();

//. function
//.. _set
function _set( &$data, $emdb_id, $pdb_id ) {
	$emdb_id = _emdbid( $emdb_id );
	$pdb_id = _pdbid( $pdb_id );
	$data[ $emdb_id ][ $pdb_id ] = true;
	$data[ $pdb_id ][ $emdb_id ] = true; 
}

//.. _unset
function _unset( &$data, $emdb_id, $pdb_id ) {
	$emdb_id = _emdbid( $emdb_id );
	$pdb_id = _pdbid( $pdb_id );
	unset( $data[ $emdb_id ][ $pdb_id ] );
	unset( $data[ $pdb_id ][ $emdb_id ] );
}

//.. _known
function _known( &$data, $emdb_id, $pdb_id ) {
	return $data[ _emdbid( $emdb_id ) ][ _pdbid( $pdb_id ) ];
}
//.. _reform
function _reform( &$data ) {
	foreach ( $data as $k => $v ) {
		$a = array_keys( $v );
		sort( $a );
		$data[ $k ] = $a;
	}
}

//.. _pdbid
function _pdbid( $i ) {
	$i = strtr( $i, [ 'pdb-' => '' ] );
	return 'pdb-'. ( IDLIST_REPLACED[ $i ][0] ?: $i );
}

//.. _emdbid
function _emdbid( $i ) {
	return _instr( '-', $i ) ? $i :"emdb-$i";
}


//.. _del_from_tsv
function _del_from_tsv( $str ) {
	 global $key, $del_line;
	 if ( FLG_DEL ){
	 	$del_line[] = $key;
	 } else {
	 	_problem( $str. ' (delete=1 オプションで自動削除)' );
	 }
}
