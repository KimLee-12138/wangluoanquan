HTTP/1.1 200 OK
Content-Type: text/xml; charset=utf-8

<?
echo "\<\?xml version='1.0' encoding='utf-8'\?\>";
include "/htdocs/phplib/xnode.php";
include "/htdocs/webinc/config.php";
include "/htdocs/phplib/trace.php";

$rlt = "OK";
$disable = get("","/runtime/hnap/SetNATSettings/Disabled");
$current = query("/device/disable_nat");

if($disable == "true")
{
	set("/device/disable_nat", 1);
	$disable = 1;

	//Disabled WiFi Guest Zone
	$path_phyinf_wlan_24g_guest = XNODE_getpathbytarget("", "phyinf", "uid", $WLAN1_GZ, 0);	
	$path_phyinf_wlan_5g_guest = XNODE_getpathbytarget("", "phyinf", "uid", $WLAN2_GZ, 0);
	set($path_phyinf_wlan_24g_guest."/active","0");
	set($path_phyinf_wlan_5g_guest."/active","0");
}
else
{
	set("/device/disable_nat", 0);
	$disable = 0;
}

if($current!=$disable) $rlt = "REBOOT";

fwrite("w",$ShellPath, "#!/bin/sh\n");
fwrite("a",$ShellPath, "echo [$0] > /dev/console\n");
if($rlt=="REBOOT")
{
	fwrite("a",$ShellPath, "event DBSAVE > /dev/console\n");
}
else if($rlt=="OK")
{
	fwrite("a",$ShellPath, "event DBSAVE > /dev/console\n");
	fwrite("a",$ShellPath, "xmldbc -s /runtime/hnap/dev_status '' > /dev/console\n");
	set("/runtime/hnap/dev_status", "ERROR");
}

?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<SetNATSettingsResponse xmlns="http://purenetworks.com/HNAP1/">
	<SetNATSettingsResult><?=$rlt?></SetNATSettingsResult>
</SetNATSettingsResponse>
</soap:Body>
</soap:Envelope>

