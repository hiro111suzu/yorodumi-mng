<?php
require 'dbdic-common.php';
//
//. donwload

_line( 'dic ダウンロード', 'mmCIF' );
_comp_save( FN_MMCIF_DIC, file_get_contents( U_MMCIF ) );

_line( 'dic ダウンロード', 'SASCIF' );
_comp_save( FN_SAS_DIC,   file_get_contents( U_SAS   ) );

_end();
