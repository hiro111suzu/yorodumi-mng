メインDB書き込み
----------
<?php
//. misc. init
require_once( "commonlib.php" );
define( 'FN_CATEG2DBID', DN_PREP. '/dbid/categ2dbid.json.gz' );
define( 'FN_DBID2CATEG', DN_PREP. '/dbid/dbid2categ.json.gz' );

//. main loop
define( 'CATEG_TSV', _tsv_load2( DN_EDIT. '/categ.tsv' ) );
$categ2dbid = [];
$dbid2categ = [];
$report = [];
$guess = [];
$ent_num = [];
/*
$cnt = [];
foreach ( [ 'emdb', 'pdb' ] as $db ) foreach ( CATEG_TSV[ $db ] as $cat ) {
	++ $cnt[ $cat ];
}
_die( $cnt );
*/

foreach ( [ 'calc', 'check' ] as $mode ) { 
//foreach ( [ 'check' ] as $mode ) { 
	//.. emdb
	_line( 'EMDB', $mode );
	_count();
	foreach ( _idlist( 'emdb' ) as $id ) {
		_count( 'emdb' );
		_do( 'emdb', $id, $mode );
	}

	//.. pdb
	_line( 'PDB', $mode );
	_count();
	foreach ( _idlist( 'epdb' ) as $id ) {
		_count( 'epdb' );
		_do( 'pdb', $id, $mode );
	}
	//.. 集計
	if ( $mode == 'calc' ) {
		foreach ( $dbid2categ as $dbid => $c ) foreach ( $c as $categ => $num ) {
			$dbid2categ[ $dbid ][ $categ ] = $num / $ent_num[ $categ ];
		}
		define( 'DBID2CATEG', $dbid2categ );
	}
}

//.. func _do
function _do( $db, $id, $mode ) {
	global $dbid2categ, $categ2dbid, $report, $guess, $ent_num;
	$dbids = _get_items( $db == 'emdb' ? "e$id" : $id );
	if ( ! $dbids ) return;
//	_pause( "$id: ". implode( ', ', $dbids ) );
	$categ = CATEG_TSV[ $db ][ $id ];
	if ( $mode == 'calc' ) {
		if ( ! $categ || $categ == '_' ) return;
		++ $ent_num[ $categ ];
		foreach ( $dbids as $dbid ) {
			++ $categ2dbid[ $categ ][ $dbid ];
			++ $dbid2categ[ $dbid ][ $categ ];
		}
	} else {
		$stat = _categ_stat( $dbids, 1 );
		if ( ! $stat ) return;
		if ( $categ == '_' ) {
			$guess[ "$db-$id" ] = $stat;
		} else {
			if ( $stat[ $categ ] ) {
				//- level
				_cnt2( 'total', 'lev' );
				if ( $stat[ $categ ] < 20 )
					_cnt2( '< 20', 'lev' );
				if ( $stat[ $categ ] < 10 )
					_cnt2( '< 10', 'lev' );
				if ( $stat[ $categ ] < 5 )
					_cnt2( '< 5', 'lev' );
/*
				if ( $stat[ $categ ] < 5 ) {
					$report[ "$db-$id" ] = [
						'cat' => $cat ,
						'rate' => $stat[ $cat ] ,
						'stat' => $stat
					];
				}
*/
				//- rank
				_cnt2( 'total', 'rank' );
				$rank = array_flip( array_keys( $stat ) )[ $categ ] + 1;
				_cnt2( "#rank", 'rank' );
				if ( 4 < $rank ) {
					$report[ "$db-$id" ] = [
						'categ' => $categ ,
						'rank' => $rank ,
						'stat' => $stat
					];
				}
			}
		}
	}
}
_cnt2();
_json_save( FN_CATEG2DBID, $categ2dbid );
_json_save( FN_DBID2CATEG, $dbid2categ );
_json_save( DN_PREP. '/categ_guess.json', $guess );
_json_save( DN_REPORT. '/categ_check.json', $report );

//.. str2items
function _get_items( $id ) {
	return array_filter( explode( '|', _ezsqlite([
		'dbname'	=> 'strid2dbids' ,
		'where'		=> [ 'strid', $id ] ,
		'select'	=> 'dbids'
	])));
}

