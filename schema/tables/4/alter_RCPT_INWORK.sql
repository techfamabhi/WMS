alter table RCPT_INWORK add packing_slip varchar(32) default " " null
;
update RCPT_INWORK set packing_slip = ' ';


