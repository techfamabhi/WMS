<?php

$top_section=<<<HTML
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Responsive Admin Dashboard Template">
        <meta name="keywords" content="admin,dashboard">
        <meta name="author" content="stacks">
        <!-- The above 6 meta tags *must* come first in the head; any other head content must come *after* these tags -->

        <!-- Title -->
        <title>JD WMS</title>

        <!-- Styles -->
        <link href="https://fonts.googleapis.com/css2?family=Montserrat&display=swap" rel="stylesheet">
        <link href="assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="assets/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet">
        <link href="assets/plugins/icomoon/style.css" rel="stylesheet">
        <link href="assets/plugins/uniform/css/default.css" rel="stylesheet"/>
        <link href="assets/plugins/switchery/switchery.min.css" rel="stylesheet"/>
        <link href="assets/plugins/nvd3/nv.d3.min.css" rel="stylesheet">

        <!-- Theme Styles -->
        <link href="assets/css/space.min.css" rel="stylesheet">
        <link href="assets/css/custom.css" rel="stylesheet">

        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body class="page-sidebar-fixed page-header-fixed">

        <!-- Page Container -->
        <div class="page-container">
                        <!-- Side Bar -->

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<div class="page-sidebar">
                <a class="logo-box" href="index.html">
                    <span> JD WMS</span>

                    <i class="icon-close" id="sidebar-toggle-button-close"></i>
                </a>
                <div class="page-sidebar-inner">
                    <div class="page-sidebar-menu">
                        <ul class="accordion-menu">
                            <li class="active-page">
                                <a href="nmenu.php">
                                    <i class="menu-icon icon-home4"></i><span>Dashboard</span>
                                </a>
                            </li>
                                                                               <li>
                                <a href="companysettings.php">
                                    <i class="menu-icon icon-settings"></i><span>General Settings</span>
                                </a>
                            </li>                                                                                
                            <li>
                                <a href="javascript:void(0)">
                                    <i class="menu-icon icon-users"></i><span>Users</span><i class="accordion-icon fa fa-angle-right"></i>
                                </a>
                                   <ul class="sub-menu">
                                        <li><a href="users.php">All Users</a></li>
                                       <li><a href="users.php?t=D">Deactive Users</a></li>
                                      <li><a href="users.php?t=S">Suspended Users</a></li>
                                   </ul>
                            </li>
                            <li>
                                <a href="warhaccess.php">
                                    <i class="menu-icon icon-user"></i><span>User Access</span>
                                </a>
                            </li>
                                                                                   <li>
                                <a href="warehouses.php?ui=1">
                                    <i class="menu-icon fa fa-building"></i><span>Warehouses</span>
                                </a>
                            </li>
                            <li>
                               <a href="javascript:void(0)">
                                    <i class="menu-icon fa fa-barcode"></i><span>Products <i class="accordion-icon fa fa-angle-right"></i></span>
                                </a>
                               <ul class="sub-menu">
                                    <li><a href="products.php"><span class="text-success"><i class="fa fa-caret-right"></i> Products</span></a></li>
                                    <li><a href="categories.php"><span class="text-success"><i class="fa fa-caret-right"></i> Categories</span></a></li>
                                    <li><a href="dimensions.php"><span class="text-success"><i class="fa fa-caret-right"></i> Product Settings</span></a></li>
                               </ul>
                            </li>
                            <li>
                                <a href="javascript:void(0)">
                                    <i class="menu-icon fa fa-cubes"></i><span>Stock <i class="accordion-icon fa fa-angle-right"></i> </span>
                                </a>
                                <ul class="sub-menu">
                                    <li><a href="addstock.php"><span class="text-success"><i class="fa fa-caret-right"></i> Add Stock</span></a></li>
                                </ul>
                            </li>
                             <li>
                               <a href="javascript:void(0)">
                                    <i class="menu-icon fa fa-list-ol"></i><span>Inventory <i class="accordion-icon fa fa-angle-right"></i></span>
                                </a>
                                    <ul class="sub-menu">
                                        <li><a href="inventproducts.php"><span class="text-success" title="Inventory by Warehouse"><i class="fa fa-caret-right"></i> Inventory</span></a></li>
                                        <li><a href="lowstock.php"><span class="text-success" title="Low Stock"><i class="fa fa-caret-right"></i> Low Stock </span></a></li>
                                        <li><a href="outstock.php"><span class="text-success" title="Low Stock"><i class="fa fa-caret-right"></i> Out Of Stock </span></a></li>
                                    </ul>
                            </li>
                            <li>
                                <a href="javascript:void(0)">
                                    <i class="menu-icon fa fa-exchange"></i><span>Transfers <i class="accordion-icon fa fa-angle-right"></i> </span>
                                </a>
                                <ul class="sub-menu">
                                   <li><a href="newtransfer.php"><span class="text-success"><i class="fa fa-caret-right"></i> New Transfer</span></a></li>
                                   <li><a href="transfers.php"><span class="text-success"><i class="fa fa-caret-right"></i> Transfers Sent</span></a></li>
                                 <li><a href="transferreceived.php"><span class="text-success"><i class="fa fa-caret-right"></i> Transfers Received</span></a></li>
                                </ul>
                            </li>
                            <li>
                                <a href="javascript:void(0)">
                                    <i class="menu-icon fa fa-address-book"></i><span>Suppliers <i class="accordion-icon fa fa-angle-right"></i> </span>
                                </a>
                                                                <ul class="sub-menu">
                                    <li><a href="suppliers.php"><span class="text-success"><i class="fa fa-caret-right"></i> All Suppliers</span></a></li>
                                                                               <li><a href="newsupplier.php"><span class="text-success"><i class="fa fa-caret-right"></i> New Supplier</span></a></li>


                                </ul>
                            </li>
                                                        <li>
                                <a href="javascript:void(0)">
                                    <i class="menu-icon fa fa-address-card"></i><span>Customers <i class="accordion-icon fa fa-angle-right"></i> </span>
                                </a>
                                <ul class="sub-menu">
                                    <li><a href="customers.php"><span class="text-success"><i class="fa fa-caret-right"></i> All Customers</span></a></li>
                                    <li><a href="newcustomer.php"><span class="text-success"><i class="fa fa-caret-right"></i> New Customer</span></a></li>
                                </ul>
                            </li>
                            <li>
                               <a href="javascript:void(0)">
                                    <i class="menu-icon fa fa-credit-card"></i><span>Purshasing Orders <i class="accordion-icon fa fa-angle-right"></i> </span>
                                </a>
                               <ul class="sub-menu">
                                    <li><a href="neworder.php"><span class="text-success"><i class="fa fa-caret-right"></i> New Order</span></a></li>
                                    <li><a href="orders.php"><span class="text-success"><i class="fa fa-caret-right"></i> View Orders</span></a></li>
                                    <li><a href="ordersbysupplier.php"><span class="text-success"><i class="fa fa-caret-right"></i> Orders Per Supplier</span></a></li>
                                </ul>
                            </li>
                                                        <li>
                                <a href="javascript:void(0)">
                                    <i class="menu-icon fa fa-truck"></i><span>Deliveries <i class="accordion-icon fa fa-angle-right"></i> </span>
                                </a>
                                                                <ul class="sub-menu">
                                                                                <li><a href="newdelivery.php"><span class="text-success"><i class="fa fa-caret-right"></i> New Delivery</span></a></li>
                                                                                                            <li><a href="deliveries.php"><span class="text-success"><i class="fa fa-caret-right"></i> All Deliveries</span></a></li>
                                                                        <li><a href="deliveriesbycust.php"><span class="text-success" title="Deliveries by Customer"><i class="fa fa-caret-right"></i> Deliveries by Cust.</span></a></li>


                                </ul>
                            </li>
                            <li>
                                <a href="javascript:void(0)">
                                     <i class="menu-icon fa fa-arrow-circle-down"></i><span>Stock Returns <i class="accordion-icon fa fa-angle-right"></i> </span>
                                </a>
                                <ul class="sub-menu">
                                    <li><a href="newreturn.php"><span class="text-success"><i class="fa fa-caret-right"></i> New Stock Return</span></a></li>
                                    <li><a href="returns.php"><span class="text-success"><i class="fa fa-caret-right"></i> All Returns</span></a></li>
                                </ul>
                            </li>
                            <li>
                               <a href="javascript:void(0)">
                                    <i class="menu-icon fa fa-bar-chart"></i><span>Reports <i class="accordion-icon fa fa-angle-right"></i> </span>
                                </a>
                                <ul class="sub-menu">
                                    <li><a href="warhreports.php"><span class="text-success"><i class="fa fa-caret-right"></i> Warehouse Reports</span></a></li>
                                    <li><a href="productreports.php"><span class="text-success"><i class="fa fa-caret-right"></i> Product Reports</span></a></li>
                                    <li><a href="orderreports.php"><span class="text-success"><i class="fa fa-caret-right"></i> Order Reports</span></a></li>
                                    <li><a href="deliveryreports.php"><span class="text-success"><i class="fa fa-caret-right"></i> Delivery Reports</span></a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </div><!-- /Page Sidebar -->
            <!-- End Side Bar

            <!-- Page Content -->
            <div class="page-content">
            <!-- Header -->
            <!--Top Onclick area Starts here-->
        <div class="settings-pane collapse" id="collapseExample">
                <a href="#collapseExample" data-toggle="collapse" data-animate="true">
                        &times;
                </a>
                <div class="settings-pane-inner">
                        <div class="row">
                                <div class="col-md-4">
                                        <div class="user-info">
                                                <div class="user-image">
                                                        <a href="edit_profile.php?user_id=1">
                                                                <img src="" class="img-responsive img-circle" />
                                                        </a>
                                                </div>
                                                <div class="user-details">
                                                        <h3>
                                                                <a href="extra-profile.html">Admin Admin</a>
                                                                <!-- Available statuses: is-online, is-idle, is-busy and is-offline -->
                                                                <span class="user-status is-online"></span>
                                                        </h3>
                                                        <p class="user-title">admin@mywarehouse.com</p>
                                                        <div class="user-links">
                                                                <a href="edit_profile.php?user_id=1" class="btn btn-primary">Edit Profile</a>
                                                        </div>
                                                </div>
                                        </div>
                                </div>
                                <div class="col-md-4 link-blocks-env">
                                        <div class="links-block left-sep">
                                                <h4>
                                                        <span>Basic Information</span>
                                                </h4>

                                                <ul class="list-unstyled">
                                                        <li>
                                                                <strong>Gender: </strong>                                                       </li>
                            <li>
                                                                <strong>Date of birth: </strong>                                                        </li>
                            <li>
                                                                <strong>Your IP: </strong> 172.14.132.234                                                      </li>
                                                </ul>
                                        </div>
                                </div>
                                <!--Third Column Starts Here-->
                <div class="col-md-4 link-blocks-env">
                                        <div class="links-block left-sep">
                                                <h4>
                                                        <a href="#">
                                                                <span>About me!</span>
                                                        </a>
                                                </h4>
                                                <p></p>
                                        </div>
                                </div>
                        </div><!--row-->
                </div>
        </div>
<div class="page-header ">
        <!--<div class="alert alert-default" role="alert">-->
                    <div class="search-form ">
                        <form action="#" method="GET">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control search-input" placeholder="Type something...">
                                <span class="input-group-btn">
                                    <button class="btn btn-default" id="close-search" type="button"><i class="icon-close"></i></button>
                                </span>
                            </div>
                        </form>
                    </div>
                    <nav class="navbar navbar-default">
                        <div class="container-fluid">
                            <!-- Brand and toggle get grouped for better mobile display -->
                            <div class="navbar-header">
                                <div class="logo-sm">
                                    <a href="javascript:void(0)" id="sidebar-toggle-button"><i class="fa fa-bars"></i></a>
                                    <a class="logo-box" href="#"><span>JD WMS</span></a>
                                </div>
                                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                                    <i class="fa fa-angle-down"></i>
                                </button>
                            </div>

                            <!-- Collect the nav links, forms, and other content for toggling -->

                            <div class="collapse navbar-collapse " id="bs-example-navbar-collapse-1">
                                <ul class="nav navbar-nav">
                                    <li><a href="javascript:void(0)" id="collapsed-sidebar-toggle-button"><i class="fa fa-bars"></i> Collapse Sidebar</a></li>
                                    <li><a href="javascript:void(0)" id="toggle-fullscreen"><i class="fa fa-expand"></i>  Full Screen</a></li>
                                    <li><a href="javascript:void(0)" id="search-button"><i class="fa fa-search"></i>  Search&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; | </a></li>
                                                                        <li class="dropdown user-dropdown">
                                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><i class="fa fa-user"></i> Welcome Admin Admin</a>
                                        <ul class="dropdown-menu">

                                            <li><a href="includes/logout.php">Log Out</a></li>
                                        </ul>
                                    </li>
                                                                               <li class="dropdown">
                                        <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><i class="fa fa-bell"></i> Notifications <sup><span class="badge">55</span></sup> </a>
                                        <ul class="dropdown-menu dropdown-lg dropdown-content">
                                            <li class="drop-title">Notifications <span class="badge">55</span><a href="#" class="drop-title-link"><i class="fa fa-angle-right"></i></a></li>
                                            <li class="slimscroll dropdown-notifications">
                                                <ul class="list-unstyled dropdown-oc">
                                                                               <li><a href="includes/processnotes.php?noteid=100"><span class="notification-badge bg-primary"><i class="fa fa-photo"></i></span><span class="notification-info text-danger"><strong>New Transfer Created</strong></span><span class="notification-info" style="font-size:12px">New Transfert created </span><small class="notification-date text-info" style="font-size:10px">2021-11-23</small></a></li><li><a href="includes/processnotes.php?noteid=99"><span class="notification-badge bg-primary"><i class="fa fa-photo"></i></span><span class="notification-info text-danger"><strong>New Transfer Created</strong></span><span class="notification-info" style="font-size:12px">New Transfert created </span><small class="notification-date text-info" style="font-size:10px">2021-11-23</small></a></li><li><a href="includes/processnotes.php?noteid=97"><span class="notification-badge bg-primary"><i class="fa fa-photo"></i></span><span class="notification-info text-danger"><strong>New Order Created</strong></span><span class="notification-info" style="font-size:12px">New Purshasing Order : 17 created recently.</span><small class="notification-date text-info" style="font-size:10px">2021-11-23</small></a></li><li><a href="includes/processnotes.php?noteid=96"><span class="notification-badge bg-primary"><i class="fa fa-photo"></i></span><span class="notification-info text-danger"><strong>New Order Created</strong></span><span class="notification-info" style="font-size:12px">New Purshasing Order : 16 created recently.</span><small class="notification-date text-info" style="font-size:10px">2021-11-23</small></a></li><li><a href="includes/processnotes.php?noteid=95"><span class="notification-badge bg-primary"><i class="fa fa-photo"></i></span><span class="notification-info text-danger"><strong>Transfer Approved</strong></span><span class="notification-info" style="font-size:12px">New Transfer #: 21 approved recently.</span><small class="notification-date text-info" style="font-size:10px">2021-11-23</small></a></li><li><a href="includes/processnotes.php?noteid=94"><span class="notification-badge bg-primary"><i class="fa fa-photo"></i></span><span class="notification-info text-danger"><strong>New Transfer Created</strong></span><span class="notification-info" style="font-size:12px">New Transfert created </span><small class="notification-date text-info" style="font-size:10px">2021-11-23</small></a></li><li><a href="includes/processnotes.php?noteid=93"><span class="notification-badge bg-primary"><i class="fa fa-photo"></i></span><span class="notification-info text-danger"><strong>New Transfer Created</strong></span><span class="notification-info" style="font-size:12px">New Transfert created </span><small class="notification-date text-info" style="font-size:10px">2021-11-23</small></a></li><li><a href="includes/processnotes.php?noteid=92"><span class="notification-badge bg-primary"><i class="fa fa-photo"></i></span><span class="notification-info text-danger"><strong>New Transfer Created</strong></span><span class="notification-info" style="font-size:12px">New Transfert created </span><small class="notification-date text-info" style="font-size:10px">2021-11-23</small></a></li><li><a href="includes/processnotes.php?noteid=91"><span class="notification-badge bg-primary"><i class="fa fa-photo"></i></span><span class="notification-info text-danger"><strong>New Transfer Created</strong></span><span class="notification-info" style="font-size:12px">New Transfert created </span><small class="notification-date text-info" style="font-size:10px">2021-11-23</small></a></li><li><a href="includes/processnotes.php?noteid=90"><span class="notification-badge bg-primary"><i class="fa fa-photo"></i></span><span class="notification-info text-danger"><strong>New Order Received</strong></span><span class="notification-info" style="font-size:12px">New Order received recently.</span><small class="notification-date text-info" style="font-size:10px">2021-11-23</small></a></li><li style="color:0000FF;text-align:right"><a href="#" ><small class="notification-date text-primary" ><b> <i class="fa fa-bell"></i> All Notifications >> </b></small></a></li>   
                                                </ul>
                                            </li>
                                        </ul>
                                    </li>
                                                                                                        </ul>
                                <ul class="nav navbar-nav navbar-right">
                                    <li class="dropdown user-dropdown">
                                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Admin</a>
                                        <ul class="dropdown-menu">
                                            <li><a href="includes/logout.php">Log Out</a></li>
                                        </ul>
                                    </li>
                                </ul>
                            </div><!-- /.navbar-collapse -->
                        </div><!-- /.container-fluid -->
                    </nav>
 </div>                         <!-- End Header -->

                <!-- Page Inner -->
                <div class="page-inner">
                    <div class="page-title">
                        <h3 class="breadcrumb-header">Dashboard - JS WMS</h3>
                    </div>
                      <div id="main-wrapper">
HTML;
$alerts=<<<HTML
                        <div class="row">
                            <div class="col-lg-3 col-md-6" >
                                <div class="panel panel-white stats-widget dashed-primary" >
                                    <div class="panel-body"  >
                                        <div class="pull-left">
                                            <span class="stats-number" style="font-size:30px;color:#0d47a1">9</span>
                                            <p class="stats-info" style="font-size:20px;color:#0d47a1">Products </p>
                                        </div>
                                        <div class="pull-right">
                                            <i class="fa fa-barcode" style="font-size:48px;color:#0d47a1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="panel panel-white stats-widget dashed-warning" >
                                    <div class="panel-body">
                                        <div class="pull-left">
                                            <span class="stats-number" style="font-size:30px;color:#FF8800">3</span>
                                            <p class="stats-info" style="font-size:20px;color:#FF8800">Stock Alert</p>
                                        </div>
                                        <div class="pull-right">
                                            <i class="fa fa-minus-square" style="font-size:48px;color:#FF8800"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="panel panel-white stats-widget dashed-danger" >
                                    <div class="panel-body">
                                        <div class="pull-left">
                                            <span class="stats-number" style="font-size:30px;color:#CC0000">5</span>
                                            <p class="stats-info" style="font-size:20px;color:#CC0000">Out Of Stock</p>
                                        </div>
                                        <div class="pull-right">
                                            <i class="fa fa-warning" style="font-size:48px;color:#CC0000"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="panel panel-white stats-widget dashed-success" >
                                    <div class="panel-body">
                                        <div class="pull-left">
                                            <span class="stats-number" style="font-size:30px;color:#007E33">55</span>
                                            <p class="stats-info" style="font-size:20px;color:#007E33">Notifications</p>
                                        </div>
                                        <div class="pull-right">
                                            <i class="fa fa-bell-o" style="font-size:48px;color:#007E33"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- Row --> 
HTML;
$menu=<<<HTML
                           <div class="row">
                            <hr width="50%">
                           </div>
                        <div class="row">
                           <div class="col-lg-3 col-md-3">
                              <a style="display:block" href="WEB_USERS.php">
                               <div class="panel warh-bloc" style="background-color:#2BBBAD;opacity:0.8">
                                <div class="panel-body" >
                                 <div class="pull-left">
                                   <p class="stats-info title-warh-bloc" >Users</p>
                                 </div>
                                 <div class="pull-right icon-warh-bloc">
                                  <i class="fa fa-user-o" ></i>
                                 </div>
                                </div>
                               </div>
                              </a>
                           </div>
                           <div class="col-lg-3 col-md-3">
                              <a style="display:block" href="products.php?s_s=9786f7863f1d7dd2557408edb8ea74e8">
                                <div class="panel warh-bloc" style="background-color:#4285F4;opacity:0.8">
                                   <div class="panel-body" >
                                    <div class="pull-left">
                                       <p class="stats-info title-warh-bloc">Products</p>
                                    </div>
                                    <div class="pull-right icon-warh-bloc">
                                     <i class="fa fa-barcode" ></i>
                                    </div>
                                   </div>
                                </div>
                              </a>
                            </div>
                            <div class="col-lg-3 col-md-3">
                             <a style="display:block" href="categories.php?s_s=9786f7863f1d7dd2557408edb8ea74e8">
                                <div class="panel warh-bloc" style="background-color:#37474F;opacity:0.8">
                                    <div class="panel-body" >
                                        <div class="pull-left">
                                           <p class="stats-info title-warh-bloc">Categories</p>
                                        </div>
                                        <div class="pull-right icon-warh-bloc">
                                           <i class="fa fa-sitemap" ></i>
                                        </div>
                                    </div>
                                </div>
                             </a>
                            </div>
                            <div class="col-lg-3 col-md-3">
                             <a style="display:block warh-bloc" href="orders.php?s_s=9786f7863f1d7dd2557408edb8ea74e8">
                                <div class="panel warh-bloc" style="background-color:#ffbb33;opacity:0.8">
                                    <div class="panel-body" >
                                        <div class="pull-left">
                                           <p class="stats-info title-warh-bloc">Orders</p>
                                        </div>
                                        <div class="pull-right icon-warh-bloc">
                                           <i class="fa fa-file-text" ></i>
                                        </div>
                                    </div>
                                </div>
                             </a>
                            </div>
                            <div class="col-lg-3 col-md-3">
                             <a style="display:block" href="deliveries.php?s_s=9786f7863f1d7dd2557408edb8ea74e8">
                                <div class="panel warh-bloc" style="background-color:#ff4444;opacity:0.8">
                                    <div class="panel-body" >
                                        <div class="pull-left">
                                           <p class="stats-info title-warh-bloc">Deliveries</p>
                                        </div>
                                        <div class="pull-right icon-warh-bloc">
                                           <i class="fa fa-truck" ></i>
                                        </div>
                                    </div>
                                </div>
                             </a>
                            </div>
                        <!--</div> Row
                        <div class="row">-->
                            <div class="col-lg-3 col-md-3">
                             <a style="display:block" href="inventproducts.php?s_s=9786f7863f1d7dd2557408edb8ea74e8">
                                <div class="panel warh-bloc" style="background-color:#aa66cc;opacity:0.8">
                                    <div class="panel-body" >
                                        <div class="pull-left">
                                         <p class="stats-info title-warh-bloc">Stock</p>
                                        </div>
                                        <div class="pull-right icon-warh-bloc">
                                         <i class="fa fa-cubes" ></i>
                                        </div>
                                    </div>
                                </div>
                             </a>
                            </div>
                            <div class="col-lg-3 col-md-3">
                                                                <a style="display:block" href="transfers.php?s_s=9786f7863f1d7dd2557408edb8ea74e8">
                                                                        <div class="panel warh-bloc" style="background-color:#00C851;opacity:0.8">
                                                                               <div class="panel-body" >
                                                                               <div class="pull-left">

                                                                               <p class="stats-info title-warh-bloc">Transfers</p>
                                                                               </div>
                                                                               <div class="pull-right icon-warh-bloc">
                                                                               <i class="fa fa-exchange" ></i>
                                                                               </div>
                                                                               </div>

                                                                        </div>
                                                                </a>
                            </div>
                            <div class="col-lg-3 col-md-3">
                                                                <a style="display:block" href="suppliers.php?s_s=9786f7863f1d7dd2557408edb8ea74e8">
                                                                        <div class="panel warh-bloc" style="background-color:#e65100;opacity:0.8">
                                                                               <div class="panel-body" >
                                                                               <div class="pull-left">
                                                                               <p class="stats-info title-warh-bloc">Suppliers</p>
                                                                               </div>
                                                                               <div class="pull-right icon-warh-bloc">
                                                                               <i class="fa fa-address-book" ></i>
                                                                               </div>
                                                                               </div>
                                                                        </div>
                                                                </a>
                            </div>
                            <div class="col-lg-3 col-md-3">
                                                                <a style="display:block" href="customers.php?s_s=9786f7863f1d7dd2557408edb8ea74e8">
                                                                        <div class="panel warh-bloc" style="background-color:#0091ea;opacity:0.8">
                                                                               <div class="panel-body" >
                                                                               <div class="pull-left">
                                                                               <p class="stats-info title-warh-bloc">Customers</p>
                                                                               </div>
                                                                               <div class="pull-right icon-warh-bloc">
                                                                               <i class="fa fa-address-card" ></i>
                                                                               </div>
                                                                               </div>
                                                                        </div>
                                                                </a>
                            </div>
                            <div class="col-lg-3 col-md-3">
                                                                <a style="display:block" href="orders.php?s_s=9786f7863f1d7dd2557408edb8ea74e8">
                                                                        <div class="panel warh-bloc" style="background-color:#3F729B;opacity:0.8">
                                                                               <div class="panel-body" >
                                                                               <div class="pull-left">
                                                                               <p class="stats-info title-warh-bloc">Receptions</p>
                                                                               </div>
                                                                               <div class="pull-right icon-warh-bloc">
                                                                               <i class="fa fa-arrow-circle-o-down" ></i>
                                                                               </div>
                                                                               </div>
                                                                        </div>
                                                                </a>
                            </div>
                            <div class="col-lg-3 col-md-3">
                                                                <a style="display:block" href="returns.php?s_s=9786f7863f1d7dd2557408edb8ea74e8">
                                                                        <div class="panel warh-bloc" style="background-color:#c51162;opacity:0.8">
                                                                               <div class="panel-body" >
                                                                               <div class="pull-left">
                                                                               <p class="stats-info title-warh-bloc">Returns</p>
                                                                               </div>
                                                                               <div class="pull-right icon-warh-bloc">
                                                                               <i class="fa fa-registered" ></i>
                                                                               </div>
                                                                               </div>
                                                                        </div>
                                                                </a>
                            </div>
                            <div class="col-lg-3 col-md-3">
                                                                <a style="display:block warh-bloc" href="reports.php?s_s=9786f7863f1d7dd2557408edb8ea74e8">
                                                                        <div class="panel warh-bloc" style="background-color:#4B515D;opacity:0.8">
                                                                               <div class="panel-body" >
                                                                               <div class="pull-left">

                                                                               <p class="stats-info title-warh-bloc">Reports</p>
                                                                               </div>
                                                                               <div class="pull-right icon-warh-bloc">
                                                                               <i class="fa fa-bar-chart-o" ></i>
                                                                               </div>
                                                                               </div>

                                                                        </div>
                                                                </a>
                            </div>
                        <!--</div> Row
                        <div class="row" style="height:50px">-->

                        </div><!-- Row -->
                    </div><!-- Main Wrapper -->
HTML;
$footer=<<<HTML
                    <div class="page-footer">
                                        <div class="panel-heading" role="tab" id="headingTwo">
    <h4 class="panel-title">
        <a class="collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                &copy; Copyright 2021 <strong>JD Software</strong> - All Rights Reserved
        </a>
    </h4>
</div>

<script type="text/javascript" charset="utf-8">
        function confirm_delete() {
                var del = confirm('Are you sure you want to perform this action?');
                if(del == true) {
                        return true;
                } else {
                        return false;
                }
        }//delete_confirmation ends here.

        //confirm delete user_error
        function confirm_deactivate_user() {
                var del = confirm('Are you sure you want to DEACTIVATE this user?');
                if(del == true) {
                        return true;
                } else {
                        return false;
                }
        }

        //confirm activate user
        function confirm_activate_user() {
                var del = confirm('Are you sure you want to ACTIVATE this user?');
                if(del == true) {
                        return true;
                } else {
                        return false;
                }
        }
</script>

                                        </div>
                </div><!-- /Page Inner -->

            </div><!-- /Page Content -->
        </div><!-- /Page Container -->


        <!-- Javascripts -->
        <script src="assets/plugins/jquery/jquery-3.1.0.min.js"></script>
        <script src="assets/plugins/bootstrap/js/bootstrap.min.js"></script>
        <script src="assets/plugins/jquery-slimscroll/jquery.slimscroll.min.js"></script>
        <script src="assets/plugins/uniform/js/jquery.uniform.standalone.js"></script>
        <script src="assets/plugins/switchery/switchery.min.js"></script>
        <script src="assets/plugins/d3/d3.min.js"></script>
        <script src="assets/plugins/nvd3/nv.d3.min.js"></script>
        <script src="assets/plugins/flot/jquery.flot.min.js"></script>
        <script src="assets/plugins/flot/jquery.flot.time.min.js"></script>
        <script src="assets/plugins/flot/jquery.flot.symbol.min.js"></script>
        <script src="assets/plugins/flot/jquery.flot.resize.min.js"></script>
        <script src="assets/plugins/flot/jquery.flot.tooltip.min.js"></script>
        <script src="assets/plugins/flot/jquery.flot.pie.min.js"></script>
        <script src="assets/js/space.min.js"></script>
        <script src="assets/js/pages/dashboard.js"></script>
    </body>
</html>
HTML;
        //<script src="assets/plugins/chartjs/chart.min.js"></script>

$htm=<<<HTML
{$top_section}
{$menu}
{$alerts}
{$footer}
HTML;
echo $htm;
?>
