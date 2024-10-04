-- drop table IF EXISTS INV_BATCH;

create table INV_BATCH
(
 count_num       int not null,
 company smallint not null,
 create_by int not null default 0,
 create_date       datetime not null,
 due_date       datetime not null,
 count_status    smallint not null default 0,
 count_type smallint default 0 -- 0=initial inv, scan bin then all parts in it
                                -- > 0 future
)
;

create unique index INV_BATCH_hash on INV_BATCH
( count_num, company )
;


