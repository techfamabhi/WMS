drop table IF EXISTS DROPSHIP;

CREATE TABLE DROPSHIP (
order_num int not null primary key,
name varchar(40) NULL,
addr1 varchar(40) NULL,
addr2 varchar(40) NULL,
city varchar(25) NULL,
state char(2) NULL,
zip char(10) NULL,
ctry char(3) NULL,
phone varchar(20) NULL,
email varchar(128) NULL

);

alter table DROPSHIP ADD CONSTRAINT DRP_ORDERS_FK FOREIGN KEY(order_num)
 REFERENCES ORDERS(order_num);

