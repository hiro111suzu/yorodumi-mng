論文DB用準備データを作成
json.gzで出力、そのままでsqliteDBにロードできるように

<?php
require_once( "pap-common.php" );

define( 'JN2ISSN', _json_load( FN_JN2ISSN ) );

//. main
define( 'PMID2DID', _json_load( FN_PMID2DID ) );
_m( "データ数: " . count( PMID2DID ) );

foreach ( PMID2DID as $pmid => $ids ) {
	_cnt( 'total' );
	if ( _count( 5000, 0 ) ) break;
	$fns_pap = [];
	$fns_kw = [];
	$rsos = [];
	//- 
	sort( $ids );
	foreach ( $ids as $did ) {
		list( $db, $id ) = explode( '-', $did );

		if ( $id == '' ) {
			$db = 'sas';
			$id = $did ;
		}

		$fns_pap[] = _fn( $db . '_pap', $id );
		$fns_kw[]  = _fn( $db . '_kw', $id );
	}
	$fn_out = _fn( 'pap_info', $pmid );

	//.. やるかやらないか
	if ( FLG_REDO ) {
		_del( $fn_out );
	} else if ( file_exists( $fn_out ) ) {
		$outtime = filemtime( $fn_out );
		foreach ( array_merge( $fns_pap, $fns_kw ) as $fn ) {
			if ( ! file_exists( $fn ) ) {
				_problem( "ファイルがない pmid = $pmid, fn = $fn" );
				continue;
			}
			if ( $outtime > filemtime( $fn ) ) continue;
			_del( $fn_out );
			break;
		}
	}
	if ( file_exists( $fn_out ) ) continue;

	//.. papデータから
	$data = [ 'ids' => $ids ];
	$date = '';
	$date_type = '';
	foreach ( $fns_pap as $fn ) {
		$j = _json_load( $fn );
		if ( ! is_array( $j ) ) {
			continue;
		}
		foreach ( $j as $k => $v ) {
			if ( $k == 'author' ) {
				if ( count( (array)$data[ 'author' ] ) < count( (array)$v ) )
					$data[ 'author' ] = $v;
			} else if ( is_array( $v ) ) {
				$data[ $k ] = array_merge( (array)$data[$k], $v );
			} else {
				$data[ $k ][] = $v;
			}
		}

		//- date情報 pub情報が優先的に拾われるように
		if ( $j[ 'date_type' ] == 'pub' || $date == '' ) {
			$date = $j[ 'date' ];
			$date_type = $j[ 'date_type' ];
		}
	}
	$data[ 'date_type' ] = $date_type;

	//.. array処理
	foreach ( [ 'method', 'method2', 'kw', 'chemid', 'src' ] as $k ) {
		$data[ $k ] = array_filter( array_unique( (array)$data[ $k ] ) );
	}

	//.. resolution
	if ( $data[ 'reso' ] != '' ) {
		$l = max( $data[ 'reso' ] );
		$h = min( $data[ 'reso' ] );
		$data[ 'reso' ] =  $h == $l ? $h : "$h - $l";
	}

	//.. IF
	$if = 0;
	$issns = [];
	foreach ( (array)$data[ 'issn' ] as $i )
		$issns[] = $i;
	foreach ( (array)$data[ 'journal' ] as $i )
		$issns[] = JN2ISSN[ $i ];
	foreach ( array_unique( array_filter( $issns ) ) as $i ) {
		$v = _is2if( $i );
		if ( $v == '' ) continue;
		$if = $v;
		break;
	}

	//.. score
	//- スコア単位は「日」
	$score = strtotime(
		strtr( $date, [ '????' => '1970', '??' => '01' ] )
	) / ( 60*60*24 ) + 0.375; //- 1970-01-01からの経過日数
	if ( $data[ 'date_type' ] == 'str' ) //- 登録日なら30日おまけ
		$score +=  30;
	if ( $data[ 'date_type' ] == 'exp' ) //- 計測日なら100日おまけ
		$score +=  100;
	$score += round( sqrt( $if ) * 3 ) ; //- IF ボーナス
	$score += ( count( $data[ 'method2' ] ) * 5 ); //- ハイブリッドボーナス
	if ( _instr( 'SARS-CoV-2', $data['title'][0] ) )
		$score += 5; //- コロナボーナス
	if ( substr( $id, 0, 1 ) == '_' ) //- pubmed-IDがなければ-30
		$score -= 20; 

	//.. 複数種ある場合の対処
	foreach ( [ 'journal', 'doi', 'pii', 'title', 'issue', 'issn' ] as $s ) {
		$d = array_unique( array_filter( (array)$data[ $s ] ) );
		$data[ $s ] = $d[0];
		if ( count( $d ) > 1 ) {
			unset( $d[0] );
			$data[ "$s-alt" ] = array_values( $d );
		}
	}
//	_m( json_encode( $data, JSON_PRETTY_PRINT ) );
//	_pause();

	//.. キーワードファイルから
	$kw = (array)$data[ 'kw' ];
	foreach ( (array)$fns_kw as $fn ) {
		if ( ! file_exists( $fn ) ) continue;
		$kw = array_merge( (array)$kw, (array)_file( $fn ) );
	}

	//.. 配列にする
	$out = [
		$pmid ,
		$data[ 'journal' ] ,
		$date ,
		in_array( 'em', (array)$data[ 'method' ] ) ? 1 : 0 ,
		$if ,
		_kwprep( $data[ 'method' ] ) ,
		_kwprep( $kw ) ,
		_kwprep( $data[ 'author' ] ) ,
		$score
	];

	//.. その他の情報、jsonにして
	unset(
		$data[ 'journal' ] ,
		$data[ 'date' ] , 
		$data[ 'pmid' ]
	);
	$out[] = json_encode( array_filter( $data ) );

	//.. 保存
	_json_save( $fn_out, $out );
	_cnt( 'save' );
}
_cnt();

//. なくなったデータ消去
_delobs_misc(
	'pap_info', 
	array_fill_keys( array_keys( PMID2DID ), true ) 
);

_end();

//. func
function _kwprep( $a ) {
	return ' ' . implode( ' | ', array_unique( array_filter( (array)$a ) ) ) . ' ';
}
