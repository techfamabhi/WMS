-- 1st, Need to flag zero picked
-- > 0 = times zero picked
-- 2nd, if found, need to know who found it, with modifing current userid
-- 3rd, need to know it's ok to proceed
-- < 0 = ok to proceed, abs value is times zero picked


alter table ITEMPULL 
add COLUMN zero_picked	  tinyint not null default 0,
add COLUMN zpuser	int default 0
;


update ITEMPULL set zero_picked = 0, zpuser=0;

