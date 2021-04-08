<?php
require_once( "commonlib.php" );
define( 'DN_ABWORK', DN_PREP. '/ab_work' );
_mkdir( DN_ABWORK );

$_filenames += [
	'ab_work'     => DN_ABWORK. '/<id>.json.gz' ,
];

//. sqlitedb
$sqlite_test = new cls_sqlw([
	'fn'	=> DN_PREP. '/abinfo.sqlite' ,
	'cols' => [
		'id UNIQUE COLLATE NOCASE' ,
		'by_name' ,
		'by_cath' ,
		'ig_like' ,
		'sacs' ,
		'seq' ,
		'dif' , 
		'ent_name',
	] ,
	'indexcols' => [ 'id' ],
	'new'		=> true
]);

$sqlite = new cls_sqlw([
	'fn'	=> 'abinfo' ,
	'cols' => [
		'id UNIQUE COLLATE NOCASE' ,
		'pdb_id' ,
		'ig_like' ,
		'seq' ,
		'sacs' ,
	] ,
	'indexcols' => [ 'id', 'pdb_id', 'ig_like' ],
	'new'		=> true
]);

//. main loop
foreach ( _idloop( 'ab_work' ) as $fn ) {
	_count( 5000 ); 
	$id = _fn2id( $fn );
	foreach ( _json_load2( $fn ) as $ent_id => $json ) {

		//.. cath
		$cath = 0;
		foreach ( (array)json_decode( _ezsqlite([
			'dbname' => 'cath' ,
			'where'  => [ 'id', $id. $json->cid[0] ] ,
			'select' => 'data'
		])) as $c2 ) {
			if ( $c2->cid != '2.60.40.10' ) continue;
			$cath = 1;
			break;
		}
		
		//.. sacs
		$sacs_json = json_decode( _ezsqlite([
			 'dbname' => DN_PREP. 'sacs.sqlite' ,
			 'where'  => [ 'id', $id. '-'. $ent_id ] ,
			 'select' => 'json' ,
		]));
		$sacs = $sacs_json ? 1 : 0;

		//.. name
		$name = 0;
		$name = _term_hit([
			[
				'(heavy|light|lambda|kappa) chain',
				'(mab\b|\bfab\b|antibody|\bfv\b|antigen binding)'
			] ,
			[ '\bFAB\b', '\bVH\b|\bVL\b' ] ,
			'\bantibody\b' ,
			'\bnanobody\b' ,
			'\bmonobody\b' ,
			'\bDiabody\b' ,
			'\bMegabody\b' ,
			'\bfab\b' ,
			'Immunoglobulin G' ,
			'\bIGG[0-9]' ,
			'SCFV FRAGMENT' ,
			'Single-chain Fv' ,
			'/\bscFv\b/' ,
			'\bFC FRAGMENT' ,
			'\bFv fragment' ,
			'\bVHH\b' ,
			'/\bscFv[\b0-9]/' ,
			'\bSingle domain antibody\b' ,
		]);

		//.. maybe
		$maybe = _term_hit([
			'\blight chain\b' ,
			'\bheavy chain\b' ,
		]);

		//.. iglike
		$like = _term_hit([
			[ 'T[ \-]cell\b', '\breceptor' ],
			[ 'tcell', 'alpha' ] ,
			[ '/TCR/', 'alpha'] ,
			'T-cell surface' ,
			'receptor' ,
			'CMRF35-like' ,
			'Cytotoxic T' ,
			'Tcell receptor' ,
			'microglobulin' ,
			'HLA class I' ,
			'/HLA-/' ,
			'/MHC-/' ,
			'MHC CLASS' ,
			'HEMOLIN' ,
			'HISTOCOMPATIBILITY' ,
			'GLYCOSYLTRANSFERASE',
			'GALACTOSIDASE' ,
			'\b(alpha|beta) chain' ,
			'Programmed cell death' ,
			'NUCLEAR FACTOR' ,
			'TISSUE FACTOR' ,
			'^cd2$' ,
			'GLUCOAMYLASE',
			'GLUCURONIDASE' ,
			'TITIN' ,
			'ACETYLHEXOSAMINIDASE' ,
			'TRANSFERASE' ,
			'CHITINASE' ,
			'TREHALOHYDROLASE' ,
			'SIALIDASE' ,
			'CELL ADHESION' ,
			'Tenascin' ,
			'amylase' ,
			'Filamin' ,
			'CHAPERONE' ,
			'Cellulase' ,
			'Fibronectin' ,
			'ADNECTIN' ,
			'adhesion' ,
			'NEOGENIN' ,
			'COLLAGENASE' ,
			'DEXTRINASE' ,
			'Antigen-presenting' ,
			'GLUCOSAMINIDASE' ,
			'MANNOSIDASE' ,
		]);

		//.. $seq
		preg_match(
			'/^(.+C)(.{10,17})(W.{14})(.{7})(.{30,34}C)(.{7,11})(FG.G)/' ,
			$json->seq, $match_l
		); 
		preg_match(
			'/^(.+C.{8})(.{5,7})(W.{13})(.{16,19})([KR][LIVFTAS][TSIA].{26}C..)(.{3,25})(WG.G)/' ,
			$json->seq, $match_h
		);
		//- ラクダ抗体
		if ( ! $match_h && in_array( strtolower( $json->src ), [
			'camelidae' ,
			'camelus dromedarius' ,
			'camelus bactrianus' ,
			'lama glama' ,
			'vicugna pacos' 
		] ) ) {
			preg_match(
				'/^(.+C.{8})(.{5,7})(W.{13})(.{16,19})([KR][LIVFTAS][TSIA].{26}C..)(.{5})(.)/' ,
				$json->seq, $match_h
			);
			if ( $match_h ) {
//				_m( "$id-$ent_id: らくだ" );
				_cnt( 'らくだ' );
			}
		}

		//- マッチしないけど抗体らしかったら、甘い基準で判定
		if ( ! $match_l && ! $match_h  && ! $like &&
			( $name || $cath || $maybe || $sacs ) 
		) {
			preg_match(
				'/^(.+C)(.{10,17})(W.{14})(.{7})(.{30,34}C)(.{7})(.)/' ,
				$json->seq, $match_l
			); 
			preg_match(
				'/^(.+C.{8})(.{5,7})(W.{13})(.{16,19})([KR][LIVFTAS][TSIA].{26}C..)(.{5})(.)/' ,
				$json->seq, $match_h
			);
			if ( $match_h || $match_l ) {
//				_m( "$id-$ent_id: 無理やり" );
				_cnt( '無理やり' );
			}
		}

		$seq = [];
		foreach ( [ 'L' => $match_l, 'H' => $match_h ] as $type => $match ) {
			if ( ! $match ) continue;
			list( $all, $s1, $c1, $s2, $c2, $s3, $c3 ) = $match;
			$seq = array_merge( $seq, [
				$type. '1' => [
					strlen( $s1 ),
					strlen( $s1. $c1 ) - 1,
					$c1
				] ,
				$type. '2' => [
					strlen( $s1. $c1. $s2 ),
					strlen( $s1. $c1. $s2. $c2 ) - 1,
					$c2
				] ,
				$type. '3' => [
					strlen( $s1. $c1. $s2. $c2. $s3 ),
					strlen( $s1. $c1. $s2. $c2. $s3. $c3 ) - 1 ,
					$c3
				] ,
			]);
		}
		$diff = ( $sacs && (array)$sacs_json->cdr != $seq ) ? 1 : 0 ;

		//.. 判定
		if ( !$cath && !$sacs && !$name && ! $seq ) continue;

		//- cathだけでヒット、likeあつかい
		if ( $cath && !$like && !$sacs && !$seq && !$maybe) {
			$like = 1;
		}
		
//		if ( $cath && !$name && !$like && !$sacs )


		//.. load data
		$sqlite_test->set([
//		_pause([ 
			"$id-$ent_id" ,
			$name ,
			$cath ,
			$like ,
			$sacs ,
			json_encode( $seq ) ,
			$diff ,
			$json->name
		]);
		$sqlite->set([
//		_pause([ 
			"$id-$ent_id" ,
			$id ,
			$like ,
			json_encode( $seq ) ,
			json_encode( $sacs_json )
		]);
	}
}

$sqlite->end();
$sqlite_test->end();

_cnt();

//. func
//.. _term_hit
function _term_hit( $term_set ) {
	global $json;
	foreach ( $term_set as $term ) {
		//- arrayは両方ヒットしないとダメ
		if ( is_array( $term ) ) {
			if ( 
				_match( $term[0], $json->name ) &&
				_match( $term[1], $json->name )
			) {
				return 1;
			}
		} else {
			if ( _match( $term, $json->name ) ) {
				return 1;
			}
		}
	}
	return 0;
}

function _match( $reg, $subj ) {
	if ( substr( $reg, 0, 1 ) != '/' )
		$reg = "/$reg/i";
	return preg_match( $reg, $subj );
}
