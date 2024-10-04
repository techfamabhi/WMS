drop table IF EXISTS  USERPID;

create table USERPID (
  user_id integer PRIMARY KEY,
  remote_address varchar(32),
  last_url varchar(255),
  device_type varchar(255),
  CONSTRAINT `USERPID_FK` FOREIGN KEY (`user_id`) REFERENCES `WEB_USERS` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

