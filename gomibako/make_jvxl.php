<?php
//. init
/*
zipコマンド
zip outfile infile1 infile2 ...
*/

require_once( "commonlib.php" );
_initlog( "make jvxl files" );

$jmol = ( php_uname( "n" ) == 'novem' )
	? 'java -jar /novdisk2/softwares-64/jmol/Jmol.jar '
	: '/usr/java/latest/bin/java -jar /aprdisk2/softwares-64/jmol/Jmol.jar '
;
$scr = "$_rootdn/managedb/mkjvxl.cmd";

//$zip = 'C:/work/zip.exe';
//$jdata = "j:/jdata/";

//. main loop
foreach ( $emdbidlist as $id ) {
	if ( _idfilter( $id ) ) continue;

	$ddn = "$_jdata/$id";
	$jfn = "$ddn/o1.jvxl";
	$ofn = "$ddn/o1.obj";
	$zfn = "$ddn/o1.zip";
	$mfn = "$ddn/movie1.flv";
	echo '.';

	$fl = 1;
	//- 既にある？
	if ( file_exists( $zfn ) ) 
		$fl = 0;
		
	//- ポリゴンデータがない？
	if ( ! file_exists( $ofn ) ) {
		if ( file_exists( $mfn ) )
			echo "\n$id - No polygon data!\n";
		$fl = 0;
	}

	if ( $fl ) {
		echo $id;
		chdir( $ddn );
		_exec( "$jmol -ionx -s $scr" );
		if ( ! file_exists( $jfn ) )
			echo " - error!";
		echo "\n";
	}

	if ( file_exists( $jfn ) and ( ! file_exists( $zfn ) ) ) {
		_exec( "zip o1.zip o1.jvxl" );
		echo "$id : zipped\n";
	}
}

