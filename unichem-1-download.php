<?php
include "commonlib.php";

//. weget
$dn = DN_FDATA. '/unichem';
_mkdir( $dn );
exec( "rm -f $dn/src3*" );
$url = 'ftp://ftp.ebi.ac.uk/pub/databases/chembl/UniChem/data/wholeSourceMapping/src_id3/';
$cl = "wget --recursive --no-directories -P $dn $url";
_m( "wget開始、コマンドライン: $cl" );
exec( $cl );

//. 
_download( "ftp://ftp.ebi.ac.uk/pub/databases/chembl/UniChem/data/wholeSourceMapping/src_id1/src1src3.txt.gz", "$dn/src1src3.txt.gz" );

_download( "ftp://ftp.ebi.ac.uk/pub/databases/chembl/UniChem/data/wholeSourceMapping/src_id2/src2src3.txt.gz", "$dn/src2src3.txt.gz" );

_end();
