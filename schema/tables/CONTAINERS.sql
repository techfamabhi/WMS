DROP TABLE IF EXISTS CONTHDR;

/* CONTAINERS -- Track Containers, Pallets
for Putaway. Stock totals are kept in WHSEQTY qty_putaway field
container_id is added from control


*/


create table CONTHDR (
container_id int primary key,
company smallint default 1,
host_id varchar(12) default " ", -- host container id if type is EXT (External)
cont_status tinyint default 0, -- -1=awaiting print, 0=unused, 1=in use, 2=parked, 3=shipped, 4=returns
cont_location varchar(18) default " ", -- either zone or location
cont_lastused datetime null,
contitems int default 0,
cont_type char(3) null, -- RCV, ORD, INV, EXT
cont_ref int null -- order#, po# or task num
);
create unique index CONTHDR_idx1 on CONTAINERS (company,cont_location,container_id);
create unique index CONTHDR_idx2 on CONTAINERS (company,container_id);

DROP TABLE IF EXISTS CONTDTL;
create table CONTDTL (
cond_id int not null,
cond_item smallint default 0,
cond_shadow int default 0,
cond_qty int default 0,
cond_uom char(3) default "EA",
cond_status tinyint default 0, -- 1=Inv adjusted, 9=delete
cond_loc varchar(18) default " " -- bin location after move
);

/* when last item for a tote is deleted, set CONTHDR.tote_status to 0 */

create unique index CONTDTL_hash on CONTDTL (cond_id,cond_item);
create unique index CONTDTL_idx1 on CONTDTL (cond_shadow,cond_id,cond_item);

