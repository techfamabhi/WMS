
alter table INV_SCAN
add COLUMN reason  varchar(40) default " "
;

update INV_SCAN set reason = " ";

;

