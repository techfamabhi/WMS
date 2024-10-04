create table ORDQUE 
(
 order_num int not null primary key,
 que_key char(3) not null, /* WAI=Waiting,
			      REL=Release to floor, 
			      PSL=Packing Slip, 
			      SHL=Ship Label, 
			      SND=Send, 
			      DEL=Complete */
 que_data varchar(255) default "" -- task specific params
);
