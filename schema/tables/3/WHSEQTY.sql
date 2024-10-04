drop table IF EXISTS WHSEQTY ;
create table WHSEQTY (
 ms_shadow      int,
 ms_company     smallint,
 primary_bin char(18) default " " ,
 qty_avail int default 0,
 qty_alloc int default 0,
 qty_putaway int default 0, -- recvd but not putaway yet
 qty_overstk int default 0, -- qty in bins other than main bin.
 qty_on_order int default 0,
 qty_on_vendbo int default 0,
 qty_on_custbo int default 0,
 qty_defect int default 0,
 qty_core int default 0,
 max_shelf int default 0,
 minimum int default 0, -- min qty for main bin
 maximum int default 0, -- max qty for main bin
 cost numeric(10,3) default 0.00,
 core numeric(10,3) default 0.00
);
create unique index WHSEQTY_hash on WHSEQTY (ms_shadow,ms_company);

