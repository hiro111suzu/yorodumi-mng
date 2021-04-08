<?
//. init
require_once( "commonlib.php" );
$json = _json_load( DN_PREP . "/simtable.json" );
//foreach( $json as $hoge=>$fuga ) {
//	echo "$hoge,";
//}

$id = (string)$argv[ 1 ];
if ( in_array( $id, $emdbidlist ) )
	$id = "emdb-$id";
if ( in_array( $id, $pdbidlist ) )
	$id = "pdb-$id";

$ar = $json[ $id ];
//print_r( $ar );

if ( count( $ar ) == 0 ) {
	die( "$id: no data" );
}



asort( $ar );
$num = 1;
//echo "0: $id: $name = $sx->{$id}[ 'sname' ] . $sx->{$id}[ 'title' ];

foreach ( $ar as $id=>$v ) {
//	$name = $sx->{$id}[ 'sname' ] . $sx->{$id}[ 'title' ];
	echo "$num\t $id\t $v\n";
	if ( $num > 100 ) break;
	++ $num;
	
}
