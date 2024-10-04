DROP TABLE IF EXISTS WMSCOMMERR;

CREATE TABLE WMSCOMMERR (
  errId INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
  utcDate DATETIME NOT NULL,
  recordType varchar(20) null default " ",
  statusCode smallint default 0, -- 500=failed, 423 Locked Rec, 200=success, 
                                -- 1=Gave up after x trys
  retryTimes smallint default 0,
  lastRetry datetime null,
  payload text null,
  response text null -- last response
)
;

create unique index WMSCOMMERR_hash on WMSCOMMERR (utcDate, errId);

