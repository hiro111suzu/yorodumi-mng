<?php
include "commonlib.php";

$ddn = "../roc_work/data";
_mkdir( $pdn = "../roc_work/plot" );

$cmd = <<<EOF
library(ROCR)
rocdata<-read.table("$ddn/omo_all-<gp>.txt")
pred1<-prediction(rocdata[,1],rocdata[,2])
pref1<-performance(pred1,"tpr","fpr" )

rocdata<-read.table("$ddn/gmfit20-<gp>.txt")
pred2<-prediction(rocdata[,1],rocdata[,2])
pref2<-performance(pred2,"tpr","fpr" )

png("$pdn/gm-vs-omo-<gp>.png",width=600,height=600)
plot(pref1, lwd=4 )
par(new=T)
plot(pref2,lty=2,col=4, lwd=3)
legend("bottomright", legend=c("omokage","gmfit"),col=c(1,4),lwd=c(4,3),lty=c(1,2))
dev.off()
EOF;

$cmd2 = <<<EOF
library(ROCR)
rocdata<-read.table("$ddn/omo_all-<gp>.txt")
pred1<-prediction(rocdata[,1],rocdata[,2])
pref1<-performance(pred1,"tpr","fpr" )

rocdata<-read.table("$ddn/omo_30-<gp>.txt")
pred2<-prediction(rocdata[,1],rocdata[,2])
pref2<-performance(pred2,"tpr","fpr" )

rocdata<-read.table("$ddn/omo_50-<gp>.txt")
pred3<-prediction(rocdata[,1],rocdata[,2])
pref3<-performance(pred3,"tpr","fpr" )

rocdata<-read.table("$ddn/omo_out-<gp>.txt")
pred4<-prediction(rocdata[,1],rocdata[,2])
pref4<-performance(pred4,"tpr","fpr" )

rocdata<-read.table("$ddn/omo_pca-<gp>.txt")
pred5<-prediction(rocdata[,1],rocdata[,2])
pref5<-performance(pred5,"tpr","fpr" )

png("$pdn/omo-subsc-<gp>.png",width=600,height=600)
plot(pref1, lwd=4 )
par(new=T)
plot(pref2,lty=2,col=2, lwd=3)
par(new=T)
plot(pref3,lty=3,col=3, lwd=3)
par(new=T)
plot(pref4,lty=4,col=4, lwd=3)
par(new=T)
plot(pref5,lty=5,col=5, lwd=3)

legend("bottomright", legend=c("all","30d","50d","outer","pca"),col=c(1,2,3,4,5),lwd=c(4,3,3,3,3),lty=c(1,2,3,4,5))

dev.off()
EOF;

$cmd3 = <<<EOF
library(ROCR)
rocdata<-read.table("$ddn/omo_30-<gp>.txt")
pred1<-prediction(rocdata[,1],rocdata[,2])
pref1<-performance(pred1,"tpr","fpr" )

rocdata<-read.table("$ddn/omo_30nd-<gp>.txt")
pred2<-prediction(rocdata[,1],rocdata[,2])
pref2<-performance(pred2,"tpr","fpr" )

rocdata<-read.table("$ddn/omo_50-<gp>.txt")
pred3<-prediction(rocdata[,1],rocdata[,2])
pref3<-performance(pred3,"tpr","fpr" )

rocdata<-read.table("$ddn/omo_50nd-<gp>.txt")
pred4<-prediction(rocdata[,1],rocdata[,2])
pref4<-performance(pred4,"tpr","fpr" )

png("$pdn/omo-dif-<gp>.png",width=600,height=600)
plot(pref1, lwd=3 )
par(new=T)
plot(pref2,lty=2,col=2, lwd=3)
par(new=T)
plot(pref3,lty=1,col=3, lwd=3)
par(new=T)
plot(pref4,lty=4,col=4, lwd=3)

legend("bottomright", legend=c("30d","30d-nodif","50d","50d-nodif"),col=c(1,2,3,4),lwd=c(3,3,3,3),lty=c(1,2,1,4))

dev.off()
EOF;


foreach ( [ 'chap', 'ribo', 'comp' ] as $gp ) {
	_line( $gp );
//	_m( $c =  );
	_Rrun( strtr( $cmd, [ '<gp>' => $gp] )  );
	_Rrun( strtr( $cmd2, [ '<gp>' => $gp] )  );
	_Rrun( strtr( $cmd3, [ '<gp>' => $gp] )  );
}

