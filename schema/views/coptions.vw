USE wms;

DROP VIEW IF EXISTS coptions ;

CREATE VIEW coptions as 
select cop_company,
       cop_option,
       copt_desc,
       cop_flag,
       copt_cat
from COPTIONS A,COPTDESC B
where B.copt_number = A.cop_option
;

