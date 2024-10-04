drop table if exists INV_SCAN;
create table INV_SCAN
(
count_num       int not null, -- count 0 is always System count
count_line      int not null,
userId      int not null default 0,
whse_loc        varchar(18) not null default " ",
bin_type        char(1) not null, -- Primary, Overstock, etc
shadow  int not null,
qty     int not null,
uom char(3) not null,
bin_avail       int not null,
bin_alloc       int not null,
qty_avail       int not null,
qty_alloc       int not null,
line_status     smallint not null,
reason	varchar(40) default " "
)
;
create   index INV_SCAN_hash on INV_SCAN
( count_num, count_line )
;
create   index INV_SCAN_idx1 on INV_SCAN
( count_num, shadow, whse_loc, count_line )
;

