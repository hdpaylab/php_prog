<?php

//
// Usage: php addr_tx.php H9wZxQXaM1osKna49FTFeLzF8ecqURX2Z5
//
// 입력된 주소의 모든 송금 내역 출력함 
//

require_once 'hdacrpc.php';


GLOBAL	$args;


init($argv);

main();


//
// $args: [0]=explorer.php [1]= [2]=...
//
function init($argv)
{
	GLOBAL	$args;

	array_shift($argv);
	$args = join($argv, ' ');

	preg_match_all('/ (--\w+ (?:[= ] [^-]+ [^\s-] )? ) | (-\w+) | (\w+) /x', $args, $match);
	$args = array_shift($match);

	// hdacrpc.cfg 읽기
	$config=read_config();

	// 기본 사용 name 선정 (hdacrpc.cfg 참조)
	$chain = "hdac-test";
	$chain = "hdac-mainnet";
	set_hdac_chain($config[$chain]);

	// 시간 측정..
//	$ret = hdac("listblocks", "1");
//	$lasttime = $ret["result"][0]["time"];
}


function main()
{
	GLOBAL	$args;

	$count = $args[1];
	if ($count <= 0)
		$count = 100000;

	$ret = hdac("listaddresstransactions", "${args[0]}", (int)$count, 0, true);
	$result = $ret["result"];

	print "Date,Txid,FromAddr,Amount,ToAddr,SendAmount,FromBalance\n";
	for ($nn = 0; $nn < 100000; $nn++)
	{
		$rec = $result[$nn];
		if ($rec == "")
			break;

		$timestamp = date("Y-m-d H:i:s", $rec["time"]);
		$txid = $rec["txid"];

		$vin_addrs = $rec["vin"][0]["addresses"][0];
		$vin_amount = $rec["vin"][0]["amount"];

	//	print "VIN: "; print_r ($vin_addrs); print "\n";
	//	print "$vin_addrs : $vin_amount\n";

		print "$timestamp,$txid,$vin_addrs,$vin_amount, ";
		for ($nout = 0; $nout < 40000; $nout++)
		{
			$vout_addrs = $rec["vout"][$nout]["addresses"][0];
			if ($vout_addrs == "")
				break;
			$vout_amount = $rec["vout"][$nout]["amount"];
			if ($vin_addrs == $vout_addrs)
			{
				print "$vout_amount,";
			}
			else
			{
			//	print "VOUT: "; print_r ($vout_addrs); print "\n";
				print "$vout_addrs,$vout_amount, ";
			}
		}
		print "\n";
	}

}


?>
