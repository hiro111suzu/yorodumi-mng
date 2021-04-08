<?php
//. init
require_once( "commonlib.php" );

$id_orig = 7639;
$list = <<<EOD
7661
7662
7663
7664
7665
7666
7667
7668
7669
7670
7671
7672
7673
7674
7675
7676
7677
7678
7679
7680
7681
7682
7683
7684
7685
7686
7687
7688
7689
7690
7691
7692
7693
7694
7695
7696
7697
7698
7699
7700
7701
7702
7703
7704
7705
7706
7707
7708
7709
7710
7711
7712
7713
7714
7715
7716
7717
7718
7719
7721
7722
7723
7724
7725
7726
7727
7728
7729
7730
7731
7732
7733
7734
7735
7736
7737
7738
7739
7740
7741
7742
7743
7744
7745
7746
7747
7748
7749
7750
7751
7752
7753
7754
7755
7756
7757
7758
7759
7760
7761
7762
7763
7764
7765
7766
7767
7768
EOD;

$fn_cmd =_tempfn( 'cmd' );
$cmd = <<<EOD
vstep 3;
saveobj;
stop noask;
EOD;


define( 'CMD_CHIMERA', DISPLAY . 'chimera --geometry +0+0 ' );

//. main
$bn = 'temp-pg.cmd';
foreach ( explode( "\r\n", $list ) as $id_dest ) {
	if ( ! $id_dest ) continue;
	_m( $id_dest );
	chdir( _fn( 'emdb_med', $id_dest ) );
	passthru( 'pwd' );
	file_put_contents( $bn, $cmd );
	_exec( CMD_CHIMERA. 's2.py '. $bn );
	_del( $bn );
//	_php( 'cp_session', "$id_orig $id_dest" );
//	_pause( 'p' );
}


