<?php

// PRINTERS.php -- Whse Zone Maintenance
// 12/09/21 dse initial
//TODO
// table needs to be modified to include is pickable
// maybe also put a wayable

/*
| Field       | Type        | Null | Key | Default | Extra |
| lpt_number      | smallint(6) | NO   | PRI | NULL    |       |
| lpt_description | char(30)    | YES  |     | NULL    |       |
| lpt_company     | smallint(6) | YES  |     | NULL    |       |
| lpt_pathname    | char(128)   | YES  |     | NULL    |       |
| lpt_type        | char(20)    | YES  |     | NULL    |       |
| lpt_copy_code   | char(4)     | YES  |     | NULL    |       |
| lpt_prompt      | char(15)    | YES  |     | NULL    |      

*/

session_start();
if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

require($_SESSION["wms"]["wmsConfig"]);
$thisprogram="PRINTERS.php";
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
if (isset($company_num)) $operComp=$company_num;
else $operComp=0;
$title="Printer Maintenance";
$panelTitle="Printers";
$Bluejay=$top;
$SRVPHP="{$wmsServer}/PRINTERS_srv.php";
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
        <th class="FieldCaptionTD">Warehouse#</th>
        <th class="FieldCaptionTD">Printer</th>
        <th class="FieldCaptionTD">Description</th>
        <th class="FieldCaptionTD">Printer Pathname</th>
        <th class="FieldCaptionTD">Printer Type</th>
        <th class="FieldCaptionTD">Action</th>
       </tr>
       <tr v-for="row in allData">
        <td align="right">{{ row.lpt_company }}</td>
        <td>{{ row.lpt_number }}</td>
        <td>{{ row.lpt_description }}</td>
        <td>{{ row.lpt_pathname }}</td>
        <td>{{ row.lpt_type }}</td>
        <td><button class="btnlink" name="edit" @click="fetchData(row.lpt_company,row.lpt_number)"><img src="images/edit2.png" border="0" title="Edit this Record"></button>
        <button class="btnlink" name="delete" @click="deleteData(row.lpt_company,row.lpt_number)"><img src="images/trash2.png" border="0" title="Delete this Record"></button></td>
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
<! -- Company Select -->
           <div class="form-group">
           <input type="hidden" v-model="CompId" />
           <label title="Company/Store/Warehouse Number">Warehouse </label><span class="required">&nbsp;*</span>
           <select class="form-control" @change="changeSelectedComp(\$event)" :disabled="enableSelect == 0">
            <option value="CompId">Please Select Warehouse</option>
            <option v-for="Comp in Comps" :key="Comp.opt_val" :value="Comp.opt_val" :selected="Comp.opt_val === CompId"> {{ Comp.opt_desc }}</option>
          </select>
<! -- in Company Select -->

          <div class="form-group">
           <label title="">Printer</label><span class="required">&nbsp;*</span>
           <input type="text" title="The Printer" class="form-control" v-model="lpt_number"/>
          </div>
          <div class="form-group">
           <label title="Description of Printer">Description</label>
           <input type="text" title="Description of Printer" class="form-control" v-model="lpt_description" />
          <div>
          <div class="form-group">
           <label title="Path of Printer">Pathname</label>
           <input type="text" title="Path of Printer" class="form-control" v-model="lpt_pathname" />
          <div>
          <div class="form-group">
           <label title="Type of Printer">Printer Type</label>
           <input type="text" title="Type of Printer" class="form-control" v-model="lpt_type" />
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
  Comps:'',
  selectedComp: '',
  enableSelect:1,
  saveSuccess: false,
  updMessg: '',
  allData:'',
  ModelAU:false,
  actionButton:'Insert',
  dynamicTitle:'Add Printer',
 },
 methods:{
  fetchAllData:function(){
   axios.post('{$SRVPHP}', {
   action:'fetchall',
   lpt_company:'-1'
   }).then(function(response){
    application.allData = response.data;
   });
  },
  initVars(flg) {
   if (flg) { application.origId = ''; }
	application.lpt_company ='';
 	application.CompId = '';
	application.lpt_number    ='';
	application.lpt_description ='';
	application.lpt_pathname ='';
	application.lpt_type ='';
  },
  showMessage: function(messg){
      // Set message
      application.updMessg = messg;
      application.saveSuccess=true;

                },
   getComps:function(){
   axios.post('{$DRPSRV}', {
    action:'getComps'
   }).then(function(resp){
    application.Comps = resp.data;
   });
  return(true);
  },
  changeSelectedComp (event) {
      this.selectedComp = event.target.options[event.target.options.selectedIndex].value
    this.lpt_company = this.selectedComp;
    },

  openModel:function(){
   application.initVars(true);
   application.actionButton   = "Insert";
   application.dynamicTitle   = "Add Printer";
   application.ModelAU        = true;
   application.enableSelect   = 1;
   var x=false;
   x=application.getComps() 
//alert(x);
  // if ({$operComp} > 0) application.CompId={$operComp};
   //if (application.CompId == '' && application.Comps.length == 1) application.CompId=application.Comps[0].opt_val;
//alert('compid=' + application.CompId);
//alert('Comps optval ' + application.Comps[0].opt_val);
//alert('Comps length=' + application.Comps.length);

  },
  submitData:function(){
      if(application.lpt_company != ''
          && application.lpt_number != ''
      )
   {
    if(application.actionButton == 'Insert')
    {
     axios.post('{$SRVPHP}', {
     action:'insert',
	lpt_company: application.selectedComp,
	lpt_number: application.lpt_number,
	lpt_description: application.lpt_description,
	lpt_pathname: application.lpt_pathname,
	lpt_type: application.lpt_type
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
	lpt_company: application.selectedComp,
        lpt_number: application.lpt_number,
        lpt_description: application.lpt_description,
	lpt_pathname: application.lpt_pathname,
	lpt_type: application.lpt_type
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
    lpt_company:id
   }).then(function(response){
 	application.lpt_company = response.data.lpt_company;
 	application.CompId = response.data.lpt_company;
 	application.lpt_number = response.data.lpt_number;
 	application.lpt_description = response.data.lpt_description;
 	application.lpt_pathname = response.data.lpt_pathname;
 	application.lpt_type = response.data.lpt_type;
    application.ModelAU      = true;
    application.actionButton = 'Update';
    application.dynamicTitle = 'Edit Printer';
   });
   application.getComps();
  },
  deleteData:function(comp,zn){
   if(confirm("Are you sure you want to remove this Printer?"))
   {
    axios.post('{$SRVPHP}', {
     action:'delete',
     lpt_company: comp,
     lpt_number: zn
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
