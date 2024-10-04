USE wms;


CREATE TABLE WEB_USERS (
	user_id integer auto_increment primary key,
	username varchar(16) NULL,
	passwd varchar(16) NULL,
	first_name varchar(20) NULL,
	last_name varchar(24) NULL,
	priv_from tinyint NULL,
	priv_thru tinyint NULL,
	sales_rep smallint NULL,
	company_num smallint NULL,
	home_menu smallint NULL,
	status_flag char(1) NULL,
	group_id smallint NULL,
	theme_id numeric(9) NULL,
	operator smallint NULL
);

CREATE UNIQUE INDEX WEB_USERS_hash on WEB_USERS(username,passwd,user_id);

ALTER TABLE WEB_USERS
	ADD CONSTRAINT WEB_USER_GROUPS_FK FOREIGN KEY (group_id) 
	REFERENCES WEB_GROUPS (group_id);

