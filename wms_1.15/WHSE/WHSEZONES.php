<?php

// WHSEZONES.php -- Whse Zone Maintenance
// 12/09/21 dse initial
// 03/14/22 dse add display seq
//TODO
/*
Table description
| Field        | Type        | Null | Key | Default | Extra |
+--------------+-------------+------+-----+---------+-------+
| zone_company | smallint(6) | YES  | MUL | NULL    |       |
| zone_type    | char(3)     | YES  |     | NULL    |       |
| zone         | char(3)     | YES  |     | NULL    |       |
| zone_desc    | char(30)    | YES  |     | NULL    |       |
| display_seq  | tinyint(4)  | YES  |     | NULL    |       |
| is_pickable  | tinyint(4)  | YES  |     | NULL    |       |
| zone_color   | char(7)     | YES  |     |         |       |

*/

session_start();
if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

require($_SESSION["wms"]["wmsConfig"]);
$thisprogram="WHSEZONES.php";
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
$title="Warehouse Zone Maintenance";
$panelTitle="Warehouse Zones";
$Bluejay=$top;
$SRVPHP="{$wmsServer}/WHSEZONES_srv.php";
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

$w=<<<HTML
<label>Zone Type</label>
         <select v-model="ZT" @change="fetchAllData();">
HTML;
$w1=<<<HTML
<label>Zone Type</label>
         <select v-model="zone_type">
HTML;
$type_select=<<<HTML
{$w}
          <option value="%" selected>All</option>
          <option value="PIC">Picking</option>
          <option value="PKG">Packing</option>
          <option value="SHP">Shipping</option>
          <option value="STG">Outgoing Staging</option>
          <option value="PUT">Putaway Staging</option>
          <option value="RET">Returns</option>
          <option value="SYS">System</option>
         </select>

HTML;
$type_select1=<<<HTML
{$w1}
          <option value="%" selected>All</option>
          <option value="PIC">Picking</option>
          <option value="PKG">Packing</option>
          <option value="SHP">Shipping</option>
          <option value="STG">Outgoing Staging</option>
          <option value="PUT">Putaway Staging</option>
          <option value="RET">Returns</option>
          <option value="SYS">System</option>
         </select>

HTML;

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
         <td width="50%" class="FormSubHeaderFont">
{$panelTitle}
         </td>
         <td>{$type_select}
         <td>
        </tr>
       </table>
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
        <th class="FieldCaptionTD">Type</th>
        <th class="FieldCaptionTD">Warehouse Zone</th>
        <th class="FieldCaptionTD">Description</th>
        <th class="FieldCaptionTD">Color</th>
        <th class="FieldCaptionTD">Display Seq</th>
        <th class="FieldCaptionTD">Pickable</th>
        <th class="FieldCaptionTD">Action</th>
       </tr>
       <tr v-for="row in allData">
        <td align="right">{{ row.zone_company }}</td>
        <td align="center" v-bind:class="setClass(row.zone_type)">{{ row.zone_type }}</td>
        <td>{{ row.zone }}</td>
        <td>{{ row.zone_desc }}</td>
        <td v-bind:style="{ 'background-color': row.zone_color }">&nbsp;</td>
        <td>{{ row.display_seq }}</td>
        <td>
<input title="Picking is allowed for this Zone" type="checkbox" true-value="1" false-value="0"  v-model="row.is_pickable" disabled> 
</td>
        <td><button class="btnlink" name="edit" @click="fetchData(row.zone_company,row.zone)"><img src="images/edit2.png" border="0" title="Edit this Record"></button>
        <button class="btnlink" name="delete" @click="deleteData(row.zone_company,row.zone)"><img src="images/trash2.png" border="0" title="Delete this Record"></button></td>
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
          </div> 
<! -- in Company Select -->

          <div class="form-group">
           <label title="">Zone</label><span class="required">&nbsp;*</span>
           <input type="text" title="The Warehouse Zone" class="form-control" v-model="zone"/>
          </div>
          <div class="form-group">
           {$type_select1}
          </div>
          <div class="form-group">
           <label title="Description of Zone">Description</label>
           <input type="text" title="Description of Zone" class="form-control" v-model="zone_desc" />
          <div>
          <div class="form-group">
           <label title="Zone Color">Zone Color</label>
           <input type="color" title="Zone Color" class="form-control" v-model="zone_color" />
          <div>
          <div class="form-group">
           <label title="Display Sequence">Display Sequence</label>
           <input type="text" title="Display Sequence" class="form-control" v-model="display_seq" />
          <div>
          <div class="form-group">
           <table>
            <tr>
            <td><input type="checkbox" title="Is this Zone Pickable" v-model="is_pickable" true-value="1" false-value="0" /></td>
            <td>&nbsp;<label title="Is this Zone Pickable">Pickable</label></td>
            </tr>
           </table>
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
  ZT: "%",
  zone_type: "",
  origKey: '',
  updMessg: '',
  allData:'',
  ModelAU:false,
  actionButton:'Insert',
  dynamicTitle:'Add Warehouse Zone',
 },
 methods:{
  fetchAllData:function(){
   axios.post('{$SRVPHP}', {
   action:'fetchall',
   zone_company:'-1',
   zone_type: this.ZT
   }).then(function(response){
    application.allData = response.data;
   });
  },
  initVars(flg) {
   if (flg) { application.origId = ''; }
	application.zone_company ='';
 	application.CompId = '';
	application.zone_type    ='';
	application.zone    ='';
	application.zone_desc ='';
	application.display_seq ='';
	application.zone_color ='#FFFFFF';
	application.is_pickable =false;
  },
  setClass(zType)
  {
   var rcls="";
   if (zType == "PIC")  { rcls="wms-pale-blue"; }
   if (zType == "PKG")  { rcls="wms-sand"; }
   if (zType == "PUT")  { rcls="wms-grey"; }
   if (zType == "RET")  { rcls="wms-pale-red"; }
   if (zType == "SHP")  { rcls="wms-orange"; }
   if (zType == "STG")  { rcls="wms-light-blue"; }
   if (zType == "SYS")  { rcls="wms-blue-grey"; }
   return(rcls);
   /*
 other colors
"wms-amber"
"wms-aqua"
"wms-light-blue"
"wms-light-green"
"wms-cyan"
"wms-blue-grey"
"wms-indigo"
"wms-khaki"
"wms-orange"
"wms-pink"
"wms-purple"
"wms-sand"
"wms-teal"
"wms-pale-red"
"wms-pale-green"
"wms-pale-yellow"
"wms-pale-blue"

   */
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
    this.zone_company = this.selectedComp;
    },

  openModel:function(){
   application.initVars(true);
   application.actionButton   = "Insert";
   application.dynamicTitle   = "Add Warehouse Zone";
   application.ModelAU        = true;
   application.enableSelect   = 1;
   application.getComps();

  },
  submitData:function(){
      if(application.zone_company != ''
          && application.zone != ''
          && application.zone_type != ''
      )
   {
    if(application.actionButton == 'Insert')
    {
     axios.post('{$SRVPHP}', {
     action:'insert',
	zone_company: application.selectedComp,
        zone_type: application.zone_type,
	zone: application.zone,
	zone_desc: application.zone_desc,
	display_seq: application.display_seq,
	zone_color: application.zone_color,
	is_pickable: application.is_pickable
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
	zone_company: application.selectedComp,
        zone_type: application.zone_type,
        origZone: application.origKey,
        zone: application.zone,
	display_seq: application.display_seq,
	zone_color: application.zone_color,
	is_pickable: application.is_pickable
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
  fetchData:function(id,zn){
   axios.post('{$SRVPHP}', {
    action:'fetchSingle',
    zone_company:id,
    zone:zn
   }).then(function(response){
 	application.zone_company = response.data.zone_company;
 	application.CompId       = response.data.zone_company;
 	application.selectedComp = response.data.zone_company;
        application.zone_type    = response.data.zone_type;
 	application.zone         = response.data.zone;
 	application.origKey     = response.data.zone;
 	application.zone_desc    = response.data.zone_desc;
	application.display_seq  = response.data.display_seq;
	application.zone_color   = response.data.zone_color;
	application.is_pickable  = response.data.is_pickable;
    application.ModelAU      = true;
    application.actionButton = 'Update';
    application.dynamicTitle = 'Edit Warehouse Zone';
   });
   application.getComps();
  },
  deleteData:function(comp,zn){
   if(confirm("Are you sure you want to remove this Warehouse Zone?"))
   {
    axios.post('{$SRVPHP}', {
     action:'delete',
     zone_company: comp,
     zone: zn
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
