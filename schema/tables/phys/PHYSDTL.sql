drop table if exists  PHYSDTL;

create table PHYSDTL
(
 phys_num int,
 line_num int,
 shadow int,
 location char(18),
 loc_type char(1),
 qty_avail int default 0,
 qty_alloc int default 0,
 counted1 int default 0,
 recount int default 0,
 last_user int default 0,
 pd_status tinyint default 0
);
create unique index PHYSDTL_hash ON PHYSDTL (phys_num,line_num);
create unique index PHYSDTL_idx1 ON PHYSDTL (shadow,phys_num);

