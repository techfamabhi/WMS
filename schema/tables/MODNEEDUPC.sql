
alter table NEEDUPC
add column upc_status tinyint null default 0 after shadow, 
add column upc_scanned varchar(24) null default "" after upc_status,
add column upc_qty smallint null default 0 after upc_scanned
;

update NEEDUPC
set upc_status = 0,
    upc_scanned = "",
    upc_qty = 0

;
