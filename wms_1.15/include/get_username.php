<?php

function get_username()
{
  $username=trim($_SESSION["wms"]["first_name"]) . " " . trim($_SESSION["wms"]["last_name"]); 
 return($username);
} // end get_username
?>
