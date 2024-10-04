select "Before",A.qty_avail,A.qty_alloc ,qty_ship
from ITEMS,WHSEQTY A
where ord_num = 10022
and line_num = 1
and ms_shadow = shadow;

update ITEMS
set qty_ship = 0
where ord_num = 10022
and line_num = 1
;


select "After",A.qty_avail,A.qty_alloc ,qty_ship
from ITEMS,WHSEQTY A
where ord_num = 10022
and line_num = 1
and ms_shadow = shadow;

