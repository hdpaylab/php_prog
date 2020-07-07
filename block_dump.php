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
	GLOBAL	$args;

	if ($args[0] > 0)
		$startblock = $args[0];
	else
		$startblock = 1;

	$ret = hdac("getinfo");
	$lastblock = $ret["result"]["blocks"];

	print "Start block = $startblock\n";
	print "Last  block = $lastblock\n";

	$fp = fopen("block_dump.csv", "a+");

	for ($bidx = $startblock; $bidx <= $lastblock; $bidx++)
	{
		block($fp, $bidx);
	}

	fclose($fp);
}


function block($fp, $bidx)
{
	$ret = hdac("getblock", "$bidx");
	$block = $ret["result"];

	$timestamp = date("Y-m-d H:i:s", $block["time"]);
	$ntx = count($block["tx"]);

	print "block #$bidx ntx=$ntx $timestamp\n";

	fprintf($fp, "%d, %d, %s\n", $bidx, $ntx, $timestamp);
}


?>
