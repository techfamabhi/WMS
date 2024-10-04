USE wms;

CREATE TABLE PRODLINE (
	pl_code char(6) NULL,
	pl_company smallint NULL,
	pl_desc char(30) NULL,
	pl_vend_code char(10) NULL,
	pl_perfered_zone char(3) NULL,
	pl_perfered_aisle char(4) NULL,
	pl_date_added datetime NULL,
	pl_num_notes int NULL
);

CREATE UNIQUE INDEX PRODLINE_hash on PRODLINE (pl_code,pl_company);

ALTER TABLE PRODLINE
        ADD CONSTRAINT PL_VEND_FK FOREIGN KEY (pl_vend_code)
        REFERENCES ENTIRY (host_id);


ALTER TABLE PRODLINE
        ADD CONSTRAINT PL_COMP_FK FOREIGN KEY (pl_company)
        REFERENCES COMPANY (company_number);


