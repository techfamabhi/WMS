drop index RCPT_SCAN_idx1 on RCPT_SCAN;

create unique index RCPT_SCAN_idx1 on RCPT_SCAN (batch_num, shadow, line_num, pkgUOM);

