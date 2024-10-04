<?php
//11/11/2021 dse changed to divs with responsive display
// 08/02/24 dse change prototype.js to jquery

include_once("include/db_main.php");

function get_menu($menu_num, $pfrom, $pthru = 0, $mtype = 1, $group_id = 0)
{
    if (!is_numeric($pfrom)) {
        $htm = <<<HTML
<script>
window.location.href="Login.php";
</script>
HTML;
        return ($htm);
    }
    $menu = $menu_num;
    if ($menu < 1) {
        $menu = 20;
    }
    ;
    $setmtype_link = "umenutype.php?m=$menu&f=$pfrom&t={$pthru}&ot={$mtype}";
    if (!$mtype)
        $mtype = 0;

    if ($mtype > 0)
        $display = disp_menu($menu, $pfrom, $pthru);
    else
        $display = disp_menu1($menu, $pfrom, $pthru);

    // <script src="/jq/prototype.js" type="text/javascript"></script>
    $search_js = <<<HTML
<link href="/wms/assets/css/menu.css" type="text/css" rel="stylesheet">
<script src="/jq/jquery-1.12.4.js" type="text/javascript"></script>
<script language="javascript" type="text/javascript">

function getHTMLSuccess(originalRequest) {
        var response = originalRequest.responseText;
        $('htmlResult').innerHTML = response;
}
function getHTMLFailure() {
        alert('woops, what happened?');
}
function check_param() {
 var s_sort = document.getElementById('s_sort').value;
if (s_sort) { getHTML(); }
} 
function getHTML() {
 var s_sort = document.searchform.s_sort.value;
 var stype = document.searchform.stype.value;
 var pfrom = document.searchform.pfrom.value;
 var pthru = document.searchform.pthru.value;

 var queryString = "servers/msrch.php?s_sort=" + s_sort + "&stype=" + stype + "&pfrom=" + pfrom + "&pthru=" + pthru;
//alert(queryString);
//if (!s_sort) { return(false); }
        //new Ajax.Request(queryString,   {
                //method:'get',
                //onSuccess: getHTMLSuccess,
                //onFailure: getHTMLFailure
        //});
        $.get(queryString).then(res=>$('htmlResult').innerHTML=res.responseText);
 $('#htmlResult').load(queryString);
}
function toggle_soptions()
{
 var o=document.getElementById('dropd');
 var d=document.getElementById('stype');
 if (o.style.display == "none")
 {
   o.style.display="block";
   d.size="3";
   d.focus();
 }
 else o.style.display="none";
}

$(function(){
    var s_sort = $('#s_sort').value;
 var stype = $('#stype').value;
 var pfrom = $('[name="pfrom"]').val();
 var pthru = $('[name="pthru"]').val();


 var queryString = "servers/msrch.php?s_sort=" + s_sort + "&stype=" + stype + "&pfrom=" + pfrom + "&pthru=" + pthru;

    $('#stype,#s_sort').on('input',function(){
        console.log(queryString);        
        $.get(queryString).then(res=>$('htmlResult').innerHTML=res.responseText);
    })
})

</script>
HTML;

    $search_form = <<<HTML
<form name="searchform" id="searchform">
<input type="hidden" name="pfrom" value="{$pfrom}">
<input type="hidden" name="pthru" value="{$pthru}">
<table>
 <tr>
  <td>
 <tr>
  <td valign="top" width="17%"> 
   <div id="mag">
    <img onclick="toggle_soptions();" src="images/filtersearch.png" width="16px" height="16px" border="0"/>
</td>
  <td width="50%"> 
    <div id="dropd" style="display:none;" onchange="toggle_soptions();"> 
<br>
     <select id="stype" size="30" style="overflow-y: auto;" name="stype" onChange="getHTML();">
      <option value="f">Begins With&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>
      <option value="a" selected>Is Anywhere&nbsp;&nbsp;&nbsp;&nbsp;</option>
     </select>
    </div>
   </div>
     <input type="text" id="s_sort" name="s_sort" size="30" value="" placeholder="Search..." autocomplete="off" onkeyup="getHTML();">
  </td>
 </tr>
<tr>
<td>&nbsp;</td>
<td>
<div id="htmlResult">
	</div>
</td>
 </tr>
</table>
</form>
HTML;

    if ($group_id < 1) {
        $search_js = "";
        $search_form = "&nbsp;";
    }

    $htm = <<<HTML
{$search_js}

  <div class="menu-one">
   {$search_form}
  </div>
  <div class="menu-two">
   {$display}
  </div>
  <div class="hidden-mobile menu-three" style="display:none;">
   <table>
    <tr>
     <td><a href="{$setmtype_link}&nt=1"><img border="0" src="images/grid.png" title="Display menu as Grid"></a></td>
     <td><a href="{$setmtype_link}&nt=0"><img border="0" src="images/list.png" title="Display menu as List"></a></td>
    </tr>
   </table>
  </div>
</body>
</html>
HTML;

    // For radio buttons on search, repmove select control and add the following
// before the search box
    /*
      <td>
       <table>
       <tr>
       <td><input type="radio" value="f" name="stype" onchange="getHTML();">Begins with</td>
       <td><input type="radio" value="a" name="stype" onchange="getHTML();" checked>Is Anywhere</td>
      </tr>
      </table>
      </td>
      </tr>
    */
    // end select

    // If you want grid/lost selection, put this before /body

    //<div class="hidden-mobile menu-three">
    //<table>
    //<tr>
    //<td><a href="{$setmtype_link}&nt=1"><img border="0" src="images/grid.png" title="Display menu as Grid"></a></td>
    //<td><a href="{$setmtype_link}&nt=0"><img border="0" src="images/list.png" title="Display menu as List"></a></td>
    //</tr>
    //</table>
    //</div>

    return $htm;
} // end function get_menu

function disp_menu($menu_number, $pfrom = 0, $pthru = 0)
{
    $db = new WMS_DB;
    $browser = $_SERVER["HTTP_USER_AGENT"];
    $type = "Desktop";
    $j = strpos($browser, "iPad");
    if ($j) {
        $type = "iPad";
    }
    if (!$j) {
        $j = strpos($browser, "Android");
        if ($j) {
            $type = "Android";
        }
    }
    //echo $type . " Edition\n";
    $tw = "40%";
    $j = 1;
    if ($j) {
        $tw = "40%";
    }

    $colors = array(
        0 => "menu-amber",
        1 => "menu-aqua",
        2 => "menu-blue",
        3 => "menu-light-blue",
        4 => "menu-brown",
        5 => "menu-cyan",
        6 => "menu-blue-grey",
        7 => "menu-green",
        8 => "menu-light-green",
        9 => "menu-indigo",
        10 => "menu-khaki",
        11 => "menu-lime",
        12 => "menu-orange",
        13 => "menu-deep-orange",
        14 => "menu-pink",
        15 => "menu-purple",
        16 => "menu-deep-purple",
        17 => "menu-red",
        18 => "menu-sand",
        19 => "menu-teal",
        20 => "menu-yellow"
    );


    $colidx = 0;
    $menutable = "";
    $menutable .= <<<HTML
<div class="container width="{$tw}%">

HTML;
    //$menutable="<table align=\"left\" width=\"$tw\" border=\"0\">\n";
    //$menutable .= "<tr>\n";
    $md = 0;
    $SQL = "SELECT menu_line,menu_image,menu_desc,menu_url,menu_target FROM WEB_MENU WHERE menu_num=" . $menu_number;
    $SQL .= " and menu_priv >=  $pfrom";
    $SQL .= " and menu_priv <= $pthru";
    $SQL .= " and menu_line > 0";
    $SQL .= " order by menu_line";
    $db->query($SQL);
    $i = 1;
    $rc = $db->next_record();
    if ($rc) {
        do {
            //width=\"50\" height=\"40\"
            if ($db->f("menu_line") == 0) {
                $menutable .= "</tr><tr><td width=\"100%\" colspan=\"5\" align=\"center\">";
                $menutable .= "<h2>" . $db->f("menu_desc") . "</h2></td></tr><tr>\n";
            } else {
                $target = "";
                $tg = $db->f("menu_target");
                if ($tg > " " && $tg <> "NULL") {
                    $target = " target=\"$tg\"";
                }
                $md++;
                if ($md > 4 or ($type <> "Desktop" and $md > 3)) {
                    $md = 1;
                    //$menutable .= "</tr>\n<tr>\n";
                }

                $href = "<a href=\"" . $db->f("menu_url") . " \" target=\"$target\"" . ">";
                $image = "<img id=\"Image1[]\"  tabindex=\"0\"  alt=\"\" src=\"";
                $image .= $db->f("menu_image") . "\" width=\"32px\" height=\"32px\"  border=\"0\" name=\"Image1[]\">";
                $desc = $db->f("menu_desc");

                $menutable .= <<<HTML
 <div class="menu-quarter {$colors[$colidx]}" nowrap height="150" tabindex="{$i}" align="center">{$href}{$image}<br>{$desc}</a></div>

HTML;
                $colidx++;
                if ($colidx > 20)
                    $colidx = 0;
                //$menutable .="</a></td>\n";
            } // end else
            $i++;
            $rc = $db->next_record();
        } while ($rc);
        //$menutable .= "</tr>\n";
    }

    //$menutable .= "<tr>\n";
    //$menutable .= "</table>";
    unset($db);
    return ($menutable);
}


function disp_menu1($menu_number, $pfrom = 0, $pthru = 0)
{
    $db = new WMS_DB;


    $menutable = "<table align=\"left\" width=\"40%\" border=\"0\">\n";
    $SQL = "SELECT menu_line,menu_image,menu_desc,menu_url,menu_target FROM WEB_MENU WHERE menu_num=" . $menu_number;
    $SQL .= " and menu_priv >=  $pfrom";
    $SQL .= " and menu_priv <= $pthru";
    $SQL .= " and menu_line > 0";
    $SQL .= " order by menu_line";
    $db->query($SQL);
    $rc = $db->next_record();
    if ($rc) {
        do {
            $menutable .= "<tr>\n";
            //width=\"50\" height=\"40\"
            if ($db->f("menu_line") == 0) {
                $menutable .= "<td width=\"5%\">&nbsp;</td>";
                $menutable .= "<td>" . $db->f("menu_desc") . "</td>\n";
            } else {
                $target = "";
                $tg = $db->f("menu_target");
                if ($tg > " " && $tg <> "NULL") {
                    $target = " target=\"$tg\"";
                }
                $menutable .= "<td width=\"5%\"> <a href=\"" . $db->f("menu_url") . "\">" . "<img id=\"Image1[]\"  tabindex=\"0\"  alt=\"\" src=\"" . $db->f("menu_image") . "\"  border=\"0\" name=\"Image1[]\"></td>";
                $menutable .= "<td nowrap ><a href=\"" . $db->f("menu_url") . "\"$target>" . $db->f("menu_desc") . "</a></td>\n";
            } // end else
            $menutable .= "</tr>\n";
            $rc = $db->next_record();
        } while ($rc);
    }

    $menutable .= "</table>";
    unset($db);
    return ($menutable);
}

?>