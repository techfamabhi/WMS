drop table if exists  PHYSHDR;
create table PHYSHDR
(
  phys_num int not null,
  company int,
  create_date datetime,
  num_items int default 0,
  phys_status tinyint default 0,
  start_date datetime,
  end_date datetime
);

create unique index PHYSHDR_hash ON PHYSHDR (company,phys_num);

