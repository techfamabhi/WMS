-- ATTRBUTE allows extra attributes to be added

drop table IF EXISTS ATTRBUTE;
create table ATTRBUTE
(
 attr_id int not null, -- PO#, OE#, task#, etc
 attr_code char(3) not null, -- PS=pack slip, WB=Waybill (see ATTRCODE)
 attr_setting varchar(64) default ""
);

create unique index ATTRBUTE_idx1 on ATTRBUTE (attr_id,attr_code);

drop table IF EXISTS ATTRCODE;
create table ATTRCODE
(
 acode_code char(3) not null, -- PS=pack slip, WB=Waybill (see ATTRCODE)
 acode_sys char(3) not null, -- PO, OE, INV, etc
 acode_desc varchar(40) default ""
);
create unique index ATTRCODE_idx1 on ATTRCODE (acode_code);

insert into ATTRCODE
(acode_code,acode_sys,acode_desc)
values ("PS","PO","Packing Slip");
