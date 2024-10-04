

alter table RCPT_SCAN add qty_stockd int null
;
update RCPT_SCAN set qty_stockd = totalQty
where recv_to = "b"
;
