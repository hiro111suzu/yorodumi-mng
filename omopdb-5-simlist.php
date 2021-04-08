omo-pdb-3: DB作成

<?php

//. init
require_once( "commonlib.php" );
require_once( "omo-common.php" );

//- DB ファイル名
define( 'FN_DB' ,  DN_DATA .  '/profdb_ss.sqlite' );
define( 'FN_DBS',  DN_DATA .  '/profdb_ss.sqlite' );


define( 'DN_EMDB', DN_DATA . '/emdb/omo' );
_mkdir( DN_EMDB );

//. emdb
$idas = [];
foreach ( _idlist( 'emdb' ) as $i ) {
	_search(
		$idas = "e$i" ,
		DN_EMDB . '/$i.json' ,
	);
}


//. _search
function _search( $ida, $fn ) {
	
}


