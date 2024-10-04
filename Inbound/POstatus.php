<?php

// PO.php -- PO Status and flag receipt
// 02/09/22 dse initial
// 07/05/23 dse add po type and select all types
// 01/04/24 dse Change Expected to Due Date
/*TODO

 Add Current Status in view
 0 = Open
 1 = On Dock (Send ON_DOCK message to Host)
 2 = In Process (Send LOCK message to Host)
 3 = In Putaway (Send PUTAWAY message to Host)
 4 = Updating
 5 = Received (send RECEIPT message to Host)
 6 = Sent (send OFF_DOCK to Host)
if backorders exist, set status to -1 when all done
if not back orders exist, set status to 7
If PO_DELETE is received from host, set status to 9
 
 
*/
foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
//error_reporting(0);

session_start();
if (get_cfg_var('wmsdir') !== false) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);

if (!isset($nh)) $nh = 0;
require("{$wmsDir}/config.php");

$thisprogram = "inbound_desk.php";
require_once("{$wmsInclude}/chklogin.php");

$comp = 1;

require_once("{$wmsInclude}/cl_Bluejay.php");
if (isset($_REQUEST["TS"])) $TS = $_REQUEST["TS"]; else $TS = "%";
$jts = array();
$jts["%"] = "";
$jts["P"] = "";
$jts["A"] = "";
$jts["T"] = "";
$jts["R"] = "";
$jts["S"] = "";
$jts[$TS] = " selected";

$title = "Expected Receipts";
$panelTitle = "Purchase Orders";
// P po, D debit, T transfer, R cust return, A=ASN, S=Special Order
$panelTitle = <<<HTML
<select class="form-control" v-model="typSearch" placeholder="Select Type" @change="setType()">
 <option value="%" {$jts["%"]}>All Incoming</option>
 <option value="P" {$jts["P"]}>Purchase Orders</option>
 <option value="A" {$jts["A"]}>ASN&apos;s</option>
 <option value="T" {$jts["T"]}>Transfer&apos;s</option>
 <option value="R" {$jts["R"]}>Customer Returns</option>
 <option value="S" {$jts["S"]}>Special Orders</option>
</select>

HTML;
// removed option 3, since status also varies on RCPT_BATCH
// <option value="3">Status, Document#</option>
$sortOrderBy = <<<HTML
 <select class="form-control" v-model="sortBy" @change="fetchAllData()">
 <option value="0" selected>Document#</option>
 <option value="1">Vendor/Customer, Due Date</option>
 <option value="2">Due Date, Vendor/Customer</option>
 <option value="4">Type, Document#</option>
</select>

HTML;
$SRVPHP = "{$wmsServer}/PO_srv.php";
$DRPSRV = "{$wmsServer}/dropdowns.php";
if (isset($_REQUEST["Redirect"])) $Redirect = $_REQUEST["Redirect"]; else $Redirect = "";
if (isset($_REQUEST["vendor"])) $vendor = $_REQUEST["vendor"]; else $vendor = "";
if (isset($_REQUEST["HPO"])) $HPO = $_REQUEST["HPO"]; else $HPO = "";
if (isset($_REQUEST["exp_date"])) $exp_date = $_REQUEST["exp_date"]; else $exp_date = "";
$js = <<<HTML
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
$pg = new Bluejay;
$pg->title = $title;
if (isset($nh) and $nh > 0) $pg->noHeader = true; else $nh = 0;
$pg->js = $js;
$pg->Display();


$htm = <<<HTML
  <div class="container" id="crudApp">
   <br />
   <h3 class="FormHeaderFont" align="center">{$title}</h3>
      <div class="col-md-6" v-if="saveSuccess">
    <div class="messg">{{ updMessg }} </div>
</div>
   <br />
   <div class="panel panel-default">
    <div class="panel-heading">
     <div class="row">
      <div class="col-md-4">
       <h3 class="panel-title">{$panelTitle}</h3>
      </div>
      <div class="col-md-8" align="center">
       <table>
        <tr>
         <td nowrap class="FieldCaption">Sort By</td>
         <td>
         {$sortOrderBy}
	</td>
         <td class="FieldCaption">{{ promptPO }}</td>
         <td><input type="text" class="form-control" v-model="hpoSearch" @change="fetchAllData()"/></td>
         <td class="FieldCaption">{{ promptVend }}</td>
         <td><input type="text" class="form-control" v-model="vendSearch" @change="fetchAllData()"/></td>
         <td>&nbsp;</td>
        <td><button class="btn btn-primary btn-xs" name="srClr" @click="clearSearch()" title="Clear Search Criteria">Clear</button>
        </tr>
       </table>
      </div>
     </div>
    </div>
    <div class="panel-body">
     <div class="table-responsive">
      <table class="table table-bordered table-striped">
       <tr>
        <th class="FieldCaptionTD">{{ promptPO }}</th>
        <th class="FieldCaptionTD">{{ promptVend }}</th>
        <th class="FieldCaptionTD">Type</th>
        <th class="FieldCaptionTD">Due Date</th>
        <th class="FieldCaptionTD">Status</th>
        <th class="FieldCaptionTD">Num Lines</th>
        <th class="FieldCaptionTD">Total Qty</th>
        <th class="FieldCaptionTD">Qty Recvd</th>
        <th class="FieldCaptionTD">In Process</th>
        <th class="FieldCaptionTD">Stocked</th>
        <th class="FieldCaptionTD">Created</th>
        <th class="FieldCaptionTD">Action</th>
       </tr>
       <tr v-for="row in allData">
        <td>{{ row.host_po_num }}</td>
        <td>{{ row.vendor }}</td>
        <td align="center">{{ row.po_type }}</td>
        <td>{{ row.est_deliv_date }}</td>
        <td v-bind:class="setClass(row.po_status)">{{ row.statDesc }}</td>
        <td align="right">{{ row.num_lines }}</td>
        <td align="right">{{ row.QtyOrd }}</td>
        <td align="right">{{ row.totalRecvd }}</td>
        <td align="right">{{ row.inProcessRecvd }}</td>
        <td align="right">{{ row.stocked }}</td>
        <td>{{ row.po_date }}</td>
        <td>
<button name="edit" @click="fetchData(row.wms_po_num);" class="btn btn-success"><i class="fa fa-share-square-o" aria-hidden="true" ></i> Set Status</button>
<button name="detail" @click="showDetail(row.wms_po_num,row.vendor,typSearch);" class="btn btn-info"><i class="fa fa-share-square-o" aria-hidden="true" ></i> Detail</button>
       </td>
       </tr>
      </table>
     </div>
    </div>
   </div>
   <div v-if="ModelAU">
    <transition name="model">
     <div class="modal-mask">
      <div class="modal-wrapper">
       <div class="modal-dialog">
        <div class="modal-content">
         <div class="modal-header">
          <button type="button" class="close" @click="ModelAU=false"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title FormSubHeaderFont">{{ dynamicTitle }}</h4>
         </div>
         <div class="modal-body">
          <div class="form-group">
           <table width="100%">
            <tr>
              <th class="FieldCaptionTD">{{ promptPO }}</th>
              <th class="FieldCaptionTD">{{ promptVend }}</th>
              <th class="FieldCaptionTD">Due Date</th>
            </tr>
            <tr>
             <td>
              <span v-html="host_po_num"/></span>
            </td>
             <td>
              <span v-html="vendor"/></span>
             </td>
             <td>
              <span v-html="est_deliv_date"/></span>
             </td>
            </tr>
           </table>
          </div>
          <div class="form-group">
           <br />
           <div align="center">
            <label>Current Status:</label>
            <span class="bg-info" v-html="statDesc"></span>
           </div>
          </div>
          <div class="form-group">
           <br />
           <div align="center">
            <input type="hidden" v-model="graphic" />
            <input type="button" class="btn btn-success btn-xs" v-model="actionButton" @click="submitData(po_status)" />
           </div>
          </div>
        </div>
       </div>
      </div>
     </div>
    </transition>
   </div>
  </div>
    <div v-if="ModelDetail">
    <transition name="model">
     <div class="modal-mask">
      <div class="modal-wrapper">
       <div class="modal-dialog">
        <div class="modal-content">
         <div class="modal-header">
          <button type="button" class="close" @click="ModelDetail=false"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title FormSubHeaderFont">{{ dynamicTitle }}</h4>
         </div>
         <div class="modal-body">
          <div class="form-group">
           <table class="table table-bordered table-striped overflow-auto">
            <tr>
             <th class="FieldCaptionTD">Line#</th>
             <th class="FieldCaptionTD">UOM</th>
             <th class="FieldCaptionTD">P/L</th>
             <th class="FieldCaptionTD">Part Number</th>
             <th class="FieldCaptionTD">Description</th>
             <th class="FieldCaptionTD">Ordered</th>
             <th class="FieldCaptionTD">Recvd</th>
             <th>&nbsp;</th>
            </tr>
            <tr v-for="det in detailData">
             <td>{{ det.poi_line_num }}</td>
             <td>{{ det.uom }}</td>
             <td>{{ det.p_l }}</td>
             <td>{{ det.part_number }}</td>
             <td>{{ det.part_desc }}</td>
             <td>{{ det.qty_ord }}</td>
             <td>{{ det.qty_recvd }}</td>
             <td>&nbsp;</td>
            </tr>
           </table>
          </div>
         </div>
        </div>
       </div>
      </div>
     </div>
    </transition>
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
  promptPO: 'Doc#',
  promptVend: 'Vend/Cust',
  typSearch:"{$TS}",
  sortBy:'',
  hpoSearch:'',
  vendSearch:'',
  enableSelect:1,
  saveSuccess: false,
  updMessg: '',
  allData:'',
  detailData:'',
  ModelAU:false,
  ModelDetail:false,
  actionButton:'Insert',
  dynamicTitle:'Expected Receipts',
 },
 watch: {
  ModelAU: function (val) {
  if ( val == false && application.aclose && "{$Redirect}" !== "")
      {
       window.location="{$Redirect}";
      }
  }
 },
 methods:{
  fetchAllData:function(){
   axios.post('{$SRVPHP}', {
   action:'fetchall',
   company: {$comp},
   vendor: this.vendSearch,
   host_po_num: this.hpoSearch,
   sortBy: this.sortBy,
   postatus: "< 4",
   typeSearch: this.typSearch
   }).then(function(response){
    application.allData = response.data;
   });
  },
  initVars(flg) {
   if (flg) { application.comp = {$comp}; }
   application.host_po_num     = '';
   application.wms_po_num       = '';
   application.est_deliv_date  = '';
   application.po_date         = '';
   application.num_lines       = '';
   application.po_status       = '';
  },
  showMessage: function(messg){
      // Set message
      application.updMessg = messg;
      application.saveSuccess=true;

  },
  setType() {
   var ty = application.typSearch;
   application.promptPO="Doc#";
   application.promptVend="Vend/Cust";
   if (ty == "P") {
    application.promptPO="PO#";
    application.promptVend="Vendor";
   }
   if (ty == "A") {
    application.promptPO="ASN#";
    application.promptVend="Vendor";
   }
   if (ty == "R") {
    application.promptPO="Return#";
    application.promptVend="Customer";
   }
   if (ty == "T") {
    application.promptPO="Transfer#";
    application.promptVend="Customer";
   }
   if (ty == "S") {
    application.promptPO="SpecOrd#";
    application.promptVend="Vendor";
   }
   application.fetchAllData();
  },
  setClass(po_status)
  {
   var rcls="bg-light";
   if (po_status == -1) { rcls="bg-secondary"; }
   if (po_status == 0)  { rcls="bg-light"; }
   if (po_status == 1)  { rcls="bg-success"; }
   if (po_status == 2)  { rcls="bg-info"; }
   if (po_status == 3)  { rcls="bg-primary"; }
   if (po_status == 4)  { rcls="bg-danger"; }
   if (po_status == 5)  { rcls="bg-danger"; }
   return(rcls);
  },
  clearSearch: function() {
   application.hpoSearch='';
   application.sortBy='';
   application.vendSearch='';
   application.fetchAllData();
  },
  showDetail:function(ponum,vend,tS){
   var url='POdetail.php?ponum=' + ponum + '&vend=' + vend + '&TS=' + tS;
   if ({$nh} > 0) url = url + "&nh={$nh}";
   document.location.href=url;
  },

  submitData:function(postat){
      if(application.wms_po_num != ''
          && postat != ''
      )
   {
     if (postat == 0) postat=1;
     else if (postat == 1) postat=0;
     axios.post('{$SRVPHP}', {
     action:'update',
      company: {$comp},
      wms_po_num :    application.wms_po_num,
      po_status :    postat
     }).then(function(response){
      application.showMessage(response.data.message);
      setTimeout(() => {  application.showMessage(""); }, 5000);
      application.ModelAU = false;
      if (application.aclose && "{$Redirect}" !== "")
      {
       window.location="{$Redirect}";
      }
      else
      {
       application.fetchAllData();
       application.initVars(false);
      }
     });
    }
  },
  fetchData:function(id){
   axios.post('{$SRVPHP}', {
    action:'fetchSingle',
    wms_po_num:id
    
   }).then(function(response){
    if (!Object.keys( response.data ).length)
    {
      alert('Record not found!');
      application.ModelAU = false;
      application.fetchAllData();
      application.initVars(false);
    }
    else
    {
     application.host_po_num     = response.data.host_po_num;
     application.wms_po_num      = response.data.wms_po_num  ;
     application.vendor          = response.data.vendor      ;
     application.est_deliv_date    = response.data.est_deliv_date;
     application.po_status     = response.data.po_status;
     application.num_lines    = response.data.num_lines;
     application.po_date   = response.data.po_date;
     application.statDesc   = response.data.statDesc;

     application.ModelAU      = true;
     application.actionButton = 'Set to "On Dock"';
     if (application.po_status == 1) 
      {
       application.actionButton = 'Reset to "Not Received';
      }
     application.enableSelect   = 1;
     application.dynamicTitle = 'Update Status of Receipt';
    }
   });
  },
 },
 
 created:function(){
  //if ( application.hpoSearch !== ""   ||  application.vendSearch !== "" ) 
   //{
        //this.aclose=true;
        //this.fetchData(application.hpoSearch,application.vendSearch);
   //}
  //else
  this.fetchAllData();
 }
});

</script>

HTML;
echo $htm;
?>
