<?php

// UOMCODES.php -- Whse Zone Maintenance
// 12/20/21 dse initial
//TODO
// table needs to be modified to include is pickable
// maybe also put a wayable

/*
| Field       | Type        | Null | Key | Default | Extra |
+----------+----------+------+-----+---------+-------+
| uom          | char(3)  | NO   | PRI | NULL    |       |
| uom_desc     | char(30) | YES  |     | NULL    |       |
| uom_inv_code | char(1)  | YES  |     | NULL    |       |


*/

session_start();
if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

require($_SESSION["wms"]["wmsConfig"]);
$thisprogram="UOMCODES.php";
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
$title="UOM Code Maintenance";
$panelTitle="UOM Codes";
$Bluejay=$top;
$SRVPHP="{$wmsServer}/UOMCODES_srv.php";
$DRPSRV="{$wmsServer}/dropdowns.php";
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
       <h3 class="panel-title">{$panelTitle}</h3>
      </div>
      <div class="col-md-6" align="right">
       <button class="btnlink" @click="openModel" value="Add"><img border="0" src="images/add1.png" title="Add New Record"></button>
      </div>
     </div>
    </div>
    <div class="panel-body">
     <div class="table-responsive">
      <table class="table table-bordered table-striped">
       <tr>
        <th class="FieldCaptionTD">UOM Code</th>
        <th class="FieldCaptionTD">Description</th>
        <th class="FieldCaptionTD">Inv Code</th>
        <th class="FieldCaptionTD">Action</th>
       </tr>
       <tr v-for="row in allData">
        <td>{{ row.uom_code }}</td>
        <td>{{ row.uom_desc }}</td>
        <td>{{ row.uom_icode_desc }}</td>
        <td><button class="btnlink" name="edit" @click="fetchData(row.uom_code)"><img src="images/edit2.png" border="0" title="Edit this Record"></button>
        <button class="btnlink" name="delete" @click="deleteData(row.uom_code)"><img src="images/trash2.png" border="0" title="Delete this Record"></button></td>
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
           <input type="hidden" v-model="origId">
           <label title="">UOM Code</label><span class="required">&nbsp;*</span>
           <input type="text" title="The UOM Code Code" class="form-control" v-model="uom_code"/>
          </div>
          <div class="form-group">
           <label title="Description of UOM Code">Description</label>
           <input type="text" title="Description of UOM Code" class="form-control" v-model="uom_desc" />
          <div>
          <div class="form-group">
           <label>Inventory Code</label>
            <select v-model="uom_inv_code">
             <option value="0">Regular Inventory</option>
             <option value="1">Defective Inventory</option>
             <option value="2">Core Inventory</option>
            </select>

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
  ModelAU:false,
  actionButton:'Insert',
  dynamicTitle:'Add UOM Code',
 },
 methods:{
  fetchAllData:function(){
   axios.post('{$SRVPHP}', {
   action:'fetchall',
   }).then(function(response){
    application.allData = response.data;
   });
  },
  initVars(flg) {
   if (flg) { application.origId = ''; }
	application.uom_code    ='';
	application.uom_desc ='';
	application.uom_inv_code ='';
	application.uom_icode_desc ='';
  },
  showMessage: function(messg){
      // Set message
      application.updMessg = messg;
      application.saveSuccess=true;

   },
  openModel:function(){
   application.initVars(true);
   application.actionButton   = "Insert";
   application.dynamicTitle   = "Add UOM Code";
   application.ModelAU        = true;
   application.enableSelect   = 1;

  },
  submitData:function(){
      if(application.uom_code != ''
      )
   {
    if(application.actionButton == 'Insert')
    {
     axios.post('{$SRVPHP}', {
     action:'insert',
	uom_code: application.uom_code,
	orig_uom: application.uom_code,
	uom_desc: application.uom_desc,
 	uom_inv_code: application.uom_inv_code
     }).then(function(response){
      application.showMessage(response.data.message);
      setTimeout(() => {  application.showMessage(""); }, 5000);
      application.ModelAU        = false;
      application.enableSelect   = 0;
      application.fetchAllData();
      application.initVars(false);
     });
    }
    if(application.actionButton == 'Update')
    {
     axios.post('{$SRVPHP}', {
     action:'update',
        orig_uom: application.origId,
        uom_code: application.uom_code,
        uom_desc: application.uom_desc,
 	uom_inv_code: application.uom_inv_code
     }).then(function(response){
      application.showMessage(response.data.message);
      setTimeout(() => {  application.showMessage(""); }, 5000);
      application.ModelAU = false;
      application.fetchAllData();
      application.initVars(false);
     });
    }
   }
   else
   {
    alert("Please Enter All fields with an Asterisk");
   }
  },
  fetchData:function(id){
   axios.post('{$SRVPHP}', {
    action:'fetchSingle',
    uom_code:id
   }).then(function(response){
 	application.uom_code = response.data.uom_code;
 	application.origId = response.data.uom_code;
 	application.uom_desc = response.data.uom_desc;
 	application.uom_inv_code = response.data.uom_inv_code;

    application.ModelAU      = true;
    application.actionButton = 'Update';
    application.dynamicTitle = 'Edit UOM Code';
   });
  },
  deleteData:function(zn){
   if(confirm("Are you sure you want to remove this UOM Code?"))
   {
    axios.post('{$SRVPHP}', {
     action:'delete',
     orig_uom: zn,
     uom_code: zn
    }).then(function(response){
      application.showMessage(response.data.message);
      setTimeout(() => {  application.showMessage(""); }, 5000);
     application.fetchAllData();
    });

   }
  }
 },
 created:function(){
  this.fetchAllData();
 }
});

</script>

HTML;
echo $htm;
?>
