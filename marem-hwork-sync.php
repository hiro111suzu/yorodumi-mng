<?php
//. init
require_once( "commonlib.php" );
require_once( 'marem-common.php' );
define( 'DN_HWORK', '/work/hwork' );
define( 'DN_UNZIP_MAP', '/data/unzipped_maps' );

_only_marem();

//. rsync
passthru( 'rsync -avz --delete --progress /data/yorodumi/fdata/hwork/ '. DN_HWORK . '/' );

//. シンボリックリンク
exec( 'rm -f '. DN_UNZIP_MAP. '/*.map' );

foreach ( glob( DN_HWORK. '/*/*.map' ) as $pn ) {
	exec( "ln -fs $pn ". DN_UNZIP_MAP. '/'. basename( $pn ) );
}

