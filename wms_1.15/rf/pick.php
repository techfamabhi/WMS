<?php
//pick.php   -- RF Picking
// 03/14/22 dse initial

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; } 
//error_reporting(0);

if (isset($_REQUEST["inOrder"])) $inOrder=$_REQUEST["inOrder"];
$thisProgram=$_SERVER["SCRIPT_NAME"];
session_start();
require($_SESSION["wms"]["wmsConfig"]);
require_once("{$wmsInclude}/cl_rf.php");
require_once("{$wmsInclude}/chk_login.php");
require_once("{$wmsInclude}/get_username.php");
require_once("../include/restSrv.php");
$ZONESRV="{$wmsIp}/{$wmsServer}/WHSEZONES_srv.php";
$f=array("action"=>"fetchall","company"=>-1);
$rc=restSrv($ZONESRV,$f);
$zones=json_decode($rc,true);
if (isset($_SESSION["pickZone"])) $pickZone=$_SESSION["pickZone"]; else $pickZone="%";
$pzs="";
if ($pickZone == "%") $pzs=" selected";
$zone_htm=<<<HTML
<span>
<select name="pickZone" id="pickzone">
 <option value="%"{$pzs}>All Zones</option>

HTML;
foreach ($zones as $key=>$z)
{
 if ($z["is_pickable"])
 {
  $pzs="";
  if ($pickZone == $z["zone"]) $pzs=" selected";
  $zone_htm.=<<<HTML
 <option value="{$z["zone"]}"{$pzs}>{$z["zone"]} - {$z["zone_desc"]}</option>

HTML;
 } // end is_pickable
} // end foreach zones

$zone_htm.=<<<HTML
</select>
</span>
HTML;

$rc=chk_login($wmsHome,$thisProgram);
$username=get_username();
if (isset($spriv_thru)) $user_priv=$spriv_thru;
else $user_priv=-1;
$title="Picking";
$helpPage="{$wmsHelp}/picking.pdf";

$pg=new displayRF;
$pg->viewport="1.0";
$pg->dispLogo=false;
$pg->Bootstrap=true;
$pg->onload="onfocus=\"showMenu(true)\" onblur=\"showMenu(false)\"";
$pg->title=$title;
if ($username <> "") $pg->User["Name"]=$username;
$pg->jsh="";

$pg->jsh=<<<HTML
<script src="/jq/shortcut.js" type="text/javascript"></script>
<script>
shortcut.add("F1",function() {
        do_reset();
});

function clickTab(tabName)
{
 document.getElementById(tabName).click();
}
function loadPage(childPage) {
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
  //  evt.currentTarget.className += " active";
  //if (childPage.substring(0,10) != "selectZone") showMenu(false);
  showMenu(false);
  document.getElementsByName("punchout")[0].contentWindow.document.body.focus();
}
function showMenu(tf)
{
  if (tf == true) document.getElementById("tabDiv").style.display="block";
  else document.getElementById("tabDiv").style.display="none";
  
}
function srcExists(srcdoc){

    var http = new XMLHttpRequest();
    http.open('HEAD', srcdoc, false);
    http.send();

    return http.status != 404;

}
//document.getElementById("tab1").click();
</script>
<style>
.helpicon {
  position: relative;
  top: 0px;
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
$pg->SystemName="Picking Operations";

  //<button id="tab5" type="button" class="tablinks" onclick="loadPage('arinq.php')">A/R Inquiry</button>

$tabs=array();
//$tabs[3]=array("program"=>"pickorder.php?nh=1","text"=>"Order #0000002");
$tab2Click="pickQue.php?nh=1";
if (!isset($inOrder))
{
 $tabs[1]=array("program"=>"selectZone.php?nh=1","text"=>"Select Zone");
 $tab2Click="selectZone.php?nh=1";
}
$tabs[2]=array("program"=>"pickQue.php?nh=1","text"=>"Pick Queue");
$tabs[3]=array("program"=>"pickOrder.php?nh=1","text"=>"Direct Picking");
$tabs[9]=array("program"=>"messages.php?nh=1","text"=>"Messages");



$htm=<<<HTML
<div class="w3-row-padding w3-margin-bottom">
 <div class="w3-half">
  <div id="tabDiv" class="w3-container w3-white w3-padding-8">
   <ul class="nav nav-tabs">

HTML;
foreach ($tabs as $tn=>$tab)
{
 $htm.=<<<HTML
    <li class="nav-item">
      <a class="nav-link active" aria-current="page" href="#" onclick="loadPage('{$tab["program"]}')">{$tab["text"]}</a>
    </li>

HTML;
} // end foreach tabs
$htm.=<<<HTML
   </ul>

  <span class="helpicon" valign="top" onclick="loadPage('{$helpPage}')"><img height="16" width="16" src="{$wmsImages}/help.png"/></span>

 <input type="hidden" name="prevform" value="">
</div>
<iframe class="responsive-iframe" id="punchout" name="punchout" frameborder="0" width="100%" height="600px" src="" marginheight="0px"></iframe>

<script>
loadPage("{$tab2Click}");
</script>

HTML;

$htm.=<<<HTML
  </div>
 </div>
</div>
HTML;
$pg->body=$htm;
$pg->Display();
?>
