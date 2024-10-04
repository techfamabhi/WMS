-- Used for Vendors and Customers

USE wms;

drop TABLE IF EXISTS ENTITY ;

CREATE TABLE ENTITY (
        entity_num INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
        entity_type char(1) NOT NULL, -- C=Customer V=Vendor
        host_id varchar(20) NOT NULL , -- Host cust# or Vendor ID
        name varchar(40) NULL,
        addr1 varchar(40) NULL,
        addr2 varchar(40) NULL,
        city varchar(25) NULL,
        state char(2) NULL,
        zip char(10) NULL,
        ctry char(3) NULL,
        contact varchar(30) NULL,
        phone varchar(20) NULL,
        email varchar(128) NULL,
        ship_via char(4) NULL, -- Primarily Used on Customers
        num_notes int NULL,
        allow_bo char(1) NULL,
        last_trans datetime NULL,
        allow_to_bin char(1) NULL DEFAULT "N", -- on Vendors, allows recving direct to Bin
        allow_inplace char(1) NULL DEFAULT "N" -- if set to "Y", 
                  -- this allows PO's for this Vendor to be
                  -- scanned and received on the pallet that was packed by
                  -- the Vendor.  
                  -- Useful for heavy or bulk items like drive axles, etc.

);
create unique index ENTITY_hash on ENTITY ( entity_type, host_id);
create unique index ENTITY_idx1 on ENTITY ( host_id, entity_type );



drop VIEW IF EXISTS VENDORS ;
CREATE VIEW VENDORS as
select
        host_id as vendor,
	name,
	addr1,
	addr2,
	city,
	state,
	zip,
	ctry,
	contact,
	phone,
	email,
	num_notes,
        last_trans as last_rcpt,
	allow_bo,
        allow_to_bin,
	allow_inplace
from ENTITY
where entity_type = "V"
;
drop VIEW IF EXISTS CUSTOMERS ;
CREATE VIEW CUSTOMERS as
select
        host_id as customer,
	name,
	addr1,
	addr2,
	city,
	state,
	zip,
	ctry,
	contact,
	phone,
	email,
	ship_via,
	num_notes,
        last_trans,
	allow_bo
from ENTITY
where entity_type = "C"
;
