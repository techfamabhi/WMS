alter table PARTHIST
change paud_bin paud_floc varchar(18) default " "
;

alter table PARTHIST
add COLUMN paud_tloc varchar(18) default " "
;

ALTER TABLE PARTHIST MODIFY paud_tloc varchar(18) AFTER paud_floc
;

update PARTHIST set paud_tloc = " ";



