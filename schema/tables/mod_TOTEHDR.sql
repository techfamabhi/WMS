alter table TOTEHDR
add column tote_code varchar(18) default " " after tote_id;

update TOTEHDR
set tote_code = convert(tote_id,CHAR);

create unique index TOTEHDR_idx4 on TOTEHDR (tote_company,tote_code);
