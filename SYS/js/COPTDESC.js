// COPTDESC vue js script 

var application = new Vue({
 el:'#coptMaint',
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
   copt_number:'-1'
   }).then(function(response){
    application.allData = response.data;
   });
  },
  initVars(flg) {
   if (flg) { application.origId    =''; }
	application.copt_number  ='';
	application.copt_desc    ='';
	application.copt_desc1   ='';
	application.copt_cat     ='';
	application.copt_text    ='';
  },
  showMessage: function(messg){
      // Set message
      application.updMessg = messg;
      application.saveSuccess=true;

                },
  openModel:function(){
   application.initVars(true);
   application.actionButton   = "Insert";
   application.dynamicTitle   = "Add Option";
   application.ModelAU        = true;
   application.enableSelect   = 1;

  },
  submitData:function(){
      if(application.copt_number != ''
          && application.copt_desc != ''
      )
   {
    if(application.actionButton == 'Insert')
    {
     axios.post(this.mainServer, {
     action:'insert',
	copt_number:  application.copt_number,
	copt_desc:    application.copt_desc,
	copt_desc1: application.copt_desc1,
	copt_cat:    application.copt_cat,
	copt_text:   application.copt_text
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
	copt_number:  application.copt_number,
	copt_desc:    application.copt_desc,
	copt_desc1: application.copt_desc1,
	copt_cat:    application.copt_cat,
	copt_text:   application.copt_text
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
    copt_number:id
   }).then(function(response){
 	application.copt_number  = response.data.copt_number;
 	application.copt_desc    = response.data.copt_desc;
 	application.copt_desc1   = response.data.copt_desc1;
 	application.copt_cat     = response.data.copt_cat;
 	application.copt_text    = response.data.copt_text;

    application.ModelAU      = true;
    application.actionButton = 'Update';
    application.dynamicTitle = 'Edit Option';
   });
  },
  deleteData:function(id){
   if(confirm("Are you sure you want to remove this Option?"))
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
