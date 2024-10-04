<?php
// getUser.php - gets the name from the userId

function getUser($db,$userId,$hostId=false)
{
 //if $hostId = true, return host_user_id
 $ret="User not found";
 $host_user_id="";
 if ($userId < 1) return $ret;
 $SQL=<<<SQL
select username, first_name, last_name,host_user_id
from WEB_USERS
where user_id = {$userId}

SQL;
 $rc=$db->query($SQL);
 $numrows=$db->num_rows();
 $i=1;
 while ($i <= $numrows)
  {
   $db->next_record();
     if ($numrows)
     {
        $uname=trim($db->f("username"));
        $first=trim($db->f("first_name"));
        $last=trim($db->f("last_name"));
        $host_user_id=trim($db->f("host_user_id"));
     }
     $i++;
   } // while i < numrows
  if (isset($uname))
  { // build display name (shortest as possible)
    //$ret="{$userId} {$first} " . substr($last,0,1);
    if ($hostId and trim($host_user_id) <> "") $ret=$host_user_id;
    else $ret="{$first} " . substr($last,0,1);
  } // build display name (shortest as possible)
 return $ret;
} // end getUser
?>
