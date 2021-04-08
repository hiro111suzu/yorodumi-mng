chem_comp_cifから画像

<?php
require_once( "commonlib.php" );
$blacklist = [ 'CB5', 'K3G', 'UNL' ];

//. main
foreach ( _idloop( 'chem_json' ) as $fn_json ) {
	if ( _count( 1000, 0 ) ) die;

	$id = _fn2id( $fn_json );
	if ( in_array( $id, $blacklist ) ) {
		_m( "$id: ブラックリスト" );
		continue;
	}

	$fn_img = _fn( 'chem_img2', $id );

	if ( file_exists( $fn_img ) && filesize( $fn_img ) < 10 ) {
		_m( "$id: 変なファイル、作り直し" );
		_del( $fn_img );
	}

	if ( _newer( $fn_img, $fn_json ) ) continue;

	$smiles = [];
	$j = _json_load2( $fn_json )->pdbx_descriptor;
	if ( $j == [] ) {
		_m( "$id: jsonデータがおかしい" );
	}
	foreach ( $j as $k => $v ) {
		if ( $v->type != 'SMILES' ) continue;
		$smiles[] = $v->descriptor;
	}
	foreach ( $j as $k => $v ) {
		if ( $v->type != 'SMILES_CANONICAL' ) continue;
		$smiles[] = $v->descriptor;
	}
	foreach ( $smiles as $s ) {
		exec( "obabel -:\"$s\" -O $fn_img -xP500" );
		if ( ! file_exists( $fn_img ) ) {
			_problem( "$id: 画像作成失敗、ファイルがない" );
			continue;
		}
		if ( filesize( $fn_img ) < 10 ) {
			unlink( $fn_img );
			_m( "$id: 画像作成失敗、変なファイル", -1 );
			continue;
		}
		break;
	}

}
//. end
_end();
/*
$ obabel -:"Nc1ncnc2n(cc(c3ccc(Oc4ccccc4)cc3)c12)C5CCOCC5" -O out.svg -xP200
*/
