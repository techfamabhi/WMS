<?php
//this function reads config and relies on the folowing begin set;
/*
 $ftpserver
 $ftpuser
 $fptwd

 $lockfile = file to place on ftp server while uploading

 Directoies 
 $wmsdir = top directory of wms export
 $ftpOut = Directory on ftp server to change to before uploading
 $outDir = the local directory where infile resides

 args    
 infile  = name of file to upload (filename not full path)
*/
require_once("cl_ftp.php");

function send_file($infile)
{
//temp line
//return(1);

require("config.php");

$f=new WFTP;
$f->server=$ftpserver;
$f->user=$ftpuser;
$f->pwd=$ftpwd;


$rc=$f->open();

if (!$rc) return(false);
if ($rc < 0)
{
 echo "error opening ftp server: {$f->server}  user: {$f->user}\n";
 exit;
}

$rc=$f->chg_dir($ftpOut);
//put a uploading file out there
$ufile="{$wmsDir}/res/{$lockfile}";
$rc=$f->upload($ufile,$lockfile);

$lfile="{$outDir}/{$infile}";
$rcc=$f->upload($lfile,$infile);

//for ftpIn directory, get list of files , temp to see if it uploads
//$contents=$f->get_list("");
//print_r($contents);

//All done, remove the lockfile and close
$rc=$f->delete($lockfile);
$f->close();
return($rcc);
} // end send_file



//$ftpOut   ="ErpToWise";  //copy from outDir to this
//$ftpIn    ="WiseToErp";  //copy from inDir to this


//$lockfile="download_in_process";
