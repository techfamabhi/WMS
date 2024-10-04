
create table WHSEBINS (
 wb_company     smallint,
 wb_location    char(18), 
 wb_zone        char(3) default ' ',
 wb_aisle       char(4) default ' ',
 wb_section     char(3) default ' ', -- rack #
 wb_level       char(3) default ' ', -- shelf
 wb_subin       char(3) default ' ',
 wb_width      smallint NULL, -- was length
 wb_depth       smallint NULL, -- was width
 wb_height      smallint NULL,
 wb_volume numeric(10,2) NULL,
 wb_pick      tinyint NULL, -- is pickable
 wb_recv      tinyint NULL, -- is allowed receiving
 wb_status    char(1) -- A=Active, I=inactive, D=deleted
);
create unique index WHSEBINS_hash on WHSEBINS (wb_company,wb_location);
create unique index WHSEBINS_idx1 on WHSEBINS (wb_location,wb_company);

-- need to update 
-- HOST to WMS doc
-- servers/RcptLine.php
-- rf/bincheck.php
-- include/cl_bins.php
-- WHSE/bin_upl.php
-- WHSE/proc_bins.php

-- Config settings for each of the following fields
--  part 1 = wb_zone
--  part 2 = wb_aisle 
--  part 3 = wb_section
--  part 4 = wb_level
--  part 5 = wb_subin

-- Each setting is 1 character, it denotes the type and length of field
--             A = alpha
--             N = numeric 
--             T = numeric zero pad to 2 digits
--             W = numeric zero pad to 3 digits
--             Z = numeric zero pad to 4 digits (only valid on aisle)
--             blank = not used

-- Display settings
-- If lower case letter, add a dash after else add a space
