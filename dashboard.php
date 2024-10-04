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
$count = (int)$Result["total"];

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

$putaway = array_filter($data, function ($i) {
    if ($i["paud_type"] == "PUT")
        return $i["Qty"];
});

$putaway = reset($putaway);

$putaway_count = 0;
if (!empty($putaway))
    $putaway_count = $putaway["Qty"];

$picking = array_filter($data, function ($i) {
    if ($i["paud_type"] == "PIC")
        return $i["Qty"];
});

$SQL = <<<SQL
SELECT COUNT(*) AS total FROM ORDERS
SQL;

// Get the total number of orders
$result = $db->query($SQL);
$row = $db->next_record();
$totalOrders = $row['total'];


$SQL = <<<SQL
SELECT COUNT(*) AS scanned FROM ORDERS WHERE order_stat = 6
SQL;

// Get the number of scanned orders
$result = $db->query($SQL);
$row = $db->next_record();
$scannedOrders = $row['scanned'];

$SQL = <<<SQL
SELECT COUNT(*) AS packed FROM ORDERS WHERE order_stat = 3
SQL;

// Get the number of packed orders
$result = $db->query($SQL);
$row = $db->next_record();
$packedOrders = $row['packed'];

// Calculate the percentages
$scannedPercentage = ($scannedOrders / $totalOrders) * 100;
$packedPercentage = ($packedOrders / $totalOrders) * 100;

$totalPackedPercentage = (int)($scannedPercentage / 100 * $packedPercentage);
$totalGoodsInPercentage = (int)($receiving_outstanding / 100 * $receiving_complete);


$db = new WMS_DB;
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

$total_open_orders = getData($conn, "SELECT * FROM POITEMS where poi_status=0;");
$total_complete_orders = getData($conn, "SELECT * FROM POITEMS where  poi_status=9;");

$total_open_orders_count = count($total_open_orders);
$total_complete_orders_count = count($total_complete_orders);

$total_complete_orders_percentage = $total_open_orders_count / 100 * $total_complete_orders_count;



$total_parts = getData($conn, "SELECT paud_type, COUNT(*) AS total from PARTS,PARTHIST 
  where paud_shadow = shadow_number and paud_type='RCV' group by part_number,paud_type");

$total_putAway = getData($conn, "SELECT paud_type, COUNT(*) AS total from PARTS,PARTHIST 
  where paud_shadow = shadow_number and paud_type='PUT' group by part_number,paud_type");

$total_parts_count=count($total_parts);
$total_putAway_count=count($total_putAway);

$putaway_percent = $total_parts_count/100*$total_putAway_count;


$dashboard_html = <<<HTML
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>WMS Dashboard</title>

    <!-- Google Font: Source Sans Pro -->
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback"
    />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
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
    <link rel="stylesheet" href="dist/css/adminlte.min.css" />
    <!-- overlayScrollbars -->
    <link
      rel="stylesheet"
      href="plugins/overlayScrollbars/css/OverlayScrollbars.min.css"
    />
    
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
 
    <!-- DevExtreme theme -->
    <link rel="stylesheet" href="https://cdn3.devexpress.com/jslib/24.1.4/css/dx.light.css">
 
    <!-- DevExtreme libraries (reference only one of them) -->
<!--     <script type="text/javascript" src="https://cdn3.devexpress.com/jslib/24.1.4/js/dx.web.js"></script> -->
<!--     <script type="text/javascript" src="https://cdn3.devexpress.com/jslib/24.1.4/js/dx.viz.js"></script> -->


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
            <a class="nav-link" id="refreshButton" role="button">
              <i class="fa fa-refresh"></i>
            </a>
          </li>
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
              <li class="nav-item menu-open">
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
            <!-- Main row -->
            <div class="row">
              <section class="connectedSortable row">
                <div class="col-6">
                  <div class="card card-info">
                    <div class="card-header">
                      <h3 class="card-title">Incoming</h3>
                    </div>
                    <div class="card-body">
                      <div id="pieChartGoodsIn"></div>
                    </div>
                    <div class="card-footer">Purchase order $total_complete_orders_percentage % Complete</div>
                  </div>
                </div>

                <div class="col-6">
                  <div class="card card-info">
                    <div class="card-header">
                      <h3 class="card-title">Packing</h3>
                    </div>
                    <div class="card-body">
                      <div id="pieChartPacking"></div>
                    </div>
                    <div class="card-footer">Packed $totalPackedPercentage %</div>
                    <!-- /.card-body -->
                  </div>
                </div>
                <!-- /.card -->

               <div class="col-6">
                  <div class="card card-info">
                    <div class="card-header">
                      <h3 class="card-title">Put Away</h3>
                    </div>
                    <div class="card-body">
                      <div id="gaugeChartPutAway"></div>
                    </div>
                    <div class="card-footer">Put Away outstanding ($putaway_percent < 100)</div>
                    <!-- /.card-body -->
                  </div>
                </div>

               <div class="col-6">
                   <div class="card card-info">
                    <div class="card-header">
                      <h3 class="card-title">Replenishment</h3>
                      
                    </div>
                    <div class="card-body">
                      <div id="gaugeChartReplenishment"></div>
                    </div>
                    <div class="card-footer">Replenishment Outstading (9 < 100)</div>
                    <!-- /.card-body -->
                  </div>
                </div>

               <div class="col-6">
                  <div class="card card-info">
                    <div class="card-header">
                      <h3 class="card-title">Picking Notes</h3>
                    </div>
                    <div class="card-body">
                      <div id="pieChartPickingNotes"></div>
                    </div>
                    <div class="card-footer">Pick Notes 35%</div>
                    <!-- /.card-body -->
                  </div>
                </div>
                
               <div class="col-12">
                  <div class="card card-info">
                    <div class="card-header">
                      <h3 class="card-title">Operator Analysis</h3>
                    </div>
                    <div class="card-body">
                      <div id="lineChartOperatorAnalysis"></div>
                    </div>
                    <div class="card-footer">Operator Analysis</div>
                    <!-- /.card-body -->
                  </div>
                </div>
               </section>
            </div>
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
    <!-- <script src="plugins/chart.js/Chart.min.js"></script> -->

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    <!-- Tempusdominus Bootstrap 4 -->
    <script src="plugins/moment/moment.min.js"></script>
    <script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>

    <!-- AdminLTE App -->
    <script src="dist/js/adminlte.js"></script>
    
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
      
      
      $('#pieChartPacking').dxPieChart({
    size: {
      width: 500,
    },
    palette: 'dark',
    dataSource:[{
  country: "Scanned ("+parseInt( $scannedOrders)+")",
  area: parseInt( $scannedPercentage),
}, {
  country: "Packed ("+parseInt($packedOrders)+")",
  area: parseInt( $packedPercentage),
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
      
      
    $('#gaugeChartPutAway').dxCircularGauge({
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
    value: $putaway_percent,
    });


 
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

    $('#pieChartPickingNotes').dxPieChart({
                size: {width: 500,},
    palette: 'dark',
    dataSource:[{
  country: "OutStatanding ("+parseInt( $scannedOrders)+")",
  area: parseInt( $scannedPercentage),
}, {
  country: "Complete ("+parseInt($packedOrders)+")",
  area: parseInt( $packedPercentage),
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
      
    
    $('#lineChartOperatorAnalysis').dxChart({
    dataSource:[
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

      $("#gaugeChartPutAway").dblclick(()=>window.location='./putAwayDetails.php');
      $("#gaugeChartReplenishment").dblclick(()=>window.location='./replenishmentDetails.php');
      $("#pieChartPickingNotes").dblclick(()=>window.location='./pickingDetails.php');
      $("#lineChartOperatorAnalysis").dblclick(()=>window.location='./operatorAnalysisDetails.php');
      $("#pieChartPacking").dblclick(()=>window.location='./packingDetails.php');
      $("#pieChartGoodsIn").dblclick(()=>window.location='./goodsInDetails.php');

    </script>
  </body>
</html>
HTML;

echo $dashboard_html;