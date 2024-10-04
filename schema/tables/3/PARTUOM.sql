use wms;
drop table PARTUOM ;
create table PARTUOM (
shadow int not null,
company smallint not null,
uom char(3) default "EA",
uom_qty int default 1,
uom_length smallint default 0,
uom_width smallint default 0,
uom_height smallint default 0,
uom_weight numeric(10,2) default 0.00,
uom_volume numeric(10,2) default 0.00,
upc_code char(25) default " "
);
create unique index PARTUOM_hash on PARTUOM ( shadow,company, upc_code);

