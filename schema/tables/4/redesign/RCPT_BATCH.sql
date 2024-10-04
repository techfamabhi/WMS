CREATE TABLE RCPT_BATCH (
  batch_num INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INTEGER UNSIGNED NOT NULL,
  batch_status SMALLINT NOT NULL,
  batch_date DATETIME NOT NULL,
  batch_company SMALLINT NOT NULL,
  batch_type SMALLINT NOT NULL,
  batch_to char(1) null
)
;

