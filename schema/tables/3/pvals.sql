drop table UOM_CODES;
drop table SUBLINES;
drop table CATEGORYS;
drop table PARTGROUPS;
drop table PARTCLASS;
drop table SHIPVIA;


create table UOM_CODES (
uom_code char(3) null primary key,
uom_desc char(30) default " ",
uom_inv_code char(1) default "0" -- 0=Normal 1=Defect 2=core
);

create table SUBLINES (
p_l char(3) not null,
subline char(3) not null,
subline_desc char(30) default " "
);
create unique index SUBLINE_hash on SUBLINES ( p_l,subline);

create table CATEGORYS (
cat_id char(3) null primary key,
cat_desc char(30) default " "
);

create table PARTGROUPS (
pgroup_id char(6) null primary key,
pgroup_desc char(30) default " "
);

create table PARTCLASS (
class_id char(3) null primary key,
class_desc char(30) default " "
);

create table SHIPVIA (
via_code char(4) null primary key,
via_desc char(30) default " ",
via_SCAC char(4) default " "
);


insert into UOM_CODES (uom_code,uom_desc,uom_inv_code) values ("C12","Case of 12",0);
insert into UOM_CODES (uom_code,uom_desc,uom_inv_code) values ("COR","Core",2);
insert into UOM_CODES (uom_code,uom_desc,uom_inv_code) values ("CS6","Case of 6",0);
insert into UOM_CODES (uom_code,uom_desc,uom_inv_code) values ("DEF","Defective", 1);
insert into UOM_CODES (uom_code,uom_desc,uom_inv_code) values ("EA","Each", 0);

insert into CATEGORYS (cat_id,cat_desc) values ("02"," ");

insert into PARTGROUPS ( pgroup_id , pgroup_desc ) values ("1"," ");
insert into PARTGROUPS ( pgroup_id , pgroup_desc ) values ("3"," ");
insert into PARTGROUPS ( pgroup_id , pgroup_desc ) values ("H"," ");

insert into PARTCLASS ( class_id , class_desc ) values ("1"," ");
insert into PARTCLASS ( class_id , class_desc ) values ("3"," ");
insert into PARTCLASS ( class_id , class_desc ) values ("3B"," ");
insert into PARTCLASS ( class_id , class_desc ) values ("D"," ");
insert into PARTCLASS ( class_id , class_desc ) values ("M"," ");

insert into SHIPVIA ( via_code , via_desc , via_SCAC ) values ("UPS","UPS"," ");
