
alter table ITEMPULL add totes	  varchar(40) null default "";
update ITEMPULL set totes = "";
