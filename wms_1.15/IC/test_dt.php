<?php

$htm=<<<HTML
<template>
  <div class="container mt-4">
    <JqueryTable/>
  </div>
</template>

<script>
//import JqueryTable from 'partTable.vue'
<script src="partTable.vue"></script>
<script type="module" src="partTable.vue"</script>

export default {
  name: 'App',
  components: {
    JqueryTable
  }
}
</script>

<style>
  .container {
    max-width: 100%;
  }
</style>
HTML;

echo $htm;
?>
