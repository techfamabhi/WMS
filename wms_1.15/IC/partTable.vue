<template>
  <div>
      <h2>Implement jQuery DataTable in Vue Js</h2>
      <table class="table" id="datatable">
        <thead>
          <tr>
            <th>ID</th>
            <th>P/L</th>
            <th>Part Number</th>
            <th>Description</th>
            <th>Class</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in products" :key="item.shadow_number">
            <td>{{item.p_l}}</td>
            <td>{{item.part_number}}</td>
            <td>{{item.part_desc}}</td>
            <td>{{item.part_class}}</td>
          </tr>
          
        </tbody>
      </table>    
  </div>
</template>

<link href="/jq/bootstrap.min.css" rel="stylesheet">
<script src="/jq/vue_2.6.14_min.js"></script>
<script src="/jq/axios.min.js"></script> 
<script src="/jq/jquery-1.10.2.min.js"></script> 
<script src="/jq/datatables.css"></script> 
<script src="/jq/datatables.min.css"></script> 

<script>

export default {
  mounted(){
    axios
    .get("https://www.testjsonapi.com/products/")
    .then((response)=>
    {
      this.products = response.data;
      $('#datatable').DataTable();
    })
  },
  data: function() {
        return {
            products:[]
        }
    },
}
</script>
