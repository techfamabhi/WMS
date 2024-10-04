/* Track which totes are used for each RCPT */

drop table IF EXISTS RCPTOTE;

CREATE TABLE RCPTOTE (
po_num        int not null,
tote_id      int not null,
last_zone char(3) null default " ",
last_loc char(18) null default ""
);

create unique index RCPTOTE_hash on RCPTOTE ( po_num, tote_id );
create unique index RCPTOTE_idx1 on RCPTOTE ( tote_id,po_num );

alter table RCPTOTE ADD CONSTRAINT OTOT_POH FOREIGN KEY(po_num)
 REFERENCES POHEADER(wms_po_num);

/*
by joining TOTEDTL, I can get line#, Part#, qty, etc...
 
*/
