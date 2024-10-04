<?php
use Vtiful\Kernel\Format;
require('config.php');

// if (!isset($_SESSION["wms"])) {
//     header("Location: ./Login.php");
//     die();
// }
require_once("./include/db_main.php");


class ApiService
{

    private $conn;
    private $params;
    public function __construct()
    {
        $wmsDB = new WMS_DB;
        $this->params = [];

        $this->conn = mysqli_connect($wmsDB->DBHost, $wmsDB->DBUser, $wmsDB->DBPassword, $wmsDB->DBDatabase);

        if (!$this->conn) {
            die('Connection failed: ' . mysqli_connect_error());
        }
    }


    private function executeQuery($conn, $query)
    {
        $result = mysqli_query($conn, $query);
        $data = [];
        while ($row = mysqli_fetch_assoc($result))
            $data[] = $row;

        return $data;
    }

    public function getAllOrders($startDate = null, $endDate = null)
    {
        $startDate ??= date_sub(new DateTime(), date_interval_create_from_date_string('15 Days'));

        $startDate = mysqli_real_escape_string($this->conn, $startDate);
        $endDate = mysqli_real_escape_string($this->conn, $endDate);

        $query = "select o.*, c.name, CONCAT( c.addr1, '  ' , c.addr2) as Address, c.city, c.state, c.zip,
        cmp.company_name,cmp.company_address,cmp.company_city,cmp.company_state,cmp.company_zip,cmp.company_abbr,cmp.company_logo
        from ORDERS o join CUSTOMERS c on c.customer = o.customer_id 
        join COMPANY cmp on cmp.company_number=o.company
        where pic_done between '{$startDate}' and '{$endDate}' order by pic_done";

        return $this->executeQuery($this->conn, $query);
    }


    public function getOrderDetails($orderNumber)
    {
        // $stmt = mysqli_prepare($conn, "INSERT INTO your_table (date_column) VALUES (?)");
        // mysqli_stmt_bind_param($stmt, "s", $date);
        // mysqli_stmt_execute($stmt);

        $orderNumber = mysqli_real_escape_string($this->conn, $orderNumber);

        $query = "select * from ITEMS where ord_num = {$orderNumber}";
        $data = $this->executeQuery($this->conn, $query);
        $js = json_encode($data);
        return $data;
    }

    public function getLabelLivePrint($orderNumber, $itemNumber = 0, $printerNumber, $numberOfCopies)
    {
        $query = "select o.*, c.name, CONCAT( c.addr1, '  ' , c.addr2) as Address, c.city, c.state, c.zip,
        cmp.company_name,cmp.company_address,cmp.company_city,cmp.company_state,cmp.company_zip,cmp.company_abbr,cmp.company_logo
        from ORDERS o join CUSTOMERS c on c.customer = o.customer_id 
        join COMPANY cmp on cmp.company_number=o.company
        where order_num = '{$orderNumber}' order by pic_done";

        $orders = $this->executeQuery($this->conn, $query);
        if (!isset($orders) || empty($orders))
            return "No order Found!";

        $order = $orders[array_key_first($orders)];
        $orderDetails = $this->getOrderDetails($orderNumber);

        if (!isset($orderDetails) || empty($orderDetails))
            return "No items Found! in order #{$orderNumber}";

        $query = "select * from PRINTERS where lpt_number='{$printerNumber}'";
        $printer = $this->executeQuery($this->conn, $query);

        if (!isset($orders) || empty($orders))
            return "No Printer Found!";

        $labelDetails = array(
            "BARCODE" => $orderDetails['shadow'],
            "ORDER_ITEM_NUMBER" => "<b>" + $orderDetails['shadow'] + "</b>",
            "PO_NUMBER" => orderData . cust_po_num,
            "SELLER_LOGO" => "<i><b>" + orderData . company_name + "</b></i>",
            "PART_DESCRIPTION" => $orderDetails['part_desc'],
            "SHIP_CODE" => orderData . ship_via,
            "SELLER_ADDRESS" => orderData . Address,
            "LABEL_NUMBER" => "<b>" + (options . row . rowIndex + 1) + "</b>",
            "BUYER_ADD" => orderData . company_address,
            "BUYER_NAME" => "<b>" + orderData . name + "</b>",
            "ORDER_NUMBER" => $orderDetails['ord_num'],
            "ABBR" => `<b>{orderData . company_abbr}</b>`
        );
    }


    public function getAllPrinters()
    {
        // $stmt = mysqli_prepare($conn, "INSERT INTO your_table (date_column) VALUES (?)");
        // mysqli_stmt_bind_param($stmt, "s", $date);
        // mysqli_stmt_execute($stmt); 

        $query = "select * from PRINTERS";
        $data = $this->executeQuery($this->conn, $query);
        $js = json_encode($data);
        return $data;
    }

}