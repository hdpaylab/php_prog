<?php

//
// Hdac Private Blockchain Demo: ICO Contract
//

require_once 'hdacrpc.php';


init();

echo ico_main();


function init()
{
	GLOBAL	 $style ;

	 $style = "style='padding-left:10;padding-right:10;font-size:12pt;'";

	// hdacrpc.cfg 읽기
	$config=read_config();

	// 기본 사용 name 선정 (hdacrpc.cfg 참조)
	$chain = "hdac-prv";
	set_hdac_chain($config[$chain]);

	// 시간 측정..
//	$ret = hdac("listblocks", "1");
//	$lasttime = $ret["result"][0]["time"];
}

function ico_main()
{
	GLOBAL	$_GET,  $style;

	$op = $_GET["op"];

	if ($op == "charge")
	{
		$fromaddr = $_GET["fromaddr"];
		$toaddr = $_GET["toaddr"];
		$amount = $_GET["amount"];

		charge_token($fromaddr, $toaddr, $amount);
	}

	$html = dashboard(); 

	$html =<<< END_HTML
	<TITLE> Hdac Private Blockchain Demo: ICO Contract </TITLE>
	<link rel="stylesheet" type="text/css" href="style.css">
	<form name=form>
	<script language=javascript>
	<!--
		setTimeout("javascript:Reload();", 3000);

		function Reload()
		{
			location.href = "?";
		}
	//-->
	</script>

	<TABLE width=800>
	<TR>
		<TD align=center style='font-size:16pt'>
			<span style='color:blue'><b>Hdac Private Blockchain</b><br></span>
			(Smart Contrat & Micro Payment)
		</TD>
	</TR>
	</TABLE>
	<p>

	$html

	</form>
END_HTML;

	return $html;
}


function dashboard()
{
	GLOBAL	$stream_cache, $balances;
	GLOBAL	$icoaddr;

	load_streams();

	$adminaddr = $stream_cache["ICO"]["creator"];
	if ($admin == "")
	{
		init_ico();
	}
	$icoaddr = $stream_cache["ICO"]["icoaddr"];

	$total_balance = get_balances($adminaddr);

	$nmember = load_ico_member();

	$bluetext = "<span style='color:blue'>";
	$bglblue = "bgcolor=#EAF2F8";
	$bglcobalt = "bgcolor=#E8F8F5";
	$bglyellow = "bgcolor=#FEF9E7";
	$bglviolet = "bgcolor=#F4ECF7";
	$bglgreen = "bgcolor=#D5F5E3";
	$ltitle = "<span style='color:#D4E6F1'>";
	$ltitle = "<span style='background-color:#D4E6F1'>";
	$redtext = "<span style='color:red;font-weight:bold;'>";


	$html =<<< END_HTML

	<TABLE width=800 cellpadding=3 cellspacing=0 border=0 align=left>
	<TR>
		<TD $style> $bluetext<b>ICO Status</b></span> </TD>
	</TR>
	<TR>
		<TD>
			<TABLE width=100% cellpadding=0 cellspacing=0 border=1 align=left>
			<TR>
				<TD width=200 $style class=list_hdr $bglblue align=center> Items</TD>
				<TD $style $bglblue align=center> <b>Device 1</b> </TD>
				<TD $style $bglblue align=center> <b>Device 2</b> </TD>
			</TR>
			<TR>
				<TD $style class=list_hdr $bglgreen align=center> Address </TD>
				<TD align=center> ${handler[0]} </TD>
				<TD align=center> ${handler[1]} </TD>
			</TR>
			<TR>
				<TD $style class=list_hdr $bglgreen align=center> HDAC*T </TD>
				<TD $style align=center> $redtext $handler_token</span> &nbsp;&nbsp;<a href="$link_charge1">[Charge]</a> </TD>
				<TD $style align=center> $redtext $handler2_token</span> &nbsp;&nbsp;<a href="$link_charge2">[Charge]</a> </TD>
			</TR>
			<TR>
				<TD $style class=list_hdr $bglgreen align=center> Started </TD>
				<TD $style align=center> $handler_time </TD>
				<TD $style align=center> $handler2_time </TD>
			</TR>
			<TR>
				<TD $style class=list_hdr $bglgreen align=center> Status </TD>
				<TD $style align=center> $redtext $status</span> </TD>
				<TD $style align=center> $redtext $status2</span> </TD>
			</TR>
			<TR>
				<TD $style class=list_hdr $bglgreen align=center> Sync data </TD>
				<TD $style align=center> $SYNC_key </TD>
				<TD $style align=center> $SYNC_key2 </TD>
			</TR>
			<TR>
				<TD $style class=list_hdr $bglgreen align=center> Last activated </TD>
				<TD $style align=center> $SYNC_lasttime </TD>
				<TD $style align=center> $SYNC_lasttime2 </TD>
			</TR>
			</TABLE>
		</TD>
	</TR>
	</TABLE>

	<TABLE width=800 cellpadding=3 cellspacing=0 border=0 align=left>
	<TR>
		<TD $style><br> $bluetext<b>Contract Publisher</b></span> </TD>
	</TR>
	<TR>
		<TD>
			<TABLE width=100% cellpadding=0 cellspacing=0 border=1 align=left>
			<TR>
				<TD width=200 $style $bglgreen class=list_hdr align=center> Address </TD>
				<TD colspan=2 align=center> $sender </TD>
			</TR>
			<TR>
				<TD $style class=list_hdr $bglgreen align=center> Micro Payment Address </TD>
				<TD colspan=2 align=center> $payaddr </TD>
			</TR>
			<TR>
				<TD $style class=list_hdr $bglgreen align=center> Token </TD>
				<TD $style align=center $bglcobalt> HDAC*T </TD>
				<TD $style align=left> $redtext $sender_token</span> </TD>
			</TR>
			<TR>
				<TD $style class=list_hdr $bglgreen align=center> Samrt Contract Stream </TD>
				<TD $style align=center $bglcobalt> CT </TD>
				<TD $style align=left> $nct contracts </TD>
			</TR>
			<TR>
				<TD $style class=list_hdr $bglgreen align=center> Last Contract Send Time </TD>
				<TD $style align=center $bglcobalt> CT </TD>
				<TD $style align=left> $CT_sendtime </TD>
			</TR>
			<TR>
				<TD $style class=list_hdr $bglgreen align=center> Authorized Devices </TD>
				<TD $style align=center $bglcobalt> $numdev </TD>
				<TD style='padding-left:10' align=left> $handler_addrs </TD>
			</TR>
			</TABLE>
		</TD>
	</TR>
	</TABLE>

	<TABLE width=800 cellpadding=3 cellspacing=0 border=0 align=left>
	<TR>
		<TD $style><br> $bluetext<b>Admin</b></span> </TD>
	</TR>
	<TR>
		<TD>
			<TABLE width=100% cellpadding=0 cellspacing=0 border=1 align=left>
			<TR>
				<TD width=200 $style class=list_hdr $bglgreen align=center> Address </TD>
				<TD colspan=2 align=center> $admin </TD>
			</TR>
			<TR>
				<TD $style class=list_hdr $bglgreen align=center> Token </TD>
				<TD $style align=center $bglcobalt> HDAC*T </TD>
				<TD $style align=left> $redtext $admin_token</span> </TD>
			</TR>
			<TR>
				<TD rowspan=6 $style class=list_hdr $bglgreen align=center> Streams </TD>
				<TD $style $bglcobalt align=center> Name </TD>
				<TD $style align=center> Created </TD>
			</TR>
			<TR>
				<TD $style $bglcobalt align=center> CT </TD>
				<TD $style align=left> $CT_time </TD>
			</TR>
			<TR>
				<TD $style $bglcobalt align=center> ACTION </TD>
				<TD $style align=left> $ACTION_time </TD>
			</TR>
			<TR>
				<TD $style $bglcobalt align=center> DEVICE </TD>
				<TD $style align=left> $DEVICE_time </TD>
			</TR>
			<TR>
				<TD $style $bglcobalt align=center> SYNC </TD>
				<TD $style align=left> $SYNC_time </TD>
			</TR>
			<TR>
				<TD $style $bglcobalt align=center> LOG </TD>
				<TD $style align=left> $LOG_time </TD>
			</TR>
			</TABLE>
		</TD>
	</TR>
	</TABLE>

END_HTML;

	return $html;

}


function load_streams()
{
	GLOBAL	$stream_cache;

	$js = hdac("liststreams", "*", true);
	$js = $js["result"];
	for ($nn = 0; $nn < count($js); $nn++)
	{
		$rec = $js[$nn];

		$name = $rec["name"];
		$txid = $rec["createtxid"];
		$creator = $rec["creators"][0];
		$icoaddr = $rec["details"]["icoaddress"];
		$rec["creator"] = $creator;
		$rec["icoaddr"] = $icoaddr;
		$rec["txid"] = $txid;
		$stream_cache[$name] = $rec;
	}
}


function load_ico_member()
{
	$nmember = 0;

	$js = hdac("liststreamitems", "ICO", true);
	$js = $js["result"];
	for ($nn = 0; $nn < count($js); $nn++)
	{
		$rec = $js[$nn];

		$key = $rec["key"];
		$publisher = $rec["publishers"][0];
		$nmember++;
		$ICO_cache[$publisher] = $rec;
	}
	return $nmember;
}


function get_balances($addr, $tokenname = "")
{
	GLOBAL	$balances;

	$js = hdac("getaddressbalances", $addr);
	$js = $js["result"];
	for ($nn = 0; $nn < count($js); $nn++)
	{
		$rec = $js[$nn];

		$name = $rec["name"];
		if ($name != $tokenname)
			continue;

		if ($rec["qty"] > 0)
		{
			$balances[$addr][$tokenname] = $rec["qty"];
			break;
		}
	}

	return $balances[$addr][$tokenname];
}


function charge_token($fromaddr, $toaddr, $amount)
{
//	print "===$fromaddr, $toaddr, $amount<p>";

	$js = hdac("sendassetfrom", $fromaddr, $toaddr, "HDACT", (double)$amount);
//	print_r ($js);
}


?>
