<?php
require_once( "commonlib.php" );
define( 'FN_LAST_WGET'	, DN_PREP. '/time_emp_last_wget.txt' );
define( 'DN_LOCAL'		, DN_FDATA. '/empiar' );
define( 'URL_EMPIAR'	, 'ftp://empiar.pdbj.org/pub/empiar/archive/' );
define( 'CMD_LINE_WGET'	, 'wget -qr -A .xml -P '. DN_LOCAL. ' '. URL_EMPIAR );
//define( 'PATH_XML'		, DN_LOCAL. '/empiar.pdbj.org/pub/empiar/archive/*/*.xml' );
//- EBI: '/ftp.ebi.ac.uk/pub/databases/empiar/archive/*/*.xml'
//- EBI: ftp://ftp.ebi.ac.uk/pub/databases/empiar/archive/

//. wget
$last = file_get_contents( FN_LAST_WGET );
if ( ( 3600 * 24 ) < time() - $last || $argv[1] == 'f' ) {
	_m( 'wget開始、コマンドライン: '. CMD_LINE_WGET );
	passthru( CMD_LINE_WGET );
} else {
	_m( 'wgetは最後に実行してから1日以内なので、実行しない' );
}
file_put_contents( FN_LAST_WGET, time() );
