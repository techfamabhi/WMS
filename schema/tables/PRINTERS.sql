use wms;

create table PRINTERS (
lpt_number smallint not null primary key,
lpt_description char(30) null,
lpt_company smallint null,
lpt_pathname char(128) null,
lpt_type char(20) null,
lpt_copy_code char( 4) null,
lpt_prompt char(15) null
)
;

