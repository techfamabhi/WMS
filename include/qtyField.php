<?php
/* class qtyField -- div html of qty field with plus and minus signs

example: $qf=new qtyField;
         $js=$qf->js; // return js and sytlesheet to include in html

         Args to QtyInput
          Prompt
          fieldName (optional)
          fieldId (optional)
         // qtyfield and qtyid are optional, if not passed, they are set as below
         $qtyfield="quantity";
         $qtyid="Qty";

         $htm=$qf->qtyInput($qtyfield,$qtyid); // return html to include in web page
or 
         $htm=$qf->qtyInput(); // return html to include in web page

 the html is returned from the qtyInput call, witch also sets $qf->htm;

 to set max qty set the qtyBin field to max qty
*/

class qtyField
{
    public $js;
    public $htm;
    public $defQty = 1; // default qty
    public $qtyMin = "0"; // default Min
    public $qtyMax = ""; // default Max
    public $qtyBin = ""; // qty in bin
    public $required = false;
    public $onfocus = "";

    public function __construct()
    {
        $this->htm = "";
        $this->js = <<<HTML
<script>
 function plusMinus(fld,flag, qmin=0, qmax=9999)
 {
  var qty=document.getElementById(fld);
  if (flag > 0) qty.value++;
  else qty.value--;
  if (qty.value < qmin) qty.value++;
  if (qty.value > qmax) qty.value--;
  return true;
 }
</script>
<style>
input,
textarea {
  border: 1px solid #eeeeee;
  box-sizing: border-box;
  margin: 0;
  outline: none;
  padding: 10px;
}

input[type="button"] {
  -webkit-appearance: button;
  cursor: pointer;
}

input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
}

.input-group {
  clear: both;
  margin: 5px 0;
  position: relative;
}

.input-group input[type='button'] {
  background-color: #eeeeee;
  min-width: 38px;
  width: auto;
  transition: all 300ms ease;
}

.input-group .button-minus,
.input-group .button-plus {
  font-weight: bold;
  height: 38px;
  padding: 0;
  width: 38px;
  position: relative;
}

.input-group .quantity-field {
  position: relative;
  color: black;
  height: 38px;
  left: -6px;
  text-align: center;
  width: 62px;
  display: inline-block;
  font-size: 13px;
  margin: 0 0 5px;
  resize: vertical;
}

.button-plus {
  left: -13px;
}

input[type="number"] {
  -moz-appearance: textfield;
  -webkit-appearance: none;
}
</style>

HTML;
    } // end construct

    public function qtyInput($prompt = "", $fldName = "quantity", $fldId = "Qty")
    {
        $req = "";
        if ($this->required) $req = " required";
        $onfocus = $this->onfocus;
        if ($this->qtyBin <> "") $this->qtyMax = $this->qtyBin;
        $qmn = $this->qtyMin;
        $qmx = $this->qtyMax;
        $this->htm = <<<HTML

   <div class="input-group">
    <label class="wmslabel" for="{$fldName}" style="vertical-align: middle;" >{$prompt}</label>
    <input type="button" value="-" class="button-minus w3-red" onclick="plusMinus('Qty',0, {$qmn},{$qmx});" data-field="{$fldName}">
    <input type="number" step="1" min="{$this->qtyMin}" max="{$this->qtyMax}" value="{$this->defQty}" id="Qty" name="{$fldName}" {$onfocus} class="quantity-field"{$req}>
    <input type="button" value="+" class="button-plus w3-green" onclick="plusMinus('Qty',1, {$qmn},{$qmx});" data-field="{$fldName}">
   </div>

HTML;
        return $this->htm;
    } // end QtyInput

} // end class qtyField
