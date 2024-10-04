<?php
//09/30/16 dse add error display
//12/28/16 dse if record is unset(from BeforeShowRecord), don't try print it.
//02/17/17 dse added headers to pipe output
//02/27/17 dse added new Bluejay header
//04/14/17 dse added new popop width and height
//06/08/17 dse Correct value comparision in set_class and add 4th arg to 
//             ignore zero value
//07/25/17 dse Finally figured out how to keep excel from munching text fields.
//             put a tab at the end of the field within the quotes.
//01/24/19 dse correct string compare on dropdown criteria
//05/07/19 dse activate 3rd param on checkbox xtra
//12/06/19 dse Dont export hidden field
//02/12/20 dse Took te tab out, causing some problems, see 7/25/17
//02/12/20 dse put hard coded pipes back in, because fieldDlimiter seems not set
//02/17/21 dse add popup_lines to it can be overridden
//02/17/21 dse add scriptname to allow setting args on reset
//02/17/21 dse add systemname to override cl_bluejays name
//02/17/21 dse add correct undefined var errors
//02/25/21 dse add Bluejay noHeader if noHeader is true
//03/23/21 dse removed ignore zero value in set_class
//03/25/21 dse Add extra_js to Bluejay->js
//05/11/21 dse Add raw type in check_xtra, allows imbeddng of raw htm or javascript
//09/10/21 dse change sizeof to count (it is depriciated)
//02/25/22 dse convert to MYSQL for WMS
//06/16/22 dse Correct Run Report Button to not take 100% width
//08/29/23 dse Don't display menu return if noHeader is on

if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
require_once("{$wmsDir}/include/db_main.php");
require_once("{$wmsDir}/include/cl_Bluejay.php");

class Report
{
var $REFER;
var $excel;
var $noexcel;
var $pipe;
var $showpipe;
var $fieldDelimiter="|";
var $title;
var $SORT;
var $MSG="";
var $sort=array();
var $criteria=array();
var $criteria_section;
var $rptdata=array();
var $fields=array();
var $xtra=array();
var $Totals=array();
var $RunTotals=array();
var $SQL;
var $B1;
var $db;
var $theme;
var $stylesheet;
var $floathdr;
var $rpt_row;
var $rpt_cols;
var $other_hidden;
var $popup;
var $popup_width=750;
var $popup_height=200;
var $popup_lines=8;
var $link;
var $scriptname="";
var $systemname;
var $usedate;
var $numdates=0;
var $usereturn=true;
var $top="";
var $noHeader=false;
var $menu_drp=""; //flag to display menu in cl_bluejay
public $extra_js="";

function init()
{
if (isset($_REQUEST["excel"])) $this->excel=$_REQUEST["excel"];
if (isset($_REQUEST["pipe"])) $this->pipe=$_REQUEST["pipe"];
if (isset($_REQUEST["referer"])) $this->REFER=$_REQUEST["referer"];
if (isset($_REQUEST["SORT"])) $this->SORT=$_REQUEST["SORT"];
if (isset($_REQUEST["floathdr"])) $this->floathdr=$_REQUEST["floathdr"];
else $this->floathdr=1;
$this->scriptname=basename($_SERVER["SCRIPT_FILENAME"]);
$this->systemname="";
$this->rpt_row=0;
$this->rpt_cols=0;
$this->fieldDelimiter="|";
if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$this->top=str_replace("/var/www","",$wmsDir);


$this->db=new WMS_DB;
$this->theme="Multipads";
$this->stylesheet="../Themes/{$this->theme}/Style.css";
$this->theme="RPT ";
$this->popup=0;
$this->link=0;
}

function Query($SQL)
{
$rc=$this->db->query($SQL);
      $numrows=$this->db->num_rows();
      $i=1;
       while ($i <= $numrows)
       {
        $this->db->next_record();
           if ($numrows)
           {
            foreach ($this->db->Record as $key=>$data)
             {
              if (!is_numeric($key))
              {
               $rptdata[$i]["$key"]=$data;
              }
             }
           }
        $i++;
       } // while i < numrows
 if (!isset($rptdata)) $rptdata=array();
 return $rptdata ;
} // end Query

function Run()
{
if (isset($this->B1))
 { // begin Run Report Section
  $this->init_totals($this->Totals);
  $this->rptdata=$this->Query($this->SQL);
if (count($this->rptdata))
{
 $record_count="Records: " .  count($this->rptdata);
$htm=$this->format_thdr($this->rptdata[1],$this->fields);
if (!$this->excel and !$this->pipe) $htm.=$this->chk_xtra("_BEG","");
//Detail
if (!isset($dhtm)) $dhtm="";
$dhtm.=$htm;
if ($this->pipe)
{
 //echo $dhtm;
 $dhtm=$htm;
}
 foreach ($this->rptdata as $rdata)
{
 $this->rpt_row++;
 if (function_exists("BeforeShowRecord")) 
{
 $rdata=BeforeShowRecord($rdata,$this->fields);
}
 if ($this->excel) $dhtm.=$this->format_excel($rdata,$this->fields);
 else if ($this->pipe) $dhtm.=$this->format_pipe($rdata,$this->fields);
 else $dhtm.=$this->format_dtl($rdata,$this->fields);
 $this->AddTotals($rdata,$this->Totals);
} // end foreach rptdata
 if (!$this->excel and !$this->pipe)
 {
  $tothtm=$this->format_Totals($this->Totals,$this->fields);
  $dhtm.=$tothtm;
  $ext=$this->chk_xtra("_END","");
  $dhtm.=<<<HTML
  <tr>
  <td colspan="{$this->rpt_cols}" align="center">
  {$ext}
 </td>
 </tr>
</table>
HTML;
  $ext=$this->chk_xtra("_END1","");
  $dhtm.=$ext;
 } //!excel or pipe
} // end count rptdata
else 
{
$htm_critiria=$this->bld_criteria();
$dhtm="{$htm_critiria}<h2>There are no Records to Display</h2>";
if (isset($this->rptdata[0])) $dhtm.=$this->format_thdr($this->rptdata[0],$this->fields);
}
if (!isset($record_count)) $record_count="";
$htm=$this->format_hdr($this->usedate,$this->B1);
$htm=str_replace("_BODY_",$dhtm,$htm);
$htm=str_replace("_RECORD_COUNT_",$record_count,$htm);

if ($this->excel or $this->pipe) echo $dhtm;
else echo $htm;

//exit;

 } // end Run Report Section
else
 { // begin report selection criteria
//$htm="help!, need to define User Prompts, see: slsm/promos.php";
//$htm_critiria=$this->format_hdr($usedate,$this->B1);
$htm_critiria=$this->bld_criteria();
$js="";
if ($this->numdates > 0) $js=$this->datepick_js();

$pg=new Bluejay;
$pg->title=$this->title;
if (isset($this->extra_js) and $this->extra_js <> "")
{
 $js.=$this->extra_js;
}
if ($this->systemname <> "") $pg->SystemName=$this->systemname;
$pg->js=$js;
if ($this->menu_drp <> "") $pg->menu_drp=$this->menu_drp;
$pg->noBootStrap=true; // turn off bootstrap for reports
if ($this->noHeader) $pg->noHeader=true;
$pg->Display();
//echo $this->systemname;

//$htm=<<<HTML
//<html>
//<head>
//<meta http-equiv="Content-Language" content="en-us">
//<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
//<link href="{$css}" type="text/css" rel="stylesheet">
//<title>{$this->title}</title>
//{$js}
//</head>
//
//<body>

//HTML;

//ob_start();
//include("../inc_hdr.php");
//$htm.=ob_get_contents();
//ob_end_clean();
//echo $htm;
$htm=<<<HTML
<table width="100%">
 <tr>
  <td colspan="1">&nbsp;</td>
  <td colspan="5" class="{$this->theme}FormHeaderFont" align="left">{$this->title}</td>
  <td class="{$this->theme}FieldCaption" align="left" colspan="1">&nbsp;</td>
 </tr>
 {$htm_critiria}
</table>

HTML;

//echo $htm_critiria;
echo $htm;
//echo "format_header";
//exit;
//echo $this->dhtm;
//exit;
 } // end report selection criteria
} // end Run

function chk_xtra($key,$value="",$dtl=array())
{
 $r="";
 if (!isset($this->xtra[$key])) return false ;
 if (!count($this->xtra[$key])) return false ;
 foreach ($this->xtra[$key] as $val)
 {
 if (trim($val) <> "") $w=explode(",",$val);
 if (!count($w)) return false ;

  switch ($w[0])
  {
   case "H":  // hidden field
   $r.=<<<HTML

<input type="hidden" name="{$key}[{$this->rpt_row}]" value="{$value}">

HTML;
   break;
   case "popup":  // checkbox field
   $href=$w[1];
   $this->popup=1;
   $j=strpos($href,"{");
   if ($j > 0)
   { // search for variables to replace
    foreach ($dtl as $fld=>$fldval)
    {
     $k="{" . $fld . "}";
     if (strpos($href,$k))
     {
      $href=str_replace($k,$fldval,$href);
     }
    } // foreach dtl
   } // search for variables to replace
//{$value}&nbsp;<img src="detail.gif" onclick="popup('{$href}',8);"></img>
   $r.=<<<HTML

<a href="javascript:popup('{$href}',{$this->popup_lines});">{$value}</a>

HTML;
   break;
  case "link":  // checkbox field
   $href=$w[1];
   $this->link=1;
   $j=strpos($href,"{");
   if ($j > 0)
   { // search for variables to replace
    foreach ($dtl as $fld=>$fldval)
    {
     $k="{" . $fld . "}";
     if (strpos($href,$k))
     {
      $href=str_replace($k,$fldval,$href);
     }
    } // foreach dtl
   } // search for variables to replace
//{$value}&nbsp;<img src="detail.gif" onclick="popup('{$href}',8);"></img>
   $r.=<<<HTML

<a href="{$href}">{$value}</a>

HTML;
   break;

   case "checkbox":  // checkbox field
   if (isset($w[2])) $chk=$w[2]; else $chk="";
   $r.=<<<HTML

<input type="checkbox" name="{$w[1]}[{$this->rpt_row}]" value="{$value}" {$chk}>

HTML;

   break;
   case "submit":  // submit field
   $r.=<<<HTML

<input type="submit" name="{$w[2]}" value="{$w[1]}">

HTML;
   break;

   case "form":  // form field
   $r.=<<<HTML

<form name="{$w[1]}" method="post" action="{$w[2]}">
<input type="hidden" name="B1" value="{$this->B1}">
<input type="hidden" name="excel" value="{$this->excel}">
<input type="hidden" name="pipe" value="{$this->pipe}">
<input type="hidden" name="referer" value="{$_SERVER["PHP_SELF"]}">
<input type="hidden" name="floathdr" value="{$this->floathdr}">
<input type="hidden" name="nh" value="{$this->noHeader}">
{$this->other_hidden}

HTML;
   break;
   case "eform":  // form field
   $r.=<<<HTML

</form>

HTML;
   break;
   case "href":  // form field
//echo "<pre>";
//print_r($this->xtra);
//print_r($w);
//exit;
   $r.=<<<HTML
<p><a href="{$w[1]}"><strong>{$w[2]}</strong></a></p>
</form>

HTML;
   break;
   case "raw":  // raw html or javascript
   $r.=<<<HTML
 {$w[1]}

HTML;
   break;

  } // end switch w[0]
 } // end foreach  xtra
return $r ;

} // end chk_xtra

function chk_field($key,$fields,$value="")
{
 if (isset($fields[$key]))
 {
  $w=explode(",",$fields[$key]);
  if ($w[0] <> "H") return "OK" ;
  $f=<<<HTML
<input type="hidden" name="{$key}[{$this->rpt_row}]" value="{$value}">

HTML;
  return $f ;
 }
 
} // end chk_field
function set_align($key,$fields,$value="",$func=0)
{
 $val=$value;
 if (isset($fields[$key]))
 {
  $w=explode(",",$fields[$key]);
  $al=" style=\"text-align:left\"";
  if ($w[0] == "R") $al=" style=\"text-align:right;padding-right:10px\"";
  if ($w[0] == "C") $al=" style=\"text-align:center\"";
  if (!isset($w[1])) $w[1]="";
  if (!isset($w[2])) $w[2]="";
  if (trim($w[2]) <> "") $al.=" title=\"{$w[2]}\"";
  if (trim($w[1]) <> "") $val=sprintf($w[1],$value);
  if ($func == 1) return $val  ;
  else return $al ;
 }
} // end set_align
function set_class($key,$fields,$value="")
{
 $val=$value;
 if (isset($fields[$key]))
 {
  $w=explode(",",$fields[$key]);
  $cls="{$this->theme}DataTD";
  $cls="{$this->theme}";
  if (!isset($w[3])) $w[3]="";
  if (!isset($w[4])) $w[4]=0;
//03/22/21 dse
//  if (trim($w[3]) <> "" and $val <> 0) $cls="{$this->theme}{$w[3]}";
//  if (trim($w[3]) <> "" and $w[4] > 0) $cls="{$this->theme}{$w[3]}";
  if (trim($w[3]) <> "" ) $cls="{$this->theme}{$w[3]}";
  return $cls ;
 }
} // end set_align


function format_hdr($usedate="N",$B1="Run Report")
{
$css=$this->stylesheet;
if ($this->excel)
{
//$fname=str_replace(" ","_",$this->title) . ".csv";
$fname=str_replace(".php",".csv",basename($_SERVER["PHP_SELF"]));

header("Content-Type: application/vnd.ms-excel");
header("Content-disposition: inline; filename=\"{$fname}\"");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
return;
}
else if ($this->pipe)
 {
//$fname=str_replace(" ","_",$this->title) . ".txt";
$fname=str_replace(".php",".csv",basename($_SERVER["PHP_SELF"]));
header("Content-Type: application/text");
header('Content-Disposition: attachment; filename="' . $fname . '"');
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
return;
 } // end pipe

else
{
 $js="";
 if ($this->usedate=="Y")
{
$js=<<<HTML
<script language="JavaScript" src="ClientI18N.php?file=Functions.js&locale={res:CCS_LocaleID}" type="text/javascript" charset="utf-8"></script>
<script language="JavaScript" src="ClientI18N.php?file=DatePicker.js&locale={res:CCS_LocaleID}" type="text/javascript" charset="utf-8"></script>
<script language="JavaScript" type="text/javascript">
//Date Picker Object Definitions @1-4FB7D743
var form1_DatePicker1 = new Object();
form1_DatePicker1.format           = "mm/dd/yyyy";
form1_DatePicker1.style            = "{$css}";
form1_DatePicker1.relativePathPart = "";
var form1_DatePicker2 = new Object();
form1_DatePicker2.format           = "mm/dd/yyyy";
form1_DatePicker2.style            = "{$css}";
form1_DatePicker2.relativePathPart = "";
//End Date Picker Object Definitions
</script>

HTML;
}
 if ($this->floathdr > 0) 
 {
$js.=<<<HTML
 <script type="text/javascript" src="/jq/jquery-1.9.1.min.js"></script>
 
    <script type="text/javascript">
        function UpdateTableHeaders() {
            $("div.divTableWithFloatingHeader").each(function() {
                var originalHeaderRow = $(".tableFloatingHeaderOriginal", this);
                var floatingHeaderRow = $(".tableFloatingHeader", this);
                var offset = $(this).offset();
                var scrollTop = $(window).scrollTop();
                if ((scrollTop > offset.top) && (scrollTop < offset.top + $(this).height())) {
                    floatingHeaderRow.css("visibility", "visible");
                    floatingHeaderRow.css("top", Math.min(scrollTop - offset.top, $(this).height() - floatingHeaderRow.height()) + "px");
 
                    // Copy cell widths from original header
                    $("th", floatingHeaderRow).each(function(index) {
                        var cellWidth = $("th", originalHeaderRow).eq(index).css('width');
                        $(this).css('width', cellWidth);
                    });
 
                    // Copy row width from whole table
                    floatingHeaderRow.css("width", $(this).css("width"));
                }
                else {
                    floatingHeaderRow.css("visibility", "hidden");
                    floatingHeaderRow.css("top", "0px");
                }
            });
        }
 
        $(document).ready(function() {
            $("table.tableWithFloatingHeader").each(function() {
                $(this).wrap("<div class=\"divTableWithFloatingHeader\" style=\"position:relative\"></div>");
 
                var originalHeaderRow = $("tr:first", this)
                originalHeaderRow.before(originalHeaderRow.clone());
                var clonedHeaderRow = $("tr:first", this)
 
                clonedHeaderRow.addClass("tableFloatingHeader");
                clonedHeaderRow.css("position", "absolute");
                clonedHeaderRow.css("top", "0px");
                clonedHeaderRow.css("left", $(this).css("margin-left"));
                clonedHeaderRow.css("visibility", "hidden");
 
                originalHeaderRow.addClass("tableFloatingHeaderOriginal");
            });
            UpdateTableHeaders();
            $(window).scroll(UpdateTableHeaders);
            $(window).resize(UpdateTableHeaders);
        });
    </script>

HTML;
 } // end float hdr

//if (count($this->sort) > 0)
if (isset($this->criteria["types"]) and count($this->criteria["types"]) > 0)
{
$js.=<<<HTML
<SCRIPT Language="JavaScript">
 function do_submit() 
 {
  if (document.form1.fltcb.checked == true)
  { document.form1.floathdr.value=1; }
  else { document.form1.floathdr.value=0; }
  document.form1.submit();
 } // end do_submit

     function do_submit1() {
       document.rform1.submit();
}

</SCRIPT>

HTML;
 }
if ($this->popup > 0)
$js.=<<<HTML
<script>
function popup(url,nlns) {
        hgt=210 + (nlns * 25);
        hgt={$this->popup_height};
        window.open(url,"altpage", "toolbar=no,left=400,top=200,status=yes,resizable=yes,scrollbars=yes,width={$this->popup_width},height=" + hgt );
     }

</script>

HTML;

if (isset($this->extra_js) and $this->extra_js <> "")
{
 $js.=$this->extra_js;
}

$pg=new Bluejay;
$pg->title=$this->title;
$pg->js=$js;
if ($this->noHeader) $pg->noHeader=true;
$pg->noBootStrap=true; // turn off bootstrap for reports
$pg->Display();
//$htm=<<<HTML
//<html>
//<head>
//<meta http-equiv="Content-Language" content="en-us">
//<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	//<link href="{$css}" type="text/css" rel="stylesheet">
//<title>{$this->title}</title>
//{$js}
//</head>

//<body>

//HTML;

//ob_start();
//include("../inc_hdr.php");
//$htm.=ob_get_contents();
//ob_end_clean();

$self=$_SERVER["PHP_SELF"];
$args="";
foreach ($_REQUEST as $fld=>$val)
{
 if ($fld <> "excel" and $fld <> "pipe" and $fld <> "B1" and $fld <> "PHPSESSID" and $fld <> "referer" and $fld <> "SORT" and $fld <> "floathdr" and $fld <> "fltcb")
 { 
  $args.="&{$fld}={$val}";
 }
}
if ($B1 == "Run Report" and $this->rpt_row > 0)
{
 $lnk="";
 $hpipe="";
 if ($this->showpipe) 
  {
   $hpipe=<<<HTML
<a target="_blank" href="{$self}?pipe=1&B1=Run{$args}" title="Export to Textfile"><img src="{$this->top}/images/export_to_text.png" width="24px" height="24px" border="0"></a>
HTML;
  }
 if (!isset($this->noexcel) )
  {
 $lnk=<<<HTML
<a target="_blank" href="{$self}?excel=1&B1=Run{$args}" title="Export to CSV file"><img src="{$this->top}/images/export_to_csv.png" width="24px" height="24px" border="0"></a>
{$hpipe}

HTML;
  }
}
else $lnk="&nbsp;";

if ($this->noHeader) $ret="";
else
$ret=<<<HTML
<a href="{$this->REFER}" title="Go Back"><img src="{$this->top}/images/back_icon.png" width="24px" height="24px" border="0"></a>
HTML;
if ($this->usereturn==false)
$ret=<<<HTML
<a href="#" onclick="self.close();">Close</a>
HTML;

if (!isset($htm)) $htm="";
$htm.=<<<HTML
 <table  width="80%" border="0">
  <tr>
   <td align="left" width="50%" class="{$this->theme}FormHeaderFont">
    {$ret}
   </td>
   <td align="right" width="30%" class="{$this->theme}FormHeaderFont">
    {$lnk}
   </td>
   <td align="right" width="20%">
    _RECORD_COUNT_
   </td>
  </tr>
 </table>
_BODY_
 <table  width="80%" border="0">
  <tr>
   <td align="left" width="50%" class="{$this->theme}FormHeaderFont">
    {$ret}
   </td>
   <td align="right" width="30%" class="{$this->theme}FormHeaderFont">
    {$lnk}
   </td>
   <td align="right" width="20%">
    _RECORD_COUNT_
   </td>
  </tr>
 </table>
</body>
</html>
HTML;

return $htm ;
} // end else not excel
}
function format_dtl($dtl,$fields)
{
 if (count($dtl) < 1) return "" ;
$cls="{$this->theme}DataTD";
$cls="{$this->theme}";
$dhtm="<tr class=\"RPT\">\n";
 foreach ($dtl as $key=>$value)
 {
  $t=$this->chk_field($key,$fields,$value);
  if ($t == "OK")
  {
   $al=$this->set_align($key,$fields);
   $val=$this->set_align($key,$fields,$value,1);
   $cls=$this->set_class($key,$fields,$value);
   $do_xtra=1;
   if (isset($this->xtra[$key][0]))
   {
    if(substr($this->xtra[$key][0],0,5) == "popup")
    {
     $do_xtra=0;
     $val=$this->chk_xtra($key,$value,$dtl);
    } // end popup
    if(substr($this->xtra[$key][0],0,4) == "link")
    {
     $do_xtra=0;
     $val=$this->chk_xtra($key,$value,$dtl);
    } // end popup
   } // $this->xtra[$key][0] is set
 
   $dhtm.=<<<HTML
<td class="{$cls}" nowrap {$al}>{$val}
HTML;
   $ext="";
   if ($do_xtra > 0) $ext=$this->chk_xtra($key,$value);
   $dhtm.=$ext . "</td>\n";
  } // end t=true
 else $dhtm.=$t;
 } // end foreach dtl
 $dhtm.="\n</tr>\n";
return $dhtm ;
}
function set_exfld($key,$fields,$value="")
{ // format excel field
 if (isset($fields[$key]))
 {
  $w=explode(",",$fields[$key]);
  $qs='"';
  //$qe="\t\""; //Jake says the tab is causing him problems
  // it actually should be {tab}"val"
  $qe='"';
  if ($w[0] == "R")
  {
   $qs="";
   $qe="";
  }
  $v=$value;
  if (trim($w[1]) <> "") $v=sprintf($w[1],$value);
  $val="{$qs}{$v}{$qe}";
  return $val  ;
 }
} // end set_exfld

function format_excel($dtl,$fields)
{
 if (count($dtl) < 1) return "" ;
$detail="";
 foreach ($dtl as $key=>$value)
 {
  if (substr($fields[$key],0,1) <> "H") 
  {
  $val=$this->set_exfld($key,$fields,$value);
$detail.=<<<EXCEL
{$val},
EXCEL;
  } // end not hidden
 } // end foreach dtl
$detail.=chr(13);
return $detail ;
}
function format_pipe($dtl,$fields)
{
 if (count($dtl) < 1) return "" ;
$detail="";
$j=count($dtl);
$i=1;
 foreach ($dtl as $key=>$value)
 {
  $p=$this->fieldDelimiter;
  if (substr($fields[$key],0,1) <> "H") 
  {
  //if ($i == $j) $p="";
  $i++;
  $val=$value;
$detail.=<<<TEXT
{$val}|
TEXT;
 } //end field is not hidden
 } // end foreach dtl
if (substr($detail,-1) == $this->fieldDelimiter) $detail=substr($detail, 0, -1);
$detail.=chr(13);
$detail.=chr(10);
return $detail ;
}

function format_thdr($hdr,$fields)
{
 if ($this->excel or $this->pipe) 
 {
  $htm="";
  foreach ($hdr as $key=>$d)
  {
   if (substr($fields[$key],0,1) <> "H") 
   { // not hidden field
    if ($this->pipe)
    {
     $htm.=<<<TEXT
{$key}|
TEXT;
    }
   else
    {
     $htm.=<<<EXCEL
"{$key}",
EXCEL;
    }
   } // not hidden field
  } // end foreach
  if (substr($htm,-1) == $this->fieldDelimiter) $htm=substr($htm, 0, -1);
  if (substr($htm,-1) == ',') $htm=substr($htm, 0, -1);
  $htm.=chr(13);
  if ($this->pipe) $htm.=chr(10);
 } // end if excel
else //if (!this->pipe)
 {
  $hhtm="<tr>\n";
  $cols=0;
  if (count($hdr)) foreach ($hdr as $key=>$d)
  {
   $t=$this->chk_field($key,$fields);
   if ($t == "OK")
   {
    $cols++;
    $al=$this->set_align($key,$fields);
    $hhtm.=<<<HTML
<th class="{$this->theme}ColumnTD" nowrap {$al}>{$key}</th>

HTML;
   } // end t = OK
  }
  $hhtm.=<<<HTML
 </tr>

HTML;
  $cls="class=\"{$this->theme}FormTABLE\"";
  $cls="class=\"RPT\"";
  if ($this->floathdr > 0) // Add floathdr table around col headings
  {
   $thtm=<<<HTML
  </table>
    <table width="100%" class="RPT tableWithFloatingHeader">
       {$hhtm}

HTML;
   $hhtm=$thtm;
   //$cls="class=\"tableWithFloatingHeader\"";
  }

 $inform=0;
 $lsortby=<<<HTML
    <form name="form1" action="{$_SERVER["PHP_SELF"]}">
<input type="hidden" name="B1" value="{$this->B1}">
<input type="hidden" name="excel" value="{$this->excel}">
<input type="hidden" name="referer" value="{$this->REFER}">
<input type="hidden" name="floathdr" value="{$this->floathdr}">
<input type="hidden" name="nh" value="{$this->noHeader}">
{$this->other_hidden}
HTML;
 $rsortby="&nbsp;";

 $this->criteria_section="&nbsp;";
 if (count($this->sort) > 1)
 {
  $s[0]="";
  $s[1]="";
  if (isset($this->SORT)) $s[$this->SORT]=" selected";
  $cb="";
  $inform=1;
  if ($this->floathdr > 0) $cb=" checked";

  $lsortby.=<<<HTML
    Sort by: <select size="1" name="SORT" onchange="document.form1.submit();">

HTML;
  foreach ($this->sort as $skey=>$sd)
   {
    $lsortby.=<<<HTML
        <option {$s[$skey]} value="{$skey}">{$sd["Desc"]}</option>

HTML;
   }
  $lsortby.=<<<HTML
      </select>
HTML;
  $rsortby=<<<HTML
<span title="If Checked, keeps column headings on the page while scrolling">
<img style="vertical-align: middle" src="{$this->top}/images/pushpin_icon.png" width="24px" height="24px" border="0">
   <input type="checkbox" name="fltcb" value="{$this->floathdr}" {$cb} onchange="document.form1.submit();">
</span>

HTML;

 } // end sort > 1

if (isset($this->criteria["types"]) and count($this->criteria["types"]) > 0)
 {
  $inform=2;
  $this->criteria_section=$this->bld_criteria();
 } // end criteria
 if ($inform == 1) $rsortby.=" </form>\n";

 if ($inform == 0) $lsortby.=" </form>\n";
 //Table header
 $ext=$this->chk_xtra("_REPORT","");
 $tc=$cols -6;
 $this->rpt_cols=$cols;
 $crit="";
 if ($this->criteria_section <> "&nbsp;")
  {
   $crit=<<<HTML
<table>
<tr>
  <td class="{$this->theme}FieldCaption" align="left" colspan="7">{$this->criteria_section}</td>
 </tr>

HTML;
  }
 else
  {
   $crit=<<<HTML
<input type="hidden" name="SORT" value="{$this->SORT}">

HTML;
  }
 $htm=<<<HTML
<table width="100%" {$cls}>
 <tr>
  <td class="{$this->theme}FieldCaption" align="left" colspan="1">{$lsortby}</td>
  <td colspan="5" class="{$this->theme}FormHeaderFont" align="left" colspan="{$tc}">{$this->title}</td>
  <td align="right" colspan="1">{$rsortby}</td>
 </tr>
</table>
 {$crit}
{$ext}
{$hhtm}

HTML;
  } //criteria section not nbsp
 return $htm ;
} //end format_thdr

function bld_criteria()
{
 $msg="";
 if ($this->MSG <> "")
 {
  $msg="<p style=\"color:red;font-weight:bold;\">{$this->MSG}</p>";
 }
 if (!isset($this->B1)) $this->B1="";
 $this->criteria_section=<<<HTML
 {$msg}
 <form name="form1" action="{$_SERVER["PHP_SELF"]}">
<input type="hidden" name="B1" value="{$this->B1}">
<input type="hidden" name="excel" value="{$this->excel}">
<input type="hidden" name="referer" value="{$this->REFER}">
<input type="hidden" name="floathdr" value="{$this->floathdr}">
<input type="hidden" name="nh" value="{$this->noHeader}">
{$this->other_hidden}

HTML;
if (isset($this->criteria["types"]) and count($this->criteria["types"]) > 0)
{
 $inform=2;
 foreach ($this->criteria["types"] as $fld_name=>$ftype)
 {
  switch ($ftype)
  {
   case "select":
  unset($s);
  $s=array();
 $this->criteria_section.=<<<HTML
    {$this->criteria[$fld_name]["prompt"]}: <select size="1" name="{$fld_name}" onchange="document.form1.submit();">
HTML;
    foreach ($this->criteria[$fld_name] as $val=>$sdesc)
    {
     //if ($val <> "prompt" and $val <> "value")
     if (strcmp($val,"prompt") <> 0 and strcmp($val,"value") <> 0)
     {
 $this->criteria_section.=<<<HTML
        <option {$sdesc["selected"]} value="{$val}">{$sdesc["Desc"]}</option>

HTML;
     } // end not prompt
    } // end foreach criteria fld_name
$this->criteria_section.=<<<HTML
      </select>

HTML;
   break;
   case "date":
 $this->usedate="Y";
 $this->numdates++;
 $dp=$this->numdates;
 $this->criteria_section.=<<<HTML
 <table>
  <tr>
   <td>
    {$this->criteria[$fld_name]["prompt"]}: <input type="text" name="{$fld_name}" value="{$this->criteria[$fld_name]["value"]}" onchange="document.form1.submit();">
   </td>
   <td>
  <a class="DataLink" href="javascript:showDatePicker('form1_DatePicker{$dp}','form1','{$fld_name}');"><img src="../Themes/DatePicker/DatePicker1.gif" border="0"></a>
   </td>
  </tr>
 </table>
HTML;
   break;
   case "text":
 $this->criteria_section.=<<<HTML
    {$this->criteria[$fld_name]["prompt"]}: <input type="text" name="{$fld_name}" value="{$this->criteria[$fld_name]["value"]}" onchange="document.form1.submit();">
HTML;
   break;
   case "utext":
 $this->criteria_section.=<<<HTML
    {$this->criteria[$fld_name]["prompt"]}: <input type="text" style="text-transform:uppercase" name="{$fld_name}" value="{$this->criteria[$fld_name]["value"]}" onchange="document.form1.submit();">
HTML;
   break;
  } //end switch ftype
 } // end foreach criteria types
} // end criteria

if ($inform == 1) $rsortby.=" </form>\n";
if ($inform == 2)
{
//$sn=basename($_SERVER["SCRIPT_FILENAME"]);
$sn=$this->scriptname;
if ($this->noHeader) $sn.="?nh={$this->noHeader}";
 $this->criteria_section.=<<<HTML

<button class="binbutton-tiny" onclick="document.form1.submit();" name="B1" value="Run Report">Run Report</button>
<a href="{$sn}"><input class="binbutton-tiny" type="button" name="R1" value="Reset"></a>
</form>
HTML;
//"binbutton
}
 return $this->criteria_section ;
} // end bld_criteria
function init_totals($tot)
{
 foreach ($tot as $field=>$val)
 {
   $k=explode(",",$val);
   if ($k[0] <> "S" and $k[0] <> "D")
   {
    //$init="0.{$k[1]}";
    $init="0.00";
    $this->RunTotals[$field]=$init;
   } // end not SubTotal or Total Prompt Display
   if ($k[0]=="S") $this->RunTotals[$field]="";
   if ($k[0]=="D") $this->RunTotals[$field]="Totals";
 } // end foreach tot
} // end init_totals

function AddTotals($rdata,$tot)
{
  foreach ($tot as $field=>$val)
 {
   $k=explode(",",$val);
   if ($k[0] <> "S" and $k[0] <> "D")
   {
    $init="0.{$k[1]}";
    $this->RunTotals[$field]=$this->RunTotals[$field] + $rdata[$field];
   } // end not SubTotal or Total Prompt Display
//    else { $this->RunTotals[$field]="{$rdata[$field]} Totals"; }
 } // end foreach tot
} // end AddTotals
function format_Totals($tot,$fields)
{
//echo "<pre>";
//echo "tot=";
//print_r($tot);
//echo "fields=";
//print_r($fields);
//echo "RunTot=";
//print_r($this->RunTotals);
//exit;

$cls="{$this->theme}AltDataTD";
$dhtm="<tr>\n";
 foreach ($fields as $key=>$v)
 {
   $al="align=\"left\"";
   if (isset($tot[$key]))
   { 
   $k=explode(",",$tot[$key]);
   if ($k[0] == "R") $al="align=\"right\"";
   if ($k[0] == "L") $al="align=\"left\"";
   if ($k[0] == "C") $al="align=\"center\"";
   } // tot[key] is set
   else $k[0]="";
  if (isset($this->RunTotals[$key]))
  {
  $value=$this->RunTotals[$key];
  if ($k[0] <> "D") $val=number_format($value,$k[1]); else $val=$value;
  } // isset totals[key]
 else $val="&nbsp;";
  if ($k[0]=="S") $val="&nbsp;";

if (substr($v,0,1) <> "H") $dhtm.=<<<HTML
<td class="{$cls}" nowrap {$al}><strong>{$val}</strong></td>

HTML;
 } // end foreach dtl
 $dhtm.="\n</tr>\n";
return $dhtm ;
}
function datepick_js()
{
 $numdates=$this->numdates;
 $css=$this->stylesheet;
 $dp="";
 for ($i=1;$i<=$numdates;$i++)
 {
  $dp.=<<<HTML
   var form1_DatePicker{$i} = new Object();
   form1_DatePicker{$i}.format           = "mm/dd/yyyy";
   form1_DatePicker{$i}.style            = "{$css}";
   form1_DatePicker{$i}.relativePathPart = "";

HTML;
 }
$js=<<<HTML
<script language="JavaScript" src="ClientI18N.php?file=Functions.js&locale={res:CCS_LocaleID}" type="text/javascript" charset="utf-8"></script>
<script language="JavaScript" src="ClientI18N.php?file=DatePicker.js&locale={res:CCS_LocaleID}" type="text/javascript" charset="utf-8"></script>
<script language="JavaScript" type="text/javascript">
{$dp}
</script>

HTML;

 return $js ;
} // end datepick_js
} // end class Report
?>
