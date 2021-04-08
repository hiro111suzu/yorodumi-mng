<?php
require_once( "commonlib.php" );

$ids = [];
$repids = [];
$related_prerel	= [];
$related_emdb	= [];
$pmid2pdbid		= [];
$chemid2pdbid	= [];
$taxo2pdbid		= [];

$cnt_taxo		= [];
$ribosome		= [];
$grp_dep		= [];

/*
	epdb
	nmrj
	helical
	icos
	ids_multic
	identasb

	ids_replaced

	pmid2pdbid
	pdbrel->emdb

chemids2pdbid
chemid count

taxo2id2pdbid(+emdbid)
taxo count

----
pdbdb?̂??黷ﾜ?ł̗??p
	epdb???X?g?擾
	nmr???X?g?擾

	pubmed?????竄ﾂ?擾
	related em?擾

	prerel related?擾
*/

//. main loop
foreach ( _idloop( 'qinfo' ) as $fn ) {
	_count( 5000 ); 
	$id = _fn2id( $fn );
	$json = _json_load( $fn );

	if ( $json[ 'method' ] == '' )
		_m( "$id: no method!!!", -1 );

	//- EM ? NMR ?
	foreach ( $json[ 'method' ] as $m ) {
		if ( in_array( $m, [ 'SOLID-STATE NMR', 'SOLUTION NMR' ] ) )
			_set( 'nmr', $id );
		if ( in_array( $m, [ 'ELECTRON CRYSTALLOGRAPHY', 'ELECTRON MICROSCOPY' ] ) )
			_set( 'epdb', $id );
	}

	//- helical icos
	if ( $json[ 'sym' ] == 'icos' )
		_set( 'icos', $id );

	if ( $json[ 'sym' ] == 'helical' )
		_set( 'helical', $id );

	//- multic
	if ( $json[ 'num_chain' ] > 1 )
		_set( 'multic', $id );

	//- multic
	if ( $json[ 'ribosome' ] )
		_set( 'ribosome', $id );

	//- identasb
	foreach ( (array)$json[ 'identasb' ] as $i ) {
		_set( 'identasb', "$id-$i" );
	}

	//- replaced
	foreach ( (array)$json[ 'repid' ] as $oldid ) {
		$repids[ $oldid ][] = $id;
	}
	
	//- related - prerel
	foreach ( (array)$json[ 'related' ][ 'PDB' ] as $i ) {
		if ( ! _inlist( $i, 'prerel' ) ) continue;
		$related_prerel[ $i ][] = $id;
	}

	//- related - emdb
	foreach ( (array)$json[ 'related' ][ 'EMDB' ] as $i )
		$related_emdb[ $i ][] = $id;

	//- pmid2pdbid
	if ( $json[ 'pmid' ] != '' ) {
		$pmid2pdbid[ $json[ 'pmid' ] ][] = $id;
	}
	
	//- chemid2pdbid
	foreach ( (array)$json[ 'chemid' ] as $c ) {
		if ( $c == 'HOH' ) continue;
		$chemid2pdbid[ $c ][] = $id;
	}
	foreach ( (array)$json[ 'ref' ] as $c ) {
		if ( $c[0] != 'BIRD' ) continue;
		$chemid2pdbid[ _numonly( $c[1] ) ][] = $id;
	}

	//- taxo2pdbid
	foreach ( (array)$json[ 'src' ] as $c ) {
		$taxo2pdbid[ $c ][] = $id;
	}
	
	//- group dep
	$g = $json[ 'grp_dep' ];
	if ( $g ) {
		if ( $grp_dep[ $g ][ 'num' ] < 10 )
			$grp_dep[ $g ][ 'ids' ][] = $id;
		++ $grp_dep[ $g ][ 'num' ];
	}
}

//. save
_save_list( 'epdb', 'pdbidlist', DN_DATA );
_save_list( 'epdb'		);
_save_list( 'ribosome'	);
_save_list( 'nmr'		);
_save_list( 'helical'	);
_save_list( 'icos'		);
_save_list( 'multic'	);
_save_list( 'identasb'	);

_comp_save( DN_DATA. '/pdb/ids_replaced.json.gz'	, $repids );

_comp_save( DN_DATA. '/pdb/prerel_related.json.gz'	, $related_prerel );
_comp_save( DN_DATA. '/chem/chemid2pdbid.json.gz'	, $chemid2pdbid );
_comp_save( DN_DATA. '/taxo/taxo2pdbid.json.gz'		, $taxo2pdbid );
_comp_save( DN_DATA. '/pdb/grp_dep.json.gz'			, $grp_dep );

_comp_save( DN_PREP. '/ids_related_pdb2emdb.json.gz', $related_emdb );
_comp_save( DN_PREP. '/pmid2pdbid.json.gz'			, $pmid2pdbid );


//. db
//.. pdbid_replaced
$o_sqlite = new cls_sqlw([
	'fn' => 'pdbid_replaced' , 
	'cols' => [
		'id UNIQUE COLLATE NOCASE' ,
		'rep COLLATE NOCASE' ,
	],
	'new' => true ,
	'indexcols' => [ 'id' ] ,
]);
foreach ( $repids as $from => $to ) {
	if ( _inlist( $from, 'pdb' ) ) continue; //- 何故か存在するIDがある
	$o_sqlite->set([ $from, implode( '|', _uniqfilt( $to ) ) ]);
}
$o_sqlite->end();

//. end
_end();

//. functions
function _set( $data, $id ) {
	global $ids;
	$ids[ $data ][ $id ] = 1;
}

function _save_list( $data, $fn = '', $dn = DN_DATA . '/ids' ) {
	global $ids;
	if ( $fn == '' )
		$fn = $data;
	_comp_save( "$dn/$fn.txt",
		implode( "\n", array_keys( $ids[ $data ] ) ) . "\n" );
}
