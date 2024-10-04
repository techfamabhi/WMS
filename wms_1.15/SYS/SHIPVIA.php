<?php

// SHIPVIA.php -- Whse Zone Maintenance
// 12/09/21 dse initial
//TODO
// table needs to be modified to include is pickable
// maybe also put a wayable

/*
| Field       | Type        | Null | Key | Default | Extra |
+----------+----------+------+-----+---------+-------+
| via_code | char(3)  | NO   | PRI | NULL    |       |
| via_desc | char(30) | YES  |     | NULL    |       |
| via_SCAC | char(4)  | YES  |     | NULL    |       |
| pack_rescan | tinyint(4) | YES  |     | 0       |       |
| drop_zone   | char(3)    | YES  |     |         |       |



*/

session_start();
if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

require($_SESSION["wms"]["wmsConfig"]);
$thisprogram="SHIPVIA.php";
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
$title="Ship Via Maintenance";
$panelTitle="Ship Via Code Maintenance";
$Bluejay=$top;
$SRVPHP="{$wmsServer}/SHIPVIA_srv.php";
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
        <th class="FieldCaptionTD">Ship Via</th>
        <th class="FieldCaptionTD">Description</th>
        <th class="FieldCaptionTD">SCAC</th>
        <th class="FieldCaptionTD">Re-Scan</th>
        <th class="FieldCaptionTD">Drop Zone</th>
        <th class="FieldCaptionTD">Action</th>
       </tr>
       <tr v-for="row in allData">
        <td>{{ row.via_code }}</td>
        <td>{{ row.via_desc }}</td>
        <td>{{ row.via_SCAC }}</td>
        <td align="center"><input title="Force Re-Scan in Packing for this Ship Via" type="checkbox" true-value="1" false-value="0"  v-model="row.pack_rescan" disabled> </td>
        <td>{{ row.drop_zone }}</td>
        <td><button class="btnlink" name="edit" @click="fetchData(row.via_code)"><img src="images/edit2.png" border="0" title="Edit this Record"></button>
        <button class="btnlink" name="delete" @click="deleteData(row.via_code)"><img src="images/trash2.png" border="0" title="Delete this Record"></button></td>
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
           <label title="">Ship Via</label><span class="required">&nbsp;*</span>
           <input type="text" title="The Ship Via Code" class="form-control" v-model="via_code"/>
          </div>
          <div class="form-group">
           <label title="Description of Ship Via">Description</label>
           <input type="text" title="Description of Ship Via" class="form-control" v-model="via_desc" />
          <div>
          <div class="form-group">
           <label title="SCAC Code">SCAC</label>
           <input type="text" title="Standard Carrier Alpha Code" class="form-control" v-model="via_SCAC" />
          <div>
          <div class="form-group">
           <label title="Force Re-Scan in Packing">Force Re-Scan in Packing</label>
           <input title="Picking is allowed for this type" type="checkbox" true-value="1" false-value="0"  v-model="pack_rescan">
          <div>
          <div class="form-group">
           <label title="Shipping Drop Zone of this Ship Via">Shipping Drop Zone of this Ship Via</label>
           <input type="text" title="Shipping Drop Zone of this Ship Via" class="form-control" v-model="drop_zone" />
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
  dynamicTitle:'Add Ship Via',
 },
 methods:{
  fetchAllData:function(){
   axios.post('{$SRVPHP}', {
   action:'fetchall',
   }).then(function(response){
//alert(JSON.stringify(response.data));
    application.allData = response.data;
   });
  },
  initVars(flg) {
   if (flg) { application.origId = ''; }
	application.via_code    ='';
	application.via_desc ='';
	application.via_SCAC ='';
	application.pack_rescan ='';
	application.drop_zone ='';
  },
  showMessage: function(messg){
      // Set message
      application.updMessg = messg;
      application.saveSuccess=true;

   },
  openModel:function(){
   application.initVars(true);
   application.actionButton   = "Insert";
   application.dynamicTitle   = "Add Ship Via";
   application.ModelAU        = true;
   application.enableSelect   = 1;

  },
  submitData:function(){
      if(application.via_code != ''
      )
   {
    if(application.actionButton == 'Insert')
    {
     axios.post('{$SRVPHP}', {
     action:'insert',
	via_code: application.via_code,
	via_desc: application.via_desc,
 	via_SCAC: application.via_SCAC,
 	pack_rescan: application.pack_rescan,
 	drop_zone: application.drop_zone
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
        via_code: application.via_code,
        via_desc: application.via_desc,
 	via_SCAC: application.via_SCAC,
 	pack_rescan: application.pack_rescan,
 	drop_zone: application.drop_zone
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
    via_code:id
   }).then(function(response){
 	application.via_code = response.data.via_code;
 	application.via_desc = response.data.via_desc;
 	application.via_SCAC = response.data.via_SCAC;
 	application.pack_rescan = response.data.pack_rescan;
 	application.drop_zone = response.data.drop_zone;

    application.ModelAU      = true;
    application.actionButton = 'Update';
    application.dynamicTitle = 'Edit Ship Via';
   });
  },
  deleteData:function(zn){
   if(confirm("Are you sure you want to remove this Ship Via?"))
   {
    axios.post('{$SRVPHP}', {
     action:'delete',
     via_code: zn
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
