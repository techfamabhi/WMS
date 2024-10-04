

use wms;


select  now()

select DATE_SUB(now(), INTERVAL 15 DAY)

--how to substract date?


select c.* from wms.`ORDERS` o join wms.`CUSTOMERS` c on c.customer=o.customer_id 


select o.*,c.name,c.addr1+' '+c.addr2 as Address,c.city,c.state,c.zip
from ORDERS o join CUSTOMERS c on c.customer=o.customer_id where pic_done between '2018-09-18 19:17:24' and '2024-09-18 19:17:24 '


select o.*, c.name, CONCAT( c.addr1, '  ' , c.addr2) as "Address", c.city, c.state, c.zip
from ORDERS o join CUSTOMERS c on c.customer = o.customer_id  

select * from PARTS 

--join string in mysql?

select * from wms.`COMPANY`

select * from ITEMS where ord_num =10021


select * from wms.`ORDERS` order by pic_done desc

select * from wms.`CUSTOMERS`


select * from wms.`PRINTERS`


update wms.`PRINTERS` set ptr_connected_mcn_ip='192.168.9.72',ptr_connected_mcn_port=11180 where 1=1
 


--adding column in table mysql?

SET
    GLOBAL sql_mode = (
        SELECT
        REPLACE (
                @@sql_mode,
                'ONLY_FULL_GROUP_BY',
                ''
            )
    );



insert into wms.WEB_MENU VALUES(
85,	51,	'Label Live',	'images/labellive.gif',	'printLabelLive.php',	0,'_blank')



select * from wms.WEB_MENU WHERE menu_num = 85 order by menu_desc


SELECT pnote_line, pnote_code, pnote_note
FROM PARTNOTE,INV_SCAN
WHERE pnote_shadow =shadow
ORDER BY pnote_line ASC




select count(*) as cnt,
  IFNULL(sum(qty_ord - qty_stockd),0) as units,
  IFNULL(sum(qty_ord),0) as orderd,
  IFNULL(sum(qty_stockd),0) as stockd
from
RCPT_BATCH A,
RCPT_SCAN B,
POHEADER D,
POITEMS E
where  B.batch_num = A.batch_num
  and D.wms_po_num = B.po_number
  and E.poi_po_num = B.po_number
  and E.shadow = B.shadow


   select RCPT_INWORK.batch_num
 from RCPT_INWORK,RCPT_BATCH


--check receiving
  select sum(totalQty) as totalQty,
 sum(qty_stockd) as qtyStocked
 from RCPT_SCAN where scan_status < 2


--getRecpt
 select
RCPT_SCAN.batch_num,
host_po_num,
 po_number,
line_num,
PARTS.p_l,
PARTS.part_number,
PARTS.part_desc,
 pkgUOM,
 scan_upc,
 po_line_num,
 scan_status,
 scan_user,
 pack_id,
 D.shadow,
 partUOM,
 RCPT_SCAN.line_type,
 pkgQty,
 qty_ord,
 scanQty,
 totalQty,
 timesScanned,
 qty_recvd,
 qty_stockd,
 recv_to
from RCPT_INWORK,RCPT_SCAN, PARTS, POHEADER, POITEMS D
where  RCPT_INWORK.batch_num = RCPT_SCAN.batch_num
and scan_status < 2
and shadow_number = RCPT_SCAN.shadow
and POHEADER.wms_po_num = po_number
and poi_po_num = po_number
and D.shadow = RCPT_SCAN.shadow




 select sum(totalQty) as inRecv
from RCPT_INWORK,RCPT_SCAN
where RCPT_INWORK.batch_num = RCPT_SCAN.batch_num
and scan_status < 2


select ship_complete, order_stat, company
from ORDERS
where  order_stat < 2


 select 
primary_bin,
qty_avail,
qty_alloc,
qty_putaway,
qty_overstk,
qty_on_order,
qty_on_vendbo,
qty_on_custbo,
qty_defect,
qty_core
from WHSEQTY 

SELECT * FROM ORDTRACK

SELECT * FROM ORDERS

SELECT * FROM ORDERS

SELECT part_number, count(part_number) as Qnty FROM `PARTS` GROUP BY part_number

SELECT * FROM `WHSEQTY` w join `PARTS` p on w.ms_shadow=p.shadow_number


  select paud_shadow,part_number,part_desc,part_class,qty_avail,primary_bin
 from PARTHIST,WHSEQTY
 where ms_shadow =  paud_shadow


SELECT paud_type, COUNT(*) AS total from PARTS,PARTHIST 
  where paud_shadow = shadow_number group by part_number,paud_type


SELECT COUNT(*) AS total from PARTS,PARTHIST 
  where paud_shadow = shadow_number and paud_type='PUT' group by part_number


SELECT * FROM ORDERS where order_stat=9

select shadow_number,p_l,part_number,part_desc,part_class,
  paud_id,
  paud_date,
  paud_source,
  paud_user,
  paud_ref,
  paud_ext_ref,
  paud_type,
  paud_qty,
  paud_uom,
  paud_floc,
  paud_tloc,
  paud_prev_qty,
  paud_inv_code,
  paud_qty_core,
  paud_qty_def
 from PARTS,PARTHIST
   where paud_shadow = shadow_number and paud_type='PIC'





select * from ITEMPULL
  where  
   qty_picked < qtytopick


SELECT count(*) FROM `POITEMS` where poi_status=9

SELECT COUNT(*) FROM `POITEMS` where  poi_status=0

select * from `INV_SCAN`,`PARTS` where shadow_number=shadow



 select company, order_num, host_order_num, order_type, order_stat, priority,
num_lines,
date_required,
enter_date,
enter_by,
ship_complete,
ORDERS.ship_via,
customer_id,
name,
addr1,
addr2,
city,
state,
zip,
ctry,
phone,
mdse_type,
drop_ship_flag,
zones,
special_instr,
shipping_instr
FROM ORDERS,CUSTOMERS where order_stat =2