<?php

// WEB_USERS.php -- User Maintenance
// 11/26/21 dse initial
//TODO
//Add session
//get user priv level
//don't allow user to see or enter a priv level greator than their own.

session_start();
if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

require($_SESSION["wms"]["wmsConfig"]);
$thisprogram="WEB_USERS.php";

require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
$title="User Maintenance";
$panelTitle="Users";
$Bluejay=$top;
$SRVPHP="{$wmsServer}/WEB_USERS_srv.php";
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
        <th class="FieldCaptionTD">Id</th>
        <th class="FieldCaptionTD">User Name</th>
        <th class="FieldCaptionTD">First Name</th>
        <th class="FieldCaptionTD">Last Name</th>
        <th class="FieldCaptionTD">Priv From</th>
        <th class="FieldCaptionTD">Priv Thru</th>
        <th class="FieldCaptionTD">Company#</th>
        <th class="FieldCaptionTD">Home Menu#</th>
        <th class="FieldCaptionTD">Status</th>
        <th class="FieldCaptionTD">Group</th>
        <th class="FieldCaptionTD">Action</th>
       </tr>
       <tr v-for="row in allData">
        <td align="right">{{ row.id }}</td>
        <td>{{ row.username }}</td>
        <td>{{ row.first_name }}</td>
        <td>{{ row.last_name }}</td>
        <td align="right">{{ row.priv_from }}</td>
        <td align="right">{{ row.priv_thru }}</td>
        <td align="right">{{ row.company_num }}</td>
        <td align="right">{{ row.home_menu }}</td>
        <td>{{ row.status_flag }}</td>
        <td>{{ row.group_desc }}</td>
        <td><button class="btnlink" name="edit" @click="fetchData(row.id)"><img src="images/edit2.png" border="0" title="Edit this Record"></button>
        <button class="btnlink" name="delete" @click="deleteData(row.id)"><img src="images/trash2.png" border="0" title="Delete this Record"></button></td>
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
<input type="hidden" id="pwd" v-model="passwd">
         <div class="modal-body">
<! -- Add dropdown for counterman, remove name, add percent and comments --!>
          <div class="form-group">
           <table>
            <tr>
             <td>
           <label title="The User Name this person will logon with">User Name </label><span class="required">&nbsp;*</span>
           <input type="text" title="The User Name this person will logon with" class="form-control" v-model="username" />
            </td>
            <td>
           <label title="javascript('document.getElementById('pwd').value;">Password</label><span class="required">&nbsp;*</span>
           <input type="password" id="pwd1" title="The User Password this person will logon with" class="form-control" v-model="passwd" onmouseover="disp_pwd();" />
            </td>
            </tr>
           </table>
          </div>
          <div class="form-group">
           <table>
            <tr>
             <td>
           <label title="First Name">First Name</label><span class="required">&nbsp;*</span>
           <input type="text" title="First Name" class="form-control" v-model="first_name" />
            </td>
            <td>
           <label title="Last Name">Last Name</label><span class="required">&nbsp;*</span>
           <input type="text" title="Last Name" class="form-control" v-model="last_name" />
            </td>
            </tr>
            <tr>
            <td>
           <label title="Host ERP System User Id">Host User ID</label>
           <input type="text" title="Host ERP System User Id" class="form-control" v-model="host_user_id" />
            </td>
            </tr>
           </table>
          </div>
          <div class="form-group">
           <table>
            <tr>
             <td>
           <label title="Privilege Level From">Priv from</label>
           <input type="number" min="0" max="99" title="Privilege level from" class="form-control" v-model="priv_from" />
            </td>
            <td>
           <label title="Privilege Level Though">Priv thru</label>
           <input type="number" min="0" max="99" title="Privilege Level Though" class="form-control" v-model="priv_thru" />
            </td>
             <td>
           <label title="Company Number">Company#</label>
           <input size="5" type="number" title="Company Number the user is addigned to" class="form-control" v-model="company_num" />
            </td>
            <td>
           <label title="The Home Menu number for this user">Home Menu#</label>
           <input size="5" type="number" title="The Home Menu number for this user" class="form-control" v-model="home_menu" />
            </td>
            </tr>
           </table>
          </div>

          <div class="form-group">
           <label>Group</label><span class="required">&nbsp;*</span>
           <select class="form-control" @change="changeSelectedGroup(\$event)" :disabled="enableSelect == 0">
            <option value="GroupId">Please Select Group</option>
            <option v-for="Group in Groups" :key="Group.opt_val" :value="Group.opt_val" :selected="Group.opt_val === GroupId"> {{ Group.opt_desc }}</option>
          </select>

          </div>
          <div class="form-group">
            <label>Status</label>
            <select v-model="status_flag">
             <option value="A">Active</option>
             <option value="D">Deactivated</option>
             <option value="S">Suspended</option>
            </select>
          </div>
          <div class="form-group">
          <br />
          <div align="center">
           <input type="hidden" v-model="GroupId" />
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
  Groups:'',
  enableSelect:1,
  saveSuccess: false,
  updMessg: '',
  selectedGroup: null,
  allData:'',
  ModelAU:false,
  actionButton:'Insert',
  dynamicTitle:'Add User',
 },
 methods:{
  fetchAllData:function(){
   axios.post('{$SRVPHP}', {
   action:'fetchall',
   priv:'{$UserPriv}',
   group:'{$GroupID}'
   }).then(function(response){
    application.allData = response.data;
   });
   //application.getGroups('');
  },
  initVars(flg) {
   if (flg) { application.origId = ''; }
   application.GroupId       = '';
   application.username     = '';
   application.passwd     = '';
   application.passwd1     = '';
   application.pwdtitle     = '';
   application.first_name           = '';
   application.last_name           = '';
   application.host_user_id           = '';
   application.priv_from   = '0';
   application.priv_thru       = '0';
   application.sales_rep       = '0';
   application.company_num       = '0';
   application.home_menu       = '20';
   application.status_flag       = 'A';
   application.group_id       = '';
   application.group_desc       = '';
   application.theme_id       = '0';
   application.operator       = '0';
  },
  showMessage: function(messg){
      // Set message
      application.updMessg = messg;
      application.saveSuccess=true;

                },
  getGroups:function(){
   axios.post('{$DRPSRV}', {
    action:'getGroups'
   }).then(function(resp){
    application.Groups = resp.data;
   });

  },
  changeSelectedGroup (event) {
      this.selectedGroup = event.target.options[event.target.options.selectedIndex].value
    this.group_id = this.selectedGroup;
    },
  openModel:function(){
   application.initVars(true);
   application.actionButton   = "Insert";
   application.dynamicTitle   = "Add User";
   application.ModelAU        = true;
   application.enableSelect   = 1;
   application.getGroups();

  },
  submitData:function(){
      if(application.username != ''
          && application.passwd != ''
          && application.first_name != ''
          && application.last_name != ''
          && application.hiddenId != ''
      )
   {
    if(application.actionButton == 'Insert')
    {
     axios.post('{$SRVPHP}', {
     action:'insert',
      username :    application.username,
      passwd :    application.passwd,
      first_name : application.first_name,
      last_name : application.last_name,
      host_user_id : application.host_user_id,
      priv_from : application.priv_from,
      priv_thru : application.priv_thru,
      sales_rep : application.sales_rep,
      company_num : application.company_num,
      home_menu : application.home_menu,
      status_flag : application.status_flag,
      group_id : application.selectedGroup,
      theme_id : application.theme_id,
      operator : application.operator
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
      user_id : application.origId,
      username :    application.username,
      passwd :    application.passwd,
      first_name : application.first_name,
      last_name : application.last_name,
      host_user_id : application.host_user_id,
      priv_from : application.priv_from,
      priv_thru : application.priv_thru,
      sales_rep : application.sales_rep,
      company_num : application.company_num,
      home_menu : application.home_menu,
      status_flag : application.status_flag,
      group_id : application.group_id,
      theme_id : application.theme_id,
      operator : application.operator
     }).then(function(response){
      application.showMessage(response.data.message);
      setTimeout(() => {  application.showMessage(""); }, 5000);
      application.ModelAU = false;
      application.fetchAllData();
      application.initVars(false);
     });
    }
         //GroupId : this.selectedGroup,
   }
   else
   {
    alert("Please Enter All fields with an Asterisk");
   }
  },
  fetchData:function(id){
   axios.post('{$SRVPHP}', {
    action:'fetchSingle',
    id:id
   }).then(function(response){
    application.origId       = response.data.id;
    application.username   = response.data.username;
    application.passwd   = response.data.passwd;
    application.pwdtitle   = response.data.passwd;
    application.first_name   = response.data.first_name;
    application.last_name         = response.data.last_name;
    application.host_user_id         = response.data.host_user_id;
    application.priv_from = response.data.priv_from;
    application.priv_thru = response.data.priv_thru;
    application.sales_rep     = response.data.sales_rep;
    application.company_num     = response.data.company_num;
    application.home_menu     = response.data.home_menu;
    application.status_flag     = response.data.status_flag;
    application.group_id     = response.data.group_id;
    application.GroupId     = response.data.group_id;
    application.group_desc     = response.data.group_id;
    application.theme_id     = response.data.theme_id;
    application.operator     = response.data.operator;
    application.ModelAU      = true;
    application.actionButton = 'Update';
    application.dynamicTitle = 'Edit User';
   });
   application.getGroups();
  },
  deleteData:function(id){
   if(confirm("Are you sure you want to remove this User?"))
   {
    axios.post('{$SRVPHP}', {
     action:'delete',
     user_id:id
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
