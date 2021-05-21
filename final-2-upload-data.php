<?php
include 'commonlib.php';

_line( "EMN データをアップロード" );

//. rsync
$exclude = <<<EOD
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
EOD;

foreach ([
	'if1'  => 'emnavi/data/' ,
	'lvh1' => '/data/pdbj/data<>/work/emnavi/data/', 
	'bk1'  => '/data/pdbj/data<>/work/emnavi/data/', 
] as $serv => $dn ){
	_rsync([
		'title'		=> "data upload-> $serv" ,
		'from'		=> DN_DATA . '/' ,
		'to'		=> [ $dn, $serv ] ,
		'copylink'	=> true ,
		'exclude'	=> $exclude ,
//		'dryrun'	=> true
	]);
}
_end();
