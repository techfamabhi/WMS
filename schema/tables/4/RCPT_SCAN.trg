DROP TRIGGER IF EXISTS RCPT_SCAN_I;
DELIMITER //
CREATE TRIGGER RCPT_SCAN_I
BEFORE INSERT ON RCPT_SCAN FOR EACH ROW
BEGIN
    insert into RCPT_USER
    (batch_num, user_id, user_status, user_action, last_action,scans)
    values ( NEW.batch_num, NEW.scan_user, 0, "RCV", NOW(),1)
    ON DUPLICATE KEY UPDATE 
    last_action = NOW(), scans = scans + 1;
END//
DELIMITER ;

DROP TRIGGER IF EXISTS RCPT_SCAN_U;
DELIMITER //
CREATE TRIGGER RCPT_SCAN_U
BEFORE UPDATE ON RCPT_SCAN FOR EACH ROW
BEGIN
    insert into RCPT_USER
    (batch_num, user_id, user_status, user_action, last_action,scans)
    values ( NEW.batch_num, NEW.scan_user, 0, "RCV", NOW(),1)
    ON DUPLICATE KEY UPDATE
    last_action = NOW(), scans = scans + 1;
END//
DELIMITER ;


