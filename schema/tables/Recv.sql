create table POHEADER
(
 company smallint,
 wms_po_num int not null,
 host_po_num char(20) default 0,
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
 packing_slip char(22) default " ", -- if ASN
 container varchar(15) default " ", -- optional container id if provided by ASN
 created_by int default 0
);
create unique index POHEADER_hash on POHEADER ( wms_po_num);
create unique index POHEADER_idx1 on POHEADER ( host_po_num,wms_po_num);

create table POITEMS
(
 poi_po_num int, -- wms po#
 poi_line_num int,
 shadow int,
 p_l char(3) default " ",
 part_number char(22) default " ",
 part_desc char(30) default " ",
 uom char(3) default "EA",
 vendor_ship_qty int default 0, -- if ASN
 qty_ord int default 0,
 qty_recvd int default 0,
 qty_prev_recvd int default 0,
 qty_stocked int default 0,
 mdse_price numeric(10,3) default 0,
 core_price numeric(10,3) default 0,
 weight numeric(10,3) default 0,
 volume numeric(10,3) default 0,
 case_uom char(3) default " ", -- if part was ordered in cases
 case_qty int default 0, -- if part was ordered in cases
 poi_status tinyint default 0, -- 0=open, 1=recvd part, 9=complete
 tracking_num char(22) default " ", -- if ASN
 bill_lading  char(22) default " ", -- if ASN
 container_id char(15) default " ", -- if ASN,
 carton_id char(10) default " ", -- if ASN and vendor provides
 line_type char(1) default " ", -- " " normal, C=core, D="Defect", see return codes
  FOREIGN KEY(poi_po_num)
    REFERENCES POHEADER(wms_po_num)

);

create unique index POITEMS_hash on POITEMS ( poi_po_num,poi_line_num);

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
core_price numeric(10,3) null,
  FOREIGN KEY(rcp_number)
    REFERENCES HRECPTS(rec_number)
);

create unique index DRECPTS_idx1 on DRECPTS ( rcp_number,rcp_line);

CREATE TABLE RCPT_BATCH (
  batch_num INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INTEGER UNSIGNED NOT NULL,
  batch_status SMALLINT NOT NULL,
  batch_date DATETIME NOT NULL,
  batch_company SMALLINT NOT NULL,
  batch_type SMALLINT NOT NULL,
  batch_to char(1) null
)
;


CREATE TABLE RCPT_INWORK (
  wms_po_num INTEGER NOT NULL,
  batch_num INTEGER NOT NULL,
  packing_slip varchar(32) default " " NULL,
   FOREIGN KEY(batch_num)
    REFERENCES RCPT_BATCH(batch_num)

)
;

create unique index RCPT_INWORK_hash on RCPT_INWORK (batch_num, wms_po_num);
create unique index RCPT_INWORK_idx1 on RCPT_INWORK (wms_po_num,batch_num);

CREATE TABLE RCPT_SCAN (
  batch_num INTEGER NOT NULL,
  line_num SMALLINT NOT NULL,
  pkgUOM CHAR(3) NOT NULL,
  scan_upc CHAR(25) NOT NULL,
  po_number INTEGER NOT NULL,
  po_line_num INTEGER NOT NULL,
  scan_status SMALLINT NOT NULL,
  scan_user INTEGER NOT NULL,
  pack_id CHAR(18) NOT NULL,
  shadow INTEGER NOT NULL,
  partUOM CHAR(3) NOT NULL,
  line_type char(1) default " ",
  pkgQty INTEGER NOT NULL,
  scanQty INTEGER NOT NULL,
  totalQty INTEGER NOT NULL,
  timesScanned SMALLINT NOT NULL,
  recv_to char(1) default "a",
  totalOrd INTEGER null default 0,
  qty_stockd INTEGER null default 0,
 FOREIGN KEY(batch_num)
    REFERENCES RCPT_BATCH(batch_num)
  
)
;
create unique index RCPT_SCAN_hash on RCPT_SCAN (batch_num, line_num,pkgUOM);
create unique index RCPT_SCAN_idx1 on RCPT_SCAN (batch_num, shadow, pkgUOM);

CREATE TABLE RCPT_TOTE (
  rcpt_num int NOT NULL,
  tote_id int NOT NULL,
  rcpt_status smallint default 0,
  last_zone CHAR(3) NULL,
  last_loc CHAR(18) NULL,
  target_zone CHAR(3) NULL,
  target_aisle SMALLINT NULL,
 FOREIGN KEY(rcpt_num)
    REFERENCES RCPT_BATCH(batch_num)
)
;
create index RCPT_TOTE_hash on RCPT_TOTE (tote_id, rcpt_num);
create unique index RCPT_TOTE_idx1 on RCPT_TOTE ( rcpt_num,tote_id);

/*
by joining TOTEDTL, I can get line#, Part#, qty, etc...

*/

CREATE TABLE RCPT_USER (
  batch_num INTEGER NOT NULL,
  user_id INTEGER UNSIGNED NOT NULL,
  user_status SMALLINT NOT NULL, -- 0 working, 1 break, 7 complete
  user_action char(3) NOT NULL, -- RCV, PUT, MOV ...
  last_action DATETIME NOT NULL,
 FOREIGN KEY(batch_num)
    REFERENCES RCPT_BATCH(batch_num)
)
;

create unique index RCPT_USER_hash on RCPT_USER ( batch_num, user_id );
create unique index RCPT_USER_hash1 on RCPT_USER ( user_id,batch_num );


DROP TRIGGER IF EXISTS RCPT_SCAN_I;
DELIMITER //
CREATE TRIGGER RCPT_SCAN_I
BEFORE INSERT ON RCPT_SCAN FOR EACH ROW
BEGIN
    insert into RCPT_USER
    (batch_num, user_id, user_status, user_action, last_action)
    values ( NEW.batch_num, NEW.scan_user, 0, "RCV", NOW())
    ON DUPLICATE KEY UPDATE
    last_action = NOW();
END//
DELIMITER ;


