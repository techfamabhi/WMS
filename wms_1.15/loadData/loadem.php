<?php
//include_once("/usr1/include/db_main.php");
include_once("../include/db_main.php");
//include_once("/usr1/include/update.php");
$db=new WMS_DB;

if (isset($argv[1]))
{
    $file=$argv[1];

 if (file_exists($file))
 {
     $data=file($file);
     $table="";
     $datafile="";
     $fields=array();
     $fldnames="(";
     if (count($data))
     {
         foreach ($data as $rec=>$d)
         {
             $d=str_replace("\n","",$d);
             $w=explode(":",$d);
             switch($w[0])
             {
             case "Table":
                 $table=$w[1];
                 break;
             case "Datafile":
                 $datafile=$w[1];
                 break;
             case "Fields":
                 $w1=explode(",",$w[1]);
                 if (count($w1))
                 {
                     foreach ($w1 as $f=>$t)
                     {
                      $w2=explode(";",$t);
                      $fields[$f]["field"]=$w2[0];
                      $fields[$f]["type"] =$w2[1];
                      $comma="";
                      if (strlen($fldnames) > 1) $comma=",";
                      $fldnames.="{$comma}{$w2[0]}";
                     } // end foreach w1
                      $fldnames.=")";
                 } // end count w1
                 break;
             } // end switch w[0]
         } // end foreach data
     } // end count data

 } // end file exists
 echo "Table: {$table} File: {$datafile}\n";
 echo "Fields;";
 //print_r($fields);
 echo "\nfld names: {$fldnames}\n";
 unset($data);
 $recs=0;
 $batchSize=100;
 $data=file($datafile);
 if (count($data))
 {
     foreach ($data as $rec=>$d)
     {
        $w=explode("|",$d);
        if (count($w))
        {
         $insert="insert into {$table} {$fldnames}\n values (";
         $j=0;
         foreach($w as $f=>$val)
         {
             $comma="";
             if ($j)
             {
                 $j=1;
                 $comma=",";
             }
             $val=str_replace("\n","",$val);
             $v=$val;
             if (strtolower($fields[$f]["type"]) == "string") $v=quoteit($val);
             else if (strpos($val,",")) $v=str_replace(",","",$val);
             $insert.="{$comma}{$v}";
             $j++;
         }  // end foreach w
          $insert.=")";
        } // end count w
        if (($recs % 100) == 0 or $recs < 25) echo "{$recs} added\n";
        $rc=$db->Update($insert);
        $recs++;
     } // end foreach data #2
    echo "All Done, Total Row(s) Added={$recs}\n";
 } // end count data #2
} // end argv[1] is there

function quoteit($in)
{
    if (strpos($in,",")) $in=str_replace(",","\'",$in);
    return('"' . $in . '"');
} // end function quoteit
?>
