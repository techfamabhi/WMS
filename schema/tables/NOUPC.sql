drop table IF EXISTS NOUPC;
create table NOUPC
(
noupc_id integer auto_increment primary key,
source  varchar(3) not null default "", -- PIC,REC,INV, ...
problem  varchar(10) not null default "", 
userId int not null default 0,
refnum        varchar(20) null default "",
shadow int not null default 0,
bin        varchar(18) not null,
qty int not null default 1,
occurred datetime null,
item_status smallint null default 0 -- 0=open, 9=delete 
)
;

DROP TRIGGER IF EXISTS NOUPC_I;
DELIMITER //
CREATE TRIGGER NOUPC_I
BEFORE INSERT ON NOUPC FOR EACH ROW
BEGIN
        SET NEW.occurred = now();
END//
DELIMITER ;

