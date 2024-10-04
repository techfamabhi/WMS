<!-- BEGIN {po} AS po -->

	<!-- IF {__i} == 0 -->
	
    <div class="panel-body">
     <div class="table-responsive">
      <form name="form1" action="{thisprogram}" method="post">
      <input type="hidden" name="vendor" value="{vendor}">
      <input type="hidden" name="func" value="selectPO">
      <input type="hidden" name="lookPO" value="1">
      <input type="input" name="scaninput" value="" style="display:none">
      <table class="table table-bordered table-striped">
       <tr>
        <th class="FieldCaptionTD">&nbsp;</th>
        <th class="FieldCaptionTD">PO#</th>
        <th class="FieldCaptionTD">Date</th>
        <th class="FieldCaptionTD">Num Lines</th>
        <th class="FieldCaptionTD">Status</th>
        <th class="FieldCaptionTD">Exp Date</th>
       </tr>
	<!-- ELSE -->
       <tr>
        <td><input type="checkbox" name="POs[]" value="{wms_po_num}" onclick="chk_sel();"></td>
        <td align="right">{host_po_num}</td>
        <td>{po_date}</td>
        <td align="right">{num_lines}</td>
        <td>{po_statis}</td>
        <td>{est_deliv_date}</td>
       </tr>
	<!-- END -->
<!-- END po -->

