<?php
include 'commonlib.php';
$dn = DN_PREP . '/nw1_phplog';
_mkdir( $dn );
$fn_log  = "$dn/nw1phplog.txt";
$fn_hist = "$dn/hist.txt";

exec( 'scp pdbj@nw1:/var/log/php_errors.log ' . $fn_log );
$log  = _file( $fn_log );
$hist = file_exists( $fn_hist ) ? _file( $fn_hist ) : [];
$data = [];
$flg = false;

//- emnavi関連のみ抽出
foreach ( $log as $line ) {
	if ( ! _instr( '/emnavi/', $line ) ) continue;
	$msg = preg_replace( '/\[.+?\] /', '', $line );
	++ $data[ $msg ];
}

//- 集計
$result = '';
foreach ( $data as $err => $cnt ) {
	if ( ! in_array( $err, $hist ) ) {
		$result .= "[$cnt x] $err\n";
		$hist[] = $err;
		$flg = true;
//		_m( '新エラー' );
	}
}

//- hist保存
_comp_save( $fn_hist, implode( "\n", array_slice( $hist, -100 ) ) . "\n" );

//- 結果
if ( $flg ) {
	_m( 'エラーあり', 'red');
	_m( $result );
	_mail(
		'kw1 php error' ,
		$result
	);
} else {
	_m( '問題なし', 'blue' );
}


