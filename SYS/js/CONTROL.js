// 11/10/22 dse add old key so we can change the control key
var application = new Vue({
 el:'#controlMaint',
 data:{
  Comps:'',
  selectedComp: '',
  enableSelect:1,
  saveSuccess: false,
  updMessg: '',
  allData:'',
  ModelAU:false,
  actionButton:'Insert',
  dynamicTitle:'Add Control Record',
  mainServer:'{SRVPHP}',
  drpServer:'{DRPSRV}'
 },
 methods:{
  fetchAllData:function(){
   axios.post(this.mainServer, {
   action:'fetchall',
   control_company:'-1'
   }).then(function(response){
    application.allData = response.data;
   });
  },
  initVars(flg) {
   if (flg) { application.origId = ''; }
	application.control_company ='';
 	application.CompId = '';
	application.control_key    ='';
	application.control_number ='';
	application.control_maxnum ='';
	application.control_reset_to ='';
  },
  showMessage: function(messg){
      // Set message
      application.updMessg = messg;
      application.saveSuccess=true;

                },
   getComps:function(){
   axios.post(this.drpServer, {
    action:'getComps',
	company: '-1'
   }).then(function(resp){
    application.Comps = resp.data;
   });
  return(true);
  },
  changeSelectedComp (event) {
      this.selectedComp = event.target.options[event.target.options.selectedIndex].value
    this.control_company = this.selectedComp;
    },

  openModel:function(){
   application.initVars(true);
   application.actionButton   = "Insert";
   application.dynamicTitle   = "Add Control Record";
   application.ModelAU        = true;
   application.enableSelect   = 1;
   var x=false;
   x=application.getComps() 
//alert(x);
  // if ({$operComp} > 0) application.CompId={$operComp};
   //if (application.CompId == '' && application.Comps.length == 1) application.CompId=application.Comps[0].opt_val;
//alert('compid=' + application.CompId);
//alert('Comps optval ' + application.Comps[0].opt_val);
//alert('Comps length=' + application.Comps.length);

  },
  submitData:function(){
      if(application.control_company != ''
          && application.control_key != ''
      )
   {
    if(application.actionButton == 'Insert')
    {
     axios.post(this.mainServer, {
     action:'insert',
	control_company: application.selectedComp,
	control_key: application.control_key,
	control_number: application.control_number,
	control_maxnum: application.control_maxnum,
	control_reset_to: application.control_reset_to
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
	control_company: application.selectedComp,
        old_key: application.old_key,
        control_key: application.control_key,
        control_number: application.control_number,
	control_maxnum: application.control_maxnum,
	control_reset_to: application.control_reset_to
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
  fetchData:function(comp,key){
   axios.post(this.mainServer, {
    action:'fetchSingle',
    control_company:comp,
    control_key:key
   }).then(function(response){
 	application.control_company = response.data.control_company;
 	application.CompId = response.data.control_company;
 	application.selectedComp = response.data.control_company;
 	application.control_key = response.data.control_key;
 	application.old_key     = response.data.control_key;
 	application.control_number = response.data.control_number;
 	application.control_maxnum = response.data.control_maxnum;
 	application.control_reset_to = response.data.control_reset_to;
    application.ModelAU      = true;
    application.actionButton = 'Update';
    application.dynamicTitle = 'Edit Control Record';
   });
   application.getComps();
  },
  deleteData:function(comp,zn){
   if(confirm("Are you sure you want to remove this Control Record?"))
   {
    axios.post(this.mainServer, {
     action:'delete',
     control_company: comp,
     control_key: zn
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

