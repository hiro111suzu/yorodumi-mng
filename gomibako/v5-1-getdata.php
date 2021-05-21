<?php
require_once( "v5-common.php" );

//. rsync
$dn = "nbdcpdbjf1.pdbj.org:/var/PDBj/ftp/v5-data/pub/pdb/data/structures/all";

//.. PDBML 取得
_rsync([
	'title' => 'V5 PDBML no-atom' ,
	'from'	=> "$dn/XML-noatom/",
	'to'	=> DN_V5XML . '/' ,
	'opt'	=> '-L' //- シンボリックリンクの実体
]);

//.. mmcif形式
_rsync([
	'title' => 'mmCIF形式',
	'from'	=> "$dn/mmCIF/",
	'to'	=> DN_V5CIF . '/' ,
	'opt'	=> '-L' //- シンボリックリンクの実体
]);

/*
//.. status
_rsync([
	'title' => 'statusデータ' ,
	'from'	=> 'nf1 | pdbj-pre/pub/pdb/derived_data/index/status_query.csv',
	'to'	=> DN_FDATA . "/"
]);
_rsync([
	'title' => 'status 配列' ,
	'from'	=> 'nf1 | pdbj-pre/pub/pdb/derived_data/index/status_query.seq',
	'to'	=> DN_FDATA . "/"
]);

//.. large_structures asb
_rsync([
	'title'	=> 'large biounit',
	'from'	=> 'nf1 | pdbj-pre/pub/pdb/data/biounit/mmCIF/all/' ,
	'to'	=> DN_FDATA . '/large_structures_asb/' ,
	'opt'	=> '-L' //- シンボリックリンクの実体
]);

//.. edmap list
_rsync([
	'title' => 'EDmap 情報',
	'from'	=> 'nf1 | pdbj-pre/edmap/edmap.list',
	'to'	=> DN_FDATA . '/' ,
	'opt'	=> '-L' //- シンボリックリンクの実体
]);
*/

//. 全IDリスト
_line( '全IDリスト作成' );

$all = '';
foreach ( _idloop( 'v5_xml' ) as $fn ) {
	$id = _fn2id( $fn );
	$all .= "$id\n";
	_cnt( 'total' );
/*
	if ( ! file_exists( _fn( 'pdb_pdb', $id ) ) ) {
		$large .= "$id\n";
		_cnt( 'large' );
	}
*/
}

_comp_save( DN_DATA . '/ids/v5_pdb.txt'	, $all );


//- edmap
copy(
	DN_FDATA . '/edmap.list' , 
	DN_DATA . '/ids/edmap.txt'
);
_cnt();

//. end
_end();
