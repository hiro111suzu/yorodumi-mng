<?php
//. init
require_once( "commonlib.php" );
define( 'SPLIT1', 'def restore_surface_color_mapping():' );
define( 'SPLIT2', 'registerAfterModelsCB(restore_surface_color_mapping)' );

define( 'OFFSET', <<<EOD
'offset': 0,
         'per_pixel_coloring': False,
         'session_volume_id': u'emd_<id>.map',
         'version': 4,
        }
EOD
);
define( 'REP', [
	'/    False,/'				=> '    True,' ,
	'/[A-Za-z]+_Color_State/'	=> 'Volume_Color_State' ,
	'/'
	. '[0-9\.]+,(\s+)'
	. '[0-9\.]+,(\s+)'
	. '[0-9\.]+,(\s+)'
	. '[0-9\.]+,(\s+)'
	. '[0-9\.]+,(\s+)'
	. '/'
	=> ''
	. '<num1>,$1'
	. '<num2>,$1'
	. '<num3>,$1'
	. '<num4>,$1'
	. '<num5>,$1'
	,
	'/\'origin\'.+?}/s' => OFFSET
]);

//. start main loop
foreach( _idlist( 'emdb' ) as $emdb_id ) {
	_count('emdb');

	$fn_movinfo = _fn( 'movinfo', $emdb_id );
	if ( !file_exists( $fn_movinfo ) ) continue;

	$fn_m1 = _fn( 'session', $emdb_id, 1 );
	$fn_m2 = _fn( 'session', $emdb_id, 2 );
	if ( ! file_exists( $fn_m2 ) ) continue;
	if (   file_exists( $fn_m1 ) ) continue;

	$info = _json_load( _fn( 'movinfo', $emdb_id ) );
	if ( ! $info ) continue;
	if ( $info[2]['mode'] == 'solid' ) continue;

	$text = file_get_contents( $fn_m2 );
	list( $seg1, $t    ) = explode( SPLIT1, $text );
	list( $seg2, $seg3 ) = explode( SPLIT2, $t );

	//.. get stat
	$j = _json_load([ 'emdb_new_json', $emdb_id ])['map']['statistics'];
	$maximum = $minimum = $average = $std = 0;
	extract( $j );
	$th = _json_load( $fn_movinfo )[2]['threshold'];

	$v1 = ( $th * 10 + $maximum * 0 ) / 10;
	$v2 = ( $th * 9 + $maximum * 1 ) / 10;
	$v3 = ( $th * 8 + $maximum * 2 ) / 10;
	$v4 = ( $th * 7 + $maximum * 3 ) / 10;
	$v5 = ( $th * 6 + $maximum * 4 ) / 10;
	_line( 'session #1 作成', $emdb_id );
//	_m( _json_pretty( $j ) );
	_m( _json_pretty( compact( 'v1', 'v2', 'v3', 'v4', 'v5' ) ) );
//	_pause();

	//.. 変換
	$seg2 = _reg_rep( $seg2, REP );
	$seg2 = strtr( $seg2, [
		'<id>'   => $emdb_id ,
		'<num1>' => $v1 ,
		'<num2>' => $v2 ,
		'<num3>' => $v3 ,
		'<num4>' => $v4 ,
		'<num5>' => $v5 ,
	]);
	_line( '変換後' );

	file_put_contents( $fn_m1, $seg1. SPLIT1. $seg2. SPLIT2. $seg3 );
}

_end();

