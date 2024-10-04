<?php
require('config.php');
if (!isset($_SESSION["wms"])) {
    header("Location: ./Login.php");
    die();
}
require_once("{$_SESSION["wms"]["wmsInclude"]}/db_main.php");

$db = new WMS_DB;
$conn = mysqli_connect($db->DBHost, $db->DBUser, $db->DBPassword, $db->DBDatabase);

$sql_string=<<<SQL
select distinct
po_number,
po_type,
vendor,
DATE_FORMAT(po_date,"%m/%d/%y") as po_date,
num_lines,
DATE_FORMAT(est_deliv_date,"%m/%d/%y") as est_deliv_date,
xdock
from RCPT_SCAN A, RCPT_INWORK B, POHEADER C
where A.scan_status < 2
and C.wms_po_num = A.po_number
and B.batch_num = A.batch_num
and B.wms_po_num = A.po_number
SQL;

function getData($conn,$query)
{
    $result = mysqli_query($conn, $query);
    $employees = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $employees[] = $row;
    }
    return $employees;
}


$d = getData($conn,$sql_string);
$grid_data= json_encode($d);

$total_open_orders=getData($conn,"SELECT * FROM POITEMS where poi_status=0;");
$total_complete_orders=getData($conn,"SELECT * FROM POITEMS where  poi_status=9;");

$total_open_orders_count=count($total_open_orders);
$total_complete_orders_count=count($total_complete_orders);

$total_complete_orders_percentage=$total_open_orders_count/100*$total_complete_orders_count;

$goodsInDetails_html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>WMS Incoming</title>

    <!-- Google Font: Source Sans Pro -->
    <link
            rel="stylesheet"
            href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback"
    />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css"/>
    <!-- Ionicons -->
    <link
            rel="stylesheet"
            href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css"
    />
    <!-- Tempusdominus Bootstrap 4 -->
    <link
            rel="stylesheet"
            href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css"
    />
    <!-- iCheck -->
    <link
            rel="stylesheet"
            href="plugins/icheck-bootstrap/icheck-bootstrap.min.css"
    />

    <!-- Theme style -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css"/>
    <!-- overlayScrollbars -->
    <link
            rel="stylesheet"
            href="plugins/overlayScrollbars/css/OverlayScrollbars.min.css"
    />
    <link rel="stylesheet" type="text/css" href="https://cdn3.devexpress.com/jslib/24.1.5/css/dx.material.blue.light.css" />

</head>
<body class="hold-transition sidebar-mini layout-fixed dx-viewport">
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"
                ><i class="fas fa-bars"></i
                    ></a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                    <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>
            <li class="nav-item">
                <a
                        class="nav-link"
                        data-widget="control-sidebar"
                        data-controlsidebar-slide="true"
                        href="#"
                        role="button"
                >
                    <i class="fas fa-th-large"></i>
                </a>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul
                        class="nav nav-pills nav-sidebar flex-column"
                        data-widget="treeview"
                        role="menu"
                        data-accordion="false"
                >
                    <!-- Add icons to the links using the .nav-icon class
                     with font-awesome or any other icon font library -->
                    <li class="nav-item">
                        <a href="./dashboard.php" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link active">
                            <i class="nav-icon fas fa-th"></i>
                            <p>Goods In</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="./operatorAnalysisDetails.php" class="nav-link">
                            <i class="nav-icon fas fa-chart-area"></i>
                            <p>Operator Analysis</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="./packingDetails.php" class="nav-link">
                            <i class="nav-icon fas fa-gift"></i>
                            <p>Packing</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="./pickingDetails.php" class="nav-link">
                            <i class="nav-icon fas fa-truck-pickup"></i>
                            <p>Picking</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="putAwayDetails.php" class="nav-link">
                            <i class="nav-icon fas fa-hand-holding"></i>
                            <p>Put Away</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="replenishmentDetails.php" class="nav-link">
                            <i class="nav-icon fas fa-recycle"></i>
                            <p>Replenishment</p>
                        </a>
                    </li>
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Main content -->
        <section class="content mt-2">
            <div class="container-fluid">
                <!-- Main row -->
                <section class="connectedSortable row">
                    <div class="col-6">
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title" 
                                style="font-weight: 400; font-size: 22px;" 
                                >Incoming</h3>
                                 <div class="card-tools">
                                    <button type="button" class="btn bg-info btn-sm" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>   
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="pieChartGoodsIn" ></div>
                            </div>
                            <div class="card-footer">Purchase order $total_complete_orders_percentage % Complete</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title" 
                                style="font-weight: 400; font-size: 22px;" 
                                >All Incoming</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <div id="gridContainer"></div>
                            </div>
                            <!-- /.card-body -->
                        </div>
                    </div>
                </section>

                <!-- /.row (main row) -->
            </div>
            <!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<script src="plugins/jquery-ui/jquery-ui.min.js"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
    $.widget.bridge("uibutton", $.ui.button);
</script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- ChartJS -->
<script src="plugins/chart.js/Chart.min.js"></script>

<!-- Tempusdominus Bootstrap 4 -->
<script src="plugins/moment/moment.min.js"></script>
<script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>

<!-- AdminLTE App -->
<script src="dist/js/adminlte.js"></script>

<script type="text/javascript" src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
 
 <!-- DevExtreme theme -->
 <script type="text/javascript" src="https://cdn3.devexpress.com/jslib/24.1.4/js/dx.all.js"></script>

<script>
 $('#pieChartGoodsIn').dxPieChart({
    size: {
      width: 500,
    },
    palette: 'dark',
    dataSource:[{
          country: "Outstanding ("+$total_open_orders_count+")",
  area: $total_open_orders_count,
}, {
  country: "Complete ("+$total_complete_orders_count+")",
  area: $total_complete_orders_count,
},],
    series: [
      {
        argumentField: 'country',
        valueField: 'area',
        label: {
          visible: true,
          connector: {
            visible: true,
            width: 1,
          },
        },
      },
    ],
  });
      


    $('#gridContainer').dxDataGrid({
    dataSource:$grid_data,
    rowAlternationEnabled: true,
    keyExpr: 'po_number',
    columns:  ['po_number','po_type','vendor','po_date','num_lines','est_deliv_date','xdock',],
    showBorders: true,
    columnWidth: 100,
        scrolling: {
      columnRenderingMode: 'virtual',
    },
  });
console.log($total_open_orders_count,$total_complete_orders_count)
</script>
</body>
</html>
HTML;

echo $goodsInDetails_html;