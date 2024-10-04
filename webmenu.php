<?php
// webmenu.php -- display menu
// 10/13/2022 dse add session last_menu var

foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
//error_reporting(0);

$myself = "webmenu.php";

session_start();
if (!isset($_SESSION["wms"])) {
    $rest = "ret_link={$myself}";
    $redirect = "Login.php?" . $rest;
    $htm = <<<HTML
 <html>
 <head>
 <script>
window.location.href="{$redirect}";
 </script>
 </head>
 <body>
 </body>
</html>

HTML;
    echo $htm;
    exit;

} // end session wms is not there

require("config.php");


if (!isset($wmsInclude))
    $wmsInclude = getcwd() . "/include";
require_once("{$wmsInclude}/chklogin.php");
//$rc=chk_login();
$username = "";
$mtype = 0;
// Set local vars from session   
foreach (array_keys($_SESSION["wms"]) as $w) {
    $$w = $_SESSION["wms"][$w];
}
/* Session vars
UserID
UserLogin
GroupID
first_name
last_name
menu_number
sales_rep
spriv_from
spriv_thru
operator
company_num
tsales_rep
slsmclause
mtype
*/


/*trying to figure a way of storing last menu that a program was executed from
 and save it to ENV variable.
 This would require all links go thru a central point to save it.
 haven't quite figured out how to do this yet

The following lines would retrieve the last use menu#
$w=getenv("menu_number");
if ($w <> "") $menu_number = $w;
*/

require_once("{$wmsInclude}/cl_Bluejay.php");
$pg = new Bluejay;
$pg->Scale = 1.5;
$pg->js = "";
$pg = new Bluejay;
$pg->js = <<<HTML
<link href="/wms/assets/css/menu.css" type="text/css" rel="stylesheet">

HTML;
$pg->Company = "{$wmsCompany}";
$pg->home = "{$wmsHome}";
$pg->menuScript = "index.php";
$pg->SysLogo = "{$wmsLogo}";
$pg->CompLogo = "{$wmsSysLogo}";

if (isset($first_name) and isset($last_name))
    $username = "{$first_name} {$last_name}";
if ($username <> "")
    $pg->User["Name"] = $username;
$pg->menuScript = $myself;
$pg->Display();
if (isset($_REQUEST["menu_num"]))
    $menu_num = $_REQUEST["menu_num"];
else
    $menu_num = $menu_number;
$_SESSION["wms"]["last_menu"] = $menu_num;

require_once("menu.php");
$menutable = (get_menu($menu_num, $spriv_from, $spriv_thru, $mtype, $GroupID));
$menutable = str_replace("webmenu.php", $myself, $menutable);
echo $menutable;