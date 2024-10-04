DROP TABLE IF EXISTS WMSERROR;

CREATE TABLE WMSERROR (
  errId INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
  utcDate DATETIME NOT NULL,
  recordType varchar(6) null default " ",
  fileName varchar(64) null default " ",
  rowNum smallint default 0,
  docNum varchar(20) default " ",
  message text null,
  rowData text null
)
;

create unique index WMSERROR_hash on WMSERROR (utcDate, errId);

