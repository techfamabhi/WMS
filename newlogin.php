<?php
// 07/07/22 dse add userIP
// 10/06/22 dse add PocketPC/Wondows CE type, color, pixels and voice flags
// 02/14/23 dse remove /var/www if present in redirect
// 01/05/24 dse Only allow Active users and stripslashes from user/pwd

session_start();
foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
error_reporting(0);


if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir) . "/";

$menuprog = "index.php";

require_once("{$wmsDir}/include/db_main.php");
$db = new WMS_DB;
$rc = LogoutUser();
$rc = LoginUser($db, $login, $password);

$UserLogin = "";
if (isset($rc["username"])) $UserLogin = $rc["username"];

if (trim($UserLogin) == "") {
    $rest = "&ret_link=" . $_SERVER["REQUEST_URI"] . "&type=notLogged";
    $msg = "msg=Invalid Username or Password";
    $redirect = $top . "Login.php?{$msg}" . $rest;
} else {
    $redirect = $top . "{$menuprog}";
}


if (isset($ret_link) and $ret_link <> "") {
    $redirect = "{$ret_link}";
}
$redirect = str_replace("/var/www", "", $redirect);

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


function LoginUser($db, $login, $password)
{
    global $wmsDir;
    require_once("{$wmsDir}/include/Mobile_Detect.php");
    $detect = new Mobile_Detect;
    $device_type = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
    $device_color = "";
    $device_px = "";
    $device_voice = false;

// check for pocket PC / Windows CE
    if (isset($detect->userAgent)) {
        $pos = strripos($detect->userAgent, "Windows CE");
        if ($pos !== false) {
            $device_type = "pocket";
        }
    }
    if (isset($detect->httpHeaders["HTTP_UA_COLOR"])) $device_color = $detect->httpHeaders["HTTP_UA_COLOR"];
    if (isset($detect->httpHeaders["HTTP_UA_PIXELS"])) $device_px = $detect->httpHeaders["HTTP_UA_PIXELS"];
    if (isset($detect->httpHeaders["HTTP_UA_VOICE"]) and $detect->httpHeaders["HTTP_UA_VOICE"] == "TRUE") $device_voice = true;
// end pocket PC

    $login = stripslashes($login);
    $password = stripslashes($password);

    $where = <<<SQL
WHERE username = "{$login}" AND passwd="{$password}"
and status_flag = "A"

SQL;
    $SQL = <<<SQL
 SELECT 
 user_id,
 username,
 group_id,
home_menu,
first_name,
last_name,
sales_rep,
priv_from,
priv_thru,
company_num,
operator 
FROM WEB_USERS 
{$where}
SQL;
    $db->query($SQL);
    $Result = $db->next_record();
    if ($Result) {
        if (isset($_SESSION["wms"])) unset($_SESSION["wms"]);
        if (isset($_SESSION["rf"])) unset($_SESSION["rf"]);
        SetSession("UserID", $db->f("user_id"));
        SetSession("UserLogin", $login);
        SetSession("GroupID", $db->f("group_id"));
        SetSession("first_name", $db->f("first_name"));
        SetSession("last_name", $db->f("last_name"));
        SetSession("menu_number", $db->f("home_menu"));
        SetSession("sales_rep", $db->f("sales_rep"));
        SetSession("spriv_from", $db->f("priv_from"));
        SetSession("spriv_thru", $db->f("priv_thru"));
        SetSession("operator", $db->f("operator"));
        SetSession("company_num", $db->f("company_num"));
        SetSession("device_type", $device_type);
        SetSession("device_color", $device_color);
        SetSession("device_px", $device_px);
        SetSession("device_voice", $device_voice);
        SetSession("userIp", $_SERVER["REMOTE_ADDR"]);
        $setConfig = true;
        require("{$wmsDir}/config.php");
        unset($setConfig);
    }
    $SResult = $Result;
    return $SResult;
}

//End CCLoginUser

//CCLogoutUser @0-55C59DC5
function LogoutUser()
{
    SetSession("UserID", "");
    SetSession("UserLogin", "");
    SetSession("GroupID", "");
    SetSession("menu_number", "");
    SetSession("sales_rep", "");
    SetSession("spriv_from", 0);
    SetSession("spriv_thru", 0);
    SetSession("slsmclause", " = 0");
    SetSession("first_name", "");
    SetSession("last_name", "");
    SetSession("operator", "");
    SetSession("tsales_rep", "");
}

//End CCLogoutUser


function CheckSessVar($ParameterName)
{
    $ParameterValue = "";
    switch ($ParameterName) {
        case "b_salesgrp":
            $ParameterValue = GetSession("sales_rep");
            break;
        case "menu_number":
            $ParameterValue = GetSession("menu_number");
            break;
        case "s_b_salesgrp":
            $ParameterValue = GetSession("sales_rep");
            break;
        case "spriv_from":
            $ParameterValue = GetSession("spriv_from");
            break;
        case "spriv_thru":
            $ParameterValue = GetSession("spriv_thru");
            break;
    }
    return ($ParameterValue);
}

//CCGetSession @0-A9848448
function GetSession($parameter_name, $default_value = "")
{
    return isset($_SESSION["wms"][$parameter_name]) ? $_SESSION["wms"][$parameter_name] : $default_value;
}

//End CCGetSession

//CCSetSession @0-7889A59E
function SetSession($param_name, $param_value)
{
    $_SESSION["wms"][$param_name] = $param_value;
}
//End CCSetSession

