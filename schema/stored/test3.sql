drop PROCEDURE IF EXISTS test_if;
DELIMITER $$
CREATE PROCEDURE test_if(
    IN comp SMALLINT,
    IN shadow VARCHAR(8)
)
BEGIN

-- shadow = 87630
    DECLARE qty_onhand INT DEFAULT 0;

    select qty_avail + qty_alloc + 4 into qty_onhand
    from WHSEQTY
    where ms_company = comp
    and ms_shadow = shadow;

    select qty_onhand;

END$$
DELIMITER ;

