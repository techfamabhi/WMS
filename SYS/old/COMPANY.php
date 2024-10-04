<?php

// COMPANY.php -- Company Maintenance
// 12/09/21 dse initial
//02/09/22 dse add host_company
//TODO

/*
        company_number smallint NOT NULL primary key,
        company_name char(34) NULL,
        company_address char(34) NULL,
        company_city char(30) NULL,
        company_state char(2) NULL,
        company_zip char(10) NULL,
        company_phone char(14) NULL,
        company_abbr char(10) NULL,
        company_region char(20) NULL,
        company_fax_num char(14) NULL,
        company_logo varchar(128) NULL, -- if null, use company 0 logo
	host_company char(6) null
*/

session_start();
if (get_cfg_var('wmsdir')) $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);

require($_SESSION["wms"]["wmsConfig"]);
$thisprogram = "COMPANY.php";
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
$title = "Warehouse Maintenance";
$panelTitle = "Warehouses";
$Bluejay = $top;
$SRVPHP = "{$wmsServer}/COMPANY_srv.php";
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
        <th class="FieldCaptionTD">Warehouse ID</th>
        <th class="FieldCaptionTD">Name</th>
        <th class="FieldCaptionTD">Address</th>
        <th class="FieldCaptionTD">City</th>
        <th class="FieldCaptionTD">State</th>
        <th class="FieldCaptionTD">Zip</th>
        <th class="FieldCaptionTD">Host ID</th>
        <th class="FieldCaptionTD">Phone</th>
        <th class="FieldCaptionTD">Fax</th>
        <th class="FieldCaptionTD">Abbr</th>
        <th class="FieldCaptionTD">Region</th>
       </tr>
       <tr v-for="row in allData">
        <td align="right">{{ row.company_number }}</td>
        <td>{{ row.company_name }}</td>
        <td>{{ row.company_address }}</td>
        <td>{{ row.company_city }}</td>
        <td>{{ row.company_state }}</td>
        <td>{{ row.company_zip }}</td>
        <td>{{ row.host_company }}</td>
        <td>{{ row.company_phone }}</td>
        <td>{{ row.company_fax_num }}</td>
        <td>{{ row.company_abbr }}</td>
        <td>{{ row.company_region }}</td>
        <td><button class="btnlink" name="edit" @click="fetchData(row.company_number)"><img src="images/edit2.png" border="0" title="Edit this Record"></button>
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
           <label title="Company/Store/Warehouse Number">Warehouse ID</label><span class="required">&nbsp;*</span>
           <input type="text" title="Company/Store/Warehouse Number" class="form-control" v-model="company_number" />
            </td>
            <td>
           <label title="">Name</label><span class="required">&nbsp;*</span>
           <input type="text" title="The Name of the Warehouse" class="form-control" v-model="company_name"/>
            </td>
            </tr>
           </table>
          </div>
          <div class="form-group">
           <label title="Address">Address</label>
           <input type="text" title="Address" class="form-control" v-model="company_address" />
          <div>
          <div class="form-group">
           <label title="City">City</label>
           <input type="text" title="City" class="form-control" v-model="company_city" />
            </td>
            </tr>
           </table>
          </div>
          <div class="form-group">
           <table>
            <tr>
             <td>
           <label title="State">State</label>
           <input type="text" size="2" title="State" class="form-control" v-model="company_state" />
             </td>
             <td>&nbsp;</td>
             <td>
           <label title="Zip Code">Zip</label>
           <input type="text" size="10" title="Zip Code" class="form-control" v-model="company_zip" />
             </td>
            </tr>
           </table>
          </div>
          <div class="form-group">
           <table>
            <tr>
             <td>
           <label title="Host Warehouse Id">Host ID</label><br>
           <input type="text" v-model="host_company">
            </td>
             <td>&nbsp;</td>
             <td>
           <label title="Phone Number">Phone</label><br>
           <input type="tel" v-model="company_phone" @input="acceptNumber">
            </td>
             <td>&nbsp;</td>
            <td>
           <label title="Fax Phone Number">Fax</label><br>
           <input type="tel" v-model="company_fax_num" @input="acceptFax">
            </td>
            </tr>
           </table>
          </div>

          <div class="form-group">
           <label title="Warehouse Abbreviation">Abbr</label>
           <input type="text" title="Warehouse Abbreviation" class="form-control" v-model="company_abbr" />
          </div>
          <div class="form-group">
           <label title="Warehouse Region">Region</label>
           <input type="text" title="Warehouse Region" class="form-control" v-model="company_region" />
          </div>
          <div class="form-group">
           <label title="Warehouse Logo">Logo</label>
           <input type="text" title="Warehouse Logo" class="form-control" v-model="company_logo" />
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
  dynamicTitle:'Add Warehouse',
 },
 methods:{
    acceptNumber() {
        var x = this.company_phone.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
  this.company_phone = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
    },
    acceptFax() {
        var x = this.company_fax_num.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
  this.company_fax_num = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
    },
  fetchAllData:function(){
   axios.post('{$SRVPHP}', {
   action:'fetchall',
   company_number:'-1'
   }).then(function(response){
    application.allData = response.data;
   });
  },
  initVars(flg) {
   if (flg) { application.origId = ''; }
	application.company_number ='';
	application.company_name    ='';
	application.company_address ='';
	application.company_city    ='';
	application.company_state   ='';
	application.company_zip     ='';
	application.company_phone   ='';
	application.company_abbr    ='';
	application.company_region  ='';
	application.company_fax_num ='';
	application.company_logo    ='';
	application.host_company    ='';
  },
  showMessage: function(messg){
      // Set message
      application.updMessg = messg;
      application.saveSuccess=true;

                },
  openModel:function(){
   application.initVars(true);
   application.actionButton   = "Insert";
   application.dynamicTitle   = "Add Warehouse";
   application.ModelAU        = true;
   application.enableSelect   = 1;

  },
  submitData:function(){
      if(application.company_number != ''
          && application.company_name != ''
      )
   {
    if(application.actionButton == 'Insert')
    {
     axios.post('{$SRVPHP}', {
     action:'insert',
	company_number: application.company_number,
	company_name: application.company_name,
	company_address: application.company_address,
	company_city: application.company_city,
	company_state: application.company_state,
	company_zip: application.company_zip,
	company_phone: application.company_phone,
	company_abbr: application.company_abbr,
	company_region: application.company_region,
	company_fax_num: application.company_fax_num,
	company_logo: application.company_logo,
	host_company: application.host_company
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
	company_number: application.company_number,
        company_name: application.company_name,
        company_address: application.company_address,
        company_city: application.company_city,
        company_state: application.company_state,
        company_zip: application.company_zip,
        company_phone: application.company_phone,
        company_abbr: application.company_abbr,
        company_region: application.company_region,
        company_fax_num: application.company_fax_num,
        company_logo: application.company_logo,
	host_company: application.host_company
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
    company_number:id
   }).then(function(response){
 	application.company_number = response.data.company_number;
 	application.company_name = response.data.company_name;
 	application.company_address = response.data.company_address;
 	application.company_city = response.data.company_city;
 	application.company_state = response.data.company_state;
 	application.company_zip = response.data.company_zip;
 	application.company_phone = response.data.company_phone;
 	application.company_abbr = response.data.company_abbr;
 	application.company_region = response.data.company_region;
 	application.company_fax_num = response.data.company_fax_num;
 	application.company_logo = response.data.company_logo;
 	application.host_company = response.data.host_company;

    application.ModelAU      = true;
    application.actionButton = 'Update';
    application.dynamicTitle = 'Edit Warehouse';
   });
  },
  deleteData:function(id){
   if(confirm("Are you sure you want to remove this Warehouse?"))
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
