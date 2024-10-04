<?php

// HAZARD_CODES.php -- Whse Zone Maintenance
// 12/09/21 dse initial
//TODO
// table needs to be modified to include is pickable
// maybe also put a wayable

/*
| Field       | Type        | Null | Key | Default | Extra |
+----------+----------+------+-----+---------+-------+
| haz_code | char(3)     | NO   | PRI | NULL    |       |
| haz_desc | varchar(30) | YES  |     | NULL    |       |


*/

session_start();
if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

require($_SESSION["wms"]["wmsConfig"]);
$thisprogram="HAZARD_CODES.php";
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
$title="Hazard Code Maintenance";
$panelTitle="Hazard Codes";
$Bluejay=$top;
$SRVPHP="{$wmsServer}/HAZARD_CODES_srv.php";
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
        <th class="FieldCaptionTD">Hazard Code</th>
        <th class="FieldCaptionTD">Description</th>
        <th class="FieldCaptionTD">Action</th>
       </tr>
       <tr v-for="row in allData">
        <td>{{ row.haz_code }}</td>
        <td>{{ row.haz_desc }}</td>
        <td><button class="btnlink" name="edit" @click="fetchData(row.haz_code)"><img src="images/edit2.png" border="0" title="Edit this Record"></button>
        <button class="btnlink" name="delete" @click="deleteData(row.haz_code)"><img src="images/trash2.png" border="0" title="Delete this Record"></button></td>
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
           <label title="">Hazard Code</label><span class="required">&nbsp;*</span>
           <input type="text" title="The Hazard Code Code" class="form-control" v-model="haz_code"/>
          </div>
          <div class="form-group">
           <label title="Description of Hazard Code">Description</label>
           <input type="text" title="Description of Hazard Code" class="form-control" v-model="haz_desc" />
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
  dynamicTitle:'Add Hazard Code',
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
	application.haz_code    ='';
	application.haz_desc ='';
  },
  showMessage: function(messg){
      // Set message
      application.updMessg = messg;
      application.saveSuccess=true;

   },
  openModel:function(){
   application.initVars(true);
   application.actionButton   = "Insert";
   application.dynamicTitle   = "Add Hazard Code";
   application.ModelAU        = true;
   application.enableSelect   = 1;

  },
  submitData:function(){
      if(application.haz_code != ''
      )
   {
    if(application.actionButton == 'Insert')
    {
     axios.post('{$SRVPHP}', {
     action:'insert',
	haz_code: application.haz_code,
	haz_desc: application.haz_desc
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
        haz_code: application.haz_code,
        haz_desc: application.haz_desc
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
    haz_code:id
   }).then(function(response){
 	application.haz_code = response.data.haz_code;
 	application.haz_desc = response.data.haz_desc;

    application.ModelAU      = true;
    application.actionButton = 'Update';
    application.dynamicTitle = 'Edit Hazard Code';
   });
  },
  deleteData:function(zn){
   if(confirm("Are you sure you want to remove this Hazard Code?"))
   {
    axios.post('{$SRVPHP}', {
     action:'delete',
     haz_code: zn
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
