<?php

require('config.php');

if (!isset($_SESSION["wms"])) {
    header("Location: ./Login.php");
    die();
}
require_once("{$_SESSION["wms"]["wmsInclude"]}/db_main.php");
$db = new WMS_DB;

$SQL = <<<SQL
select
distinct paud_user,
first_name,
last_name,
paud_type,
count(*)
 as Records,
sum(paud_qty) as Qty
from PARTHIST,WEB_USERS where  
--DATE_FORMAT(paud_date,"%m/%Y") = DATE_FORMAT(NOW(),"%m/%Y") and 
user_id = paud_user
group by paud_user,paud_type
SQL;

$data = $db->gData($SQL);

$receiving = array_filter($data, function ($i) {
    if ($i["paud_type"] == "RCV")
        return $i["Qty"];
});

$receiving_outstanding = 0;
$receiving_complete = 0;
if (!empty($receiving)) {
    $receiving_outstanding = reset($receiving)["Records"];
    $receiving_complete = reset($receiving)["Qty"];
}

$totalGoodsInPercentage = (int)($receiving_outstanding / 100 * $receiving_complete);


$conn = mysqli_connect($db->DBHost, $db->DBUser, $db->DBPassword, $db->DBDatabase);
$sql_string = <<<SQL
select * from web_usergrp
SQL;

function getData($conn, $query)
{
    $result = mysqli_query($conn, $query);
    $employees = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $employees[] = $row;
    }
    return $employees;
}


$d = getData($conn, $sql_string);
$grid_data = json_encode($d);


$operator_analysis_details_html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>WMS Dashboard</title>

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

    <link rel="stylesheet" href="https://cdn3.devexpress.com/jslib/24.1.4/css/dx.light.css">

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
                        <a href="./goodsInDetails.php" class="nav-link">
                            <i class="nav-icon fas fa-th"></i>
                            <p>Goods In</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="#" class="nav-link active">
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
        <section class="content">
            <div class="container-fluid">
                <!-- Main row -->
                <section class="connectedSortable row">
                    <div class="col-12">
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">Operator Analysis</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn bg-info btn-sm" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="lineChartOperatorAnalysis"></div>
                            </div>
                            <div class="card-footer">Operator Analysis</div>
                            <!-- /.card-body -->
                        </div>
                    </div>
                    <!-- /.card -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Operator Analysis</h3>
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
<script type="text/javascript" src="https://cdn3.devexpress.com/jslib/24.1.4/js/dx.all.js"></script>

<script>


    $('#lineChartOperatorAnalysis').dxChart({
        dataSource: [
            {age: 'User 1', number: 7.4},
            {age: 'User 3', number: 6.3},
            {age: 'User 2', number: 5.3},
            {age: 'User 4', number: 7.4},
            {age: 'User 8', number: 8.2},
            {age: 'User 5', number: 8.4},
            {age: 'User 6', number: 6.2},
            {age: 'User 7', number: 7.5},
        ],
        palette: 'soft',
        commonSeriesSettings: {
            type: 'bar',
            valueField: 'number',
            argumentField: 'age',
            ignoreEmptyPoints: true,
        },
        seriesTemplate: {
            nameField: 'age',
        },
    });



    $('#gridContainer').dxDataGrid({
        dataSource:$grid_data,
        rowAlternationEnabled: true,
        keyExpr: 'user_id',
        columns:  ['username','first_name','last_name','priv_from','priv_thru','sales_rep','company_num','home_menu','status_flag','group_id','group_desc','theme_id','operator','host_user_id',],
        showBorders: true,
        columnWidth: 100,
        scrolling: {
      columnRenderingMode: 'virtual',
    },
    });    
</script>
</body>
</html>
HTML;

echo $operator_analysis_details_html;