DROP TABLE IF EXISTS TOTEHDR;

/* TOTES -- Track Carts, Totes, packs, etc and their contents
When generating new totes, status is set to -1
Printing the barcodes moves it to status 0

*/


create table TOTEHDR (
tote_id int primary key,
tote_code varchr(18) default " ", -- tote barcode 
tote_company smallint default 1,
tote_status tinyint default 0, -- -1=awaiting print, 0=unused, 1=in use, 2=parked, 3=shipped, 4=returns
tote_location varchar(18) default " ", -- either zone or location
tote_lastused datetime null,
num_items int default 0,
tote_type char(3) null, -- RCV, ORD, INV, etc, TMP
tote_ref int default 0 -- order#, po# or task num
);
create unique index TOTEHDR_idx1 on TOTEHDR (tote_company,tote_location,tote_id);
create unique index TOTEHDR_idx2 on TOTEHDR (tote_company,tote_id);
create unique index TOTEHDR_idx3 on TOTEHDR ( tote_company, tote_ref, tote_type, tote_id);
create unique index TOTEHDR_idx4 on TOTEHDR (tote_company,tote_code);

DROP TABLE IF EXISTS TOTEDTL;
create table TOTEDTL (
tote_id int not null,
tote_item smallint default 0,
tote_shadow int default 0,
tote_qty int default 0,
tote_uom char(3) default "EA"
);

/* when last item for a tote is deleted, set TOTEHDR.tote_status to 0 */

create unique index TOTEDTL_hash on TOTEDTL (tote_id,tote_item);
create unique index TOTEDTL_idx1 on TOTEDTL (tote_shadow,tote_id,tote_item);

