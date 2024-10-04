<?php
session_start();
require($_SESSION["wms"]["wmsConfig"]);
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/cl_Bluejay.php");

$db = new WMS_DB;
$pg = new Bluejay;

$pg->title = "View Menu Items by Priv Level";
$pg->js = "";
$pg->Display();

$fpriv = 0;
$tpriv = 0;
if (isset($_SESSION["wms"]["spriv_from"])) $fpriv = $_SESSION["wms"]["spriv_from"];
if (isset($_SESSION["wms"]["spriv_thru"])) $tpriv = $_SESSION["wms"]["spriv_thru"];

if (isset($_REQUEST["fpriv"])) $fpriv = $_REQUEST["fpriv"];
if (isset($_REQUEST["tpriv"])) $tpriv = $_REQUEST["tpriv"];

$htm = <<<HTML
<!DOCTYPE html>
<html>
<body>
<h2>View Menu Items by Priv Level</h2>

<form action="{$_SERVER["PHP_SELF"]}">
  <label class="FieldCaptionTD" for="fname">From Priv Level:</label>
  <input class="DataTD" type="text" id="fpriv" name="fpriv" value="{$fpriv}"><br>
  <label class="FieldCaptionTD" for="fname">&nbsp;Thru Priv Level:</label>
  <input class="DataTD" type="text" id="tpriv" name="tpriv" value="{$tpriv}"><br>
  <input type="submit" value="Submit">
</form> 
HTML;
echo $htm;

$SQL = <<<SQL
select
 menu_num,
 menu_line,
 menu_desc,
 menu_priv,
 menu_url
from WEB_MENU
where menu_priv >= {$fpriv} and menu_priv <= {$tpriv}
and menu_num <> 96
and menu_line <> 0
and menu_url not like '%webmenu%'
order by menu_desc,menu_num,menu_line
SQL;
//order by menu_desc,menu_num,menu_line

$items = array();

$rc = $db->query($SQL);
$numrows = $db->num_rows();
$i = 1;
while ($i <= $numrows) {
    $db->next_record();
    if ($numrows and $db->Record) {
        foreach ($db->Record as $key => $data) {
            if (!is_numeric($key)) {
                $items[$i]["$key"] = $data;
            }
        }
    }
    $i++;
} // while i < numrows
if (count($items) > 0) {
    $j = count($items);
    $htm = <<<HTML
  <table width="70%">
   <tr>
    <td valign="top" align="left" width="50%">
   <table>
    <tr>
    <th colspan="4" class="MultipadsFormHeaderFont">{$j} Menu Items Available for priv: {$fpriv} thru {$tpriv}</th>
    </tr>
    <tr>
     <th class="FieldCaptionTD">Menu#</th>
     <th class="FieldCaptionTD">Line#</th>
     <th class="FieldCaptionTD">Description</th>
     <th class="FieldCaptionTD">Priv</th>
    </tr>

HTML;
    foreach ($items as $key => $item) {
        $dd = urlencode($item["menu_desc"]);
        $r = "SYS/menu_list.php";
        $lnk = <<<HTML
<a href="../WEB_MENU.php?Redirect={$r}&mdesc={$dd}&menu_line={$item["menu_line"]}&menu_num={$item["menu_num"]}">
HTML;
        $lnk = <<<HTML
<a href="../{$item["menu_url"]}">
HTML;
        $cls = "DataTD";
        if ($item["menu_priv"] > 0) $cls = "AltDataTD";
        if ($item["menu_priv"] > 10) $cls = "Alt4DataTD";
        if ($item["menu_priv"] > 20) $cls = "Alt3DataTD";
        if ($item["menu_priv"] > 30) $cls = "Alt5DataTD";
        if ($item["menu_priv"] > 40) $cls = "Alt6DataTD";
        if ($item["menu_priv"] > 50) $cls = "Alt2DataTD";
        $htm .= <<<HTML
     <tr>
      <td class="{$cls}" align="center">{$item["menu_num"]}</td>
      <td class="{$cls}" align="center">{$item["menu_line"]}</td>
      <td class="{$cls}">&nbsp;{$lnk}{$item["menu_desc"]}</a></td>
      <td class="{$cls}" align="center">{$item["menu_priv"]}</td>
    </tr>
HTML;
    } // end foreach items
    $htm .= <<<HTML
   </table>
    </td>
    <td valign="top" align="center" width="45%">
  

HTML;
    echo $htm;
} // count > 0

$users = get_users($db, $fpriv, $tpriv);
if (count($users) > 0) {
    $j = count($users);
    $htm = <<<HTML
  <table>
    <tr>
    <th colspan="5" class="MultipadsFormHeaderFont">{$j} Users have Access to these programs</th>
    </tr>
    <tr>
     <th class="FieldCaptionTD">First Name</th>
     <th class="FieldCaptionTD">Last Name</th>
     <th class="FieldCaptionTD">Priv From</th>
     <th class="FieldCaptionTD">Priv Thru</th>
     <th class="FieldCaptionTD">Group</th>
    </tr>

HTML;
    foreach ($users as $key => $user) {
        $cls = "DataTD";
        $htm .= <<<HTML
     <tr>
      <td class="{$cls}">{$user["first_name"]}</td>
      <td class="{$cls}">{$user["last_name"]}</td>
      <td class="{$cls}" align="right">{$user["priv_from"]}</td>
      <td class="{$cls}" align="right">{$user["priv_thru"]}</td>
      <td class="{$cls}">&nbsp;{$user["group_desc"]}</a></td>
    </tr>
HTML;

    } // end foreach users
    $htm .= <<<HTML
   </table>
HTML;
    echo $htm;
} // end count users > 0
$colors = bldColorHelp();
$htm = <<<HTML
    </td>
    <td valign="top" align="center" width="5%">
 {$colors}
   </td>
  </table>
</body>
</html>

HTML;
echo $htm;
function get_users($db, $fp, $tp)
{
    $SQL = <<<SQL
select
 first_name,
 last_name,
 priv_from,
 priv_thru,
group_desc
from WEB_USERS,WEB_GROUPS
where priv_from <=  {$fp}
and priv_thru  >= {$tp}
and WEB_USERS.group_id = WEB_GROUPS.group_id
order by first_name,last_name

SQL;
    $ret = array();

    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $ret[$i]["$key"] = $data;
                }
            }
        }
        $i++;
    } // while i < numrows
    return ($ret);
} // end get_users

function bldColorHelp()
{
    $htm = <<<HTML
 <table>
  <tr>
   <td colspan="2"class="MultipadsFormHeaderFont">Colors</td>
  </tr>
 </tr>
   <td colspan="2" align="center" class="FieldCaptionTD">Priviledge</td>
  <tr>
   <td class="FieldCaptionTD">From</td>
   <td class="FieldCaptionTD">Thru</td>
 </tr>
  <tr>
   <td align="center" class="AltDataTD">1</td>
   <td align="center" class="AltDataTD">10</td>
  </tr>
  <tr>
   <td align="center" class="Alt4DataTD">11</td>
   <td align="center" class="Alt4DataTD">20</td>
  </tr>
  <tr>
   <td align="center" class="Alt3DataTD">21</td>
   <td align="center" class="Alt3DataTD">30</td>
  </tr>
  <tr>
   <td align="center" class="Alt5DataTD">31</td>
   <td align="center" class="Alt5DataTD">40</td>
  </tr>
  <tr>
   <td align="center" class="Alt6DataTD">41</td>
   <td align="center" class="Alt6DataTD">50</td>
  </tr>
  <tr>
   <td align="center" class="Alt2DataTD">51</td>
   <td align="center" class="Alt2DataTD">99</td>
  </tr>
 </table>

HTML;
    return $htm;

} // end bldColorHelp
?>
