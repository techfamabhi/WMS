select ord_num,part_number,qty_ship,A.qty_avail, qty_alloc
from ITEMS,WHSEQTY A
where ms_shadow = shadow
and ms_company = inv_comp
;

select * from ORDQUE;
