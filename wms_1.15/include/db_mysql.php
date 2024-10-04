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
 */

class DB_MySQL {
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
  var $Record   = array();
  var $Row;
  var $CurrRow;
  var $NumRows  = 0;
  var $replace_quotes = false;

  var $Auto_Free = 1;

  function connect() {
    //$dsn = "mysql:host={$this->DBHost}:{$this->DBPort};dbname={$this->DBDatabase};charset={$this->DBCharset}";
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
    /* No empty queries, please, since PHP4 chokes on them. */
    if ($Query_String == "") return 0;
    $SQL=$this->rmquotes($Query_String);

    $this->connect();

#   printf("Debug: query = %s<br>\n", $Query_String);
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
    return($numrows);
  } // end queryAll

  function query($Query_String) {
      /* No empty queries, please, since PHP4 chokes on them. */
    if ($Query_String == "") return 0;
    $SQL=$this->rmquotes($Query_String);

    $this->connect();

    $this->CurrRow=0;
    unset($this->Row);
    $this->Row=array();
    $i=0;
    foreach ($this->Link_ID->query($SQL) as $row)
    {
     foreach ($row as $key=>$data)
      {
           $this->Row[$i]["$key"]=$data;
      }
     $i++;
    }
     $numrows=$i - 1;
    $this->NumRows = $numrows;
    return($numrows);
  } // end query

  function next_record()
  {
    $i=$this->CurrRow;
    $this->CurrRow++;
    if (isset($this->Row[$i])) $this->Record=$this->Row[$i];
     else $this->Record=false;
    return($this->Record);
  } // end next_record

  function metadata($table) {
    /* Metadata required fields
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
    return($res);
  }

  function create_cursor($SQL,$params) {
    //Does not use replace_quotes, must pass substitution var array in params
    $this->NumRows=0;
    $numrows = $this->NumRows;
    $res     = array();
    $this->connect();
 try {
   $this->CurCursor = $this->Link_ID->prepare($SQL);
  }
  catch (PDOException $e) {
    print $e->getMessage();
    return(false);
  }
   $this->CurCursor->execute($params);
   return(true);
  } // end create cursor

  function curfetch()
  {
/* Exercise PDOStatement::fetch styles */
  //print("PDO::FETCH_ASSOC: ");
  //print("Return next row as an array indexed by column name\n");
   $result = $this->CurCursor->fetch(PDO::FETCH_ASSOC);
   if ($result) {
    return($result);
   } // end rowcount
   else
   {
     $this->CurCursor->closeCursor();
     return(false);
   } // end else of rowcount
  } // end curfetch

  function Update($Query_String)
  {
   $SQL=$this->rmquotes($Query_String);
   $this->Link_ID->beginTransaction();
   try
   {
    $rc = $this->Link_ID->query($SQL);
    $this->NumRows = $rc->rowCount();
    $this->Link_ID->commit();
   } // end of try
   catch (Exception $e)
   {
    $this->Link_ID->rollback();
    print_r($e);
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
   } // end catch
   return($this->NumRows);
  } // end of Update
  function oldUpdate($Query_String)
  {
   $SQL=$this->rmquotes($Query_String);
   $this->connect();
   try
   {
    //$this->NumRows = $this->Link_ID->exec($SQL);
    $rc = $this->Link_ID->query($SQL);
    $this->NumRows = $rc->rowCount();
   }
   catch (PDOException $e) {
    //print $e->getMessage();
    print_r($this->Link_ID->errorInfo());
    return(false);
   }
  return($this->NumRows);
  } // end Old Update

  function affected_rows() {
    return($this->NumRows);
  }

  function num_rows() {
    return($this->NumRows) + 1;
  }

  function rmquotes($Query_String)
  {
   //Function to allow quotes in the SQL, the PDO prepare and exec bomb
   // out if quotes are in the SQL around a string in the where clause
   $SQL=$Query_String;
   if ($this->replace_quotes and strpos($SQL,'"'))
   { // there are quotes , may have to escape single quotes if present
    $SQL=str_replace('"',"'",$SQL);
   } // there are quotes
   return($SQL);
  }

  function nf() {
    return($this->NumRows());
  }

  function f($Name) {
    if($this->Uppercase) $Name = strtoupper($Name);
    return $this->Record[$Name];
  }

  function p($Name) {
    if($this->Uppercase) $Name = strtoupper($Name);
    print $this->Record[$Name];
  }

  function free_result() {
    @$this->Query_ID->closeCursor();
    $this->Query_ID = 0;
  }

  function close() {
    if ($this->Link_ID != 0) {
      $this->Link_ID = null;
      $this->Link_ID = 0;
    }
  }

  function halt($msg) {
    $cdate=date("m/d/Y H:i:s");
    $fp=fopen("/tmp/syberr.log", "a");
    fwrite($fp,"{$cdate}|{$_SERVER["PHP_SELF"]}|{$msg}\n");
    fclose($fp);

    printf("<b>Database error:</b> %s<br>\n", $msg);
    printf("<b>MySQL Error</b><br>\n");
    die("Session halted.");
  }
}

//End DB mysql Class

//Maintain compatability with Bluejay Code
class_alias("DB_MySQL", "DB_Sybase");
?>
