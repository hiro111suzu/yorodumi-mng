# eman
export EMANDIR=~/EMAN
PATH=$PATH:$EMANDIR/bin
export LD_LIBRARY_PATH=$EMANDIR/lib/
export PYTHONPATH=${EMANDIR}/lib

# spider
### spider & web ###
export SPIDER_DIR="/novdisk2/softwares-64/spider15.10" 
export SPBIN_DIR="$SPIDER_DIR/bin/" 
export SPMAN_DIR="$SPIDER_DIR/man/" 
export SPPROC_DIR="$SPIDER_DIR/proc/" 
export PATH="${SPIDER_DIR}/bin:${PATH}" 

export SPDIR=/novdisk2/softwares-64/spider15.10
alias spider=$SPDIR/bin/spider_linux_mpfftw_opt64

export SPRGB_DIR=/novdisk2/softwares-64/web/rgb
export WEBMAN_DIR=/novdisk2/softwares-64/web/docs

#jmol
#alias jmol='java -jar /novdisk2/softwares-64/jmol/Jmol.jar'

export hogehoge=fugafuga