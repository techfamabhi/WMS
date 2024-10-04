<?php

// BINTYPES.php -- Whse Zone Maintenance
// 12/09/21 dse initial
//TODO
// table needs to be modified to include is pickable
// maybe also put a wayable

/*
| Field       | Type        | Null | Key | Default | Extra |
+-------------+-------------+------+-----+---------+-------+
| typ_company | smallint(6) | NO   | PRI | NULL    |       |
| typ_code    | char(2)     | YES  |     | NULL    |       |
| typ_desc    | char(30)    | YES  |     | NULL    |       |

*/

session_start();
if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);

require($_SESSION["wms"]["wmsConfig"]);
$thisprogram = "BINTYPES.php";
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
$title = "Bin Type Maintenance";
$panelTitle = "Bin Types";
$Bluejay = $top;
$SRVPHP = "{$wmsServer}/BINTYPES_srv.php";
$DRPSRV = "{$wmsServer}/dropdowns.php";
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
$pg = new Bluejay;
$pg->title = $title;
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
        <th class="FieldCaptionTD">Bin Type</th>
        <th class="FieldCaptionTD">Description</th>
        <th class="FieldCaptionTD">Pick</th>
        <th class="FieldCaptionTD">Recv</th>
        <th class="FieldCaptionTD">Core</th>
        <th class="FieldCaptionTD">Defect</th>
        <th class="FieldCaptionTD">Action</th>
       </tr>
       <tr v-for="row in allData">
        <td align="right">{{ row.typ_company }}</td>
        <td>{{ row.typ_code }}</td>
        <td>{{ row.typ_desc }}</td>
        <td><input title="Picking is allowed for this type" type="checkbox" true-value="1" false-value="0"  v-model="row.typ_pick" disabled> </td>
        <td><input title="Receiving is allowed for this type" type="checkbox" true-value="1" false-value="0"  v-model="row.typ_recv" disabled> </td>
        <td><input title="This type is reserved for core items" type="checkbox" true-value="1" false-value="0"  v-model="row.typ_core" disabled> </td>
        <td><input title="This type is reserved for defective items" type="checkbox" true-value="1" false-value="0"  v-model="row.typ_defect" disabled> </td>
        <td><button class="btnlink" name="edit" @click="fetchData(row.typ_company,row.typ_code)"><img src="images/edit2.png" border="0" title="Edit this Record"></button>
        <button class="btnlink" name="delete" @click="deleteData(row.typ_company,row.typ_code)"><img src="images/trash2.png" border="0" title="Delete this Record"></button></td>
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
           <label title="">Bin Type</label><span class="required">&nbsp;*</span>
           <input type="text" title="The Bin Type" class="form-control" v-model="typ_code"/>
          </div>
          <div class="form-group">
           <label title="Description of Bin Type">Description</label>
           <input type="text" title="Description of Bin Type" class="form-control" v-model="typ_desc" />
          <div>
          <div class="form-group">
           <table align="center">
            <tr>
             <th title="Picking is allowed for this type" class="FieldCaptionTD">Pick</th>
             <th>&nbsp;</th>
             <th title="Receiving is allowed for this type" class="FieldCaptionTD">Recv</th>
             <th>&nbsp;</th>
             <th title="This type is reserved for core items" class="FieldCaptionTD">Core</th>
             <th>&nbsp;</th>
             <th title="This type is reserved for defective items" class="FieldCaptionTD">Defect</th>
            </tr>
            <tr>
             <td align="center"><input title="Picking is allowed for this type" type="checkbox" true-value="1" false-value="0"  v-model="typ_pick"> </td>
             <td>&nbsp;</td>
             <td align="center"><input title="Receiving is allowed for this type" type="checkbox" true-value="1" false-value="0"  v-model="typ_recv"> </td>
             <td>&nbsp;</td>
             <td align="center"><input title="This type is reserved for core items" type="checkbox" true-value="1" false-value="0"  v-model="typ_core"> </td>
             <td>&nbsp;</td>
             <td align="center"><input title="This type is reserved for defective items" type="checkbox" true-value="1" false-value="0"  v-model="typ_defect"> </td>
            </tr>
           </table>
           <br>
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
  selectedComp: null,
  enableSelect:1,
  saveSuccess: false,
  updMessg: '',
  allData:'',
  ModelAU:false,
  actionButton:'Insert',
  dynamicTitle:'Add Bin Type',
 },
 methods:{
  fetchAllData:function(){
   axios.post('{$SRVPHP}', {
   action:'fetchall',
   typ_company:'-1'
   }).then(function(response){
    application.allData = response.data;
   });
  },
  initVars(flg) {
   if (flg) { application.origId = ''; }
	application.typ_company ='';
 	application.CompId = '';
	application.typ_code    ='';
	application.typ_desc ='';
	application.typ_pick =0;
	application.typ_recv =0;
	application.typ_core =0;
	application.typ_defect =0;
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

  },
  changeSelectedComp (event) {
      this.selectedComp = event.target.options[event.target.options.selectedIndex].value
    this.typ_company = this.selectedComp;
    },

  openModel:function(){
   application.initVars(true);
   application.actionButton   = "Insert";
   application.dynamicTitle   = "Add Bin Type";
   application.ModelAU        = true;
   application.enableSelect   = 1;
   application.getComps();

  },
  submitData:function(){
      if(application.typ_company != ''
          && application.typ_code != ''
      )
   {
    if(application.actionButton == 'Insert')
    {
     axios.post('{$SRVPHP}', {
     action:'insert',
	typ_company: application.selectedComp,
	typ_code: application.typ_code,
	typ_desc: application.typ_desc,
 	typ_pick: application.typ_pick,
 	typ_recv: application.typ_recv,
 	typ_core: application.typ_core,
 	typ_defect: application.typ_defect
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
	typ_company: application.selectedComp,
        typ_code: application.typ_code,
        typ_desc: application.typ_desc,
 	typ_pick: application.typ_pick,
 	typ_recv: application.typ_recv,
 	typ_core: application.typ_core,
 	typ_defect: application.typ_defect
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
  fetchData:function(id,typ){
   axios.post('{$SRVPHP}', {
    action:'fetchSingle',
    typ_company:id,
    typ_code:typ
   }).then(function(response){
 	application.typ_company = response.data.typ_company;
 	application.CompId = response.data.typ_company;
 	application.selectedComp = response.data.typ_company;
 	application.typ_code = response.data.typ_code;
 	application.typ_desc = response.data.typ_desc;
 	application.typ_pick = response.data.typ_pick;
 	application.typ_recv = response.data.typ_recv;
 	application.typ_core = response.data.typ_core;
 	application.typ_defect = response.data.typ_defect;
    application.ModelAU      = true;
    application.actionButton = 'Update';
    application.dynamicTitle = 'Edit Bin Type';
   });
   application.getComps();
  },
  deleteData:function(comp,zn){
   if(confirm("Are you sure you want to remove this Bin Type?"))
   {
    axios.post('{$SRVPHP}', {
     action:'delete',
     typ_company: comp,
     typ_code: zn
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
