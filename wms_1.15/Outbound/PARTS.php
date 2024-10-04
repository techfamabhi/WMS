<?php

// PARTS.php -- Whse Zone Maintenance
// 12/09/21 dse initial
//04/5/22 correct next/last rec calc for buttons
//TODO

/*

*/

if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

session_start();
require($_SESSION["wms"]["wmsConfig"]);

$thisprogram="PARTS.php";
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
$title="Listing of Parts";
$panelTitle="Parts";
$Bluejay=$top;
$SRVPHP="{$wmsServer}/PARTS_srv.php";
$DRPSRV="{$wmsServer}/dropdowns.php";
  //<script src="/jq/jquery-1.10.2.min.js" type="text/javascript"></script>
$js=<<<HTML
  <link href="/jq/bootstrap.min.css" rel="stylesheet">
  <script src="/jq/vue_2.6.14_min.js"></script>
  <script src="/jq/axios.min.js"></script>
<script src="/jq/shortcut.js" type="text/javascript"></script>
<script language="javascript" type="text/javascript">
shortcut.add("pagedown",function() {
  document.getElementById('next').click();
});
shortcut.add("pageup",function() {
  document.getElementById('prev').click();
});
shortcut.add("home",function() {
  document.getElementById('first').click();
});
shortcut.add("end",function() {
  document.getElementById('last').click();
});
</script>

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
  .PL { width: 4em; }
  .PARTNUM { width: 22em; }
  .PARTDESC { width: 34em; }
  .CLS { width: 4em; }
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
  </style>
<script>
 function disp_pwd()
 {
     var j=document.getElementById('pwd');
     var j1=document.getElementById('pwd1');
     j1.title=j.value;
 }
</script>

HTML;
$pg=new Bluejay;
$pg->title=$title;
$pg->Body="body onload=\"document.getElementById('PLentry').focus();\"";
$pg->js=$js;
$pg->Display();


$htm=<<<HTML
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
      <div class="col-md-6">
       <table>
        <tr>
         <td valign="bottom">
       <label>P/L&nbsp;</label>
         </td>
         <td valign="top">
       <input id="PLentry" type="text" autofocus size="6" style="width: 8em" class="form-control" v-model="PL" @change="loadPL();"/>
         </td>
         <td valign="bottom">
          &nbsp;
         <input type="radio" value="" v-model="avail">
         <label for="availAll">All</label>
         <input type="radio" value="0" v-model="avail">
         <label for="availYes">With Inventory</label>
         <input type="radio" value="-1" v-model="avail">
         <label for="availNo">Neg Inventory</label>
           <input type="button" class="btn btn-info btn-xs" v-model="loadButton" @click="loadPL();" />
         </td>
        </tr>
       </table>
      </div>
      <div class="col-md-6" align="right">
       <table>
        <tr>
         <td valign="bottom">
       <label>Records per Page&nbsp;</label>
         </td>
         <td valign="top">
       <input type="number" min="10" max="500" style="width: 5em" class="form-control" v-model="pageSize"/>
         </td>
        </tr>
       </table>
      </div>
     </div>
    </div>
    <div class="panel-body">
     <div class="table-responsive">
      <table class="table table-bordered table-striped">
       <tr>
        <th class="FieldCaptionTD">P/L</th>
        <th class="FieldCaptionTD">Part Number</th>
        <th class="FieldCaptionTD">Description</th>
        <th class="FieldCaptionTD">Class</th>
        <th class="FieldCaptionTD">Qty Avail</th>
        <th nowrap class="FieldCaptionTD">Primary Bin</th>
        <th class="FieldCaptionTD">Action</th>
       </tr>
       <tr v-for="row in allData">
        <td class="PL">{{ row.p_l }}</td>
        <td class="PARTNUM">{{ row.part_number }}</td>
        <td class="PARTDESC">{{ row.part_desc }}</td>
        <td class="CLS">{{ row.part_class }}</td>
        <td align="right" class="CLS">{{ row.qty_avail }}</td>
        <td nowrap class="CLS">{{ row.primary_bin }}</td>
        <td><button class="btnlink" name="edit" @click="fetchData(row.shadow_number)"><img src="../images/edit2.png" border="0" title="Edit this Record"></button>
       </tr>
        <td align="right"><strong>Row</strong></td>
        <td>
         <strong>{{ rowFrom }} - {{ rowThru }} of {{ rowCount }} </strong>
	</td>
        <td align="left" colspan="2">
         <a id="first" @click="fetchPage('first');"><img src="../Themes/Multipads/Images/FirstOff.gif" border="0" title="First Page"/></a>
         <a id="prev" @click="fetchPage('prev');"><img src="../Themes/Multipads/Images/PrevOff.gif" border="0" title="Previous Page"/></a>
         &nbsp; &nbsp; &nbsp;
	 <a id="next" @click="fetchPage('next');"><img src="../Themes/Multipads/Images/NextOff.gif" border="0" title="Next Page"/></a>
         <a id="last" @click="fetchPage('last');"><img src="../Themes/Multipads/Images/LastOff.gif" border="0" title="Last Page"/></a>
        </td>
       <tr>
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
           <label title="">P/L</label><span class="required">&nbsp;*</span>
           <input type="text" title="The P/L Code" class="form-control" v-model="p_l"/>
          </div>
          <div class="form-group">
           <label title="">Part Number</label><span class="required">&nbsp;*</span>
           <input type="text" title="The Parts Code" class="form-control" v-model="part_number"/>
          </div>
          <div class="form-group">
           <label title="Description of Parts">Description</label>
           <input type="text" title="Description of Parts" class="form-control" v-model="part_desc" />
          <div>
          <div class="form-group">
           <label title="Class">Class</label>
           <input type="text" title="Class" class="form-control" v-model="part_class" />
          <div>


          <div align="center">
           <input type="button" class="btn btn-success btn-xs" v-model="actionButton" @click="submitData" />
          </div>
         </div>
        </div>
       </div>
      </div>
     </div>
    </transition>
   </div>
  </div>
 </body>
</html>

<script>

var application = new Vue({
 el:'#crudApp',
 data:{
  enableSelect:1,
  saveSuccess: false,
  updMessg: '',
  allData:'',
  PL:'',
  avail:0,
  rowCount:0,
  rowFrom:0,
  rowThru:0,
  startRec:1,
  pageSize:10,
  ModelAU:false,
  actionButton:'Insert',
  loadButton:'Reload',
  dynamicTitle:'Add Parts',
 },
 methods:{
  fetchAllData:function(pl,nRows,start){
   if (pl.trim() != "")
   {
    axios.post('{$SRVPHP}', {
    action:'fetchall',
    numRows: nRows,
    startRec: start,
    p_l: pl,
    avail: application.avail
    }).then(function(response){
     application.allData = response.data.rowData;
     application.rowCount = response.data.rowCount;
     application.rowFrom = response.data.rowFrom;
     application.rowThru = response.data.rowThru;
 //alert(application.rowFrom + ' ' + application.rowThru);
    });
   } // end pl <> ""
   else document.getElementById('PLentry').focus();
  },
  initVars(flg) {
   if (flg) { application.shadow_number = ''; }
	application.p_l    ='';
	application.part_number ='';
	application.part_desc ='';
	application.part_class ='';
  },
  showMessage: function(messg){
      // Set message
      application.updMessg = messg;
      application.saveSuccess=true;

   },
  openModel:function(){
   application.initVars(true);
   application.actionButton   = "Insert";
   application.dynamicTitle   = "Add Parts";
   application.ModelAU        = true;
   application.enableSelect   = 1;

  },
  submitData:function(){
      if(application.shadow_number != ''
      )
   {
    if(application.actionButton == 'Insert')
    {
     axios.post('{$SRVPHP}', {
     action:'insert',
	shadow_number: application.shadow_number,
	p_l: application.p_l,
	part_number: application.part_number,
	part_desc: application.part_desc,
 	part_class: application.part_class
     }).then(function(response){
      application.showMessage(response.data.message);
      setTimeout(() => {  application.showMessage(""); }, 5000);
      application.ModelAU        = false;
      application.enableSelect   = 0;
      application.fetchAllData(this.PL,this.pageSize);
      application.initVars(false);
     });
    }
    if(application.actionButton == 'Update')
    {
     axios.post('{$SRVPHP}', {
     action:'update',
        shadow_number: application.shadow_number,
	p_l: application.p_l,
	part_number: application.part_number,
        part_desc: application.part_desc,
 	part_class: application.part_class
     }).then(function(response){
      application.showMessage(response.data.message);
      setTimeout(() => {  application.showMessage(""); }, 5000);
      application.ModelAU = false;
      application.fetchAllData(application.PL,this.pageSize);
      application.initVars(false);
     });
    }
   }
   else
   {
    alert("Please Enter All fields with an Asterisk");
   }
  },
  loadPL:function(){
   if (this.PL.trim() != "")
   {
    this.PL=this.PL.toUpperCase();
    application.PL=this.PL;
    this.fetchAllData(this.PL,this.pageSize, this.startRec);
   }
   else document.getElementById('PLentry').focus();
  },
  fetchPage:function(cmd){
  document.getElementById('first').style.visibility="visible";
  document.getElementById('prev').style.visibility="visible";
  document.getElementById('next').style.visibility="visible";
  document.getElementById('last').style.visibility="visible";
  if (cmd == 'first') { this.startRec = 1; }
  if (cmd == 'prev')  { this.startRec = ( parseInt(this.startRec) - parseInt(this.pageSize) ); }
  if (cmd == 'next')  { this.startRec = ( parseInt(this.startRec) + parseInt(this.pageSize) ); }
  if (cmd == 'last')  { 
   this.startRec = ( parseInt(this.rowCount) - parseInt(this.pageSize) ); 
   document.getElementById('next').style.visibility="hidden";
   document.getElementById('last').style.visibility="hidden";
  }

  if (parseInt(this.startRec) < 1) this.startRec=1;
  if (parseInt(this.startRec) < 2) {
   document.getElementById('first').style.visibility="hidden";
   document.getElementById('prev').style.visibility="hidden";
  }
  if (parseInt(this.startRec) > ( parseInt(this.rowCount)  - parseInt(this.pageSize) ) + 1) {
   this.startRec = ( parseInt(this.rowCount) - parseInt(this.pageSize) );
   document.getElementById('next').style.visibility="hidden";
   document.getElementById('last').style.visibility="hidden";
  }
  application.fetchAllData(this.PL,this.pageSize, this.startRec);
  },
  fetchData:function(id){
   axios.post('{$SRVPHP}', {
    action:'fetchSingle',
    shadow_number:id
   }).then(function(response){
 	application.shadow_number = response.data.shadow_number;
 	application.p_l = response.data.p_l;
 	application.part_number = response.data.part_number;
 	application.part_desc = response.data.part_desc;
 	application.part_class = response.data.part_class;

    application.ModelAU      = true;
    application.actionButton = 'Update';
    application.dynamicTitle = 'Edit Part';
   });
  },
  deleteData:function(zn){
   if(confirm("Are you sure you want to remove this Part?"))
   {
    axios.post('{$SRVPHP}', {
     action:'delete',
     shadow_number: zn
    }).then(function(response){
      application.showMessage(response.data.message);
      setTimeout(() => {  application.showMessage(""); }, 5000);
      application.fetchAllData(this.PL,this.pageSize, this.startRec);
    });

   }
  }
 },
 created:function(){
  this.fetchAllData(this.PL,this.pageSize, this.startRec);
 }
});

</script>

HTML;
echo $htm;
?>
