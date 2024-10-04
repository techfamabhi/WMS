
alter table WHSEZONES
add column is_pickable tinyint null
;
update WHSEZONES set is_pickable = 0;

