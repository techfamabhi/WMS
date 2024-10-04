


drop index RCPT_SCAN_hash on RCPT_SCAN;
drop index RCPT_SCAN_idx1 on RCPT_SCAN;



create unique index RCPT_SCAN_hash on RCPT_SCAN (batch_num, line_num);
create unique index RCPT_SCAN_idx1 on RCPT_SCAN (batch_num, shadow, line_num);
