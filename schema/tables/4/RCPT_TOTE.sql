drop TABLE IF EXISTS RCPTOTE;
drop TABLE IF EXISTS RCPT_TOTE;

CREATE TABLE RCPT_TOTE (
  rcpt_num int NOT NULL,
  tote_id int NOT NULL,
  rcpt_status smallint default 0,
  last_zone CHAR(3) NULL,
  last_loc CHAR(18) NULL,
  target_zone CHAR(3) NULL,
  target_aisle SMALLINT NULL
)
;
create index RCPT_TOTE_hash on RCPT_TOTE (tote_id, rcpt_num);
create unique index RCPT_TOTE_idx1 on RCPT_TOTE ( rcpt_num,tote_id);

/*
by joining TOTEDTL, I can get line#, Part#, qty, etc...

*/


