<?php

// POdetail.php -- PO detail 
// 02/10/22 dse initial
/*TODO
 
*/
foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
//error_reporting(0);

session_start();
require($_SESSION["wms"]["wmsConfig"]);
$thisprogram="POdetail.php";
$comp=1;

require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
if (isset($_REQUEST["ponum"])) $ponum=$_REQUEST["ponum"]; else $ponum=0;
if (isset($_REQUEST["vend"])) $vend=$_REQUEST["vend"]; else $vend="";
if (isset($_REQUEST["batch"])) $batch=$_REQUEST["batch"]; else $batch=0;
if (isset($_REQUEST["TS"])) $TS=$_REQUEST["TS"]; else $TS="";

if ($ponum < 1)
{ // no PO#, error, redirect back to POstatus.php
 $htm=<<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Language" content="en-us">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.5">
<script>
 window.location="{$Redirect}";
</script>
</head>
</html>

HTML;
echo $htm;
} // end no ponum

$title="Detail of  {$ponum}";
$panelTitle="Expected Receipt Detail";
$SRVPHP="{$wmsServer}/PO_srv.php";
$DRPSRV="{$wmsServer}/dropdowns.php";
if (isset($_REQUEST["Redirect"])) $Redirect=$_REQUEST["Redirect"]; else $Redirect="POstatus.php";
if (isset($_REQUEST["vendor"])) $vendor=$_REQUEST["vendor"]; else $vendor="";
if (isset($_REQUEST["HPO"])) $HPO=$_REQUEST["HPO"]; else $HPO="";
if (isset($_REQUEST["exp_date"])) $exp_date=$_REQUEST["exp_date"]; else $exp_date="";
$js=<<<HTML
  <link href="/jq/bootstrap.min.css" rel="stylesheet">
  <script src="/jq/vue_2.6.14_min.js"></script>
  <script src="/jq/axios.min.js"></script>
  <style>
   .modal-mask {
     position: fixed;
     z-index: 9998;
     top: 0;
     left: 0;
     width: 100%;
     height: 100%;
     background-color: rgba(0, 0, 0, .5);
     display: table;
     transition: opacity .3s ease;
   }

   .modal-wrapper {
     display: table-cell;
     vertical-align: middle;
   }
  .messg {
    color: red;
    font-weight: bold;
    font-size: large;
    text-align: center;
  }
  .btnlink {
   border: none;
   background-color: transparent;
  }
  .required {
    color: red;
 }
 .vertical-scrollable> .row {
          position: absolute;
          top: 120px;
          bottom: 100px;
          right: 30px;
          width: 50%;
          overflow-y: scroll; 
        }
  </style>

HTML;
$pg=new Bluejay;
$pg->title=$title;
if (isset($nh) and $nh > 0) $pg->noHeader=true; else $nh=0;
$pg->js=$js;
$pg->Display();

$batchPrompt="";
if ($batch > 0) $batchPrompt="<br> Batch # {$batch}";
$extra="";
if ($TS <> "")  $extra="&TS={$TS}";
$backOnclick="'" . $Redirect . "?nh=" . $nh . "{$extra}';";
$htm=<<<HTML
  <div class="container" id="crudApp">
      <div class="col-md-6" v-if="saveSuccess">
    <div class="messg">{{ updMessg }} </div>
</div>
   <br />
   <div class="panel panel-default">
    <div class="panel-heading">
     <div class="row">
      <div class="col-md-4">
       <h3 class="panel-title">
<button name="goBack" onclick="document.location.href={$backOnclick}" class="btn btn-info btn-xs"><i class="fa fa-share-square-o" aria-hidden="true" ></i>Back</button>
{$panelTitle} #{{ poNumber }} {$batchPrompt}</h3>
      </div>
      <div class="col-md-6" align="center">
       <table>
        <tr>
         <td class="FieldCaption">P/L&nbsp;</td>
         <td><input size="3" type="text" class="form-control" v-model="plSearch" @change="fetchAllData()"/></td>
         <td nowrap class="FieldCaption">&nbsp;Part Number&nbsp;</td>
         <td><input type="text" class="form-control" v-model="pnSearch" @change="fetchAllData()"/></td>
         <td>&nbsp;</td>
        <td><button class="btn btn-primary btn-xs" name="srClr" @click="clearSearch()" title="Clear Search Criteria">Clear</button>
        </tr>
       </table>
      </div>
     </div>
    </div>
    <div class="panel-body">
     <div class="table-responsive">
      <table class="table table-bordered table-striped overflow-auto">
       <thead>
        <tr>
         <th class="FieldCaptionTD"><br>Line#</th>
         <th class="FieldCaptionTD"><br>P/L</th>
         <th class="FieldCaptionTD"><br>Part Number</th>
         <th class="FieldCaptionTD"><br>Description</th>
         <th class="FieldCaptionTD"><br>UOM</th>
         <th class="FieldCaptionTD"><br>Ordered</th>
         <th class="FieldCaptionTD">Recvd<br>so Far</th>
         <th class="FieldCaptionTD">Stocked</th>
         <th class="FieldCaptionTD"><br>Type</th>
        </tr>
       </thead>
        <tr v-for="det in detailData">
         <td align="right">{{ det.poi_line_num }}</td>
         <td>{{ det.p_l }}</td>
         <td>{{ det.part_number }}</td>
         <td>{{ det.part_desc }}</td>
         <td>{{ det.uom }}</td>
         <td align="right">{{ det.qty_ord }}</td>
         <td align="right" v-bind:class="setClass(det.qty_recvd,det.qty_ord)">{{ det.qty_recvd }}</td>
         <td align="right">{{ det.qtyStocked }}</td>
         <td>{{ det.line_type }}</td>
        </tr>
       </table>
     </div>
    </div>
   </div>
  </div>
 </body>
</html>

<script>

var application = new Vue({
 el:'#crudApp',
 data:{
  graphic:'',
  graphics:'',
  aclose: false,
  poNumber:'',
  typSearch:'P',
  plSearch:'',
  pnSearch:'',
  saveSuccess: false,
  updMessg: '',
  detailData:'',
  dynamicTitle:'PO Detail',
 },
 methods:{
  fetchAllData:function(){
   axios.post('{$SRVPHP}', {
   action:'fetchDetail',
   company: {$comp},
   wms_po_num: {$ponum},
   batch: {$batch},
   plSearch: this.plSearch,
   pnSearch: this.pnSearch
   }).then(function(response){
    application.detailData = response.data;
    if (typeof response.data[0] !== 'undefined') {
     application.poNumber = response.data[0].host_po_num;
    }
   else application.poNumber = "No Records Found";
   });
  },
  setClass(qty_rec,qty_ord)
  {
   var rcls="";
   if (qty_rec > 0) { rcls="Alt2DataTD"; }    // red over-recvd
   if (qty_rec == 0) {rcls="Alt3DataTD"}      // if set to gray, not recvd
   if (qty_rec < 0)  { rcls="bg-danger"; }    // they recvd negative, different red
   if (qty_rec > qty_ord) {rcls="Alt5DataTD"} // blue overage
   if (qty_rec == qty_ord) {rcls=""}          // if all correct, no color
   return(rcls);
  },

  clearSearch: function() {
   application.plSearch='';
   application.pnSearch='';
   application.fetchAllData();
  },
 },
 
 created:function(){
  this.fetchAllData();
 }
});

</script>

HTML;
echo $htm;
?>
