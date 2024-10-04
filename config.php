<?php
// config.php -- set config vars in envionment or set vars from session
// 07/18/22 dse add colors
// 10/06/22 dse add rfTheme

if (!isset($_SESSION)) {
    //session has not started
    session_start();
}

if (get_cfg_var('wmsdir'))
    $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}

$wmsInclude = "{$wmsDir}/include";

if (isset($setConfig) and $setConfig == true) { // set config variables
    $thisProgram = "{$wmsDir}/config.php";
    $h = str_replace("/var/www", "", $wmsDir);
    $ip = $_SERVER['SERVER_ADDR'];
    $rfTheme = "en";
    if (isset($_SESSION["wms"]["device_type"]) and $_SESSION["wms"]["device_type"] == "pocket")
        $rfTheme = "ppc_en";

    $globalSettings = array(
        "wmsIp" => $ip,
        "wmsHome" => $h,
        "wmsInclude" => "{$wmsDir}/include",
        "wmsImages" => "{$h}/images",
        "wmsAssets" => "{$h}/assets",
        "wmsServer" => "{$h}/servers",
        "wmsHelp" => "{$h}/assets/help",
        "wmsDefComp" => 1,
        "wmsCompany" => "Warehouse of Parts",
        "wmsSystem" => "<i>Real</i>WMS",
        "wmsLogo" => "jds1.png",
        "wmsSysLogo" => "{$h}/images/reallwmsT.png",
        "wmsConfig" => $thisProgram,
        "rfTheme" => $rfTheme,
        "playsound" => 1,
        "colorPrimary" => "wms-light-blue",
        "colorSuccess" => "wms-green",
        "colorWarning" => "wms-amber",
        "colorFailure" => "wms-red",
        "dashboard_dir" => "{$wmsDir}/dashboard",
        "compconvertUrl" => "{$ip}/servers/COMPANY_srv.php"
    );

    if (count($globalSettings))
        foreach ($globalSettings as $var => $val) {
            $_SESSION["wms"][$var] = $val;
            $$var = $val;
        } // end foreach global setting
}  // set config variables

if (isset($_SESSION["wms"])) { // session is set load vars
    foreach ($_SESSION["wms"] as $var => $val) {
        $$var = $val;
    } // end foreach global setting
} // session is set load vars
add_userpid();

function add_userpid()
{
    global $wmsDir;
    if (isset($_SERVER['HTTP_USER_AGENT']))
        $agent = $_SERVER['HTTP_USER_AGENT'];
    else
        $agent = "";
    if (isset($_SERVER["REMOTE_ADDR"]))
        $ip = $_SERVER["REMOTE_ADDR"];
    else
        $ip = "localhost";
    $self = basename($_SERVER["PHP_SELF"]);
    if (isset($_SERVER["REQUEST_URI"]))
        $url = $_SERVER["REQUEST_URI"];
    else
        $url = "localhost";
    if (isset($_SESSION["wms"]["UserID"])) { // user id is set
        $dtype = $_SESSION["wms"]["device_type"];
        $user = $_SESSION["wms"]["UserID"];
        $SQL = <<<SQL
insert into USERPID
(user_id, remote_address, last_url, device_type)
values ($user,"{$ip}","{$self}","{$dtype}:{$agent}")
ON DUPLICATE KEY UPDATE
 remote_address="{$ip}",
 last_url="{$self}",
 device_type="{$dtype}:{$agent}"

SQL;
    } // user id is set
    else { // user id is not set
        $SQL = <<<SQL
delete from USERPID 
where remote_address = "{$ip}"

SQL;
    } // user id is not set
    if (!empty($user)) {
        require_once("{$wmsDir}/include/db_main.php");
        $db = new WMS_DB;
        $db->Update($SQL);
        unset($db);
    } // user not empty
} // end add_userpid
?>