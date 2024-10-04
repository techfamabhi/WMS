DROP TABLE IF EXISTS REASONS;
CREATE TABLE REASONS (
reason_code char(3) default " ", 
reason_desc char(30) default " ",
host_reason varchar(30) default " "

);

create unique index REASONS_idx1 ON REASONS (reason_code);

insert into REASONS values ("A","Inventory Correction","Adjustment");
insert into REASONS values ("B","Break Carton/Roll","Adjustment");
insert into REASONS values ("D","Defective/Leaking","Adjustment");
insert into REASONS values ("DS","Damaged","Adjustment");
insert into REASONS values ("F","Found","Adjustment");
insert into REASONS values ("M","Missing/Stolen","Adjustment");
insert into REASONS values ("S","Scrap","Adjustment");
insert into REASONS values ("SD","Sales Demo","Adjustment");
insert into REASONS values ("U","Wrong UPC","Adjustment");
insert into REASONS values ("X","Wrong Packaging","Adjustment");
insert into REASONS values ("CYC","Cycle Count","Cycle Count");
insert into REASONS values ("PHY","Physical Count","Physical Count");

insert into REASONS values ("R","Correct Receiving","");

