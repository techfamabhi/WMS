drop table IF EXISTS NEEDUPC;
create table NEEDUPC
(
shadow int not null default 0,
upc_status tinyint null default 0, 
-- 0=open, 
-- 1=No UPC on box, 
-- 2=duplicate upc with other part, 
-- 3=UPC is the part # of another part
-- 4=UPC is same as the part#
-- 5=UPC is linked to multiple parts

upc_scanned varchar(24) null default "",
upc_qty smallint null default 0
)
;

