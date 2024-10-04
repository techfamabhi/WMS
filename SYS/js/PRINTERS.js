// PRINTERS.js vue app for Printer Maintenance

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
  mainServer:'{SRVPHP}',
  drpServer:'{DRPSRV}'
 },
 methods:{
  fetchAllData:function(){
   axios.post(this.mainServer, {
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
   axios.post(this.drpServer, {
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
  // if (0 > 0) application.CompId=0;
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
     axios.post(this.mainServer, {
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
     axios.post(this.mainServer, {
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
  fetchData:function(id,lpt){
   axios.post(this.mainServer, {
    action:'fetchSingle',
    lpt_company:id,
    lpt_printer:lpt
   }).then(function(response){
 	application.lpt_company = response.data.lpt_company;
 	application.CompId = response.data.lpt_company;
 	application.selectedComp = response.data.lpt_company;
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
    axios.post(this.mainServer, {
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

