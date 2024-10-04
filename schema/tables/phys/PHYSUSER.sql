drop table if exists  PHYSUSER;

create table PHYSUSER
(
 phys_num int,
 user_id int,
 last_action datetime,
 bins_visited int,
 uniq_parts int
);
create unique index PHYSUSER_hash ON PHYSUSER (phys_num,user_id);
