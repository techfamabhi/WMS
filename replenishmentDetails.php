<?php
require('config.php');

if (!isset($_SESSION["wms"])) {
    header("Location: ./Login.php");
    die();
}

require_once("{$_SESSION["wms"]["wmsInclude"]}/db_main.php");
$db = new WMS_DB;

$SQL = <<<SQL
SELECT COUNT(*) AS total FROM POITEMS; 
SQL;
$db->query($SQL);
$Result = $db->next_record();
$count = (int) $Result["total"];

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


$putaway = array_filter($data, function ($i) {
    if ($i["paud_type"] == "PUT")
        return $i["Qty"];
});

$putaway = reset($putaway);

$putaway_count = 0;
if (!empty($putaway))
    $putaway_count = $putaway["Qty"];


$conn = mysqli_connect($db->DBHost, $db->DBUser, $db->DBPassword, $db->DBDatabase);
function getData($conn, $query)
{
    $result = mysqli_query($conn, $query);
    $employees = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $employees[] = $row;
    }
    return $employees;
}


$d = getData($conn, 'SELECT * FROM `WHSEQTY` w join `PARTS` p on w.ms_shadow=p.shadow_number');
$grid_data = json_encode($d);


$replenishment_details_html = <<<HTML
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
                        <a href="#" class="nav-link active">
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
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Dashboard</h1>
                    </div>
                </div>
                <!-- /.row -->
            </div>
            <!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Small boxes (Stat box) -->
                <div class="row d-none">
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3>150</h3>

                                <p>New Orders</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-bag"></i>
                            </div>
                            <a href="#" class="small-box-footer"
                            >More info <i class="fas fa-arrow-circle-right"></i
                                ></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>53<sup style="font-size: 20px">%</sup></h3>

                                <p>Bounce Rate</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-stats-bars"></i>
                            </div>
                            <a href="#" class="small-box-footer"
                            >More info <i class="fas fa-arrow-circle-right"></i
                                ></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3>44</h3>

                                <p>User Registrations</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-person-add"></i>
                            </div>
                            <a href="#" class="small-box-footer"
                            >More info <i class="fas fa-arrow-circle-right"></i
                                ></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-3 col-6">
                        <!-- small box -->
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3>65</h3>

                                <p>Unique Visitors</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-pie-graph"></i>
                            </div>
                            <a href="#" class="small-box-footer"
                            >More info <i class="fas fa-arrow-circle-right"></i
                                ></a>
                        </div>
                    </div>
                    <!-- ./col -->
                </div>
                <!-- /.row -->

                <section class="connectedSortable row">
                    <div class="col-6">
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">Replenishment</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn bg-info btn-sm" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="gaugeChartReplenishment" ></div>
                            </div>
                            <div class="card-footer">Replenishment Outstading ($putaway_count < 100)</div>
                            <!-- /.card-body -->
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Replenishment Details</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                               <div id="gridContainer"></div>
                              </div>
                            <!-- /.card-body -->
                        </div>
                    </div>

                    <!-- /.card -->
                </section>
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
    
    $('#gaugeChartReplenishment').dxCircularGauge({
    scale: {
      startValue: 0,
      endValue: 100,
      tickInterval: 10,
      label: {
        customizeText(arg) {
          return arg.valueText+' %';
        },
      },
    },
    rangeContainer: {
      ranges: [
        { startValue: 0, endValue: 20, color: '#CE2029' },
        { startValue: 20, endValue: 50, color: '#FFD700' },
        { startValue: 50, endValue: 100, color: '#228B22' },
      ],
    },
    value: $putaway_count,
    });

    
        $('#gridContainer').dxDataGrid({
        dataSource:$grid_data,
        rowAlternationEnabled: true,
        keyExpr: 'ms_shadow',
        columns:  ['part_number','part_desc','part_long_desc','ms_shadow','ms_company','primary_bin','qty_avail','qty_alloc',
        'qty_putaway','qty_overstk','qty_on_order','qty_on_vendbo','qty_on_custbo'
        ,'qty_defect','qty_core','max_shelf','minimum','maximum','cost','core'
        ,'p_l','unit_of_measure',
        'part_seq_num','part_subline','part_category','part_group','part_class',
        'date_added','lmaint_date','serial_num_flag','part_status','special_instr',
        'hazard_id','kit_flag','cost','core','core_group','part_returnable',
        'shadow_number','part_weight',],
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

echo $replenishment_details_html;