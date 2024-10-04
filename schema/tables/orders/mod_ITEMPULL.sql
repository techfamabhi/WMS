alter table ITEMPULL add qty_verified	  int not null default 0;
update ITEMPULL set qty_verified = 0;
