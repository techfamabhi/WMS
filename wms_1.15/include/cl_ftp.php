<?php
/*
WFTP - Ver 1.0 FTP class
07/26/19 dse

 methods;
	open = open connection and login
      upload = upload file
    download = download file
      pasive = toggle passive mode
      rename = rename file
      delete = delete file
     chg_dir = change directory
    get_list = get a list of files in directory
       close = close connection
*/

class WFTP
{
var $fp;
var $server;
var $user;
var $pwd;
var $pasive=false;
var $curdir;

function open()
{
// set up basic connection
$this->fp = ftp_connect($this->server);

if (!$this->fp) return(-1);

// login with username and password
$rc = ftp_login($this->fp, $this->user, $this->pwd);
$this->curdir=ftp_pwd($this->fp);
return($rc);
} // end open

function pasive($mode)
{
 if ($mode) $this->pasive=true;
    else    $this->pasive=false;
 ftp_pasv($resource, $this->pasive);
} // end pasive

function upload($lfile,$rfile,$mode="A")
{
// upload a file
if ($mode=="A") $rc=ftp_put($this->fp, $rfile, $lfile, FTP_ASCII);
else            $rc=ftp_put($this->fp, $rfile, $lfile, FTP_BINARY);

return($rc);      
} // end upload

function download($rfile,$lfile,$mode="A")
{
if ($mode=="A") $rc=ftp_get($this->fp, $lfile, $rfile, FTP_ASCII);
else            $rc=ftp_get($this->fp, $lfile, $rlile, FTP_BINARY);

return($rc);      
} // end download

function rename($ofile, $nfile)
{
 $rc=ftp_rename($this->fp, $ofile, $nfile);
 return($rc);
} // end rename

function delete($file)
{
 $rc=ftp_delete($this->fp, $file);
 return($rc);
} // end rename

function chg_dir($dir)
{
 if (ftp_chdir($this->fp, $dir))
 {
  $this->curdir=ftp_pwd($this->fp);
  return(true);
 } 
 else { return(false); }
}//end chg_dir

function get_list($dir)
{
 $contents = ftp_nlist($this->fp, $dir);
 return($contents);
} // end get_list

function get_daysold($filename)
{
 $fileCreationTime = ftp_mdtm($this->fp, $filename);
 $fileAge=time();
 $d=($fileAge-$fileCreationTime);
 return(round((($d / 3600) / 24),2)); // return in days
} // end get_daysold

function get_size($filename)
{
 return(ftp_size($this->fp, $filename));
} // end get_size

function close()
{
// close the connection
ftp_close($this->fp);
} // end close
} // end class WFTP
?>
