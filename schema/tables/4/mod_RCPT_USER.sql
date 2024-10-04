

alter table RCPT_USER
add scans INTEGER default 0
;

update RCPT_USER set scans = 0;


