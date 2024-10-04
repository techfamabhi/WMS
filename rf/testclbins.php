<?php
require_once("cl_PARTS2.php");
require_once("../include/db_main.php");
require_once("../include/cl_bins.php");
require_once("../include/get_table.php");
$db = new WMS_DB;

$bin = new BIN;

$bin->Company = 1;
$bin->User = 1;
$bin->init($db);


echo "<pre>";
echo "chkPart";
$w = $bin->chkPart(24006, 1);
print_r($w);
$po = array(0 => 1013);
echo "chkPartOnPO";
$w = $bin->chkPartOnPO(87630, $po, 1);
print_r($w);
echo "get_batch";
$w = $bin->get_batch(107);
print_r($w);
echo "get_batchDetail";
$w = $bin->get_batchDetail(107, 87630, 1);
print_r($w);
