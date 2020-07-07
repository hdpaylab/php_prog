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

	@mkdir("txdump", 0755);

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

	for ($blkno = $startblock; $blkno <= $lastblock; $blkno++)
	{
		block($blkno);
	}
}


function block($blkno)
{
	$ret = hdac("getblock", "$blkno");
	$block = $ret["result"];
	$txlist = $block["tx"];

	$fileno = (int) ($blkno / 1000);
	$fname = sprintf("txdump/hdac-%03d.json", $fileno);
	$fp = fopen($fname, "ab");

	$timestamp = date("Y-m-d H:i:s", $block["time"]);
	$numtx = count($block["tx"]);

	print "block #$blkno numtx=$numtx $timestamp\n";

	for ($nn = 0; $nn < $numtx; $nn++)
	{
		$txid = $txlist[$nn];
		$ret = hdac("getrawtransaction", $txid, 4);
		$txjson = $ret["result"];

		fprintf($fp, "\n{\n  \"block\": %d,\n  \"ntx\":%d,\n  \"txid\":$txid,\n  %s\n}\n\n\n", 
			$blkno, $nn + 1, json_encode($txjson, JSON_PRETTY_PRINT));
	}

	fclose($fp);
}


?>
