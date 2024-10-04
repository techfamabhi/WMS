<?php

// pictic.php -- Print Picking Ticket
// 03/08/23 dse initial

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
//error_reporting(0);

if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir) . "/";
require("{$wmsDir}/config.php");

if (!isset($comp)) $comp=1;
require_once("{$wmsDir}/assets/pdf/fpdf_js.php");
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/date_functions.php");
require_once("{$wmsInclude}/cl_ORDERS.php");
$db=new WMS_DB;
/*
TODO

Gut this, mv all reads to servers/cl_ORDERS
format pick tick with barcode on top

*/


ignore_user_abort(true);

//header('Content-type: application/pdf');
$print_type="";
$hdr_prted=0;

//class PDF extends PDF_Javascript
class PDF extends PDF_Javascript
{
//Current column
var $col=0;
//Ordinate of column start
var $y0;
//outputed line counter
var $oline=0;
var $font="Courier";

function Header()
{
    //Page header
    global $title;
    global $shipfrom;
    global $pagenum;
    global $order;
    global $ord;
    global $nototpages;
    global $bill_to;
    global $ship_to;
    global $nobgd;
    global $svia;
    global $wmsDir;
    global $wmsSysLogo;
    global $db;
    global $lns_prted;
    global $hdr_prted;
    global $print_type;
    $this->SetXY(0,0);
    $this->SetXY(0,1);
    $this->SetLineWidth(.1);
    $print_type="";

    if (1 == 1)
{
$this->SetFont($this->font,'B',10.1);
$this->SetXY(0,0);
//$logo=str_replace(".png",".jpg","{$wmsDir}/../{$wmsSysLogo}");
//$this->Image($logo,10,4.6,44,15,"JPG");
$this->ln(4);

    $w1="Account # " . sprintf("%6s",$order["customer_id"]);
    //$this->D($w1,15,0);
   $w2=$order["order_type"];
  switch ($w2)
  {
   case "O":
   $p=" PICKING TICKET";
   $print_type="I";

  break;
   case "D":
    $p="DEBIT MEMO PICK";
    $print_type="I";
    break;
   case "T":
    $p="  TRANSFER PICK";
    $print_type="";
    break;

  }

$sub1=0; 
// Company Name and Addr
$this->SetFont($this->font,'',10.1);
    $w1=$shipfrom["name"];
    $w=$this->GetStringWidth($w1)+6;
$this->SetFont($this->font,'B',10.1);
    $this->D($w1,(100-$w)/2,0);
// Print Doc type and host id
   $w1="{$p} {$order["host_order_num"]}";
    $this->SetFont($this->font,'B',12.1);
    $this->D($w1,130,4);
    $this->SetFont($this->font,'',12.1);
$this->SetFont($this->font,'',10.1);
    $w1=$shipfrom["addr"];
    $w=$this->GetStringWidth($w1)+6;
    $this->D($w1,(100-$w)/2,0);

    $w1=$pagenum . " of ";
    $w1=sprintf("%4d",$pagenum) . "  of  {nb}";
    if ( $nototpages > 0 ) $w1=$pagenum;
    $this->SetFont($this->font,'',10.1);
    $this->D("Page",150,0);
    $this->D($w1,160,4);

    $w1=$shipfrom["city"];
    $w=$this->GetStringWidth($w1)+6;
    $this->D($w1,(100-$w)/2,4);

    $w1=$shipfrom["phone"];
    $w=$this->GetStringWidth($w1)+6;
    $this->D($w1,(100-$w)/2,0);

// print barcode of Order#
    $w=$this->GetY();
    $barcode="{$ord->ordNumber}";
    $this->Code39(140, $w, $barcode,true,false,0.4,10,false);
// print barcode of Order#
   $this->ln(9);


   //$w1="{$p} {$order["host_order_num"]}";
    //$this->SetFont($this->font,'B',12.1);
    //$this->D($w1,130,0);
    //$this->SetFont($this->font,'',12.1);

    $w1=$bill_to["name"];
    $saveY=$this->GetY();
    $this->SetFont($this->font,'',8.1);
    $this->D("S",14,0);
    $this->D("S",96,2);
    $this->D("O",14,0);
    $this->D("H",96,2);
    $this->D("L",14,0);
    $this->D("I",96,0);
    $this->D("",170,2);
    $this->D("D",14,0);
    $this->D("P",96,2);
    $this->D(" ",14,0);
    $this->D(" ",96,2);
    $this->D("T",14,0);
    $this->D("T",96,2);
    $this->D("O",14,0);
    $this->D("O",96,0);
    $this->D("",172,2);
    $this->SetY($saveY);
    $this->SetFont($this->font,'',10.1);
    $this->D($w1,18,0);

    $w1=$ship_to["name"];
    $this->D($w1,100,4);

    $w1=$bill_to["addr1"];
    $this->D($w1,18,0);

    $w1=$ship_to["addr1"];
    $this->D($w1,100,4);

    $w1=$bill_to["addr2"];
    $this->D($w1,18,0);
    $w1=$ship_to["addr2"];
    $this->D($w1,100,4);

    $w1=$bill_to["city"] . ", " . $bill_to["state"] . " " . $bill_to["zip"];
    $this->D($w1,18,0);
    $w1=$ship_to["city"] . ", " . $ship_to["state"] . " " . $ship_to["zip"];
    $this->D($w1,100,4);
    $w1=$bill_to["phone"];
    $this->D($w1,18,0);
    $w1=$ship_to["phone"];
    $this->D($w1,100,4);

    $this->SetFont($this->font,'',8.1);
    
    $w1=$order["shipping_instr"];
    $this->D($w1,10,0);
    $w1=$order["shipping_instr"];
    $this->D($w1,110,8);
//Add Ship Inst
    $w1="Account #";
    $this->DM($w1,4,0,25);
    $w1="Ref";
    $this->DM($w1,29,0,18);
    $w1="Order Date";
    $this->DM($w1,47,0,30);
    $w1="PO Number";
    $this->DM($w1,75,0,42);
    $w1="Pick Date/Time";
    $this->DM($w1,117,0,50);
    $w1="Ship Via";
    $this->DM($w1,167,0,15);
    $this->ln(3);

    $this->SetFont($this->font,'',10.1);
    $w1=$order["customer_id"];
    $this->DB($w1,4,0,25,0,"LR");
    $w1=$order["host_order_num"];
    $this->DB($w1,29,0,18,0,"LR");
    $w1=eur_to_usa($order["enter_date"],false);
    $this->DB($w1,47,0,30,0,"LR");
    $w1=$order["cust_po_num"];
    $this->DB($w1,75,0,42,0,"LR");
    //$this->D($w1,84,0);
    $w1=date("m/d/y h:m");
    $this->DB($w1,117,0,50,0,"LR");
    $w1=$order["ship_via"];
    $this->DB($w1,167,6,15,0,"LR");
    //begin colum headings
    $this->SetFont($this->font,'',8.1);
    $w1="Line#";
    $this->DH($w1,4,0,11);
    $w1="Whse";
    $this->DH($w1,15,0,30);
    $w1="UOM";
    $this->DH($w1,45,0,8);
    $w1="Ord Qty";
    $this->DH($w1,53,0,14);
    $w1="Pick Qty";
    $this->DH($w1,67,0,14);
    $w1="Avail";
    $this->DH($w1,81,0,14);
    $w1="B/O ";
    $this->DH($w1,95,0,14);
    $w1="P/L Part Number";
    $this->DH($w1,105,0,40);
    //$w1="Part Number";
    //$this->DH($w1,115,0,34);
    //$this->DH($w1,79,0,35);
    $w1="Description";
    $this->DH($w1,144,0,77);
    $w1="Mdse";
    $this->DH($w1,216,0,10);
    $this->ln(3);
    $this->SetFont($this->font,'',10.1);


   //Draw the rest of the form
   $y0=$this->GetY();
   $saveY=$y0 -1;
   $this->SetLineWidth(.2);
   $lines=array(0=>24, 1=>35, 2=>79, 3=>114, 4=>156, 5=>164, 6=>178, 7=>192, 8=>206, 9=>228, 10=>250, 11=>276);
/*
   foreach($lines as $x0)
    {
    	$this->Dline($x0,$y0,$x0,$y0 + 97);
    } // end foreach lines
   $newY=$y0 + 97;
   $this->Dline(4,$newY,286,$newY);
   $newY=$newY + 7;
 
   //lower boxes 
   $this->SetY($newY);
   $this->SetFont($this->font,'',8.1);
   $w1="Miscellaneous Fees";
   $this->DB1($w1,4,0,41);
   $this->Dline(45,$newY -7,45,$newY + 12);
   $w1="Merchandise";
   $this->DB1($w1,45,0,34);
   $this->Dline(79,$newY -7,79,$newY + 12);
   $w1="Discount";
   $this->DB1($w1,79,0,35);
   $this->Dline(114,$newY -7,114,$newY + 12);
   $w1="Merchandise - Net";
   $this->DB1($w1,114,0,35);
   $this->Dline(149,$newY -7,149,$newY);
   $w1="Cores";
   $this->DB1($w1,149,0,29);
   $this->Dline(178,$newY -7,178,$newY);
   $w1="Taxable Amount";
   $this->DB1($w1,178,0,28);
   $this->Dline(206,$newY -7,206,$newY);
   $w1="Sales Tax";
   $this->DB1($w1,206,0,22);
   $this->Dline(228,$newY -7,228,$newY + 12);
   $w1="Freight";
   $this->DB1($w1,228,0,23);
   $this->Dline(250,$newY -7,250,$newY);
   $w1="TOTAL";
   $this->DB1($w1,250,0,37);
   $newY=$newY + 1;
   $this->SetY($newY);
   $this->D("Picker",1,0);
   $this->D("Packer",15,0);
   $this->D("Checker",29,0);
   $this->D("Pieces",64,0);
   $this->D("Weight",99,0);
   $this->SetFont('Arial','',8.1);
   $w1="All claims and returned goods must be accompanied by this Invoice No. and date. A 25%";
   $this->D($w1,112,3);
 $w1="handling charge may be made on all returned merchandise. Unpaid balance subject to";
   $this->D($w1,112,3);
$w1="service charge of 1 1/2% (18% per Annum).";
   $this->D($w1,112,3);
*/

   //all done with form 
   $this->SetFont($this->font,'',10.1);
   $this->SetY($saveY);
    if ($order["messg"] <> "0" and $pagenum == 1)
    {
       $msg=$order["messg"];
       if (sizeof($msg))
       {
echo strlen($order["messg"]);
echo "[{$msg}]";
exit;
        foreach ($msg as $messg)
        {
         $w1=$messg;
         $this->D($w1,44,4);
         $lns_prted++;
         $hdr_prted++;
        }
        $w=$this->GetY();
        //$w=$w - 4;
        //$this->SetY($w);
       } // end sizeof msg
    } // hdr messages
} // 1==1
}

function Line_Item($det)
{
    //Page header
 global $lns_prted;
 global $hash;
 global $tot_file;
 global $o_number;
 global $print_type;
 global $bill_to;
 global $db;

 $kithdr=0;
 $prline=1;
    $w1=sprintf("%4s",$det["line_num"]);
    if ($prline) $this->D($w1,2,0,"C");
    $w1=$det["whse_loc"];
    if ($prline) $this->D($w1,15,0);
    $w1=$det["uom"];
    if ($prline) $this->D($w1,43,0);
    $w1=sprintf("%5d",$det["qty_ord"]);
    if ($prline) $this->D($w1,50,0);
    $w1=sprintf("%5d",$det["qtytopick"]);
    if ($prline) $this->D($w1,65,0);
    $hash=$hash + $det["qtytopick"];
    $w1=sprintf("%5d",$det["qty_avail"]);
    if ($prline) $this->D($w1,79,0);

    $w1=sprintf("%4d",$det["qty_bo"]);
    if ($prline) $this->D($w1,92,0);

    $w1=$det["p_l"];
    if ($prline) $this->D($w1,105,0);
    $w1=$det["part_number"];
    if ($prline) $this->D($w1,115,0);
    //$w1=$det["oem_number"];
    //if ($prline) $this->D($w1,77,0);
    $w1=substr($det["part_desc"],0,14);
    if ($prline) $this->D($w1,144,0);
//Three prices to print List,Net and Extension

    $w1=$det["inv_code"];
    switch($w1)
    {
     case 1:
      $w1="Core";
      break;
     case 2:
      $w1="Defect";
      break;
     default:
      $w1="";
      break;
    } // end switch w1
    $l=8;
    if ($prline)
    {
     if ($w1 <> "")
     {
      $l=4;
      $this->ln(4);
      $this->D($w1,65,0);
     }
    $this->ln($l);
    }
    $lns_prted++;
    $lns_prted++;
    //$w1=" ";
    //$this->DL($w1,4,4,20);
    //$lns_prted++;

    if (intval($det["num_messg"]) > 0)
    {
       $msg=get_omessg($db,$o_number,$det["od_line_number"]);
       if (sizeof($msg)) foreach ($msg as $messg)
       {
        $w1=$messg;
        $this->D($w1,44,4);
        $lns_prted++;
       }
    } // dtl messages

    //Check for Part Messages
      $msg=array();
      //$msg=chk_partmsg($db,$det["od_shadow"],$print_type);
      if (sizeof($msg)) foreach ($msg as $messg)
       {
        $w1=$messg;
        $this->D($w1,44,4);
        $lns_prted++;
       }
    //Check for Part Messages

} // end line item
function D($in,$X,$ln,$align="C")
{
    $w=$this->GetStringWidth($in)+6;
    $this->SetX($X);
    $this->SetLineWidth(1);
    $this->Cell($w,7,$in,0,0,$align,false);
     if ($ln > 0) { $this->ln($ln); }

}

function DL($in,$X,$ln,$w=0,$align="L")
{
    if ($w==0) $w=$this->GetStringWidth($in)+6;
    $this->SetX($X);
    $this->SetLineWidth(.1);
    $this->Cell($w,7,$in,"R",0,$align,false);
     if ($ln > 0) { $this->ln($ln); }

}
function DA($in,$X,$ln,$pad="6")
{
     $in=str_pad(number_format($in,2,'.',','),$pad," ",STR_PAD_LEFT);
     $w=$this->GetStringWidth($in)+6;
     $this->SetX($X);
     $this->SetLineWidth(1);
     $this->Cell($w,7,$in,0,0,"R",false);
     if ($ln > 0) { $this->ln($ln); }
}
function DB1($in,$X,$ln,$w)
{
    //$w=$this->GetStringWidth($in)+5;
    $this->SetX($X);
    $this->SetFillColor(240,240,240);
    $this->SetLineWidth(.1);
    $this->Cell($w,3,$in,"TLRB",0,'C',true);
     if ($ln > 0) { $this->ln($ln); }
}
function DM($in,$X,$ln,$w)
{
    //$w=$this->GetStringWidth($in)+5;
    $this->SetX($X);
    $this->SetFillColor(240,240,240);
    $this->SetLineWidth(.1);
    $this->Cell($w,3,$in,"TLR",0,'C',true);
     if ($ln > 0) { $this->ln($ln); }
}
function DH($in,$X,$ln,$w)
{
    //$w=$this->GetStringWidth($in)+5;
    $this->SetX($X);
    $this->SetFillColor(220,220,220);
    $this->SetLineWidth(.1);
    $this->Cell($w,3,$in,1,0,'C',true);
     if ($ln > 0) { $this->ln($ln); }
}
function DB($in,$X,$ln,$w,$fill=3,$border="1")
{
    //$w=$this->GetStringWidth($in)+5;
    $this->SetX($X);
    switch ($fill)
    {
     case 1:
        $this->SetFillColor(220,220,220);
        break;
     case 2:
        $this->SetFillColor(100,100,100);
        $this->SetTextColor(255,255,255);
        break;
     default:
        $this->SetFillColor(255,255,255);
        break;
    } // end switch
    $this->SetLineWidth(.1);
    if (strpos($in,"\n"))
      $this->MultiCell($w,3.5,$in,$border,0,'C',true);
    else
      $this->Cell($w,7,$in,$border,0,'C',true);
    $this->SetTextColor(0,0,0);
     if ($ln > 0) { $this->ln($ln); }
}
function Dline($x1,$y1,$x2,$y2)
{
    $this->SetLineWidth(.2);
    $this->Line($x1,$y1,$x2,$y2);
}

function Dsep($font,$ftype,$point,$ln)
{
    $this->SetFont($font,$ftype,$point);
    if ($ln > 0) { $this->ln($ln); }
}

function Footer()
{
    //Page footer
global $lns_prted;
global $inv_end;
global $pagenum;
global $nototpages;
global $hash;
global $tot_file;
global $bill_to;
global $xorder;
//modify to get Y, then add lines to the end
if ($inv_end  > 0)
{ // print totals
$YY=$this->GetY();
$this->ln(8);
//$this->SetY(180);
        $w1="End of Order";
        $w1.="   Total Units: " . sprintf("%6s",$hash);
        $this->D($w1,10,0);
    $w1=$pagenum . " of ";
    $w1=sprintf("%4d",$pagenum) . "  of  {nb}";
    if ( $nototpages > 0 ) $w1=$pagenum;
    $this->SetFont($this->font,'',10.1);
    $this->D("Page",150,0);
    $this->D($w1,160,4);
    $pagenum++;
//$this->SetY(203);
//-rw-r--r--  1 nobody nobody 7370 Sep  7 14:58 611463-1.bmp
//-rw-r--r--  1 nobody nobody 8966 Sep  7 14:58 611465-1.bmp
//$w1=get_delv($db,$xorder["order_num"]);
$this->SetFont($this->font,'',12.1);
} 
else
{
$this->ln(8);
//$this->SetY(191);
        $w1="Continued";
        $this->D($w1,10,0);
    $w1=sprintf("%4d",$pagenum) . "  of  {nb}";
    if ( $nototpages > 0 ) $w1=$pagenum;
    $this->SetFont($this->font,'',10.1);
    $this->D("Page",150,0);
    $this->D($w1,160,4);
    $pagenum++;
        $w1="Next Page";
        $this->D($w1,10,4);
}
}

function AutoPrint($dialog=false)
{
    //Launch the print dialog or start printing immediately on the standard printer
    $param=($dialog ? 'true' : 'false');
    $script="print($param);";
//$script.="\r\nthis.submitForm(\"http://172.16.202.18/fpdf16/ld1.php\");";
$script.="\r\nthis.closeDoc();";
    $this->IncludeJS($script);
}
function AddAttach($afile)
{
 $script="var oFile = \"" . $afile . "\";";
 $script.="this.setDataObjectContents(\"MyNotes.txt\", oFile);";
 $script="this.AddAttachedFileFromBuffer(" . $afile . ", (uint)" . strlen($afile). ", \"HelloWorld.pdf\", \"HelloWorld\", false);";

 $this->IncludeJS($script);
}

function AutoPrintToPrinter($server, $printer, $dialog=false)
{
    //Print on a shared printer (requires at least Acrobat 6)
    $script = "var pp = getPrintParams();";
    if($dialog)
        $script .= "pp.interactive = pp.constants.interactionLevel.full;";
    else
        $script .= "pp.interactive = pp.constants.interactionLevel.automatic;";
    $script .= "pp.printerName = '\\\\\\\\".$server."\\\\".$printer."';";
    $script .= "print(pp);";
    $this->IncludeJS($script);
}

/*Code 39 params
x: abscissa
y: ordinate
code: barcode value
ext: indicates if extended mode must be used (true> by default)
cks: indicates if a checksum must be appended (false by default)
w: 0.4 by default)
h: 20 by default)
show human readable text (true by default)
wide: indicates if ratio between wide and narrow bars is high; if yes, ratio is 3, if no, it's 2 (true> by
*/
function Code39($x, $y, $code, $ext = true, $cks = false, $w = 0.4, $h = 20, $showhuman=true,$wide = true) {

    //Display code
    $this->SetFont('Courier', '', 10);
    $j=strlen($code);
    $j=$j * 2.8;
    if ($showhuman) $this->Text($x + $j, $y+$h+4, $code);

    if($ext) {
        //Extended encoding
        $code = $this->encode_code39_ext($code);
    }
    else {
        //Convert to upper case
        $code = strtoupper($code);
        //Check validity
        if(!preg_match('|^[0-9A-Z. $/+%-]*$|', $code))
            $this->Error('Invalid barcode value: '.$code);
    }

    //Compute checksum
    if ($cks)
        $code .= $this->checksum_code39($code);

    //Add start and stop characters
    $code = '*'.$code.'*';

    //Conversion tables
    $narrow_encoding = array (
        '0' => '101001101101', '1' => '110100101011', '2' => '101100101011', 
        '3' => '110110010101', '4' => '101001101011', '5' => '110100110101', 
        '6' => '101100110101', '7' => '101001011011', '8' => '110100101101', 
        '9' => '101100101101', 'A' => '110101001011', 'B' => '101101001011', 
        'C' => '110110100101', 'D' => '101011001011', 'E' => '110101100101', 
        'F' => '101101100101', 'G' => '101010011011', 'H' => '110101001101', 
        'I' => '101101001101', 'J' => '101011001101', 'K' => '110101010011', 
        'L' => '101101010011', 'M' => '110110101001', 'N' => '101011010011', 
        'O' => '110101101001', 'P' => '101101101001', 'Q' => '101010110011', 
        'R' => '110101011001', 'S' => '101101011001', 'T' => '101011011001', 
        'U' => '110010101011', 'V' => '100110101011', 'W' => '110011010101', 
        'X' => '100101101011', 'Y' => '110010110101', 'Z' => '100110110101', 
        '-' => '100101011011', '.' => '110010101101', ' ' => '100110101101', 
        '*' => '100101101101', '$' => '100100100101', '/' => '100100101001', 
        '+' => '100101001001', '%' => '101001001001' );

    $wide_encoding = array (
        '0' => '101000111011101', '1' => '111010001010111', '2' => '101110001010111', 
        '3' => '111011100010101', '4' => '101000111010111', '5' => '111010001110101', 
        '6' => '101110001110101', '7' => '101000101110111', '8' => '111010001011101', 
        '9' => '101110001011101', 'A' => '111010100010111', 'B' => '101110100010111', 
        'C' => '111011101000101', 'D' => '101011100010111', 'E' => '111010111000101', 
        'F' => '101110111000101', 'G' => '101010001110111', 'H' => '111010100011101', 
        'I' => '101110100011101', 'J' => '101011100011101', 'K' => '111010101000111', 
        'L' => '101110101000111', 'M' => '111011101010001', 'N' => '101011101000111', 
        'O' => '111010111010001', 'P' => '101110111010001', 'Q' => '101010111000111', 
        'R' => '111010101110001', 'S' => '101110101110001', 'T' => '101011101110001', 
        'U' => '111000101010111', 'V' => '100011101010111', 'W' => '111000111010101', 
        'X' => '100010111010111', 'Y' => '111000101110101', 'Z' => '100011101110101', 
        '-' => '100010101110111', '.' => '111000101011101', ' ' => '100011101011101', 
        '*' => '100010111011101', '$' => '100010001000101', '/' => '100010001010001', 
        '+' => '100010100010001', '%' => '101000100010001');

    $encoding = $wide ? $wide_encoding : $narrow_encoding;

    //Inter-character spacing
    $gap = ($w > 0.29) ? '00' : '0';

    //Convert to bars
    $encode = '';
    for ($i = 0; $i< strlen($code); $i++)
        $encode .= $encoding[$code[$i]].$gap;

    //Draw bars
    $this->draw_code39($encode, $x, $y, $w, $h);
}

function checksum_code39($code) {

    //Compute the modulo 43 checksum

    $chars = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 
                            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 
                            'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 
                            'W', 'X', 'Y', 'Z', '-', '.', ' ', '$', '/', '+', '%');
    $sum = 0;
    for ($i=0 ; $i<strlen($code); $i++) {
        $a = array_keys($chars, $code[$i]);
        $sum += $a[0];
    }
    $r = $sum % 43;
    return $chars[$r];
}

function encode_code39_ext($code) {

    //Encode characters in extended mode

    $encode = array(
        chr(0) => '%U', chr(1) => '$A', chr(2) => '$B', chr(3) => '$C', 
        chr(4) => '$D', chr(5) => '$E', chr(6) => '$F', chr(7) => '$G', 
        chr(8) => '$H', chr(9) => '$I', chr(10) => '$J', chr(11) => '£K', 
        chr(12) => '$L', chr(13) => '$M', chr(14) => '$N', chr(15) => '$O', 
        chr(16) => '$P', chr(17) => '$Q', chr(18) => '$R', chr(19) => '$S', 
        chr(20) => '$T', chr(21) => '$U', chr(22) => '$V', chr(23) => '$W', 
        chr(24) => '$X', chr(25) => '$Y', chr(26) => '$Z', chr(27) => '%A', 
        chr(28) => '%B', chr(29) => '%C', chr(30) => '%D', chr(31) => '%E', 
        chr(32) => ' ', chr(33) => '/A', chr(34) => '/B', chr(35) => '/C', 
        chr(36) => '/D', chr(37) => '/E', chr(38) => '/F', chr(39) => '/G', 
        chr(40) => '/H', chr(41) => '/I', chr(42) => '/J', chr(43) => '/K', 
        chr(44) => '/L', chr(45) => '-', chr(46) => '.', chr(47) => '/O', 
        chr(48) => '0', chr(49) => '1', chr(50) => '2', chr(51) => '3', 
        chr(52) => '4', chr(53) => '5', chr(54) => '6', chr(55) => '7', 
        chr(56) => '8', chr(57) => '9', chr(58) => '/Z', chr(59) => '%F', 
        chr(60) => '%G', chr(61) => '%H', chr(62) => '%I', chr(63) => '%J', 
        chr(64) => '%V', chr(65) => 'A', chr(66) => 'B', chr(67) => 'C', 
        chr(68) => 'D', chr(69) => 'E', chr(70) => 'F', chr(71) => 'G', 
        chr(72) => 'H', chr(73) => 'I', chr(74) => 'J', chr(75) => 'K', 
        chr(76) => 'L', chr(77) => 'M', chr(78) => 'N', chr(79) => 'O', 
        chr(80) => 'P', chr(81) => 'Q', chr(82) => 'R', chr(83) => 'S', 
        chr(84) => 'T', chr(85) => 'U', chr(86) => 'V', chr(87) => 'W', 
        chr(88) => 'X', chr(89) => 'Y', chr(90) => 'Z', chr(91) => '%K', 
        chr(92) => '%L', chr(93) => '%M', chr(94) => '%N', chr(95) => '%O', 
        chr(96) => '%W', chr(97) => '+A', chr(98) => '+B', chr(99) => '+C', 
        chr(100) => '+D', chr(101) => '+E', chr(102) => '+F', chr(103) => '+G', 
        chr(104) => '+H', chr(105) => '+I', chr(106) => '+J', chr(107) => '+K', 
        chr(108) => '+L', chr(109) => '+M', chr(110) => '+N', chr(111) => '+O', 
        chr(112) => '+P', chr(113) => '+Q', chr(114) => '+R', chr(115) => '+S', 
        chr(116) => '+T', chr(117) => '+U', chr(118) => '+V', chr(119) => '+W', 
        chr(120) => '+X', chr(121) => '+Y', chr(122) => '+Z', chr(123) => '%P', 
        chr(124) => '%Q', chr(125) => '%R', chr(126) => '%S', chr(127) => '%T');

    $code_ext = '';
    for ($i = 0 ; $i<strlen($code); $i++) {
        if (ord($code[$i]) > 127)
            $this->Error('Invalid character: '.$code[$i]);
        $code_ext .= $encode[$code[$i]];
    }
    return $code_ext;
}

function draw_code39($code, $x, $y, $w, $h) {

    //Draw bars

    for($i=0; $i<strlen($code); $i++) {
        if($code[$i] == '1')
            $this->Rect($x+$i*$w, $y, $w, $h, 'F');
    }
}
}

//Start of main **********************************************************
$xorder=array();
$print_it=0;
$ord=new ORDERS;
$pdf=new PDF();
////$pdf=new PDF_Code39();

if (isset($_REQUEST["o_number"])) $o_number=$_REQUEST["o_number"]; else $o_number=0;
 //$o_number= 10081; // temp
 if ($o_number > 0)
 {
 $ord->loadWhseOrder($o_number);
 $print_it=1;
//echo "<pre>";
//print_r($ord);
//exit;
 $order=$ord->Order;
 if (count($order) < 1) 
 {
  echo "<p>Order {$o_number} Not Found!</p>";
  exit;
 }
 $bill_to=$ord->BillTo;
 $ship_to=$ord->ShipTo;
 $shipfrom=get_remit_to($db,$order["company"]);

 if ($order["drop_ship_flag"] > 0)
 {
  $ship_to=get_drop_ship($db,$o_number);
 }
 $d=$ord->Items; // ordered by line num
 $ip=$ord->ItemPull; // ordered by whse loc and only items to pick
 $detail=array();
 $usedLines=array();
 $i=1;
 // Create new detail line with all lines in Itempull then add additional 
 // parts like out of stock and Spec orders
if (count($d) > 0)
{
 foreach ($ip as $det)
 {
  $ln=$det["line_num"];
  $detail[$i]=$d[$ln];
  if (isset($det["qtytopick"])) $detail[$i]["qtytopick"]=$det["qtytopick"];
  if (isset($det["whse_loc"])) $detail[$i]["whse_loc"]=$det["whse_loc"];
  $usedLines[$ln]=true;
  $i++; 
 } // end foreach ip
}
// Add any other lines that are not included in itempull
if (count($d) > 0)
{
 foreach($d as $det)
 {
  $ln=$det["line_num"];
  if (!isset($usedLines[$ln]))
  {
   $detail[$i]=$d[$ln];
   $detail[$i]["qtytopick"]=0;
   $detail[$i]["whse_loc"]="*";
   $i++;
  }
 } // end foreach detail
}// end count detail

 //$detail=$ord->whseSeq; // ordered by whse location
$lineitem=1;

$title="Pick: " . $order["company"] . " " . $order["order_num"];
$pagenum=1;
$pdf->SetTitle($title);
$pdf->SetSubject($title);
$pdf->SetCreator($o_number);
$pdf->SetAuthor('WD');
$pdf->SetMargins(-.7,0,0);
$pdf->AliasNbPages();
$nototpages=0;
$pdf->AddPage("P") ;
$pages=0;
//Save Totals for Footer
//Invoice Header
$pdf->SetFont('Courier','',12.1);
$inv_end=0;
$hash=0;
$cur_pl="";
$oem_flag="N";
$tot_file=0.00;
$lns_prted=$hdr_prted;
$lines_processed=0;
$i=1;
//while ($lines_processed < $order["num_lines"])
//{
//if ($lns_prted > 23)
 //{
   //$pagenum++;
   //$lns_prted=0;
   //$pdf->AddPage("P");
 //}
//}
//$det=check_oem($db,$detail[$i],9);
if (count($detail) > 0)
{
 foreach ($detail as $det)
 {
  if ($det["qty_ord"] <> 0) { $pdf->Line_Item($det); }
  $lines_processed++;
  $i++;
 } // end foreach detail
} // end count detail > 0
$inv_end=1;
 } // end o_number > 0

if ($print_it > 0) { $pdf->Output(); }


function get_omessg($db,$o_number,$line_num)
{
 $messg=array();
 $qstring="select message_num, message";
 $qstring.=" from ORDMESSG";
 $qstring.=" where order_num={$o_number}";
 $qstring.=" and line_num={$line_num}";
 $qstring.=" order by message_num";
 $rc=$db->query($qstring);
 $numrows=$db->num_rows();
 $i=1;
 while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     { 
	$messg[$i]=$db->f("message"); 
     }
    $i++;
   } // while i < numrows
return($messg);
} // end get_omessg

function get_drop_ship($db,$o_number)
{
 $dropship=array();
 $qstring=<<<SQL
select _name,addr1,addr2,city,state, zip,ctry,phone
from DROPSHIP
where drp_order_number={$o_number}
SQL;
 $rc=$db->query($qstring);
 $numrows=$db->num_rows();
 $i=1;
 while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
	$dropship["name"] =$db->f("name");
	$dropship["attn"] =$db->f("addr1");
	$dropship["addr"] =$db->f("addr2");
	$dropship["city"] =$db->f("city");
	$dropship["state"]=$db->f("state");
	$dropship["zip"]  =$db->f("zip");
	$dropship["cntry"]=$db->f("ctry");
	$dropship["phone"]=$db->f("phone");
     }
    $i++;
   } // while i < numrows
return($dropship);
} // end get_drop_ship

function get_svia($db,$svia)
{
 $svia_info="";
 $qstring="select via_desc";
 $qstring.=" from SHIPVIA";
 $qstring.=" where via_code=" . quoteit($svia);
 $rc=$db->query($qstring);
 $numrows=$db->num_rows();
 $i=1;
 while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $svia_info=$db->f("via_desc");
     }
    $i++;
   } // while i < numrows
return($svia_info);
} // end get_svia
function get_remit_to($db,$comp)
{
 $remit_info=array();
 $qstring="select company_name,company_address,company_city,
company_state,
company_zip,
company_phone";
 $qstring.=" from COMPANY";
 $qstring.=" where company_number= $comp";

 $rc=$db->query($qstring);
 $numrows=$db->num_rows();
 $i=1;
 while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $remit_info["name"]=$db->f("company_name");
        $remit_info["addr"]=$db->f("company_address");
        $city=$db->f("company_city");
        $state=$db->f("company_state");
        $zip=$db->f("company_zip");
        $remit_info["city"]="{$city},{$state} {$zip}";
        $remit_info["phone"]=$db->f("company_phone");
     }
    $i++;
   } // while i < numrows
return($remit_info);
} // end get_svia


function check_oem($db,$det,$pl_comp)
{
 global $cur_pl;
 global $oem_flag;
 $det["oem_number"]="";
 $pl=$det["od_prod_line"];

IF ($pl <> $cur_pl) 
          {
           $oem_flag="N";
           $cur_pl=$pl;
            $qstring=" SELECT oem_flag FROM PRODLINE";
            $qstring.=" WHERE pl_code    = " . quoteit($cur_pl);
 	    $qstring.=" AND pl_company = $pl_comp";
 	    $rc=$db->query($qstring);
 	    $numrows=$db->num_rows();
            if ($numrows)
             {
              $db->next_record();
              $oem_flag=$db->f("oem_flag");
             }
          }

IF ($oem_flag == 'Y') 
          {   // prodline has OEM numbers
            $qstring=" SELECT alt_part_number FROM ALTERNAT";
            $qstring.=" WHERE alt_shadow_num = " . $det["od_shadow"];
  	    $qstring.=" and alt_type_code = 9990";
 	    $rc=$db->query($qstring);
 	    $numrows=$db->num_rows();
            if ($numrows)
             {
              $db->next_record();
              $det["oem_number"]=$db->f("alt_part_number");
             }
          }   // prodline has OEM numbers

return($det);
} // end check_oem

function chk_partmsg($db,$shadow,$print_type)
{
$msgs=array();
$awhere="";
if ($print_type == "I") $awhere = "and pnote_code in ('I','B')";
if ($print_type == "P") $awhere = "and pnote_code in ('P','B')";
if ($awhere=="") return($msg);

$SQL=<<<SQL
select part_num_notes from PARTS where shadow_number = {$shadow}

SQL;

  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
            $part_num_notes=$db->f("part_num_notes");
     }
     $i++;
   } // while i < numrows

if ($part_num_notes > 0)
{
$SQL=<<<SQL
SELECT pnote_line, pnote_code, pnote_note
FROM PARTNOTE
WHERE pnote_shadow = {$shadow}
{$awhere}
ORDER BY pnote_line ASC

SQL;
  $rc=$db->query($SQL);
  $numrows=$db->num_rows();
  $i=1;
  while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
            $msg[$i]=$db->f("pnote_note");
     }
     $i++;
   } // while i < numrows
} // end num_notes > 0
return($msg);
} // end chk_partmsg
function get_delv($db,$o_number)
{
 $delv="";
 $qstring="select convert(char(10), delivery_date,101) as delivered,";
 $qstring.=" convert(char(5), delivery_time,8) as delivtime";
 $qstring.=" from ORDDELVD A, ORDDELVH B";
 $qstring.=" where o_number=$o_number";
 $qstring.=" and B.run_number = A.run_number";

 $rc=$db->query($qstring);
 $numrows=$db->num_rows();
 $i=1;
 while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $delv=$db->f("delivered");
        $dtime=$db->f("delivtime");
        if (trim($dtime) <> "") $delv.=" @ {$dtime}";
     }
    $i++;
   } // while i < numrows
return($delv);
} // end get_delv
function kitbits($in)
{
 $out=array();
 $j=decbin($in);
 $out["showOnPic"]=substr($j,0,1);
 $out["showOnInv"]=substr($j,1,1);
 $out["showPrice"]=substr($j,2,1);
 $out["showPart"]=substr($j,3,1);
 return($out);
}

?>
