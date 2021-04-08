<?php
include "commonlib.php";

define( 'FLG_DOWNLOAD', true );
define( 'URL_XML', 'http://www.bioinf.org.uk/abs/sacs/antibodies.xml' );
define( 'FN_XML', DN_FDATA. '/dbid/antibody_sacs.xml' );


//. download

_line( 'XML', 'downloading' );
_flg_copy( URL_XML, FN_XML );

/*
if ( _newer( FN_XML, DN_DATA. '/sacs.sqlite' ) ) {
	_m( 'データに変更なし, DB作成作業スキップ' );
	_end();
	_die();
}
*/

_line( 'XML', 'conversion' );

//. prep db
$sqlite = new cls_sqlw([
	'fn' => 'sacs', 
	'cols' => [
		'id UNIQUE' ,
		'json' ,
	],
	'new' => true ,
	'indexcols' => [ 'id' ],
]);

//. convert
//$data = [];
foreach ( simplexml_load_file( FN_XML ) as $c1 ) {
	$cdr = [];
	foreach ( $c1->structural_info->cdrs->children() as $c2 ) {
		$cdr[ (string)$c2['id'] ] = (string)$c2->sequence;
	}

	$id = strtolower( (string)$c1[ 'pdb' ] );
	foreach ( (array)_json_load2( [ 'pdb_json', $id ] )->entity_poly as $cat ) {
		$data = [];
		$pdbseq = strtr( $cat->pdbx_seq_one_letter_code_can, [ ' ' => '' ] );
//		_pause( $pdbseq );
		foreach ( $cdr as $name => $seq ) {
//			if ( ! $seq  )
//				_pause( "$id:$name" );
			$pos = strpos( $pdbseq, $seq ?: '---' );
			if ( $pos === false ) continue;
			$data[ 'cdr' ][ $name ] = [ $pos, $pos + strlen( $seq ) - 1, $seq ];
			$cls = substr( $name, 0, 1 ) == 'L'
				? $c1->summary_info->class->light
				: $c1->summary_info->class->heavy
			;
			if ( $cls && $cls != '?' )
				$data[ 'cls' ] = (string)$cls;
		}
		if ( $data ) {
			$sqlite->set([
				"$id-". $cat->entity_id ,
				json_encode( $data )
			]);
		}
	}
}

//. end
//_kvtable( $data );
$sqlite->end();
_end();

//. func
//.. _flg_do
function _flg_do( $db ) {
	return DBS_TODO == 'all' || DBS_TODO == $db;
}

//.. _flg_copy
function _flg_copy( $in, $out ) {
	_m( "$in\n=> $out ");
	if ( ! FLG_DOWNLOAD ) {
		_m( 'Downloading is canceled' );
		return;
	}
	_download( $in, $out );
}

//.. _similar
function _similar( $s1, $s2 ) {
	$c = [];
	foreach ([ $s1, $s2 ] as $s ) {
		$c[] = strtolower( strtr( $s, [ ','=>' ', '_'=> ' ' ] ) );
	}
	return $c[0] == $c[1];
}

//.. q2null
function _q2null( $in ) {
	return $in == '?' ? '' : (string)$in ;
}
