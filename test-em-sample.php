<?php
require_once( "commonlib.php" );
$cryo = [];
$non_cryo = [];
foreach ( _idloop( 'emdb_new_json' ) as $fn ) {
	$id = _fn2id( $fn );
	_m();
	$flg_cryo = false;
	foreach ( _emdb_json3_rep( _json_load2( $fn ) )->structure_determination as $c ) {
		foreach ( $c->preparation as $c2 ) {
			$n = $c2->vitrification->cryogen_name;
			if ( $n != '' && $n != 'NONE' ) {
				$flg_cryo = true;
				break;
			}
		}
		$tmp_a = [];
		foreach ( $c->microscopy as $c2 ) {
//			_pause([ $id => $c2->temperature ]);
			$avg = $c2->temperature->temperature_average;
			$min = $c2->temperature->temperature_min;
			$max = $c2->temperature->temperature_max;
			$avg2 = $min && $max ? ( $min + $max ) /2 : false;
			$tmp_a[] = $avg ?: $avg2 ?: $min ?: $max;
		}
		$tmp = round( array_sum( $tmp_a ) / count( $tmp_a ) / 10 ) * 10;
		++ $data[ $flg_cryo ? 'cryo' : 'non-cryo' ][ $tmp ?: '(^_^)' ];
/*
		if ( 200 < $tmp && $flg_cryo )
			_m( "$id: おかしい" );
*/
		if ( 0 < $tmp && $tmp < 200 && !$flg_cryo )
			_m( "$id: $tmp" );

	}
	++ $data[ _imp( $va ) ?: '(^_^)' ];
}
ksort( $data[ 'cryo' ] );
ksort( $data[ 'non-cryo' ] );
_kvtable( $data[ 'cryo' ], 'cryo' );
_kvtable( $data[ 'non-cryo' ], 'non-cryo' );




/*
	$a = _emdb_json3_rep( _json_load2( $fn ) )->admin;
	if ( $a->keywords )
		_m( "$id: ". $a->keywords );
		continue;

/*
//	_pause( $a );
	if ( ! is_array( $a ) )  {
		++ $data[ 'no_proc tag' ];
	}
	$res = [];
	foreach ( $a as $k => $v ) {
		$res[] = (float)$v->final_reconstruction->resolution;
	}
	$c1 = count( $res );
	$c2 = count( array_unique( $res ) );
	++ $data[ "$c1 => $c2" ];
	if ( $c2 != 1 )
		_m( "$id: $c1 -> $c2: ". _imp( $res ) );
/*
	$c = _json_load2( $fn )->map->contour[0]->source ?: '-';
	++ $data[ $c ];
	if ( $c == 'SOFTWARE' ) _m( "$id: SOFTWARE" );
//	_m( $c );
//	_pause( _json_load2( $fn )->map->contour[0]->level );
/*
	$c = _json_load2( $fn )->map->contour;
//	if ( ! $c ) continue;
	$cnt = count( $c );
	if ( $cnt != 1 ) _m( "$id: $cnt" );
	++ $data[ $cnt ];
*/;

/*
$data = [];
foreach ( _idloop( 'emdb_new_json' ) as $fn ) {
	$id = _fn2id( $fn );
	foreach ( _json_load2( $fn )->structure_determination[0] as $k => $v ) {
//		_pause( $k );
		foreach ([
			'single_particle_' ,
			'singleparticle_' ,
			'subtomogram_averaging_' ,
			'helical_' ,
			'tomography_' ,
			'crystallography_' ,
		] as $s ) {
			if ( _instr( $s, $k ) ) {
				if ( is_object( $v ) ) {
					++ $data[ "$k:obj" ];
					break;
				}
				$n = count( $v );
				if ( $n != 1 )
					_m( "$id - $k - $n items" );
				else
					++ $data[ "$k:single" ];
				break;
			}
		}
//		if ( _instr( 'singleparticle_', $k ) ) {
//			 _m( "$id - $k ?????????????" );
	}

//	if ( $n != 1 )
//		_m( "$id: $n" );
//	else
//		_m();
}
_kvtable( $data ) ;

/*
foreach ( _idlist( 'emdb' ) as $id ) {
	$in  = _fn( 'prevq50_old', $id );
	$out = _fn( 'prevq50', $id ) ;
	if ( file_exists( $in  ) )
		rename( $in, $out);
	else
		_m( "no file $in" );
//	rename( _fn( 'prevq50_old', $id ), _fn( 'prevq50', $id ) );
}

foreach ( _idloop( 'filelist' ) as $in ) {
	_count( 'emdb' );
	$id = _fn2id( $in );
	rename( $in, _fn( 'filelist_new', $id ) );
}

copy( 'ftp://ftp.ebi.ac.uk/pub/databases/chembl/UniChem/data/wholeSourceMapping/src_id3/src3src6.txt.gz', 'chemid2kegg.txt.gz' );

/*
$ids = $cnt = [];
$data = [];

foreach ( _idlist( 'emdb' ) as $id ) {
	$json = _json_load2( _fn( 'emdb_new_json', $id ) );
	foreach ( (array)$json->interpretation->additional_map_list->additional_map as $j ) {
//		++ $data[ $j->annotation_details ];
//		++ $data[ strlen( $j->annotation_details ) ];
		$d = $j->annotation_details;
		if ( 100 < strlen( $d ) )
			_m( "$id: $d" );
	}
	foreach ( (array)$json->interpretation->half_map_list->half_map as $j ) {
//		++ $data[ $j->annotation_details ];
//		++ $data[ strlen( $j->annotation_details ) ];
		$d = $j->annotation_details;
		if ( 100 < strlen( $d ) )
			_m( "$id: $d" );

	}
}

//krsort( $data );
//_kvtable( $data );


/*
foreach ( _idloop( 'chem_json' ) as $fn ) {
	_count( 5000 );
	$id = _fn2id( $fn );
	$json = _json_load2( $fn );
	foreach ( $json->chem_comp as $k => $v ) {
		if ( $v == '' ) continue;
		$ids[ $k ][] = $id;
		++ $cnt[ $k ];
	}
}
arsort( $cnt );
//kvtable( $cnt );

$data = [];
foreach ( $cnt as $k => $num ) {
	if ( 5000 < $num )
		$data[ $k ] = $num;
	else
		$data[ $k ] = $num. ' - '. _imp( array_slice( $ids[ $k ], 0, 10 ) );
}
_kvtable( $data );


/*
$a = '0';
$b = '';
echo( $a == $b ? '同じ' : '違う');
echo('');



/*
//. main loop
$out = '';
foreach ([
	"A"=>[154,166,204,1],
	"B"=>[141,204,141,1],
	"C"=>[204,154,160,1],
	"D"=>[204,204,102,1],
	"E"=>[204,154,204,1],
	"F"=>[141,192,192,1],
	"G"=>[204,166,90,1],
	"H"=>[192,102,102,1],
	"I"=>[196,178,143,1],
	"J"=>[0,153,204,1],
	"K"=>[164,74,74,1],
	"L"=>[82,164,136,1],
	"M"=>[123,164,40,1],
	"N"=>[190,104,190,1],
	"O"=>[0,165,167,1],
	"P"=>[0,204,102,1],
	"Q"=>[48,143,90,1],
	"R"=>[0,0,111,1],
	"S"=>[151,146,86,1],
	"T"=>[0,80,0,1],
	"U"=>[102,0,0,1],
	"V"=>[102,102,0,1],
	"W"=>[102,0,102,1],
	"X"=>[0,102,102,1],
	"Y"=>[147,107,9,1],
	"Z"=>[142,27,27,1],
	"0"=>[0,204,102,1],
	"1"=>[48,143,90,1],
	"2"=>[0,0,111,1],
	"3"=>[151,146,86,1],
	"4"=>[0,80,0,1],
	"5"=>[102,0,0,1],
	"6"=>[102,102,0,1],
	"7"=>[102,0,102,1],
	"8"=>[0,102,102,1],
	"9"=>[147,107,9,1]
] as $cid => $ar ) {
	$out .= 'color #'. _hex( $ar[0] ). _hex( $ar[1] ). _hex( $ar[2] )
		. " :.$cid"
		. ( is_numeric( $cid ) ? '' : '|:.'. strtolower( $cid ) )
		. "\n"
	;
}
_m( $out );

function _hex( $in ) {
	return substr( '0'. dechex( $in ), -2 );
}

/*
$data = [];
foreach ( _idloop( 'pdb_json' ) as $fn ) {
	if ( _count( 'pdb', 0 ) ) break;
	foreach ( (array)_json_load2( $fn )->pdbx_audit_support as $c ) {
		++ $data[ $c->country ];
	}
}
arsort( $data );
_kvtable( $data );

//file_put_contents( 'temp.txt', base64_decode( file_get_contents('temp.base64') ) );


//_xxmail( 'hirofumi@protein.osaka-u.ac.jp', 'test', 'testtest', '' );



/*
	if ( ! $r ) continue;
	if ( '2017-03-31' < $r && $r < '2018-04-01' ) {
//		if ( $a->database == 'PDB' )
//		_pause( $a->id . ': ' . $r );
		++ $cnt;
	}
}
_m( $cnt );
//_m( strtr( 'hoge', [] ) );
/*
foreach ( _idloop( 'chem_json' ) as $fn ) {
	$id = _fn2id( $fn );
	$syn = _json_load2( $fn )->chem_comp->pdbx_synonyms;
	if ( _instr( ', ', $syn ) )
		_m( "$id: $syn" );
}

/*


//foreach ( _idloop( 'pdb_json' ) as $fn ) {
foreach ( _idloop( 'qinfo' ) as $fn ) {
	if ( _count( 'pdb', 0 ) ) _pause();
	$id = _fn2id( $fn );
	foreach ( (array)_json_load2( $fn )->ref as $a ){
		list( $d, $i ) = $a;
		_cnt( strtoupper( trim( $d ) ) );
	}

/*
	$json = _json_load2( $fn )->ref;
	foreach ( (array)$json as $c ) {
		if ( strtoupper( trim( $c->db_name ) ) != 'PDB' ) continue;
		$i = strtolower( trim( $c->db_code ?: pdbx_db_accession ));
		_cnt( 'total' );
		if (  $i == $id ) {
			_m( "$id: self" );
			_cnt( 'same');
		} else {
			_m( "$id != $i", 'red' );
			_cnt( 'dif');
		}
			
/*
		if ( in_array( strtoupper( trim( $c->db_name )), [
			'PDB' ,
			'UNP' ,
//			'NDB' ,
//			'TREMBL' ,
			'GB' , 
			'EMBL' ,
//			'NOR' ,
//			'PIR' ,
//			'REF' ,
//			'GENP' ,
//			'PRF'

		])) continue;
		
		_show( $c, $id );
	}
}		
_cnt();
function _show( $o, $id ) {
	$a = (array)$o;
	unset( $a[ 'pdbx_seq_one_letter_code' ] );
	unset( $a[ 'pdbx_align_begin' ] );
	unset( $a[ 'id' ] );
	_kvtable( $a, $id );
}
*/
