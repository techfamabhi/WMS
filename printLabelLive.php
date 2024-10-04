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



$html = <<<HTML
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Print Labels</title>    

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fon`ts.sgoogleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css" />
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css" />
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css" />
    <!-- iCheck -->
    <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css" />

    <!-- Theme style -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css" />
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="plugins/overlayScrollbars/css/OverlayScrollbars.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"/>

    <link rel="stylesheet" type="text/css"
        href="https://cdn3.devexpress.com/jslib/24.1.5/css/dx.material.blue.light.css" />

</head>

<body class="dx-viewport">
    <div id="popup"></div>
    <div class="container-flex">
        <div class="col-12 mt-3">
           <div class="card">
               <div class="card-header">
                <div class="col-12">
                    <div class="row">
                        <h3 class="card-title" style="font-weight: 400; font-size: 22px;">Print Labels</h3>
                    </div>
                    <div class="row">
                        <div class="offset-2 col col-4">
                            <div id="start-date-time"></div>
                        </div>
                        <div class="col col-4">
                            <div id="end-date-time"></div>
                        </div>
                        <div class="getdata">
                            <div id="end-date-time"></div>
                        </div>
                    </div>
                </div>
               </div>
               <!-- /.card-header -->
               <div class="card-body">
                   <div id="gridContainer"></div>
               </div>
               <!-- /.card-body -->
           </div>
        </div>
    </div>
    <style>
    .label-container {
      border: 1px solid #000;
      border-radius: 10px;
      padding: 20px;
      width: 500px;
      margin: 20px auto;
      font-family: Arial, sans-serif;
    }
    .label-header {
      font-weight: bold;
    }
    .border-box {
      border: 1px solid #000;
      padding: 10px;
    }
    .text-bold {
      font-weight: bold;
    }
    .barcode {
      height: 50px;
      background: #b1aeae;
      margin-top: 10px;
      margin-bottom: 10px;
    }
    .small-text {
      font-size: 0.8rem;
    }
    .id-box {
      border: 1px solid #000;
      width: 50px;
      text-align: center;
      font-size: 1.5rem;
      font-weight: bold;
    }
  </style>

    <!-- DevExtreme theme -->
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script type="text/javascript" src="https://cdn3.devexpress.com/jslib/24.1.4/js/dx.all.js"></script>
    <script type="text/javascript" src="./dist/js/labellive.js"></script>

</body>
</html>
HTML;


echo $html;
