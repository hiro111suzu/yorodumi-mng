<?php
require_once( "commonlib.php" );

foreach ( _tsv_load( DN_EDIT. '/pap_title.tsv' ) as $id => $title ) {
	$pmid = _json_load([ _inlist( $id, 'epdb' ) ? 'pdb_add' : 'emdb_add', $id ])['pmid'];
	if ( $pmid )
		_m( "$id: $pmid <= $title" );
}
