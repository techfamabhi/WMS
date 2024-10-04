alter table RCPT_BATCH add batch_to char(1) null
;
update RCPT_BATCH set batch_to = 'a';

