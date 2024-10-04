USE wms;
drop table IF EXISTS COPTDESC;

CREATE TABLE COPTDESC (
	copt_number smallint NOT NULL primary key,
	copt_desc varchar(50) NULL default " ",
	copt_desc1 varchar(50) NULL default " ",
	copt_cat  varchar(40) NULL default " ",
	copt_text text NULL
);
