
-- need method to subtrack whs_alloc when picked and released

-- 07/23/24 dse This method does not reduce qty in the bin,
--              Change PICK_srv to subtract whs_qty, when the part is picked
--              This trigger add to whs_alloc when added
--              and subtract from whs_alloc  when deleted
--              make sure on a cancel, to set NEW.qtytopick to 0

DROP TRIGGER IF EXISTS ITEMPULL_I;
DELIMITER //
CREATE TRIGGER ITEMPULL_I
BEFORE INSERT ON ITEMPULL FOR EACH ROW
BEGIN
    IF (NEW.qtytopick <> 0) THEN 
    update WHSELOC
    set WHSELOC.whs_alloc = WHSELOC.whs_alloc + NEW.qtytopick
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
    set WHSELOC.whs_alloc = WHSELOC.whs_alloc - OLD.qtytopick
    where WHSELOC.whs_shadow = OLD.shadow
    and WHSELOC.whs_location = OLD.whse_loc
    and WHSELOC.whs_company = OLD.company;
    END IF;
  
    IF (NEW.qtytopick <> 0) THEN
    update WHSELOC
    set WHSELOC.whs_alloc = WHSELOC.whs_alloc + NEW.qtytopick
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
    set WHSELOC.whs_alloc = WHSELOC.whs_alloc - OLD.qtytopick
    where WHSELOC.whs_shadow = OLD.shadow
    and WHSELOC.whs_location = OLD.whse_loc
    and WHSELOC.whs_company = OLD.company;

    END IF;
END//
DELIMITER ;

