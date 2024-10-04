
DROP TABLE RCPT_INWORK ;

CREATE TABLE RCPT_INWORK (
  wms_po_num INTEGER NOT NULL,
  batch_num INTEGER NOT NULL,
  packing_slip varchar(32) default " " NULL
)
;

create unique index RCPT_INWORK_hash on RCPT_INWORK (batch_num, wms_po_num);
create unique index RCPT_INWORK_idx1 on RCPT_INWORK (wms_po_num,batch_num);
