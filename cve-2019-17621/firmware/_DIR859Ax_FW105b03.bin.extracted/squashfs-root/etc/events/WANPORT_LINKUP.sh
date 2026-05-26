<?
include "/htdocs/webinc/config.php";
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/phyinf.php";

echo '#!/bin/sh\n';

$stsp = XNODE_getpathbytarget("/runtime", "inf", "uid", $WAN1, 1);
$inet_type = query($stsp."/inet/addrtype");
if($inet_type == "ipv4")
{
	$static = query($stsp."/inet/ipv4/static");
	if($static == "1")
	{
		echo "event WAN-1.CONNECTED\n";
	}
}
if($inet_type == "ppp4")
{
	$status = query($stsp."/pppd/status");
	$process = query($stsp."/pppd/process");
	if($status == "connected")
	{
		echo "event WAN-1.CONNECTED\n";
	}
	if($status == "on demand")
	{
		echo "event WAN-1.PPP.ONDEMAND\n";
	}
	if($process == "PPPoE:PADI" || $process == "authFailed")
	{
		 echo "event WAN-1.PPP.ERROR\n";
	}
}

$path_wan1 = XNODE_getpathbytarget("", "inf", "uid", $WAN1, 0);
$wan1_phyuid  = get("x", $path_wan1."/phyinf");
$runtime_wan1_phy  =  XNODE_getpathbytarget("/runtime", "phyinf", "uid", $wan1_phyuid, 0);
set($runtime_wan1_phy."/linkuptime", get("", "/runtime/device/uptime"));
echo 'exit 0\n';
?>
