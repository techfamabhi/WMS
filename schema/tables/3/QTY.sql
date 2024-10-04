use wms;
drop table PARTUOM ;
create table PARTUOM (
shadow int,
company smallint,
uom char(3) null,
uom_each_qty int null,
uom_length smallint null,
uom_width smallint null,
uom_height smallint null,
uom_volume numeric(10,2) NULL,
uom_weight numeric(10,2) NULL
);
create unique index PARTUOM_hash on PARTUOM ( shadow,company);

drop table WHSEBINS ;
create table WHSEBINS (
 wb_location    char(18), 
 wb_company     smallint,
 wb_zone	char(3),
 wb_aisle	char(3),
 wb_section	char(3),
 wb_level	char(2), -- shelf
 wb_subin	char(2),
 wb_length      smallint NULL,
 wb_width       smallint NULL,
 wb_height      smallint NULL,
 wb_volume numeric(10,2) NULL,
 wb_pick      tinyint NULL, -- is pickable
 wb_recv      tinyint NULL, -- is allowed receiving
 wb_statis    char(1) -- A=Active, I=inactive, D=delete
);
create unique index WHSEBINS_hash on WHSEBINS (wb_company,wb_location);

drop table WHSELOC ;
create table WHSELOC (
 whs_location    varchar(18),
 whs_company     smallint,
 whs_shadow      int,
 whse_uom	 char(3),
 whs_qty	 int,
 whs_code        char(2) -- P=Primary Bin,S=Secondary,O=Overstock, M=Moveable
);
create unique index WHSELOC_hash on WHSELOC (whs_location,whs_company, whs_shadow);
create unique index WHSELOC_idx1 on WHSELOC (whs_shadow,whs_company,whs_location);

drop table WHSEQTY ;
create table WHSEQTY (
 wq_shadow      int,
 wq_company     smallint,
 qty_avail int,
 primary_bin char(18),
 qty_overstk int not null, -- qty in bins other than main bin.
 qty_cases int,
 qty_on_hold int,
 qty_on_order int,
 qty_on_vendbo int,
 qty_on_custbo int,
 qty_defect int,
 qty_core int,
 minimum int, -- min qty for main bin
 maximum int, -- max qty for main bin
 cost numeric(10,3) null,
 core numeric(10,3) null
);
create unique index WHSEQTY_hash on WHSEQTY (wq_shadow,wq_company);

-- ?? uom, pack qtys, etc...
