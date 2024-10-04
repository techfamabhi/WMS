alter table WHSELOC
add COLUMN whs_alloc int NULL
;

update WHSELOC set whs_alloc = 0;

;

