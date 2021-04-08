<?php
require "commonlib.php";
$id = $argv[1];
$a = explode( '-', $id );
$fn = _instr( '-', $id )
	? DN_FDATA . "/pdb/asb/{$a[0]}.pdb{$a[1]}.gz"
	: DN_FDATA . "/pdb/dep/pdb$id.ent.gz"
;
if ( ! file_exists( $fn ) )
	die( "No PDB file for $id" );

$flg_model = false;
$modelid = 0;
foreach ( explode( "\n", _gzload( $fn ) ) as $line ) {
	if ( _getval(1, 5) == 'MODEL' ) {
		$modelid = _getval(7, 20);
		$flg_model = true;
	}

	if ( _getval(1, 6) != 'ATOM' ) continue;
	$atype = _getval(13, 16);
	if ( $atype != 'CA' && $atype != 'P' ) continue;

	$models[ $modelid ][ _getval(22, 22) ][] = [
		_getval( 31, 38 ) ,
		_getval( 39, 46 ) ,
		_getval( 47, 54 ) ,
	] ;
}

$out = '';
foreach ( $models as $modelid => $chains ) {
	$aid = 0;
	$con = '';
	if ( $flg_model )
		$out .= 'MODEL ' . _lenstr( $modelid, 7, 14 ) . "\n";
	foreach ( $chains as $chainid => $atoms ) {
	//	_pause( "$chainid:" . count( $atoms ) );
		//- 重心
		$sum = [];
		foreach ( $atoms as $atom ) {
			$sum[0] += $atom[0];
			$sum[1] += $atom[1];
			$sum[2] += $atom[2];
		}
		$ac = count( $atoms );
		$cent = [
			$sum[0] / $ac ,
			$sum[1] / $ac ,
			$sum[2] / $ac ,
		];
		
		//- 一番遠い
		$atom1 = [];
		$atom2 = [];
		$max = 0;
		$min = 10000000000;
		foreach ( $atoms as $atom ) {
			$d = _udist( $cent, $atom );
			if ( $max < $d ) {
				$max = $d;
				$atom1 = $atom;
			}
			if ( $d < $min ) {
				$min = $d;
				$atom2 = $atom;
			}
		}

		//- 2つ目
		$atom3 = [];
		$max = 0;
		foreach ( $atoms as $atom ) {
			$d = _udist( $atom1, $atom );
			if ( $max < $d ) {
				$max = $d;
				$atom3 = $atom;
			}
		}
		
		++ $aid;
		$out .= _linerep( 1, $chainid, $aid, $atom1 ) . "\n";
		++ $aid;
		$out .= _linerep( 2, $chainid, $aid, $atom2 ) . "\n";
		++ $aid;
		$out .= _linerep( 3, $chainid, $aid, $atom3 ) . "\n";
		$con .= strtr( "CONECT <1> <2>\n", [
			'<1>' => _lenstr( $aid - 2, 4 ),
			'<2>' => _lenstr( $aid - 1, 4 )
		])
		. strtr( "CONECT <1> <2>\n", [
			'<1>' => _lenstr( $aid - 1, 4 ),
			'<2>' => _lenstr( $aid    , 4 )
		]);
	}
	$out .= $con;
	if ( $flg_model )
		$out .= "ENDMDL\n";
}

file_put_contents( "msm_$id.pdb", $out );
_m( $out );

//. func: _udist
function _udist( $c1, $c2 ) {
	return sqrt(
		pow( $c1[0] - $c2[0], 2 ) +
		pow( $c1[1] - $c2[1], 2 ) +
		pow( $c1[2] - $c2[2], 2 )
	);
}

function _linerep( $resid, $cid, $atomid, $atom ) {
	return strtr( 'ATOM    <aid> CA   XXX <cid>   <resid>     <x> <y> <z>           0           C',
		[
			'<resid>'	=> _lenstr( $resid, 1 ) ,
			'<aid>'		=> _lenstr( $atomid, 3 ),
			'<cid>'		=> _lenstr( $cid, 1 ),
			'<x>'		=> _lenstr( $atom[0], 7 ),
			'<y>'		=> _lenstr( $atom[1], 7 ),
			'<z>'		=> _lenstr( $atom[2], 7 ),
		]
	);
}

function _lenstr( $s, $num ) {
	if ( strlen( $s ) < $num )
		return substr( '          ' . $s, $num * -1 );
	else 
		return substr( $s, 0, $num ) ;
}

function _getval( $n1, $n2 ) {
	global $line;
	return trim( substr( $line, $n1 - 1, $n2 - $n1 + 1 ) );
}


/*
ATOM   2930  CA  TYR A 642     262.834 250.562 154.631  1.00255.64           C  
ATOM      1  CA  XXX X   1      95.698  52.454  12.984           0           C
ATOM      1  CA  XXX A   1      95.698  52.454  12.984           0           C
*/
