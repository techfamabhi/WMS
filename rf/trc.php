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
foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; } 
//error_reporting(0);

session_start();
//echo "<pre>";
//print_r($_REQUEST);
//echo "</pre>";
$returnTo=$thisprogram;
$thisprogram=$_SERVER["SCRIPT_NAME"];
if (isset($_REQUEST["sorter"])) $sorter=$_REQUEST["sorter"]; else $sorter="";
if (isset($_REQUEST["sortDir"])) $sortDir=$_REQUEST["sortDir"]; else $sortDir="";
require($_SESSION["wms"]["wmsConfig"]);
require_once("{$wmsInclude}/cl_rf1.php");
require_once("{$wmsInclude}/wr_log.php");
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/chklogin.php");
$SRV="{$wmsServer}/RcptLine.php";

$sortasc=<<<HTML
<img src="../images/sort_asc.png" width="16" height="16" border="0" title="Sort Ascending"/>
HTML;
$sortdesc=<<<HTML
<img src="../images/sort_desc.png" width="16" height="16" border="0" title="Sort Descending"/>
HTML;
if (isset($_SESSION["rf"]["POs"]))
{
 $POs=$_SESSION["rf"]["POs"];
 $jsonPOs=json_encode($POs);
}
else 
{
 $POs=array();
 $jsonPOs="";
}
$si=array();
$si['host_po_num']="&nbsp;";
$si['p_l']="&nbsp;";
$si['part_number']="&nbsp;";
$si['part_desc']="&nbsp;";
$si['partUOM']="&nbsp;";
$si['totalOrd']="&nbsp;";
$si['totalQty']="&nbsp;";
$si['pack_id']="&nbsp;";
if ($sorter <> "")
{
 if ($sortDir == "") $sortDir="asc";
 $dir="sort{$sortDir}";
 $si[$sorter]=$$dir;
}


//temp
$main_ms=1;
$sounds="../assets";

$db = new WMS_DB;
$pg=new displayRF;
$pg->viewport="1.10";
$pg->dispLogo=false;
$pg->jsh=<<<HTML
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

if (!isset($_SESSION["rf"]["RECEIPT"]) and isset($batch_num)) $rcpt=$batch_num;
<?php
else $rcpt=$_SESSION["rf"]["RECEIPT"];
$tprompt="Tote";
$xx=getRecvType($db,$rcpt);
if ($xx == "b") $tprompt="Bin";
$receipt=get_rcpt($db,$rcpt,$sorter,$sortDir,$POs);
//print_r($receipt);

        //editItem(121,5);
$body=<<<HTML
<div class="panel panel-default">
    </div>
HTML;
$body.=<<<HTML
   <div id="editRcpt" v-if="ModelAU">
     <transition name="model">
     <div class="modal-mask">
      <div class="modal-wrapper">
       <div class="modal-dialog">
        <div class="modal-content">
         <div class="modal-header">
          <button type="button" class="close" onclick="showHide(false)"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title FormSubHeaderFont" id="dtitle"></h4>
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
           <label title="">Qty Recieved</label><span class="required">&nbsp;*</span>
           <input type="number" id="newQty" name="newQty" class="form-control"/>
          </div>
          <div class="form-group">
           <label title="The Bin/Cart or Pallet ID">{$tprompt}</label>
           <input type="text" class="form-control" id="newBin" name="newBin" />
          <div>
          <div class="form-group">
           <br>
          <div>
          <div align="center">
           <input type="button" class="btn btn-success btn-xs" id="actionButton" value="Update" onclick="updData();" />
          </div>
         </div>
        </div>
       </div>
      </div>
     </transition>
     </div>
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
  linenum:'',
  p_l:'',
  part_number:'',
  partUOM:'',
  ModelAU:false,
  actionButton:'Update',
  dynamicTitle:'Edit Reciept',
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
 	application.origQty = response.data.totalQty;
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
    //alert(JSON.stringify(application.multiPO));
    //alert(JSON.stringify(response.data));
    application.actionButton = 'Update';
    application.dynamicTitle='Edit Reciept ';
    document.getElementById('POId').value=application.POId;
    document.getElementById('newQty').value=application.newQty;
    document.getElementById('newBin').value=application.newBin;
    document.getElementById('dtitle').innerHTML=application.dynamicTitle;

    //build PO dropdown
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
  alertIt()
  {
alert(JSONStringify(application.thePO));
  },
  submitData:function(){
    application.newQty=document.getElementById('newQty').value
    application.newBin=document.getElementById('newBin').value
      if(application.newQty != ''
          && application.newBin != ''
      )
   {
//alert(application.batch + " " + application.linenum);
    axios.post('{$SRV}', {
     action:'update',
	batch: application.batch,
        line: application.linenum,
        newQty: application.newQty,
        newBin: application.newBin,
        origQty: application.origQty,
        origBin: application.origBin
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

 showHide(true);
 application.fetchData(121,5);
</script>
HTML;
$pg->body=$body;
//$buttons=loadButtons("SubMit|clrbtn|complete|review");
//$pg->body.=$buttons;
$pg->Bootstrap=true;
$pg->title="Review Receipt";
$pg->Display();


function get_rcpt($db,$rcpt,$sort,$sortDir,$POs)
{
 $ret=array();
 $ret["numRows"]=0;
 $orderby="";
 if ($sortDir == "desc")
  {
   $sort.=" desc";
  }
 if (trim($sort) <> "") $orderby="order by {$sort},line_num";
 $SQL=<<<SQL
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
{$orderby}

SQL;

  
$rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows and $db->Record)
     {
      foreach ($db->Record as $key=>$data)
       {
        if (!is_numeric($key)) { $ret[$i]["$key"]=$data; }
       }
     }
    $i++;
  } // while i < numrows
 $save_numrows=$numrows;
 if ($numrows > 0 and count($POs) > 0)
 {
   foreach ($ret as $key=>$d)
   {
    if (is_array($d))
    {
     $SQL=<<<SQL
select sum(qty_ord) as Qty from POITEMS
where poi_po_num = {$d["po_number"]}
and shadow = {$d["shadow"]}

SQL;

     $rc=$db->query($SQL);
     $numrows=$db->num_rows();
     $i=1;
     while ($i <= $numrows)
     {
      $db->next_record();
        if ($numrows)
        {
           $ret[$key]["totalOrd"]=$db->f("Qty");
        }
        $i++;
     } // while i < numrows
    } // end is array d
   } // end foreach ret
 } // end numrows > 0
 $ret["numRows"]=$save_numrows;
 return($ret);
} // end get_rcpt
function getRecvType($db,$batch)
{
 $SQL=<<<SQL
select distinct recv_to from RCPT_SCAN where batch_num = {$batch}

SQL;

  $ret=""; 
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $ret=$db->f("recv_to");
     }
     $i++;
   } // while i < numrows
 return($ret);
} // end getRecvType
?>
