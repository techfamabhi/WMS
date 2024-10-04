USE wms;

DROP TABLE IF EXISTS WMSLOCK ;

-- lockrow is the key to the record in tableName
-- if the key has multiple fields, the value is set of field and value pairs
-- separated by ";"
-- field:value;field:value;...
-- operation is what the user is doing, like Pick, Move, Count, etc

CREATE TABLE WMSLOCK (
	tableName varchar(48) default "",
	lockRow varchar(255) default "",
	operation varchar(10) default "",
	userId int default 0,
	dateAdded datetime NOT NULL 
);

CREATE UNIQUE INDEX WMSLOCK_hash ON WMSLOCK (tableName,lockRow);

