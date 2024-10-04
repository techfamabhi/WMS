drop PROCEDURE IF EXISTS test_trans;
DELIMITER $$
CREATE PROCEDURE test_trans(
    IN ctrl_comp SMALLINT,
    IN ctrl_key VARCHAR(8)
)
BEGIN
    DECLARE rc SMALLINT DEFAULT 0;
    DECLARE rc1 SMALLINT DEFAULT 0;

    START TRANSACTION;

    update CONTROL set control_number = control_number + 1
    where control_company = ctrl_comp and control_key = ctrl_key;

    set rc=ROW_COUNT();

    COMMIT WORK;
    select control_number into rc1 from CONTROL
    where control_company = ctrl_comp and control_key = ctrl_key;
    
    select rc1;

END$$
DELIMITER ;

