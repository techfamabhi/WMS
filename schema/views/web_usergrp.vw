USE wms;
drop VIEW if exists web_usergrp;


CREATE VIEW web_usergrp as 
select 
	user_id,
	username,
	passwd,
	first_name,
	last_name,
	priv_from,
	priv_thru,
	sales_rep,
	company_num,
	home_menu,
	status_flag,
	A.group_id,
        group_desc,
	theme_id,
	operator,
        host_user_id

from WEB_USERS A,WEB_GROUPS B
where B.group_id = A.group_id
;

