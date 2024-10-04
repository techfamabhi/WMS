DROP TRIGGER IF EXISTS ITEMS_I;
DELIMITER //
CREATE TRIGGER ITEMS_I
BEFORE INSERT ON ITEMS FOR EACH ROW
BEGIN
    IF (NEW.qty_ship <> 0) THEN 
    update WHSEQTY
    set WHSEQTY.qty_avail = WHSEQTY.qty_avail - NEW.qty_ship,
        WHSEQTY.qty_alloc = WHSEQTY.qty_alloc + NEW.qty_ship
    where WHSEQTY.ms_shadow = NEW.shadow
    and WHSEQTY.ms_company = NEW.inv_comp;
    END IF;
END//
DELIMITER ;

DROP TRIGGER IF EXISTS ITEMS_U;
DELIMITER //
CREATE TRIGGER ITEMS_U
BEFORE UPDATE ON ITEMS FOR EACH ROW
BEGIN
    IF (OLD.qty_ship <> 0) THEN 
    update WHSEQTY
    set WHSEQTY.qty_avail = WHSEQTY.qty_avail + OLD.qty_ship,
        WHSEQTY.qty_alloc = WHSEQTY.qty_alloc - OLD.qty_ship
    where WHSEQTY.ms_shadow = OLD.shadow
    and WHSEQTY.ms_company = OLD.inv_comp;
    END IF;
    IF (NEW.qty_ship <> 0) THEN 
    update WHSEQTY
    set WHSEQTY.qty_avail = WHSEQTY.qty_avail - NEW.qty_ship,
        WHSEQTY.qty_alloc = WHSEQTY.qty_alloc + NEW.qty_ship
    where WHSEQTY.ms_shadow = NEW.shadow
    and WHSEQTY.ms_company = NEW.inv_comp;
    END IF;
END//
DELIMITER ;

DROP TRIGGER IF EXISTS ITEMS_D;
DELIMITER //
CREATE TRIGGER ITEMS_D
BEFORE DELETE ON ITEMS FOR EACH ROW
BEGIN
    IF (OLD.qty_ship <> 0) THEN 
    update WHSEQTY
    set WHSEQTY.qty_avail = WHSEQTY.qty_avail + OLD.qty_ship,
        WHSEQTY.qty_alloc = WHSEQTY.qty_alloc - OLD.qty_ship
    where WHSEQTY.ms_shadow = OLD.shadow
    and WHSEQTY.ms_company = OLD.inv_comp;
    END IF;
END//
DELIMITER ;

