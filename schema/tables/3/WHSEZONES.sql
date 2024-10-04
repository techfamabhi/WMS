drop table WHSEZONES ;
create table WHSEZONES (
 zone_company smallint,
 zone_type char(3), -- PIC, PAK, SHP
 zone char(3),
 zone_desc char(30) null,
 display_seq tinyint null,
 is_pickable tinyint null,
 zone_color char(7) null default " "
);
create unique index WHSEZONES_hash on WHSEZONES (zone_company,zone_type,zone);
create unique index WHSEZONES_idx1 on WHSEZONES (zone_company,zone,zone_type);


-- PIC drops to PAK zone
-- PAK drops to SHP zone
-- SHP drops to STG zone
-- STG actually ships
-- REC drops to PUT or Bin
-- PUT goes to Bin

