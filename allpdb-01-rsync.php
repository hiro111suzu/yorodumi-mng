rsyncでPDB各種ファイル取得
各種IDlist作成

<?php
require_once( "commonlib.php" );
define( 'RD', 'rsyncでダウンロード' );
define( 'LVH1_PDB', '/data/pdbj/data<>/ftp/pub/pdb/' );
define( 'LVH1_ALL', '/data/pdbj/data<>/ftp/pub/pdb/data/structures/all/' );
					 
define( 'DRY_RUN', false );

//. rsync
//.. PDBMLplus
_rsync([
	'title' => 'PDBMLplus' ,
	'from'	=> [ 'XML/pdbmlplus/pdbml_add/', 'lvh2' ] ,
	'to'	=> DN_FDATA. '/pdbmlplus' ,
	'dryrun' => DRY_RUN ,
]);

//.. PDBML 取得
_rsync([
	'title' => 'PDBML no-atom' ,
	'from'	=> [ LVH1_ALL. 'XML-noatom/', 'lvh1' ] ,
	'to'	=> DN_FDATA. '/pdbml_noatom/' ,
	'opt'	=> '-L' , //- シンボリックリンクの実体
	'dryrun' => DRY_RUN ,
]);

//.. PDB形式
//- dep
_rsync([
	'title' => 'PDB形式 登録構造' ,
	'from'	=> [ LVH1_ALL. 'pdb/', 'lvh1' ] ,
	'to'	=> DN_FDATA. '/pdb/dep/' ,
	'opt'	=> '-L' , //- シンボリックリンクの実体
	'dryrun' => DRY_RUN ,
]);

//- biounit
_rsync([
	'title' => 'PDB形式 集合体',
	'from'	=> [ LVH1_PDB. 'data/biounit/coordinates/all/', 'lvh1' ] ,
	'to'	=> DN_FDATA. '/pdb/asb/' ,
	'opt'	=> '-L' , //- シンボリックリンクの実体
	'dryrun' => DRY_RUN ,
]);

//.. mmcif形式
_rsync([
	'title' => 'mmCIF形式',
	'from'	=> [ LVH1_ALL. 'mmCIF/', 'lvh1' ] ,
	'to'	=> DN_FDATA. '/mmcif' ,
	'opt'	=> '-L' , //- シンボリックリンクの実体
	'dryrun' => DRY_RUN ,
]);

//- large_structures asb
_rsync([
	'title'	=> 'large biounit',
	'from'	=> [ LVH1_PDB. 'data/biounit/mmCIF/all/', 'lvh1' ],
	'to'	=> DN_FDATA. '/large_structures_asb/' ,
	'opt'	=> '-L', //- シンボリックリンクの実体
	'dryrun' => DRY_RUN ,
]);

//.. bundle
_rsync([
	'title'	=> 'bundle',
	'from'	=> [ LVH1_PDB. 'compatible/pdb_bundle/', 'lvh1' ],
	'to'	=> DN_FDATA. '/pdb_bundle/' ,
	'opt'	=> '-L',  //- シンボリックリンクの実体
	'dryrun' => DRY_RUN ,
]);

//.. status
_rsync([
	'title' => 'statusデータ csv' ,
	'from'	=> [ LVH1_PDB. 'derived_data/index/status_query.csv', 'lvh1' ],
	'to'	=> DN_FDATA ,
	'dryrun' => DRY_RUN ,
]);

_rsync([
	'title' => 'statusデータ 配列' ,
	'from'	=> [ LVH1_PDB. 'derived_data/index/status_query.seq', 'lvh1' ],
	'to'	=> DN_FDATA ,
	'dryrun' => DRY_RUN ,
]);

_rsync([
	'title' => 'obsoleteデータ dat' ,
	'from'	=> [ LVH1_PDB. 'data/status/obsolete.dat', 'lvh1' ],
	'to'	=> DN_FDATA ,
	'dryrun' => DRY_RUN ,
]);

//.. edmap list
_rsync([
	'title' => 'EDmap 情報',
	'from'	=> [ '/data/pdbj/edmap2/edmap.list', 'lvh1' ],
	'to'	=> DN_FDATA,
	'opt'	=> '-L', //- シンボリックリンクの実体
	'dryrun' => DRY_RUN ,
]);

//.. bird
_rsync([
	'title' => 'bird',
	'from'	=> [ LVH1_PDB. 'pdb/data/bird/', 'lvh1' ],
	'to'	=> DN_FDATA. '/bird',
//	'opt'	=> '-L' //- シンボリックリンクの実体
	'dryrun' => DRY_RUN ,
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
