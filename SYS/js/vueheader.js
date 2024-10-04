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
