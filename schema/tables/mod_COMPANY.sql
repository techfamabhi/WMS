alter table COMPANY
add COLUMN host_company char(6) NULL
;

update COMPANY set host_company = CONVERT(company_number,char(6));

;
