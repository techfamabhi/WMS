drop table IF EXISTS ORDMESSG;

CREATE TABLE ORDMESSG (
order_num        int not null,
line_num      int not null,
message_num  smallint not null,
message      varchar(255)
);

create unique index ORDMESSG_hash on ORDMESSG ( order_num, line_num, message_num );

alter table ORDMESSG ADD CONSTRAINT OMSG_ORDERS_FK FOREIGN KEY(order_num)
 REFERENCES ORDERS(order_num);

