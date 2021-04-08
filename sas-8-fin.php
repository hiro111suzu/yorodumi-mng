subdataファイナライズ
各ID [ title, src, mid2id ]
mid2id
src
<?php
include "commonlib.php";
include "sas-common.php";

$data = _json_load( FN_SUBDATA_PRE );
$mid_json = _json_load( FN_SAS_MID );
//. subdata.json;
foreach ( array_keys( $data ) as $id ) {
	//- $id: SASBDB-ID
	if ( $id == 'src' ) continue; 
	$data[ $id ][ 'mid' ] = $mid_json[ 'id2mid' ][ $id ];
}
$data[ 'mid' ] = $mid_json[ 'mid2id' ];
_comp_save( FN_SUBDATA, $data );


//. idtable.tsv
$idtable = [ "model-ID\tSASBDB-ID\ttitle" ];
foreach ( $mid_json[ 'mid2id' ] as $mid => $id ) {
	$idtable[] = implode( "\t", [ $mid, $id, $data[ $id ][ 'title' ] ] );
}
_comp_save( FN_IDTABLE, implode( "\n", $idtable ) );
_end();

