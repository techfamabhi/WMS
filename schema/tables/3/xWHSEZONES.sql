drop table WHSEZONES ;
create table WHSEZONES (
 zone_company smallint,
 zone char(3),
 zone_desc char(30) null,
 display_seq tinyint null,
 is_pickable tinyint null
);
create unique index WHSEZONES_hash on WHSEZONES (zone_company,zone);


