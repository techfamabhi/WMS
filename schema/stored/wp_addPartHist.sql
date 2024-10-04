
drop PROCEDURE IF EXISTS wp_addPartHist;
DELIMITER $$
CREATE PROCEDURE wp_addPartHist(   
			    IN wms_trans_id   int,
                            IN shadow     int,
                            IN company    smallint,
                            IN psource      char(  10 ),
                            IN host_id   char(  20 ),
                            IN ext_ref  char( 20 ),
 			    IN trans_type char(  3 ),
                            IN qty_mdse   int,
 			    IN uom char(  3 ),
                            IN floc  varchar(18),
                            IN tloc  varchar(18),
                            IN inv_code  char(  1 ),
                            IN mdse_price numeric (10,3),
                            IN core_price numeric (10,3),
                            IN qty_core   smallint,
                            IN qty_def smallint )

BEGIN

    DECLARE qty_onhand INT DEFAULT 0;
    DECLARE whsLoc INT DEFAULT 0;
    DECLARE whsQty INT DEFAULT 0;
    DECLARE today      datetime;

    select qty_avail + qty_alloc into qty_onhand
    from WHSEQTY
    where ms_company = company
    and ms_shadow = shadow;

    select count(*) into whsLoc
    from WHSELOC
    where whs_company = company
    and whs_shadow = shadow;


   INSERT INTO PARTHIST
         ( paud_id,                     -- order#       receiver#
	   paud_shadow,
	   paud_company,
    	   paud_date,
           paud_source,                 -- cust#        vendor          oper
           paud_ref,                    -- invoice#     po#
           paud_ext_ref,                -- cust po#     vendor invc#
	   paud_type,
	   paud_qty,
	   paud_uom,
	   paud_floc,
	   paud_tloc,
    	   paud_prev_qty,
	   paud_inv_code,
	   paud_price,
	   paud_core_price,
	   paud_qty_core,
	   paud_qty_def )
        VALUES
         ( wms_trans_id,
           shadow,
           company,
	   today,
           psource,
           host_id,
           ext_ref,
           trans_type,
           qty_mdse,
	   uom,
	   floc,
	   tloc,
           qty_onhand,
           inv_code,
           mdse_price,
           core_price,
           qty_core,
	   qty_def );

    select ROW_COUNT() as rc;
                           -- commit or rollback in calling routine 

END$$
DELIMITER ;

