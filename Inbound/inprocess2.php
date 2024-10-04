<?php

// inprocess1.php -- PO currently in process
// 07/08/22 dse initial
// 01/15/24 dse Change Title and Detail button to Review
// 06/24/24 dse set Stocked to green if fully stocked
/*TODO

*/
foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
//error_reporting(0);

session_start();
require($_SESSION["wms"]["wmsConfig"]);
$thisprogram = "inprocess1.php";
$comp = 1;

require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");

require_once("{$wmsInclude}/cl_modal.php");
$Modal = new cl_Modal;
$Modal->mwidth = "78%";
$Modal->cwidth = "88%";
$Modal->cheight = "90%";
$Modal->init("myModal", "modalFrame");
//reaize the modal
$Modal->StyleSheet = str_replace("top: 60px;", "top: 20px;", $Modal->StyleSheet);
$Modal->StyleSheet = str_replace("left: calc( 0.25rem + 10% );", "left: calc( 0.25rem + 20.5% );", $Modal->StyleSheet);
$Modal->javaScript = str_replace("location.reload()", "do_refresh()", $Modal->javaScript);
//$Modal->Modal=str_replace('"close" onclick="cancel_modal();"','"close" @click="cancel_modal();"',$Modal->Modal);


$title = "Review/Finalize Receipts In Process";
$panelTitle = "Receipts";
// P po, D debit, T transfer, R cust return, A=ASN, S=Special Order
$panelTitle = <<<HTML
<select class="form-control" v-model="typSearch" placeholder="Select Type" @change="setType()">
 <option value="%" selected>All Receipts</option>
 <option value="P" >Purchase Orders</option>
 <option value="A">ASN&apos;s</option>
 <option value="T">Transfer&apos;s</option>
 <option value="R">Customer Returns</option>
 <option value="S">Special Orders</option>
</select>

HTML;
$SRVPHP = "{$wmsServer}/RcptLine.php";
$DRPSRV = "{$wmsServer}/dropdowns.php";
if (isset($_REQUEST["Redirect"])) $Redirect = $_REQUEST["Redirect"]; else $Redirect = "";
if (isset($_REQUEST["vendor"])) $vendor = $_REQUEST["vendor"]; else $vendor = "";
if (isset($_REQUEST["HPO"])) $HPO = $_REQUEST["HPO"]; else $HPO = "";
if (isset($_REQUEST["exp_date"])) $exp_date = $_REQUEST["exp_date"]; else $exp_date = "";
$js = <<<HTML
  <link rel="stylesheet"
      href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" />

  <link href="/jq/bootstrap.min.css" rel="stylesheet">
  <script src="/jq/vue_2.6.14_min.js"></script>
  <script src="/jq/axios.min.js"></script>
  <script src="/jq/jquery-1.12.4.js" type="text/javascript"></script>
  <style>
.hideTheDiv {
  display: none;
}
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
.parent ~ .cchild {
  display: none;
}
.open .parent ~ .cchild {
  display: table-row;
}
.parent {
  cursor: pointer;
}
tbody {
  color: #212121;
}
.open {
  background-color: #e6e6e6;
}

.open .cchild {
  background-color: #999;
  color: white;
}
.parent > *:last-child {
  width: 30px;
}
.parent i {
  transform: rotate(0deg);
  transition: transform .3s cubic-bezier(.4,0,.2,1);
  margin: -.5rem;
  padding: .5rem;
 
}
.open .parent i {
  transform: rotate(180deg)
}
  </style>

HTML;
$pg = new Bluejay;
$pg->title = $title;
if (isset($nh) and $nh > 0) $pg->noHeader = true; else $nh = 0;
$alljs = <<<HTML
{$js}
<script>
  function cancel_modal() {
    var modal = document.getElementById("myModal");
    document.getElementById('modalFrame').src = "";
    modal.style.display = "none";
    //do_refresh();
}
</script>

<! -- end js -->
{$Modal->StyleSheet}
<! -- end s -->

HTML;
//{$Modal->javaScript}
//<! -- end mjs -->
$pg->js = $alljs;
$pg->Display();
echo $Modal->Modal;


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
      <div class="col-md-4" align="center">
       <table>
        <tr>
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
        <th class="FieldCaptionTD">Name</th>
        <th class="FieldCaptionTD">#Batch</th>
        <th class="FieldCaptionTD">Num Items</th>
        <th class="FieldCaptionTD">Qty Ord</th>
        <th class="FieldCaptionTD">Prev Recvd</th>
        <th class="FieldCaptionTD">This Recvd</th>
        <th class="FieldCaptionTD">Open Qty</th>
        <th class="FieldCaptionTD">Stocked</th>
        <th class="FieldCaptionTD">Action</th>
       </tr>
       <template v-for="(row, index) in allData">
       <tr @click="openDetail(index)">
        <td>{{ row.host_po_num }}</td>
        <td>{{ row.vendor }}</td>
        <td>{{ row.name }}</td>
        <td align="right">{{ row.numBatches }}</td>
        <td align="right">{{ row.lineItems }}</td>
        <td align="right">{{ row.qty_ord }}</td>
        <td align="right">{{ row.prevRecvd }}</td>
        <td align="right">{{ row.thisRecvd }}</td>
        <td align="right">{{ row.openQty }}</td>
        <td align="right" v-bind:class="setDone(row.thisRecvd,row.Stocked)">{{ row.Stocked }}</td>
        <td>
<button name="detail" @click="showDetail(row.host_po_num,row.vendor,row.Batches);" class="btn btn-info"><i class="fa fa-share-square-o" aria-hidden="true" ></i> Review</button>
       </td>
       </tr>
       </template>
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
  promptPO: 'PO#',
  promptVend: 'Vendor',
  typSearch:'%',
  hpoSearch:'',
  vendSearch:'',
  enableSelect:1,
  saveSuccess: false,
  updMessg: '',
  allData:'',
  detailData:'',
  selectedBatch: [],
  actionButton:'Insert',
  dynamicTitle:'Expected Receipts',
 },
 methods:{
  fetchAllData:function(){
   axios.post('{$SRVPHP}', {
   action:'getOpenBatches1',
   company: {$comp},
   vendor: this.vendSearch,
   host_po_num: this.hpoSearch,
   postatus: "< 4",
   typeSearch: this.typSearch
   }).then(function(response){
    application.allData = response.data;
   });
  },
  initVars(flg) {
   if (flg) { application.comp = {$comp}; }
   application.host_po_num     = '';
   application.Batches     = '';
   application.wms_po_num       = '';
   application.batch_date  = '';
   application.num_lines       = '';
   application.po_status       = '';
   application.scan_status       = '';
   application.userName       = '';
  },
  showMessage: function(messg){
      // Set message
      application.updMessg = messg;
      application.saveSuccess=true;

  },
  setType() {
   var ty = application.typSearch;
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
  setChev(idx)
  {
   r="chevron" + idx;
   return(r);
  },
  setClass(po_status)
  {
   var rcls="bg-light";
   if (po_status == -1) { rcls="bg-info"; }
   if (po_status == 0)  { rcls="bg-info"; }
   if (po_status == 1)  { rcls="bg-success"; }
   if (po_status == 2)  { rcls="bg-success"; }
   if (po_status == 3)  { rcls="bg-primary"; }
   if (po_status == 4)  { rcls="bg-danger"; }
   if (po_status == 5)  { rcls="bg-danger"; }
   return(rcls);
  },
  setDone(recvd,stockd)
  {
   if (recvd == stockd) return "Alt4DataTD";
  },

  clearSearch: function() {
   application.hpoSearch='';
   application.vendSearch='';
   application.fetchAllData();
  },
  showDetail:function(ponum,vend,batch){
   //var url='POdetail.php?ponum=' + ponum + '&vend=' + vend + '&Redirect={$thisprogram}&batch=' + batch;
   //var url='../rf/rcpt_review.php?batch_num=' + batch + "&nh=1";
   var url='batch_review.php?po=' + ponum + '&batch_num=' + batch + "&nh=1";
   if ({$nh} > 0) url = url + "&nh={$nh}";
   //document.location.href=url;
  application.setframe(url);
  },
  setframe(ifr) {
     var modal = document.getElementById("myModal");
     document.getElementById('modalFrame').src = ifr;
     modal.style.display = "block";
    },

  openDetail(idx)
  {
//alert(document.getElementById("ROW" + idx).class);
  var cchilds = document.getElementsByTagName("tr");
//alert("idx=" + idx + " length=" + cchilds.length);
for (var i = 0; i < cchilds.length; i++) {
    //if ( i < 10 && cchilds[i].title !== undefined) alert("[" + cchilds[i].title + "]");
 if (cchilds[i].title == idx) {
 //alert(" in hide/unhide " + i + " " + idx + cchilds[i].hidden);
  cchilds[i].hidden = !cchilds[i].hidden;
  cid='chevron' + idx;
  var chev=document.getElementById(cid);
  if (cchilds[i].hidden)
  chev.innerHTML='<i class="fa fa-chevron-down"></i>';
  else chev.innerHTML='<i class="fa fa-chevron-up"></i>';
//alert(cchilds[i].hidden);
}
} // eend for loop
   
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
