<?php
if (isset($_REQUEST["msg"])) $msg=$_REQUEST["msg"]; else $msg="";
if (isset($_REQUEST["ret_link"])) $ret_link=$_REQUEST["ret_link"]; else $ret_link="";
//error_reporting(0);
if (get_cfg_var('wmsdir')) $wmsDir=get_cfg_var('wmsdir');
else { echo "<h1>WMS System is not Configured on this System</h1>"; exit; }
$top=str_replace("/var/www","",$wmsDir);

$setConfig=true;
require("{$wmsDir}/config.php");
unset($setConfig);

$logo=$wmsSysLogo;

$sysDir=$wmsDir;

$sysName=$wmsSystem;
$sysLogo=$wmsLogo;
chdir($sysDir);

if ($msg <> "") $m=<<<HTML
<br><span style="color:red;text-align: center;">{$msg}</span>

HTML;
else $m="";

if (strpos($ret_link,"newlogin.php") !== false) $ret_link="";

$htm=<<<HTML
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body {font-family: Arial, Helvetica, sans-serif;}

/* Full-width input fields */
input[type=text], input[type=password] {
    width: 100%;
    padding: 12px 20px;
    margin: 8px 0;
    display: inline-block;
    border: 1px solid #ccc;
    box-sizing: border-box;
}

/* Set a style for all buttons */
button {
    background-color: #809fff;
    color: white;
    padding: 14px 20px;
    margin: 8px 0;
    border: none;
    cursor: pointer;
    width: 100%;
}

button:hover {
    opacity: 0.8;
}


/* Center the image and position the close button */
.imgcontainer {
    text-align: center;
    margin: 24px 0 12px 0;
    position: relative;
}

.container {
    padding: 16px;
}

span.psw {
    float: right;
    padding-top: 16px;
    display: none;  /* hidden until I'm ready for it */
}

/* The Modal (background) */
.modal {
    display: block;
    position: fixed; /* Stay in place */
    z-index: 1; /* Sit on top */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    overflow: auto; /* Enable scroll if needed */
    background-color: rgb(0,0,0); /* Fallback color */
    background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
    padding-top: 20px;
}

/* Modal Content/Box */
.modal-content {
    background-color: #fefefe;
    margin: 5% auto 15% auto; /* 5% from the top, 15% from the bottom and centered */
    border: 1px solid #888;
    width: 80%; /* Could be more or less, depending on screen size */
}

/* The Close Button (x) */
.close {
    position: absolute;
    right: 25px;
    top: 0;
    color: #000;
    font-size: 35px;
    font-weight: bold;
}

.close:hover,
.close:focus {
    color: red;
    cursor: pointer;
}

/* Add Zoom Animation */
.animate {
    -webkit-animation: animatezoom 0.6s;
    animation: animatezoom 0.6s
}

@-webkit-keyframes animatezoom {
    from {-webkit-transform: scale(0)}
    to {-webkit-transform: scale(1)}
}

@keyframes animatezoom {
    from {transform: scale(0)}
    to {transform: scale(1)}
}

}
</style>
</head>
<body>


<div id="id01" class="modal">
  <form class="modal-content animate" name="Login" action="newlogin.php?ccsForm=Login" method="post">
   <input type="hidden" name="ret_link" id="ret_link" value="{$ret_link}">
   <input type="hidden" name="msg" id="msg" value="">
    <div class="imgcontainer">
      <img src="{$sysLogo}" alt="Avatar" class="avatar">
  {$m}
<h2 valign="top">
      <img id="Image1" src="{$logo}" alt="{$sysName}" name="Image1">
 Login
</h2>
    </div>

    <div class="container">
      <label for="uname"><b>Username</b></label>
      <input type="text" placeholder="Enter Username" name="login" required>

      <label for="psw"><b>Password</b></label>
      <input type="password" placeholder="Enter Password" name="password" required>

      <button type="submit">Login</button>
      <label>
        <input type="checkbox" checked="checked" name="autoLogin"> Remember me
      </label>
    </div>

    <div class="container" style="background-color:#f1f1f1">
      <span id="forgotp" class="psw">Forgot <a href="#">password?</a></span>
    </div>
  </form>
</div>

</body>
</html>
HTML;
echo $htm;
?>
