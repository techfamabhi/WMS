
alter table WHSEZONES
add column zone_color char(7) null default " "
;
update WHSEZONES set zone_color = "";

