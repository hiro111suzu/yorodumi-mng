-----
simlist作成
入力: simtable<mode>.json
出力: [各DID]-list.txt
-----
<?php
//. init

require_once( "commonlib.php" );

define( 'LIMIT', [
	0 => 0.7,
	1 => 0.73,
	2 => 0.76,
	3 => 0.79,
	4 => 0.82,
	5 => 0.85,
	6 => 0.88,
	7 => 1.1
]);

$_filenames += [
	'omolist' => DN_PREP. '/omolist/<did>.txt'
];

//. データ読み込み
$mode = '_lim';
define( 'TABLE', _json_load( $fn = DN_PREP . "/simtable$mode.json.gz" ) );
if ( ! TABLE )
	die( "ファイルがない: $fn" );
_m( count( TABLE ) . '個のデータを読み込み' );

//. main loop
foreach ( _joblist() as $job ) {
	_count( 'both' );
	extract( $job );  //- $db, $id,  $did

	$ls = TABLE[ $did ];
	if ( ! $ls ) continue;
	unset( $ls[ $did ] );
	arsort( $ls );
	$rank = 0;
	$out = '';

	foreach ( $ls as $n => $score ) {
		if ( $score < LIMIT[ $rank ] ) break;
		if ( $did == $n ) continue;
		$out .= "$n\n";
		++ $rank;
	}
	file_put_contents( _fn( 'omolist', $did ), $out );
}
