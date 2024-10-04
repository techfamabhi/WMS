// COMPANY vue js script 

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
  mainServer:'{SRVPHP}'
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
   axios.post(this.mainServer, {
   action:'fetchall',
   company_number:'-1'
   }).then(function(response){
    application.allData = response.data;
   });
  },
  initVars(flg) {
   if (flg) { application.origId    =''; }
	application.company_number  ='';
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
     axios.post(this.mainServer, {
     action:'insert',
	company_number:  application.company_number,
	company_name:    application.company_name,
	company_address: application.company_address,
	company_city:    application.company_city,
	company_state:   application.company_state,
	company_zip:     application.company_zip,
	company_phone:   application.company_phone,
	company_abbr:    application.company_abbr,
	company_region:  application.company_region,
	company_fax_num: application.company_fax_num,
	company_logo:    application.company_logo,
	host_company:    application.host_company
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
     axios.post(this.mainServer, {
     action:'update',
	company_number:  application.company_number,
        company_name:    application.company_name,
        company_address: application.company_address,
        company_city:    application.company_city,
        company_state:   application.company_state,
        company_zip:     application.company_zip,
        company_phone:   application.company_phone,
        company_abbr:    application.company_abbr,
        company_region:  application.company_region,
        company_fax_num: application.company_fax_num,
        company_logo:    application.company_logo,
	host_company:    application.host_company
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
   axios.post(this.mainServer, {
    action:'fetchSingle',
    company_number:id
   }).then(function(response){
 	application.company_number  = response.data.company_number;
 	application.company_name    = response.data.company_name;
 	application.company_address = response.data.company_address;
 	application.company_city    = response.data.company_city;
 	application.company_state   = response.data.company_state;
 	application.company_zip     = response.data.company_zip;
 	application.company_phone   = response.data.company_phone;
 	application.company_abbr    = response.data.company_abbr;
 	application.company_region  = response.data.company_region;
 	application.company_fax_num = response.data.company_fax_num;
 	application.company_logo    = response.data.company_logo;
 	application.host_company    = response.data.host_company;

    application.ModelAU      = true;
    application.actionButton = 'Update';
    application.dynamicTitle = 'Edit Warehouse';
   });
  },
  deleteData:function(id){
   if(confirm("Are you sure you want to remove this Warehouse?"))
   {
    axios.post(this.mainServer, {
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
