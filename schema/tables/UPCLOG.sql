drop table IF EXISTS UPCLOG;
create table UPCLOG
(
noupc_id integer auto_increment primary key,
source  varchar(3) not null default "", -- REC, ? ...
upc  varchar(20) not null default "", 
userId int not null default 0,
shadow int not null default 0,
qty int not null default 1,
occurred timestamp null,
upc_status smallint null default 0 -- 0=logged, 8=sent to host, 9=delete 
)
;

DROP TRIGGER IF EXISTS UPCLOG_I;
DELIMITER //
CREATE TRIGGER UPCLOG_I
BEFORE INSERT ON UPCLOG FOR EACH ROW
BEGIN
        SET NEW.occurred = now();
END//
DELIMITER ;

