drop table IF EXISTS ORDERS;

CREATE TABLE ORDERS (

order_num        integer auto_increment primary key,
company          smallint not null,
order_type       char(2) not null, -- O=Order, C=Credit Memo, D=Debit Memo, T=transfer
host_order_num   varchar(20) not null, -- or debit#
customer_id      varchar(20) not null, -- or vendor if DM
cust_po_num      varchar(20) null default " ", -- customer PO number
enter_by         varchar(15) null default " ", -- host enter_by
enter_date       datetime not null, -- on host system
wms_date         datetime not null, -- on wms system
pic_release      datetime,
pic_done         datetime,
wms_complete     datetime,
date_required    datetime not null,
priority         smallint null default 1, -- 0 is highest, 99 is lowest
ship_complete    char(1) null default "N",
order_stat       smallint null default 0, -- -1 awaiting product, 0=Open, 1=scheduling, 2=in proc, 3=Pack, 4=Ship, 5=reserved, 6=sent, 7=compl, 9=done/delete
num_lines        int default 0,
spec_order_num   varchar(20) null default "", -- if special order
mdse_type        smallint default 1, -- 0-non-inv, 1=mdse, 2=core, 3=defect
ship_via         char(4) null default "",
conveyor         varchar(20) null default "", -- conveyor to divert to
drop_ship_flag   tinyint, -- if 1, drop ship record exists
special_instr    varchar(24),
shipping_instr   varchar(24),
zones            varchar(128) null,
o_num_pieces     int ,
messg            text null,
track_recs       smallint -- number of picker/checker/packer recs

);

create unique index ORDERS_hash on ORDERS ( order_num, company );

create unique index ORDERS_index004 on ORDERS (customer_id ,order_num);

create unique index ORDERS_index008 on ORDERS (company, order_stat, customer_id, order_num);
create unique index ORDERS_index022 on ORDERS (company, wms_complete, order_num);
create unique index ORDERS_index023 on ORDERS (company, enter_date, order_num);
create unique index ORDERS_index029 on ORDERS (company, order_stat, order_num);
create unique index ORDERS_cust on ORDERS (customer_id, order_num );


