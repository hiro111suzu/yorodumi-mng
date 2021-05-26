add jsonを作成
replacedidのリスト作成
pubmed-id.ini整理

<?php
//. misc init
require_once( "commonlib.php" );

$resdata = [
	'1brd' => '3.5',
	'1upn' => '16',
	'2bgz' => '12.00',
	'2c7e' => '9.7',
	'2i68' => '7.5',
	'2ix8' => '9.9',
	'487d' => '7.5'
];

$met_data = [
	'1foq' => 's' ,
	'1x18' => 's' , 
	'2rec' => 'h' , 
	'3dco' => 's' , 
	'3epc' => 's' , 
	'3izk' => 's' ,
	'3jce' => 's' ,
	'3jcd' => 's' ,
	'3j07' => 's' ,
	'5ijn' => 'a' ,
	'5ijo' => 'a' ,

	'5j8v' => 's' ,
	'5j4z' => 's' ,
	'5j7y' => 's' ,
	'5j8k' => 's' ,
];

//- splitのIDも加える
$reps = _json_load( DN_PREP . '/split2large.json' );

//- pubmed-ID
$pubmedid_tsv = new cls_pubmedid_tsv( 'pdb', [ '5oyb' ] );

$ids_unknown_method = [];

define( 'FITDB', _json_load( DN_PREP. '/emn/fitdb.json.gz' ) );

_line( 'main loop' );
//. start main loop
foreach( _idlist( 'epdb' ) as $id ) {
	_count( 'epdb' );
	$did = "pdb-$id";

//. データ読み出し
	$json = _json_load2( _fn( 'epdb_json', $id ) );
	$data = [];

	//.. misc
	$data[ 'ddate' ] = $json->pdbx_database_status[0]->recvd_initial_deposition_date;
	$data[ 'rdate' ] = $json->pdbx_audit_revision_history[0]->revision_date;

	$data[ 'reso' ] = rtrim( _x(
		$resdata[ $id ] ?:
		$json->em_3d_reconstruction[0]->resolution ?:
		$json->refine[0]->ls_d_res_high ?:
		$json->reflns[0]->d_resolution_high
	, 'num' ), '.' );

	//.. authors
	$a = [];
	foreach( (array)$json->audit_author as $au )
		$a[ $au->pdbx_ordinal ] = $au->name;
	$data[ 'author' ] = array_values( $a );

	//.. pubmed-ID
	$pmid_tsv = $id2pubmedid[ 'pdb' ][ $id ];
	if ( $pmid_tsv )
		_m( "$id: $pmid_tsv" );

	$pmid_xml = '';
	foreach ( (array)$json->citation as $x ) {
		if ( $x->id != 'primary' ) continue;
		$pmid_xml = $x->pdbx_database_id_PubMed;
	}
	$data[ 'pmid' ] = $pubmedid_tsv->get( $id, $pmid_xml );

	//.. method
	$met = $json->exptl[0]->method == 'ELECTRON CRYSTALLOGRAPHY'
		? '2'
		: _met_code( $json->em_experiment[0]->reconstruction_method )
	;

	//... 不明対策

	if ( ! $met ) {
		//- EMDBのデータを参照
		foreach ( (array)FITDB[ $did ] as $eid ) {
			$m = _json_load2( _fn( 'add', $eid ) )->met;
			if ( ! $m ) continue;
			$met = $m;
			break;
		}
	}

	if ( $met == '' ) {
		//- 別のタグから探す
		$recmet	= $json->em_3d_reconstruction[0]->method;
		$asb	= $json->pdbx_struct_assembly[0]->details;

		if ( $asb == 'complete icosahedral assembly' )
			$met = 'i';
		else if ( _instr( 'tomograph', $recmet ) )
			$met = 't';
		else if ( _instr( 'single particle', $recmet )
			|| _instr( 'cross-common lines', $recmet )
			|| _instr( 'projection matching', $recmet )
			|| _instr( 'spider', $recmet )
		)
			$met = 's';
//		else if ( _instr( 'helical', $recmet ))
//			$met = 'helical';
		else if ( $asb == 'representative helical assembly' )
			$met = 'h';
		else {
			//- マニュアル指定
			$met = $met_data[ $id ];
		}
	 }

	//- とりあえずsingleparticleにしておく
	if ( $met == '' ) {
		$ids_unknown_method[] = $id;
		++ $nomet_recmet[ $recmet ];
		_problem( "PDB-$id: unknown method" );
		$met = 's';
		_cnt2( 'unknown', 'met' );
	}

	_cnt2( $met, 'met' );
	$data[ 'met' ] = $met;

	//.. cryo / stain
	$s = $json->em_specimen[0];
	$data[ 'cryo' ] = $s->vitrification_applied == 'YES' ? 1 : '';
	$data[ 'stained' ] = $s->staining_applied == 'YES' ? 1 : '';

	if ( $data[ 'cryo' ] && $data[ 'stained' ] )
		_cnt2( 'cryo+stained', 'stain/cryo' );
	else if ( $data[ 'cryo' ] )
		_cnt2( 'cryo', 'stain/cryo' );
	else if ( $data[ 'stained' ] )
		_cnt2( 'stained', 'stain/cryo' );
	else
		_cnt2( 'no cryo + no stain', 'stain/cryo' );

	//.. 書き込み 
	_comp_save(
		_fn( 'pdb_add', $id ),
		array_filter( $data ),
		'nomsg'
	);

	//- replaced ID * jsonに書き込まない！
	foreach ( (array)$json->pdbx_database_PDB_obs_spr as $n => $r ) {
		$reps[ $o = strtolower( $r->replace_pdb_id ) ] = $id;
	}
}
_cnt2();
_delobs_pdb( 'pdb_add' );

//. end
_line( 'データ保存' );
_comp_save( DN_PREP . '/replacedid.json', $reps );

$pubmedid_tsv->save();

//. 手法が不明のエントリ、あれば表示
if ( count( $ids_unknown_method ) > 0 ) {
	_m( '手法不明のエントリあり', -1 );
	_m( "num: " . count( $ids_unknown_method )
		. "\n" . implode( ', ', $ids_unknown_method ) 
	);
	foreach ( (array)$nomet_recmet as $n => $v )
		echo "$n \t: $v\n";
}

_end();
