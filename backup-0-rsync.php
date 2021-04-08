<?php
//. init
include 'commonlib.php';
define( 'DN_MNG' , realpath( __DIR__ ) );
define( 'DN_BKUP', realpath( __DIR__ . '/../backup' ) );
define( 'DN_MIRROR', DN_BKUP. '/mng_mirror' );
_mkdir( DN_MIRROR );
passthru( 'rsync -avz --delete '. DN_MNG. '/ '. DN_MIRROR. '/' );

