drop table WHSEBINS ;
create table WHSEBINS (
 wb_company     smallint,
 wb_location    char(18),
 wb_zone        char(3) default ' ',
 wb_aisle       smallint unsigned default 0,
 wb_section     tinyint unsigned default 0,
 wb_level       char(1) default ' ', -- shelf
 wb_subin       tinyint unsigned default 0,
 wb_length      numeric (7,2) default 0,
 wb_width       numeric (7,2) default 0,
 wb_height      numeric (7,2) default 0,
 wb_volume numeric(10,2) default 0.00,
 wb_pick      tinyint default 1, -- is pickable
 wb_recv      tinyint default 1, -- is allowed receiving
 wb_statis    char(1) default "A" -- A=Active, I=inactive, D=delete
);
create unique index WHSEBINS_hash on WHSEBINS (wb_company,wb_location);
