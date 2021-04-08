<?php
require_once( "commonlib.php" );
$dn = DN_FDATA;

_download(
	'ftp://orengoftp.biochem.ucl.ac.uk/cath/releases/daily-release/newest/cath-b-newest-all.gz' ,
	DN_FDATA. '/dbid/cath-b-newest-all.gz'
);
_download(
	'ftp://orengoftp.biochem.ucl.ac.uk/cath/releases/daily-release/newest/cath-b-newest-names.gz' ,
	DN_FDATA. '/dbid/cath-b-newest-names.gz'
);

_end();
