<?php
//outboud_desk.php  
// 04/14/22 dse initial
// 01/05/24 dse Rem out Shipping tab

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; } 
//error_reporting(0);

$thisProgram=$_SERVER["SCRIPT_NAME"];
session_start();
require($_SESSION["wms"]["wmsConfig"]);
require_once("{$wmsInclude}/cl_Bluejay.php");
require_once("{$wmsInclude}/chk_login.php");
require_once("{$wmsInclude}/get_username.php");
$rc=chk_login($wmsHome,$thisProgram);
$username=get_username();
if (isset($spriv_thru)) $user_priv=$spriv_thru;
else $user_priv=-1;
$title="Outbound Desktop";
$helpPage="outbound_desk.pdf";

$pg=new Bluejay;
$pg->title=$title;
if ($username <> "") $pg->User["Name"]=$username;
$pg->js="";

$pg->js=<<<HTML
<script src="/jq/jquery-1.12.4.js" type="text/javascript"></script>
<script src="/jq/jquery-ui.js" type="text/javascript"></script>
<link href="/jq/jquery-ui.css.1" type="text/css" rel="stylesheet">
<script src="/jq/shortcut.js" type="text/javascript"></script>
<link href="/jq/tab_style.css" type="text/css" rel="stylesheet">

<script>
function clickTab(tabName)
{
 document.getElementById(tabName).click();
}
function loadPage(evt, childPage) {
   document.getElementById("punchout").src = childPage;
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    evt.currentTarget.className += " active";
}
//document.getElementById("tab1").click();
</script>
<style>
.helpicon {
  position: relative;
  top: 10px;
  float: right;
  right: 10px;
  overflow: hidden;

}
.container {
  position: relative;
  width: 100%;
  overflow: hidden;
  padding-top: 6.25%; /* 16:9 Aspect Ratio */
}

.responsive-iframe {
  position: relative;
  top: 0;
  left: 0;
  bottom: 0;
  right: 0;
  width: 100%;
  border: none;
}
</style>


HTML;
$pg->SystemName="Outbound Operations";
$pg->Display();

$tabs=array(
1=>array("program"=>"picking.php?nh=1","text"=>"Open Orders"),
2=>array("program"=>"picking.php?nh=1&orderType=P","text"=>"Picking"),
3=>array("program"=>"picking.php?nh=1&orderType=3","text"=>"Packing"),
//4=>array("program"=>"picking.php?nh=1&orderType=4","text"=>"Shipping"),
5=>array("program"=>"pickingc.php?nh=1&orderType=O","text"=>"Completed")
// ,
// 6=>array("program"=>"complete.php?nh=1","text"=>"Alerts"),
// 9=>array("program"=>"schedule.php?nh=1","text"=>"Schedules")

);
$htm=<<<HTML
<div class="tab">

HTML;
foreach ($tabs as $tn=>$tab)
{
 $htm.=<<<HTML
  <button id="tab{$tn}" valign="bottom" type="button" class="tablinks" onclick="loadPage(event, '{$tab["program"]}')">{$tab["text"]}</button>

HTML;
} // end foreach tabs
$htm.=<<<HTML

  <span class="helpicon" valign="top" onclick="loadPage(event, '{$wmsAssets}/docs/{$helpPage}')"><img height="16" width="16" src="{$wmsImages}/help.png"/></span>

 <input type="hidden" name="prevform" value="">
</div>
<iframe class="responsive-iframe" id="punchout" name="punchout" frameborder="0" width="100%" height="600px" src="" marginheight="0px"></iframe>

<script>
clickTab("tab1");
</script>

HTML;

$htm.=<<<HTML
</body>
</html>
HTML;
echo $htm;
?>
