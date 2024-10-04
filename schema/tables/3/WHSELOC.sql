drop table WHSELOC ;
create table WHSELOC (
 whs_company     smallint,
 whs_location    varchar(18),
 whs_shadow      int default 0,
 whs_code        char(2) default " ", -- P=Primary Bin,S=Secondary,O=Overstock, M=Moveable
 whs_qty         int default 0,
 whs_alloc       int NULL default 0,
 whs_uom        char(3) default " "
);
create unique index WHSELOC_hash on WHSELOC (whs_location,whs_company, whs_shadow);
create unique index WHSELOC_idx1 on WHSELOC (whs_shadow,whs_company,whs_location);


