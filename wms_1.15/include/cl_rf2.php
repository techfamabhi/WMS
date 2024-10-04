<?php
/* class to handle basic RF screen display

vars;
title    = title of page
css      = location of std css files
theme    = location of theme Style.css
logo     = company logo 
dispLogo = display logo (true/false)
viewport = default viewport setting (.75 seems to work best on small screens)
body     = html of the body of the page
onload   = optional body onload clause
style    = Add any additional needed styles to support body
jsh      = js scripts in header section
jsb      = js scripts in body section
msg      = Information message to display
infoLine = 2nd Information message to display
msgColor = color of message (default red)
footer   = optional footer of page (be careful on small screens)
color    = background color of header
Bootstrap= include Bootstrap

*/

class displayRF
{
 public $title="";
 public $css="../assets/css";
 public $theme="../Themes/Multipads";
 public $logo="../logo.png";
 public $home="/wms";
 public $menuScript="webmenu.php";
 public $dispLogo=true;
 public $viewport="1.0";
 public $body="";
 public $menuItems=array();
 public $stdMenu=array();
 public $noHeader=false;
 public $Bootstrap=false;
 public $style="";
 public $User=Array();
 public $onload="";
 public $footer="";
 public $color="light-blue";
 public $jsh="";
 public $jsb="";
 public $msg="";
 public $infoLine="";
 public $msgColor="#FFAAAA";
 public $bodyTop="";
 public $bodyBottom="";
 private $std_css="";
 private $dlogo="";
 private $windowName="";

 public function __construct()
 {
  $this->windowName=str_replace(".php","",basename($_SERVER["PHP_SELF"]));
 if (!isset($this->User["Name"])) $this->User["Name"]="";
 if (isset($_SESSION["wms"]["first_name"]) and isset($_SESSION["wms"]["last_name"]))
 {
  $username="{$_SESSION["wms"]["first_name"]} {$_SESSION["wms"]["last_name"]}";
  if ($username <> "") $this->User["Name"]=$username;
 }
  $this->dlogo="";
  if (trim($this->logo) <> "") $this->dlogo=$this->logo;
 $w="";
 if (trim($this->onload) <> "") $w=" onload=\"{$this->onload}\"";
 //$this->stdMenu=array(0=>array("href"=>"{$this->home}/{$this->menuScript}","prompt"=>"Exit")
 //);
 //$this->stdMenu=1=>array("href"=>"{$this->home}/{Login.php}","prompt"=>"Logout")
 $this->css="{$this->home}/assets/css";
 $this->theme="{$this->home}/Themes/Multipads";
 $this->logo="{$this->home}/logo.png";
 } // end contruct

 private function bldMenuLinks($links)
 {
  $this->menuItems=[]; 
  $j=count($links);
  $j1=0;
  if ($j > 0)
  {
   foreach($links as $key=>$item)
   {
    $j1++;
    $this->menuItems[$j1]=array("href"=>$item["href"],"prompt"=>$item["prompt"]);
   } // end foreach links
  } // end j > 0

  // add stdMenu items
  foreach($this->stdMenu as $key=>$item)
  {
   $j1++;
   $this->menuItems[$j1]=array("href"=>$item["href"],"prompt"=>$item["prompt"]);
  } // end foreach stdmenu
  
 } // end bldMenuLinks

 public function addMenuLink($href,$prompt)
 {
  $j=count($this->menuItems) + 1;
  $this->menuItems[$j]=array("href"=>$href,"prompt"=>$prompt);
 } // end addMenuLink

 public function display()
 {
  $bootstrap="";
 if ($this->Bootstrap) $bootstrap=<<<HTML
 <link href="/jq/bootstrap.min.css" rel="stylesheet">
HTML;
  $this->std_css=<<<HTML
 <link rel="stylesheet" href="{$this->css}/wdi3.css">
 <link rel="stylesheet" href="{$this->css}/font-awesome.min.css">
 <link rel="stylesheet" href="{$this->theme}/Style.css">
{$bootstrap}
 <link rel="stylesheet" href="{$this->css}/wms.css">
 <style>
 .menuI {
  position: absolute;
  right:0;
 }
 </style>
HTML;

  $msghtm="";
  if (trim($this->msg) <> "")
  {
   if (trim($this->msgColor) == "") $this->msgColor=$this->color;
   $msghtm=<<<HTML

   <span id="sw">
    <div class="w3-half">
      <div  style="margin-left:0px;" class="w3-container"><span style="font-weight: bold; font-size: large; text-align: center;">{$this->msg}</span></div>
    </div>
   </span>
HTML;
   $this->msg="";
  } // end msg

  $infohtm="";
  if (trim($this->infoLine) <> "")
  {
   if (trim($this->msgColor) == "") $this->msgColor=$this->color;
   $infohtm=<<<HTML
   <span id="sw">
    <div class="w3-half">
      <div  style="margin-left:0px;" class="w3-container"><span style="font-weight: bold; font-size: large; text-align: center;">{$this->infoLine}</span></div>
    </div>
   </span>
HTML;
   //$this->infoLine="";
  } // end infoLine

  $menuicon=<<<HTML
<img border="0" src="{$this->home}/images/menu_grey.png">
HTML;
$menu_htm=<<<HTML
    <a class="menuI" title="Menu" href="{$this->home}/{$this->menuScript}">{$menuicon}</a>

HTML;

$l="";
if ($this->dispLogo) $l="<img src=\"{$this->dlogo}\" border=\"0\" height=\"32px\" width=\"48px\">";
$hdr_htm=<<<HTML
  <div class="w3-main" style="margin-left:8px;margin-top:2px;">
   <!-- Header -->
   <div class="w3-half w3-row-padding w3-medium" style="padding-right:12px">
    <div id="header" class="w3-container w3-{$this->color}" style="position:absolute; top:0px; left:0px; height:50px; right:0px;overflow:hidden;"> 
     <span>{$l}<b><span id="pageTitle">{$this->title}</span></b>
     </span>
     <div class="w3-small w3-center userName-mobile" style='position: fixed; left: 30%;top:5px;'>&nbsp;&nbsp;{$this->User["Name"]}</div>
     <div style='float:right;'>
      <div style='position: fixed; top:5px;'>
       {$menu_htm}
      </div>
     </div>{$msghtm}
    </div>
     {$infohtm}
   </div>
  </div>
<div class="w3-row"></div>
HTML;
$mhtm=<<<HTML
<div class="topnav w3-{$this->color}" id="rfTopnav">
<a href="#"><strong>{$this->title}</strong></a>
<a href="#">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a>

HTML;

//build menuLinks with program specifics and STD links
$this->bldMenuLinks($this->menuItems);

if (count($this->menuItems))
{
 $i=0;
 foreach ($this->menuItems as $key=>$item)
 {
  $cls="";
  //if ($i==0) $cls=" class=\"active\""; else $cls="";
  $i++;
  $mhtm.=<<<HTML
 <a href="{$item["href"]}"{$cls}>{$item["prompt"]}</a>

HTML;
 } // end foreach  menuItems
} // end count menuItems
$mhtm.=<<<HTML
 <a href="javascript:void(0);" class="icon" onclick="menuClick()">
<img border="0" src="{$this->home}/images/menu_grey.png">
 </a>
</div>

     {$msghtm}
     {$infohtm}

<script>
function menuClick() {
  var x = document.getElementById("rfTopnav");
  if (x.className === "topnav") {
    x.className += " responsive";
  } else {
    x.className = "topnav";
  }
}
</script>

HTML;
$hdr_htm=$mhtm;
$hdr_htm=<<<HTML
<div id="header" class="w3-container w3-{$this->color}" style="position:absolute; top:0px; left:0px; height:50px; right:0px;overflow:hidden;"> 
 <table width="98%" class="topnav1 z-blue">
  <tr>
   <td nowrap width="25%">
     <span>{$l}<b><span id="pageTitle">{$this->title}</span></b>
   </td>
     <div style='float:right;'>
      <div style='position: fixed; top:5px;'>
       {$menu_htm}
      </div>
     </div>{$msghtm}
_LINKS_
  </tr>
 </table>
</div>

HTML;
$mhtm="";
//build menuLinks with program specifics and STD links
//$this->bldMenuLinks($this->menuItems);

if (count($this->menuItems))
{
 $i=0;
 foreach ($this->menuItems as $key=>$item)
 {
  $cls="";
  //if ($i==0) $cls=" class=\"active\""; else $cls="";
  $i++;
  $mhtm.=<<<HTML
   <td nowrap width="15%"><a href="{$item["href"]}"{$cls}>{$item["prompt"]}</a>
   </td>

HTML;
 } // end foreach  menuItems
} // end count menuItems

$hdr_htm=str_replace("_LINKS_",$mhtm,$hdr_htm);
if ($this->noHeader)
 {
  $hdr_htm="";
 }

$htm=<<<HTML
<!DOCTYPE html>
<html>
 <head>
 <title>{$this->title}</title>
 <meta name="robots" content="noindex">
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <meta name="viewport" content="initial-scale={$this->viewport}, width=device-width, user-scalable=yes" />
 <script>
  window.name="{$this->windowName}";
 </script>

 {$this->std_css}
 {$this->style}
 {$this->jsh}
</head>

 <body class="w3-light-grey" style="height:100%; width:100%" {$this->onload}>
<!-- !PAGE CONTENT! -->
{$hdr_htm}
 <div id="content" style="position:absolute; top:50px;{$this->bodyBottom}left:0px; right:0px; overflow:auto;"> 
  {$this->body}
 </div> 
  {$this->jsb}
  {$this->footer}

HTML;
  echo $htm;
 } // end display

 function bldFooter($funcs,$cls="binbutton-small")
 {
  /* funcs is an array of fkeys to display
  array(
   0=>array("fkey"=>"F1",
          "prompt"=>"Cancel",
          "name"=>"B1",
          "onClick"=>"do_something();",
          "value"=>"Cancel"
   ),
 ...
  );

 */

  $j=count($funcs);
  if ($j < 2) $hpos="left"; else $hpos="center";
  $fk="";
  if ($j > 0)
  {
   $i=0;
   $js="";
   foreach ($funcs as $pos=>$fkey)
    {
     $id="fKey{$i}";
     if (isset($fkey["fkey"]) and trim($fkey["fkey"]) <> "")
      { // fkey is set, add javascript
        $js.=<<<HTML
        shortcut.add("{$fkey["fkey"]}",function() {
        document.getElementById("{$id}").click();
});

HTML;
      if ($fkey["fkey"] <> "return") $f=$fkey["fkey"] . "-"; else $f="";
      } // fkey is set, add javascript
      else $f="";
     if (isset($fkey["name"])) $nm=$fkey["name"]; else $nm=$id;
     if (isset($fkey["value"])) $v=$fkey["value"]; else $v="";
     if (isset($fkey["title"])) $t=" title=\"{$fkey["title"]}\""; else $t="";
     if (isset($fkey["onClick"])) $oc=" onclick=\"{$fkey["onClick"]}\""; else $oc="";
     $fk.=<<<HTML
     <td><button class="{$cls}" id="{$id}" name="{$nm}" value="{$v}"{$t}{$oc}>{$f}{$fkey["prompt"]}</button></td>

HTML;
     $i++;
    }
  } // end count > 0

  $fjs="";
  if ($js <> "") $fjs=<<<HTML
  
<script>
{$js}
</script>

HTML;

  $footer=<<<HTML
    <div id="footer" style="position:absolute; bottom:0px; height:50px; left:0px; right:0px; overflow:hidden;"> 
     <table width="100%">
      <tr>
    {$fk}
      </tr>
    </div>{$fjs}

HTML;
  $this->bodyBottom=" bottom:50px;";
  $this->footer=$footer;
 } // end footer
} // end class displayRF
