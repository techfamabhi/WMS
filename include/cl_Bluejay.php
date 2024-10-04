<?php
// 11/05/20 add Scale, set it to 1 or less for mobile
// 02/25/21 dse add noHeader to supress header for use in an iframe
// 03/02/21 dse add menuScript
// 02/11/22 dse set names and logos from session
// 03/23/22 dse get wmsdir
// 04/29/22 dse add wms.css

//Class to load Bluejay header
/*
 TODO, if _SESSION is not set, redirect to Login.php with redirect
*/

class Bluejay
{
    var $REFER = "";
    var $js = "";
    var $title = "";
    var $theme = "Mulitipads";
    var $home = "/wms";
    var $Company = "JD Software";
    var $CompLogo = "/wms/jds.png";
    var $SystemName = "Real WMS";
    var $SysLogo = "/wms/images/hawkeye1.png";
    var $BackGrd = "/wms/images/hdr_bd.jpg";
    var $menuScript = "webmenu.php";
    var $top = "";
    var $noHeader = false;
    var $User = array(); // user Name, number, group, priv
    var $menu_drp = "";
    var $noBootStrap = false;
    var $Body = "body";

    var $stylesheet = "";
//menu contents array "description"=>href
//var $menu_contents=array(0=>array("desc"=>"My Favorites","href"=>"#"),
//                         1=>array("desc"=>"Settings","href"=>"#"));
    var $menu_contents = array();
    var $Scale = "";

    function __construct()
    {
        if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
        else {
            echo "<h1>WMS System is not Configured on this System</h1>";
            exit;
        }
        $this->top = str_replace("/var/www", "", $wmsDir);

        if (isset($_SESSION["wms"])) {
            $w = $_SESSION["wms"]["wmsHome"];
            $this->home = $w;
            $this->Company = $_SESSION["wms"]["wmsCompany"];
            $this->SystemName = $_SESSION["wms"]["wmsSystem"];
            $this->SysLogo = "{$w}/images/{$_SESSION["wms"]["wmsLogo"]}";
            $this->CompLogo = $_SESSION["wms"]["wmsSysLogo"];
            $this->BackGrd = "{$w}/images/hdr_bd.jpg";

        } // session is set
    } // end construct

    function Display()
    {
        if (!isset($this->User["Name"])) $this->User["Name"] = "";
        if (isset($_SESSION["wms"]["first_name"]) and isset($_SESSION["wms"]["last_name"])) {
            $username = "{$_SESSION["wms"]["first_name"]} {$_SESSION["wms"]["last_name"]}";
            if ($username <> "") $this->User["Name"] = $username;
        }

        if (isset($_REQUEST["referer"])) $this->REFER = $_REQUEST["referer"];

        $this->theme = "Multipads";
        $this->stylesheet = "{$this->home}/Themes/{$this->theme}/Style.css?=time()";


        $js_time = <<<HTML
<script language="Javascript">
setInterval("settime()", 1000);

function settime () {
  var curtime = new Date();
  var curhour = curtime.getHours();
  var curmin = curtime.getMinutes();
  var cursec = curtime.getSeconds();
  var time = "";

  if(curhour == 0) curhour = 12;
  time = (curhour > 12 ? curhour - 12 : curhour) + ":" +
         (curmin < 10 ? "0" : "") + curmin + ":" +
         (cursec < 10 ? "0" : "") + cursec + " " +
         (curhour > 12 ? "PM" : "AM");

  //document.hdr.clock.value = time;
  document.getElementById('clock').value = time;
}
function openalt(url,nlns) {
        hgt=210 + (nlns * 25);
        window.open(url,"altpage", "toolbar=no,left=125,top=125,status=yes,resizable=yes,scrollbars=yes,width=750,height=" + hgt );
     }

</script>
<style>
.dropbtn {
    background-color : transparent;
    color: #4d4d4d;
    padding: 2px;
    font-size: 14px;
    border: none;
    cursor: pointer;
}

.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-content {
    display: none;
    position: absolute;
    background-color: #f9f9f9;
    min-width: 160px;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
    z-index: 1;
}

.dropdown-content a {
    color: black;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
}

.dropdown-content a:hover {background-color: #f1f1f1}

.dropdown:hover .dropdown-content {
    display: block;
    right: 0px;
}

.dropdown:hover .dropbtn {
    background-color: #00a3cf;
}
</style>
HTML;
        $Label1 = date("l F j, Y");
        $ClockStyle = "border: 0px; font-family: Tahoma, Verdana, Arial, Helvetica; font-size: 11px; filter:alpha(opacity=100); opacity:1; background-color : transparent; text-decoration:none;";
        //<input type="text" name="clock" style="border: 0px; font-family: Tahoma, Verdana, Arial, Helvetica; font-size: 11px; filter:alpha(opacity=100); opacity:1;" text-decoration:none; size="10" value="">
        $menuicon = <<<HTML
<img border="0" src="{$this->home}/images/menu_grey.png">
HTML;
        $menu_htm = <<<HTML
    <a title="Menu" href="{$this->home}/{$this->menuScript}">{$menuicon}</a>

HTML;
        if ($this->menu_drp <> "") $menu_htm = $this->menu_drp;

        $add_menu = "";
        if (count($this->menu_contents) > 0) {
            foreach ($this->menu_contents as $desc => $mc) {
                $add_menu .= <<<HTML
    <a title="{$mc["desc"]}" href="{$mc["href"]}"><strong>{$mc["desc"]}</strong></a>

HTML;
            }
        }
        $user_drp = <<<HTML
<div class="dropdown">
  <button class="dropbtn">{$this->User["Name"]}</button>
  <div class="dropdown-content">
    {$add_menu}
    <hr align="center">
    <a title="Log Off the System" href="{$this->home}/Login.php"><strong>Logout</strong></a>
  </div>
</div>

HTML;

        //<td class="hidden-mobile" width="25%" align="center">
        //<h4 align="center">{$this->SystemName}</h4>
        //</td>
        $htm_hdr = <<<HTML
<form name="hdr">
<div style="background: url({$this->BackGrd}); background-size: 100% 28;background-repeat: no-repeat;">
<table cellspacing="0" cellpadding="0" width="100%" border="0">
  <tr>
   <td valign="top" width="7%">
    <img border="0" src="{$this->CompLogo}" width="74" height="28">
    <br>&nbsp;
    <input type="text" id="clock" name="clock" style="$ClockStyle}" size="10" value="">
   </td> 
    <td class="FormHeaderFont hidden-mobile" valign="center" width="20%">
      &nbsp;{$this->Company}
    </td> 
    <td nowrap width="7%" align="left">{$user_drp}</td> 
    <td width="10%" align="center">{$menu_htm}&nbsp;</td> 
    <td class="hidden-mobile" width="20%" nowrap align="right" >
     {$Label1}
    </td> 
    <td class="hidden-mobile1" align="right" width="4%">
    <img id="Image1" height="20" src="{$this->SysLogo}" width="37" name="Image1">
   </tr>
 </table>
</div>
</form>
HTML;
        if ($this->noHeader) {
            $htm_hdr = "";
            $js_time = "";
        }
//<a href="Login.php">Logout</a>
//$lines = $htm_hdr;
//$lines=str_replace("href=\"","href=\"../",$lines);
//$lines=str_replace("ground=\"hdr","ground=\"../hdr",$lines);
//$lines=str_replace("images","../images",$lines);
//$lines=str_replace("src=\"bluejay","src=\"../bluejay",$lines);
//$lines=str_replace("src=\"logo","src=\"../logo",$lines);
//foreach ($lines as $line_num => $line) {
        //echo $line . "\n";
//}
//ob_start();
//include("../inc_hdr.php");
//$htm.=ob_get_contents();
//ob_end_clean();
        $scale = "";
        if ($this->Scale > .05) $scale = <<<HTML
 <meta name="viewport" content="width=device-width, initial-scale={$this->Scale}">

HTML;
        if ($this->noBootStrap) $bs = "";
        else $bs = <<<HTML
<link href="/jq/bootstrap.min.css" rel="stylesheet">
HTML;

        $htm = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Language" content="en-us">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="robots" content="noindex">
<meta name="viewport" content="width=device-width, initial-scale=1.5">
{$scale}
{$bs}
<link href="{$this->stylesheet}" type="text/css" rel="stylesheet">
<link rel="stylesheet" href="{$this->home}/assets/css/wms.css">

<style>
      @media (max-width: 780px) {
        .hidden-mobile {
          display: none;
        }
      }
      @media (max-width: 319px) {
        .hidden-mobile1 {
          display: none;
        }
      }
</style>
<title>{$this->title}</title>
{$this->js}
{$js_time}
</head>

<{$this->Body}>
{$htm_hdr}

HTML;
        echo $htm;
    } // end Display
} // end class Report
?>
