#!/bin/sh

opt=""	#-rpcconnect=192.168.1.13 -rpcport=8822 -rpcuser=hdacrpc -rpcpassword=hdac.mainnet"

param="$1"
if [ "$param" = "" ]; then
	param="xx1"
fi

echo "#!/bin/sh\n\nrm -f qty.csv qty/*.qty\n\nopt=\"$opt\"\n\n" > _getqty.sh

awk '{print "cmd=\"hdac-cli $opt hdac getaddressbalances "$1"\"; echo $cmd; $cmd | tee qty/"$1".qty; qty=`grep qty qty/"$1".qty | sed \"s/:/,/\" `; echo "$1",$qty >> qty.csv"}' $param >> _getqty.sh

chmod 755 _getqty.sh
ls -l _getqty.sh
