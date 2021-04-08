<?php
include 'commonlib.php';

//. exclude
_line( 'exclude リスト作成' );

$fn_exclude = "template/exclude_upload_data.txt";
_comp_save( $fn_exclude, <<<EOD
*.tar
*.situs
*.mrc
*.map
.emanlog
*.md5
*.pyc
start.py
fit.py
*.cmd
*.ent
*.ent.gz
*.cif.gz
*~
mov_*
pre_*
/temp
Sitemap.xml
mnglog/
gomibako/
/-*/
/omo-tune/
/webwork/
/mnglog/
/temp/
/gz

EOD
);


//. 
_line( "EMN データをアップロード" );

chdir( __DIR__ );
foreach ( [ 'pdbjif1-p' ] as $serv ) {
//foreach ( [ 'nf1', 'if1' ] as $serv ) {
	_rsync( [
		'title'	=> "data upload-> $serv" ,
		'from'	=> DN_DATA . '/' ,
		'to'	=> [ 'emnavi/data/', $serv ] ,
		'opt'	=> "--exclude-from=$fn_exclude --copy-links" // --dry-run"
	]);
}

_end();
