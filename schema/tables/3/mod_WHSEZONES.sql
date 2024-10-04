alter table WHSEZONES
add column display_seq tinyint null
;
update WHSEZONES set display_seq = 0;

