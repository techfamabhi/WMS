<?php

// WEB_MENU.php -- User Maintenance
// 11/26/21 dse initial
//TODO
//Add session
//get user priv level
//don't allow user to see or enter a priv level greator than their own.
// Change menu input to dropdown list of vvalid menus
// allow menu number entry in add mode if menu# not set

session_start();
if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

require($_SESSION["wms"]["wmsConfig"]);
$thisprogram="WEB_MENU.php";

require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
$title="Menu Maintenance";
$panelTitle="Menu";
$SRVPHP="{$wmsServer}/WEB_MENU_srv.php";
$DRPSRV="{$wmsServer}/dropdowns.php";
if (isset($_REQUEST["Redirect"])) $Redirect=$_REQUEST["Redirect"]; else $Redirect="";
if (isset($_REQUEST["menunum"])) $menunum=$_REQUEST["menunum"];
if (isset($_REQUEST["menu_num"])) $menunum=$_REQUEST["menu_num"];
else $menunum=20;
if (isset($_REQUEST["menu_line"])) $menuline=$_REQUEST["menu_line"]; else $menuline=-1;
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
      <div class="col-md-4">
       <h3 class="panel-title">{$panelTitle}</h3>
      </div>
      <div class="col-md-2" align="center">
       <table>
        <tr>
         <td class="FieldCaption">Menu#</td>
         <td><input type="number" min="1" max="999" class="form-control" v-model="menuSearch" @change="fetchAllData()"/></td>
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
        <th class="FieldCaptionTD">Menu#</th>
        <th class="FieldCaptionTD">Line</th>
        <th class="FieldCaptionTD">Description</th>
        <th class="FieldCaptionTD">URL</th>
        <th class="FieldCaptionTD">Priv</th>
        <th class="FieldCaptionTD">Image</th>
        <th class="FieldCaptionTD">Action</th>
       </tr>
       <tr v-for="row in allData">
        <td align="right">{{ row.menu_num }}</td>
        <td align="right">{{ row.menu_line }}</td>
        <td>{{ row.menu_desc }}</td>
        <td>{{ row.menu_url }}</td>
        <td align="right">{{ row.menu_priv }}</td>
        <td><img v-bind:src="row.menu_image"></td>
        <td><button class="btnlink" name="edit" @click="fetchData(row.menu_num,row.menu_line)"><img src="images/edit2.png" border="0" title="Edit this Record"></button>
        <button class="btnlink" name="delete" @click="deleteData(row.menu_num,row.menu_line)"><img src="images/trash2.png" border="0" title="Delete this Record"></button></td>
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
           <label>Menu#</label>
           <input type="text" title="Menu Number" class="form-control" v-model="menu_num" disabled />
            </td>
            <td>
           <label>Orig Line#</label>
           <input type="number" min="0" max="999" disabled class="form-control" v-model="orig_menu_line"/>
            </td>
            <td>
           <label>new Line#</label><span class="required">&nbsp;*</span>
           <input type="number" min="0" max="999" title="The line number of this menu item" class="form-control" v-model="menu_line"/>
            </td>
            </tr>
           </table>
          </div>
          <div class="form-group">
           <label title="Description">Description</label><span class="required">&nbsp;*</span>
           <input type="text" title="Description" class="form-control" v-model="menu_desc" />
          </div>
          <div class="form-group">
           <label title="Privilege Level of this menu item">Priv Level</label>
           <br>
           <input type="number" class="fix" v-autowidth="{maxWidth: '40px'}" min="0" max="99" title="Privilege Level" class="form-control" v-model="menu_priv"/>
          </div>
          <div class="form-group">
           <table>
            <tr>
             <td>
           <label title="The URL of this menu item">URL</label><span class="required">&nbsp;*</span>
           <input size="80" type="text" title="The URL if this menu item" class="form-control" v-model="menu_url" />
            </td>
            <td>
            </tr>
           </table>
          </div>

          <div class="form-group">
           <label>Image</label><span class="required">&nbsp;*<img v-bind:src="graphic">
</span>
           <select class="form-control" @change="changeSelectedGroup(\$event)" :disabled="enableSelect == 0">
            <option value="graphic">Please Select Image</option>
            <option v-for="Group in graphics" :key="Group.opt_val" :value="Group.opt_val" :selected="Group.opt_val === graphic">{{ Group.opt_desc }}</option>
          </select>

          </div>
          <div class="form-group">
            <label>Target</label>
            <select v-model="menu_target">
             <option value=" ">Default</option>
             <option value="_blank">New Window</option>
             <option value="_self">Same Window</option>
            </select>
          </div>
          <div class="form-group">
          <br />
          <div align="center">
           <input type="hidden" v-model="graphic" />
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
  graphic:'',
  graphics:'',
  aclose: false,
  menuSearch:{$menunum},
  enableSelect:1,
  saveSuccess: false,
  updMessg: '',
  selectedGraphic: null,
  allData:'',
  ModelAU:false,
  actionButton:'Insert',
  dynamicTitle:'Add User',
 },
 watch: {
  ModelAU: function (val) {
  if ( val == false && application.aclose && "{$Redirect}" !== "")
      {
       window.location="{$Redirect}";
      }
  }
 },
 methods:{
  fetchAllData:function(){
   axios.post('{$SRVPHP}', {
   action:'fetchall',
   priv:'{$UserPriv}',
   menu_num: this.menuSearch
   }).then(function(response){
    application.allData = response.data;
    if(typeof application.allData.menu_image !== "undefined")
    application.graphic=application.allData[0].menu_image;
    else
    application.graphic='';
   });
   //application.getGraphics('');
  },
  initVars(flg) {
   if (flg) { application.origId = ''; }
   application.menu_num       = '';
   application.menu_line      = '';
   application.orig_menu_line = '';
   application.menu_desc      = '';
   application.menu_url       = '';
   application.menu_priv      = '';
   application.menu_image     = '';
   application.menu_target    = ' ';
  },
  showMessage: function(messg){
      // Set message
      application.updMessg = messg;
      application.saveSuccess=true;

                },
  getGraphics:function(){
   axios.post('{$DRPSRV}', {
    action:'getGraphics'
   }).then(function(resp){
    application.graphics = resp.data;
   });

  },
  changeSelectedGroup (event) {
      this.selectedGraphic = event.target.options[event.target.options.selectedIndex].value
    this.graphic = this.selectedGraphic;
    },
  openModel:function(){
   application.initVars(true);
   application.actionButton   = "Insert";
   application.dynamicTitle   = "Add User";
   application.ModelAU        = true;
   application.menu_num = application.menuSearch;
   application.enableSelect   = 1;
   application.getGraphics();

  },
  submitData:function(){
      if(application.menu_num != ''
          && application.menu_line != ''
          && application.menu_desc != ''
          && application.menu_priv != ''
          && application.menu_url != ''
      )
   {
    if(application.actionButton == 'Insert')
    {
     axios.post('{$SRVPHP}', {
     action:'insert',
      menu_num :    application.menu_num,
      menu_line :    application.menu_line,
      menu_desc : application.menu_desc,
      menu_url : application.menu_url,
      menu_priv : application.menu_priv,
      menu_image : application.selectedGraphic,
      menu_target : application.menu_target
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
      menu_num :    application.menu_num,
      orig_menu_line :    application.orig_menu_line,
      menu_line :    application.menu_line,
      menu_desc : application.menu_desc,
      menu_url : application.menu_url,
      menu_priv : application.menu_priv,
      menu_image : this.graphic,
      menu_target : application.menu_target
     }).then(function(response){
      application.showMessage(response.data.message);
      setTimeout(() => {  application.showMessage(""); }, 5000);
      application.ModelAU = false;
      if (application.aclose && "{$Redirect}" !== "")
      {
       window.location="{$Redirect}";
      }
      else
      {
       application.fetchAllData();
       application.initVars(false);
      }
     });
    }
         //graphic : this.selectedGraphic,
   }
   else
   {
    alert("Please Enter All fields with an Asterisk");
   }
  },
  fetchData:function(id,line){
   axios.post('{$SRVPHP}', {
    action:'fetchSingle',
    menu_num:id,
    menu_line:line
    
   }).then(function(response){
    if (!Object.keys( response.data ).length)
    {
      alert('Record not found!');
      application.ModelAU = false;
      application.fetchAllData();
      application.initVars(false);
    }
    else
    {
     application.menu_num     = response.data.menu_num;
     application.menu_line    = response.data.menu_line;
     application.orig_menu_line    = response.data.menu_line;
     application.menu_desc    = response.data.menu_desc;
     application.menu_url     = response.data.menu_url;
     application.menu_priv    = response.data.menu_priv;
     application.menu_image   = response.data.menu_image;
     application.graphic      = response.data.menu_image;
     application.menu_target  = response.data.menu_target;
     application.getGraphics();
     application.ModelAU      = true;
     application.actionButton = 'Update';
     application.enableSelect   = 1;
     application.dynamicTitle = 'Edit Menu Item';
    }
   });
  },
  deleteData:function(id,line){
   if(confirm("Are you sure you want to remove this Menu Item?"))
   {
    axios.post('{$SRVPHP}', {
     action:'delete',
     menu_num:id,
     menu_line:line
    }).then(function(response){
      application.showMessage(response.data.message);
      setTimeout(() => {  application.showMessage(""); }, 5000);
     application.fetchAllData();
    });

   }
  }
 },
 
 created:function(){
  if ( {$menuline} > -1  &&  {$menunum} > 0 ) 
   {
        this.aclose=true;
        this.fetchData({$menunum},{$menuline});
   }
  else
  this.fetchAllData();
 }
});

</script>

HTML;
echo $htm;
?>
