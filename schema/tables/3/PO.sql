use wms;
drop table IF EXISTS POHEADER;
create table POHEADER
(
 company smallint,
 wms_po_num int not null,
 host_po_num varchar(30) default " ",
 po_type char(1) default "P", -- P po, D debit, T transfer, R cust return, A=ASN, S Special Order
 vendor char(10) default " ",
 po_date datetime null,
 num_lines int default 0,
 po_status smallint default 0,
 bo_flag tinyint default 1, -- 0=cancel b/o, 1=bo allowed
 num_messages smallint default 1,
 est_deliv_date datetime null,
 ship_via char(6) default " ",
 sched_date datetime null, -- scheduled delivery date
 xdock tinyint default 0,
 disp_comment tinyint default 0,
 comment varchar(128) default " ",
 customer_id char(12) default 0,
 ordernum int default 0, -- if special order for a customer or RMA
 container varchar(15) default " ", -- optional container id if provided by ASN
 created_by int default 0
);
create unique index POHEADER_hash on POHEADER ( wms_po_num);
create unique index POHEADER_idx1 on POHEADER ( host_po_num,wms_po_num);

drop table IF EXISTS POITEMS;
create table POITEMS
(
 poi_po_num int, -- wms po#
 poi_line_num int,
 shadow int,
 p_l char(3) default " ",
 part_number char(22) default " ",
 part_desc char(30) default " ",
 uom char(3) default "EA",
 qty_ord int default 0,
 qty_recvd int default 0,
 qty_bo int default 0,
 qty_cancel int default 0,
 mdse_price numeric(10,3) default 0,
 core_price numeric(10,3) default 0,
 weight numeric(10,3) default 0,
 volume numeric(10,3) default 0,
 case_uom char(3) default " ", -- if part was ordered in cases
 case_qty int default 0, -- if part was ordered in cases
 poi_status tinyint default 0, -- 0=open, 1=recvd part, 9=complete
 vendor_ship_qty int default 0, -- if ASN
 packing_slip char(22) default " ", -- if ASN
 tracking_num char(22) default " ", -- if ASN
 bill_lading  char(22) default " ", -- if ASN
 container_id char(15) default " ", -- if ASN,
 carton_id char(10) default " ", -- if ASN and vendor provides
 line_type char(1) default " " -- " " normal, C=core, D="Defect", see return codes
);

create unique index POITEMS_hash on POITEMS ( poi_po_num,poi_line_num);

