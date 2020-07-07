<?php

//
// Usage: php addr_tx.php H9wZxQXaM1osKna49FTFeLzF8ecqURX2Z5 depth
//
// 입력된 주소의 모든 송금 내역 출력함 
//

require_once 'hdacrpc.php';


GLOBAL	$args, $trace_cache;
GLOBAL	$wallet_cache, $nth_wallet, $amount_limit;




init($argv);

trace_addr(1, $args[0], $args[1], $args[2]);

trace_balance($trace_cache);


//
// $args: [0]=trace_addr.php [1]=ADDRESS [2]=OPTION
//
function init($argv)
{
	GLOBAL	$args, $amount_limit;

	$nth_wallet = 0;

	$amount_limit = 100000;		// 100,000 coin 이상의 거래만 출력 

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


function trace_addr($depth, $addr, $depth_limit, $txcount)
{
	GLOBAL	$trace_cache, $amount_limit;


	if ($trace_cache[$addr] > 0)
		return;
	$trace_cache[$addr] = $depth;

	if ($depth_limit == "")
		$depth_limit = 1;
	if ($depth > $depth_limit)
		return;
	if ($txcount <= 0)
		$txcount = 10;

	print "Depth = $depth_limit\n";
	print "Tx Count = $txcount\n";
	print "\n";

	$txdata = listaddrtxs($addr, $txcount);
	indent($depth, true);
	print "$addr ";
	if (count($txdata) <= 0)
	{
		print "(Last address)\n";
		print "\n";
		return;
	}

	// vin/vout?
	$nvin_sum = $nvout_sum = 0;
	for ($nn = 0; $nn < count($txdata); $nn++)
	{
		$rec = $txdata[$nn];
		if ($rec == "")
			break;
		if ($rec["vin"][0]["addresses"][0] != $addr && $rec["vout"][0]["addresses"][0] != $addr)
			continue;

		$print_addr++;
		if ($print_addr > 1)
			print "    $addr ";
		$timestamp = date("Y-m-d H:i:s", $rec["time"]);
		print "($timestamp) ${rec["txid"]}\n";

		$nvin = $nvout = 0;
		$vin = null;
		for ($ii = 0; $ii < count($rec["vin"]); $ii++)
		{
			$vin_addr = $rec["vin"][$ii]["addresses"][0];
			$vin_amount = $rec["vin"][$ii]["amount"];
			$walletid = find_wallet($vin_addr);
			if ($walletid > 0)
				$walletid = "#$walletid";
			else
				$walletid = "  ";

			indent($depth);
			$wallet = find_wallet($vin_addr);
			if ($amount_limit > 0 && $vin_amount < $amount_limit)
				continue;
			print "  vin:  $vin_addr $walletid ($vin_amount) \n";
			$nvin++;
		}

		$vout = null;
		$same_return = 0;
		for ($ii = 0; $ii < count($rec["vout"]); $ii++)
		{
			$vout_addr = $rec["vout"][$ii]["addresses"][0];
			$vout_amount = $rec["vout"][$ii]["amount"];
			$walletid = find_wallet($vout_addr);
			if ($walletid > 0)
				$walletid = "#$walletid";
			else
				$walletid = "  ";

			if ($amount_limit > 0 && $vout_amount < $amount_limit)
				continue;

			indent($depth);
			if ($nvin == 1 && $ii > 0 && $vout_addr != $addr)
			{
				$wallet = same_wallet($rec["vin"][0]["addresses"][0], $vout_addr);
				print "  vout: $vout_addr #$wallet ($vout_amount) \n";
			}
			else
				print "  vout: $vout_addr $walletid ($vout_amount) \n";
			if ($vout_addr == $addr)
				$same_return = 1;
			$nvout++;
		}
		if ($same_return == 0 && count($rec["vout"]) == 2)
		{
		//	same_wallet($addr, $rec["vout"][1]["addresses"][0]);
		}
		$nvin_sum += $nvin;
		$nvout_sum += $nvout;
	}
	print "\n";

	// trace sub..
	for ($nn = 0; $nn < count($txdata); $nn++)
	{
		$rec = $txdata[$nn];
		if ($rec == "")
			break;

		if ($rec["vin"][0]["addresses"][0] != $addr)
			continue;

		for ($ii = 0; $ii < count($rec["vout"]); $ii++)
		{
			$vout_addr = $rec["vout"][$ii]["addresses"][0];
			trace_addr($depth + 1, $vout_addr, $depth_limit, $txcount);
		}
	}
}


function trace_balance($trace_cache)
{
	print "List address balances:\n";

	foreach ($trace_cache as $addr => $count)
	{
		$result = hdac("getaddressbalances", $addr);
		$result = $result["result"];
		for ($nn = 0; $nn < count($result); $nn++)
		{
			$rec = $result[$nn];
			$qty = $rec["qty"];
			print "  $addr  $qty\n";
		}
	}
	print "\n";
}


function indent($indent, $print_depth = false)
{
	if ($print_depth)
		print sprintf("%2d", $indent);
	else
		print "  ";
	print "  ";
//	for ($nn = 0; $nn < $indent; $nn++)
//		print "  ";
}


function listaddrtxs($addr, $count)
{
	$txdata = null;

	$ret = hdac("listaddresstxs", $addr, (int)$count, 0, true);
	$result = $ret["result"];

	for ($nn = 0; $nn < count($result); $nn++)
	{
		$rec = $result[$nn];
		if ($rec == "")
			break;

		if ($rec["myaddresses"][0] != $addr)	// 자신이 보낸 것만 추적 
			continue;

		$txid = $rec["txid"];
		$timestamp = date("Y-m-d H:i:s", $rec["time"]);

		$txdata[] = $rec;
	}

	return $txdata;

}


function same_wallet($addr, $nextaddr)
{
	GLOBAL	$wallet_cache, $nth_wallet;

	$nth = $wallet_cache[$addr];
	if ($nth <= 0)
		$nth = $wallet_cache[$nextaddr];
	if ($nth <= 0)
	{
		$nth_wallet++;
		$wallet_cache[$addr] = $nth_wallet;
		$wallet_cache[$nextaddr] = $nth_wallet;
		return $nth_wallet;
	}
	else
	{
		$wallet_cache[$addr] = $nth;
		$wallet_cache[$nextaddr] = $nth;
		return $nth;
	}
}


function find_wallet($addr)
{
	GLOBAL	$wallet_cache, $nth_wallet;

	if ($wallet_cache[$addr] > 0)
		return $wallet_cache[$addr];

	return "";
}

?>
