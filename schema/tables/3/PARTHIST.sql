drop table IF EXISTS PARTHIST;
create table PARTHIST (
paud_num        int auto_increment primary key,
paud_id	int not null default 0, -- wms internal Ord#, po#, task#, etc
paud_shadow int not null,
paud_company smallint null,
paud_date datetime null,
paud_source varchar(10) default "",
paud_user integer default 0,
paud_ref varchar(20) default " ",
paud_ext_ref varchar(20) default " ",
paud_type char(3) null, -- PCK, RCV, PUT, ADJ, CNT, MOV
paud_qty int default 0,
paud_uom char(3) default "EA",
-- paud_bin varchar(18) default " ",
paud_floc varchar(18) default " ", -- from location
paud_tloc varchar(18) default " ", -- to location
paud_prev_qty int default 0,
paud_inv_code char(1) default "0",
paud_price numeric(10,3) NOT NULL default 0.00,
paud_core_price numeric(10,3) NOT NULL default 0.00,
paud_qty_core INTEGER NULL,
paud_qty_def INTEGER NULL

);

DROP TRIGGER IF EXISTS PARTHIST_I;
DELIMITER //
CREATE TRIGGER PARTHIST_I
BEFORE INSERT ON PARTHIST FOR EACH ROW
BEGIN
    IF (NEW.paud_date IS NULL) THEN -- change the isnull check for the default used
        SET NEW.paud_date = now();
    END IF;
END//
DELIMITER ;


