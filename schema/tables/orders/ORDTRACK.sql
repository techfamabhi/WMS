drop table IF EXISTS ORDTRACK;

CREATE TABLE ORDTRACK (
order_num int not null,
line_num smallint not null,
user_id int not null,
track_type char(3) not null, -- PIC, PAK, CHK, SHI, etc
zone char(3),
num_lines int default 0,
nim_units int default 0

);
create unique index ORDTRACK_hash on ORDTRACK
( order_num, line_num , user_id, track_type, zone );

alter table ORDTRACK ADD CONSTRAINT OTRK_ORDERS_FK FOREIGN KEY(order_num)
 REFERENCES ORDERS(order_num);

