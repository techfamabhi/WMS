USE wms;

-- alter table PRODLINE drop FOREIGN KEY PL_COMP_FK;

drop TABLE COMPANY ;

CREATE TABLE COMPANY (
	company_number smallint NOT NULL primary key,
	company_name char(34) NULL,
	company_address char(34) NULL,
	company_city char(30) NULL,
	company_state char(2) NULL,
	company_zip char(10) NULL,
	company_phone char(14) NULL,
	company_abbr char(10) NULL,
	company_region char(20) NULL,
	company_fax_num char(14) NULL,
	company_logo varchar(128) NULL -- if null, use company 0 logo
);

ALTER TABLE PRODLINE
        ADD CONSTRAINT PL_COMP_FK FOREIGN KEY (pl_company)
        REFERENCES COMPANY (company_number);

