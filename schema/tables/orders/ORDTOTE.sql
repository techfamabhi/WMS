/* Track which totes are used for each order */

drop table IF EXISTS ORDTOTE;

CREATE TABLE ORDTOTE (
order_num        int not null,
tote_id      int not null,
last_zone char(3) null default " ",
last_loc char(18) null default "",
tote_status smallint default 0
);

create unique index ORDTOTE_hash on ORDTOTE ( order_num, tote_id );
create unique index ORDTOTE_idx1 on ORDTOTE ( tote_id,order_num );

alter table ORDTOTE ADD CONSTRAINT OTOT_ORDERS_FK FOREIGN KEY(order_num)
 REFERENCES ORDERS(order_num);

/*
by joining TOTEDTL, I can get line#, Part#, qty, etc...
 
*/
