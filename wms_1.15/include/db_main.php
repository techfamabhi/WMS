<?php

//DB MySQL PDO Class @0-5E30A537
/*
 * db_sybpdo.php
 * 03/17/20 dse initial
 *
 * PDO Class to allow php 4 style querys and updates in PDO.
 * if compatability is needed, set $replace_quotes to true.
 * This will allow quotes in the SQL instead of the prepared statements
 * used in PDO.
 *
 * 09/25/20 dse Make query and next_record compatible with old db_MySQL
 * 12/15/21 dse Add transactions to Update
 * 02/16/22 dse add start,upd and end Trans functions
 * 02/21/22 dse add execStored 
 * 03/11/22 dse add gData which runs query2Array
 * 03/31/23 dse add DBDBG

 * to debug, set DBDBG to the path/filename to log SQL history
 */

class WMS_DB {
  var $DBType     = "MYSQL";
  var $DBHost     = "localhost";
  var $DBPort     = "3306";
  var $DBDatabase = "wms";
  var $DBUser     = "wms";
  var $DBPassword = "zzz001";
  var $DBCharset  = "utf8mb4";

  var $DBOptions = array();
  var $CurCursor;
  var $DSN = "";
  var $Persistent = false;
  var $Uppercase  = false;

  var $Link_ID  = 0;
  var $inTransaction  = false;
  var $useTransactions  = false;
  var $Query_ID = 0;
  var $lastQuery = "";
  var $Record   = array();
  var $Row;
  var $CurrRow;
  var $DBDBG="";
  var $NumRows  = 0;
  var $replace_quotes = false;

  var $Auto_Free = 1;

  function connect() {
    //Connect to Database
    $dsn = "mysql:host={$this->DBHost};dbname={$this->DBDatabase};charset={$this->DBCharset}";
    $options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_FOUND_ROWS => true,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

    try {
     $this->Link_ID = new PDO($dsn, $this->DBUser,$this->DBPassword, $options);
   } catch (\PDOException $e) {
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
   }
  }

  function queryAll($Query_String) {
   //Runs the query passed in and loads the results in the $db->Record array

    /* No empty queries, please, since PHP4 chokes on them. */
    if ($Query_String == "") return 0;
    $SQL=$this->rmquotes($Query_String);
    if ($this->DBDBG <> "") $this->debugLog($SQL);

    $this->connect();

#   printf("Debug: query = %s<br>\n", $Query_String);
    $numrows=0;
    $i=1;
    foreach ($this->Link_ID->query($SQL) as $row)
    {
    foreach ($row as $key=>$data)
          {
           $this->Record[$i]["$key"]=$data;
          }
    $numrows=$i;
    $i++;
    }
    $this->NumRows = $numrows;
    return $numrows ;
  } // end queryAll

  function query($Query_String) {
  //runs the query passed in and loads the results into $db->Row, 
  // use next_record() to move to next record

      /* No empty queries, please, since PHP4 chokes on them. */
    if ($Query_String == "") return 0;
    $SQL=$this->rmquotes($Query_String);
    $this->lastQuery=$SQL;
    if ($this->DBDBG <> "") $this->debugLog($SQL);

    $this->connect();

    $this->CurrRow=0;
    unset($this->Row);
    $this->Row=array();
    $i=0;
    try
    {
    foreach ($this->Link_ID->query($SQL) as $row)
    {
     foreach ($row as $key=>$data)
      {
           $this->Row[$i]["$key"]=$data;
      }
     $i++;
    }
    } // end try
   catch (PDOException $e) 
   {
        //error
      $this->halt($e->getMessage(),$SQL);
    }
     $numrows=$i - 1;
    $this->NumRows = $numrows;
    return $numrows ;
  } // end query

  function gData($Query_String) {
  // gets the data resulting from the query passed in and returns an array of the results
   return $this->query2Array($Query_String);
  } // end gData

  function query2Array($Query_String) {
  // gets the data resulting from the query passed in 
  // and returns an array of the results with an extra field numRows
   
    /* No empty queries, please, since PHP4 chokes on them. */
    if ($Query_String == "") return 0;
    $SQL=$this->rmquotes($Query_String);
    if ($this->DBDBG <> "") $this->debugLog($SQL);

    $this->connect();
    $ret=array();
    $numrows=0;
    $i=1;
    foreach ($this->Link_ID->query($SQL) as $row)
    {
    foreach ($row as $key=>$data)
          {
           $ret[$i]["$key"]=$data;
          }
    $numrows=$i;
    $i++;
    }
    $this->NumRows = $numrows;
    return $ret ;
  } // end query2Array

  function execStored($proc,$params) {
    if ($proc == "") return 0;
    /*
     Runs a stored procedure and returns the results

     params = array ( arg# (starting at 0)=>array(value, type ("NULL|INT|LOB|STR","IO"=>true)))
     PDO::PARAM_NULL Represents the SQL NULL data type.
     PDO::PARAM_INT Represents SQL integer types.
     PDO::PARAM_LOB Represents SQL large object types.
     PDO::PARAM_STR Represents SQL character data types.
     if IO is true, the param is INOUT, otherwise the param in IN only
    */
    //count params for prepare statement
    $j=count($params);
    if ($j == 0) $SQL="CALL {$proc}()";
    else
    { // there are params
      $SQL="CALL {$proc}(";
      $comma="";
      foreach ($params as $key=>$p)
      {
       $SQL.="{$comma} ?";
       $comma=",";
      } //end of foreach param
      $SQL.=")"; 
    } // end of there are params

    if ($this->DBDBG <> "") $this->debugLog($SQL);
    $this->connect();
    $storedProc = $this->Link_ID->prepare($SQL);
    $j1=1;
    foreach ($params as $key=>$p)
     {
      switch ($p["type"])
       {
        case "NULL":
         if (isset($p["IO"]) and $p["IO"] == true)
         $storedProc->bindValue($j1,$p["value"], PDO::PARAM_NULL|PDO::PARAM_INPUT_OUTPUT);
         else $storedProc->bindValue($j1,$p["value"], PDO::PARAM_NULL);
         break;
        case "INT":
         if (isset($p["IO"]) and $p["IO"] == true)
         $storedProc->bindValue($j1,$p["value"], PDO::PARAM_INT|PDO::PARAM_INPUT_OUTPUT);
         else $storedProc->bindValue($j1,$p["value"], PDO::PARAM_INT);
         break;
        case "LOB":
         if (isset($p["IO"]) and $p["IO"] == true)
         $storedProc->bindValue($j1,$p["value"], PDO::PARAM_LOB|PDO::PARAM_INPUT_OUTPUT);
         else $storedProc->bindValue($j1,$p["value"], PDO::PARAM_LOB);
         break;
        case "STR":
         if (isset($p["IO"]) and $p["IO"] == true)
         $storedProc->bindValue($j1,$p["value"], PDO::PARAM_STR|PDO::PARAM_INPUT_OUTPUT);
         else $storedProc->bindValue($j1,$p["value"], PDO::PARAM_STR);
         break;
       } // end switch type
      $j1++;
     } //end of foreach param #2
    $rc = $storedProc->execute();
    $result = $storedProc->fetchAll(PDO::FETCH_ASSOC);
    if (isset($result[0]["rc"])) $rc=$result[0]["rc"];
    return $rc ;
  } // end execStored

  function next_record()
  {
    // move to next record in result set
    $i=$this->CurrRow;
    $this->CurrRow++;
    if (isset($this->Row[$i])) $this->Record=$this->Row[$i];
     else $this->Record=false;
    return $this->Record ;
  } // end next_record

  // function to return current date and time in the database required format
  function dbDate($in="") 
  {
   // takes a mm/dd/YYYY date and returns the MYSQL style date the DB requires
   switch($in)
   {
    case "D": // return date only
     return date("Y/m/d") ;
     break;
    case "T": // return time only
     return date("H:i:s") ;
     break;
    default: // return data and time
     return date("Y/m/d H:i:s") ;
     break;
   } // end switch in 
  } // end dbDate

  function metadata($table) {
    /*  returns column information for a table
     Metadata required fields
        column_name, type_name, length, ordinal_position, remarks
    now      Field       | Type         | Null | Key | Default | Extra
    */
    $reqflds=array("Field",
                   "Type",
                   "Null",
                   "Key",
                   "Default",
                   "Extra");
    $numrows = 0;
    $res     = array();
    $this->connect();
    $i=1;
    foreach ($this->Link_ID->query("show columns from $table") as $row)
    {
      $res[$i]["table"]    = $table;
      foreach ($row as $key=>$data)
          {
           if (in_array($key,$reqflds) and !is_numeric($key)) { $res[$i]["$key"]=$data; }
          }
     $numrows=$i;
     $i++;
    }
    if ($numrows < 0) {
      $this->Errno = 1;
      $this->Error = "Metadata query failed";
      $this->halt("Metadata query failed.");
    }
    return $res ;
  }

  function create_cursor($SQL,$params) {
   //Create cursor

    //Does not use replace_quotes, must pass substitution var array in params
    $this->NumRows=0;
    $numrows = $this->NumRows;
    $res     = array();
    if ($this->DBDBG <> "") $this->debugLog($SQL);
    $this->connect();
 try {
   $this->CurCursor = $this->Link_ID->prepare($SQL);
  }
  catch (PDOException $e) {
    print $e->getMessage();
    return false ;
  }
   $this->CurCursor->execute($params);
   return true ;
  } // end create cursor

  function curfetch()
  {
   // Fetch next record for current cursor, closes cursor if no more rows
   // Returns next row as an array indexed by column name

   $result = $this->CurCursor->fetch(PDO::FETCH_ASSOC);
   if ($result) {
    return $result ;
   } // end rowcount
   else
   {
     $this->CurCursor->closeCursor();
     return false ;
   } // end else of rowcount
  } // end curfetch

  function Trans($typ)
  {
   // begin or end a transaction
   $this->connect();
   switch($typ)
   {
    case "B": if ($this->DBDBG <> "") $this->debugLog("Begin Transaction");
              $this->Link_ID->beginTransaction();
              break;
    case "C": if ($this->DBDBG <> "") $this->debugLog("Commit Transaction");
	      $this->Link_ID->commit();
              break;
    case "R": if ($this->DBDBG <> "") $this->debugLog("Rollback Transaction");
              $msg=$e->getMessage(). "Code:" . $e->getCode();
              if ($this->DBDBG <> "") $this->debugLog($msg);
	      $this->Link_ID->rollback();
              break;
   }
  } // end Trans

  function startTrans() //Start a transaction
  {
   //starts a transaction
   $this->connect();
   if ($this->DBDBG <> "") $this->debugLog("Rollback Transaction");
   $rc=$this->Link_ID->beginTransaction();
   return $rc ;
  } // end startTrans

  function updTrans($QueryString) // update, once startTrans is called
  {
   // update within a transaction
   $SQL=$this->rmquotes($QueryString);
   if ($this->DBDBG <> "") $this->debugLog($SQL);
   $rc = $this->Link_ID->exec($SQL);
   if ($rc === false )
   {
    $err=$this->Link_ID->errorInfo();
    if ($err[0] === '00000' or $err[0] === '01000') { return 1 ; }
   }
   return $rc ;
  } // end updTrans

  function endTrans($rc) // end trans, once startTrans and updTrans is called
  {
   // end the transaction
   // rc = record count of affected rows
   if ($this->DBDBG <> "") $this->debugLog("Commit Transaction");
   if ($rc > 0) $rc1=$this->Link_ID->commit();
     else       
     {
      if ($this->DBDBG <> "") $this->debugLog("Rollback Transaction");
      $e = new Exception;
      $msg=$e->getMessage(). "Code:" . $e->getCode();
      if ($this->DBDBG <> "") $this->debugLog($msg);
      $rc1=$this->Link_ID->rollback();
      $rc1=0; // return 0 for transaction failed
     }
   return $rc1 ;
  }

  function Update($Query_String)
  {
   // connects, begins a transaction, runs the update, then ends the transaction
   $this->connect();
   $SQL=$this->rmquotes($Query_String);
   if ($this->DBDBG <> "") $this->debugLog("Begin Transaction");
   $this->Link_ID->beginTransaction();
   try
   {
    //$rc = $this->Link_ID->query($SQL);
    //$this->NumRows = $rc->rowCount();
    if ($this->DBDBG <> "") $this->debugLog($SQL);
    $rc = $this->Link_ID->exec($SQL);
    $this->NumRows = $rc;
    if ($this->DBDBG <> "") $this->debugLog("Commit Transaction");
    $this->Link_ID->commit();
   } // end of try
   catch (Exception $e)
   {
     if ($this->DBDBG <> "") $this->debugLog("RollBack Transaction");
     $msg=$e->getMessage(). "Code:" . $e->getCode();
     if ($this->DBDBG <> "") $this->debugLog($msg);
    $this->Link_ID->rollback();
    print_r($e);
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
   } // end catch
   return $this->NumRows ;
  } // end of Update
  function oldUpdate($Query_String)
  {
   //no longer used
   $SQL=$this->rmquotes($Query_String);
   $this->connect();
   try
   {
    //$this->NumRows = $this->Link_ID->exec($SQL);
    if ($this->DBDBG <> "") $this->debugLog($SQL);
    $rc = $this->Link_ID->query($SQL);
    $this->NumRows = $rc->rowCount();
   }
   catch (PDOException $e) {
    //print $e->getMessage();
    print_r($this->Link_ID->errorInfo());
    return false ;
   }
  return $this->NumRows ;
  } // end Old Update

  function affected_rows() {
   // returns the number of rows affected by the last update
    return $this->NumRows ;
  }

  function num_rows() {
   // returns the number of rows affected by the last query
    return $this->NumRows  + 1;
  }

  function rmquotes($Query_String)
  {
   // replaces quotes with single quotes

   //Function to allow quotes in the SQL, the PDO prepare and exec bomb
   // out if quotes are in the SQL around a string in the where clause
   $SQL=$Query_String;
   if ($this->replace_quotes and strpos($SQL,'"'))
   { // there are quotes , may have to escape single quotes if present
    $SQL=str_replace('"',"'",$SQL);
   } // there are quotes
   return $SQL ;
  }

  function nf() {
   //altername for NumRows
    return $this->NumRows();
  }

  function f($Name) {
   // returns the value of the fieldName from the last query
    if($this->Uppercase) $Name = strtoupper($Name);
    return $this->Record[$Name];
  }

  function p($Name) {
   // prints the value of the fieldName from the last query
    if($this->Uppercase) $Name = strtoupper($Name);
    print $this->Record[$Name];
  }

  function free_result() {
   // closes a cursor
    @$this->Query_ID->closeCursor();
    $this->Query_ID = 0;
  }

  function close() {
   // closes the connextion to the database
    if ($this->Link_ID != 0) {
      $this->Link_ID = null;
      $this->Link_ID = 0;
    }
  }

  function halt($msg,$SQL="") {
   // logs a message and stops any further program execution
    $cdate=date("m/d/Y H:i:s");
    $fp=fopen("/tmp/mysql.log", "a");
    fwrite($fp,"{$cdate}|{$_SERVER["PHP_SELF"]}|{$msg}\n{$SQL}\n");
    fclose($fp);

    printf("<pre><b>Database error:</b> %s<br>\n", $msg);
    if ($SQL <> "") printf("<b>SQL:</b>\n %s<br>\n", $SQL);
    printf("<b>MySQL Error</b><br>\n");
    die("Session halted.");
  }
 function debugLog($logentry)
 {
  if ($this->DBDBG <> "")
  {
   $cdate=date("m/d/Y H:i:s");
   $fp=fopen($this->DBDBG, "a");
   fwrite($fp,"{$_SERVER["SCRIPT_NAME"]}|{$cdate}|");
   fwrite($fp,"$logentry\n");
   fclose($fp);
  } // end DBDBG is set
 } // end debugLog
} //End DB mysql Class

//Maintain compatability with Bluejay Code
class_alias("WMS_DB", "DB_Sybase"); // temp, may be some test programs still
class_alias("WMS_DB", "DB_MySQL");
?>
