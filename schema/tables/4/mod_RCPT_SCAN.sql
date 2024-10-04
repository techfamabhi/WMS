 ALTER TABLE RCPT_SCAN DROP FOREIGN KEY RCPT_SCAN_FK;

sleep(1);

 drop index RCPT_SCAN_hash on RCPT_SCAN;
sleep(1);
 drop index RCPT_SCAN_idx1 on RCPT_SCAN;
sleep(1);


create unique index RCPT_SCAN_hash on RCPT_SCAN (batch_num, line_num,scan_user,pkgUOM);
create unique index RCPT_SCAN_idx1 on RCPT_SCAN (batch_num, shadow, line_num,scan_user, pkgUOM);
