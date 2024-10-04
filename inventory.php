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


include_once(__DIR__ . '/vendor/autoload.php');

use chillerlan\QRCode\QRCode;

$mpdf = new \Mpdf\Mpdf();
 

// $mpdf->WriteHTML('<img src="'.(new QRCode)->render('otpauth://totp/test?secret=B3JX4VCVJDVNXNZ5&issuer=chillerlan.net').'" alt="QR Code" />');
$mpdf->WriteHTML('<h1>Hello World</h1>');
$mpdf->Output();



$html = <<<HTML
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>WMS Dashboard</title>    

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

    <link rel="stylesheet" type="text/css"
        href="https://cdn3.devexpress.com/jslib/24.1.5/css/dx.material.blue.light.css" />

</head>

<body class="dx-viewport">
    <div class="container-flex">
    {$qr}
        <div class="col-12 mt-3">
           <div class="card">
               <div class="card-header">
                   <h3 class="card-title" style="font-weight: 400; font-size: 22px;">Inventory Details</h3>
               </div>
               <!-- /.card-header -->
               <div class="card-body">
                   <div id="gridContainer"></div>
               </div>
               <!-- /.card-body -->
           </div>
        </div>
    </div>

    <!-- DevExtreme theme -->
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script type="text/javascript" src="https://cdn3.devexpress.com/jslib/24.1.4/js/dx.all.js"></script>

    <script>
        $.get('./api.php/getINV_SCAN').then((res)=> {
            let columns=[
                {
        dataField: 'whse_loc',
        caption: 'warehouse location',
        width: '15%',}, 
        {
        dataField: 'bin_type',
        caption: 'Bin Type',}
        ,"shadow",
        {
        dataField: 'qty',
        caption: 'Quentity', 
        },
        {
        dataField: 'bin_avail',
        caption: 'Bin avail', 
        },
        "bin_alloc",
        {
        dataField: 'qty_avail',
        caption: 'Quentity Avail',
        },"qty_alloc","line_status","reason",];
                        
            $('#gridContainer').dxDataGrid({
            dataSource: res.data,
            rowAlternationEnabled: true,
            keyExpr: 'shadow',
            columns,

            showBorders: true,
            columnWidth: 100,
            scrolling: {

                columnRenderingMode: 'virtual',
            },
            filterRow: {
                visible: false,
                applyFilter: 'auto',
            },
        });
    });

</script>

</body>
</html>
HTML;


echo $html;