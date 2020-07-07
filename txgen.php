<?php

//
// Usage: php txgen.php
//
// Worldmap의 테스트용 TX 생성 프로그램 
//

require_once 'hdacrpc.php';


GLOBAL	$args;


main();


function main()
{
	$linev = file("_test_ip");
	for ($ii = 0; $ii < count($linev); $ii++)
	{
		$linev[$ii] = trim($linev[$ii]);
	}

	print "Time,From Address,To Address,From IP,To IP,Amount\n";
	for ($ii = 0; $ii < 10000; $ii++)
	{
		$tm = time() - 86400 + $ii * 10;
		$datestr =  date("Y-m-d H:i:s", $tm);
		$rnd1 = rand() % count($linev);
		$rnd2 = rand() % count($linev);

		$aa1 = rand() % 255;
		$aa2 = rand() % 255;
		$aa3 = rand() % 255;
		$aa4 = rand() % 255;
		$fromip = "$aa1.$aa2.$aa3.$aa4";

		$bb1 = rand() % 255;
		$bb2 = rand() % 255;
		$bb3 = rand() % 255;
		$bb4 = rand() % 255;
		$toip = "$bb1.$bb2.$bb3.$bb4";

		$amount = (rand() % 10000) / 100;

		print "$datestr,${linev[$rnd1]},${linev[$rnd2]},$fromip,$toip,$amount\n";
	}
}


?>
