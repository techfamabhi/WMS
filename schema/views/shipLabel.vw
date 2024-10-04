create view shipLabel
as

select distinct

A.order_num,
carton_num,
A.host_order_num,
A.customer_id,
A.cust_po_num,
company_number,
company_name,
company_address,
company_city,
company_state,
company_zip,
A.priority,
A.num_lines,
A.ship_via,
A.special_instr,
A.shipping_instr,
B.name,
B.addr1,
B.addr2,
B.city,
B.state,
B.zip,
B.ctry

from ORDERS A, CUSTOMERS B, ORDPACK C, COMPANY
where B.customer = A.customer_id
and C.order_num = A.order_num
and company_number = A.company
;


