<?php
include "commonlib.php";
//_m( 
$xml = simplexml_load_string(
		strtr(
			file_get_contents(
				'/data/yorodumi/fdata/emdb-mirror/doc/XML-schemas/emdb-schemas/current/emdb.xsd'
			) ,
			[
				'<xs:' => '<' ,
				'</xs:' => '</'
			]
		)
	)
;
_json_save( DN_REPORT. '/emdb.xsd.json', 
	$xml
);
file_put_contents( DN_REPORT. '/emdb_xsd_json_pretty.txt',
	_json_pretty( $xml )
);


/*
$fn_tsv = DN_EDIT. '/pubmed_id.tsv';
$data = array_merge(
//	[ 'emdb' => _json_load( 'temp_emdb2pmid.json.gz' ), 'pdb' => '' ] ,
	_tsv_load2( $fn_tsv ) ,
	[ 'emdb' => _json_load( 'temp_emdb2pmid.json.gz' ) ]

);
_tsv_save2( $fn_tsv, $data );



$dn = DN_PREP . '/pap';
$_filenames += [
	'emdb_pap'	=> "$dn/emdb/<id>.json" ,
];
$out = [];
foreach ( _idloop( 'emdb_pap' ) as $fn ) {
	$id = _fn2id( $fn );
	$json = _json_load2( $fn );
	$pmid = $json->pmid;
	_cnt( 'total' );
	if ( ! $pmid  ) {
		_cnt( 'unknown' );
		continue;
	}
	if ( substr( $pmid, 0, 1 ) == '_' ) {
		_cnt( 'pap_id' );
		continue;
	}
	if ( _json_load2([ 'emdb_add', $id ])->pmid == $pmid ) {
		_cnt( 'same as xml' );
		continue;
	}
	$out[ $id ] = $pmid;
	_m( "$id: $pmid" );
	_cnt( 'original' );
}
_json_save( 'temp_emdb2pmid.json.gz', $out );
_cnt();

/*
define( 'DN_UNPXML', DN_FDATA . '/unp' );
$_filenames += [
	'unp_xml' => DN_FDATA. '/unp/<id>.xml' ,
	'unp_json' => DN_DATA. '/unp/<id>.json.gz'
];

foreach ( _idloop( 'unp_xml' ) as $fn ) {
	_count( 500 );

	 if ( _instr( 'Reactome', file_get_contents( $fn ) ) ) {
	 	_m( _fn2id( $fn ) );
	 }
}

/*
$only_ids = explode( ',', $argv[1] );
$out = [];

foreach ( glob( _fn( 'pdb_json', '*' ) ) as $fn ) {
	$id = basename( $fn, '.json.gz' );
	_count( 1000 );
//	if ( $only_ids != [] )
//		if ( !in_array( $id, $only_ids ) ) continue;
	foreach ( (array)_json_load2( $fn )->entity_name_com as $c ) {
		$nm = $c->name;
		if ( $nm == '' ) continue;

		$red = 0;
		$len = strlen( $nm );
		foreach ( range( 50, 2 ) as $i ) {
			$p = substr( $nm, 0, floor( $len / $i ) );
			if ( strlen( $p ) < 3 ) continue;
			if ( substr_count( $nm, $p ) != $i ) continue;
			$red = $i;
			break;
		}
		if ( $red == 0 ) continue;
		$eid = $c->entity_id;
		$o = "$id: entity-$eid: $red x \"$p\"";
		_m( $o );
		$out[] = $o;
	}
}
file_put_contents( 'redundant_com_name.txt', implode( "\n", $out ) );


/*
_mkdir( 'fscimg' );
foreach ( $emdbidlist as $id ) {
	foreach ( glob( DN_EMDB_MR . "/structures/EMD-$id/fsc/*" ) as $fn ) {
		$fn = basename( $fn );
		if ( $fn != "emd_{$id}_fsc.xml" )
			_m( "$id: " . basename( $fn ) ) ;

	}
}

/*
$blist = [];
foreach ([
	'192.168.39.187' ,
	'192.168.39.185' ,
	'192.168.39.79',
	'192.168.39.102',
	'192.168.39.103',
	'192.168.39.104',
	'192.168.39.119',
	'192.168.39.120',
	'192.168.39.121',
	'192.168.39.126',
	'192.168.39.140',
	'192.168.39.146',
	'192.168.39.173',
	'192.168.39.175',
	'192.168.39.184',
	'192.168.40.193',
	'133.1.158.161',
	'133.1.158.166',
	'133.1.158.167',
	'133.1.158.168',
	'133.1.158.169',
	'133.1.158.137',
	'133.1.158.139',
	'133.1.158.153',
	'133.1.158.133',
	'127.0.0.1',
	'133.1.158.98',
	'133.1.158.101',
	'133.1.158.102',
	'160.74.25.1',
] as $i ) {
	$blist[ $i ] = true;
}

$previd = '';
$cnt_users;
$data = [];
foreach ( _file( "../httpd-log/lines.txt" ) as $line ) {
	if ( _instr( '.com', $line ) ) continue; 
	$a = explode( ' ', $line, 2 );
	$a = explode( ':', $a[0] );
	$ip = $a[1];
	if ( _instr( '192.168.36.', $ip ) ) {
//		_m( "blist 2", 1 );
		continue;
	}
	if ( $blist[ $ip ] ) {
//		_m( "blist: $ip" );
		continue;
	}
	$id = preg_replace( '/^.+id=([_a-zA-Z0-9\-]+).+$/', '\1', $line );
	if ( $previd == $id ) {
		_m( '同じ' );
		continue;
	}
	$previd = $id;
	_m( $id ); 

	++ $data[ $a[1] ];
	++ $cnt_calc;
}
arsort( $data );

foreach ( $data as $ip => $cnt ) {
	_m( "$ip\t$cnt" );
}

_line( 'sum' );
_m( 'user count: ' . count( $data ) );
_m( 'cals count: ' . $cnt_calc );


$dn = DN_PREP . '/img_que';
foreach ( array_keys( _json_load( DN_DATA . '/ids/ids_ribosome.json.gz' ) ) as $id ) {
	$a = array_merge( glob( "$dn/done/*$id*" ), glob( "$dn/pend/*$id*" ) );
	if ( count( $a ) == 0 )
		_m( "$id: no old que" );
	else 
		_m( "$id: old que" . implode( "\n", $a ) );


//	foreach ( [ 'img', 'img_asb', 'img_dep' ] as $s ) {
//		foreach ( (array)glob( "$dn/$s/$id*.jpg" ) as $fn ) {
//			exec( "mv $fn $dn/_$s/" );
//		}
//	}
}


*/