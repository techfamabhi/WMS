use wms;
 drop table PARTS;
create table PARTS
(
p_l char(3) not null,
part_number char(22) not null,
part_desc char(30) default " ",
part_long_desc char(60) default " ",
unit_of_measure char(3) default " ",
part_seq_num int default 0,
part_subline char(3) default " ",
part_category char(4) default " ",
part_group char(10) default " ",
part_class char(3) default " ",
date_added datetime null,
lmaint_date datetime null,
serial_num_flag smallint default 0,
part_status char(1) default " ",
special_instr char(30) default " ",
hazard_id char(3) default " ",
kit_flag smallint default 0,
cost numeric(10,3) default 0.00,
core numeric(10,3) default 0.00,
core_group char(3) default " ",
part_returnable char(1) default " ",
shadow_number int not null
)
;
create unique index PARTS_hash on PARTS ( shadow_number);
create unique index PARTS_idx1 on PARTS ( p_l,part_number,shadow_number);

create unique index PARTS_idx2 on PARTS ( p_l,part_seq_num,part_number,shadow_number);
create unique index PARTS_idx3 on PARTS ( p_l,part_subline,part_number,shadow_number);
create unique index PARTS_idx4 on PARTS ( part_category,p_l,part_number,shadow_number);

DROP TRIGGER IF EXISTS PARTS_I;
DELIMITER //
CREATE TRIGGER PARTS_I
BEFORE INSERT ON PARTS FOR EACH ROW
BEGIN
    IF (NEW.date_added IS NULL) THEN -- change the isnull check for the default used
        SET NEW.date_added = now();
    END IF;
    IF (NEW.lmaint_date IS NULL) THEN -- change the isnull check for the default used
        SET NEW.lmaint_date = now();
    END IF;
END//
DELIMITER ;

DROP TRIGGER IF EXISTS PARTS_U;
DELIMITER //
CREATE TRIGGER PARTS_U
BEFORE UPDATE ON PARTS FOR EACH ROW
BEGIN
    IF (NEW.lmaint_date IS NULL) THEN -- change the isnull check for the default used
        SET NEW.lmaint_date = now();
    END IF;
END//
DELIMITER ;

