rsyncでPDB各種ファイル取得
各種IDlist作成

<?php
require_once( "commonlib.php" );
define( 'RD', 'rsyncでダウンロード' );


//. rsync
//.. PDBMLplus
_rsync([
	'title' => 'PDBMLplus' ,
	'from'	=> [ 'XML/pdbmlplus/pdbml_add/' ],
	'to'	=> DN_FDATA. '/pdbmlplus'
]);

//.. PDBML 取得
_rsync([
	'title' => 'PDBML no-atom' ,
	'from'	=> [ 'XML/all-noatom/' ],
	'to'	=> DN_FDATA. '/pdbml_noatom/' ,
	'opt'	=> '-L' //- シンボリックリンクの実体
]);

//.. PDB形式
//- dep
_rsync([
	'title' => 'PDB形式 登録構造' ,
	'from'	=> [ 'pdb/' ] ,
	'to'	=> DN_FDATA. '/pdb/dep/' ,
	'opt'	=> '-L' //- シンボリックリンクの実体
]);

//- biounit
_rsync([
	'title' => 'PDB形式 集合体',
	'from'	=> [ 'pub/pdb/data/biounit/coordinates/all/' ],
	'to'	=> DN_FDATA. '/pdb/asb/' ,
	'opt'	=> '-L' //- シンボリックリンクの実体
]);

//.. mmcif形式
_rsync([
	'title' => 'mmCIF形式',
	'from'	=> [ 'mmcif/' ],
	'to'	=> DN_FDATA. '/mmcif' ,
	'opt'	=> '-L' //- シンボリックリンクの実体
]);

//.. status
_rsync([
	'title' => 'statusデータ csv' ,
	'from'	=> [ 'pub/pdb/derived_data/index/status_query.csv' ],
	'to'	=> DN_FDATA
]);
_rsync([
	'title' => 'statusデータ 配列' ,
	'from'	=> [ 'pub/pdb/derived_data/index/status_query.seq' ],
	'to'	=> DN_FDATA
]);
_rsync([
	'title' => 'obsoleteデータ dat' ,
	'from'	=> [ 'pub/pdb/data/status/obsolete.dat' ],
	'to'	=> DN_FDATA
]);



//.. large_structures asb
_rsync([
	'title'	=> 'large biounit',
	'from'	=> [ 'pub/pdb/data/biounit/mmCIF/all/' ],
	'to'	=> DN_FDATA. '/large_structures_asb/' ,
	'opt'	=> '-L' //- シンボリックリンクの実体
]);

//.. bundle
_rsync([
	'title'	=> 'bundle',
	'from'	=> [ 'pub/pdb/compatible/pdb_bundle/' ],
	'to'	=> DN_FDATA. '/pdb_bundle/' ,
	'opt'	=> '-L' //- シンボリックリンクの実体
]);

//.. edmap list
_rsync([
	'title' => 'EDmap 情報',
	'from'	=> [ 'edmap/edmap.list' ],
	'to'	=> DN_FDATA,
	'opt'	=> '-L' //- シンボリックリンクの実体
]);

//.. bird
_rsync([
	'title' => 'bird',
	'from'	=> [ 'pub/pdb/data/bird/' ],
	'to'	=> DN_FDATA. '/bird',
//	'opt'	=> '-L' //- シンボリックリンクの実体
]);


//. 全IDリスト
$large = $all = '';
foreach ( _idloop( 'pdbml_noatom', '全IDリスト作成' ) as $fn ) {
	$id = _fn2id( $fn );
	$all .= "$id\n";
	_cnt( 'total' );
	if ( ! file_exists( _fn( 'pdb_pdb', $id ) ) ) {
		$large .= "$id\n";
		_cnt( 'large' );
	}
}

_comp_save( DN_DATA. '/ids/pdb.txt'	 , $all );
_comp_save( DN_DATA. '/ids/large.txt', $large );

//. edmap list
copy(
	DN_FDATA. '/edmap.list' , 
	DN_DATA. '/ids/edmap.txt'
);
_cnt();

//. end
_end();
