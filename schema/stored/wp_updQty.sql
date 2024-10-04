
drop PROCEDURE IF EXISTS wp_updQty;
DELIMITER $$
CREATE PROCEDURE wp_updQty(   
			    IN wms_trans_id   int,
                            IN shadow     int,
                            IN company    smallint,
                            IN psource      char(  10 ),
                            IN user_id     int,
                            IN host_id   char(  20 ),
                            IN ext_ref  char( 20 ),
 			    IN trans_type char(  3 ),
                            IN in_qty   int,
 			    IN uom char(  3 ),
                            IN bin  varchar(18),
                            IN toLoc  varchar(18),
                            IN inv_code  char(  1 ),
                            IN mdse_price numeric (10,3),
                            IN core_price numeric (10,3),
                            IN in_qty_core   smallint,
                            IN in_qty_def smallint,
			    IN bin_type char(1) )

BEGIN
   -- Update or insert WHSEQTY, WHSELOC and PARTHIST
   -- bin types P=Primary Bin,S=Secondary,O=Overstock, M=Moveable, ...

    DECLARE qty_onhand INT DEFAULT 0;
    DECLARE whsLoc INT ;
    DECLARE whsQty INT DEFAULT 0;
    DECLARE today      datetime;

    select qty_avail + qty_alloc into qty_onhand
    from WHSEQTY
    where ms_company = company
    and ms_shadow = shadow;
    
    insert into WHSELOC ( whs_company, whs_location, whs_shadow, whs_code, whs_qty, whs_uom)
     values (company,bin,shadow,bin_type, in_qty, uom)
    ON DUPLICATE KEY UPDATE whs_qty = whs_qty + in_qty;

    select whs_qty into whsLoc
    from WHSELOC
    where whs_company = company
    and whs_shadow = shadow
    and whs_location = bin;

    insert into WHSEQTY ( ms_shadow, ms_company, primary_bin, qty_avail,qty_core,qty_defect)
     values (shadow,company,bin,in_qty,in_qty_core,in_qty_def)
    ON DUPLICATE KEY UPDATE qty_avail = qty_avail + in_qty,
                            qty_core = qty_core + in_qty_core,
                            qty_defect = qty_defect + in_qty_def;

    select qty_avail into whsQty
    from WHSEQTY
    where ms_company = company
    and ms_shadow = shadow;

    -- set primary bin if emtpy 
    update WHSEQTY set primary_bin = bin 
    where ms_company = company
    and ms_shadow = shadow
    and primary_bin = "";

   INSERT INTO PARTHIST
         ( paud_id,                     -- order#       receiver#
	   paud_shadow,
	   paud_company,
    	   paud_date,
           paud_source,                 -- cust#        vendor          oper
	   paud_user,
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
           user_id,
           host_id,
           ext_ref,
           trans_type,
           in_qty,
	   uom,
	   bin,
           toLoc,
           qty_onhand,
           inv_code,
           mdse_price,
           core_price,
           in_qty_core,
	   in_qty_def );

    select ROW_COUNT() as rc;
                           -- commit or rollback in calling routine 

END$$
DELIMITER ;

