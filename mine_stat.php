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
	$args = $argv;

	// hdacrpc.cfg 읽기
	$config=read_config();

	// 기본 사용 name 선정 (hdacrpc.cfg 참조)
	$chain = "hdac-mainnet";
	set_hdac_chain($config[$chain]);

	// 시간 측정..
//	$ret = hdac("listblocks", "1");
//	$lasttime = $ret["result"][0]["time"];
}


function sort_by_reward($aa, $bb)
{
	return $bb["reward"] - $aa["reward"];
}


function main()
{
	GLOBAL	$args;

	$baseminer = 2600;
	$closed_mine = $open_mine = $gpu_mine = $day_mine = 0;

	switch ($args[0])
	{
	case "-c":		// CLOSED_MINING (57856~86326)
		array_shift($args);
		$closed_mine = 1;
		break;
	case "-o":		// OPEN_MINING (block 86327~)
		array_shift($args);
		$open_mine = 1;
		break;
	case "-g":		// GPU_MINING
		array_shift($args);
		$gpu_mine = 1;
		break;
	default:		// -N : Latest N hours
		if ($args[0][0] == "-")
		{
			$hour = substr($args[0], 1);
			if ($hour <= 0)
				$hour = 100 * 365 * 24;		// -a
			array_shift($args);
			$day_mine = 1;
		}
		break;
	}

	$linev = @file("_NAME.csv");

	if ($linev != "")
	{
		foreach ($linev as $lineno => $line)
		{
			list($addr, $name) = preg_split("/[,]/", $line);
			$map[$addr] = trim($name);
		}
	}

	$linev = file($args[0]);

	// 87237: MINE / HGoH4m78d17sXLia4C8Ee3R3FTtCCuaPKs / 87237 / 4950.0099 / 5000.01 / 1526708457 / 2018-05-19 14:10:57 /
	$count = $starttime = $endtime = $total_reward = 0;
	foreach ($linev as $lineno => $line)
	{
		$line = trim($line);

		list($tmp, $addr, $height, $pure_reward, $reward, $txtime, $date, $diffsec, $numtx, $blksize, $comment) = preg_split("/[,]/", $line);

		if ($closed_mine == 1)
		{
			if (strstr($comment, "CLOSED_MINING_START") == "")
				continue;
			else 
				$closed_mine = 2;
		}
		if ($closed_mine == 2 && strstr($comment, "CLOSED_MINING_END") != "")
			$closed_mine = 3;

		if ($gpu_mine == 1 && strstr($comment, "GPU") == "")
			continue;
		if ($open_mine == 1 && strstr($comment, "OPEN") == "")
			continue;
		if ($day_mine == 1 && $txtime < (time() - $hour * 3600))
			continue;
		$gpu_mine = $open_mine = $day_mine = 0;

	//	print "$lineno: $addr / blk=$height / $xx / reward=$reward / $txtime / $date / $comment\n";
		$stat[$addr]["count"] += 1;
		$stat[$addr]["reward"] += $reward;
		if ($stat[$addr]["starttime"] == 0 || $stat[$addr]["starttime"] > $txtime)
			$stat[$addr]["starttime"] = $txtime;
		if ($stat[$addr]["endtime"] == 0 || $stat[$addr]["endtime"] < $txtime)
			$stat[$addr]["endtime"] = $txtime;
		if ($starttime == 0)
			$starttime = $txtime;
		$endtime = $txtime;
		$total_reward += $reward;
		$count++;

		if ($closed_mine == 3)
			break;
	}

	if ($count > 0)
		$mining_itv = sprintf("%d", (int) (($endtime - $starttime) / $count));
	else
		$mining_itv = 0;

	$nn = 0;
	if ($stat != "")
	{
		foreach ($stat as $addr => $data)
		{
			$result[$nn]["addr"] = $addr;
			$result[$nn]["count"] = $stat[$addr]["count"];
			$result[$nn]["reward"] = $stat[$addr]["reward"];
			$result[$nn]["starttime"] = $stat[$addr]["starttime"];
			$result[$nn]["endtime"] = $stat[$addr]["endtime"];
			$result[$nn]["per"] = 100.0 * $stat[$addr]["reward"] / $total_reward;
			$nn++;
		}

		// sort 
		usort($result, "sort_by_reward");
	}

	// calc
	for ($nn = 0; $nn < count($result); $nn++)
	{
		$data = $result[$nn];
		$addr = $data["addr"];
		$count = $data["count"];
		$diff = $result[$nn]["diff"] = $data["endtime"] - $data["starttime"];

		$reward = sprintf("%12.1f", $data["reward"]);
		$itv = $result[$nn]["itv"] = (int) ($diff / $count);
		if ($itv <= 0)
			$itv = "0";
		$result[$nn]["itv"] = sprintf("%6d s", (int) $itv);
		if ($addr == "HAFeamK5VmgNhDDEMkeQoDxeFFZLg2bMw3")
			$basereward = $reward;
	}

	// print
	print "              Address                Count Interval   Miner      Reward    Ratio  Name\n";
	$total_blocks = $total_reward = 0;
	for ($nn = 0; $nn < count($result); $nn++)
	{
		$data = $result[$nn];
		$addr = $data["addr"];
		$count = $data["count"];
		$diff = $data["diff"];

		$total_blocks += $count;
		$total_reward += $data["reward"];

		$disp_count = sprintf("%5d", $count);
		$reward = sprintf("%12.1f", $data["reward"]);
		$itv = $data["itv"];
		if ($basereward <= 0)
			$size = $data["size"] = sprintf("%6d", 0);
		else
			$size = $data["size"] = sprintf("%6d", (int)(($reward * $baseminer) / $basereward));
		$total_size += $size;
		$per = sprintf("%5.2f%%", $data["per"]);

		print "$addr  $disp_count  $itv  $size  $reward  $per  ${map[$addr]}\n";
	}
	$nminer = count($result);

	$days = sprintf("%.1f", ($endtime - $starttime) / (24 * 3600));

	print "\nPeriod                  = ".date("Y-m-d H:i", $starttime)." ~ ".date("Y-m-d H:i", $endtime)." ($days days)\n";
	print "\nMining Blocks           = $total_blocks blocks / Total reward = $total_reward DAC\n";
	print "\nEstimated total Miner   = $nminer miners / $total_size GPU mining machines\n";
	print "\nAverage mining interval = $mining_itv s\n";
}


?>
