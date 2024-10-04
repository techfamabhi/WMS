<?php
/**
 * Global Location Number (GLN) Check Digit Calculator
 *
 * Calculates a check digit for GS1 Identification keys
 * that ensures the integrity of the key.
 *
 * All GS1 ID Keys need a check digit, except Component/Part Identifier (CPID) and Global Individual Asset Identifier (GIAI). Global Model Number (GMN) includes a pair of check characters rather than a single check digit.
 * cl_sbcc.php
 *
 * @author Dave Erlenbach
 * @version 1.0
 * @copyright (c) 2022
 *
 * Primarily used to generate an SSCC-18 check digit for a barcode to it
 * can easily be read by barcode readers during handling operations
 * see https://as2protocol.com/sscc-18-generation/
 * Structure of a SSCC-18 number (preceeded by a 00 app identifier)
 * GS1 Prefix varies in length between 6 to 9 digits
 * The 1st digit of that segment is always 0 for North American
 * (US & Canada) companies and can be any non-zero value for others.
 * Variable Length Company Prefixes (VLCPs)
 * Based on the number of products allowed for a company, the length of the GS1
 * company prefix varies between 6 to 9 digits (excluding the 1st digit
 * mentioned above). So in some cases it is possible that the GS1 prefix
 * occupying less than 10 digits. In such cases, the rest of the digits
 * can be occupied by the serial number.
 *
 * 0          1
 * 1 2345678901 234567 8
 * 0 0xxxxxxXXX yyyyyy n
 *
 * position    Description
 * 1-1        Extension digit (normally 0)
 * 2-(8-11)    GS1 Prefix
 * (9-11)-17    Serial or Container Number
 * 18-18    Check Digit
 */

/* usage =================================================================
// calc check digit and return full string
echo "<pre>";
$val="00629104150021";
$val="0699239000006";
$gln=new SSCC;
$cd=$gln->calc($val);
echo "<pre>Original value is {$val}, Check digited value is {$cd}\n";

// Or send GS1 account Prefix and container # to format complete number

$ap_gln="0699239";
$cont="1001";
$ww= $gln->bld_SSCC($ap_gln,$cont);

echo "full barcode string for GS1={$ap_gln} container={$cont} ={$ww}";

end  usage ===============================================================
*/


// **********************************************************************
class SSCC
{
    public $inputVal;
    public $outputVal;       // the last input value used
    public $stdUsed;      // the last output value
    public $checkDigit;        // the last standard used;
    public $errCode;     // the last check digit calculated
    public $errText;        // the last check digit calculated
    private $formats;        // the last Error Text

    //----------------------------------------------------------------------

    public function __construct()
    {
        //----------------------------------------------------------------------
        // array of formats and their output lengths (input lenght is 1 less)
        $this->formats = array(
            "GTIN-8" => 8,
            "GTIN-12" => 12,
            "GTIN-13" => 13,
            "GTIN-14" => 14,
            "GSIN" => 17,
            "SSCC" => 18
        );
    } // end construct
    //----------------------------------------------------------------------

//Odd numbers x 3, even number x 1
    //----------------------------------------------------------------------

    public function bld_SSCC($gs1Prefix, $container)
    {
        // givin Id and container, build a SSCC-18 string with prefix and check digit
        // to print tracking barcode
        $out = "000"; // Appl Identifier and Extension digit
        // pad the gs1 id, but check if length = 7 or more
        if (strlen($gs1Prefix) == 7) { // normal GS1 Prefix
            $serial = "000000";
            $preOrSer = "000";
            $j = strlen($container);
            if ($j < 7) $w = str_pad($container, 6, "0", STR_PAD_LEFT);
            else {
                $preOrSer = substr($preOrSer, 0, (10 - $j));
                $w = str_pad($container, 9, "0", STR_PAD_LEFT);
            } // j > 7

            // add extension digit, the prefix, then 000, then container
            $sscc = "{$gs1Prefix}{$preOrSer}{$w}";
            $cd = $this->calc($sscc);
            $out .= "{$cd}";
            return $out;
        } // normal GS1 Prefix

        // to be continued
    } // end function calc

    //----------------------------------------------------------------------

    public function calc($val)
    {
        //$val=abs($val); // remove negative numbers

        // init class vars
        $this->outputVal = "";
        $this->inputVal = $val;
        $this->checkDigit = 0;
        $this->stdUsed = "";
        $this->errCode = "";
        $this->errText = "";

        $len = strlen($val); // local var to store length
        // figure out what standard the input val is
        switch ($len + 1) {
            case 8:
            case 12:
            case 13:
            case 14:
                $this->stdUsed = "GTIN-" . trim($len + 1);
                break;
            case 17:
                $this->stdUsed = "GSIN";
                break;
            case 18:
                $this->stdUsed = "SSCC";
                break;
        } // end switch length

        if (isset($this->formats[$this->stdUsed]) and is_numeric($val)) { // format is set, lets calculate
            $sum = 0;
            $w = str_pad($val, 18, "0", STR_PAD_LEFT);
            // calc each position of the number separatly
            for ($i = 0; $i < 18; $i++) {
                $m = ($i % 2) ? 3 : 1;
                $sum = $sum + ((intval(substr($w, $i, 1)) * intval($m)));
            } // end for i

            // round to next multiple of 10 and sumbtract the sum
            $this->checkDigit = (intval(ceil($sum / 10) * 10) - $sum);

            //load ret values
            $this->outputVal = str_pad($val . $this->checkDigit, $this->formats[$this->stdUsed], "0", STR_PAD_LEFT);
        } // format is set, lets calculate
        else { // there is an error with the input
            if ($this->stdUsed == "") { // incorrect length was givin
                $this->errCode = -1;
                $this->errText = "Value {$val} is not the correct length ({$len})";
            } // incorrect length was givin
            else { // input was a correct length so a different error occurred
                if (!is_numeric($val)) {
                    $this->errCode = -2;
                    $this->errText = "Value {$val} is not numeric";
                } else {
                    $this->errCode = -2;
                    $this->errText = "Invalid Format {$this->stdUsed}";
                }
            } // there is an error with the input
        } // input was a correct length so a different error occurred

        return $this->outputVal;

    } // end bld_SSCC
    //----------------------------------------------------------------------

} // end class checkDigit
