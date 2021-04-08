<?php
include 'commonlib.php';

//. exclude
_line( 'exclude リスト作成' );

$fn_exclude = "template/exclude_upload_data.txt";
_comp_save( $fn_exclude, <<<EOF
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

EOF
);


//. 
_line( "EMN データをアップロード" );

chdir( __DIR__ );
foreach ( [ false, true ] as $flg_nf1 ) {
	_line( 'Data upload', $flg_nf1 ? 'nf1' : 'kf1' );
	_rsync( [
		'from'	=> DN_DATA . '/' ,
		'to'	=> _kf1dir( 'pdbj-pre/emnavi/data/', $flg_nf1 ) ,
		'opt'	=> "--exclude-from=$fn_exclude --copy-links" // --dry-run"
	]);
}

