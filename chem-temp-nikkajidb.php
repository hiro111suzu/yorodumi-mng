<?php
require_once( "commonlib.php" );
//. init

//. prep DB
$sqlite = new cls_sqlw([
	'fn' => DN_PREP. '/chem/nikkaji_name.sqlite', 
	'cols' => [
		'id COLLATE NOCASE' ,
		'name COLLATE NOCASE' ,
	],
	'new' => true ,
	'indexcols' => [ 'id' ] ,
]);

//. 
$id = $name = '';
foreach ( glob( DN_FDATA. '/nikkaji/*.ttl.gz' ) as $fn ) {
	_count();
	_line( basename( $fn ) );
	foreach ( gzfile( $fn ) as $line ) {
		//- セクション終了
		if ( substr( $line, 0, 5 ) == '<http' ) {
			_count( 100000 );
			if ( $id && $name )
				$sqlite->set([ $id, $name ]);
			$id = $name = '';
			continue;
		}

		list( $l, $r ) = explode( ' ', trim( $line ), 2 );

		//- 名称
		if ( !$name && $l == 'rdfs:label' ) {
			$name = explode( '"', $r, 3 )[1];
//			_m( $name );
		}
		//- id
		if ( !$id && $l == 'jst:nikkaji-number' ) {
			$id = explode( '"', $r, 3 )[1];
//			_m( $id );
		}
	}
}

//- DB終了
$sqlite->end();

