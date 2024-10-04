use wms;
drop table IF EXISTS HRECPTS;
create table HRECPTS (
rec_number	int auto_increment primary key, -- receiver#
company smallint,
trans_type char(3), -- PO, TRN, RMA cust-return, UXP - unexpected
host_trans_num char(20), -- PO#, Order# for TRN or RMA
wms_po_num int, 
packing_slip char(22) null, 
asn_number varchar(48) null, 
date_recvd datetime null,
recvd_by char(12) null,
sent_flag tinyint default 0
);
create unique index HRECPTS_idx1 on HRECPTS ( host_trans_num,company,rec_number);

drop table IF EXISTS DRECPTS;
create table DRECPTS (
rcp_number	int not null,
rcp_line smallint not null,
shadow int not null,
p_l char(3) default " ",
part_number char(22) default " ",
upc char(25) default " ",
barcode_qty int default 1,
qty_recvd int default 0,
qty_bo int default 0,
qty_cancel int default 0,
uom char(3) default "EA",
man_qty_ovrd char(1) default " ",
serial_number varchar(30) default "",
bin_pack char(15) default " ", -- bin number or pack id
rcp_status tinyint default 0, -- 1 = done, 2 = exported
mdse_price numeric(10,3) null,
core_price numeric(10,3) null
);

create unique index DRECPTS_idx1 on DRECPTS ( rcp_number,rcp_line);

drop table IF EXISTS PARTHIST;
create table PARTHIST (
paud_num	int auto_increment primary key, 
paud_shadow int not null,
paud_company smallint null,
paud_date datetime null,
paud_ref varchar(15) default " ",
paud_ext_ref varchar(20) default " ",
paud_type char(3) null, -- PCK, RCV, PUT, ADJ, CNT, MOV
paud_po_ord varchar(15) default " ", -- host po or order#
paud_qty int default 0,
paud_uom char(3) default "EA",
paud_floc varchar(18) default " ",
paud_tloc varchar(18) default " ",
paud_prev_qty int default 0,
paud_prev_uom char(3) default "EA",
paud_prev_bin varchar(18) default " ",
paud_inv_code char(1) default "0",
mdse_price numeric(10,3) null,
core_price numeric(10,3) null,
paud_neg_seq int default 0
);

