<?php
/* cl_pwpost -- post to PartsWatch with token authorization

  Send methods;
     Ship    - send to orderShipped service
     Receive - send to orderReceived service
     Adjust  - send to Adjustment service
    token is automatic

  Gets Auth pack and base url from OPTIONS table option 9100 - 9103

  11/15/23 dse add save to WMSCOMMERR to allow retries
*/
if (get_cfg_var('wmsdir'))
    $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);

$wmsInclude = "{$wmsDir}/include"; // main include for this system
require_once("{$wmsInclude}/db_main.php");

class TPOST
{
    public $token = "";
    public $url;
    public $httpStatus;
    public $logfile = "/tmp/pwpost.log"; // wont log if = ""
    public $auth; // array of apiKey, parterId and systemId

    public function __construct()
    {
        $url = "";
        $auth = array(
            "apiKey" => "",
            "partnerId" => "",
            "systemId" => ""
        );

        $db = new WMS_DB;
        $SQL = <<<SQL
  SELECT cop_option,cop_flag
FROM COPTIONS
WHERE cop_company = 0
  AND cop_option between 9100 and 9103

SQL;
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows) {
                $opt = $db->f("cop_option");
                switch ($opt) {
                    case 9100:
                        $this->url = trim($db->f("cop_flag"));
                        break;
                    case 9101:
                        $auth["apiKey"] = trim($db->f("cop_flag"));
                        break;
                    case 9102:
                        $auth["partnerId"] = trim($db->f("cop_flag"));
                        break;
                    case 9103:
                        $auth["systemId"] = trim($db->f("cop_flag"));
                        break;
                }
            }
            $i++;
        } // while i < numrows
        unset($db);
        $ok = true;
        if (!isset($auth["apiKey"]))
            $ok = false;
        if (isset($auth["apiKey"]) and $auth["apiKey"] == "")
            $ok = false;
        if (!isset($auth["partnerId"]))
            $ok = false;
        if (isset($auth["partnerId"]) and $auth["partnerId"] == "")
            $ok = false;
        if (!isset($auth["systemId"]))
            $ok = false;
        if (isset($auth["systemId"]) and $auth["systemId"] == "")
            $ok = false;

        if ($ok and $this->url <> "") {
            $this->auth = $auth;
        } else {
            echo "Error: System Options (9100-9103) are not configured to Communicate to Host via Service";
            exit;
        }
    } // end contruct

    public function Send($type, $post, $errId = 0)
    {
        $this->httpStatus = 0;
        // if errId  > 0, means this is a retry, update the errId record,
        // else add new record
        $url = "";
        // setup url to partswatch service names
        if ($type == "Ship")
            $url = "{$this->url}/orderShipped";
        if ($type == "Receive")
            $url = "{$this->url}/orderReceived";
        if ($type == "Adjust")
            $url = "{$this->url}/inventoryAdjustment";
        if ($type == "Error")
            $url = "{$this->url}/errorNotification";
        $auth = $this->auth;

        // post is an array of fields to post
        $data = json_encode($post); // Encode the data array into a JSON string
        $token = $this->getToken();
        if ($token === false)
            return false;
        $ch = curl_init($url); // Initialise cURL
        $authorization = "Authorization: Bearer {$token}"; // Prepare the authorisation token

        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0
        );
        $rc = curl_setopt_array($ch, $options);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); // Inject the token into the header
        $this->wr_log("posting to: {$url}");
        $this->wr_log("data : {$post}");
        $result = curl_exec($ch); // Execute the cURL statement
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->httpStatus = $statusCode;
        $this->wr_log("result : {$result}");
        $this->wr_log("status : {$statusCode}");
        // check for error if so, put error, url, type and post data in log or table
        // exit for now
        // check statusCode, if error, log to WMSCOMMERR
        if (intval($statusCode) <> 200 and intval($statusCode) > 0) {
            $db = new WMS_DB;
            if ($errId > 0) // arg passed in for retry
            { // update existing wms Comm Error
                $rData = trim(addslashes($result));
                $SQL = <<<SQL
update WMSCOMMERR
set statusCode={$statusCode},
    retryTimes= retryTimes + 1,
    lastRetry=NOW(),
    response="{$rData}"
where errId = {$errId}

SQL;
                $rc = $db->Update($SQL);
            } // update existing wms Comm Error
            else { // Add New wms Comm Error
                $sData = trim(addslashes($post));
                $rData = trim(addslashes($result));

                $SQL = 'insert into WMSCOMMERR (utcDate, recordType, statusCode, retryTimes, lastRetry, payload, response)
                                    VALUES (NOW(),"{$type}",{$statusCode},0,null,"{$sData}","{$rData}")';
                $rc = $db->Update($SQL);
            } // Add New wms Comm Error
        } // end statusCode <> 200

        curl_close($ch); // Close the cURL connection
        return json_decode($result, true); // Return the received data
    } // end token_sspost

    private function getToken()
    {
        $turl = "{$this->url}/token";
        // auth is an array of apiKey, partnerId, systemId from config
        $auth = $this->auth;
        if (is_array($auth))
            $post = json_encode($auth);
        else
            return false;
        $ch = curl_init($turl);
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
        );
        $rc = curl_setopt_array($ch, $options);
        //$this->wr_log("posting to: {$turl}");
        //$this->wr_log("Data\n{$post}");
        $response = curl_exec($ch);
        //$this->wr_log("Result\n{$response}");

        curl_close($ch);
        $result = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // JSON is valid
            if (isset($result["token"])) {
                $this->token = $result["token"];
                return $result["token"];
            } else {
                $this->wr_log("POST\n{$post}");
                $r = json_encode($result);
                $this->wr_log("Result\n{$r}");
                echo "Error: Failed to get Token";
                exit;
            }
        } else
            return false;
    } // end getToken

    function wr_log($logentry)
    {
        if ($this->logfile == "")
            return;
        $cdate = date("m/d/Y H:i:s");
        $fp = fopen("$this->logfile", "a");
        fwrite($fp, "{$_SERVER["SCRIPT_FILENAME"]}|{$cdate}|");
        fwrite($fp, "$logentry\n");
        fclose($fp);
        return;
    }

} // end class TPOST
?>