create table VIADROP
(
 ship_via char(4) not null,
 zone char(3) not null,
 pick_drop char(3) not null
);
create unique index VIADROP_hash (ship_via,zone);

