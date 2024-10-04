create table RMA_DTL
(
rmd_number	int not null,
rmd_line_num	smallint not null,
rmd_shadow	int not null,
rmd_pl	char(3) not null,
rmd_part_number	char(22) not null,
rmd_desc	char(25) not null,
rmd_qty	int not null,
rmd_type	char(1) not null,
rmd_act_type	char(1) not null,
rmd_last_12	int not null,
rmd_line_stat	smallint not null,
rmd_orig_comp	smallint not null,
rmd_orig_inv	char(6) not null,
rmd_orig_order	int not null,
rmd_orig_line	smallint not null,
rmd_mdse_price	money not null,
rmd_def_price	money not null,
rmd_core_price	money not null,
rmd_mdse_prsc	char(1) not null,
rmd_def_prsc	char(1) not null,
rmd_core_prsc	char(1) not null,
rmd_trackid	char(12) null,
rmd_reject_reason	smallint null,
rmd_qty_recvd	int null,
rmd_tote	char(12) null
)
;
create unique  index RMA_DTL_hash on RMA_DTL
( rmd_number, rmd_line_num )
;
create unique  index RMA_DTL_idx1 on RMA_DTL
( rmd_shadow, rmd_number, rmd_line_num )
;
create unique  index RMA_DTL_idx2 on RMA_DTL
( rmd_trackid )
;


create table RMA_HDR
(
rma_number	int not null,
rma_type	char(1) not null,
rma_customer	int not null,
rma_branch	smallint not null,
rma_company	smallint not null,
ordernumber	int not null,
rma_status	smallint not null,
rma_entdate	datetime not null,
rma_enter_by	smallint not null,
rma_placed_by	varchar(20) not null,
rma_rcvdate	datetime not null,
rma_recvd_by	smallint not null,
rma_appdate	datetime not null,
rma_approved_by	smallint not null,
rma_manual_num	char(12) not null,
rma_num_lines	smallint not null
)
;
create unique  index RMA_HDR_hash on RMA_HDR
( rma_number )
;
create unique  index RMA_HDR_idx1 on RMA_HDR
( rma_customer, rma_branch, rma_number )
;


create table WDI_ASGBIN
(
batch_num	int not null,
batch_line	int not null,
whse_loc	char(8) not null,
bin_type	char(1) not null,
shadow	int not null,
qty	int not null,
qty_avail	int not null,
qty_alloc	int not null,
line_status	smallint not null
)
;
create   index WDI_ASGBIN_hash on WDI_ASGBIN
( batch_num, batch_line )
;
create   index WDI_ASGBIN_idx1 on WDI_ASGBIN
( batch_num, shadow, batch_line )
;


create table WMS_RDTL
(
dtote_num	varchar(20) not null,
tote_line	int not null,
whse_loc	char(8) not null,
shadow	int not null,
qty	int not null,
qty_avail	int not null,
qty_alloc	int not null,
line_status	smallint not null,
mdse_type	char(1) not null,
qty_credited	int not null
)
;


create table WMS_RETURNS
(
tote_num	varchar(20) not null,
scan_date	datetime not null,
scan_by	char(20) null,
company	smallint not null,
mdse_type	char(1) not null,
batch_status	smallint null
)
;


-- table tmp_bin_xref -- USE WHSEBINS  **********************


