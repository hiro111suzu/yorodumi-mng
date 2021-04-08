<?php
//. init
include "commonlib.php";
include "sas-common.php";

//. sascifファイル取得
$data = [];

foreach ( _idloop( 'sas_json' ) as $fn ) {
	$id = _fn2id( $fn );
	$json = _json_load2( $fn );

	//.. title
	$title = $json->sas_sample[0]->name;
	if ( strlen( $title ) < 50 ) {
		$a = [];
		foreach ( (array)$json->entity as $c ) {
			$n = $c->pdbx_description;
			if ( $n != $title )
				$a[ $c->id ][] = $n;
		}
		foreach ( (array)$json->entity_name_com as $c ) {
			$n = $c->name;
			if ( $n != $title )
				$a[ $c->entity_id ][] = $n;
		}
		$en = [];
		foreach ( $a as $names ) {
			if ( $names != [] )
				$en[] = _imp( array_filter( array_unique( $names ) ) );
		}
		$en = implode( ' + ', $en );
		if ( $en != '' )
			$title .= " ($en)";
	}
	$data[ $id ][ 'title' ] = $title;

	//.. file time
//	$data[ $id ][ 'cif_date' ] = date( 'Y-m-d', filemtime( _fn( 'sas_cif', $id ) ) );

	//.. source
	$src = [];
	foreach ( (array)$json->entity_src_gen as $j )
		$src[] = _taxrep( $j->gene_src_common_name );
	foreach ( (array)$json->struct_ref as $j )
		$src[] = _taxrep( $j->gene_src_common_name );

	foreach ( array_unique( array_filter( $src ) ) as $s ) {
		if ( $s == '' ) continue;
		$data[ 'src' ][ $s ][] = $id;
		$data[ $id ][ 'src' ][] = $s;
	}

}
//. func
//.. _taxrep 生物種名 変換
function _taxrep( $in ) {
	if ( $in == '' ) return;
	$ret = trim( _reg_rep(
		ucfirst( strtolower( $in ) ), [
			'/\((strain.+?)\)/'	=> '{{$1}}' , //- strain じゃない奴は括弧を消す
			'/\((.+?)\)/'		=> '' ,
			'/{{(.+?)}}/'		=> '($1)' ,
			'/^Mouse$/' 		=> 'Mus musculus' ,
			'/^Human$/'			=> 'Homo sapiens' ,
			'/E. ?coli/'		=> 'Escherichia coli' ,
			'/B. ?subtilis/'	=> 'Bacillus subtilis' ,
			'/^Streptococcus pneumonia$/' => 'Streptococcus pneumoniae',
		]
	));
//	if ( $in != $ret )
//		_m( "$in => $ret" );
	return $ret;
}

/*
$test = [];
foreach ( $data[ 'src'] as $s => $ids ) {
	$test[ $s ] = count($ids );
}
arsort( $test );
foreach ( $test as $n => $num )
	_m( "$n : $num" );
*/

_comp_save( FN_SUBDATA_PRE, $data );
_end();

