drop table IF EXISTS INV_ERROR;
create table INV_ERROR
(
count_num       int not null,
ex_type smallint not null, -- 0=no UPC, 1=Invalid UPC,2=Invalid part
last_bin        varchar(18) not null,
this_bin        varchar(18) not null,
upc     char(20) not null,
p_l char(6) default " ",
part_number char(22) default " ",
qty int not null
)
;

