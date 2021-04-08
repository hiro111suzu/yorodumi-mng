//- どの画像をエントリの代表画像にするか決定
//- assemblyの小さい番号優先、author-definedがあればそれ、なければ登録構造

<?php
require_once( "commonlib.php" );
require_once( "pdbimg-common.php" );
define( 'FLG_DEL', $argv[1] == 'del' );


//. 各ディレクトリ
//.. dep
_count();
foreach ( _idloop( 'img_dep' ) as $fn ) {
	_count( 'pdb' );
	_main( $fn, _fn2id( $fn ) . ": (img dep) -", "$id-dep" );
}

foreach ( _idloop( 'img_rep' ) as $fn ) {
	_count( 'pdb' );
	_main( $fn, _fn2id( $fn ) . ": (img rep) -"  );
}

foreach ( _idloop( 'img_asb' ) as $fn ) {
	_count( 'pdb' );
	list( $id, $asb ) = explode( '_', _fn2id( $fn )  );
	_main( $fn, "$id: (asb #$asb) -", "$id-$asb"  );
}

//.. asb

//. main func
function _main( $fn, $name, $di_done = '' ) {
	$sz = filesize( $fn );
	if ( 1000 < $sz ) return;
	_problem( "$name image too small" );
	if ( ! FLG_DEL ) return;
	_del( $fn );
	if ( $id_done )
		_del( _fn( 'que_done', $id_done ) );
}

//. que file
foreach ( _idloop( 'que_done' ) as $fn ) {
	_count( 'pdb' );
	$name = _fn2id( $fn );
	list( $id, $asb ) = explode( '-', $name, 2 );
	$fn_img = $asb == 'dep'
		? _fn( 'img_dep', $id )
		: _fn( 'img_asb', $id. '_'. $asb )
	;
	if ( file_exists( $fn_img ) ) continue;

	_problem( "$id: ($asb) no img file" );
	if ( FLG_DEL )
		_del( $fn );
}


_end();
