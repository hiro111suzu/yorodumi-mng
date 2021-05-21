ddbjデータから、jsonデータを作成

==========

<?php
require_once( "commonlib.php" );
$tdn = "$_ddn/taxo";

//if ( $_redo )
	exec( "rm $tdn/json/*" );

//. json
$name2tid = _json_load( "$tdn/name2tid.json" );
_m( 'make json', 1 );

$bname = []; //- 分類名
$syn = [];

foreach ( glob( "$tdn/txt/*.txt" ) as $fn ) {
	$name_ = basename( $fn, '.txt' );
	$name = strtr( $name_, '_', ' ' );
	$jfn = "$tdn/json/$name_.json";
	if ( file_exists( $jfn ) ) continue;

	$f = _file( $fn );
	if ( count( $f ) < 2 ) continue;

	$json = [];

	//- 解析
	$d = [];
	foreach ( $f as $l ) {
		$a = explode( ':', $l, 2 );
		$d[ $a[0] ] = trim( $a[1] );
	}

	//- name
	$name = $json[ 'name' ] = $d[ 'Taxonomic name' ];

	//- tid (PDBデータから)
	if ( $name2tid[ $name ] > 0 )
		$json[ 'tid' ] = array_values( $name2tid[ $name ] );

	//- lineage
	$json[ 'line' ] = $d[ 'Abbreviated lineage' ];

	foreach ( explode( ';', $json[ 'line' ] ) as $s ) {
		++ $bname[ $s ];
	}

	//- シノニム集計
	$on = $d[ 'Other name(s)' ];
	if ( $on != '' and $on != 'null' )  {
		$json[ 'oname' ] = $on;
		foreach ( explode( ',', $on ) as $n ) {
			$n = trim( preg_replace( '/ ?\(.+?\)/', '', $n ) ) ;//- 括弧の文字列を消す
			$syn[ $n ][ $name ] = 1;
		}
	}

	//- type
	if ( strpos( $json[ 'line' ], 'Viruses'       ) !== false ) $json[ 'type' ] = 'vir';
	if ( strpos( $json[ 'line' ], 'Bacteria'      ) !== false ) $json[ 'type' ] = 'bac';
	if ( strpos( $json[ 'line' ], 'Archaea'       ) !== false ) $json[ 'type' ] = 'arc';
	if ( strpos( $json[ 'line' ], 'Eukaryota'     ) !== false ) $json[ 'type' ] = 'euk';
	if ( strpos( $json[ 'line' ], 'Fungi'         ) !== false ) $json[ 'type' ] = 'fng';
	if ( strpos( $json[ 'line' ], 'Viridiplantae' ) !== false ) $json[ 'type' ] = 'plt';
	if ( strpos( $json[ 'line' ], 'Vertebrata'    ) !== false ) $json[ 'type' ] = 'vrt';
	if ( strpos( $json[ 'line' ], 'Mammalia'      ) !== false ) $json[ 'type' ] = 'mam';
	if ( $name == 'Synthetic construct' ) $json[ 'type' ] = 'syn';
	_json_save( $jfn, $json );
//	print_r( $json );
//	if ( $json[ 'type' ] == '' ) die( "no type info $name" );
}

arsort( $bname );
$o = [];
foreach ( $bname as $n => $cnt ) {
	if ( $cnt < 3 ) continue;
	$o[] = "$n [$cnt]";
}
_m( implode( "\t", $o ) );

foreach ( $syn as $n => $v )
	$syn[ $n ] = array_keys( $v );
_comp_save( "$tdn/syn_ddbj.data", $syn );
