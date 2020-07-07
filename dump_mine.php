<?php

// Usage:
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
	$chain = "hdac-mainnet";
	set_hdac_chain($config[$chain]);

	// 시간 측정..
//	$ret = hdac("listblocks", "1");
//	$lasttime = $ret["result"][0]["time"];
}


function main()
{
	GLOBAL	$args, $prev_txtime;

	$ret = hdac("listblocks", "-1");
	$lasttime = $ret["result"][0]["time"];

	if (count($args) > 0 && $args[0] == "-rescan")
		$startblock = 0;
	else if (count($args) > 0 && $args[0] > 0)
		$startblock = (int)$args[0];
	else
	{
		$linev = file("mine.csv");
		if (count($linev) > 1000)
		{
			$lastline = $linev[count($linev) - 1];
			$flds = preg_split("/,/", $lastline);
			$startblock = $flds[2] + 1;
			$prev_txtime = $flds[5];
		}
	}
	print "last block of mine.csv = $startblock\n";

	$fp = fopen("mine.csv", "a+");

	$ret = hdac("getinfo");
	$blocks = $ret["result"]["blocks"];

	print "last block of hdac = $blocks\n";

	for ($bidx = $startblock; $bidx <= $blocks; $bidx++)
	{
		block($fp, $bidx);
	}

	fclose($fp);
}


function block($fp, $block)
{
	$ret = hdac("getblock", "$block");
	$block = $ret["result"];

	$txhtml = calc_mining_tx($fp, $block);
}


function calc_mining_tx($fp, $block)
{
	GLOBAL	$prev_txtime;

	$numtx = count($block["tx"]);
	$miner = $block["miner"];
	$txlist = $block["tx"];
	$blksize = $block["size"];
	$height = $block["height"];

	$rewards = $total_vout = $txtime = 0;

	for ($ntx = count($txlist) - 1; $ntx >= 0; $ntx--)
	{
		$vinaddrs = $voutaddrs = "";
		$vin_sum = $vout_sum = 0;

		$txid = trim($txlist[$ntx]);
		if ($txid == "")
			continue;

		$ret = hdac("getrawtransaction", "$txid", 1);
		$result = $ret["result"];

		$confirm = $result["confirmations"];
		$txtime = $result["time"];
		$timestamp = date("Y-m-d H:i:s", $result["time"]);

		$vinobj = $result["vin"];
		if (@$vinobj[0]["coinbase"] == "")
			continue;	// Mining tx 아니면 skip

		$rewards = 0;
		$voutobj = @$result["vout"];
		for ($nn = 0; $nn < count($voutobj); $nn++)
		{
			$value = $voutobj[$nn]["value"];
			if ($value > $rewards)
				$rewards = $value;
		}
		$vinaddrs = "coinbase: ".$vinobj[0]["coinbase"];

		$vout_sum = 0;
		for ($ii = 0; $ii < 100; $ii++)
		{
			$voutobj = @$result["vout"][$ii];
			if ($voutobj == "")
				break;
			$addrs = @$voutobj["scriptPubKey"]["addresses"];
//print "ADDR="; print_r ($addrs); print "<p>";
			for ($nn = 0; $nn < count($addrs); $nn++)
			{
				$voutaddrs .= "${addrs[$nn]}|";
			}
			$total_vout += @$voutobj["value"];
			$vout_sum += $voutobj["value"];
		}
		$voutaddrs = substr($voutaddrs, 0, strlen($voutaddrs) - 1);

		break;	// 마이닝 블록은 1개
	}

	if ($prev_txtime > 0)
		$diffsec = $txtime - $prev_txtime;
	else
		$diffsec = "-";
	$rewards = sprintf("%8.3f", $rewards);
	$vout_sum = sprintf("%8.3f", $vout_sum);
	$diffsec = sprintf("%4d", $diffsec);
	$numtx = sprintf("%4d", $numtx);
	$blksize = sprintf("%5.1f", $blksize / 1024.0);

	fprintf($fp, "MINE,$miner,$height,$rewards,$vout_sum,$txtime,$timestamp,$diffsec,$numtx,$blksize,\n");
	print "$timestamp  $miner  $height  ${diffsec}s  ${numtx}tx  ${blksize}kb  $rewards  $vout_sum\n";

	$prev_txtime = $txtime;
}


?>
