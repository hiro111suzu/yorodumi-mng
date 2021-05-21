<?php
require( __DIR__ . '/taxo-common.php' );

_add_unit([
	"aLength"			=> "A" ,
	"acceleratingVoltage" => "kV" ,
	"alpha"				=> "deg." ,
	"bLength"			=> "A" ,
	"beta"				=> "deg." ,
	"cLength"			=> "A" ,
	"cellA"				=> "A" ,
	"cellAlpha"			=> "deg." ,
	"cellB"				=> "A" ,
	"cellBeta"			=> "deg." ,
	"cellC"				=> "A" ,
	"cellGamma"			=> "deg." ,
	"deltaPhi"			=> "deg." ,
	"deltaZ"			=> "A" ,
	"diameter"			=> "A" ,
	"electronDose"		=> "e/A<sup>2</sup>" ,
	"energyWindow"		=> "eV" ,
	"gamma"				=> "deg." ,
	"molWtExp"			=> "MDa" ,
	"molWtTheo"			=> "MDa" ,
	"nominalCs"			=> "mm" ,
	"nominalDefocusMax"	=> "nm" ,
	"nominalDefocusMin"	=> "nm" ,
	"pixelX"			=> "A" ,
	"pixelY"			=> "A" ,
	"pixelZ"			=> "A" ,
	"samplingSize"		=> "microns" ,
	"specimenConc"		=> "mg/ml" ,
	"temperature"		=> "K" ,
	"temperatureMax"	=> "K" ,
	"temperatureMin"	=> "K" ,
	"tiltAngleMax"		=> "deg." ,
	"tiltAngleMin"		=> "deg." ,
	"tiltAngleIncrement" => "deg." ,
	"resolutionByAuthor" => "A" ,
	'humidity'			=> '%'
]);


_add_trep([
	'Last update'		=> '最新の更新' ,
	'Header (metadata) release' => 'ヘッダ(付随情報) 公開' ,
	'Map release'		=> 'マップ公開' ,
	'Map data'			=> 'マップデータ' ,
	'Projections & Slices' =>  '投影像・断面図' ,
	'Density'			=> '密度' ,
	'Mask'				=> 'マスク' ,
	'Details'			=> '詳細' ,
	'File'				=> 'ファイル', 
	'Supplemental map'	=> '添付マップデータ' ,

	'compDegree'		=> [ 'Oligomeric State', 'オリゴマーの状態' ] ,
	'numComponents'		=> '構成要素数' ,
	'sciName'			=> [ 'Name', '名称' ] ,
	'synName'			=> [ 'a.k.a', '別称' ] ,
	'numCopies'			=> [ 'Number of Copies', '個数' ] ,
	'oligomericDetails'	=> [ 'Oligomeric Details', 'オリゴマーの状態' ],
	'hostCategory'		=> '宿主の分類',
	'hostSpecies'		=> [ 'Host Species', '宿主' ],
	'natSource'			=> [ 'Source (natural)', '由来(天然)' ],
	'engSource'			=> [ 'Source (engineered)', '由来(合成)' ],
	'UniProt'			=> [ 'UniProt', 'UniProt' ] ,
	'InterPro'			=> [ 'InterPro', 'InterPro' ] ,
	'expSystem'			=> [ 'Expression System', '発現系' ] ,
	'recombinantExpFlag'	=> [ 'Recombinant expression', '組換発現' ],
	'organOrTissue'		=> '臓器・器官・組織' ,
	'cellLocation'		=> [ 'Location in cell', '細胞中の位置' ] ,
	'expSystemCell'		=> [ 'Cell of expression system', '発現系の細胞' ],
	'cell'				=> '細胞' ,
	
	'Supplemental images' => '添付画像' ,
	'Supplemental data'	=> '添付データ',
]);

$ftpdir = _url( 'ftpdir' );

define( 'MAP_EX'	, $main_id->ex_map() );
define( 'MOV_EX'	, $main_id->ex_mov() );
define( 'JMOL_EX'	, $main_id->ex_polygon() );
$ddn = DN_EMDB_MED . "/$id";

//. 追加データ
$movjson = $main_id->movjson(); //- ムービー情報ファイル
$mapjson = $main_id->mapjson(); //- マップ情報ファイル

//- fitdb
$j = _json_load( 'data/ids/fitdb.json.gz' );
$fitdb = $j[ "emdb-$id" ];
unset( $j );

//. basic
$met = _ej( "by $t", "{$t}による" );

//.. taxo
$a = [];
$syn = [];
foreach ( $json->sample->sampleComponent as $k1 => $v1 ) {
	if ( ! is_object( $v1 ) ) continue;
	foreach ( $v1 as $k2 => $v2 ) {
		$n = _nn( $v2->natSpeciesName, $v2->sciSpeciesName );
		if ( $n == '' ) continue;
		$a[ $n ] = true;
		if ( $v2->synSpeciesName != '' )
			$syn[ $n ][ $v2->synSpeciesName ] = true;
//		}
	}
}

$src = [];
foreach ( array_unique( (array)array_keys( (array)$a ) ) as $sp ) {
	$src[] = _taxoinfo( $sp, 
		_imp( array_unique( (array)array_keys( (array)$syn[ $sp ] ) ) ) );
}

//.. citation
//- primary

$jnl = $json->deposition->primaryReference->journalArticle;
$nj  = $json->deposition->primaryReference->nonJournalArticle;

//- primary
if ( _citation_pubmed( $main_id->add()->pmid ) ) {
	if ( $nj != '' )
		_nonjnl( $nj );
	else
		_citation_emdbjson( $jnl );
}

//- secondary
$cnt = 1;
foreach ( (array)$json->deposition->secondaryReference as $x ) {
	$jnl = $x->journalArticle;
	$nj  = $x->nonJournalArticle;

	if ( _citation_pubmed( $jnl->ref_pubmed . $nj->ref_pubmed, $cnt ) ) {
		if ( $nj != '' )
			_nonjnl( $nj, $cnt );
		else
			_citation_emdbjson( $jnl, $cnt );
	}
	++ $cnt;
}

//.. article 専用の関数
//- emdbxmlから読み込み
function _citation_emdbjson( $j, $cid = 'primary' ) {
	//- authors
	$auth = [];
	foreach ( explode( ',', (string)$j->authors ) as $s )
		$auth[] = _x( $s );

	_citation([
		'cid'	=> $cid ,
		'jnl'	=> $j->journal ,
		'year'	=> $j->year ,
		'vol'	=> $j->volume ,
		'doi' 	=> $j->ref_doi ,
		'title' => $j->articleTitle ,
		'auth'	=> $auth ,
		'pg_f'	=> $j->firstPage ,
		'pg_l'	=> $j->lastPage ,
		'pmid'	=> $j->ref_pubmed ,
	]);
}

//- nonjounarl
function _nonjnl( $j, $cid = 'primary' ) {
	//- authors
	$auth = [];
	foreach ( explode( ',', $j->authors ) as $s )
		$auth[] = _x( $s );
	_citation([
		'cid'	=> $cid ,
		'jnl'	=> _imp(
			$j->book ,
			$j->editor ,
			$j->publisher ,
			_ifnn( $j->publisherLocation, '(\1)' ) 
		) ,
		'year'	=> $j->year ,
		'vol'	=> $j->volume ,
		'doi' 	=> $j->ref_doi ,
		'title' => _imp( $j->thesisTitle, $j->chapterTitle ) ,
		'auth'	=> $auth ,
		'pg_f'	=> $j->firstPage ,
		'pg_l'	=> $j->lastPage ,
		'pmid'	=> $j->ref_pubmed ,
	]);
}

//.. output

$o_data->basicinfo([
//lev1title( 'Basic information' )
	'f_vis' => true ,
	'f_lnk' => true ,
	'cmd' => MOV_EX ? "_pmov.open('$did','')" : '',
])
->lev1ar([
//		. _links([ 'id' => $id, 'db' => $db, 'pages' => 'ym omos emn json' ])
//		. _pop( _icon_title( 'Donwload' ), $main_id->download() )
//	,
	'Title'		=> _drep( _x( $json->deposition->title ) ) ,
	'Keywords'	=> _keywords( $json->deposition->keywords ) ,
	'Sample'	=> _drep( _x( $json->sample->name ) ) . _hdiv_focus( 'sample' ),
	'Source'	=> implode( BR, $src ) ,
	'Map data'	=> _drep( _x( $json->map->annotationDetails ) ) . _hdiv_focus( 'map' ),

	'Method'	=> $_metname[ $main_id->smalldb()->met ]
		. _ifnn( $main_id->smalldb()->reso, _ej( ', at \1 A resolution', ', \1Å分解能' ) )
		. _hdiv_focus( 'experimental' )
	,
	'Authors'	=> _authlist( explode( '|', $main_id->add()->author ) ) ,
	'Citation'	=> _citation_out() ,
	'Date'		=> [
		'Deposition'	=> _datestr( $json->deposition->depositionDate ),
		'Header (metadata) release' => _datestr( $json->deposition->headerReleaseDate ),
		'Map release'	=> _datestr( $main_id->add()->rdate ),
		'Last update'	=> _datestr( $main_id->add()->ddate ),
	]
]);

//. visualization
//.. ムービー、ビューア
_viewer();

//.. sup-fig
//$sup_images = '';
$out = '';
//- imagesディレクトリの中
foreach ( glob( "$ddn/images/*.thumb.jpg" ) as $tfn ) {
	$ffn = strtr( $tfn, [ '.thumb.jpg' => '' ] ); //- フルサイズの画像
	$fn = basename( $ffn );
	$out .= _icap([
		'img' => $tfn ,  //- thumb
		'cap' => strlen( $fn ) < 15 ? $fn : substr( $fn, 0, 12 ) . '...'  ,
		'url' => _instr( '.tif', $fn ) ? "$ffn.jpg" : $ffn
	]); 
}

//- EMDBの画像
$f = DN_DATA . "/emdb/emdb-fig/$id.gif";
if ( $out == '' and file_exists( $f ) )
	$out = _icap([
		'url' => $f ,
		'img' => DN_DATA . "/emdb/emdb-fig/$id.jpg" ,
		'cap' => _ej( 'EMDB figure', 'EMDB登録画像' )  ,
	]);

//- 'other'の画像
foreach ( glob( "$ddn/other/*.thumb.jpg" ) as $tfn ) {
	$ffn = strtr( $tfn, [ '.thumb.jpg' => '' ] ); //- 元画像フルパス
	$fn = basename( $ffn ); //- 元画像ファイル名
	//- tif画像か、jpg画像か
	$out .= _icap([
		'url' =>_instr( '.tif', $fn ) ? "$ffn.jpg" : $ffn ,
		'img' => $tfn ,  //- thumb
		'cap' => ( strlen( $fn ) < 15 ) ? $fn : substr( $fn, 0, 12 ) . '...'
	]); 
}

$o_data->lev1( 'Supplemental images', $out );

//. ダウンロードとリンク
_add_trep([
	'FTP directory' => 'FTPディレクトリ',
]);

$o_data->lev1title( 'downlink', true );

//.. ダウンロード
// $_emdb_filenames;

$u = _url( 'ftpdir', $id );
$files = [
	$_emdb_filenames[ 'map' ] => '' ,
	$_emdb_filenames[ 'header' ] => '' ,
];
foreach ( _json_load2( _fn( 'emdb_med', $id ) . '/filelist.json' ) as $type => $c ) {
	 foreach ( $c as $a ) {
		$files[ $_emdb_filenames[ $type ]][] = _a(
			"$u/$type/{$a->name}" ,
			IC_DL . $a->name . ' (' . _format_bytes( $a->size ) . ')'
		);
	}
}
foreach ( $files as $type => $lnk ) {
	$o_data->lev2( $type, _imp2( $lnk ) );
}

$o_data->lev2( 'FTP directory', _a( $u, $u ) );
$o_data->end2( 'Download' );

//.. リンク
//	->lev3( '#notag',  )
//	->lev3( '#notag',  )
//... empiar
$jemp = _json_load2( DN_DATA . '/empiar.json.gz' );

$emp_link = [];
foreach ( (array)$jemp->$did as $ei ) {
	$emp_link[] = ''
		. _ab( "http://www.ebi.ac.uk/pdbe/emdb/empiar/entry/$ei/", IC_L . "EMPIAR-$ei" )
		. ' (' . _quick_kv( $jemp->$ei ) . ') ';
	;
}
$emp_link = implode( BR, $emp_link );

$o_data
	->lev2( 'Legacy pages', _imp2(
		_ab( _url( 'det', $id ), IC_EMN . _ej( 'EM Navigator legacy', '旧 EM Navigator' ) ) ,
		_ab( _url( 'ym', $id ), IC_YM . _ej( 'Yorodumi legacy', '旧 万見' ) )
	))
	->lev2( 'test', _test(
		_ab( _url( 'json', DID ), 'JSONview' )
	))
	->lev2( 'EMDB pages', _imp2([
		_ab( _url( 'em-ebi', $id ) , IC_L . 'PDBe' ) ,
		_ab( _url( 'em-rcsb', $id ) , IC_L . 'RCSB PDB' ),
	]))
	->lev2( 'EM raw data', $emp_link )
	->end2( 'Links' )
;

//.. 関連構造データ
_related_out();
//_similar();

//. map data
$o_data->lev1title( 'Map' );
$map = $json->map;

_add_trep([
	'Projections & Slices'	=> '投影像・断面図' ,
	'Density Histograms'	=> '密度ヒストグラム' ,
	'Voxel size'			=> 'ボクセルのサイズ' ,
	'Notes'					=> '注釈' ,
]);

//.. map status
$ar = [
	'HPUB' => _ej(
		'On hold (released when the article is published)' ,
		'未公開（論文掲載時に公開）'
	) ,
	'HOLD1' => _ej( 'On hold for 1 year'	, '公開保留（1年間）' ) ,
	'HOLD2' => _ej( 'On hold for 2 years'	, '公開保留（2年間）' ) ,
	'HOLD4' => _ej( 'On hold for 4 years'	, '公開保留（4年間）' ) 

];

$mapstatus = $ar[ _x( $json->deposition->status ) ];
//$_out .= _tr( 'Map data status', $mapstatus );

//.. file
$file = $map->file;
$o_data->lev1( 'File', MAP_EX
	? _ab( _url( 'dl-map' ), IC_DL .
		"{$file->name} ({$file->type} file in {$file->format} format, {$file->sizeKb} KB)"
	)
	: $mapstatus
);

//.. details
/*
$o = [];
foreach( [ 'details', 'annotationDetails' ] as $n ) {
	if ( $map->$n == "::::EMDATABANK.org::::EMD-$id::::" ) continue;
	$o[] = preg_replace( '/(\/\/| : )/', BR, $map->$n );
}
$o_data->lev1( 'Notes', $o );
*/
//.. projection / slices
if ( file_exists( "$ddn/mapi/proj0.jpg" ) ) {

	//... スライスjs用パラメータ
	$slicejs = 1; //- javascript 実行する

	//- 「長い」と判断されたかどうか (「長い構造」のスライスは大きさが違う)
	$x = $mapjson->NC;
	$y = $mapjson->NR;
	$z = $mapjson->NS;
	$a = [ $x, $y, $z ];
	rsort( $a );

	//- 長い構造？
	$long = ( $a[0] > $a[1] and $a[0] > $a[2] );

	//- 薄い？
	if ( $a[2] * 10 < $a[0] and $a[1] * 10 < $a[0] ) $long = 0;
	if ( $a[0] > 1000 ) $long = 0;

	if ( $long ) {
		$xr = $x / $a[0] / 6;
		$yr = $y / $a[0] / 6;
		$zr = $z / $a[0] / 6;
		$xa = [ 's' => 0, '_' =>  0, 'a' => 0.5 - $xr, 'b' => 0.5, 'c' => 0.5 + $xr ];
		$ya = [ 's' => 1, '_' =>  1, 'a' => 0.5 - $yr, 'b' => 0.5, 'c' => 0.5 + $yr ];
		$za = [ 's' => 0, '_' =>  0, 'a' => 0.5 - $zr, 'b' => 0.5, 'c' => 0.5 + $zr ];
		
		//- 0:横線 , 1:縦線
		$lv2r[0][ 'x' ] = $za;
		$lv2r[1][ 'x' ] = $ya;

		$lv2r[0][ 'y' ] = $za;
		$lv2r[1][ 'y' ] = $xa;

		$lv2r[0][ 'z' ] = $ya;
		$lv2r[1][ 'z' ] = $xa;
	} else {
		$lv2r[0][ 'x' ] = $lv2r[0][ 'y' ] =
		$lv2r[1][ 'y' ] = $lv2r[1][ 'z' ] =
			[ 's' => 0, '_' =>  0, 'a' => 1/3, 'b'=> 0.5, 'c' => 2/3 ];
		$lv2r[1][ 'x' ] = $lv2r[0][ 'z' ] =
			[ 's' => 1, '_' =>  1, 'a' => 1/3, 'b'=> 0.5, 'c' => 2/3 ];
	}
	_jsvar([ 'lv2r' => $lv2r]);

	//- スライスラインを出力
	$s = '';
	foreach ( [ 
		'slc_za', 'slc_zb', 'slc_zc',
		'slc_ya', 'slc_yb', 'slc_yc',
		'slc_xa', 'slc_xb', 'slc_xc',
		'slc_z_', 'slc_y_', 'slc_x_',
		'slc_xs', 'slc_ys', 'slc_zs'
	] as $n ) {
		$s .= _div( "#l_$n | .l_slc" );
	}
	$_echo .= $s;

	_add_lang([
		'Surface' => '表面',
	]);

	//... output
	$sl = _ej( 'Slices', '断面' );
	$n2xyz = [ 1 => 'X', 2 => 'Y', 3 => 'Z' ];
	$xyz_s = $n2xyz[ $mapjson->MAPS ];
	$xyz_r = $n2xyz[ $mapjson->MAPR ];
	$xyz_c = $n2xyz[ $mapjson->MAPC ];
	
	$hidebtn = _xbtn( '_slcimg.hide(this)', 'style: display:none' );

	$spider_url = 'http://spider.wadsworth.org/';

	$o_data->lev1( 'Projections & slices', ''
		//- 拡大ボタン
		. _ej( 'Size of images: ', '画像のサイズ: '  )
		. _sizebtn( 'ss', "pssize | !_slcimg.size(100,this) | " . DISABLE )
		. _sizebtn( 'm',  "pssize | !_slcimg.size(200,this) " )
		. _sizebtn( 'll', "pssize | !_slcimg.size(300,this) " )

		//- テーブル
		. _t( 'table|#pstable', ''
			. TR
				. TH . _ej( 'Axes', '軸' )
				. TH . _img( 'img/ori-z.gif' ) . "$xyz_s (Sec.)"
				. TH . _img( 'img/ori-y.gif' ) . "$xyz_r (Row.)"
				. TH . _img( 'img/ori-x.gif' ) . "$xyz_c (Col.)"
			. _t( 'tr | .small', ''
				. TD
				. TD
					. $mapjson->NS . ' pix' . BR
					.round( $mapjson->{"APIX $xyz_s"}, 2 )  . ' A/pix' .BR
					. '= ' . rtrim( $mapjson->{"$xyz_s length"}, '0' ) . ' A'
				. TD
					. $mapjson->NR . ' pix' . BR
					. round( $mapjson->{"APIX $xyz_r"}, 2 )  . ' A/pix' .BR
					. '= ' . rtrim( $mapjson->{"$xyz_r length"}, '0' ) . ' A'
				. TD
					. $mapjson->NC . ' pix' . BR
					. round( $mapjson->{"APIX $xyz_c"}, 2 ) . ' A/pix' .BR
					. '= ' . rtrim( $mapjson->{"$xyz_c length"}, '0' ) . ' A'
			)
			. ( file_exists( "$ddn/mapi/surf_x.jpg" )
				? TR
					. TH . $hidebtn . _img( 'img/ori-sf.gif' ) . _p( _l( 'Surface' ) )
					. _pstdt( 'surf_z' )
					. _pstdt( 'surf_y' )
					. _pstdt( 'surf_x' )
				: '' 
			)
			. TR
				. TH . $hidebtn
					. _img( 'img/ori-pj.gif' ) . _p( _l( 'Projections', '投影像' ) )
				. _pstdt( 'proj0' )
				. _pstdt( 'proj2' )
				. _pstdt( 'proj3' )
			. TR
				. TH . $hidebtn . _img( 'img/slc-1.gif' ) . _p( "$sl (1/3)" )
				. _pstdt( 'slc_za' )
				. _pstdt( 'slc_ya' )
				. _pstdt( 'slc_xa' )
			. TR
				. TH . $hidebtn . _img( 'img/slc-2.gif' ) . _p( "$sl (1/2)" )
				. _pstdt( 'slc_zb' )
				. _pstdt( 'slc_yb' )
				. _pstdt( 'slc_xb' )
			. TR
				. TH . $hidebtn . _img( 'img/slc-3.gif' ) . _p( "$sl (2/3)" ) 
				. _pstdt( 'slc_zc' )
				. _pstdt( 'slc_yc' )
				. _pstdt( 'slc_xc' )
		)
		. _p( _ej(
			'Images are generated by ' . _ab( $spider_url, 'Spider package' ) . '.' ,
			'画像は ' . _ab( $spider_url, 'Spider' ) . 'により作成'
		) )

		//- 直行座標じゃないマップ
		. ( (
			$map->cell->cellAlpha != 90 or 
			$map->cell->cellBeta  != 90 or
			$map->cell->cellGamma != 90 or
			$map->pixelSpacing->pixelX != $map->pixelSpacing->pixelY or
			$map->pixelSpacing->pixelX != $map->pixelSpacing->pixelZ 
		)
			? _p( '.red', _ej( '(generated in cubic-lattice coordinate)' ,
					'(これらの図は立方格子座標系で作成されたものです)' ) )
			: ''
		)
	);
}
$css .= <<<EOF
//- プロジェクションとかのテーブル
#pstable, #pstable td, #pstable th {
	border: none; margin: 0; padding: 0; background: white; }
#pstable td, #pstable th {
	width: auto; height: auto; vertical-align: middle; text-align: center; }

//- スライスホバー
.slc { border: 2px solid transparent; }
.slc:hover { border: 2px solid #a00; }
.l_slc { display: none; position: absolute; border: 1px solid #a00;
	box-shadow: 0 0 3px #ffffff; z-index: 300; background: rgba(255,255,255,0.2); }
EOF;

//.. voxel size
//- xml info
$xx = round( $map->pixelSpacing->pixelX , 5 );
$xy = round( $map->pixelSpacing->pixelY , 5 );
$xz = round( $map->pixelSpacing->pixelZ , 5 );

//- ccp4 header info
if ( $mapjson != '' ) {
	$hx =round( $mapjson->{'APIX X'} , 5 );
	$hy =round( $mapjson->{'APIX Y'} , 5 );
	$hz =round( $mapjson->{'APIX Z'} , 5 );
}

//- Movie info
if ( $movjson !='' ) {
	$mx = _ifnn( round( $movjson->{1}->{'apix x'}, 5 ), '\1', $hx );
	$my = _ifnn( round( $movjson->{1}->{'apix y'}, 5 ), '\1', $hy );
	$mz = _ifnn( round( $movjson->{1}->{'apix z'}, 5 ), '\1', $hz );
}

function _near( $a, $b ) {
	if ( $a == '' || $b == '' ) return true;
	if ( max( [ $a/$b, $b/$a ] ) < 1.1 ) return true;
}

//- 一致？
$o_data->lev1( 'Voxel size', (
	_near( $xx, $hx ) and _near( $xx, $mx ) and
	_near( $xy, $hy ) and _near( $xy, $my ) and
	_near( $xz, $hz ) and _near( $xz, $mz )
)
	//- だいたい同じ
	? ( ( $xx == $xy and $xx == $xz )
		? [ 'X=Y=Z' => "$xx A" ]
		: [ 'X' => "$xx A", 'Y' => "$xy A", 'Z' => "$xz A" ]
	)

	//- 無視できない違い
	: _t( 'table | class:itable | st:color:red', ''
		. TR_TOP . TH . '' . TH. 'X' . TH . 'Y' . TH .'Z'
		. TRTH. _ej( 'EMDB info.', 'EMDB情報' ) .TD. $xx .TD. $xy .TD. $xz
		. TRTH. _ej( 'CCP4 map header', 'CCP4マップ ヘッダ情報' ) .TD. $hx .TD. $hy .TD. $hz
		. ( $mx == '' ? ''
			: TRTH. _ej( 'EM Navigator Movie #1', 'EM Navigator ムービー #1' )
				.TD. $mx .TD. $my .TD. $mz
		)
	)
);

//.. density
$out = '';
//- 表面
$a = [];
$by = $map->contourLevel_source;
$s = _ifnn( (string)$map->contourLevel , '\1' ) . _ifnn( $by, ' (by \1)' );
if ( $s != '' ) 
	$a[] = _span( 'style: color:' . ( $by == 'author' ) ? '#00d' : '#0a0' , $s );

$s = ( $movjson != '' and $movjson->{1}->mode != 'solid' )
	? round( $movjson->{1}->threshold, 7 ) 
		. _ej( ' (movie #1):', ' (ムービー #1)' )
	: ''
;
if ( $s != '' )
	$a[] = _span( "style: color:#d00", $s );

$surf = implode( ', ', $a );

$d = "$ddn/mapi";
$ms = $map->statistics;

$o_data->lev1( 'Density',

//- ヒストグラム
( file_exists( "$d/hists.png" )
	? ''
		. _icap([
			'url' => "$d/hist.png" ,
			'img' => "$d/hists.png" ,
			'cap' =>_ej( 'Histogram', 'ヒストグラム' ) ,
		])
		. _icap([
			'url' => "$d/histlog.png" ,
			'img' => "$d/histlogs.png" ,
			'cap' => _ej( 'Histogram (log scale)', 'ヒストグラム (対数)' ) ,
		])
	: ''
)

//- 表面レベルの表
. _table_2col([
	_ej( 'Contour Level:', '表面のレベル:' )
		=> $surf ,
	_ej( 'Minimum - Maximum', '最小 - 最大' )
		=> $ms->minimum .' - '. $ms->maximum ,
	_ej( 'Average (Standard dev.)', '平均 (標準偏差)' )
		=> $ms->average .' (' . ( $ms->std == 0 ? '-' : $ms->std ) . ')'
	],
	[ 'opt' => '.clboth' ]
) );

//.. details

//... space group
$out =  _thtd( _ej( 'Space Group Number', '空間群番号' ) , $map->spaceGroupNumber  );

//... ジオメトリ
$t = TRTH . _l( 'Axis order' )
	.TD. $map->axisOrder->axisOrderFast
	.TD. $map->axisOrder->axisOrderMedium 
	.TD. $map->axisOrder->axisOrderSlow
;

foreach( [ 'dimensions', 'origin', 'limit', 'spacing' ] as $n ) {
	$r = $c = $s = '';
	foreach ( (array)$map->$n as $k => $v ) {
		if ( _instr( 'row', $k ) ) $r = $v;
		if ( _instr( 'col', $k ) ) $c = $v;
		if ( _instr( 'sec', $k ) ) $s = $v;
	}
	$t .= TRTH . _trep( $n ) .TD. $r .TD. $c .TD. $s;
}
$out .= TH .  _ej( 'Map Geometry', 'マップ形状' ). TD . _t( 'table', $t );

//... cell

$a = $map->cell->cellA;
$b = $map->cell->cellB;
$c = $map->cell->cellC;
$d = $map->cell->cellAlpha;
$e = $map->cell->cellBeta;
$f = $map->cell->cellGamma;

$out .= _thtd( _ej( 'Cell', 'セル'  ), ''
	. ( ( $a == $b and $b == $c )
		? _kv([ 'A=B=C' => "$a A" ])
		: _kv([
			'A' => "$a A" ,
			'B' => "$b A" ,
			'C' => "$c A"
		])
	)
	. BR
	. ( ( $d == $e and $e == $f )
		? _kv([ 'alpha=beta=gamma' => "$f deg." ])
		: _kv([
			'alpha' => "$d deg." ,
			'beta'  => "$e deg." ,
			'gamma' => "$f deg."
		])
	)
);
$out = _p( '.bld', 'EMDB XML:' ) . _t( 'table', $out );

//... ccp4 header info
if ( $movjson != '' and $main_id->smalldb()->mov1 ) {
	$a = [ 
		0 => 'envelope stored as signed bytes (from -128 lowest to 127 highest)' ,
		1 => 'Image stored as Integer*27' ,
		2 => 'Image stored as Reals' ,
		3 => 'Transform stored as Complex Integer*2' ,
		4 => 'Transform stored as Complex Reals'
	];

	$s = TRTH. 'mode<td colspan=3>' . $a[ $mapjson->MODE ];
	foreach ( [
		'A/pix X/Y/Z'		=> 'APIX X/APIX Y/APIX Z',
		'M x/y/z'			=> 'MX/MY/MZ',
		'origin x/y/z'		=> 'XORIGIN/YORIGIN/ZORIGIN',
		'length x/y/z'		=> 'X length/Y length/Z length',
		'alpha/beta/gamma'	=>'Alpha/Beta/Gamma',
		'start NX/NY/NZ'	=>'NXSTART/NYSTART/NZSTART',
		'NX/NY/NZ'			=> 'NX/NY/NZ',
		'MAP C/R/S'			=> 'MAPC/MAPR/MAPS',
		'start NC/NR/NS'	=> 'NCSTART/NRSTART/NSSTART',
		'NC/NR/NS'			=> 'NC/NR/NS',
//		'start NC,NX/NR,NY/NS,NZ' => 'NCSTART, NXSTART/NRSTART, NYSTART/NSSTART, NSSTART',
//		'NC,NX/NR,NY/NS,NZ'	=> 'NC,NX/NR,NY/NS,NZ',
		'D min/max/mean'	=> 'DMIN/DMAX/DMEAN'
	] as $t => $n ) {
		$a = [];
		foreach ( explode( '/', $n ) as $n1 )
			$a[] = $mapjson->$n1;
		$s .= TRTH. $t .TD. implode( TD, $a );
	}
	$out .= _p( '.bld', _ej( 'CCP4 map header', 'CCP4マップヘッダ' ) . ': ' )
		. _t( 'table', $s )
	;
}

$o_data->lev1( 'Details', _more( $out ) );

//. Supplement
$o_data->lev1title( 'Supplemental data', true );
$_out = '';

//.. mask
//- ファイル
$mskjson = _json_load( "$ddn/masks/list.json" );
$addnum = ( $mskjson[ 0 ] == '' and $mskjson[ 1 ] != '' ) ? 1 : 0;

$done = [];
$i = 0;
foreach ( (array)$json->supplement->mask as $c1 ) {
	$f = $c1->file;
	$o_data->lev2( 'File' ,
		_a( "$ftpdir/masks/{$f->name}", IC_DL
			. "{$f->name} ( {$f->type} file in {$f->format} format, {$f->sizeKb} KB )"
		)
	);

	//- 画像
	if ( $mskjson[ $i + $addnum ] != '' )
		_prjslc( "$ddn/masks/" . ( $i + $addnum ) );

	//- 文字情報
	foreach( $c1 as $n2=>$c2 ) {
		if ( $n2 == 'file' ) continue;

		//- 文字列
		if ( ! is_object( $c2 ) ) {
			$o_data->lev2( $n2, $c2 );
			continue;
		}
	}
	$done[ $i ] = 1;
	$i ++;
	$o_data->end2( "Mask|#$i" );
}

//- 書いていないのがあれば追加
foreach ( (array)$mskjson as $i => $fn ) {
	if ( $done[ $i ] ) continue;
	$o_data->lev2( 'File', _a( "$ftpdir/masks/$fn", IC_DL . $fn ) );
	_prjslc( "$ddn/masks/$i" );
	$o_data->end2( "Mask|#$i" );
}

//.. figure
//- 今のところ、EMDB-XML情報には、ファイル名しか書いてない、間違っているのも多い
//- なので使わない、実際にファイルがあるモノのみ、そのファイルを表示

//.. images
//if ( $sup_images != '' )
//	$_out .= _index( 2, 'Images' ) . _tr( 'Images', $sup_images );

//.. fsc
//- EMDB-XMLのFSCタグに、ファイル名以外の情報を持つデータは、存在しないので
//if ( file_exists( "$ddn/fsc/fscs.jpg" ) ) {
//	$_out .= _index( 2, 'FSC' )
//		. _tr( 'FSC', _ab( "$ddn/fsc/fscl.png", _timg( "$ddn/fsc/fscs.jpg" ) ) );
//}

//.. other
$d = "$ddn/other";
$out = '';

if ( is_dir( $d ) ) {
	$scd = scandir( $d );

	//- map
	$o_map = [];
	foreach ( $scd as $fn ) {
		if ( is_dir( "$d/$fn" ) ) continue;
		if ( ! _instr( '.map', $fn ) and ! _instr( '.mrc', $fn ) ) continue;
		$o_map[ _l( 'Supplemental map' ) . ": $fn" ] = [
			'f' => _a( "$ftpdir/other/$fn.gz", $fn ) ,
			'd' => "$d/$fn.d"
		];
	}
	foreach ( (array)$o_map as $n => $a ) {
		$o_data->lev2( 'File', $a[ 'f' ] );
		_prjslc( $a[ 'd' ] );
		$o_data->end2( $n );
	}

	//- info
	if ( file_exists( "$d/info.txt" ) ) {
		$o_data->lev2( 'Details',
			preg_replace( '/[\n\r]+/', BR, file_get_contents( "$d/info.txt" ) ) 
		);
	}

	//- img
	foreach ( $scd as $fn ) {
		if ( ! _instr( 'thumb.jpg', $fn ) ) continue;
		//- tif画像か、jpg画像か
		$n = strtr( $fn, [ '.thumb.jpg' => '' ] );
		$l = ( substr( $n, -3 ) == 'tif' )
			? "$d/$n.jpg" : "$ftpdir/other/$n" ;
		$o_data->lev2( 'Image', _icap([
			'img' => "$d/$fn" ,
			'url' => $l ,
			'cap' => "File: $n"
		]));
	}

	//- その他の型式
	if ( file_exists( "$d/other.json" ) ) {
		foreach( _json_load( "$d/other.json" ) as $fn => $sz ) {
			$sz = _format_bytes( $sz );
			$o_data->lev2( 'File', _a( "$ftpdir/other/$fn" , "$fn ($sz)" ) );
		}
	}
	$o_data->end2( 'Others' );
}

//.. _prjslc(): プロジェクションと断面とヒストグラムを表示（サプリマップ用）
function _prjslc( $dn ) {
	global $o_data;
	$sl = _ej( 'Slices', '断面' );
	$o_data->lev2( 'Projections & Slices',
		_t( 'table|#pstable', ''
			. TR
				. TH . _ej( 'Axes', '軸' )
				. TH . _img( 'img/ori-z.gif' ) . 'Z' 
				. TH . _img( 'img/ori-y.gif' ) . 'Y' 
				. TH . _img( 'img/ori-x.gif' ) . 'X'
			. TR
				. TH . _img( 'img/ori-pj.gif' ) . _ej( 'Projections', '投影像' )
				. _pstdt( 'proj0', $dn )
				. _pstdt( 'proj2', $dn )
				. _pstdt( 'proj3', $dn )
			. TR
				. TH . _img( 'img/slc-2.gif' ) . "$sl (1/2)"
				. _pstdt( 'slc_zb', $dn )
				. _pstdt( 'slc_yb', $dn )
				. _pstdt( 'slc_xb', $dn )
		)
	)->lev2( 'Density Histograms', ''
		. _icap([
			'url' => "$dn/hist.png" ,
			'img' => "$dn/hists.png" ,
			'cap' =>_ej( 'Histogram', 'ヒストグラム' ) 
		])
		. _icap([
			'url' => "$dn/histlog.png",
			'img' => "$dn/histlogs.png",
			'cap' => _ej( 'Histogram (log scale)', 'ヒストグラム (対数)' )
		])
	);
}

//. Sample
$o_data->lev1title( 'Sample components', true );
_add_trep([
	'expSystemStrain'	=> [ 'Strain'		, '株' ] ,
	'refUniProt'		=> [ 'UniProt'		, 'UniProt' ] ,
	'refInterpro'		=> [ 'InterPro'		, 'InterPro' ] ,
	'refGo'				=> [ 'Gene Ontology', '遺伝子オントロジー' ] ,
	'molWtTheo'			=> [ 'Theoretical'	, '理論値' ] ,
	'molWtExp'			=> [ 'Experimental'	, '実験値' ] ,
	'molWtMethod'		=> [ 'Measured by'	, '測定法' ] ,
	'timeResolvedState' => '時間分割' ,
	'syntheticFlag'		=> '合成', 
	'tNumber'			=> [ 'T number(triangulation number)', 'T番号(三角分割数)'] ,
	'Sample components' => '試料の構成要素' ,
//	'Source species'	=> '由来生物種' ,
	'Serotype'			=> '血清型' ,
	'Strain'			=> '株' ,
	'hostSpeciesStrain'	=> '宿主株',
	'Entire'			=> '全体' ,
	'externalReferences' => '外部リンク' ,
	'External references' => '外部リンク' ,
	'Mass'				=> '分子量' ,
	'nameElement'		=> [ 'Name of element', '要素名' ] ,
]);

$order = [
	'name' ,
	'Name' , //-?
	'sciName' ,
	'synName' ,
	'class' ,
	'mutantFlag' ,
	'oligomericDetails' ,
	'numberOfCopies' ,
	'protein' ,
	'details' ,
	'structure' ,
	
	'host' ,
	'hostSpecies',
	'hostCategory',
	'hostSpeciesStrain',

	'sciSpeciesName' ,
	'synSpeciesName' ,
	'expSystem'  ,
	'sciSpeciesStrain' ,
	'organelle' ,
	'Tissue' ,
	'cellLocation' ,
	'cell' ,
	'mutant' ,
	'vector' ,
];

//.. sample全体
$o_data->lev3order( $order )
	->lev3ign([ 'sampleComponent', 'molWtTheo', 'molWtExp', 'molWtMethod' ]);
foreach ( $json->sample as $k => $v )
	$o_data->lev3( $k, $v );
$o_data->end3( 'Entire' )
->lev2( 'Mass', _mol_wt( $json->sample ) )
->end2( 'Entire|' . _span( '.h_addstr', _short( $json->sample->name ) ) );

//.. sample 各要素

//- 第3階層があるデータは以下のみ
$_keys_lev3 = [ 'engSource', 'natSource', 'shell', 'externalReferences' ];


foreach ( $json->sample->sampleComponent as $c1 ) {
	$flg_virus = $c1->entry == 'virus';
	$src = '';

	$o_data->lev3order( $order )
		->lev3ign([ 'entry', 'componentID', 'ncbiTaxId', 'sciSpeciesName', 'synSpeciesName',
			'sciSpeciesSerotype', 'sciSpeciesStrain', 'molWtTheo', 'molWtExp', 'molWtMethod'
		])
	;
	$ent = $c1->{ $c1->entry };

	//... 一般
	foreach ( $c1 as $n2=>$c2 ) {
		//- 文字列ならそのまま入れる
		if ( ! is_object( $c2 ) ) {
			$o_data->lev3( $n2, $c2 );

		//- オブジェクト (Protein とか ribosomeとかなので、そのままのレベルで処理)
		} else foreach ( $c2 as $n3=>$c3 ) {
			//- level3がないタグのみ
			if ( in_array( $n3, $_keys_lev3 ) ) continue;
			$o_data->lev3( $n3, $c3 );
		}
	}
	$o_data->end3( $c1->entry )
	->lev2( 'Mass', _mol_wt( $c1 ) )
	->lev3order( $order );
		
	//... 種名
	//- ウイルスなら、種名が名称
	if ( $flg_virus ) {
		$o_data->lev3( 'Species' ,
				_taxoinfo( _nn( $ent->sciSpeciesName, $ent->name ), $ent->synSpeciesName ) )
			->lev3( 'Strain', $ent->sciSpeciesStrain )
			->lev3( 'Serotype',  $ent->sciSpeciesSerotype )
			->end3( 'Species' )
		;
	} else {
		$o_data
			->lev3( 'Species', _taxoinfo( $ent->sciSpeciesName, $ent->synSpeciesName ) )
			->lev3( 'Strain', $ent->sciSpeciesStrain )
			->lev3( 'Serotype',  $ent->sciSpeciesSerotype )
			->end3( 'Source' )
		;
	}

	//... source 3種
	//- engSource
	$o_data->lev3ign([ 'ncbiTaxId' ])->lev3order( $order );
	foreach ( (array)$ent->engSource as $n4 => $c4 ) {
		if ( in_array( $n4, [ 'expSystem', 'hostSpecies' ] ) )
			$c4 = _taxoinfo( $c4 );
		$o_data->lev3( $n4, $c4 );
	}
	$o_data->end3( 'engSource' );

	//- natSource
	$o_data->lev3ign([ 'ncbiTaxId' ])->lev3order( $order );
	foreach ( (array)$ent->natSource as $n4 => $c4 ) {
		if ( in_array( $n4, [ 'expSystem', 'hostSpecies' ] ) )
			$c4 = _taxoinfo( $c4 );
		$o_data->lev3( $n4, $c4 );
	}
	$o_data->end3( 'natSource' );

	//- hostSpecies
	$o_data->lev3ign([ 'ncbiTaxId' ])->lev3order( $order );
	foreach ( (array)$ent->hostSpecies as $n4 => $c4 ) {
		if ( in_array( $n4, [ 'expSystem', 'hostSpecies' ] ) )
			$c4 = _taxoinfo( $c4 );
		$o_data->lev3( $n4, $c4 );
	}
	$o_data->end3( 'hostSpecies' );

	//... shell
	foreach ( (array)$ent->shell as $n4 => $c4 ) {
		if ( $c4->nameElement != '' )
		$o_data
			->lev3( 'nameElement', $c4->nameElement )
			->lev3( 'Diameter', _ifnn( $c4->diameter, '\1 A' ) )
			->lev3( 'tNumber', $c4->tNumber )
			->end3( 'Shell|#' . $c4->id )
		;
	}

	//... ext ref
	foreach ( (array)$ent->externalReferences as $n4 => $c4 )
		$o_data->lev3( $n4, _emdb_dblink( $n4, $c4 ) );
	$o_data->end3( 'External references' );

	$o_data->end2( 'component|' . '#' . $c1->componentID
		. ': ' . _span( '.h_addstr', _short( _imp(
			_l( $c1->entry ), _l( $c1->sciName )
		)))
	);
}

//. Experiment
$o_data->lev1title( 'Experimental details', true, true );

//.. sample preparation / Vitrification
$jprep = $json->experiment->specimenPreparation;
_add_trep([
	'specimenConc'				=> [ 'Specimen conc.', '試料濃度' ],
	'specimenState'				=> '試料の状態' ,
	'specimenSupportDetails'	=> [ 'Support film', '支持膜' ] ,
	'cryogenName'				=> '凍結剤' ,

	'aLength'					=> [ 'A', 'A' ] ,
	'bLength'					=> [ 'B', 'B' ] ,
	'cLength'					=> [ 'C', 'C' ] ,
	'helicalParameters'			=> 'らせんパラメータ' ,
	'twoDCrystalParameters'		=> [ 'Crystal parameters', '結晶パラメータ' ] ,
	'threeDCrystalParameters'	=> [ 'Crystal parameters', '結晶パラメータ' ] ,
	'planeGroup'				=> '面群' ,
	'spaceGroup'				=> '空間群' ,
	'axialSymmetry'				=> '軸対称性' ,
	'crystalGrowDetails'		=> '結晶化の詳細' ,

	'Crystal parameters'		=> '結晶パラメータ' ,
	'Sample solution'			=> '試料溶液' ,
	'Buffer solution'			=> '緩衝液' ,
]);

//- 状態
$s = $jprep->specimenState;
if ( $jprep->specimenState != '' ) {
	$a = _ej([
		'twoDArray'		=> '2D array',
		'threeDArray'	=> '3D array',
		'helicalArray'	=> 'helical array',
	],[
		'particle'		=> '粒子',
		'filament'		=> '線維',
		'twoDArray'		=> '2次元配列',
		'threeDArray'	=> '3次元配列',
		'helicalArray'	=> 'らせん状配列',
		'tissue'		=> '組織',
		'cell'			=> '細胞',
	]);
	$o_data->lev2( 'specimenState', _nn( $a[ $s ], $s ) );
}

//- らせんパラメータ
$o_data->lev3order([ 'axialSymmetry', 'hand', 'deltaZ', 'deltaPhi' ]);
foreach ( (array)$jprep->helicalParameters as $k => $v ) {
	if ( $k == 'axialSymmetry' )
		$v = _symstr( $v );
	$o_data->lev3( $k, $v );
}
$o_data->end3( 'helicalParameters' );

//- 結晶パラメータ
$o_data->lev3order([
	'planeGroup', 'spaceGroup', 'aLength', 'bLength', 'cLength', 'alpha', 'beta', 'gamma' 
]);
foreach ( (array)$jprep->twoDCrystalParameters as $k => $v ) {
	$o_data->lev3( $k, $v );
}
foreach ( (array)$jprep->threeDCrystalParameters as $k => $v ) {
	$o_data->lev3( $k, $v );
}
$o_data->end3( 'Crystal parameters' );

//- 結晶化法
$o_data->lev2( 'crystalGrowDetails', $jprep->crystalGrowDetails );

//- sample solution
$o_data
	->lev3( 'specimenConc'		, $jprep->specimenConc )
	->lev3( 'Buffer solution'	, $jprep->buffer->details )
	->lev3( 'ph'				, $jprep->buffer->ph )
	->end3( 'Sample solution' );
;

//- その他exp:
foreach ( [ 'specimenSupportDetails', 'staining' ] as $k ) {
	$o_data->lev2( $k, $jprep->$k );
}

//- vitrification
$multi = count( $vit = $json->experiment->vitrification ) > 1;
$num = 1;
foreach ( (array)$json->experiment->vitrification as $n1 => $c1 ) {

//	$s = implode( (array)$c1 );
//	if ( $s == 'NONE' || $s == 'NONENONE' ) continue;

	$o_data->lev3order(
		[ 'instrument', 'cryogenName', 'temperature', 'humidity', 'method' ]
	);
	foreach ( $c1 as $k => $v ) {
		$o_data->lev3( $k, $v );
	}
	$o_data->end3( 'Vitrification' . ( $multi ? "|#$num" : '' ) );
	++ $num;
}
$o_data->end2( 'Sample preparation' );

//.. Imaging
_add_trep([
	'Electron gun'			=> '電子銃' ,
	'electronSource'		=> '電子線源' ,
	'acceleratingVoltage'	=> '加速電圧' ,
	'electronDose'			=> '照射量' ,
	'illuminationMode'		=> '照射モード' ,
	'nominalCs'				=> [ 'Cs', 'Cs' ],
	'imagingMode'			=> '撮影モード' ,
	'energyFilter'			=> 'エネルギーフィルター' ,
	'energyWindow'			=> 'スリット' ,

	'Specimen Holder'		=> '試料ホルダ' ,
	'Tilt angle'			=> '傾斜度' ,
	'3D reconstruction'		=> '3次元再構成' ,
]);

$multi = count( $json->experiment->imaging ) > 1;
$num = 1;
foreach ( $json->experiment->imaging as $n1 => $o_img ) {

	//... EM
	$o_data
		->lev3( 'microscope', $o_img->microscope )
		->lev3( 'Date'		, $o_img->date )
		->lev3( 'details'	, $o_img->details )
		->end3( 'Imaging' )
	;


	//... gun
	foreach ( [ 'electronSource', 'acceleratingVoltage', 'electronDose',
				'electronBeamTiltParams', 'illuminationMode' ] as $n )
		$o_data->lev3( $n, $o_img->$n );
	$o_data->end3( 'Electron gun' );
	
	//... lens
	$o_data->lev3( 'Magnification', _imp( 
		_ifnn( $o_img->nominalMagnification, '\1 X ('. _l( 'nominal' ) . ')' ),
		_ifnn( $o_img->calibratedMagnification, '\1 X ('. _l( 'calibrated' ) . ')' )
	));

	foreach( [ 'astigmatism', 'nominalCs', 'imagingMode' ] as $n )
		$o_data->lev3( $n, $o_img->$n );

	$min = $o_img->nominalDefocusMin;
	$max =  $o_img->nominalDefocusMax;
	if ( $min . $max != '' ) {
		$o_data->lev3( 'Defocus', $min != $max ? "$min - $max nm" : "$max nm");
	}
//	foreach ( [ 'energyFilter', 'energyWindow' ] as $n )
	$o_data
		->lev3( 'energyFilter', $o_img->energyFilter )
		->lev3( 'energyWindow', _ifnn( $o_img->energyWindow, '\1 eV' ) )
	;

	$o_data->end3( 'lens' );
	//... Sample Holder
	$o_data
		->lev3( 'Holder' , $o_img->specimenHolder )
		->lev3( 'Model'  , $o_img->specimenHolderModel )
	;

	$min = $o_img->tiltAngleMin;
	$max = $o_img->tiltAngleMax;
	if ( ( $min != '' && $min != '0' ) || ( $max != '' && $max != '0' ) )
		$o_data->lev3( 'Tilt Angle', "$min - $max deg." );

	$tmp = $o_img->temperature;
	$max = $o_img->temperatureMax;
	$min = $o_img->temperatureMin;
	if ( $tmp . $max . $min != '' )
		$o_data->lev3( 'Temperature' ,
			"$tmp K" . _ifnn( $min . $max, " ( $min - $max K)" )
		);
		
	$o_data->end3( 'Specimen Holder' );

	//... camera
	$o_data->lev3( 'Detector', $o_img->detector );

	$s = $o_img->detectorDistance;
	if ( $o_img->detectorDistance != '' ) {
		if ( $s != '' and $s != '-' )
			$o_data->lev3( 'Distance', $s );
	}
	$o_data->end3( 'Camera' );

	//... output
	$o_data->end2( 'Electron microscopy imaging' . ( $multi ? "|#$num" : '' ) );
	++ $num;
}

//.. imageAcquisition
_add_trep([
	'imageAcquisition'	=> '画像取得' ,
	'Image acquisition'	=> '画像取得' ,
	'numDigitalImages'	=> 'デジタル画像の数' ,
	'quantBitNumber'	=> [ 'Bit depth', 'ビット深度' ],
	'samplingSize'		=> 'サンプリングサイズ' ,
	'odRange'			=> [ 'OD range', 'ODレンジ' ],
	'URLRawData'		=> [ 'URL of raw data', '生データのURL' ],
]);

$multi = count( $json->experiment->imageAcquisition ) > 1;
$o_data->lev3order([
	'numDigitalImages',
	'scanner',
	'samplingSize',
	'quantBitNumber',
	'odRange',
	'details',
	'URLRawData',
]);

foreach ( (array)$json->experiment->imageAcquisition as $n1 => $c ) {
	foreach ( (array)$c as $k => $v ) {
		$o_data->lev3( $k, _instr( 'URL', $k ) && $v != '' ? _ab( $v, $v ) : $v );
	}
	$o_data->end3( 'image acquisition' . ( $multi ? '|#' . ( $n1 + 1 ) : '' ) );
}

$o_data
	->lev2( 'Raw data', $emp_link )
	->end2( 'image acquisition' )
;

//.. Processing
_add_trep([
	'Image processing'		=> '画像解析' ,
	'appliedSymmetry'		=> '想定した対称性' ,
	'resolutionByAuthor'	=> [ 'Resolution', '分解能' ] ,
	'ctfCorrection'			=> [ 'CTF correction', 'CTF補正' ] ,
	'resolutionMethod'		=> '分解能の算定法' ,
	'tiltAngleIncrement'	=> '傾斜角の増分' ,
	'numProjections' 		=> '投影像の数' ,
	'numSubtomograms'		=> 'サブトモグラムの数' ,
	'numSections'			=> 'セクションの数' ,
	'numClassAverages'		=> 'クラス平均の数' ,
	'eulerAnglesDetails'	=> [ 'Euler angles', 'オイラー角' ] ,

	'FSC plot'				=> [ 'FSC plot (resolution assessment)',
								 'FSCプロット (分解能の見積もり)' ] ,
	'targetCriteria'		=> '当てはまり具合の基準' ,
]);

//... method
$o_data->lev3( 'Method', $_metname[ $main_id->smalldb()->met ] );

//... processing 手法別

foreach ( (array)$json->processing as $n1 => $c1 ) {
	if ( $n1 == 'method' or $n1 == 'reconstruction' ) continue;
	$o = '';
	foreach ( $c1 as $n2 => $c2 ) {
		if ( $n2 == 'appliedSymmetry' )
			$c2 = _symstr( $c2 );
		$o_data->lev3( $n2, $c2 );
	}
}
$o_data->end3( 'Processing' );
	
//... reconstruction
$x = $json->processing->reconstruction;
$multi = count( $x ) > 1;
$num = 1;
foreach ( (array)$x as $c1 ) {
	$o_data->lev2( '3D reconstruction' . ( $multi ? "|#$num" : '' ), $c1 );
	++ $num;
}

if ( file_exists( $fn = "$ddn/fsc/fscs.jpg" ) ) {
	$o_data->lev2( 'FSC plot', _ab( "$ddn/fsc/fscl.png", _img( $fn  ) ) );
}

$o_data->end2( 'Image processing' );

//..  Fitting
_add_trep([
	'Atomic model buiding'	=> '原子モデル構築' ,
	'Input PDB model'		=> '利用したPDBモデル' ,
	'refProtocol'			=> [ 'Refinement protocol', '精密化のプロトコル' ] ,
	'refSpace'				=> [ 'Refinement space', '精密化に使用した空間' ] ,
	'Output model'			=> '得られたモデル' ,
	'pdbEntryId' 			=> [ 'Input PDB model', '利用したPDBモデル' ], 
	'pdbChainId' 			=> [ 'Chain ID', '鎖ID' ], 
	'modeling'				=> 'モデリング',
]);

$num = 1;

foreach ( (array)$json->experiment->fitting as $c1 ) {
	$o_data->lev3order([
		'software', 'refProtocol', 'targetCriteria', 'refSpace',
		'details', 'pdbEntryId', 'pdbChainId'
	]);
	foreach( $c1 as $n2=>$c2 ) {
		if ( is_array( $c2 ) )
			$c2 = _imp( $c2 );
		$o_data->lev3( $n2, $c2 );
	}
	$o_data->end3( 'Modeling' . "| #$num" );
	++ $num;
}

$o_data->lev3( '_', _ent_catalog(
	(array)_json_cache( DN_DATA . '/ids/fitdb.json.gz' )->$did ,
	[ 'mode'=>'icon' ]
))
->end3( 'Output model' );

$o_data->end2( 'Atomic model buiding' );


//. function
//.. _pstdt: pngへのアンカー: jpgイメージ td (プロジェクションなど用)
function _pstdt( $s, $dn = '' ) {
	global $ddn;
	if ( $dn == '' ) {//- メインマップ以外？
		$dn = "$ddn/mapi";

		//- スライスのホバーで線を出す用のIDとclass
		$a = [
			'proj0' => 'slc_z_',
			'proj2' => 'slc_y_',
			'proj3' => 'slc_x_',
			'surf_x' => 'slc_xs',
			'surf_y' => 'slc_ys',
			'surf_z' => 'slc_zs'
		];
		$i = _nn( $a[ $s ], $s );
		$o = "#$i | .slc";
	}
	return TD . _ab( "$dn/$s.png", _img( $o, "$dn/$s.jpg" ) );
}

//.. _mol_wt
//- 連想配列の中の分子量関連の表記をまとめる
function _mol_wt( $o ) {
	return [
		'molWtTheo'		=> _mw_calc( $o->molWtTheo ) ,
		'molWtExp' 		=> _mw_calc( $o->molWtExp ) ,
		'molWtMethod'	=> $o->molWtMethod
	];
}

function _mw_calc( $w ) {
	if ( $w == '' ) return;
	$w = preg_replace( '/[^0-9\.]/', '', ( $w ) );
	if ( $w > 1000 ) return ( $w / 1000 ) . 'GDa';
	if ( $w > 1    ) return "$w MDa";
	return ( $w * 1000 ) . " kDa";
}

//.. _symstr 対称性の説明
function _symstr( $str ) {
	$a = [
		'C' => _ej( '_ fold cyclic',		'_回回転対称' ) ,
		'D' => _ej( '2*_ fold dihedral',	'2回*_回 2面回転対称' ) ,
		'T' => _ej( 'tetrahedral',			'正4面体型対称' ) ,
		'O' => _ej( 'octahedral',			'正8面体型対称' ),
		'I' => _ej( 'icosahedral',			'正20面体型対称' )
	];
	$add = $str == 'C1'
		? _ej( 'asymmetric', '非対称' )
		: $a[ substr( $str, 0, 1 ) ]
	;
	return $add == ''
		? $str
		: "$str (" . strtr( $add, [ '_' => substr( $str, 1 ) ] ) . ')'
	;
}

//.. _emdb_dblink: 各種データベースへのリンクを生成して返す
function _emdb_dblink( $name, $str ) {
	if ( is_array( $str ) )
		$str = _imp( $str );
	$str = trim( $str );
	$ret = [];

	if ( $name == 'refGo' ) {
		//- GO
		preg_match_all( '/[0-9]+/', $str, $ids );
		foreach ( (array)$ids[0] as $i )
			$ret[] = _dblink( 'GO', $i );
	} else if ( $name == 'refInterpro' ) {
		//- InterPro
		preg_match_all( '/[0-9]+/', $str, $ids );
		foreach ( (array)$ids[0] as $i )
			$ret[] = _dblink( 'InterPro', $i );
	} else if ( $name == 'refUniProt' ) {
		//- UniProt
		preg_match_all( '/[A-Za-z0-9]+/', $str, $ids );
		foreach ( (array)$ids[0] as $i )
			$ret[] = _dblink( 'UniProt', $i );
	} else {
		//- それ以外（ないはずだけど）
		$ret = [ $str ];
	}
	return _imp( $ret );

/*
	//- ICTVDB 無くなった
	} else if ( $name == 'refIctvdb' ) {
		$ret = '<b>' . _l( 'ICTVdb' ) . '</b>: '
			. _ab( _url( 'ictvdb', $str ), IC_L . $str );
*/

}

