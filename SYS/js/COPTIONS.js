// COPTDESC vue js script 

var application = new Vue({
 el:'#optionMaint',
 data:{
  enableSelect:1,
  saveSuccess: false,
  updMessg: '',
  allData:'',
  ModelAU:false,
  actionButton:'Insert',
  dynamicTitle:'Add Option',
  mainServer:'{SRVPHP}'
 },
 methods:{
  fetchAllData:function(){
   axios.post(this.mainServer, {
   action:'fetchall',
   cop_company:'-1'
   }).then(function(response){
    application.allData = response.data;
   });
  },
  initVars(flg) {
   if (flg) { application.origId    =''; }
	application.cop_company  ='';
	application.cop_option   ='';
	application.copt_desc    ='';
	application.copt_cat     ='';
	application.cop_flag     ='';
  },
  showMessage: function(messg){
      // Set message
      application.updMessg = messg;
      application.saveSuccess=true;

                },
  openModel:function(){
   application.initVars(true);
   application.actionButton   = "Insert";
   application.dynamicTitle   = "Add Warehouse Option";
   application.ModelAU        = true;
   application.enableSelect   = 1;

  },
  submitData:function(){
      if(application.cop_company != ''
          && application.cop_option != ''
      )
   {
    if(application.actionButton == 'Insert')
    {
     axios.post(this.mainServer, {
     action:'insert',
	cop_company:  application.cop_company,
	cop_option:    application.cop_option,
	cop_flag: application.cop_flag
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
	cop_company:  application.cop_company,
	cop_option:    application.cop_option,
	cop_flag: application.cop_flag
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
  fetchData:function(comp,id){
   axios.post(this.mainServer, {
    action:'fetchSingle',
    xop_company:comp,
    cop_option:id
   }).then(function(response){
 	application.cop_company  = response.data.cop_company;
 	application.cop_option   = response.data.cop_option;
 	application.copt_desc    = response.data.copt_desc;
 	application.cop_flag     = response.data.cop_flag;
 	application.copt_cat     = response.data.copt_cat;

    application.ModelAU      = true;
    application.actionButton = 'Update';
    application.dynamicTitle = 'Edit Warehouse Option';
   });
  },
  deleteData:function(comp,id){
   if(confirm("Are you sure you want to remove this Option?"))
   {
    axios.post(this.mainServer, {
     action:'delete',
     cop_company:comp,
     cop_option:id
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
