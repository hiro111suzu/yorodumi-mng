<?php
include 'commonlib.php';
$dn = DN_PREP . '/iw1_phplog';
_mkdir( $dn );
define( 'FN_LOG' , "$dn/iw1phplog.txt" );
define( 'FN_HIST', "$dn/hist.txt" );

exec( 'scp pdbj@pdbjiw1-p:/var/log/php_errors.log ' . FN_LOG );
$log  = _file( FN_LOG );
$hist = file_exists( FN_HIST ) ? _file( FN_HIST ) : [];
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
_comp_save( FN_HIST, implode( "\n", array_slice( $hist, -100 ) ) . "\n" );

//- 結果
if ( $flg ) {
	_m( 'エラーあり', 'red');
	_m( $result );
	_mail(
		'iw1 php error' ,
		$result
	);
} else {
	_m( '問題なし', 'blue' );
}


