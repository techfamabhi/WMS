<?php
// Function to write to logfile 
function wr_log($logfile,$logentry)
{
  $cdate=date("m/d/Y H:i:s");
  $fp=fopen("$logfile", "a");
  fwrite($fp,"$cdate\n");
  fwrite($fp,"$logentry\n");
  fclose($fp);
}
?>
