drop table IF EXISTS ITEMS;

CREATE TABLE ITEMS (
ord_num           int not null,
line_num          int not null,
shadow            int not null,
p_l               char(6) not null,
part_number       varchar(22) not null,
part_desc         varchar(30) default " ",
uom               char(3) not null,
qty_ord           int not null,
qty_ship          int not null,
qty_bo            int not null,
qty_avail         int not null, -- at time of order
min_ship_qty      int default 1,
case_qty          int default 1,
inv_code          char(1) default "0",
line_status       smallint default 0,
hazard_id         char(3) default " ",
zone              char(3)  default " ",
whse_loc          varchar(18) not null default " ", -- the bin to pull from
qty_in_primary    int default 0,
num_messg         smallint default 0,
part_weight       numeric(10,2) default 0.00, 
part_subline      char(3) default " ",
part_category     char(4) default " ",
part_group        char(5) default " ",
part_class        char(3) default " ",
item_pulls	  smallint default 0, -- # of item pulls
inv_comp	  smallint null,
specord_num       varchar(20) default "" -- if special order
);

create unique index ITEMS_hash on ITEMS ( ord_num, line_num );
create index ITEMS_idx01 on ITEMS ( zone, whse_loc, ord_num,p_l,part_number);
create unique index ITEMS_idx02 on ITEMS ( shadow, line_status, ord_num);

alter table ITEMS ADD CONSTRAINT ITEMS_ORDERS_FK FOREIGN KEY(ord_num)
 REFERENCES ORDERS(order_num);

