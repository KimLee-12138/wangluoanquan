<? include "/htdocs/phplib/html.php";
if($Remove_XML_Head_Tail != 1)	{HTML_hnap_200_header();}

include "/htdocs/phplib/trace.php";
$RadioID = get("","/runtime/hnap/GetSiteSurvey/RadioID");
$sitesurvey_node = "/runtime/wifi_tmpnode";

$state = query($sitesurvey_node."/state");
$result = "OK";

if($RadioID=="2.4GHZ" || $RadioID=="RADIO_24GHz" || $RadioID=="RADIO_2.4GHz")
{
    $sitesurvey_path = "/runtime/wifi_tmpnode/sitesurvey_24G/entry";
}
else if($RadioID=="5GHZ" || $RadioID=="RADIO_5GHz")
{
    $sitesurvey_path = "/runtime/wifi_tmpnode/sitesurvey_5G/entry";
}
else
{
    $result = "ERROR_BAD_BandID";
}

?>
<? if($Remove_XML_Head_Tail != 1)	{HTML_hnap_xml_header();}?>
        <GetSiteSurveyResponse xmlns="http://purenetworks.com/HNAP1/">
            <GetSiteSurveyResult><?=$result?></GetSiteSurveyResult>
            <APStatInfoLists>
                <?
                    if($result == "OK")
                    {
                        foreach($sitesurvey_path)
                        {
                            TRACE_debug("==========================");
                            echo "                <APStatInfo>\n";
                            echo "                    <SSID>".get("x","ssid")."</SSID>\n";
                            echo "                    <Channel>".get("x","channel")."</Channel>\n";
                            echo "                    <SignalStrength>".get("x","rssi")."</SignalStrength>\n";
                            echo "                    <MacAddress>".get("x","macaddr")."</MacAddress>\n";
                            echo "                    <SupportedSecurity>\n";
                            echo "                        <SecurityInfo>\n";

                            $authtype = get("x","authtype");
                            $encrtype = get("x","encrtype");

                            if($encrtype!="NONE")
                            {
                                if($encrtype=="WEP") /* WEP */
                                {
                                    $encr_string = "";

/*                                    if( $authtype == "OPEN" )
                    				{
                    					$type = "WEP-OPEN";
                    				}
                    				else */if( $authtype == "SHARED" )
                    				{
                    					$type = "WEP-SHARED";
                    				}
                    				else if( $authtype == "OPEN" || $authtype == "WEPAUTO" )
                    				{
                    					$type = "WEP-AUTO";
                    				}
                                }
                                else if(strstr($authtype, "WPA")!="") /* WPA */
                                {
                                    $encr_string="unset";
                                    $encr_string2="";
                                    if($authtype=="WPA") {$type="WPA-RADIUS";}
                                    else if($authtype=="WPA2") {$type="WPA2-RADIUS";}
                                    else if($authtype=="WPA+2") {$type="WPAORWPA2-RADIUS";}
                                    else if($authtype=="WPAPSK") {$type="WPA-PSK";}
                                    else if($authtype=="WPA2PSK") {$type="WPA2-PSK";}
                                    else if($authtype=="WPA+2PSK") {$type="WPAORWPA2-PSK";}
                                    else { TRACE_error("Unexpected authtype:".$authtype." with ssid=".get("x","ssid")); }

                                    if($encrtype=="TKIP") {$encr_string="TKIP";}
                                    else if($encrtype=="AES") {$encr_string="AES";}
                                    else if($encrtype=="TKIP+AES" || $encrtype=="TKIPAES")
                                    {
                                        $encr_string="TKIP";
                                        $encr_string2="AES";
                                    }
                                    else { $result = "ERROR"; }
                                }
                            }
                            else    /* NONE */
                            {
                                $type = "NONE";
                                $encr_string = "";
                            }

                            echo "                            <SecurityType>".$type."</SecurityType>\n";
                            echo "                            <Encryptions>\n";
                            echo "                                <string>".$encr_string."</string>\n";
                            if($encr_string2!="")
                            {
                                echo "                                <string>".$encr_string2."</string>\n";
                            }
                            echo "                            </Encryptions>\n";
                            echo "                        </SecurityInfo>\n";
                            echo "                    </SupportedSecurity>\n";
                            echo "                </APStatInfo>\n";
                        }
                    }
                ?>
            </APStatInfoLists>
        </GetSiteSurveyResponse>
<? if($Remove_XML_Head_Tail != 1)	{HTML_hnap_xml_tail();}?>