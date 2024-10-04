drop table IF EXISTS INV_USERS;

create table INV_USERS
(
 count_num       int not null,
 userId int not null default 0,
 last_bin        char(8) not null,
 last_access       datetime not null,
 scan_count int default 0
)
;

create unique index INV_USERS_hash on INV_USERS
( count_num, userId )
;


