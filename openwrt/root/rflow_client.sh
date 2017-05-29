#!/bin/sh

while [ 1 ]; 
do
	if [ `ps | grep -c fprobe` == 0 ]; then
		echo "Launching fprobe";
		RFLOWHOST=`uci get system.wr.rflowHost`;
		RFLOWPORT=`uci get system.wr.rflowPort`;
		fprobe -i br-lan -fip $RFLOWHOST:$RFLOWPORT
	fi
	sleep 1;
done
