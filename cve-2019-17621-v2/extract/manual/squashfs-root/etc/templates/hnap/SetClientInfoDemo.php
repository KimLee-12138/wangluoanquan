HTTP/1.1 200 OK
Content-Type: text/xml; charset=utf-8

<?
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/inf.php";
include "/htdocs/phplib/phyinf.php";
include "/htdocs/webinc/config.php";
echo "\<\?xml version='1.0' encoding='utf-8'\?\>";

function parse_xml_node($string, $tag_name)
{
	$tag_len = strlen($tag_name);
	$tag_index = strstr($string, "<".$tag_name.">");
	$tagtail_index = strstr($string, "</".$tag_name.">");
	$str_offset_index = $tag_index + $tag_len + 2;
	$str_len =  $tagtail_index - $str_offset_index;
	return substr($string, $str_offset_index, $str_len);
}

$nodebase="/runtime/hnap/SetClientInfoDemo/";
$result="OK";

$dhcps4_lan1 = get("", INF_getinfpath($LAN1)."/dhcps4");
$dhcps4_lan2 = get("", INF_getinfpath($LAN2)."/dhcps4");
$dhcps4_lan1_path = XNODE_getpathbytarget("/dhcps4", "entry", "uid", $dhcps4_lan1, "0");
$dhcps4_lan2_path = XNODE_getpathbytarget("/dhcps4", "entry", "uid", $dhcps4_lan2, "0");
$max_dhcps4_lan1_staticleases = get("", $dhcps4_lan1_path."/staticleases/max");
$max_dhcps4_lan2_staticleases = get("", $dhcps4_lan2_path."/staticleases/max");
$networkid_lan1 = ipv4networkid(INF_getcurripaddr($LAN1), INF_getcurrmask($LAN1));
$networkid_lan2 = ipv4networkid(INF_getcurripaddr($LAN2), INF_getcurrmask($LAN2));

foreach($nodebase."ClientInfoLists/ClientInfo")
{
	$MacAddr	= tolower(get("", "MacAddress"));
	$NickName	= get("", "NickName");
	$ReserveIP	= get("", "ReserveIP");
	$AudioMute	= get("", "SupportedAction/AudioMute");
	$AudioEnable= get("", "SupportedAction/AudioEnable");
	$SmartPlugEnable= get("", "SupportedAction/SmartPlugEnable");
	$ZWaveSmartPlug= get("", "SupportedAction/ZWaveSmartPlug");
	TRACE_debug("$AudioMute=".$AudioMute." $ZWaveSmartPlug=".$ZWaveSmartPlug);
	if($AudioMute!="" || $AudioEnable!="")
	{
		$AudioMute_Action = $AudioMute;
		$AudioMute_client_path = XNODE_getpathbytarget("/runtime/mydlink/userlist", "entry", "macaddr", $MacAddr, "0");
		$AudioMute_IP = get("", $AudioMute_client_path."/ipv4addr");
		TRACE_debug("$AudioMute_client_path=".$AudioMute_client_path);
		TRACE_debug("$AudioMute_IP=".$AudioMute_IP);
		if($AudioMute_Action=="") {$AudioMute_Action = "false";}
		if($AudioEnable=="") {$AudioEnable = "true";}
	}
	if($SmartPlugEnable!="")
	{
		$SmartPlugEnable_client_path = XNODE_getpathbytarget("/runtime/mydlink/userlist", "entry", "macaddr", $MacAddr, "0");
		$SmartPlugEnable_IP = get("", $SmartPlugEnable_client_path."/ipv4addr");
		TRACE_debug("$SmartPlugEnable_client_path=".$SmartPlugEnable_client_path);
		TRACE_debug("$SmartPlugEnable_IP=".$SmartPlugEnable_IP);
	}
	foreach("/runtime/mydlink/userlist/entry")
	{
		if(PHYINF_macnormalize($MacAddr)==PHYINF_macnormalize(get("", "macaddr")))
		{
			$HostName = get("", "hostname");
			$ipv4addr = get("", "ipv4addr");
			break;
		}
	}
	$InLAN1 = 0;
	$InLAN2 = 0;
 	if(ipv4networkid($ipv4addr, INF_getcurrmask($LAN1))==$networkid_lan1) $InLAN1=1;
	if(ipv4networkid($ipv4addr, INF_getcurrmask($LAN2))==$networkid_lan2) $InLAN2=2;
	if($InLAN1==1)
	{
		anchor($dhcps4_lan1_path."/staticleases");
		$dhcps4_staticleases_path = $dhcps4_lan1_path."/staticleases";
		$hostid = ipv4hostid($ReserveIP, INF_getcurrmask($LAN1));
	}
	else if($InLAN2==1)
	{
		anchor($dhcps4_lan2_path."/staticleases");
		$dhcps4_staticleases_path = $dhcps4_lan2_path."/staticleases";
		$hostid = ipv4hostid($ReserveIP, INF_getcurrmask($LAN2));
	}

	if($InLAN1==1 || $InLAN2==1)
	{
		$count = get("", "count");
		$seqno = get("", "seqno");
		if($count == "")$count=1;
		if($seqno == "")$seqno=1;
		$dhcps4_staticleases_entry_path =  XNODE_getpathbytarget($dhcps4_staticleases_path, "entry", "macaddr", $MacAddr, 0);
		if($dhcps4_staticleases_entry_path != "") //Modified old setting.
		{
			if($NickName!="" && $ReserveIP!="")
			{
				set($dhcps4_staticleases_entry_path."/enable", "1");
				set($dhcps4_staticleases_entry_path."/description", $NickName);
				set($dhcps4_staticleases_entry_path."/hostname", $HostName);
				set($dhcps4_staticleases_entry_path."/hostid", $hostid);
			}
			else if($NickName!="")
			{
				set($dhcps4_staticleases_entry_path."/enable", "0");
				set($dhcps4_staticleases_entry_path."/description", $NickName);
				del($dhcps4_staticleases_entry_path."/hostname");
				del($dhcps4_staticleases_entry_path."/hostid");
			}
			else if($ReserveIP!="")
			{
				set($dhcps4_staticleases_entry_path."/enable", "1");
				del($dhcps4_staticleases_entry_path."/description");
				set($dhcps4_staticleases_entry_path."/hostname", $HostName);
				set($dhcps4_staticleases_entry_path."/hostid", $hostid);
			}
			else if($NickName=="" && $ReserveIP=="") //Remove the entry setting
			{
				del($dhcps4_staticleases_entry_path);
				$count--;
				set("count", $count);
			}
		}
		else //Add new setting.
		{
			if($count < get("", "max"))
			{
				if($NickName=="" && $ReserveIP=="") continue;//Don't save anything.

				$count++;
				set("entry:".$count."/uid", "STIP-".$seqno);
				set("entry:".$count."/macaddr", $MacAddr);
				if($NickName!="" && $ReserveIP!="")
				{
					set("entry:".$count."/enable", "1");
					set("entry:".$count."/description", $NickName);
					set("entry:".$count."/hostname", $HostName);
					set("entry:".$count."/hostid", $hostid);
				}
				else if($NickName!="")
				{
					set("entry:".$count."/enable", "0");
					set("entry:".$count."/description", $NickName);
				}
				else if($ReserveIP!="")
				{
					set("entry:".$count."/enable", "1");
					set("entry:".$count."/hostname", $HostName);
					set("entry:".$count."/hostid", $hostid);
				}
				set("count", $count);
				set("seqno", $seqno+1);
			}
		}
	}
}

fwrite("w",$ShellPath, "#!/bin/sh\n");
fwrite("a",$ShellPath, "echo \"[$0]-->Client Info Changed\" > /dev/console\n");
TRACE_debug("$ShellPath=".$ShellPath." $AudioMute_Action=".$AudioMute_Action." $AudioMute_client_path=".$AudioMute_client_path."  $AudioMute_IP=".$AudioMute_IP);
set("/runtime/test/clientip", $AudioMute_IP);
set("/runtime/test/audiomute", $AudioMute_Action);
if($result == "OK")
{

	fwrite("a",$ShellPath, "event DBSAVE > /dev/console\n");
	if($AudioMute_Action!="")
	{
		$getaudioresult = fread("s", "/var/getaudioresult");
		$MediaName = parse_xml_node($getaudioresult, "MediaName");
		if($MediaName == "") {$MediaName = "DCH-M225";}
		fwrite("a",$ShellPath, "wget  http://".$AudioMute_IP."/HNAP1/ -O /tmp/a  --header 'SOAPACTION: \"http://purenetworks.com/HNAP1/SetAudioRenderSettings\"'  --header 'Authorization: Basic YWRtaW46' --header 'Content-Type: text/xml' --post-data '<?xml version=\"1.0\" encoding=\"utf-8\"?><soap:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\"><soap:Body><SetAudioRenderSettings xmlns=\"http://purenetworks.com/HNAP1/\"><AirPlay>".$AudioEnable."</AirPlay><DLNA>".$AudioEnable."</DLNA><AudioCablePlug></AudioCablePlug><MediaName>".$MediaName."</MediaName><AudioMute>".$AudioMute_Action."</AudioMute></SetAudioRenderSettings></soap:Body></soap:Envelope>' \n");
	}
	if($SmartPlugEnable=="true")
	{
		fwrite("a",$ShellPath, "/etc/events/hnapSP.sh setSPstatus ".$SmartPlugEnable_IP." on\n");
	}
	else if($SmartPlugEnable=="false")
	{
		fwrite("a",$ShellPath, "/etc/events/hnapSP.sh setSPstatus ".$SmartPlugEnable_IP." off\n");
	}
	if($ZWaveSmartPlug=="On")
	{
		fwrite("a",$ShellPath, "sh /etc/events/OZW_SWITCH_ON_OFF.sh 1\n");
	}
	else if($ZWaveSmartPlug=="Off")
	{
		fwrite("a",$ShellPath, "sh /etc/events/OZW_SWITCH_ON_OFF.sh 0\n");
	}
	fwrite("a",$ShellPath, "service DHCPS4.LAN-1 restart\n");
	fwrite("a",$ShellPath, "service DHCPS4.LAN-2 restart\n");
	fwrite("a",$ShellPath, "xmldbc -s /runtime/hnap/dev_status '' > /dev/console\n");
	set("/runtime/hnap/dev_status", "ERROR");
}
else
{
	fwrite("a",$ShellPath, "echo \"We got a error in setting, so we do nothing...\" > /dev/console\n");
}
?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <SetClientInfoDemoResponse xmlns="http://purenetworks.com/HNAP1/">
      <SetClientInfoDemoResult><?=$result?></SetClientInfoDemoResult>
    </SetClientInfoDemoResponse>
  </soap:Body>
</soap:Envelope>