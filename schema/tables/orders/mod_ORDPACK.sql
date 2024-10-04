drop index ORDPACK_hash on ORDPACK;

create index ORDPACK_hash on ORDPACK
( order_num, carton_num, line_num );
drop  index ORDPACK_idx1 on ORDPACK;
create index ORDPACK_idx1 on ORDPACK
( order_num, line_num , carton_num );
