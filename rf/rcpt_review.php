<?php
//04/23/19 dse move bin validation to tmp_bin_xref instead of WHSELOC

/*TODO
if Adjust qty,  
if select distinct paud_inv_code from PARTHIST where paud_id = {internal po#} and paud_shadow = {shadow#}  <> "-"

to do that I must;
1) set qty to diff from old qty to new qty
2) update WHSEQTY
3) update WHSELOC
4) insert into PARTHIST
5) adjust RCPT_SCAN qty

if adjust bin, do two transactions,
   1) 1 take it out like above for the old bin or tote,
   2) put it back in on the new bin or tote like above
*/
foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
//error_reporting(0);

session_start();
//echo "<pre>";
//print_r($_REQUEST);
//echo "</pre>";
$thisprogram = $_SERVER["SCRIPT_NAME"];
$returnTo = $thisprogram;
if (isset($_REQUEST["sorter"])) $sorter = $_REQUEST["sorter"]; else $sorter = "";
if (isset($_REQUEST["sortDir"])) $sortDir = $_REQUEST["sortDir"]; else $sortDir = "";
require($_SESSION["wms"]["wmsConfig"]);
require_once("{$wmsInclude}/cl_rf1.php");
require_once("{$wmsInclude}/wr_log.php");
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/chklogin.php");
$SRV = "{$wmsServer}/RcptLine.php";

$sortasc = <<<HTML
<img src="../images/sort_asc.png" width="16" height="16" border="0" title="Sort Ascending"/>
HTML;
$sortdesc = <<<HTML
<img src="../images/sort_desc.png" width="16" height="16" border="0" title="Sort Descending"/>
HTML;
if (isset($_SESSION["rf"]["POs"])) {
    $POs = $_SESSION["rf"]["POs"];
    $jsonPOs = json_encode($POs);
} else {
    $POs = array();
    $jsonPOs = "[]";
}
if (!isset($nh)) $nh = 0;
$si = array();
$si['host_po_num'] = "&nbsp;";
$si['p_l'] = "&nbsp;";
$si['part_number'] = "&nbsp;";
$si['part_desc'] = "&nbsp;";
$si['partUOM'] = "&nbsp;";
$si['totalOrd'] = "&nbsp;";
$si['scanQty'] = "&nbsp;";
$si['pack_id'] = "&nbsp;";
if ($sorter <> "") {
    if ($sortDir == "") $sortDir = "asc";
    $dir = "sort{$sortDir}";
    $si[$sorter] = $$dir;
}


//temp
$main_ms = 1;
$sounds = "../assets";

$db = new WMS_DB;
$pg = new displayRF;
$pg->viewport = "1.0";
$pg->dispLogo = false;
$pg->jsh = <<<HTML
<script src="/jq/shortcut.js" type="text/javascript"></script>
<script src="/jq/vue_2.6.14_min.js"></script>
<script src="/jq/axios.min.js"></script>
<script>
shortcut.add("F1",function() {
        do_reset();
});
function setSort(fld)
{
 var ele=document.getElementById('sorter');
 var sdir=document.getElementById('sortDir');
 var sortArrow=document.getElementById('si_' + fld);
 ele.value=fld;
 sortArrow.innerHTML="";
 if (sdir.value === 'asc')
  {
   sortArrow.innerHTML='{$sortdesc}';
   document.getElementById('sortDir').value="desc";
  }
 else 
  {
   ele.value=fld;
   sortArrow.innerHTML='{$sortasc}';
   document.getElementById('sortDir').value="asc";
  }


 document.form1.submit();
}
function showHide(inflag)
{
 if (inflag) document.getElementById('editRcpt').style.display="block";
 else document.getElementById('editRcpt').style.display="none";
}
function editItem(batch,line)
{
 showHide(true);
 application.fetchData(batch,line);
 //alert(batch + ' ' + line);
}
function updData()
{
 showHide(false);
 application.submitData();
 //alert(batch + ' ' + line);
}
</script>

<style>
html,body,h1,h2,h3,h4,h5 {font-family: "Raleway", sans-serif}
.binbutton {
    background-color: #2196F3;
    border: none;
    color: white;
    padding: 15px 32px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 20px;
    margin: 4px 2px;
    cursor: pointer;
}
.binbutton:disabled {
    background-color: #dddddd;
}
.binbutton:enabled {
    background-color: #2196F3;
}
label {
 font-size: 1.2em;
}
strong {
 font-size: 1.2em;
}

</style>

HTML;
$inZoom = false;
if (!isset($_SESSION["rf"]["RECEIPT"]) and isset($batch_num)) {
    $rcpt = $batch_num;
    $inZoom = true;
} else $rcpt = $_SESSION["rf"]["RECEIPT"];

$tprompt = "Tote";
$xx = getRecvType($db, $rcpt);
if ($xx == "b") $tprompt = "Bin";
$receipt = get_rcpt($db, $rcpt, $sorter, $sortDir, $POs);
//print_r($receipt);

if (!isset($msg)) $msg = "";
$ptitle = "Received so Far";
if (isset($batch_num)) $ptitle = "Batch: {$batch_num} Detail";
$button = <<<HTML
<button name="goBack" onclick="javascript:history.back();" class="btn btn-info btn-xs">Back</button>
HTML;
if ($inZoom) {
    $button = <<<HTML
<button name="close" onclick="parent.cancel_modal()" class="btn btn-info btn-xs">Close</button>
HTML;

}

// Need to allow a checkbox to allow them to see all users entry for this PO
// need to make a form so it posts args back to this program for refresh
$userSel = <<<HTML
<input type="checkbox" name="user" value"{$UserID}">
<label for="user">Show All Users</label>

HTML;


$body = <<<HTML
<div class="panel panel-default">
 <div class="panel-heading">
  <div class="messg">{$msg}</div>
 </div>
 <br />
 <div class="row">
  <div class="col-md-4">
       <h3 class="panel-title">
{$button}
{$ptitle}</h3>
  </div>
 </div>
 </div>
  <div id="updMessg" class="messg"></div>
   <div class="panel-body">
    <div class="table-responsive">
      <form name="form1" action="{$thisprogram}" method="GET">
      <input type="hidden" name="sorter" id="sorter" value="{$sorter}">
      <input type="hidden" name="sortDir" id="sortDir" value="$sortDir">
      <table class="table table-bordered table-striped overflow-auto">
       <thead>
        <tr>
         <th onclick="setSort('host_po_num');" nowrap class="FieldCaptionTD">PO#<span id="si_host_po_num">{$si['host_po_num']}</span></th>
         <th onclick="setSort('p_l');" nowrap class="FieldCaptionTD">P/L<span id="si_p_l">{$si['p_l']}</span></th>
         <th onclick="setSort('part_number');" nowrap class="FieldCaptionTD">Part Number<span id="si_part_number">{$si['part_number']}</span></th>
         <th onclick="setSort('scanQty');" nowrap class="FieldCaptionTD">Received<span id="si_scanQty">{$si['scanQty']}</span></th>
         <th onclick="setSort('part_desc');" class="FieldCaptionTD">Description<span id="si_part_desc">{$si['part_desc']}</span></th>
         <th onclick="setSort('partUOM');" nowrap class="FieldCaptionTD">UOM<span id="si_partUOM">{$si['partUOM']}</span></th>
         <th onclick="setSort('pack_id');" nowrap class="FieldCaptionTD">Tote/Bin<span id="si_pack_id">{$si['pack_id']}</span></th>
         <th nowrap class="FieldCaptionTD">Prev Recvd</th>
         <th nowrap class="FieldCaptionTD" title="Qty scanned on other batches not yet complete">Other Recvd</th>
         <th nowrap class="FieldCaptionTD">TotalRecvd</th>
         <th onclick="setSort('totalOrd');" nowrap class="FieldCaptionTD">Expected<span id="si_totalOrd">{$si['totalOrd']}</span></th>
        </tr>
       </thead>

HTML;
if (count($receipt) > 0) {
    foreach ($receipt as $line => $item) {
        if (is_numeric($line)) {
            $ocls = "";
            $totalPrec = 0;
            $onclick = "";
            if (count($POs) > 0) {
                $onclick = <<<HTML
onclick="editItem({$item["batch_num"]},{$item["line_num"]});"
HTML;
            }
            if (isset($item["totalPrec"])) $totalPrec = $item["totalPrec"];
            $to = $item["totalOrd"] - $totalPrec;
            if ($to <> $item["totalOrd"]) $ocls = "class=\"Alt5DataTD\" ";
            if (!isset($item["otherBatchQty"])) $item["otherBatchQty"] = 0;
            if ($item["otherBatchQty"] > 0) $item["totalQty"] = $item["totalQty"] + $item["otherBatchQty"];
            $body .= <<<HTML
        <tr {$onclick}>
         <td align="right">{$item["host_po_num"]}</td>
         <td>{$item["p_l"]}</td>
         <td>{$item["part_number"]}</td>
         <td align="right">{$item["scanQty"]}</td>
         <td>{$item["part_desc"]}</td>
         <td>{$item["partUOM"]}</td>
         <td nowrap>{$item["pack_id"]}</td>
         <td align="right">{$totalPrec}</td>
         <td align="right">{$item["otherBatchQty"]}</td>
         <td align="right">{$item["totalQty"]}</td>
         <td {$ocls} align="right">{$item["totalOrd"]}</td>
        </tr>

HTML;
        } // end line is numeric
    } // end foreach receipt
} // count receipt > 0
$body .= <<<HTML
       </table>
      </form>
    </div>
   </div>
   <div id="editRcpt" >
    <transition name="model">
     <div class="modal-mask">
      <div class="modal-wrapper">
       <div class="modal-dialog">
        <div class="modal-content">
         <div class="modal-header">
          <button type="button" class="close" onclick="showHide(false)"><span aria-hidden="true">&times;</span></button>
          <span class="modal-title FormSubHeaderFont" id="dtitle"></span>
         </div>
         <div class="modal-body">
<! -- PO Select Select -->
          <div class="form-group">
           <input type="hidden" name="POId" id="POId" value="" />
           <label title="PO Number">PO Number</label>
           <select class="form-control" name="newPO" id="thePO">
          </select>
          </div>
<! -- in PO Select -->
          <div class="form-group">
           <label title="">Qty Recieved</label>
           <input type="number" id="newQty" value="" name="newQty" class="form-control"/>
          </div>
          <div class="form-group">
           <label title="The Bin/Cart or Pallet ID">{$tprompt}</label>
           <input type="text" class="form-control" id="newBin" value="" name="newBin" />
          </div>
          <div class="form-group">
           <br>
          </div>
          <div align="center">
           <input type="button" class="btn btn-success btn-xs" id="actionButton" value="Update" onclick="updData();" />
          </div>
         </div>                 <!= modal-body ->
        </div>                  <!- modal-content ->
       </div>                   <!- modal-dialog ->
      </div>                    <!- modal-wrapper ->
     </div>                     <!- modal-mask ->
    </transition>
   </div>                       <! - editRcpt ->

<script>
showHide(false);
</script>

<script>

var application = new Vue({
 el:'#editLine',
 data:{
  origQty:'',
  newQty:'',
  origBin:'',
  newBin:'',
  updMessg:'',
  POs:{$jsonPOs},
  saveSuccess: false,
  multiIndicator:'',
  multiPO:'',
  POId:'',
  batch:'',
  userId:"{$UserID}",
  linenum:'',
  p_l:'',
  part_number:'',
  partUOM:'',
  ModelAU:false,
  actionButton:'Update',
  dynamicTitle:'Edit Receipt',
 },
 methods:{
showMessage: function(messg){
      // Set message
      document.getElementById('updMessg').innerHTML=messg;
  },

  fetchData:function(batch,line){
   application.batch=batch;
   application.linenum=line;
   axios.post('{$SRV}', {
    action:'fetchSingle',
    POs: application.POs,
    batch:batch,
    line:line
   }).then(function(response){
 	application.origQty = response.data.Prec;
 	application.newQty = response.data.totalQty;
 	application.totalOrd = response.data.totalOrd;
 	application.origBin = response.data.pack_id;
 	application.newBin = response.data.pack_id;
 	application.p_l = response.data.p_l;
 	tmp = response.data.poCount;
        if (tmp > 1) multiIndicator="*"; else multiIndicator='';
        application.multiPO = response.data.multiPO;
        application.POId = response.data.po_number;
 	application.part_number = response.data.part_number;
 	application.partUOM = response.data.partUOM;
 	application.batch = response.data.batch_num;
 	application.linenum = response.data.line_num;
    //alert(JSON.stringify(response.data));
   // alert(response.data);
    application.actionButton = 'Update';
    application.dynamicTitle='Edit Receipt ';
    document.getElementById('POId').value=application.POId;
    document.getElementById('newQty').value=application.newQty;
    document.getElementById('newBin').value=application.newBin;
    document.getElementById('dtitle').innerHTML=application.dynamicTitle;

    //build PO dropdown
    //alert(JSON.stringify(response.data.multiPO));
  //alert(response.data.multiPO);
    var selPO=document.getElementById('thePO');
    selPO.length=0;
    const data = application.multiPO;
    let option;
    for(i=0; i< data.length; i++)
    {
     option = document.createElement('option');
     option.text = data[i].host_po_num;
     option.value = data[i].po_number;
     selPO.add(option);
     if (application.POId == data[i].po_number) selPO.selectedIndex=i;
    }
    // end PO dropdown
    application.ModelAU=true;
    showHide(true);
   });
  },
  submitData:function(){
    application.newQty=document.getElementById('newQty').value
    application.newBin=document.getElementById('newBin').value
    application.POId=document.getElementById('POId').value
    application.newPO=document.getElementById('thePO').value
      if(application.newQty != ''
          && application.newBin != ''
      )
   {
//alert(application.batch + " " + application.linenum);
    axios.post('{$SRV}', {
     action:'update',
	batch: application.batch,
        line: application.linenum,
        origBin: application.origBin,
        newBin: application.newBin,
        origQty: application.origQty,
        newQty: application.newQty,
        origPO: application.POId,
        newPO: application.newPO
     }).then(function(response){
    //alert(JSON.stringify(response.data));
      application.showMessage(response.data.message);
      setTimeout(() => {  application.showMessage(""); }, 5000);
      application.ModelAU=false;
      showHide(false);
     });
   }
  }
 }
});

</script>
HTML;
$pg->body = $body;
//$buttons=loadButtons("SubMit|clrbtn|complete|review");
//$pg->body.=$buttons;
$pg->Bootstrap = true;
$pg->title = "Review Receipt";
if (isset($nh) and $nh) $pg->noHeader = true;
$pg->Display();
echo "</body>\n</html>";


function get_rcpt($db, $rcpt, $sort, $sortDir, $POs)
{
    global $user;
    $byUser = "";
    if ($user <> 0) $byUser = " and scan_user = {$user} ";
    $ret = array();
    $ret["numRows"] = 0;
    $orderby = "";
    if ($sortDir == "desc") {
        $sort .= " desc";
    }
    if (trim($sort) <> "") $orderby = "order by {$sort},line_num";
    $SQL = <<<SQL
 select 
RCPT_SCAN.batch_num, 
host_po_num,
 po_number,
line_num,
p_l,
part_number,
part_desc,
 pkgUOM,
 scan_upc,
 part_desc,
 po_line_num,
 scan_status,
 scan_user,
 pack_id,
 shadow,
 partUOM,
 line_type,
 pkgQty,
 scanQty,
 totalQty,
 totalOrd,
 timesScanned,
 recv_to
from RCPT_INWORK,RCPT_SCAN, PARTS, POHEADER
where RCPT_INWORK.batch_num = {$rcpt}
and  RCPT_INWORK.batch_num = RCPT_SCAN.batch_num
and scan_status < 2
and shadow_number = shadow
and POHEADER.wms_po_num = po_number
{$byUser}
{$orderby}

SQL;


    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $ret[$i]["$key"] = $data;
                }
            }
        }
        $i++;
    } // while i < numrows
    $save_numrows = $numrows;
    if ($numrows > 0 and count($POs) > 0) {
        foreach ($ret as $key => $d) {
            if (is_array($d)) {
                $ret[$key]["otherBatchQty"] = 0;
                $SQL = <<<SQL
select sum(qty_ord) as Qty,
       sum(qty_recvd) as rQty
from POITEMS
where poi_po_num = {$d["po_number"]}
and shadow = {$d["shadow"]}

SQL;

                $rc = $db->query($SQL);
                $numrows = $db->num_rows();
                $i = 1;
                while ($i <= $numrows) {
                    $db->next_record();
                    if ($numrows) {
                        $ret[$key]["totalOrd"] = $db->f("Qty");
                        $ret[$key]["totalPrec"] = $db->f("rQty");
                    }
                    $i++;
                } // while i < numrows
                // check RCPT_SCAN to see if part has been recvd on another batch
                $SQL = <<<SQL
select sum(scanQty) as qty

 from RCPT_SCAN
where po_number = {$d["po_number"]}
and shadow = {$d["shadow"]}
and batch_num <> {$rcpt}
and scan_status < 2

SQL;
                $rc = $db->query($SQL);
                $numrows = $db->num_rows();
                $i = 1;
                while ($i <= $numrows) {
                    $db->next_record();
                    if ($numrows) {
                        $ret[$key]["otherBatchQty"] = $ret[$key]["otherBatchQty"] + $db->f("qty");
                    }
                    $i++;
                } // while i < numrows

            } // end is array d
        } // end foreach ret
    } // end numrows > 0
    $ret["numRows"] = $save_numrows;
    return ($ret);
} // end get_rcpt
function getRecvType($db, $batch)
{
    if (trim($batch) == "" or $batch == 0) {
        echo "<pre>";
        echo "Seems to be a problem with the cookie\nPlease report this to edavenbach@gmail.com";
        echo "\nSESSION\n";
        print_r($_SESSION);
        echo "REQUEST\n";
        print_r($_REQUEST);
        exit;

    }
    $SQL = <<<SQL
select distinct recv_to from RCPT_SCAN where batch_num = {$batch}

SQL;

    $ret = "";
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $ret = $db->f("recv_to");
        }
        $i++;
    } // while i < numrows
    return ($ret);
} // end getRecvType
?>
