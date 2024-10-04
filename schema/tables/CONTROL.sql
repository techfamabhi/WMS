USE wms;

DROP TABLE CONTROL ;

CREATE TABLE CONTROL (
	control_key char(8) NULL,
	control_company smallint NULL,
	control_number int NULL,
	control_maxnum int NULL,
	control_reset_to int NULL
);

CREATE UNIQUE INDEX CONTROL_hash ON CONTROL (control_key,control_company);

