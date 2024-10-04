-- drop table IF EXISTS ITEMPULL;

CREATE TABLE ITEMPULL (
ord_num           int not null,
line_num          int not null,
pull_num          smallint not null, -- normal 0, > 0 if more than 1 bin is needed
user_id           int not null,
company           smallint not null,
shadow            int not null,
zone              char(3)  default " ",
whse_loc          varchar(18) not null default " ", -- the bin to pull from
qtytopick         int not null,
qty_picked        int not null,
uom_picked	  char(3) default "EA",
qty_verified	  int not null default 0,
totes    varchar(40) null default ""
);
create unique index ITEMPULL_hash on ITEMPULL ( ord_num, line_num, pull_num );
create unique index ITEMPULL_idx1 on ITEMPULL ( whse_loc,ord_num, line_num, pull_num );

alter table ITEMPULL ADD CONSTRAINT ITEMPULL_ORDERS_FK FOREIGN KEY(ord_num)
 REFERENCES ORDERS(order_num);

