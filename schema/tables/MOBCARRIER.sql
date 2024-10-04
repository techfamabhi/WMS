-- table for mobile carrier domain names to send texts as emails to number@carrier
create table MOBCARRIER (
country	char(3) not null,
carrier char(20) not null,
SMS	varchar(30) null,
MMS	varchar(30) null
);

create unique index MOBCARRIER_hash on MOBCARRIER (country,carrier)
;

