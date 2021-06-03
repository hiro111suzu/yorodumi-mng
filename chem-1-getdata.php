kf1から、公開前のchemデータを取得

<?php
require_once( "commonlib.php" );

//. rsync
_rsync([
	'from'	=> [ 'pub/pdb/data/monomers/components.cif.gz', 'lvh2' ],
	'to'	=> DN_FDATA
]);
_rsync([
	'from'	=> [ 'pub/pdb/data/monomers/components_iupac.cif', 'lvh2' ] ,
	'to'	=> DN_FDATA
]);
_rsync([
	'from'	=> [ 'pub/pdb/data/monomers/aa-variants-v1.cif', 'lvh2' ] ,
	'to'	=> DN_FDATA
]);

_end();
