-- need method to subtrack whs_alloc when picked and released

DROP TRIGGER IF EXISTS ITEMPULL_I;
DELIMITER //
CREATE TRIGGER ITEMPULL_I
BEFORE INSERT ON ITEMPULL FOR EACH ROW
BEGIN
    IF (NEW.qtytopick <> 0) THEN 
    update WHSELOC
    set WHSELOC.whs_qty = WHSELOC.whs_qty - NEW.qtytopick,
        WHSELOC.whs_alloc = WHSELOC.whs_alloc + NEW.qtytopick
    where WHSELOC.whs_shadow = NEW.shadow
    and WHSELOC.whs_location = NEW.whse_loc
    and WHSELOC.whs_company = NEW.company;
    END IF;
END//
DELIMITER ;

DROP TRIGGER IF EXISTS ITEMPULL_U;
DELIMITER //
CREATE TRIGGER ITEMPULL_U
BEFORE UPDATE ON ITEMPULL FOR EACH ROW
BEGIN
    IF (OLD.qtytopick <> 0) THEN
    update WHSELOC
    set WHSELOC.whs_qty = WHSELOC.whs_qty + OLD.qtytopick,
        WHSELOC.whs_alloc = WHSELOC.whs_alloc - OLD.qtytopick
    where WHSELOC.whs_shadow = OLD.shadow
    and WHSELOC.whs_location = OLD.whse_loc
    and WHSELOC.whs_company = OLD.company;
    END IF;
  
    IF (NEW.qtytopick <> 0) THEN
    update WHSELOC
    set WHSELOC.whs_qty = WHSELOC.whs_qty - NEW.qtytopick,
        WHSELOC.whs_alloc = WHSELOC.whs_alloc + NEW.qtytopick
    where WHSELOC.whs_shadow = NEW.shadow
    and WHSELOC.whs_location = NEW.whse_loc
    and WHSELOC.whs_company = NEW.company;

    END IF;
END//
DELIMITER ;

DROP TRIGGER IF EXISTS ITEMPULL_D;
DELIMITER //
CREATE TRIGGER ITEMPULL_D
BEFORE DELETE ON ITEMPULL FOR EACH ROW
BEGIN
    IF (OLD.qtytopick <> 0) THEN
    update WHSELOC
    set WHSELOC.whs_qty = WHSELOC.whs_qty + OLD.qtytopick,
        WHSELOC.whs_alloc = WHSELOC.whs_alloc - OLD.qtytopick
    where WHSELOC.whs_shadow = OLD.shadow
    and WHSELOC.whs_location = OLD.whse_loc
    and WHSELOC.whs_company = OLD.company;

    END IF;
END//
DELIMITER ;

