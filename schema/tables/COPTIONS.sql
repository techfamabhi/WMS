USE wms;

CREATE TABLE COPTIONS (
	cop_company smallint NULL,
	cop_option smallint NULL,
	cop_flag varchar(128) NULL
);
CREATE UNIQUE INDEX COPTIONS_hash ON COPTIONS (cop_company,cop_option);


