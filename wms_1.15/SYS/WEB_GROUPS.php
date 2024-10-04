<?php

// WEB_GROUPS.php -- Company Maintenance
// 12/09/21 dse initial
//TODO

/*
+------------+-------------+------+-----+---------+-------+
| Field      | Type        | Null | Key | Default | Extra |
+------------+-------------+------+-----+---------+-------+
| group_id   | smallint(6) | NO   | PRI | NULL    |       |
| group_desc | varchar(32) | YES  |     | NULL    |       |
+------------+-------------+------+-----+---------+-------+

*/

session_start();
if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

require($_SESSION["wms"]["wmsConfig"]);
$thisprogram="WEB_GROUPS.php";
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
$title="Groups Maintenance";
$panelTitle="Groups";
$Bluejay=$top;
$SRVPHP="{$wmsServer}/WEB_GROUPS_srv.php";
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
        <th class="FieldCaptionTD">Group#</th>
        <th class="FieldCaptionTD">Description</th>
       </tr>
       <tr v-for="row in allData">
        <td align="right">{{ row.group_id }}</td>
        <td>{{ row.group_desc }}</td>
        <td><button class="btnlink" name="edit" @click="fetchData(row.group_id)"><img src="images/edit2.png" border="0" title="Edit this Record"></button>
        <button class="btnlink" name="delete" @click="deleteData(row.group_id)"><img src="images/trash2.png" border="0" title="Delete this Record"></button></td>
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
<! -- Add dropdown for counterman, remove name, add percent and comments --!>
          <div class="form-group">
           <table>
            <tr>
             <td>
           <label title="Group Number">Group </label><span class="required">&nbsp;*</span>
           <input type="text" title="Group Number" class="form-control" v-model="group_id" />
            </td>
            <td>
           <label title="">Description</label><span class="required">&nbsp;*</span>
           <input type="text" title="The Description of the Group" class="form-control" v-model="group_desc"/>
            </td>
            </tr>
           </table>
          </div>
          <div class="form-group">
          <br />
          <div align="center">
           <input type="button" class="btn btn-success btn-xs" v-model="actionButton" @click="submitData" />
          </div>
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
  company_phone:'',
  company_fax_num:'',
  saveSuccess: false,
  updMessg: '',
  allData:'',
  ModelAU:false,
  actionButton:'Insert',
  dynamicTitle:'Add Group',
 },
 methods:{
  fetchAllData:function(){
   axios.post('{$SRVPHP}', {
   action:'fetchall',
   group_id:'-1'
   }).then(function(response){
    application.allData = response.data;
   });
  },
  initVars(flg) {
   if (flg) { application.origId = ''; }
	application.group_id ='';
	application.group_desc    ='';
  },
  showMessage: function(messg){
      // Set message
      application.updMessg = messg;
      application.saveSuccess=true;

                },
  openModel:function(){
   application.initVars(true);
   application.actionButton   = "Insert";
   application.dynamicTitle   = "Add Group";
   application.ModelAU        = true;
   application.enableSelect   = 1;

  },
  submitData:function(){
      if(application.group_id != ''
          && application.group_desc != ''
      )
   {
    if(application.actionButton == 'Insert')
    {
     axios.post('{$SRVPHP}', {
     action:'insert',
	group_id: application.group_id,
	group_desc: application.group_desc
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
	group_id: application.group_id,
        group_desc: application.group_desc
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
    group_id:id
   }).then(function(response){
 	application.group_id = response.data.group_id;
 	application.group_desc = response.data.group_desc;

    application.ModelAU      = true;
    application.actionButton = 'Update';
    application.dynamicTitle = 'Edit Group';
   });
  },
  deleteData:function(id){
   if(confirm("Are you sure you want to remove this Group?"))
   {
    axios.post('{$SRVPHP}', {
     action:'delete',
     group_id:id
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
