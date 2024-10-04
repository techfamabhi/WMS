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

$totalPackedPercentage=(int)($scannedPercentage / 100 * $packedPercentage );
$totalGoodsInPercentage=(int)($receiving_outstanding / 100 * $receiving_complete );

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
                  </div>gaugeChartPutAway.update();
gaugeChartReplenishment.update();
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

            <!-- Main row -->
            <div class="row">
              <section class="connectedSortable row">
                <div class="col-4">
                  <div class="card card-danger">
                    <div class="card-header">
                      <h3 class="card-title">Goods In</h3>
                      
                    </div>
                    <div class="card-body">
                      <canvas
                        id="pieChartGoodsIn"
                        style="
                          min-height: 250px;
                          height: 250px;
                          max-height: 250px;
                          max-width: 100%;  
                        "
                      ></canvas>
                    </div>
                    <div class="card-footer">Purchase order $totalGoodsInPercentage % Complete</div>
                  </div>
                </div>

                <div class="col-4">
                  <div class="card card-danger">
                    <div class="card-header">
                      <h3 class="card-title">Packing</h3>
                      
                    </div>
                    <div class="card-body">
                      <canvas
                        id="pieChartPacking"
                        style="
                          min-height: 250px;
                          height: 250px;
                          max-height: 250px;
                          max-width: 100%;
                        "
                      ></canvas>
                    </div>
                    <div class="card-footer">Packed $totalPackedPercentage %</div>
                    <!-- /.card-body -->
                  </div>
                </div>
                <!-- /.card -->

                <div class="col-4">
                  <div class="card card-danger">
                    <div class="card-header">
                      <h3 class="card-title">Put Away</h3>
                      
                    </div>
                    <div class="card-body">
                      <canvas
                        id="gaugeChartPutAway"
                        style="
                          min-height: 250px;
                          height: 250px;
                          max-height: 250px;
                          max-width: 100%;
                        "
                      ></canvas>
                    </div>
                    <div class="card-footer">Put Away outstanding ($putaway_count < 100)</div>
                    <!-- /.card-body -->
                  </div>
                </div>
                <!-- /.card -->

                <div class="col-4">
                  <div class="card card-danger">
                    <div class="card-header">
                      <h3 class="card-title">Operator Analysis</h3>
                      
                    </div>
                    <div class="card-body">
                      <canvas
                        id="lineChartOperatorAnalysis"
                        style="
                          min-height: 250px;
                          height: 250px;
                          max-height: 250px;
                          max-width: 100%;
                        "
                      ></canvas>
                    </div>
                    <div class="card-footer">Operator Analysis</div>
                    <!-- /.card-body -->
                  </div>
                </div>
                <!-- /.card -->

                <div class="col-4">
                  <div class="card card-danger">
                    <div class="card-header">
                      <h3 class="card-title">Picking Notes</h3>
                      
                    </div>
                    <div class="card-body">
                      <canvas
                        id="pieChartPickingNotes"
                        style="
                          min-height: 250px;
                          height: 250px;
                          max-height: 250px;
                          max-width: 100%;
                        "
                      ></canvas>
                    </div>
                    <div class="card-footer">Pick Notes 35%</div>
                    <!-- /.card-body -->
                  </div>
                </div>
                <!-- /.card -->

                <div class="col-4">
                  <div class="card card-danger">
                    <div class="card-header">
                      <h3 class="card-title">Replenishment</h3>
                      
                    </div>
                    <div class="card-body">
                      <canvas
                        id="gaugeChartReplenishment"
                        style="
                          min-height: 250px;
                          height: 250px;
                          max-height: 250px;
                          max-width: 100%;
                        "
                      ></canvas>
                    </div>
                    <div class="card-footer">Replenishment Outstading (70 < 100)</div>
                    <!-- /.card-body -->
                  </div>
                </div>
                <!-- /.card -->
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

    <!-- <script src="https://unpkg.com/chart.js@4.4.4/dist/Chart.bundle.js"></script>-->

    <!-- <script src="https://unpkg.com/chartjs-gauge@0.3.0/dist/chartjs-gauge.js"></script>  -->
    <script src="{$_SESSION['wms']['wmsAssets']}/js/gaugecharts.js"></script> 

    <script>
      var pieChartGoodsIn = new Chart(
        $("#pieChartGoodsIn").get(0).getContext("2d"),
        {
          type: "pie",
          data: {
            labels: ["Outstanding ("+$receiving_outstanding+")", "Complete ("+$receiving_complete+")"],
            datasets: [
              {
                data: [$receiving_outstanding, $receiving_complete],
                backgroundColor: ["#f56954", "#00a65a"],
              },
            ],
          },
          options: {
            maintainAspectRatio: false,
            responsive: true,
          },
        }
      );

      var pieChartPicking = new Chart(
        $("#pieChartPacking").get(0).getContext("2d"),
        {
          type: "pie",
          data: {
            labels: ["Scanned ("+$scannedOrders+")", "Packed ("+$packedOrders+")"],
            datasets: [
              {
                data: [$scannedPercentage, $packedPercentage],
                backgroundColor: ["#f56954", "#00a65a"],
              },
            ],
          },
          options: {
            maintainAspectRatio: false,
            responsive: true,
          },
        }
      );

      var lineChartOperatorAnalysis = new Chart(
        $("#lineChartOperatorAnalysis").get(0).getContext("2d"),
        {
          type: "line",
          data: {
            labels: [
              0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18,
              19, 20, 21, 22, 23,
            ],
            datasets: [
              {
                label:"Operator Analysis",
                data: [0, 0.2, .65, .63, .2, .8],
                fill: true,
                borderColor: "rgb(75, 192, 192)",
                tension: 0.1,
              },
            ],
          },
          options: {
            responsive: true,
            scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Hours',
                            font: {
                                padding: 4,
                                size: 14,
                                weight: 'bold',
                                family: 'Arial'
                            },
                            color: 'darkblue'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Completed',
                            font: {
                                size: 14,
                                weight: 'bold',
                                family: 'Arial'
                            },
                            color: 'darkblue'
                        },
                        beginAtZero: true,
                        scaleLabel: {
                            display: true,
                            labelString: 'Values',
                        }
                    }
                },
            plugins: {
              legend: {
                position: "top",
              },
              title: {
                display: false,
                text: "Operator Analysis",
              },
            },
          },
        }
      );

      var pieChartPickingNotes = new Chart(
        $("#pieChartPickingNotes").get(0).getContext("2d"),
        {
          type: "pie",
          data: {
            labels: ["Outstanding", "Complete"],
            datasets: [
              {
                data: [700, 500],
                backgroundColor: ["#f56954", "#00a65a"],
              },
            ],
          },
          options: {
            maintainAspectRatio: false,
            responsive: true,
          },
        }
      );

      
    </script>

<script>

  var gaugeChartReplenishment = new gaugeChart(
        $("#gaugeChartReplenishment").get(0).getContext("2d"),
        {
          type: "gauge",
          data: {
            labels: ["Success", "Warning", "Warning", "Error"],
            // labels: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
            datasets: [
              {
                data: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
                value: [70],
                backgroundColor: ["#2cba00","#2cba00",'#a3ff00','#a3ff00', "#fff400","#fff400", "#ffa700","#ffa700", "#ff0000","#ff0000"],
                borderWidth: 2,
              },
            ],
          },
          options: {
            responsive: true,
            title: {
              display: false,
              text: "Replenishment outstanding (Target<100)",
            },
            layout: {
              padding: {
                bottom: 30,
              },
            },
            needle: {
              // Needle circle radius as the percentage of the chart area width
              radiusPercentage: 2,
              // Needle width as the percentage of the chart area width
              widthPercentage: 3.2,
              // Needle length as the percentage of the interval between inner radius (0%) and outer radius (100%) of the arc
              lengthPercentage: 80,
              // The color of the needle
              color: "rgba(0, 0, 0, 1)",
            },
            valueLabel: {
              formatter: Math.round,
            },
          },
        }
      );

      var gaugeChartPutAway = new gaugeChart(
        $("#gaugeChartPutAway").get(0).getContext("2d"),
        {
          type: "gauge",
          data: {
            labels: ["Success", "Warning", "Warning", "Error"],
            // labels: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
            datasets: [
              {
                data: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
                value: [$putaway_count],
                backgroundColor: ["#2cba00","#2cba00",'#a3ff00','#a3ff00', "#fff400","#fff400", "#ffa700","#ffa700", "#ff0000","#ff0000"],
                borderWidth: 2,
              },
            ],
          },
          options: {
            responsive: true,
            title: {
              display: false,
              text: "Replenishment outstanding (Target<100)",
            },
            layout: {
              padding: {
                bottom: 30,
              },
            },
            needle: {
              // Needle circle radius as the percentage of the chart area width
              radiusPercentage: 2,
              // Needle width as the percentage of the chart area width
              widthPercentage: 3.2,
              // Needle length as the percentage of the interval between inner radius (0%) and outer radius (100%) of the arc
              lengthPercentage: 80,
              // The color of the needle
              color: "rgba(0, 0, 0, 1)",
            },
            valueLabel: {
              formatter: Math.round,
            },
          },
        }
      );
      
      $("#refreshButton").click(()=>{
          // console.log(gaugeChartPutAway)
// gaugeChartPutAway.update();
// gaugeChartReplenishment.update();
pieChartPickingNotes.update();
lineChartOperatorAnalysis.update();
pieChartPacking.update();
pieChartGoodsIn.update();
      })
      
      
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