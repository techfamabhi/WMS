use wms;
drop table PARTS;
create table PARTS
(
p_l char(3) not null,
part_number char(22) not null,
part_desc char(30) null,
part_long_desc char(60) null,
unit_of_measure char(3) null,
part_seq_num int null,
part_subline char(3) null,
part_category char(4) null,
part_group char(10) null,
part_class char(3) null,
date_added datetime null,
lmaint_date datetime null,
serial_num_flag smallint null,
part_status char(1) null,
special_instr char(30) null,
hazard_id smallint null,
kit_flag smallint null,
cost numeric(10,3) null,
core numeric(10,3) null,
core_group char(3) null,
shadow_number int not null,
part_weight numeric(10,3) default 0.00
)
;
create unique index PARTS_hash on PARTS ( shadow_number);
create unique index PARTS_idx1 on PARTS ( p_l,part_number,shadow_number);

create unique index PARTS_idx2 on PARTS ( p_l,part_seq_num,part_number,shadow_number);
create unique index PARTS_idx3 on PARTS ( p_l,part_subline,part_number,shadow_number);
create unique index PARTS_idx4 on PARTS ( part_category,p_l,part_number,shadow_number);

drop table HAZARD_CODES ;
create table HAZARD_CODES 
(
 haz_code char(3) primary key,
 haz_desc varchar(30) null
);
drop table PARTUOM ;
create table PARTUOM (
shadow int,
company smallint,
uom char(3) null,
uom_desc char(30) null,
uom_qty int null,
uom_length smallint null,
uom_width smallint null,
uom_height smallint null,
uom_weight numeric(10,2) NULL,
uom_volume numeric(10,2) NULL
);
create unique index PARTUOM_hash on PARTUOM ( shadow,company);

drop table ALTERNAT ;
create table ALTERNAT (
 alt_shadow_num  int ,
 alt_part_number char(25) ,
 alt_type_code   smallint,
 alt_uom char(3) null,
 alt_sort int null
);
create unique index ALTERNAT_hash on ALTERNAT (alt_part_number,alt_type_code, alt_shadow_num,alt_sort);
create unique index ALTERNAT_idx1 on ALTERNAT (alt_shadow_num,alt_part_number,alt_type_code, alt_sort);

drop table ALTYPES ;
create table ALTYPES (
al_key smallint primary key,
al_desc char(30) null
);
drop table WHSEZONES ;
create table WHSEZONES (
 zone_company smallint,
 zone char(3),
 zone_desc char(30) null
);
create unique index WHSEZONES_hash on WHSEZONES (zone_company,zone);


drop table BINTYPES ;
create table BINTYPES (
 typ_company smallint ,
 typ_code        char(2), -- P=Primary Bin,S=Secondary,O=Overstock, M=Moveable
                          -- C=Core D=Defect
 typ_desc char(30) null
);
create unique index BINTYPES_hash on BINTYPES (typ_company,typ_code);

insert into BINTYPES values (1,"P","Primary");
insert into BINTYPES values (1,"S","Secondary");
insert into BINTYPES values (1,"O","Overstock");
insert into BINTYPES values (1,"M","Moveable/Cart/Pallet");
insert into BINTYPES values (1,"C","Core");
insert into BINTYPES values (1,"D","Defect");

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
 whs_code        char(2), -- P=Primary Bin,S=Secondary,O=Overstock, M=Moveable
 whs_qty	 int,
 whse_uom	 char(3),
 whs_phys_qty1   int      NULL,
 whs_phys_qty2   int  NULL
-- may not need phys qtys if I put inventory in a inv transaction table
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
drop table POHEADER;
create table POHEADER
(
 wms_po_num int not null,
 po_type char(1) not null, -- P po, D debit, T transfer, R cust return, A=ASN
 host_po_num char(20) null,
 company smallint,
 vendor char(10),
 po_date datetime null,
 num_lines int,
 po_statis smallint,
 bo_flag tinyint, -- 0=cancel b/o, 1=bo allowed
 num_messages smallint,
 est_deliv_date datetime null,
 ship_via char(6) null,
 sched_date datetime null,
 xdock tinyint null,
 disp_comment tinyint null,
 customer_id char(12) null,
 ordernum int null, -- if special order for a customer or RMA
 container char(15) null, -- optional container id
 created_by int null
);

create table POITEMS
(
 poi_po_num int,
 poi_line_num int,
 shadow int,
 p_l char(3),
 part_number char(22),
 part_desc char(30) null,
 uom char(3),
 qty_ord int,
 qty_recvd int,
 qty_bo int,
 qty_cancel int,
 mdse_price numeric(10,3) null,
 core_price numeric(10,3) null,
 weight numeric(10,3) null,
 volume numeric(10,3) null,
 case_uom char(3) null, -- if part was ordered in cases
 case_qty int null, -- if part was ordered in cases
 poi_status tinyint, -- 0=open, 1=recvd part, 9=complete
 vendor_ship_qty int null, -- if ASN
 packing_slip char(22) null, -- if ASN
 tracking_num char(22) null, -- if ASN
 bill_lading  char(22) null, -- if ASN
 container_id char(15) null, -- if ASN,
 carton_id char(10) null, -- if ASN and vendor provides
 line_type char(1) null -- " " normal, C=core, D="Defect", see return codes
);

