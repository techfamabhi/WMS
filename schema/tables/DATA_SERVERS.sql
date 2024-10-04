drop table IF EXISTS DATA_SERVERS;

create table DATA_SERVERS
(
 actionName varchar(64) not null,
 serverName varchar(32) not null,
 requestData text null,
 description text null,
 returnData text null
);
create unique index DATA_SERVERS_idx on DATA_SERVERS(actionName,serverName);

insert into DATA_SERVERS ( serverName, actionName, description) values (
"RcptLine.php","fetchSingle",null);
insert into DATA_SERVERS ( serverName, actionName, description) values (
"RcptLine.php","palletsToMove","looks for pallets in receiving to move");
insert into DATA_SERVERS ( serverName, actionName, description) values (
"RcptLine.php","getTote","Gets Tote Information");
insert into DATA_SERVERS ( serverName, actionName, description) values (
"RcptLine.php","getToteContents","get a totes contents");
insert into DATA_SERVERS ( serverName, actionName, description) values (
"RcptLine.php","getNewLoc","check New Location");
insert into DATA_SERVERS ( serverName, actionName, description) values (
"RcptLine.php","getToteLoc","get current tote location");
insert into DATA_SERVERS ( serverName, actionName, description) values (
"RcptLine.php","chkTask","read task info");
insert into DATA_SERVERS ( serverName, actionName, description) values (
"RcptLine.php","addTask","Add new Task");
insert into DATA_SERVERS ( serverName, actionName, description) values (
"RcptLine.php","chkBatches",null);
insert into DATA_SERVERS ( serverName, actionName, description) values (
"RcptLine.php","chkCloseBatch",null);
insert into DATA_SERVERS ( serverName, actionName, description) values (
"RcptLine.php","chkUserBatch",null);



