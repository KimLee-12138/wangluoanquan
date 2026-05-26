<? include "/htdocs/phplib/html.php";
if($Remove_XML_Head_Tail != 1)	{HTML_hnap_200_header();}

include "/htdocs/phplib/xnode.php";
include "/htdocs/webinc/config.php";
include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/phyinf.php";
include "/htdocs/phplib/inf.php";
$result = "OK";

function parse_xml_node($string, $tag_name)
{
	$tag_len = strlen($tag_name);
	$tag_index = strstr($string, "<".$tag_name.">");
	$tagtail_index = strstr($string, "</".$tag_name.">");
	$str_offset_index = $tag_index + $tag_len + 2;
	$str_len =  $tagtail_index - $str_offset_index;
	return substr($string, $str_offset_index, $str_len);
}

function find_dhcps4_staticleases_info($mac, $getinfo, $LAN1, $LAN2)
{
	foreach("/dhcps4/entry")
	{
		$dhcps4_name = get("", "uid");
		foreach("staticleases/entry")
		{
			if(PHYINF_macnormalize($mac)==PHYINF_macnormalize(get("", "macaddr")))
			{
				if($getinfo=="nickname")
				{return get("", "description");}
				else if($getinfo=="reserveip" && get("", "hostid")!="")
				{
					if($dhcps4_name==get("", INF_getinfpath($LAN1)."/dhcps4"))
					{return ipv4ip(INF_getcurripaddr($LAN1), INF_getcurrmask($LAN1), get("", "hostid"));}
					else
					{return	ipv4ip(INF_getcurripaddr($LAN2), INF_getcurrmask($LAN2), get("", "hostid"));}
				}
			}
		}
	}
	return "";
}

function get_clientpath($mac, $LAN1, $LAN2)
{
	/* MAC OS 10.7 would not supply hostname in DHCP process. It could not get the hostname in /runtime/inf(LAN-1)/dhcps4/leases.
	   However, Mydlink service would use Netbios to get the client information include hostname and the information from static client. */
	foreach("/runtime/mydlink/userlist/entry")
	{
		if(PHYINF_macnormalize($mac)==PHYINF_macnormalize(get("", "macaddr")))
		{return "/runtime/mydlink/userlist/entry:".$InDeX;}
	}

	/* If Mydlink service is not supported get the DHCP client information from our DHCP leases. */
	$LAN=$LAN1;
	while($LAN != "")
	{
		$path = XNODE_getpathbytarget("/runtime", "inf", "uid", $LAN, 0);
		foreach($path."/dhcps4/leases/entry")
		{
			if(PHYINF_macnormalize($mac)==PHYINF_macnormalize(get("", "macaddr")))
			{return $path."/dhcps4/leases/entry:".$InDeX;}
		}

		if($LAN==$LAN1)	{$LAN = $LAN2;}
		else			{$LAN = "";}
	}

	return "";
}

function getIPv6AddrByMAC($mac)
{
	$tmp_node_path = "/runtime/cmd";
	$cmd = "ip -6 neigh | grep ".$mac." | scut -n 1";
	
	setattr($tmp_node_path, "get", $cmd);
	$addr_list = get("", $tmp_node_path);
	del ($tmp_node_path);
	
	$ip6_addr_number = cut_count($addr_list, " ");
	$i = 0;
	while ($i < $ip6_addr_number)
	{
		$addr_tmp = cut($addr_list, $i, " ");
		if (strstr($addr_tmp, "fe80::") == "")
		{$addr_global = $addr_tmp;}
		else
		{$addr_ll = $addr_tmp;}
		$i = $i+1;
	}

	if ($addr_global != "")	{return $addr_global;}
	else                    {return $addr_ll;}
}

setattr("/runtime/getclientsinfo/brctl_show", "get", "brctl show > /var/brctl_show");
setattr("/runtime/getclientsinfo/brctl_showmacs_br1", "get", "brctl showmacs br1 > /var/brctl_showmacs_br1");
setattr("/runtime/getclientsinfo/brctl_showmacs_br0", "get", "brctl showmacs br0 > /var/brctl_showmacs_br0");
get("s", "/runtime/getclientsinfo/brctl_show");
get("s", "/runtime/getclientsinfo/brctl_showmacs_br1");
get("s", "/runtime/getclientsinfo/brctl_showmacs_br0");
$brctl_show = fread("s", "/var/brctl_show");
$brctl_showmacs_br1 = fread("s", "/var/brctl_showmacs_br1");
$brctl_showmacs_br0 = fread("s", "/var/brctl_showmacs_br0");
unlink("/var/brctl_show");
unlink("/var/brctl_showmacs_br1");
unlink("/var/brctl_showmacs_br0");
$wlan1_name		= PHYINF_getifname($WLAN1);
$wlan1_gz_name	= PHYINF_getifname($WLAN1_GZ);
$wlan2_name		= PHYINF_getifname($WLAN2);
$wlan2_gz_name	= PHYINF_getifname($WLAN2_GZ);
TRACE_debug("$wlan1_name=".$wlan1_name."\n$wlan1_gz_name=".$wlan1_gz_name."\n$wlan2_name=".$wlan2_name."\n$wlan2_gz_name=".$wlan2_gz_name);

$tailindex	= strstr($brctl_show, "\n")+1;
$tablelen	= strlen($brctl_show);
$line		= substr($brctl_show, $tailindex, $tablelen-$tailindex);
while($line != "")
{
	$tailindex	= strstr($line, "\n")+1;
	$subline	= substr($line, 0, $tailindex);
	$interface	= scut($subline, 3, "");
	if($interface==""){$interface = scut($subline, 0, "");}
	TRACE_debug("$interface=".$interface);

	if($interface == $wlan1_name)			{$br_type = "WiFi_2.4G";}
	else if($interface == $wlan1_gz_name)	{$br_type = "WiFi_2.4G_Guest";}
	else if($interface == $wlan2_name)		{$br_type = "WiFi_5G";}
	else if($interface == $wlan2_gz_name)	{$br_type = "WiFi_5G_Guest";}
	else									{$br_type = "LAN";}
	TRACE_debug("$br_type=".$br_type);

	if(strstr($subline, "br1")!="")						{$br1_p1_type = $br_type;$br="br1";}
	else if(strstr($subline, "br0")=="" && $br=="br1")	{$br1_p2_type = $br_type;}
	else if(strstr($subline, "br0")!="")				{$br0_p1_type = $br_type;$br="br0";}
	else if($br=="br0" && $br0_p2_type=="")				{$br0_p2_type = $br_type;}
	else if($br=="br0" && $br0_p3_type=="")				{$br0_p3_type = $br_type;}

	$tablelen	= strlen($line);
	$line		= substr($line, $tailindex, $tablelen-$tailindex);
}
TRACE_debug("$br1_p1_type=".$br1_p1_type."\n $br1_p2_type=".$br1_p2_type."\n $br0_p1_type=".$br0_p1_type."\n $br0_p2_type=".$br0_p2_type."\n $br0_p3_type=".$br0_p3_type);
?>
<? if($Remove_XML_Head_Tail != 1)	{HTML_hnap_xml_header();}?>
<GetClientInfoDemoResponse xmlns="http://purenetworks.com/HNAP1/">
	<GetClientInfoDemoResult><?=$result?></GetClientInfoDemoResult>
		<ClientInfoLists>
		<?
		$i=0;
		while($i < 2)
		{
			if($i==0)	{$brctl_showmacs = $brctl_showmacs_br0;$br="br0";}
			else		{$brctl_showmacs = $brctl_showmacs_br1;$br="br1";}

			$tailindex	= strstr($brctl_showmacs, "\n")+1;
			$portindex	= 2;
			$macindex	= strstr($brctl_showmacs, "mac addr")-4;
			$maclen     = strlen("00:00:00:00:00:00");
			$tablelen	= strlen($brctl_showmacs);
			$line		= substr($brctl_showmacs, $tailindex, $tablelen-$tailindex);

			while($line != "")
			{
				$tailindex	= strstr($line, "\n")+1;
				$subline	= substr($line, 0, $tailindex);
				if(strstr($subline, "no")!="") //It means not in local.
				{
					$mac			= strip(substr($subline, $macindex, $maclen));
					$client_path	= get_clientpath($mac, $LAN1, $LAN2);
					TRACE_debug("$client_path=".$client_path);
					$ipaddr			= get("", $client_path."/ipaddr");
					if($ipaddr==""){$ipaddr	= get("", $client_path."/ipv4addr");}
					if($ipaddr!="" && $ipaddr!="0.0.0.0" && $ipaddr!=INF_getcurripaddr($LAN1) && $ipaddr!=INF_getcurripaddr($LAN2))
					{
						$hostname		= get("x", $client_path."/hostname");
						$portnumber		= substr($subline, 2, 1);
						if($br=="br1" && $portnumber==1)		{$type=$br1_p1_type;}
						else if($br=="br1" && $portnumber==2)	{$type=$br1_p2_type;}
						else if($br=="br0" && $portnumber==1)	{$type=$br0_p1_type;}
						else if($br=="br0" && $portnumber==2)	{$type=$br0_p2_type;}
						else if($br=="br0" && $portnumber==3)	{$type=$br0_p3_type;}
							$nickname = find_dhcps4_staticleases_info($mac, "nickname", $LAN1, $LAN2);
							$reserveip = find_dhcps4_staticleases_info($mac, "reserveip", $LAN1, $LAN2);

						echo "	<ClientInfo>\n";
						echo "		<MacAddress>".$mac."</MacAddress>\n";
						echo "		<IPv4Address>".$ipaddr."</IPv4Address>\n";
						echo "		<IPv6Address>".getIPv6AddrByMAC($mac)."</IPv6Address>\n";
						echo "		<Type>".$type."</Type>\n";
						echo "		<DeviceName>".$hostname."</DeviceName>\n";
						echo "		<NickName>".$nickname."</NickName>\n";
						echo "		<ReserveIP>".$reserveip."</ReserveIP>\n";
						echo "		<SupportedAction>\n";
						if(strstr($hostname, "DAP") != "" || strstr($hostname, "dap") != "" || strstr($hostname, "1320") != ""  || strstr($hostname, "DCH") != "" || strstr($hostname, "225") != "") //The new name of DAP-1320B is DCH-M225
						{
							echo "			<Audio>true</Audio>\n";
							if(isfile("/var/getaudioresult")==1)
							{
								$getaudioresult = fread("s", "/var/getaudioresult");
								echo "			<Audio_DLNA>".parse_xml_node($getaudioresult, "DLNA")."</Audio_DLNA>\n";
								echo "			<Audio_AirPlay>".parse_xml_node($getaudioresult, "AirPlay")."</Audio_AirPlay>\n";
								echo "			<Audio_Mute>".parse_xml_node($getaudioresult, "AudioMute")."</Audio_Mute>\n";
							}
						}
						if(strstr($hostname, "DSP") != "" || strstr($hostname, "dsp") != "" || strstr($hostname, "215") != "")
						{
							echo "			<SmartPlug>true</SmartPlug>\n";
							if(isfile("/var/spresult")==1)
							{
								$spresult = fread("s", "/var/spresult");
								echo "			<SmartPlug_Name>".parse_xml_node($spresult, "PlugName")."</SmartPlug_Name>\n";
								echo "			<SmartPlug_Watt>".parse_xml_node($spresult, "Power")."</SmartPlug_Watt>\n";
								echo "			<SmartPlug_SW>".parse_xml_node($spresult, "RelayEnabled")."</SmartPlug_SW>\n";
							}
						}
						echo "		</SupportedAction>\n";
						echo "	</ClientInfo>\n";
					}
				}

				$tablelen	= strlen($line);
				$line		= substr($line, $tailindex, $tablelen-$tailindex);
			}
			$i++;
		}
		?></ClientInfoLists>
</GetClientInfoDemoResponse>
<? if($Remove_XML_Head_Tail != 1)	{HTML_hnap_xml_tail();}?>
