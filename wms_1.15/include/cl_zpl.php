<?php
// cl_zpl.php -- Print Barcode to Zebra printer using ZPL

//* usage
/*
 +-----------------+-----+-------------+-------------+
| alt_part_number | p_l | part_number | primary_bin |
+-----------------+-----+-------------+-------------+
| 765809465732    | WIX | 46573       | A-07-33-D   |
| 765809490888    | WIX | 49088       | A-03-18-B   |
| 765809571518    | WIX | 57151       |             |
+-----------------+-----+-------------+-------------+

*/
$a=new ZPL; // create instance
$a->human="152"; // set human readable text
$a->q=2;
$a->printerURL="172.16.202.81:9100"; // set printer
$a->printLabel("152"); //print the UPC code to the printer
//*/

class ZPL
{
 public $UPC=""; // upc code to output
 public $human="";  // part number or text to output
 public $printerURL="172.16.202.81:9100"; // ip and port number of printer
 public $q=1; // ????
 public $use=0; // 
 public $debug=0; // output debug info
 public $printOutput;

 private $beginLabel="^XA"; // beginning of label
 private $endLabel  ="^XZ"; // end of label

//------------------------------------------------------------------------
 function __construct()
 {
  $this->bc="^XA"; // beginning of format
 } // end construct
//------------------------------------------------------------------------
 public function printLabel($UPC)
 {
if ($UPC == "") exit;
/*
 example of code 128 barcode in ZPL (zebra programming language)


$bc="^XA"; // beginning of format

// LS shifts label over x pixels (^LS20 it is moved 20 pixels), must be before
//    the first ^FS

//set field origin, x,y,[z]   z optional, 0=left justify, 1=right,2=auto
$bc.="^FO100,100";

// specify ratio between lines in barcode
$bc.="^BY3"; // default is 2

//Add Barcode formatting
$orientation="N"; // N=normal, R=rotate 90 colckwise, I=180, B=270
$barcodeheigh=100; // height in dots
$printHuman="Y";
$phu="N"; //Print Human above
$ucc="N"; // add mod 103 check digit;
$bc.="^BC{$orientation},{$barcodeheight},{$printHuman},{$phu},{$ucc}";

//print the barcode
$barcode="123456";
$bc.="^FD{$bar_code}^FS";

//end format
$bc.="^XZ";

// ^BC = Code 128, N=Orientation Normal,
// ^B3 = Code 39,  N=Orientation Normal,
// ^BU = UPC,  N=Orientation Normal,

*/
if ($this->human <> "") $human=$this->human; else $human="";
//if ($human <> "") $human="   " . substr($human,0,3) . " " . substr($human,3);
$j=strlen($UPC);
$k=is_numeric($UPC);
$barcode="BC"; //code 128
if ($k and $j == 12) $this->use="1"; // upc
if ($k and $j > 12) $this->use="2"; // interleaved
$qp="";
if ($this->q > 1) $qp=" ({$this->q})";
//aztec
//^B0R,7,N,0,N,1,0^FD{$UPC}^FS
//^{$barcode}N,100,Y,N,Y^FO20,40^BY3^FD{$UPC}^FS
$code="aztec";
if (!$this->debug) $code=$qp;
$aztec=<<<TEXT
^XA
^LS20
^FO20,10^CFD,27,16^FD{$human} {$code}^FS
^FO40,50^BY3
^B0N,8,N,0,N,1,0^FD{$UPC}^FS
^FO50,200^CFD,27,16^FD{$UPC}^FS
^XZ

TEXT;
$code="UPC";
if (!$this->debug) $code=$qp;
$upccode=<<<TEXT
^XA
^LS20
^FO20,10^CFD,27,16^FD{$human} {$code}^FS
^FO40,50^BY3
^BUN,100,Y,N,Y^FO85,80^BY3^FD{$UPC}^FS
^XZ
TEXT;
$code="39";
if (!$this->debug) $code=$qp;
$code39=<<<TEXT
^XA
^LS20
^FO20,10^CFD,27,16^FD{$human} {$code}^FS
^FO40,50^BY3
^BCN,N,100,Y,N^FO10,40^BY3^FD{$UPC}^FS
^XZ
TEXT;
$wid="3,2";
if ($j > 10) $wid="2,1";
if ($j > 14) $wid="1.4,1.4";
$code="128";
if (!$this->debug) $code=$qp;
//^B3N,100,Y,N,N
//^FD{$UPC}^FS
//FOr some reason, the ZD410 printer wont print code128, this is printing code39
$code128=<<<TEXT
^XA
^LS20
^FO20,10^CFD,27,16^FD{$human} {$code}^FS
^FO10,60^BY{$wid}
^B3N,N,100,Y,N^FO10,60^BY{$wid}^FD{$UPC}^FS
^XZ
TEXT;

$code="Intlv";
if (!$this->debug) $code=$qp;
// LS shifts label over x pixels (^LS20 it is moved 20 pixels)
$interleaved=<<<TEXT
^XA
^LS20
^FO20,10^CFD,27,16^FD{$human} {$code}^FS
^FO40,50^BY2,1
^B2N,100,Y,N,N^FO20,80^BY3,2^FD{$UPC}^FS
^XZ
TEXT;
$code="pdf";
if (!$this->debug) $code=$qp;
$micropdf417=<<<TEXT
^XA
^FO20,10^CFD,12,4^FD{$human} {$code}^FS
^FO40,50^BY4
^BCN,100
^FD>;>{$UPC}^FS
^XZ
TEXT;
$printjob="";
switch ($this->use)
 {
 case 1:
   $printjob=$upccode;
   break;
  case 2:
   $printjob=$interleaved;
   break;
  default:
   //$printjob=$aztec;
   $printjob=$code128;
  break;
 } // end switch use
//Print directly to the printer
if(isset($printjob)){
$k=explode(":",$this->printerURL);
$ip=$k[0];
$port=$k[1];
$print_output= $printjob;
if (!$this->debug) 
{
 echo "Human Readable#: {$human}\n";
 echo "ip: {$ip}\n";
 echo "port: {$port}\n";
//echo "printerURL: {$this->printerURL}\n";
} // end debug
    $fp=pfsockopen($ip, $port);
    fputs($fp, $print_output);
echo "Printing\n";
    fclose($fp);
 $this->printOutput=$printjob;
}
 } // end printLabel
//close the window

public function closeHtml()
{
$htm=<<<HTML
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Language" content="en-us">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
 <textarea>{$printjob}</textarea>
</body>
</html>
<script>
 window.close();
</script>
HTML;
 return $htm;
} // end showContents
} // end class ZPL
?>
