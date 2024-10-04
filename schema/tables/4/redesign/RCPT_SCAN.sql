
 drop TABLE RCPT_SCAN;


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
  totalOrd INTEGER null default 0

add qty_dropd
    qty_stockd
if recv_to bin, dropd and stockd get populated else, just dropd (into tote)

  
)
;
create unique index RCPT_SCAN_hash on RCPT_SCAN (batch_num, line_num,pkgUOM);
create unique index RCPT_SCAN_idx1 on RCPT_SCAN (batch_num, shadow, pkgUOM);

