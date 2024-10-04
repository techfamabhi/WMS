use wms;


drop table BINTYPES ;
create table BINTYPES (
 typ_company smallint ,
 typ_code        char(3), -- P=Primary Bin,S=Secondary,O=Overstock, M=Moveable
                          -- C=Core D=Defect
 typ_desc char(30) null,
 typ_pick	tinyint null, -- is bin pickable
 typ_recv	tinyint null, -- is recv allowed
 typ_core	tinyint null, -- is reservered for COR uom's
 typ_defect	tinyint null -- is reservered for defect uom's
 
);
create unique index BINTYPES_hash on BINTYPES (typ_company,typ_code);

insert into BINTYPES values (1,"P","Primary",1,1,0,0);
insert into BINTYPES values (1,"S","Secondary",1,1,0,0);
insert into BINTYPES values (1,"O","Overstock",1,1,0,0);
insert into BINTYPES values (1,"M","Moveable/Cart/Pallet",1,1,1,1);
insert into BINTYPES values (1,"C","Core",0,0,1,1);
insert into BINTYPES values (1,"D","Defect",0,0,1,1);

