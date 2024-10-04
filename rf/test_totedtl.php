<?php
// make a function of of this to display contents anywhere

$wmsInclude = "../include";
$wmsIp = "192.168.10.126";
$wmsServer = "/wms/servers";

require_once("pb_utils.php");

$htm = <<<HTML
<!DOCTYPE html>
<html>
 <head>
 <title>Putaway</title>
 <meta name="robots" content="noindex">
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <meta name="viewport" content="initial-scale=1.0, width=device-width, user-scalable=yes" />
 <script>
  window.name="putaway";
 </script>

  <link rel="stylesheet" href="/wms/assets/css/wdi3.css">
 <link rel="stylesheet" href="/wms/assets/css/font-awesome.min.css">
 <link rel="stylesheet" href="/wms/Themes/Multipads/Style.css">
 <link href="/jq/bootstrap.min.css" rel="stylesheet">
 <link rel="stylesheet" href="/wms/assets/css/wms.css">
 <style>
 .menuI {
  position: absolute;
  right:0;
 }
 </style>
</head>

 <body class="w3-light-grey" >
HTML;

$toteId = 138;
$comp = 1;
$buttons = array(
    1 => array(
        "btn_id" => "b1",
        "btn_name" => "B1",
        "btn_value" => "Close",
        "btn_onclick" => "do_close();",
        "btn_prompt" => "Close"
    ),
    2 => array(
        "btn_id" => "b2",
        "btn_name" => "B2",
        "btn_value" => "Test 1",
        "btn_onclick" => "do_Test1();",
        "btn_prompt" => "Test 1"
    ),
    3 => array(
        "btn_id" => "b3",
        "btn_name" => "B3",
        "btn_value" => "Test 2",
        "btn_onclick" => "do_Test1();",
        "btn_prompt" => "Test 2"
    ),
    4 => array(
        "btn_id" => "b4",
        "btn_name" => "B4",
        "btn_value" => "Test 3",
        "btn_onclick" => "do_Test1();",
        "btn_prompt" => "Test 3"
    )

);

$ret = showToteContents($comp, $toteId, $buttons);

echo $htm;
echo $ret;
