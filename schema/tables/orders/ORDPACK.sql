/* ORDPACK -- store packing info for an order
   can get the part# by joining ITEMS
   The qty shipped of a part may be in several cartions
*/

drop table IF EXISTS ORDPACK;

CREATE TABLE ORDPACK (
order_num int not null,
carton_num smallint not null,
line_num smallint not null,
shadow int not null,
qty int default 0,
uom char(3) default "EA"
);

create index ORDPACK_hash on ORDPACK
( order_num, carton_num, line_num );
create index ORDPACK_idx1 on ORDPACK
( order_num, line_num , carton_num );
create index ORDPACK_idx2 on ORDPACK
( order_num, shadow , carton_num );

alter table ORDPACK ADD CONSTRAINT OTRK_ORDERS_FK FOREIGN KEY(order_num)
 REFERENCES ORDERS(order_num);


