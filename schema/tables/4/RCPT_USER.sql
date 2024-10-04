
drop table IF EXISTS RCPT_USER;

CREATE TABLE RCPT_USER (
  batch_num INTEGER NOT NULL,
  user_id INTEGER UNSIGNED NOT NULL,
  user_status SMALLINT NOT NULL, -- 0 working, 1 break, 7 complete
  user_action char(3) NOT NULL, -- RCV, PUT, MOV ...
  last_action DATETIME NOT NULL,
  scans INTEGER default 0
)
;

create unique index RCPT_USER_hash on RCPT_USER ( batch_num, user_id );
create unique index RCPT_USER_hash1 on RCPT_USER ( user_id,batch_num );


