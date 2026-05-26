<? include "/htdocs/phplib/html.php";
if($Remove_XML_Head_Tail != 1)	{HTML_hnap_200_header();}

include "/htdocs/webinc/config.php";

$result = "OK";

if($FEATURE_NOLAN==1) { $feature_lan = "false"; }
else { $feature_lan = "true"; }

if($FEATURE_NOAPMODE==1) { $feature_apmode = "false"; }
else { $feature_apmode = "true"; }

if($FEATURE_DUAL_BAND==1){ $feature_dualband = "true"; }
else { $feature_dualband = "false"; }

if($FEATURE_NOIPV6==1){ $feature_ipv6 = "false"; }
else { $feature_ipv6 = "true"; }

if($FEATURE_VLAN==1){ $feature_vlan = "true"; }
else { $feature_vlan = "false"; }

if($FEATURE_TURBOMODE==1) { $feature_turbomode = "true"; }
else { $feature_turbomode = "false";}

if($FEATURE_MODIFY_WPS==1)  {$feature_wps = "true";} //Kit-porting WPS control
else   {$feature_wps = "false";}

if(query("/runtime/devdata/countrycode") == "CN") { $FEATURE_CN = 1; }
else { $FEATURE_CN= 0; }

?>
<? if($Remove_XML_Head_Tail != 1)	{HTML_hnap_xml_header();}?>
		<GetDeviceFeatureAlphaResponse xmlns="http://purenetworks.com/HNAP1/">
			<GetDeviceFeatureAlphaResult><?=$result?></GetDeviceFeatureAlphaResult>
			<FeatureLAN><?=$feature_lan?></FeatureLAN>
			<FeatureAP><?=$feature_apmode?></FeatureAP>
			<FeatureDualBand><?=$feature_dualband?></FeatureDualBand>
			<FeatureIPv6><?=$feature_ipv6?></FeatureIPv6>
			<FeatureVLAN><?=$feature_vlan?></FeatureVLAN>
			<FeatureTurboMode><?=$feature_turbomode?></FeatureTurboMode>
			<FeatureCN><?=$FEATURE_CN?></FeatureCN>
			<FeatureWPS><?=$feature_wps?></FeatureWPS>
		</GetDeviceFeatureAlphaResponse>
<? if($Remove_XML_Head_Tail != 1)	{HTML_hnap_xml_tail();}?>
