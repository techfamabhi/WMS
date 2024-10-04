<?
      if (isset($rowData)) unset($rowData);
      $rowData=array();
      $maxFlds=count($fields["POD"]) -1;
      if (trim($w[1]) <> "")
      { // po is not empty
       $rowData=loadFields($fields["POD"],$w);
       if (isset($poType) and $opt[399] > 0 and $poType == "R")
//echo "<pre>";
//print_r($rowData);
//echo "poType={$poType}\n";
//print_r($opt);
//exit;
       { // its a return and direct to putaway is on
        $pl=trim($rowData["p_l"]);
        $partNum=trim($rowData["part_number"]);
        $pnum=$pl . $partNum;
        $part=getPart($db,$pnum);
        $j=0;
        if ($part["num_rows"] > 1)
        { // uh ooh have more than 1 part
        $shadow=$rowData["shadow"];
        $msg="07|{$PO} Duplicate part number, P/L: {$pl} part number: {$partNum}\n";
        header('X-PHP-Response-Code: 400', true, 400);
        $rc=logError($w[0],$baseFile,$rowcnt,$PO,$msg,$d,0);
//$output[]= $msg;
exit;
       } // uh ooh have more than 1 part
       if ($part["status"] <> -35)
       { // part is on file
         $rowData["shadow"]=$part[$j]["shadow_number"];
         // need to get UOM info to fill in wght and volume
       } // part is on file
       else
       { // part is NOF
        $msg="08|Invalid part P/L: {$pl} part number: {$partNum}";
        header('X-PHP-Response-Code: 400', true, 400);
        $rc=logError($w[0],$baseFile,$rowcnt,$PO,$msg,$d,0);
//$output[]= $msg;
        $output[]= $rc;
        mvFile($f,$errDir);
        exit;
       } // part is NOF
        // Part is good, add to putaway tote
        $shadow=$part[$j]["shadow_number"];
        $lt=$rowData["line_type"];
        $op=0;
        if ($lt == "C") $op=1; 
        if ($lt == "D") $op=2;
        $toteCode=$opt[(400 + $lt)];
        $Tote=new Tote;
        // verify the tote is valid
        $toteId=$Tote->getToteId($toteCode);
        if ($toteId < 0)
        {
         echo "Option {$lt} is set to an invalid tote.";
         exit;
        }
        // update totehdr, set date, last loc = "{typeof} RETURN"
        $rc=$Tote->updToteHdr($toteId,$comp, 2,"PUT","","RETURN");
echo "comp={$comp} rc={$rc}\n";;
print_r($rowData);
exit;
        // add to totedtl
        $rc=$Tote->addItemToTote($toteCode,$shadow, $rowData["qty_ord"], $rowData["uom"]);
        if ($Tote->numRows < 1) 
        { // add to tote failed
         echo "Error Adding {$pl} {$partNumber} to tote {$toteCode}.";
         exit;
         
        } // add to tote failed
       } // its a return and direct to putaway is on
       $rowData["shadow"]=0;


       // force comp to 1 since POD doesn't have the company# in it
       //$hostcomp=$rowData["company"];
       //$comp=convert_comp($hostcomp);
       if (!isset($comp)) $comp=1;
       
       $PO=$rowData["poi_po_num"];
       $poLine=$rowData["poi_line_num"];
       $pl=trim($rowData["p_l"]);
       $partNum=trim($rowData["part_number"]);
       $pnum=$pl . $partNum;
       $validPO=chkHostPo($db,$comp,$PO);
//make sure PO is on file
       if ($validPO < 1) 
       {
        $msg="06|(POD) PO Number: {$PO} is Not on File\n";
        header('X-PHP-Response-Code: 400', true, 400);
        $rc=logError($w[0],$baseFile,$rowcnt,$PO,$msg,$d,0);
//$output[]= $msg;
exit;
       } // end validPO
       //replace host PO with WMS PO
       $rowData["poi_po_num"]=$validPO;
       $poline=chkPoLine($db,$validPO,$poLine);
//check if line exists already, if not init non imported fieds
       if ($poline["status"] == -35)
       {
         $rowData["weight"]=0.00;
         $rowData["volume"]=0.00;
         $rowData["qty_recvd"]=0;
         $rowData["qty_cancel"]=0;
       } // end po line nof
       $part=getPart($db,$pnum);
       $j=0;
//check part count
       if ($part["num_rows"] > 1)
       { // uh ooh have more than 1 part
        $msg="07|{$PO} Duplicate part number, P/L: {$pl} part number: {$partNum}\n";
        header('X-PHP-Response-Code: 400', true, 400);
        $rc=logError($w[0],$baseFile,$rowcnt,$PO,$msg,$d,0);
//$output[]= $msg;
exit;
       } // uh ooh have more than 1 part
       if ($part["status"] <> -35)
       { // part is on file
         $rowData["shadow"]=$part[$j]["shadow_number"];
         // need to get UOM info to fill in wght and volume
       } // part is on file
       else
       { // part is NOF
        $msg="08|Invalid part P/L: {$pl} part number: {$partNum}";
        header('X-PHP-Response-Code: 400', true, 400);
        $rc=logError($w[0],$baseFile,$rowcnt,$PO,$msg,$d,0);
//$output[]= $msg;
        $output[]= $rc;
        mvFile($f,$errDir);
        exit;
       } // part is NOF
       
       //Update, Insert or Delete the record
       $where=<<<SQL
where poi_po_num = "{$validPO}"
  and poi_line_num = {$poLine}

SQL;
       $rc=$upd->updRecord($rowData,"POITEMS",$where);
       $rc1=updPOLines($db,$validPO,$poLine);
       $x=json_decode($rc,true);
       $output[]= "{$w[0]}|{$x["message"]}\n";
       $xx=$w[0] . "S";
       $$xx++;
      } // po is not empty
?>
