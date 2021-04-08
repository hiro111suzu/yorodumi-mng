pubmed-jsonを作成

<?php
//. init

require_once( "commonlib.php" );
_mkdir( DN_DATA . '/pubmed' );

//.. cost
define( 'MON_NUM', [
	'Jan' => '01' ,
	'Feb' => '02' ,
	'Mar' => '03' ,
	'Apr' => '04' ,
	'May' => '05' ,
	'Jun' => '06' ,
	'Jul' => '07' ,
	'Aug' => '08' ,
	'Sep' => '09' ,
	'Oct' => '10' ,
	'Nov' => '11' ,
	'Dec' => '12' ,
]);

define( 'USA_ST', [
	'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'DC', 'FL', 'GA', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY'
]);

$ids_no_date_info = [];

define( 'COUNTRY_NAMES', _file( DN_EDIT. '/country_names.txt' ) );
define( 'CITY_NAMES', _tsv_load2( DN_EDIT. '/affi_rep.tsv' )[ 'city_names' ] );

$reg_cnames = implode( '|', COUNTRY_NAMES );

define( 'REP_AFFI', [
	'/<?[a-zA-Z0-9\._\-]+@[a-zA-Z0-9\._\-]+>?/'  => '', //- メールアドレス

	'/^1\]/'					=> '',
	'/Electronic address:/i'	=> '',

	//- 末尾
	
	'/[\s\.:;,]+$/'					=> '' ,
	'/[\s\.:;,]+\[[0-9]+\]$/'		=> '' , //- 末尾の参照番号
	'/[\s\.:;,]+and$/'				=> '', 
	'/[\s\.:;,]+$/'				=> '' ,
	'/,\s?([0-9]+)$/'				=> ' $1' ,

	'/('. $reg_cnames. ') ([0-9\-]+)$/'		=> '$2, $1' ,
	'/ ('. $reg_cnames. ')$/'		=> ', $1' ,

	//- 国別
	'/people\'s republic of china/i' => 'China',
	'/P\.? ?R\.? China/'			=> 'China' ,
	'/ prc$/i'						=> ' China' ,
	'/[\s\.:;,]+ROC$/'				=> ', China' ,

	'/russian federation/i'			=> 'Russia' ,
	'/United States of America/'	=> 'USA' ,
	'/United Sates/'				=> 'USA' ,
	'/U\.S\.A/i'					=> 'USA' ,
	'/(PA|CA|NY|) USA$/'			=> '$1, USA' ,
	
	'/U\.K$/i'						=> 'UK' ,
	'/United Kingdo./'				=> 'UK' ,
	'/United Kingdom/'				=> 'UK' ,
	'/(England|Scotland)$/'			=> '$1, UK' ,
	'/Republic of Korea/'			=> 'Korea' ,
	'/South Korea/'					=> 'Korea' ,

	'/Orsay France/'				=> 'Orsay, France' ,
	
	'/\bJapana\b/'						=> 'Japan' ,
	'/the Netherlands/i'			=> 'Netherlands' ,
	'/Canada ([0-9A-Z]+ [0-9A-Z]+)$/'	=> '$1, Canada' ,
	'/Canada ([0-9A-Z]+)$/'			=> '$1, Canada' ,
	'/([0-9A-Z]+) Canada$/'			=> '$1, Canada' ,

	'/Viet Nam/'					=> 'Vietnam' ,


	//- 最終調整
	'/,+/'							=> ',',
	'/\s+/' 						=> ' ' ,
	'/[\s\.:;,_]+$/'				=> '' ,
]);

define( 'ADD_COUNTRY', [
	'Hong Kong/'	=>  'China' ,
]);

//. main loop
foreach ( _idloop( 'pubmed_xml', 'pubmed xml->json' ) as $fn_in ) {
	_cnt( 'total' );

	//.. タイムスタンプ比較
	$id = _fn2id( $fn_in );
	$fn_out = _fn( 'pubmed_json', $id );
	if ( _same_time( $fn_in, $fn_out ) ) continue;

	//.. 準備
	$out = [];
	$xml = simplexml_load_file( $fn_in );
	$xc = $xml->PubmedArticle->MedlineCitation;
	$xa = $xc->Article;
	$xj = $xa->Journal;
	$xi = $xj->JournalIssue;

	//.. ID
	foreach ( $xml->PubmedArticle->PubmedData->ArticleIdList->children() as $n => $v )
		$out[ 'id' ][ (string)$v[ 'IdType' ] ] = (string)$v;

	//.. article
	//... journal, issue, etc
	$out[ 'journal' ] = (string)$xj->ISOAbbreviation ?: (string)$xj->Title ;
	$out[ 'id' ][ 'issn' ] = (string)$xj->ISSN;

	$page = (string)$xa->Pagination->MedlinePgn;
	if ( $page ) {
		list( $first, $last ) = explode( '-', $page );
		$diff = strlen( $first ) - strlen( $last );
		if ( $diff && $last )
			$page = $first. '-'. substr( $first, 0, $diff ). $last;
	}

	$out[ 'issue' ] = implode( ', ', array_filter([
		_ifnn( $xi->Volume			, 'Vol. \1'		) ,
		_ifnn( $xi->Issue			, 'Issue \1'	) ,
		_ifnn( $page				, 'Page \1'	) ,
		_ifnn( $xi->PubDate->Year	, 'Year \1'	)
	]) );
	
	$out[ 'vol'   ] = (string)$xi->Volume;
	$out[ 'isu'   ] = (string)$xi->Issue;
	$out[ 'page'  ] = $page;
	$out[ 'year'  ] = (string)$xi->PubDate->Year;
	$out[ 'title' ] = (string)$xa->ArticleTitle;

	//... date
	list( $date, $date_ok ) = _ymd2date(
		(string)$xi->PubDate->Year ,
		(string)$xi->PubDate->Month ,
		(string)$xi->PubDate->Day
	);
	list( $date2, $date2_ok ) = _ymd2date(
		(string)$xa->ArticleDate->Year ,
		(string)$xa->ArticleDate->Month ,
		(string)$xa->ArticleDate->Day 
	);
	list( $date3, $date3_ok ) = _ymd2date(
		(string)$xc->DateCompleted->Year ,
		(string)$xc->DateCompleted->Month ,
		(string)$xc->DateCompleted->Day
	);
	
	$date4 = '';
	if ( $xi->PubDate->MedlineDate ) {
		list( $y, $m ) = explode( ' ', $xi->PubDate->MedlineDate );
		list( $date4, $dummy ) = _ymd2date(
			$y,
			explode( '-', $m, 2 )[0],
			'??'
		);
	}

	$out[ 'date' ] = $date_ok ?: $date2_ok ?: $date3_ok
		?: $date ?: $date2 ?: $date3 ?: $date4
	;

	if ( strtotime( $out[ 'date' ] ) === false ) {
		_m( "$id: no date info - ". $out[ 'date' ], 'red' );
		print_r( $xi->PubDate );
		$ids_no_date_info[] = $id;
	}


	//... author + affi
	$affi1 = '';
	foreach ( $xa->AuthorList->Author as $c1 ) {
		$out[ 'auth' ][] = $n = "{$c1->ForeName}{$c1->FirstName} {$c1->LastName}";
		foreach ( $c1->AffiliationInfo as $c2 ) {
			$a = _reg_rep( trim( $c2->Affiliation, ' .;:' ), REP_AFFI );

			//- アメリカの住所
			list( $st, $num ) = explode( ' ', preg_replace( '/.+, /', '', $a ) );
			if (
				is_numeric( trim( strtr( $num, [ '-' => '' ]), '; ' ) )
				&& in_array( $st, USA_ST ) 
			)
				$a = trim( $a, '.' ) . ', USA';

			if ( ! in_array( _affi2country( $a ), COUNTRY_NAMES ) ) {
				foreach ( CITY_NAMES as $k => $v ) {
					if ( preg_match( '/\b'. $k. '\b/', $a ) !== 1 ) continue;
					$a .= ", $v";
					break;
				}
			}
			if ( ! $affi1 && $a )
				$affi1 = $a;
			$out['affi'][ $n ][] = $a;
		}
		if ( $o = (string)$c1->Identifier )
			$out['orcid'][ $n ] = _reg_rep( $o, [ '/.*\//' => '' ] );
	}
	if ( $affi1 )
		$out['affi']['auth1'] = $affi1;

	//... abst
	$num = 1;
 	foreach ( (object)$xa->Abstract->AbstractText as $k => $v ) {
 		$l = is_object( $v ) ? (string)$v->attributes()->Label : '';
 		if ( $l == 'UNLABELLED' || $l == '' ) {
 			$l = $num;
 			++ $num;
 		}
 		$out[ 'abst' ][ $l ] = (string)$v;
	}
 
	//- コピーライト （余分なマークを消す）
	$c = (string)$xa->Abstract->CopyrightInformation;
	if ( $c != '' ) {
		$out[ 'abst' ][ 'Copyright' ] = strtr(
			$c, [ 'Copyright ' => '', '©' => '' ] );
	}

	//... Chemical
	$o = [];
	if ( $xc->ChemicalList != '' ) foreach( $xc->ChemicalList->Chemical as $c1 ) {
		$name = (string)$c1->NameOfSubstance;
		$o[ $name ][ 'name' ] = $name;

		$i = (string)$c1->RegistryNumber;
		if ( $i != '0' && $i != '' ) {
			$o[ $name ][ 'id' ] = $i;
			if ( substr( $i, 0, 3 ) == 'EC ' ) {
				$o[ $name ][ 'id' ] = strtr( $i, [ 'EC ' => '' ] );
				$o[ $name ][ 'db' ] = 'ec';
			} else if ( _instr( '-', $i ) ) {
				$o[ $name ][ 'db' ] = 'cas';
			} else {
				//- unknown
				$o[ $name ][ 'db' ] = 'uk';
			}
		}
	}

	//... Mesh Heading
	if ( $xc->MeshHeadingList != '' ) foreach( $xc->MeshHeadingList->MeshHeading as $c1 ) {
		$name = (string)$c1->DescriptorName;

		$o[ $name ][ 'name' ] = $name;
		$q = (string)$c1->QualifierName;
		if ( $q != '' )
			$o[ $name ][ 'q' ] = $q;
	}

	//... まとめ
	if ( count( $o ) ) {
		ksort( $o );
		$out[ 'kw' ] = array_values( $o );
	}

	//.. output
	if ( _json_save( $fn_out, $out ) ) {
		_m( "$id: pubmed-json作成" );
		touch( $fn_out, filemtime( $fn_in ) );
	} else {
		_problem( "$id: pubmed-json作成失敗" );
	}
	_cnt( 'converted' );
}

//. 要らなくなったjsonファイル削除
foreach ( _idloop( 'pubmed_json', '不要ファイル削除' ) as $fn ) {
	if ( file_exists( _fn( 'pubmed_xml', _fn2id( $fn ) ) ) ) continue;
	_del( $fn );
	_m( "[要らないファイルを削除] $fn " );
}

//. 国名チェック
$data = [];
$non_place_term = [
	' Biology', 'Center for ', 'Research', 'Department '
];


foreach ( _idloop( 'pubmed_json', '国名チェック' ) as $fn ) {
	$id = _fn2id( $fn );
	$affi = [];
	foreach ( (array)_json_load( $fn )['affi'] as $a ) {
		$affi = array_merge( $affi, is_array( $a ) ? $a : [ $a ] );
	}
	foreach ( _uniqfilt( $affi ) as $a ) {
		$c = _affi2country( $a );
		if ( in_array( $c, COUNTRY_NAMES ) ) continue;
		foreach ( $non_place_term as $term ) {
			if ( _instr( $term, $c ) ) continue 2;
		}
		++ $data[ $c ];
	}
}
arsort( $data );
//_kvtable( $data );
$out = '';
foreach ( $data as $ctr => $num ) {
	$out .= "$num\t$ctr\n";
}
_comp_save( DN_PREP. '/pubmed_countries.tsv', $out );

//. end
_end();

//. function
//.. _ymd2date 日付の文字列作成
function _ymd2date( $y, $m, $d ) {
	if ( $y . $m . $d == '' )
		return [ '', '' ];
	$r = implode( '-', [
		$y ?: '????',
		is_numeric( $m ) ? substr( "0$m", -2 ) : ( MON_NUM[$m] ?: $m ?: '??' ) ,
		substr( "0$d", -2 ) ?: '??' ,
	]);
	return [ $r, _instr( '?', $r ) ? '' : $r ];
}

//.. _affi2country
function _affi2country( $affi ) {
	return trim( _reg_rep( $affi, [ '/^.*,\s?/' => '' ] ) );
}

