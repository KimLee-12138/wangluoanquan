HTTP/1.1 200 OK
Content-Type: text/xml; charset=utf-8

<?
echo "\<\?xml version='1.0' encoding='utf-8'\?\>";
/*This HNAP action is refer the /htdocs/web/wpsacts.php in dlob.hans */
include "/htdocs/phplib/xnode.php";
include "/htdocs/webinc/config.php";
include "/htdocs/phplib/trace.php";

/* Kit-porting WPS control-Start */
$wps_enable = get("","/runtime/hnap/SetWiFiVerifyAlpha/WPS/enable");
$pbc_en = get("","/runtime/hnap/SetWiFiVerifyAlpha/WPS/ENABLEPBC");
$pin_en = get("","/runtime/hnap/SetWiFiVerifyAlpha/WPS/ENABLEPIN");

$encr_check_wlan1 = XNODE_getpathbytarget("", "phyinf", "uid", $WLAN1, 0);
$encr_check_wlan2 = XNODE_getpathbytarget("", "phyinf", "uid", $WLAN2, 0);
$encr_wifi_wlan1 = XNODE_getpathbytarget("/wifi", "entry", "uid", query($encr_check_wlan1."/wifi"), 0);
$encr_wifi_wlan2 = XNODE_getpathbytarget("/wifi", "entry", "uid", query($encr_check_wlan2."/wifi"), 0);
/* Kit-porting WPS control-End */

$nodebase = "/runtime/hnap/SetWiFiVerifyAlpha/WPS/";

if(query($nodebase."DEV_PIN") != "")
{
	$ACTION = "SAVEPIN";
	$DEVPIN = query($nodebase."DEV_PIN");
	TRACE_info("ACTION=".$ACTION.",DEVPIN=".$DEVPIN);
}
else if(query($nodebase."WPSPIN") != "")
{
	$ACTION = "PIN";
	$PIN = query($nodebase."WPSPIN");
	TRACE_info("ACTION=".$ACTION.",PIN=".$PIN);
}
else if(query($nodebase."WPSPBC") == "1")
{
	$ACTION = "PBC";
	TRACE_info("ACTION=".$ACTION);
}
else if(query($nodebase."ResetToUnconfigured") == "1")
{
	$ACTION = "RESETCFG";
	TRACE_info("ACTION=".$ACTION);
}

//$ACTION = get("", $nodebase."ACTION");
//$PIN = get("", $nodebase."PIN");

function check_pin($pin)
{
	/* more checking added for WPS 2.0
		We allow pin with : xxxx-xxxx
							xxxx xxxx
							xxxxxxxx
	*/
	$len = strlen($pin);
	$delim = "";

	//we support 4 digits
	if($len==4)
	{
		if(isdigit($pin)!=1) { return 0; }
		else 				 { return $pin; }
	}
	if($len==9)
	{
		if(cut_count($pin, "-")==2) 		{ $delim = "-"; }
		else if(cut_count($pin, " ")==2) 	{ $delim = " "; }
		else { return 0; }

		$val1=cut($pin,0,$delim);
		$val2=cut($pin,1,$delim);
		if(strlen($val1)!=4 || strlen($val2)!=4) { return 0; }
		$pin = $val1.$val2;
	}

	if (isdigit($pin)!=1) return 0;
	if (strlen($pin)!=8) return 0;
	$i = 0; $pow = 3; $sum = 0;
	while($i < 8)
	{
		$sum = $pow * substr($pin, $i, 1) + $sum;
		if ($pow == 3)  $pow = 1;
		else            $pow = 3;
		$i++;
	}
	$sum = $sum % 10;
	if ($sum == 0)  return $pin;
	else            return 0;
}

$i = 0;
while ($i < 2)
{
	$i++;
	if($i==1)
	{$uid = $WLAN1;}
	else
	{$uid = $WLAN2;}

    $p = XNODE_getpathbytarget("", "phyinf", "uid",$uid);
    if(get("", $p."/active")=="0") continue;

	if ($ACTION == "PIN")
	{
		$pin = check_pin($PIN);
		if ($pin == 0)	{ $result = "ERROR_PIN";	break; }

		$path = XNODE_getpathbytarget("/runtime", "phyinf", "uid", $uid);
		set($path."/media/wps/enrollee/method", "pin");
		set($path."/media/wps/enrollee/pin", $pin);
		event("WPSPIN");
	}
	else if ($ACTION == "PBC")
	{
		$path = XNODE_getpathbytarget("/runtime", "phyinf", "uid", $uid);
		set($path."/media/wps/enrollee/method", "pbc");
		set($path."/media/wps/enrollee/pin", "00000000");
		event("WPSPBC.PUSH");
	}
	else if ($ACTION == "SAVEPIN")
	{
		$pin = check_pin($DEVPIN);
		if ($pin == 0)  { $result = "ERROR_PIN";        break; }
		set("/runtime/devdata/pin",$pin);

		event("DBSAVE");
	}
	else if ($ACTION == "RESETCFG")
	{
		$encr_check_wlan1 = XNODE_getpathbytarget("", "phyinf", "uid", $WLAN1, 0);
		$encr_check_wlan2 = XNODE_getpathbytarget("", "phyinf", "uid", $WLAN2, 0);
		$encr_wifi_wlan1 = XNODE_getpathbytarget("/wifi", "entry", "uid", query($encr_check_wlan1."/wifi"), 0);
		$encr_wifi_wlan2 = XNODE_getpathbytarget("/wifi", "entry", "uid", query($encr_check_wlan2."/wifi"), 0);
		set($encr_wifi_wlan1."/wps/configured", "0");
		set($encr_wifi_wlan2."/wps/configured", "0");
	}
	else
	{
		$result = "ERROR_ACTION";
		break;
	}
	$result = "OK";
}

fwrite("w",$ShellPath, "#!/bin/sh\n");
fwrite("a",$ShellPath, "echo \"[$0]--> Trigger WPS\" > /dev/console\n");
if($result=="OK")
{
	fwrite("a",$ShellPath, "xmldbc -s /runtime/hnap/dev_status '' > /dev/console\n");
	set("/runtime/hnap/dev_status", "ERROR");
}
/* Kit-porting WPS control-Start */
else if ($wps_enable == "true")
{
	if($encr_wifi_wlan1!=""){set($encr_wifi_wlan1."/wps/enable", 1);}
	if($encr_wifi_wlan2!=""){set($encr_wifi_wlan2."/wps/enable", 1);}
	if($pbc_en=="0")
		set("/device/features/enablepbc", "0");
	else
		set("/device/features/enablepbc", "1");
	if($pin_en=="0")
		set("/device/features/enablepin", "0");
	else
		set("/device/features/enablepin", "1");
	$result = "SUCCESS";
}
else if ($wps_enable == "false")
{
	if($encr_wifi_wlan1!=""){set($encr_wifi_wlan1."/wps/enable", 0);}
	if($encr_wifi_wlan2!=""){set($encr_wifi_wlan2."/wps/enable", 0);}
	$result = "SUCCESS";
}
/* Kit porting WPS-End */
else
{
	fwrite("a",$ShellPath, "echo \"We got a error in setting, so we do nothing...\" > /dev/console");
}

?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xmlns:xsd="http://www.w3.org/2001/XMLSchema"
	xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
	<soap:Body>
		<SetWiFiVerifyAlphaResponse xmlns="http://purenetworks.com/HNAP1/">
			<SetTriggerWPSResult><?=$result?></SetTriggerWPSResult>
			<WaitTime>3</WaitTime>
		</SetWiFiVerifyAlphaResponse>
	</soap:Body>
</soap:Envelope>
