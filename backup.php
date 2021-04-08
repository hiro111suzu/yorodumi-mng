<?php
//. init
chdir( __DIR__ );
include 'commonlib.php';

//- ディレクトリ決定
if ( _instr( 'novdisk', __DIR__ ) )
	$r = '/novdisk';
else if ( _instr( 'mardisk', __DIR__ ) )
	$r = '/mardisk';
else
	die( 'unknown host' );

$o = $r . '2/';
$d = $r . '3/';

if ( $argv[1] == '2' ) {
	$o = $r. '3/';
	$d = $r. '4/bkup/';
}

$exfn = __DIR__ . '/exclude_bkup.txt';

_m( "back up $o => $d" );

passthru( "rsync -avz --exclude-from=$exfn --delete $o $d" );
//_m( "rsync -avz --exclude-from=$exfn --delete $o $d" );

//. 残り容量

exec( 'df --block-size=1G', $lines );
$data = [];
foreach ( $lines as $line ) {
	$a = preg_split( '/ +/', $line );
	$mp = trim( $a[5], '/' );
	if ( !_instr( 'disk', $mp ) ) continue;
	$data[ $mp ] = $a[3];
}
ksort( $data );
_kvtable( $data );

$out = "ディスク残り容量\n";
foreach ( $data as $drive => $avl ) {
	$out .= "$drive: $avl GB\n";
}
_hourly_msg( $out );
