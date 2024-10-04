drop table IF EXISTS SHIPVIA;

create table SHIPVIA (
via_code char(4) null primary key,
via_desc char(30) default " ",
via_SCAC char(4) default " ",
pack_rescan tinyint NULL default 0,
drop_zone char(3) default " "
);

insert into SHIPVIA ( via_code , via_desc , via_SCAC ) values ("UPS","UPS"," ");
