<?php
//inFldMap.php -- Incoming Field Definitions
//05/24/23 dse initial
//operation field is always 0=Add, 1=Delete, 2=If Exists Update, Else Add

//Vendors Import
$fields = array("VEN" => array(
    0 => "vendor",
    1 => "name",
    2 => "addr1",
    3 => "addr2",
    4 => "city",
    5 => "state",
    6 => "zip",
    7 => "ctry",
    8 => "contact",
    9 => "phone",
    10 => "email",
    11 => "allow_bo",
    12 => "allow_to_bin",
    13 => "allow_inplace",
    14 => "operation"
), // end VEN
//Product Line Import
    "PLM" => array(
        0 => "pl_code",
        1 => "pl_company",
        2 => "pl_desc",
        3 => "pl_vend_code",
        4 => "pl_perfered_zone",
        5 => "pl_perfered_aisle",
        6 => "pl_date_added",
        7 => "operation"
    ), //end PLM
//Valid Unit of Measures
    "UMC" => array(
        0 => "uom_code",
        1 => "uom_desc",
        2 => "operation"
    ),
//Valid Sublines
    "SUB" => array(
        0 => "p_l",
        1 => "subline",
        2 => "subline_desc",
        3 => "operation"
    ),
//valid Categories
    "CAT" => array(
        0 => "cat_id",
        1 => "cat_desc",
        2 => "operation"
    ),
//valid Product Groups
    "PGR" => array(
        0 => "pgroup_id",
        1 => "pgroup_desc",
        2 => "operation"
    ),
// valid Part Classes
    "CLS" => array(
        0 => "class_id",
        1 => "class_desc",
        2 => "operation"
    ),
// Valid Ship Via Codes
    "VIA" => array(
        0 => "via_code",
        1 => "via_desc",
        2 => "via_SCAC",
        3 => "operation"
    ),
// valid warehouse zones
    "ZON" => array(
        0 => "zone_company",
        1 => "zone",
        2 => "zone_desc",
        3 => "operation"
    ),
// Parts Import
    "PRT" => array(
        0 => "p_l",
        1 => "part_number",
        2 => "part_desc",
        3 => "part_long_desc",
        4 => "unit_of_measure",
        5 => "part_seq_num",
        6 => "part_subline",
        7 => "part_category",
        8 => "part_group",
        9 => "part_class",
        10 => "date_added",
        11 => "serial_num_flag",
        12 => "part_status",
        13 => "special_instr",
        14 => "hazard_id",
        15 => "kit_flag",
        16 => "cost",
        17 => "core",
        18 => "core_group",
        19 => "part_returnable",
        20 => "shadow_number" // MacCEL only, allows inport of shadow #
    ),
// valid Bin Types
    "BTP" => array(
        0 => "typ_company",
        1 => "typ_code",
        2 => "typ_desc",
        3 => "operation"
    ),
// Part Unit of Measure Import
    "UOM" => array(
        0 => "p_l",
        1 => "part_number",
        2 => "company",
        3 => "uom",
        4 => "uom_qty",
        5 => "uom_length",
        6 => "uom_width",
        7 => "uom_height",
        8 => "uom_weight",
        9 => "uom_volume",
        10 => "upc_code"
    ),

// Purchase Order Inport
    "POH" => array(
        0 => "company",
        1 => "host_po_num", //Host Document #
        //PO if type is "P" or ASN
        //RMA# if type "R" for Credit
        //Transfer# if type is "T" ...
        2 => "po_type", // P=po, T=transfer, R=cust return(RMA), A=ASN, S=Spec Order
        3 => "vendor",  //if type R,vendor is blank, customer must be populated
        4 => "po_date",
        5 => "num_lines",
        6 => "bo_flag", // 0=cancel back orders, 1= BO allowed
        7 => "est_deliv_date",
        8 => "ship_via",
        9 => "sched_date",
        10 => "xdock", //1=crossdock
        11 => "disp_comment",
        12 => "comment",
        13 => "customer_id", // if cust return, spec order or xdock
        14 => "ordernum",    //if special order, xdock or RMA
        15 => "container",   //optional if ASN provides the container id
        16 => "carton_id"    //optional if provided by ASN

    ),
//Purchase Order Detail
    "POD" => array(
        0 => "poi_po_num",
        1 => "poi_line_num",
        2 => "p_l",   //Product Line
        3 => "part_number",
        4 => "part_desc",
        5 => "uom",
        6 => "qty_ord",
        7 => "mdse_price",
        8 => "core_price",
        9 => "line_type",
        10 => "case_uom",
        11 => "case_qty",
        12 => "vendor_ship_qty", //if ASN
        13 => "packing_slip", //if ASN
        14 => "tracking_num", //if ASN
        15 => "bill_lading", //if ASN
        16 => "container_id", //if ASN
        17 => "carton_id" //if ASN
    ),
// Customers
    "CST" => array(
        0 => "customer",
        1 => "name",
        2 => "addr1",
        3 => "addr2",
        4 => "city",
        5 => "state",
        6 => "zip",
        7 => "ctry",
        8 => "contact",
        9 => "phone",
        10 => "email",
        11 => "allow_bo",
        12 => "ship_via",
        13 => "operation"
    ), // end CST
    "ORD" => array(  // Sales Order Header
        0 => "order_type", // O=Order, T=Transfer, D=Vendor Memo
        1 => "host_order_num", // Order number in docs
        2 => "customer_id",
        3 => "company",
        4 => "cust_po_num", // customer po number
        5 => "date_required",
        6 => "enter_by", // host entered by
        7 => "enter_date", // host enter date
        8 => "priority", // Order Priority 0=Highest,99=lowest, 0-5 automatically released to floor
        9 => "ship_complete", // Y/N
        10 => "mdse_type", // 0=Mdse 1=Defect, 2=Core
        11 => "ship_via",
        12 => "spec_order_num", // Incoming Document if whole order is a Special Order
        13 => "conveyor", // conveyor to route order to when shipping
        14 => "drop_ship_flag", // if dropship
        15 => "special_instr",  //24 character special handling info
        16 => "shipping_instr", //24 character shipping info
        17 => "messg"    // message text varchar
//wms_date, // date wms got it
//order_stat, // always 0
//num_lines, // auto increment
    ), // end Sales Order Header
    "ITM" => array(  // Sales Order Items
        0 => "ord_num", // host order number
        1 => "line_num", // item number
        2 => "p_l",
        3 => "part_number",
        4 => "part_desc",
        5 => "uom",
        6 => "qty_ord",
        7 => "min_ship_qty", // minimum qty to ship
        8 => "case_qty", // case qty
        9 => "inv_code", // default "0" -- 0=Normal 1=Defect 2=core
        10 => "specord_num", // Incoming Document if line is a Special Order
        11 => "company"

// get these from the Parts Record
// 10=>"hazard_id", // char(3) hazard code 
// 11=>"part_weight",
// 12=>"part_subline",
// 13=>"part_category",
// 14=>"part_group",
// 15=>"part_class",

//shadow, // lookup
//qty_ship, // 0
//qty_bo, // 0
//qty_avail, // lookup
//line_status, // 0
//zone, // lookup
//whse_loc, // lookup
//qty_in_primary, // lookup
//num_messg, // 0
//item_pulls,// 0
    ), // end Sales Order Items
    "DRP" => array(  // Sales Order Dropship, must occur after the ORD record
        0 => "order_num", // host order num
        1 => "name",
        2 => "addr1",
        3 => "addr2",
        4 => "city",
        5 => "state",
        6 => "zip",
        7 => "ctry",
        8 => "phone",
        9 => "email",
        10 => "company"
    ),  // end dropship
    "ORL" => array(  // Sales Order Complete flag
        0 => "order_num", // Order number in docs
        1 => "releaseCode",   // char(3) blank=use priority to release, "REL"=Force Release to Floor
        /* by default priority 0 thru 3 Orders are released to the floor,
           all other priorities require release thru Order Release Screen */
        2 => "company"
    ), // end ORL
//Return to Tote as Putaway
    "RET" => array(
        0 => "company",
        1 => "host_po",
        2 => "p_l",   //Product Line
        3 => "part_number",
        4 => "uom",
        5 => "qty",
        6 => "line_type"
    )
);
$partValFields = array(
    "unit_of_measure" => "UOMCODES|uom_code",
    "part_category" => "CATEGORYS|cat_id",
    "part_group" => "PARTGROUPS|pgroup_id",
    "part_class" => "PARTCLASS|class_id",
    "hazard_id" => "HAZARD_CODES|haz_code"
);

?>
