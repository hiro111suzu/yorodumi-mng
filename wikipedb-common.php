<?php
require_once( "wikipe-common.php" );

//. define
//.. fdata
define( 'QUOTE_MARK', '__<<q>>__' );
define( 'DN_F_WIKIPE', DN_FDATA. '/wikipe' );

define( 'FN_LANGLINKS', DN_F_WIKIPE. '/jawiki-latest-langlinks.sql.gz' );
define( 'FN_REDIRECT', DN_F_WIKIPE. '/enwiki-latest-redirect.sql.gz' );

define( 'FN_JA_STUB_XML' , DN_F_WIKIPE. '/jawiki-latest-stub-articles.xml.gz' );
define( 'FN_EN_STUB_XML' , DN_F_WIKIPE. '/enwiki-latest-stub-articles.xml.gz' );

//.. sqlite
define( 'FN_DB_ID2EN' , DN_WIKIPE. '/id2en.sqlite' );
//define( 'FN_DB_ID_RDCT'   , DN_WIKIPE. '/i2_rdct.sqlite' );
define( 'FN_DB_E2J'   , DN_WIKIPE. '/e2j.sqlite' );

define( 'FN_DB_ID2EN'    , DN_WIKIPE. '/id2en.sqlite' );

define( 'FN_DB_EN_TITLE' , DN_WIKIPE. '/en_title.sqlite' );
define( 'FN_DB_E2J'      , DN_WIKIPE. '/e2j.sqlite' );

/*

*/
