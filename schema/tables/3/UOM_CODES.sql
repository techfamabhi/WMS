drop table UOMCODES ;
create table UOMCODES (
uom_code char(3) null primary key,
uom_desc char(30) default " ",
uom_inv_code char(1) default "0" -- 0=Normal 1=Defect 2=core
);


insert into UOMCODES (uom_code,uom_desc,uom_inv_code) values ("C12","Case of 12",0);
insert into UOMCODES (uom_code,uom_desc,uom_inv_code) values ("COR","Core",2);
insert into UOMCODES (uom_code,uom_desc,uom_inv_code) values ("CS6","Case of 6",0);
insert into UOMCODES (uom_code,uom_desc,uom_inv_code) values ("DEF","Defective", 1);
insert into UOMCODES (uom_code,uom_desc,uom_inv_code) values ("EA","Each", 0);

