cifを分割

<?php
require_once( "commonlib.php" );

define( 'FN_ORIG'   , DN_FDATA . '/components.cif.gz' );
define( 'FN_IDLIST' , DN_DATA  . '/ids/chem.txt' );

//. main
$chemidlist = [];
_count();
foreach ( preg_split( '/[\n\r]+data_/', "\n" . _gzload( FN_ORIG ) ) as $str ) {
	if ( trim( $str ) == '' ) continue;
	if ( _count( 'chem', 0 ) ) break;
	$a = explode( "\n", $str );
	$id = trim( $a[0] );

	$chemidlist[] = $id;
//	if ( $id != 'NA' ) continue; //-----------

	$fn_cif = _fn( 'chem_cif', $id );
	$str = "data_$str\n";

	//- 原子一個だけ
	if ( _instr( '_chem_comp_atom.comp_id  ', $str ) ) {
//		_m( "-- $id - 1原子だけ --" );
		$out = [];
		foreach ( explode( "\n# \n", $str ) as $cat ) {
			if ( ! _instr( '_chem_comp_atom.comp_id  ', $cat ) ) {
				$out[] = $cat;
				continue;
			}
			$cols = [];
			$vals = [];
			foreach ( explode( "\n", $cat ) as $line ) {
				$a = explode( ' ', $line, 2 );
				$cols[] = trim( $a[0] );
				$vals[] = trim( $a[1] );
			}
			$out[] = "loop_ \n" . implode( " \n", $cols ) . "\n" . implode( ' ', $vals ) . ' ';
		}
		$str = implode( "\n# \n", $out );
	}

	//- すでにある？
	if ( file_exists( $fn_cif ) && !FLG_REDO ) {
		if ( _gzload( $fn_cif ) == $str )
			continue;
	}
	_comp_save( $fn_cif, $str );	
}

//. end
_comp_save( FN_IDLIST, implode( "\n", $chemidlist ) );
_end();
