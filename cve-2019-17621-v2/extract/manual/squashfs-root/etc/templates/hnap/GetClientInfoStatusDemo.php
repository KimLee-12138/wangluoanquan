HTTP/1.1 200 OK
Content-Type: text/xml; charset=utf-8

<?
echo "\<\?xml version='1.0' encoding='utf-8'\?\>";
include "/htdocs/phplib/xnode.php";
include "/htdocs/webinc/config.php";
include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/phyinf.php";
include "/htdocs/phplib/inf.php";
$result = "OK";

foreach("/runtime/mydlink/userlist/entry")
{
	$HostName = get("", "hostname");
	$ipv4addr = get("", "ipv4addr");
	if(strstr($HostName, "DAP") != "" || strstr($HostName, "dap") != "" || strstr($HostName, "1320") != "" || strstr($HostName, "DCH") != "" || strstr($HostName, "225") != "")
	{
		$AudioMute_IP = $ipv4addr;
	}
	else if(strstr($HostName, "DSP") != "" || strstr($HostName, "dsp") != "" || strstr($HostName, "215") != "")
	{
		$SmartPlugEnable_IP = $ipv4addr;
	}
}

fwrite("w",$ShellPath, "#!/bin/sh\n");
fwrite("a",$ShellPath, "echo \"[$0]-->GetClientInfoStatusDemo\" > /dev/console\n");
TRACE_debug(" $AudioMute_IP=".$AudioMute_IP." $SmartPlugEnable_IP=".$SmartPlugEnable_IP);
if($AudioMute_IP != "")
{
	fwrite("a",$ShellPath, "wget  http://".$AudioMute_IP."/HNAP1/ -O /var/getaudioresult  --header 'SOAPACTION: \"http://purenetworks.com/HNAP1/GetAudioRenderSettings\"'  --header 'Authorization: Basic YWRtaW46' --header 'Content-Type: text/xml' --post-data '<?xml version=\"1.0\" encoding=\"utf-8\"?><soap:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\"><soap:Body><GetAudioRenderSettings xmlns=\"http://purenetworks.com/HNAP1/\" /></soap:Body></soap:Envelope>' &\n");
}
if($SmartPlugEnable_IP != "")
{
	fwrite("a",$ShellPath, "/etc/events/hnapSP.sh getSPstatus ".$SmartPlugEnable_IP." &\n");
}
fwrite("a",$ShellPath, "xmldbc -s /runtime/hnap/dev_status '' > /dev/console\n");
set("/runtime/hnap/dev_status", "ERROR");
?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<GetClientInfoStatusDemoResponse xmlns="http://purenetworks.com/HNAP1/">
	<GetClientInfoStatusDemoResult><?=$result?></GetClientInfoStatusDemoResult>
</GetClientInfoStatusDemoResponse>
</soap:Body>
</soap:Envelope>
