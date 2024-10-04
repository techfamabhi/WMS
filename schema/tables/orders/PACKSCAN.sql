/* PACKSCAN -- packing scan verification for an order
   can get the part# by joining ITEMS
   the same part may be in many records
*/

drop table IF EXISTS PACKSCAN;

CREATE TABLE PACKSCAN (
ord_number   int  not null,
line_num     int not null,
shadow       int not null,
qty_scan     int null default 0,
checker      int  null default 0,
scan_line    int null default 0, -- scan increment
scan_tote    int null default 0, -- tote number if present
uom char(3) default "EA"
);

create unique index PACKSCAN_hash on PACKSCAN
( ord_number, shadow, scan_line );
create unique index PACKSCAN_idx1 on PACKSCAN
( ord_number, line_num, scan_line );

alter table PACKSCAN ADD CONSTRAINT PACKSCAN_ORD_FK FOREIGN KEY(ord_number)
 REFERENCES ORDERS(order_num);


