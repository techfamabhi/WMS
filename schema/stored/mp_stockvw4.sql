delimiter //

create procedure mp_stockvw4( p_paud_fcomp    int,
                              p_paud_tcomp    int,
                             p_paud_src     varchar( 32 ),
                             p_paud_shdw    int,
                             p_paud_typ     varchar( 20 ),
                             p_start_rec    int,
                             p_numrecs int /* = 12 */,
                             p_pl char(3) /* = "%" */,
                             p_po varchar(15) /* = "%" */,
                             p_ref char(8) /* = "%" */)
sp_lbl:
begin
declare not_found int default 0;
declare continue handler for not found set not_found = 1;

/***********************************************************
*   DECLARE CURSORS AND SQL VARIABLES                      *
************************************************************/
    set p_numrecs = p_numrecs - 1 /*  starting at zero */

    create table #svresult
    (
     rec_num int null,
     paud_date datetime null,
     paud_company smallint null,
     paud_type char(1) null,
     paud_id int null,
     paud_ref char(6) null,
     paud_salesgrp smallint null,
     paud_source char(6) null,
     paud_ext_ref char(15) null,
     paud_shadow int null,
     paud_linetype char(1) null,
     paud_qty int null,
     paud_qty_returnd int null,
     paud_qty_core int null,
     paud_onhand int null,
     paud_price money null,
     paud_core_price money null,
     paud_num int null,
     p_l char(3) null,
     part_number char(22) null,
     part_desc char(25) null,
     ord_hdr_bucket smallint null

    )

   
   
   
    declare v_sccs_id          char( 40 );
            declare v_paud_date        datetime(3);
            declare v_paud_company     smallint;
            declare v_paud_type        char(  1 );
            declare v_paud_id          int;
            declare v_paud_ref         char(  6 );
            declare v_paud_salesgrp    smallint;
            declare v_paud_source      char(  6 );
            declare v_paud_ext_ref     char( 15 );
            declare v_paud_shadow      int;
            declare v_paud_linetype    char(  1 );
            declare v_paud_qty         int;
            declare v_paud_qty_returnd int;
            declare v_paud_qty_core    int;
            declare v_paud_onhand      int;
            declare v_paud_price       decimal;
            declare v_paud_core_price  decimal;
            declare v_paud_num         int;
            declare v_p_l              char(  3 );
            declare v_part_number      char( 22 );
            declare v_part_desc        char( 25 );
            declare v_ord_hdr_bucket   smallint;
            declare v_rec_count        int;

/***********************************************************
*   INITIALIZE LOCAL VARIABLES                             *
***********************************************************/

    set nocount on

    select v_sccs_id = '@(#)mp_stockvw4   1.1 - 12/30/20'

    set v_rec_count = 0;

/***********************************************************
*   LOOP THROUGH THE PARTHIST/PARTS JOIN BY CUST/SHDW/COMP SEQUENCE  *
**********************************************************************/

    if ( p_paud_src <> `%` and p_paud_shdw is not NULL )
        then

        open sv_cust_part;

        fetch sv_cust_part into
            v_paud_date,
            v_paud_company,
            v_paud_type,
            v_paud_id,
            v_paud_ref,
            v_paud_salesgrp,
            v_paud_source,
            v_paud_ext_ref,
            v_paud_shadow,
            v_paud_linetype,
            v_paud_qty,
            v_paud_qty_returnd,
            v_paud_qty_core,
            v_paud_onhand,
            v_paud_price,
            v_paud_core_price,
            v_paud_num,
            v_p_l,
            v_part_number,
            v_part_desc,
            v_ord_hdr_bucket;

       while ( Not_found = 0 )
            do
            set v_rec_count = v_rec_count + 1;

            if ( v_rec_count >= p_start_rec )
                then
                insert into #svresult values( v_rec_count, v_paud_date,
                       v_paud_company,
                       v_paud_type,
                       v_paud_id,
                       v_paud_ref,
                       v_paud_salesgrp,
                       v_paud_source,
                       v_paud_ext_ref,
                       v_paud_shadow,
                       v_paud_linetype,
                       v_paud_qty,
                       v_paud_qty_returnd,
                       v_paud_qty_core,
                       0,               -- paud_onhand
                       v_paud_price,
                       v_paud_core_price,
                       v_paud_num,
                       v_p_l,
                       v_part_number,
                       v_part_desc,
                       v_ord_hdr_bucket);

                if ( ( v_rec_count - p_start_rec ) = p_numrecs ) then goto
                end if; EXIT_LOOP_0

                end if;

/***********************************************************
*   GET THE NEXT RECORD IN THE LOOP                        *
************************************************************/

            fetch sv_cust_part into
                v_paud_date,
                v_paud_company,
                v_paud_type,
                v_paud_id,
                v_paud_ref,
                v_paud_salesgrp,
                v_paud_source,
                v_paud_ext_ref,
                v_paud_shadow,
                v_paud_linetype,
                v_paud_qty,
                v_paud_qty_returnd,
                v_paud_qty_core,
                v_paud_onhand,
                v_paud_price,
                v_paud_core_price,
                v_paud_num,
                v_p_l,
                v_part_number,
                v_part_desc,
                v_ord_hdr_bucket;

            if ( Not_found = then 2
            end if; ) goto EXIT_LOOP_0

            end while;

        EXIT_LOOP_0:

        close sv_cust_part;
        goto EXIT_LOOP_ALL

/***********************************************************
*   LOOP THROUGH THE PARTHIST/PARTS JOIN BY CUST/COMP SEQUENCE   *
******************************************************************/

    elseif ( p_paud_src <> '%')
        then

        set not_found = 0;
        open sv_cust;

        fetch sv_cust into
            v_paud_date,
            v_paud_company,
            v_paud_type,
            v_paud_id,
            v_paud_ref,
            v_paud_salesgrp,
            v_paud_source,
            v_paud_ext_ref,
            v_paud_shadow,
            v_paud_linetype,
            v_paud_qty,
            v_paud_qty_returnd,
            v_paud_qty_core,
            v_paud_onhand,
            v_paud_price,
            v_paud_core_price,
            v_paud_num,
            v_p_l,
            v_part_number,
            v_part_desc,
            v_ord_hdr_bucket;

        while ( Not_found = 0 )
            do
            set v_rec_count = v_rec_count + 1;

            if ( v_rec_count >= p_start_rec )
                then
                insert into #svresult values( v_rec_count, v_paud_date,
                       v_paud_company,
                       v_paud_type,
                       v_paud_id,
                       v_paud_ref,
                       v_paud_salesgrp,
                       v_paud_source,
                       v_paud_ext_ref,
                       v_paud_shadow,
                       v_paud_linetype,
                       v_paud_qty,
                       v_paud_qty_returnd,
                       v_paud_qty_core,
                       0,               -- paud_onhand
                       v_paud_price,
                       v_paud_core_price,
                       v_paud_num,
                       v_p_l,
                       v_part_number,
                       v_part_desc,
                       v_ord_hdr_bucket);

                if ( ( v_rec_count - p_start_rec ) = p_numrecs ) then goto
                end if; EXIT_LOOP_1

                end if;

/***********************************************************
*   GET THE NEXT RECORD IN THE LOOP                        *
************************************************************/

            fetch sv_cust into
                v_paud_date,
                v_paud_company,
                v_paud_type,
                v_paud_id,
                v_paud_ref,
                v_paud_salesgrp,
                v_paud_source,
                v_paud_ext_ref,
                v_paud_shadow,
                v_paud_linetype,
                v_paud_qty,
                v_paud_qty_returnd,
                v_paud_qty_core,
                v_paud_onhand,
                v_paud_price,
                v_paud_core_price,
                v_paud_num,
                v_p_l,
                v_part_number,
                v_part_desc,
                v_ord_hdr_bucket;

            if ( Not_found = then 2
            end if; ) goto EXIT_LOOP_1

            end while;

        EXIT_LOOP_1:

        close sv_cust;
        goto EXIT_LOOP_ALL

/***********************************************************
*   LOOP THROUGH THE PARTHIST/PARTS JOIN BY SHDW/COMP SEQUENCE  *
*****************************************************************/

    elseif ( p_paud_shdw is not NULL )
        then

        set not_found = 0;
        open sv_part;

        fetch sv_part into
            v_paud_date,
            v_paud_company,
            v_paud_type,
            v_paud_id,
            v_paud_ref,
            v_paud_salesgrp,
            v_paud_source,
            v_paud_ext_ref,
            v_paud_shadow,
            v_paud_linetype,
            v_paud_qty,
            v_paud_qty_returnd,
            v_paud_qty_core,
            v_paud_onhand,
            v_paud_price,
            v_paud_core_price,
            v_paud_num,
            v_p_l,
            v_part_number,
            v_part_desc,
            v_ord_hdr_bucket;

        while ( Not_found = 0 )
            do
            set v_rec_count = v_rec_count + 1;

            if ( v_rec_count >= p_start_rec )
                then
                insert into #svresult values( v_rec_count, v_paud_date,
                       v_paud_company,
                       v_paud_type,
                       v_paud_id,
                       v_paud_ref,
                       v_paud_salesgrp,
                       v_paud_source,
                       v_paud_ext_ref,
                       v_paud_shadow,
                       v_paud_linetype,
                       v_paud_qty,
                       v_paud_qty_returnd,
                       v_paud_qty_core,
                       v_paud_onhand,
                       v_paud_price,
                       v_paud_core_price,
                       v_paud_num,
                       v_p_l,
                       v_part_number,
                       v_part_desc,
                       v_ord_hdr_bucket);

                if ( v_rec_count - p_start_rec ) = p_numrecs then goto
                end if; EXIT_LOOP_2

                end if;

/***********************************************************
*   GET THE NEXT RECORD IN THE LOOP                        *
************************************************************/

            fetch sv_part into
                v_paud_date,
                v_paud_company,
                v_paud_type,
                v_paud_id,
                v_paud_ref,
                v_paud_salesgrp,
                v_paud_source,
                v_paud_ext_ref,
                v_paud_shadow,
                v_paud_linetype,
                v_paud_qty,
                v_paud_qty_returnd,
                v_paud_qty_core,
                v_paud_onhand,
                v_paud_price,
                v_paud_core_price,
                v_paud_num,
                v_p_l,
                v_part_number,
                v_part_desc,
                v_ord_hdr_bucket;

            if ( Not_found = then 2
            end if; ) goto EXIT_LOOP_2

            end while;

        EXIT_LOOP_2:
        close sv_part;
        goto EXIT_LOOP_ALL
    end if;

        EXIT_LOOP_ALL:
select
rec_num,
date_format(paud_date,'%m/%d/%y') as paud_date,
               paud_company,
               paud_type,
               paud_id,
               paud_ref,
               paud_salesgrp,
               paud_source,
               paud_ext_ref,
               paud_shadow,
               paud_linetype,
               paud_qty,
               paud_qty_returnd,
               paud_qty_core,
               paud_onhand,
               paud_price,
               paud_core_price,
               paud_num,
               p_l,
               part_number,
               part_desc,
               ord_hdr_bucket from #svresult
               order by rec_num;
        leave sp_lbl 0;


/* END  OF DEFINITION */

end;
//

delimiter ;


grant execute on mp_stockvw4 to public;
 
