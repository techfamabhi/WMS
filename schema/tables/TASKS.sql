-- Needs trailing table with user and times. 
-- don't need totes, cause tote_ref would be the task# in TOTEHDR
/*
Stuff needed for receiving, returns and error putaway
1) which totes need to be putaway
2) where is the tote now
3) where does the tote need to go (to be close to where the items go)
4) who moved it to 3 (and how long did it take)
5) who put it on the shelf (and how long did it take)

1) join TASKS and TOTEHDR (TASKS.id_num references TOTEHDR.tote_ref), each move generates a task, once moved, each tote detail generates a task.
2) TOTEHDR.tote_location (normally "RCV")
3) TASKS.targets should contain Zone/aisle for putaway (type PUT)
4) 

*/

DROP TABLE IF EXISTS TASKS;
CREATE TABLE TASKS (
task_id integer auto_increment primary key,
task_type char(3) default "?", -- RCV, PUT, PIC, PAK, CHK, SHI, CNT, REP, MOV,
task_date datetime null,
task_status tinyint default 0, -- 0=open, 1=in process, 9=done
id_num int not null, -- recpt#, order#, inv task#
user_id int not null default 0,

tote_id integer default 0, -- tote id, 0 if inventory

last_zone CHAR(3) default " ",
last_loc CHAR(18) default " ",
target_zone CHAR(3) default " ",
target_aisle SMALLINT default 0,

start_time datetime null,
end_time datetime null

);

create unique index TASKS_idx1 ON TASKS (task_type,last_zone,id_num,task_id);
create unique index TASKS_idx2 ON TASKS (last_zone,task_type,id_num,task_id);
create unique index TASKS_idx3 ON TASKS (tote_id,task_type,task_id);
create unique index TASKS_idx4 ON TASKS (id_num,task_id);



/* type;
 RCV - Receiving, id_num = rcpt#
 PUT - Putaway, id_num = rcpt#
 PIC - Pick,    id_num=order#
 PAK - Packing,    id_num=order#
 CHK - Check (verify pick),    id_num=order#
 SHI - Shipping,    id_num=order#
 CNT - Physical Count or Cycle Count,  id_num=back office task #
 REP - Replenishment,  id_num=back office task #
 MOV - Move parts from bin to bin,  id_num=back office task # or 0 for adhoc
*/
