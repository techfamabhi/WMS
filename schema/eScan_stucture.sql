-- MySQL dump 10.13  Distrib 5.5.60, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: wms
-- ------------------------------------------------------
-- Server version	5.5.60-0+deb7u1-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ALTERNAT`
--

DROP TABLE IF EXISTS `ALTERNAT`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ALTERNAT` (
  `alt_shadow_num` int(11) DEFAULT NULL,
  `alt_part_number` char(25) DEFAULT NULL,
  `alt_type_code` smallint(6) DEFAULT NULL,
  `alt_uom` char(3) DEFAULT NULL,
  `alt_sort` int(11) DEFAULT NULL,
  UNIQUE KEY `ALTERNAT_hash` (`alt_part_number`,`alt_type_code`,`alt_shadow_num`,`alt_sort`),
  UNIQUE KEY `ALTERNAT_idx1` (`alt_shadow_num`,`alt_part_number`,`alt_type_code`,`alt_sort`),
  KEY `ALTERNAT_FK` (`alt_type_code`),
  CONSTRAINT `ALTERNAT_FK` FOREIGN KEY (`alt_type_code`) REFERENCES `ALTYPES` (`al_key`),
  CONSTRAINT `ALTERNAT_FK1` FOREIGN KEY (`alt_shadow_num`) REFERENCES `PARTS` (`shadow_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ALTYPES`
--

DROP TABLE IF EXISTS `ALTYPES`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ALTYPES` (
  `al_key` smallint(6) NOT NULL,
  `al_desc` char(30) DEFAULT NULL,
  PRIMARY KEY (`al_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ATTRBUTE`
--

DROP TABLE IF EXISTS `ATTRBUTE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ATTRBUTE` (
  `attr_id` int(11) NOT NULL,
  `attr_code` char(3) NOT NULL,
  `attr_setting` varchar(64) DEFAULT '',
  UNIQUE KEY `ATTRBUTE_idx1` (`attr_id`,`attr_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ATTRCODE`
--

DROP TABLE IF EXISTS `ATTRCODE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ATTRCODE` (
  `acode_code` char(3) NOT NULL,
  `acode_sys` char(3) NOT NULL,
  `acode_desc` varchar(40) DEFAULT '',
  UNIQUE KEY `ATTRCODE_idx1` (`acode_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `BINTYPES`
--

DROP TABLE IF EXISTS `BINTYPES`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `BINTYPES` (
  `typ_company` smallint(6) DEFAULT NULL,
  `typ_code` char(3) DEFAULT NULL,
  `typ_desc` char(30) DEFAULT NULL,
  `typ_pick` tinyint(4) DEFAULT NULL,
  `typ_recv` tinyint(4) DEFAULT NULL,
  `typ_core` tinyint(4) DEFAULT NULL,
  `typ_defect` tinyint(4) DEFAULT NULL,
  UNIQUE KEY `BINTYPES_hash` (`typ_company`,`typ_code`),
  CONSTRAINT `BINTYPES_FK` FOREIGN KEY (`typ_company`) REFERENCES `COMPANY` (`company_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `CATEGORYS`
--

DROP TABLE IF EXISTS `CATEGORYS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CATEGORYS` (
  `cat_id` char(3) NOT NULL,
  `cat_desc` char(30) DEFAULT '',
  PRIMARY KEY (`cat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `COMPANY`
--

DROP TABLE IF EXISTS `COMPANY`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `COMPANY` (
  `company_number` smallint(6) NOT NULL,
  `company_name` char(34) DEFAULT NULL,
  `company_address` char(34) DEFAULT NULL,
  `company_city` char(30) DEFAULT NULL,
  `company_state` char(2) DEFAULT NULL,
  `company_zip` char(10) DEFAULT NULL,
  `company_phone` char(14) DEFAULT NULL,
  `company_abbr` char(10) DEFAULT NULL,
  `company_region` char(20) DEFAULT NULL,
  `company_fax_num` char(14) DEFAULT NULL,
  `company_logo` varchar(128) DEFAULT NULL,
  `host_company` char(6) DEFAULT NULL,
  PRIMARY KEY (`company_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `CONTROL`
--

DROP TABLE IF EXISTS `CONTROL`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CONTROL` (
  `control_key` char(8) DEFAULT NULL,
  `control_company` smallint(6) DEFAULT NULL,
  `control_number` int(11) DEFAULT NULL,
  `control_maxnum` int(11) DEFAULT NULL,
  `control_reset_to` int(11) DEFAULT NULL,
  UNIQUE KEY `CONTROL_hash` (`control_key`,`control_company`),
  KEY `CONTROL_FK` (`control_company`),
  CONSTRAINT `CONTROL_FK` FOREIGN KEY (`control_company`) REFERENCES `COMPANY` (`company_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `COPTDESC`
--

DROP TABLE IF EXISTS `COPTDESC`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `COPTDESC` (
  `copt_number` smallint(6) NOT NULL,
  `copt_desc` varchar(50) DEFAULT ' ',
  `copt_desc1` varchar(50) DEFAULT ' ',
  `copt_cat` varchar(40) DEFAULT ' ',
  `copt_text` text,
  PRIMARY KEY (`copt_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `COPTIONS`
--

DROP TABLE IF EXISTS `COPTIONS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `COPTIONS` (
  `cop_company` smallint(6) DEFAULT NULL,
  `cop_option` smallint(6) DEFAULT NULL,
  `cop_flag` varchar(128) DEFAULT NULL,
  UNIQUE KEY `COPTIONS_hash` (`cop_company`,`cop_option`),
  KEY `COPTIONS_FK` (`cop_option`),
  CONSTRAINT `COPTIONS_FK` FOREIGN KEY (`cop_option`) REFERENCES `COPTDESC` (`copt_number`),
  CONSTRAINT `COPTIONS_FK1` FOREIGN KEY (`cop_company`) REFERENCES `COMPANY` (`company_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `CUSTOMERS`
--

DROP TABLE IF EXISTS `CUSTOMERS`;
/*!50001 DROP VIEW IF EXISTS `CUSTOMERS`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `CUSTOMERS` (
  `customer` tinyint NOT NULL,
  `name` tinyint NOT NULL,
  `addr1` tinyint NOT NULL,
  `addr2` tinyint NOT NULL,
  `city` tinyint NOT NULL,
  `state` tinyint NOT NULL,
  `zip` tinyint NOT NULL,
  `ctry` tinyint NOT NULL,
  `contact` tinyint NOT NULL,
  `phone` tinyint NOT NULL,
  `email` tinyint NOT NULL,
  `ship_via` tinyint NOT NULL,
  `num_notes` tinyint NOT NULL,
  `last_trans` tinyint NOT NULL,
  `allow_bo` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `DATA_SERVERS`
--

DROP TABLE IF EXISTS `DATA_SERVERS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DATA_SERVERS` (
  `actionName` varchar(64) NOT NULL,
  `serverName` varchar(32) NOT NULL,
  `requestData` text,
  `description` text,
  `returnData` text,
  UNIQUE KEY `DATA_SERVERS_idx` (`actionName`,`serverName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DRECPTS`
--

DROP TABLE IF EXISTS `DRECPTS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DRECPTS` (
  `rcp_number` int(11) NOT NULL,
  `rcp_line` smallint(6) NOT NULL,
  `shadow` int(11) NOT NULL,
  `p_l` char(3) DEFAULT '',
  `part_number` char(22) DEFAULT '',
  `upc` char(25) DEFAULT '',
  `barcode_qty` int(11) DEFAULT '1',
  `qty_recvd` int(11) DEFAULT '0',
  `qty_bo` int(11) DEFAULT '0',
  `qty_cancel` int(11) DEFAULT '0',
  `uom` char(3) DEFAULT 'EA',
  `man_qty_ovrd` char(1) DEFAULT '',
  `serial_number` varchar(30) DEFAULT '',
  `bin_pack` char(15) DEFAULT '',
  `rcp_status` tinyint(4) DEFAULT '0',
  `mdse_price` decimal(10,3) DEFAULT NULL,
  `core_price` decimal(10,3) DEFAULT NULL,
  UNIQUE KEY `DRECPTS_idx1` (`rcp_number`,`rcp_line`),
  CONSTRAINT `DRECPTS_FK` FOREIGN KEY (`rcp_number`) REFERENCES `HRECPTS` (`rec_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `DROPSHIP`
--

DROP TABLE IF EXISTS `DROPSHIP`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DROPSHIP` (
  `order_num` int(11) NOT NULL,
  `name` varchar(40) DEFAULT NULL,
  `addr1` varchar(40) DEFAULT NULL,
  `addr2` varchar(40) DEFAULT NULL,
  `city` varchar(25) DEFAULT NULL,
  `state` char(2) DEFAULT NULL,
  `zip` char(10) DEFAULT NULL,
  `ctry` char(3) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`order_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `DUPEALT`
--

DROP TABLE IF EXISTS `DUPEALT`;
/*!50001 DROP VIEW IF EXISTS `DUPEALT`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `DUPEALT` (
  `upc` tinyint NOT NULL,
  `shad` tinyint NOT NULL,
  `cnt` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `ENTITY`
--

DROP TABLE IF EXISTS `ENTITY`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ENTITY` (
  `entity_num` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` char(1) NOT NULL,
  `host_id` varchar(20) NOT NULL,
  `name` varchar(40) DEFAULT NULL,
  `addr1` varchar(40) DEFAULT NULL,
  `addr2` varchar(40) DEFAULT NULL,
  `city` varchar(25) DEFAULT NULL,
  `state` char(2) DEFAULT NULL,
  `zip` char(10) DEFAULT NULL,
  `ctry` char(3) DEFAULT NULL,
  `contact` varchar(30) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(128) DEFAULT NULL,
  `ship_via` char(4) DEFAULT '',
  `num_notes` int(11) DEFAULT NULL,
  `allow_bo` char(1) DEFAULT NULL,
  `last_trans` datetime DEFAULT NULL,
  `allow_to_bin` char(1) DEFAULT 'N',
  `allow_inplace` char(1) DEFAULT 'N',
  PRIMARY KEY (`entity_num`),
  UNIQUE KEY `ENTITY_hash` (`entity_type`,`host_id`),
  UNIQUE KEY `ENTITY_idx1` (`host_id`,`entity_type`)
) ENGINE=InnoDB AUTO_INCREMENT=7465 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `HAZARD_CODES`
--

DROP TABLE IF EXISTS `HAZARD_CODES`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `HAZARD_CODES` (
  `haz_code` char(3) NOT NULL,
  `haz_desc` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`haz_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `HRECPTS`
--

DROP TABLE IF EXISTS `HRECPTS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `HRECPTS` (
  `rec_number` int(11) NOT NULL AUTO_INCREMENT,
  `company` smallint(6) DEFAULT NULL,
  `trans_type` char(3) DEFAULT NULL,
  `host_trans_num` char(20) DEFAULT NULL,
  `wms_po_num` int(11) DEFAULT NULL,
  `packing_slip` char(22) DEFAULT NULL,
  `asn_number` varchar(48) DEFAULT NULL,
  `date_recvd` datetime DEFAULT NULL,
  `recvd_by` char(12) DEFAULT NULL,
  `sent_flag` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`rec_number`),
  UNIQUE KEY `HRECPTS_idx1` (`host_trans_num`,`company`,`rec_number`),
  KEY `HRECPTS_FK` (`wms_po_num`),
  CONSTRAINT `HRECPTS_FK` FOREIGN KEY (`wms_po_num`) REFERENCES `POHEADER` (`wms_po_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `INV_BATCH`
--

DROP TABLE IF EXISTS `INV_BATCH`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `INV_BATCH` (
  `count_num` int(11) NOT NULL,
  `company` smallint(6) NOT NULL,
  `create_by` int(11) NOT NULL DEFAULT '0',
  `create_date` datetime NOT NULL,
  `due_date` datetime NOT NULL,
  `count_status` smallint(6) NOT NULL DEFAULT '0',
  `count_type` smallint(6) DEFAULT '0',
  UNIQUE KEY `INV_BATCH_hash` (`count_num`,`company`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `INV_ERROR`
--

DROP TABLE IF EXISTS `INV_ERROR`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `INV_ERROR` (
  `count_num` int(11) NOT NULL,
  `ex_type` smallint(6) NOT NULL,
  `last_bin` varchar(18) NOT NULL,
  `this_bin` varchar(18) NOT NULL,
  `upc` char(20) NOT NULL,
  `p_l` char(6) DEFAULT '',
  `part_number` char(22) DEFAULT '',
  `qty` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `INV_SCAN`
--

DROP TABLE IF EXISTS `INV_SCAN`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `INV_SCAN` (
  `count_num` int(11) NOT NULL,
  `count_line` int(11) NOT NULL,
  `userId` int(11) NOT NULL DEFAULT '0',
  `whse_loc` varchar(18) NOT NULL DEFAULT ' ',
  `bin_type` char(1) NOT NULL,
  `shadow` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `uom` char(3) NOT NULL,
  `bin_avail` int(11) NOT NULL,
  `bin_alloc` int(11) NOT NULL,
  `qty_avail` int(11) NOT NULL,
  `qty_alloc` int(11) NOT NULL,
  `line_status` smallint(6) NOT NULL,
  `reason` varchar(40) DEFAULT ' ',
  KEY `INV_SCAN_hash` (`count_num`,`count_line`),
  KEY `INV_SCAN_idx1` (`count_num`,`shadow`,`whse_loc`,`count_line`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `INV_USERS`
--

DROP TABLE IF EXISTS `INV_USERS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `INV_USERS` (
  `count_num` int(11) NOT NULL,
  `userId` int(11) NOT NULL DEFAULT '0',
  `last_bin` char(8) NOT NULL,
  `last_access` datetime NOT NULL,
  `scan_count` int(11) DEFAULT '0',
  UNIQUE KEY `INV_USERS_hash` (`count_num`,`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ITEMPULL`
--

DROP TABLE IF EXISTS `ITEMPULL`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ITEMPULL` (
  `ord_num` int(11) NOT NULL,
  `line_num` int(11) NOT NULL,
  `pull_num` smallint(6) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company` smallint(6) NOT NULL,
  `shadow` int(11) NOT NULL,
  `zone` char(3) DEFAULT '',
  `whse_loc` varchar(18) NOT NULL DEFAULT ' ',
  `qtytopick` int(11) NOT NULL,
  `qty_picked` int(11) NOT NULL,
  `uom_picked` char(3) DEFAULT 'EA',
  `qty_verified` int(11) NOT NULL DEFAULT '0',
  `totes` varchar(40) DEFAULT '',
  `zero_picked` tinyint(4) NOT NULL DEFAULT '0',
  `zpuser` int(11) DEFAULT '0',
  UNIQUE KEY `ITEMPULL_hash` (`ord_num`,`line_num`,`pull_num`),
  UNIQUE KEY `ITEMPULL_idx1` (`whse_loc`,`ord_num`,`line_num`,`pull_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`wms`@`localhost`*/ /*!50003 TRIGGER ITEMPULL_I
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`wms`@`localhost`*/ /*!50003 TRIGGER ITEMPULL_U
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`wms`@`localhost`*/ /*!50003 TRIGGER ITEMPULL_D
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `ITEMS`
--

DROP TABLE IF EXISTS `ITEMS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ITEMS` (
  `ord_num` int(11) NOT NULL,
  `line_num` int(11) NOT NULL,
  `shadow` int(11) NOT NULL,
  `p_l` char(6) NOT NULL,
  `part_number` varchar(22) NOT NULL,
  `part_desc` varchar(30) DEFAULT ' ',
  `uom` char(3) NOT NULL,
  `qty_ord` int(11) NOT NULL,
  `qty_ship` int(11) NOT NULL,
  `qty_bo` int(11) NOT NULL,
  `qty_avail` int(11) NOT NULL,
  `min_ship_qty` int(11) DEFAULT '1',
  `case_qty` int(11) DEFAULT '1',
  `inv_code` char(1) DEFAULT '0',
  `line_status` smallint(6) DEFAULT '0',
  `hazard_id` char(3) DEFAULT '',
  `zone` char(3) DEFAULT '',
  `whse_loc` varchar(18) NOT NULL DEFAULT ' ',
  `qty_in_primary` int(11) DEFAULT '0',
  `num_messg` smallint(6) DEFAULT '0',
  `part_weight` decimal(10,2) DEFAULT '0.00',
  `part_subline` char(3) DEFAULT '',
  `part_category` char(4) DEFAULT '',
  `part_group` char(5) DEFAULT '',
  `part_class` char(3) DEFAULT '',
  `item_pulls` smallint(6) DEFAULT '0',
  `specord_num` varchar(20) DEFAULT '',
  `inv_comp` smallint(6) DEFAULT NULL,
  UNIQUE KEY `ITEMS_hash` (`ord_num`,`line_num`),
  UNIQUE KEY `ITEMS_idx02` (`shadow`,`line_status`,`ord_num`),
  KEY `ITEMS_idx01` (`zone`,`whse_loc`,`ord_num`,`p_l`,`part_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`wms`@`localhost`*/ /*!50003 TRIGGER ITEMS_I
BEFORE INSERT ON ITEMS FOR EACH ROW
BEGIN
    IF (NEW.qty_ship <> 0) THEN 
    update WHSEQTY
    set WHSEQTY.qty_avail = WHSEQTY.qty_avail - NEW.qty_ship,
        WHSEQTY.qty_alloc = WHSEQTY.qty_alloc + NEW.qty_ship
    where WHSEQTY.ms_shadow = NEW.shadow
    and WHSEQTY.ms_company = NEW.inv_comp;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`wms`@`localhost`*/ /*!50003 TRIGGER ITEMS_U
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`wms`@`localhost`*/ /*!50003 TRIGGER ITEMS_D
BEFORE DELETE ON ITEMS FOR EACH ROW
BEGIN
    IF (OLD.qty_ship <> 0) THEN 
    update WHSEQTY
    set WHSEQTY.qty_avail = WHSEQTY.qty_avail + OLD.qty_ship,
        WHSEQTY.qty_alloc = WHSEQTY.qty_alloc - OLD.qty_ship
    where WHSEQTY.ms_shadow = OLD.shadow
    and WHSEQTY.ms_company = OLD.inv_comp;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `MOBCARRIER`
--

DROP TABLE IF EXISTS `MOBCARRIER`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `MOBCARRIER` (
  `country` char(3) NOT NULL,
  `carrier` char(20) NOT NULL,
  `SMS` varchar(30) DEFAULT NULL,
  `MMS` varchar(30) DEFAULT NULL,
  UNIQUE KEY `MOBCARRIER_hash` (`country`,`carrier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `NEEDUPC`
--

DROP TABLE IF EXISTS `NEEDUPC`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `NEEDUPC` (
  `shadow` int(11) NOT NULL DEFAULT '0',
  `upc_status` tinyint(4) DEFAULT '0',
  `upc_scanned` varchar(24) DEFAULT '',
  `upc_qty` smallint(6) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `NOUPC`
--

DROP TABLE IF EXISTS `NOUPC`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `NOUPC` (
  `noupc_id` int(11) NOT NULL AUTO_INCREMENT,
  `source` varchar(3) NOT NULL DEFAULT '',
  `problem` varchar(10) NOT NULL DEFAULT '',
  `userId` int(11) NOT NULL DEFAULT '0',
  `refnum` varchar(20) DEFAULT '',
  `shadow` int(11) NOT NULL DEFAULT '0',
  `bin` varchar(18) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT '1',
  `occurred` datetime DEFAULT NULL,
  `item_status` smallint(6) DEFAULT '0',
  PRIMARY KEY (`noupc_id`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`wms`@`localhost`*/ /*!50003 TRIGGER NOUPC_I
BEFORE INSERT ON NOUPC FOR EACH ROW
BEGIN
        SET NEW.occurred = now();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `ORDERS`
--

DROP TABLE IF EXISTS `ORDERS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ORDERS` (
  `order_num` int(11) NOT NULL AUTO_INCREMENT,
  `company` smallint(6) NOT NULL,
  `order_type` char(2) NOT NULL,
  `host_order_num` varchar(20) NOT NULL,
  `customer_id` varchar(20) NOT NULL,
  `cust_po_num` varchar(20) DEFAULT ' ',
  `enter_by` varchar(15) DEFAULT ' ',
  `enter_date` datetime NOT NULL,
  `wms_date` datetime NOT NULL,
  `pic_release` datetime DEFAULT NULL,
  `pic_done` datetime DEFAULT NULL,
  `wms_complete` datetime DEFAULT NULL,
  `date_required` datetime NOT NULL,
  `priority` smallint(6) DEFAULT '1',
  `ship_complete` char(1) DEFAULT 'N',
  `order_stat` smallint(6) DEFAULT '0',
  `num_lines` int(11) DEFAULT '0',
  `spec_order_num` varchar(20) DEFAULT '',
  `mdse_type` smallint(6) DEFAULT '1',
  `ship_via` char(4) DEFAULT '',
  `conveyor` varchar(20) DEFAULT '',
  `drop_ship_flag` tinyint(4) DEFAULT NULL,
  `special_instr` varchar(24) DEFAULT NULL,
  `shipping_instr` varchar(24) DEFAULT NULL,
  `zones` varchar(128) DEFAULT NULL,
  `o_num_pieces` int(11) DEFAULT NULL,
  `messg` text,
  `track_recs` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`order_num`),
  UNIQUE KEY `ORDERS_hash` (`order_num`,`company`),
  UNIQUE KEY `ORDERS_index004` (`customer_id`,`order_num`),
  UNIQUE KEY `ORDERS_index023` (`company`,`enter_date`,`order_num`),
  UNIQUE KEY `ORDERS_cust` (`customer_id`,`order_num`),
  UNIQUE KEY `ORDERS_index008` (`company`,`order_stat`,`customer_id`,`order_num`),
  UNIQUE KEY `ORDERS_index022` (`company`,`wms_complete`,`order_num`),
  UNIQUE KEY `ORDERS_index029` (`company`,`order_stat`,`order_num`)
) ENGINE=InnoDB AUTO_INCREMENT=10152 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ORDMESSG`
--

DROP TABLE IF EXISTS `ORDMESSG`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ORDMESSG` (
  `order_num` int(11) NOT NULL,
  `line_num` int(11) NOT NULL,
  `message_num` smallint(6) NOT NULL,
  `message` varchar(255) DEFAULT NULL,
  UNIQUE KEY `ORDMESSG_hash` (`order_num`,`line_num`,`message_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ORDPACK`
--

DROP TABLE IF EXISTS `ORDPACK`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ORDPACK` (
  `order_num` int(11) NOT NULL,
  `carton_num` smallint(6) NOT NULL,
  `line_num` smallint(6) NOT NULL,
  `shadow` int(11) NOT NULL,
  `qty` int(11) DEFAULT '0',
  `uom` char(3) DEFAULT 'EA',
  KEY `ORDPACK_idx2` (`order_num`,`shadow`,`carton_num`),
  KEY `ORDPACK_hash` (`order_num`,`carton_num`,`line_num`),
  KEY `ORDPACK_idx1` (`order_num`,`line_num`,`carton_num`),
  CONSTRAINT `OTRK_ORDERS_FK` FOREIGN KEY (`order_num`) REFERENCES `ORDERS` (`order_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ORDQUE`
--

DROP TABLE IF EXISTS `ORDQUE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ORDQUE` (
  `order_num` int(11) NOT NULL,
  `que_key` char(3) NOT NULL,
  `que_data` varchar(255) DEFAULT '',
  PRIMARY KEY (`order_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ORDTOTE`
--

DROP TABLE IF EXISTS `ORDTOTE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ORDTOTE` (
  `order_num` int(11) NOT NULL,
  `tote_id` int(11) NOT NULL,
  `last_zone` char(3) DEFAULT '',
  `last_loc` char(18) DEFAULT '',
  `tote_status` smallint(6) DEFAULT '0',
  UNIQUE KEY `ORDTOTE_hash` (`order_num`,`tote_id`),
  UNIQUE KEY `ORDTOTE_idx1` (`tote_id`,`order_num`),
  CONSTRAINT `OTOT_ORDERS_FK` FOREIGN KEY (`order_num`) REFERENCES `ORDERS` (`order_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ORDTRACK`
--

DROP TABLE IF EXISTS `ORDTRACK`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ORDTRACK` (
  `order_num` int(11) NOT NULL,
  `line_num` smallint(6) NOT NULL,
  `user_id` int(11) NOT NULL,
  `track_type` char(3) NOT NULL,
  `zone` char(3) DEFAULT NULL,
  `num_lines` int(11) DEFAULT '0',
  UNIQUE KEY `ORDTRACK_hash` (`order_num`,`line_num`,`user_id`,`track_type`,`zone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `PACKSCAN`
--

DROP TABLE IF EXISTS `PACKSCAN`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PACKSCAN` (
  `ord_number` int(11) NOT NULL,
  `line_num` int(11) NOT NULL,
  `shadow` int(11) NOT NULL,
  `qty_scan` int(11) DEFAULT '0',
  `checker` int(11) DEFAULT '0',
  `scan_line` int(11) DEFAULT '0',
  `scan_tote` int(11) DEFAULT '0',
  `uom` char(3) DEFAULT 'EA',
  UNIQUE KEY `PACKSCAN_hash` (`ord_number`,`shadow`,`scan_line`),
  UNIQUE KEY `PACKSCAN_idx1` (`ord_number`,`line_num`,`scan_line`),
  CONSTRAINT `PACKSCAN_ORD_FK` FOREIGN KEY (`ord_number`) REFERENCES `ORDERS` (`order_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `PARTCLASS`
--

DROP TABLE IF EXISTS `PARTCLASS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PARTCLASS` (
  `class_id` char(3) NOT NULL,
  `class_desc` char(30) DEFAULT '',
  PRIMARY KEY (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `PARTGROUPS`
--

DROP TABLE IF EXISTS `PARTGROUPS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PARTGROUPS` (
  `pgroup_id` char(10) NOT NULL DEFAULT '',
  `pgroup_desc` char(30) DEFAULT '',
  PRIMARY KEY (`pgroup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `PARTHIST`
--

DROP TABLE IF EXISTS `PARTHIST`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PARTHIST` (
  `paud_num` int(11) NOT NULL AUTO_INCREMENT,
  `paud_id` int(11) NOT NULL DEFAULT '0',
  `paud_shadow` int(11) NOT NULL,
  `paud_company` smallint(6) DEFAULT NULL,
  `paud_date` datetime DEFAULT NULL,
  `paud_source` varchar(10) DEFAULT '',
  `paud_user` int(11) DEFAULT '0',
  `paud_ref` varchar(20) DEFAULT ' ',
  `paud_ext_ref` varchar(20) DEFAULT ' ',
  `paud_type` char(3) DEFAULT NULL,
  `paud_qty` int(11) DEFAULT '0',
  `paud_uom` char(3) DEFAULT 'EA',
  `paud_floc` varchar(18) DEFAULT ' ',
  `paud_tloc` varchar(18) DEFAULT NULL,
  `paud_prev_qty` int(11) DEFAULT '0',
  `paud_inv_code` char(1) DEFAULT '0',
  `paud_price` decimal(10,3) NOT NULL DEFAULT '0.000',
  `paud_core_price` decimal(10,3) NOT NULL DEFAULT '0.000',
  `paud_qty_core` int(11) DEFAULT NULL,
  `paud_qty_def` int(11) DEFAULT NULL,
  PRIMARY KEY (`paud_num`)
) ENGINE=InnoDB AUTO_INCREMENT=863 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`wms`@`localhost`*/ /*!50003 TRIGGER PARTHIST_I
BEFORE INSERT ON PARTHIST FOR EACH ROW
BEGIN
    IF (NEW.paud_date IS NULL) THEN 
        SET NEW.paud_date = now();
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `PARTS`
--

DROP TABLE IF EXISTS `PARTS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PARTS` (
  `p_l` char(3) NOT NULL,
  `part_number` char(22) NOT NULL,
  `part_desc` char(30) DEFAULT '',
  `part_long_desc` char(60) DEFAULT '',
  `unit_of_measure` char(3) DEFAULT '',
  `part_seq_num` int(11) DEFAULT '0',
  `part_subline` char(3) DEFAULT '',
  `part_category` char(4) DEFAULT '',
  `part_group` char(10) DEFAULT '',
  `part_class` char(3) DEFAULT '',
  `date_added` datetime DEFAULT NULL,
  `lmaint_date` datetime DEFAULT NULL,
  `serial_num_flag` smallint(6) DEFAULT '0',
  `part_status` char(1) DEFAULT '',
  `special_instr` char(30) DEFAULT '',
  `hazard_id` char(3) DEFAULT '',
  `kit_flag` smallint(6) DEFAULT '0',
  `cost` decimal(10,3) DEFAULT '0.000',
  `core` decimal(10,3) DEFAULT '0.000',
  `core_group` char(3) DEFAULT '',
  `part_returnable` char(1) DEFAULT '',
  `shadow_number` int(11) NOT NULL,
  `part_weight` decimal(10,3) DEFAULT '0.000',
  UNIQUE KEY `PARTS_hash` (`shadow_number`),
  UNIQUE KEY `PARTS_idx1` (`p_l`,`part_number`,`shadow_number`),
  UNIQUE KEY `PARTS_idx2` (`p_l`,`part_seq_num`,`part_number`,`shadow_number`),
  UNIQUE KEY `PARTS_idx3` (`p_l`,`part_subline`,`part_number`,`shadow_number`),
  UNIQUE KEY `PARTS_idx4` (`part_category`,`p_l`,`part_number`,`shadow_number`),
  KEY `PARTS_FK` (`part_group`),
  CONSTRAINT `PARTS_FK` FOREIGN KEY (`part_group`) REFERENCES `PARTGROUPS` (`pgroup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`wms`@`localhost`*/ /*!50003 TRIGGER PARTS_I
BEFORE INSERT ON PARTS FOR EACH ROW
BEGIN
    IF (NEW.date_added IS NULL) THEN 
        SET NEW.date_added = now();
    END IF;
    IF (NEW.lmaint_date IS NULL) THEN 
        SET NEW.lmaint_date = now();
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`wms`@`localhost`*/ /*!50003 TRIGGER PARTS_U
BEFORE UPDATE ON PARTS FOR EACH ROW
BEGIN
    IF (NEW.lmaint_date IS NULL) THEN 
        SET NEW.lmaint_date = now();
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `PARTUOM`
--

DROP TABLE IF EXISTS `PARTUOM`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PARTUOM` (
  `shadow` int(11) NOT NULL,
  `company` smallint(6) NOT NULL,
  `uom` char(3) DEFAULT 'EA',
  `uom_qty` int(11) DEFAULT '1',
  `uom_length` smallint(6) DEFAULT '0',
  `uom_width` smallint(6) DEFAULT '0',
  `uom_height` smallint(6) DEFAULT '0',
  `uom_weight` decimal(10,2) DEFAULT '0.00',
  `uom_volume` decimal(10,2) DEFAULT '0.00',
  `upc_code` varchar(25) DEFAULT '',
  UNIQUE KEY `PARTUOM_hash` (`shadow`,`company`,`uom`,`upc_code`),
  KEY `PARTUOM_FK1` (`uom`),
  CONSTRAINT `PARTUOM_FK` FOREIGN KEY (`shadow`) REFERENCES `PARTS` (`shadow_number`),
  CONSTRAINT `PARTUOM_FK1` FOREIGN KEY (`uom`) REFERENCES `UOMCODES` (`uom_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `PHYSDTL`
--

DROP TABLE IF EXISTS `PHYSDTL`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PHYSDTL` (
  `phys_num` int(11) DEFAULT NULL,
  `line_num` int(11) DEFAULT NULL,
  `shadow` int(11) DEFAULT NULL,
  `location` char(18) DEFAULT NULL,
  `loc_type` char(1) DEFAULT NULL,
  `qty_avail` int(11) DEFAULT '0',
  `qty_alloc` int(11) DEFAULT '0',
  `counted1` int(11) DEFAULT '0',
  `recount` int(11) DEFAULT '0',
  `last_user` int(11) DEFAULT '0',
  `pd_status` tinyint(4) DEFAULT '0',
  UNIQUE KEY `PHYSDTL_hash` (`phys_num`,`line_num`),
  UNIQUE KEY `PHYSDTL_idx1` (`shadow`,`phys_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `PHYSHDR`
--

DROP TABLE IF EXISTS `PHYSHDR`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PHYSHDR` (
  `phys_num` int(11) NOT NULL,
  `company` int(11) DEFAULT NULL,
  `create_date` datetime DEFAULT NULL,
  `num_items` int(11) DEFAULT '0',
  `phys_status` tinyint(4) DEFAULT '0',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  UNIQUE KEY `PHYSHDR_hash` (`company`,`phys_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `PHYSUSER`
--

DROP TABLE IF EXISTS `PHYSUSER`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PHYSUSER` (
  `phys_num` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `last_action` datetime DEFAULT NULL,
  `bins_visited` int(11) DEFAULT NULL,
  `uniq_parts` int(11) DEFAULT NULL,
  UNIQUE KEY `PHYSUSER_hash` (`phys_num`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `POHEADER`
--

DROP TABLE IF EXISTS `POHEADER`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `POHEADER` (
  `company` smallint(6) DEFAULT NULL,
  `wms_po_num` int(11) NOT NULL,
  `host_po_num` varchar(30) DEFAULT ' ',
  `po_type` char(1) DEFAULT 'P',
  `vendor` char(10) DEFAULT '',
  `po_date` datetime DEFAULT NULL,
  `num_lines` int(11) DEFAULT '0',
  `po_status` smallint(6) DEFAULT NULL,
  `bo_flag` tinyint(4) DEFAULT '1',
  `num_messages` smallint(6) DEFAULT '1',
  `est_deliv_date` datetime DEFAULT NULL,
  `ship_via` char(6) DEFAULT '',
  `sched_date` datetime DEFAULT NULL,
  `xdock` tinyint(4) DEFAULT '0',
  `disp_comment` tinyint(4) DEFAULT '0',
  `comment` varchar(128) DEFAULT ' ',
  `customer_id` char(12) DEFAULT '0',
  `ordernum` int(11) DEFAULT '0',
  `container` varchar(15) DEFAULT ' ',
  `created_by` int(11) DEFAULT '0',
  UNIQUE KEY `POHEADER_hash` (`wms_po_num`),
  UNIQUE KEY `POHEADER_idx1` (`host_po_num`,`wms_po_num`),
  KEY `POHEADER_FK` (`ship_via`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `POITEMS`
--

DROP TABLE IF EXISTS `POITEMS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `POITEMS` (
  `poi_po_num` int(11) DEFAULT NULL,
  `poi_line_num` int(11) DEFAULT NULL,
  `shadow` int(11) DEFAULT NULL,
  `p_l` char(3) DEFAULT '',
  `part_number` char(22) DEFAULT '',
  `part_desc` char(30) DEFAULT '',
  `uom` char(3) DEFAULT 'EA',
  `qty_ord` int(11) DEFAULT '0',
  `qty_recvd` int(11) DEFAULT '0',
  `qty_bo` int(11) DEFAULT '0',
  `qty_cancel` int(11) DEFAULT '0',
  `mdse_price` decimal(10,3) DEFAULT '0.000',
  `core_price` decimal(10,3) DEFAULT '0.000',
  `weight` decimal(10,3) DEFAULT '0.000',
  `volume` decimal(10,3) DEFAULT '0.000',
  `case_uom` char(3) DEFAULT '',
  `case_qty` int(11) DEFAULT '0',
  `poi_status` tinyint(4) DEFAULT '0',
  `vendor_ship_qty` int(11) DEFAULT '0',
  `packing_slip` char(22) DEFAULT '',
  `tracking_num` char(22) DEFAULT '',
  `bill_lading` char(22) DEFAULT '',
  `container_id` char(15) DEFAULT '',
  `carton_id` char(10) DEFAULT '',
  `line_type` char(1) DEFAULT '',
  UNIQUE KEY `POITEMS_hash` (`poi_po_num`,`poi_line_num`),
  CONSTRAINT `POITEMS_FK` FOREIGN KEY (`poi_po_num`) REFERENCES `POHEADER` (`wms_po_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `PRINTERS`
--

DROP TABLE IF EXISTS `PRINTERS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PRINTERS` (
  `lpt_number` smallint(6) NOT NULL,
  `lpt_description` char(30) DEFAULT NULL,
  `lpt_company` smallint(6) DEFAULT NULL,
  `lpt_pathname` char(128) DEFAULT NULL,
  `lpt_type` char(20) DEFAULT NULL,
  `lpt_copy_code` char(4) DEFAULT NULL,
  `lpt_prompt` char(15) DEFAULT NULL,
  PRIMARY KEY (`lpt_number`),
  KEY `PRINTERS_FK` (`lpt_company`),
  CONSTRAINT `PRINTERS_FK` FOREIGN KEY (`lpt_company`) REFERENCES `COMPANY` (`company_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `PRODLINE`
--

DROP TABLE IF EXISTS `PRODLINE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PRODLINE` (
  `pl_code` char(6) DEFAULT NULL,
  `pl_company` smallint(6) DEFAULT NULL,
  `pl_desc` char(30) DEFAULT NULL,
  `pl_vend_code` char(10) DEFAULT NULL,
  `pl_perfered_zone` char(3) DEFAULT NULL,
  `pl_perfered_aisle` char(4) DEFAULT NULL,
  `pl_date_added` datetime DEFAULT NULL,
  `pl_num_notes` int(11) DEFAULT NULL,
  UNIQUE KEY `PRODLINE_hash` (`pl_code`,`pl_company`),
  KEY `PL_COMP_FK` (`pl_company`),
  KEY `PL_VEND_FK1` (`pl_vend_code`),
  CONSTRAINT `PL_COMP_FK` FOREIGN KEY (`pl_company`) REFERENCES `COMPANY` (`company_number`),
  CONSTRAINT `PL_VEND_FK1` FOREIGN KEY (`pl_vend_code`) REFERENCES `ENTITY` (`host_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `RCPT_BATCH`
--

DROP TABLE IF EXISTS `RCPT_BATCH`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `RCPT_BATCH` (
  `batch_num` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `batch_status` smallint(6) NOT NULL,
  `batch_date` datetime NOT NULL,
  `batch_company` smallint(6) NOT NULL,
  `batch_type` smallint(6) NOT NULL,
  `batch_to` char(1) DEFAULT NULL,
  PRIMARY KEY (`batch_num`)
) ENGINE=InnoDB AUTO_INCREMENT=234 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `RCPT_INWORK`
--

DROP TABLE IF EXISTS `RCPT_INWORK`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `RCPT_INWORK` (
  `wms_po_num` int(11) NOT NULL,
  `batch_num` int(11) NOT NULL,
  `packing_slip` varchar(32) DEFAULT ' ',
  UNIQUE KEY `RCPT_INWORK_hash` (`batch_num`,`wms_po_num`),
  UNIQUE KEY `RCPT_INWORK_idx1` (`wms_po_num`,`batch_num`),
  CONSTRAINT `RCPT_INWORK_FK` FOREIGN KEY (`batch_num`) REFERENCES `RCPT_BATCH` (`batch_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `RCPT_SCAN`
--

DROP TABLE IF EXISTS `RCPT_SCAN`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `RCPT_SCAN` (
  `batch_num` int(11) NOT NULL,
  `line_num` smallint(6) NOT NULL,
  `pkgUOM` char(3) NOT NULL,
  `scan_upc` char(25) NOT NULL,
  `po_number` int(11) NOT NULL,
  `po_line_num` int(11) NOT NULL,
  `scan_status` smallint(6) NOT NULL,
  `scan_user` int(11) NOT NULL,
  `pack_id` char(18) NOT NULL,
  `shadow` int(11) NOT NULL,
  `partUOM` char(3) NOT NULL,
  `line_type` char(1) DEFAULT '',
  `pkgQty` int(11) NOT NULL,
  `scanQty` int(11) NOT NULL,
  `totalQty` int(11) NOT NULL,
  `timesScanned` smallint(6) NOT NULL,
  `recv_to` char(1) DEFAULT 'a',
  `totalOrd` int(11) DEFAULT '0',
  `qty_stockd` int(11) DEFAULT NULL,
  UNIQUE KEY `RCPT_SCAN_hash` (`batch_num`,`line_num`),
  UNIQUE KEY `RCPT_SCAN_idx1` (`batch_num`,`shadow`,`line_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`wms`@`localhost`*/ /*!50003 TRIGGER RCPT_SCAN_I
BEFORE INSERT ON RCPT_SCAN FOR EACH ROW
BEGIN
    insert into RCPT_USER
    (batch_num, user_id, user_status, user_action, last_action,scans)
    values ( NEW.batch_num, NEW.scan_user, 0, "RCV", NOW(),1)
    ON DUPLICATE KEY UPDATE 
    last_action = NOW(), scans = scans + 1;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`wms`@`localhost`*/ /*!50003 TRIGGER RCPT_SCAN_U
BEFORE UPDATE ON RCPT_SCAN FOR EACH ROW
BEGIN
    insert into RCPT_USER
    (batch_num, user_id, user_status, user_action, last_action,scans)
    values ( NEW.batch_num, NEW.scan_user, 0, "RCV", NOW(),1)
    ON DUPLICATE KEY UPDATE
    last_action = NOW(), scans = scans + 1;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `RCPT_TOTE`
--

DROP TABLE IF EXISTS `RCPT_TOTE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `RCPT_TOTE` (
  `rcpt_num` int(11) NOT NULL,
  `tote_id` int(11) NOT NULL,
  `rcpt_status` smallint(6) DEFAULT '0',
  `last_zone` char(3) DEFAULT NULL,
  `last_loc` char(18) DEFAULT NULL,
  `target_zone` char(3) DEFAULT NULL,
  `target_aisle` smallint(6) DEFAULT NULL,
  UNIQUE KEY `RCPT_TOTE_idx1` (`rcpt_num`,`tote_id`),
  KEY `RCPT_TOTE_hash` (`tote_id`,`rcpt_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `RCPT_USER`
--

DROP TABLE IF EXISTS `RCPT_USER`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `RCPT_USER` (
  `batch_num` int(11) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `user_status` smallint(6) NOT NULL,
  `user_action` char(3) NOT NULL,
  `last_action` datetime NOT NULL,
  `scans` int(11) DEFAULT '0',
  UNIQUE KEY `RCPT_USER_hash` (`batch_num`,`user_id`),
  UNIQUE KEY `RCPT_USER_hash1` (`user_id`,`batch_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `REASONS`
--

DROP TABLE IF EXISTS `REASONS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `REASONS` (
  `reason_code` char(3) DEFAULT '',
  `reason_desc` char(30) DEFAULT '',
  `host_reason` varchar(30) DEFAULT ' ',
  UNIQUE KEY `REASONS_idx1` (`reason_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `RF_MENU`
--

DROP TABLE IF EXISTS `RF_MENU`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `RF_MENU` (
  `menu_num` smallint(6) NOT NULL,
  `menu_line` smallint(6) NOT NULL,
  `menu_desc` varchar(40) DEFAULT NULL,
  `menu_image` varchar(64) DEFAULT NULL,
  `menu_url` varchar(128) DEFAULT NULL,
  `menu_priv` smallint(6) DEFAULT NULL,
  `menu_target` varchar(10) DEFAULT NULL,
  UNIQUE KEY `RF_MENU_hash` (`menu_line`,`menu_num`),
  KEY `RF_MENU_FK` (`menu_image`),
  CONSTRAINT `RF_MENU_FK` FOREIGN KEY (`menu_image`) REFERENCES `WEB_GRAPHICS` (`image_url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `SHIPVIA`
--

DROP TABLE IF EXISTS `SHIPVIA`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `SHIPVIA` (
  `via_code` char(4) NOT NULL DEFAULT '',
  `via_desc` char(30) DEFAULT '',
  `via_SCAC` char(4) DEFAULT '',
  `pack_rescan` tinyint(4) DEFAULT '0',
  `drop_zone` char(3) DEFAULT '',
  PRIMARY KEY (`via_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `SUBLINES`
--

DROP TABLE IF EXISTS `SUBLINES`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `SUBLINES` (
  `p_l` char(3) NOT NULL,
  `subline` char(3) NOT NULL,
  `subline_desc` char(30) DEFAULT '',
  UNIQUE KEY `SUBLINE_hash` (`p_l`,`subline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `TASKS`
--

DROP TABLE IF EXISTS `TASKS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TASKS` (
  `task_id` int(11) NOT NULL AUTO_INCREMENT,
  `task_type` char(3) DEFAULT '?',
  `task_date` datetime DEFAULT NULL,
  `task_status` tinyint(4) DEFAULT '0',
  `id_num` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `tote_id` int(11) DEFAULT '0',
  `last_zone` char(3) DEFAULT '',
  `last_loc` char(18) DEFAULT '',
  `target_zone` char(3) DEFAULT '',
  `target_aisle` smallint(6) DEFAULT '0',
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  PRIMARY KEY (`task_id`),
  UNIQUE KEY `TASKS_idx4` (`id_num`,`task_id`),
  UNIQUE KEY `TASKS_idx1` (`task_type`,`last_zone`,`id_num`,`task_id`),
  UNIQUE KEY `TASKS_idx2` (`last_zone`,`task_type`,`id_num`,`task_id`),
  UNIQUE KEY `TASKS_idx3` (`tote_id`,`task_type`,`task_id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `TOTEDTL`
--

DROP TABLE IF EXISTS `TOTEDTL`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TOTEDTL` (
  `tote_id` int(11) NOT NULL,
  `tote_item` smallint(6) DEFAULT '0',
  `tote_shadow` int(11) DEFAULT '0',
  `tote_qty` int(11) DEFAULT '0',
  `tote_uom` char(3) DEFAULT 'EA',
  UNIQUE KEY `TOTEDTL_hash` (`tote_id`,`tote_item`),
  UNIQUE KEY `TOTEDTL_idx1` (`tote_shadow`,`tote_id`,`tote_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `TOTEHDR`
--

DROP TABLE IF EXISTS `TOTEHDR`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TOTEHDR` (
  `tote_id` int(11) NOT NULL,
  `tote_code` varchar(18) DEFAULT ' ',
  `tote_company` smallint(6) DEFAULT '1',
  `tote_status` tinyint(4) DEFAULT '0',
  `tote_location` varchar(18) DEFAULT ' ',
  `tote_lastused` datetime DEFAULT NULL,
  `num_items` int(11) DEFAULT '0',
  `tote_type` char(3) DEFAULT NULL,
  `tote_ref` int(11) DEFAULT NULL,
  PRIMARY KEY (`tote_id`),
  UNIQUE KEY `TOTEHDR_idx1` (`tote_company`,`tote_location`,`tote_id`),
  UNIQUE KEY `TOTEHDR_idx2` (`tote_company`,`tote_id`),
  UNIQUE KEY `TOTEHDR_idx3` (`tote_company`,`tote_ref`,`tote_type`,`tote_id`),
  UNIQUE KEY `TOTEHDR_idx4` (`tote_company`,`tote_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`wms`@`localhost`*/ /*!50003 TRIGGER TOTEHDR_U
BEFORE UPDATE ON TOTEHDR FOR EACH ROW
BEGIN
    IF (OLD.tote_type = "RET") THEN 
     set NEW.tote_type = OLD.tote_type;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `UOMCODES`
--

DROP TABLE IF EXISTS `UOMCODES`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UOMCODES` (
  `uom_code` char(3) NOT NULL,
  `uom_desc` char(30) DEFAULT '',
  `uom_inv_code` char(1) DEFAULT '0',
  PRIMARY KEY (`uom_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `UPCLOG`
--

DROP TABLE IF EXISTS `UPCLOG`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UPCLOG` (
  `noupc_id` int(11) NOT NULL AUTO_INCREMENT,
  `source` varchar(3) NOT NULL DEFAULT '',
  `upc` varchar(20) NOT NULL DEFAULT '',
  `userId` int(11) NOT NULL DEFAULT '0',
  `shadow` int(11) NOT NULL DEFAULT '0',
  `qty` int(11) NOT NULL DEFAULT '1',
  `occurred` timestamp NULL DEFAULT NULL,
  `upc_status` smallint(6) DEFAULT '0',
  PRIMARY KEY (`noupc_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`wms`@`localhost`*/ /*!50003 TRIGGER UPCLOG_I
BEFORE INSERT ON UPCLOG FOR EACH ROW
BEGIN
        SET NEW.occurred = now();
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `USERPID`
--

DROP TABLE IF EXISTS `USERPID`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `USERPID` (
  `user_id` int(11) NOT NULL,
  `remote_address` varchar(32) DEFAULT NULL,
  `last_url` varchar(255) DEFAULT NULL,
  `device_type` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `USERPID_FK` FOREIGN KEY (`user_id`) REFERENCES `WEB_USERS` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `VENDORS`
--

DROP TABLE IF EXISTS `VENDORS`;
/*!50001 DROP VIEW IF EXISTS `VENDORS`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `VENDORS` (
  `vendor` tinyint NOT NULL,
  `name` tinyint NOT NULL,
  `addr1` tinyint NOT NULL,
  `addr2` tinyint NOT NULL,
  `city` tinyint NOT NULL,
  `state` tinyint NOT NULL,
  `zip` tinyint NOT NULL,
  `ctry` tinyint NOT NULL,
  `contact` tinyint NOT NULL,
  `phone` tinyint NOT NULL,
  `email` tinyint NOT NULL,
  `num_notes` tinyint NOT NULL,
  `last_rcpt` tinyint NOT NULL,
  `allow_bo` tinyint NOT NULL,
  `allow_to_bin` tinyint NOT NULL,
  `allow_inplace` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `WEB_GRAPHICS`
--

DROP TABLE IF EXISTS `WEB_GRAPHICS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WEB_GRAPHICS` (
  `image_url` varchar(64) NOT NULL,
  PRIMARY KEY (`image_url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `WEB_GROUPS`
--

DROP TABLE IF EXISTS `WEB_GROUPS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WEB_GROUPS` (
  `group_id` smallint(6) NOT NULL,
  `group_desc` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `WEB_MENU`
--

DROP TABLE IF EXISTS `WEB_MENU`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WEB_MENU` (
  `menu_num` smallint(6) NOT NULL,
  `menu_line` smallint(6) NOT NULL,
  `menu_desc` varchar(40) DEFAULT NULL,
  `menu_image` varchar(64) DEFAULT NULL,
  `menu_url` varchar(128) DEFAULT NULL,
  `menu_priv` smallint(6) DEFAULT NULL,
  `menu_target` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`menu_line`,`menu_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `WEB_THEMES`
--

DROP TABLE IF EXISTS `WEB_THEMES`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WEB_THEMES` (
  `theme_id` int(11) NOT NULL AUTO_INCREMENT,
  `theme_desc` varchar(30) DEFAULT NULL,
  `theme_path` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`theme_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `WEB_USERS`
--

DROP TABLE IF EXISTS `WEB_USERS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WEB_USERS` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(16) DEFAULT NULL,
  `passwd` varchar(16) DEFAULT NULL,
  `first_name` varchar(20) DEFAULT NULL,
  `last_name` varchar(24) DEFAULT NULL,
  `priv_from` tinyint(4) DEFAULT NULL,
  `priv_thru` tinyint(4) DEFAULT NULL,
  `sales_rep` smallint(6) DEFAULT NULL,
  `company_num` smallint(6) DEFAULT NULL,
  `home_menu` smallint(6) DEFAULT NULL,
  `status_flag` char(1) DEFAULT NULL,
  `group_id` smallint(6) DEFAULT NULL,
  `theme_id` decimal(9,0) DEFAULT NULL,
  `operator` smallint(6) DEFAULT NULL,
  `host_user_id` varchar(12) DEFAULT '',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `WEB_USERS_hash` (`username`,`passwd`,`user_id`),
  KEY `WEB_USER_GROUPS_FK` (`group_id`),
  CONSTRAINT `WEB_USER_GROUPS_FK` FOREIGN KEY (`group_id`) REFERENCES `WEB_GROUPS` (`group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `WHSEBINS`
--

DROP TABLE IF EXISTS `WHSEBINS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WHSEBINS` (
  `wb_company` smallint(6) DEFAULT NULL,
  `wb_location` char(18) DEFAULT NULL,
  `wb_zone` char(3) DEFAULT '',
  `wb_aisle` char(4) DEFAULT '',
  `wb_section` char(3) DEFAULT '',
  `wb_level` char(3) DEFAULT '',
  `wb_subin` char(3) DEFAULT '',
  `wb_width` smallint(6) DEFAULT NULL,
  `wb_depth` smallint(6) DEFAULT NULL,
  `wb_height` smallint(6) DEFAULT NULL,
  `wb_volume` decimal(10,2) DEFAULT NULL,
  `wb_pick` tinyint(4) DEFAULT NULL,
  `wb_recv` tinyint(4) DEFAULT NULL,
  `wb_status` char(1) DEFAULT NULL,
  UNIQUE KEY `WHSEBINS_hash` (`wb_company`,`wb_location`),
  UNIQUE KEY `WHSEBINS_idx1` (`wb_location`,`wb_company`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `WHSELOC`
--

DROP TABLE IF EXISTS `WHSELOC`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WHSELOC` (
  `whs_company` smallint(6) DEFAULT NULL,
  `whs_location` varchar(18) DEFAULT NULL,
  `whs_shadow` int(11) DEFAULT '0',
  `whs_code` char(2) DEFAULT '',
  `whs_qty` int(11) DEFAULT '0',
  `whs_uom` char(3) DEFAULT '',
  `whs_alloc` int(11) DEFAULT NULL,
  UNIQUE KEY `WHSELOC_hash` (`whs_location`,`whs_company`,`whs_shadow`),
  UNIQUE KEY `WHSELOC_idx1` (`whs_shadow`,`whs_company`,`whs_location`),
  KEY `WHSELOC_FK` (`whs_company`,`whs_location`),
  CONSTRAINT `WHSELOC_FK1` FOREIGN KEY (`whs_shadow`) REFERENCES `PARTS` (`shadow_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `WHSEQTY`
--

DROP TABLE IF EXISTS `WHSEQTY`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WHSEQTY` (
  `ms_shadow` int(11) DEFAULT NULL,
  `ms_company` smallint(6) DEFAULT NULL,
  `primary_bin` char(18) DEFAULT '',
  `qty_avail` int(11) DEFAULT '0',
  `qty_alloc` int(11) DEFAULT '0',
  `qty_putaway` int(11) DEFAULT '0',
  `qty_overstk` int(11) DEFAULT '0',
  `qty_on_order` int(11) DEFAULT '0',
  `qty_on_vendbo` int(11) DEFAULT '0',
  `qty_on_custbo` int(11) DEFAULT '0',
  `qty_defect` int(11) DEFAULT '0',
  `qty_core` int(11) DEFAULT '0',
  `max_shelf` int(11) DEFAULT '0',
  `minimum` int(11) DEFAULT '0',
  `maximum` int(11) DEFAULT '0',
  `cost` decimal(10,3) DEFAULT '0.000',
  `core` decimal(10,3) DEFAULT '0.000',
  UNIQUE KEY `WHSEQTY_hash` (`ms_shadow`,`ms_company`),
  KEY `WHSEQTY_FK1` (`ms_company`,`primary_bin`),
  CONSTRAINT `WHSEQTY_FK` FOREIGN KEY (`ms_shadow`) REFERENCES `PARTS` (`shadow_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `WHSEZONES`
--

DROP TABLE IF EXISTS `WHSEZONES`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WHSEZONES` (
  `zone_company` smallint(6) DEFAULT NULL,
  `zone_type` char(3) DEFAULT NULL,
  `zone` char(3) DEFAULT NULL,
  `zone_desc` char(30) DEFAULT NULL,
  `display_seq` tinyint(4) DEFAULT NULL,
  `is_pickable` tinyint(4) DEFAULT NULL,
  `zone_color` char(7) DEFAULT '',
  UNIQUE KEY `WHSEZONES_hash` (`zone_company`,`zone_type`,`zone`),
  UNIQUE KEY `WHSEZONES_idx1` (`zone_company`,`zone`,`zone_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `WMSCOMMERR`
--

DROP TABLE IF EXISTS `WMSCOMMERR`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WMSCOMMERR` (
  `errId` int(11) NOT NULL AUTO_INCREMENT,
  `utcDate` datetime NOT NULL,
  `recordType` varchar(20) DEFAULT ' ',
  `statusCode` smallint(6) DEFAULT '0',
  `retryTimes` smallint(6) DEFAULT '0',
  `lastRetry` datetime DEFAULT NULL,
  `payload` text,
  `response` text,
  PRIMARY KEY (`errId`),
  UNIQUE KEY `WMSCOMMERR_hash` (`utcDate`,`errId`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `WMSERROR`
--

DROP TABLE IF EXISTS `WMSERROR`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WMSERROR` (
  `errId` int(11) NOT NULL AUTO_INCREMENT,
  `utcDate` datetime NOT NULL,
  `recordType` varchar(6) DEFAULT ' ',
  `fileName` varchar(64) DEFAULT ' ',
  `rowNum` smallint(6) DEFAULT '0',
  `docNum` varchar(20) DEFAULT ' ',
  `message` text,
  `rowData` text,
  PRIMARY KEY (`errId`),
  UNIQUE KEY `WMSERROR_hash` (`utcDate`,`errId`)
) ENGINE=InnoDB AUTO_INCREMENT=14454 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `WMSLOCK`
--

DROP TABLE IF EXISTS `WMSLOCK`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WMSLOCK` (
  `tableName` varchar(48) DEFAULT '',
  `lockRow` varchar(255) DEFAULT '',
  `operation` varchar(10) DEFAULT '',
  `userId` int(11) DEFAULT '0',
  `dateAdded` datetime NOT NULL,
  UNIQUE KEY `WMSLOCK_hash` (`tableName`,`lockRow`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `coptions`
--

DROP TABLE IF EXISTS `coptions`;
/*!50001 DROP VIEW IF EXISTS `coptions`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `coptions` (
  `cop_company` tinyint NOT NULL,
  `cop_option` tinyint NOT NULL,
  `copt_desc` tinyint NOT NULL,
  `cop_flag` tinyint NOT NULL,
  `copt_cat` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `shipLabel`
--

DROP TABLE IF EXISTS `shipLabel`;
/*!50001 DROP VIEW IF EXISTS `shipLabel`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `shipLabel` (
  `order_num` tinyint NOT NULL,
  `carton_num` tinyint NOT NULL,
  `host_order_num` tinyint NOT NULL,
  `customer_id` tinyint NOT NULL,
  `cust_po_num` tinyint NOT NULL,
  `company_number` tinyint NOT NULL,
  `company_name` tinyint NOT NULL,
  `company_address` tinyint NOT NULL,
  `company_city` tinyint NOT NULL,
  `company_state` tinyint NOT NULL,
  `company_zip` tinyint NOT NULL,
  `priority` tinyint NOT NULL,
  `num_lines` tinyint NOT NULL,
  `ship_via` tinyint NOT NULL,
  `special_instr` tinyint NOT NULL,
  `shipping_instr` tinyint NOT NULL,
  `name` tinyint NOT NULL,
  `addr1` tinyint NOT NULL,
  `addr2` tinyint NOT NULL,
  `city` tinyint NOT NULL,
  `state` tinyint NOT NULL,
  `zip` tinyint NOT NULL,
  `ctry` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `tmp_noupc`
--

DROP TABLE IF EXISTS `tmp_noupc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tmp_noupc` (
  `pl` char(3) DEFAULT NULL,
  `partnum` char(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Temporary table structure for view `web_usergrp`
--

DROP TABLE IF EXISTS `web_usergrp`;
/*!50001 DROP VIEW IF EXISTS `web_usergrp`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `web_usergrp` (
  `user_id` tinyint NOT NULL,
  `username` tinyint NOT NULL,
  `passwd` tinyint NOT NULL,
  `first_name` tinyint NOT NULL,
  `last_name` tinyint NOT NULL,
  `priv_from` tinyint NOT NULL,
  `priv_thru` tinyint NOT NULL,
  `sales_rep` tinyint NOT NULL,
  `company_num` tinyint NOT NULL,
  `home_menu` tinyint NOT NULL,
  `status_flag` tinyint NOT NULL,
  `group_id` tinyint NOT NULL,
  `group_desc` tinyint NOT NULL,
  `theme_id` tinyint NOT NULL,
  `operator` tinyint NOT NULL,
  `host_user_id` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Dumping routines for database 'wms'
--
/*!50003 DROP PROCEDURE IF EXISTS `test_if` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
CREATE DEFINER=`wms`@`localhost` PROCEDURE `test_if`(
    IN comp SMALLINT,
    IN shadow VARCHAR(8)
)
BEGIN


    DECLARE qty_onhand INT DEFAULT 0;

    select qty_avail + qty_alloc + 4 into qty_onhand
    from WHSEQTY
    where ms_company = comp
    and ms_shadow = shadow;

    select qty_onhand;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `test_ph` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
CREATE DEFINER=`wms`@`localhost` PROCEDURE `test_ph`(   IN wms_trans_id   int,
                            IN shadow     int,
                            IN company    smallint,
                            IN psource      char(  10 ),
                            IN host_id   char(  20 ),
                            IN ext_ref  char( 20 ),
 			    IN trans_type char(  3 ),
                            IN qty_mdse   int,
 			    IN uom char(  3 ),
                            IN bin  varchar(18),
                            IN inv_code  char(  1 ),
                            IN mdse_price numeric (10,3),
                            IN core_price numeric (10,3),
                            IN qty_core   smallint,
                            IN qty_def smallint )
BEGIN

    DECLARE qty_onhand INT DEFAULT 0;
    DECLARE today      datetime;

    select qty_avail + qty_alloc into qty_onhand
    from WHSEQTY
    where ms_company = company
    and ms_shadow = shadow;

   INSERT INTO PARTHIST
         ( paud_id,                     
	   paud_shadow,
	   paud_company,
    	   paud_date,
           paud_source,                 
           paud_ref,                    
           paud_ext_ref,                
	   paud_type,
	   paud_qty,
	   paud_uom,
	   paud_bin,
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
	   bin,
           qty_onhand,
           inv_code,
           mdse_price,
           core_price,
           qty_core,
	   qty_def );

    select ROW_COUNT() as rc;
                           

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `test_trans` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
CREATE DEFINER=`wms`@`localhost` PROCEDURE `test_trans`(
    IN ctrl_comp SMALLINT,
    IN ctrl_key VARCHAR(8)
)
BEGIN
    DECLARE rc SMALLINT DEFAULT 0;
    DECLARE rc1 SMALLINT DEFAULT -35;

    START TRANSACTION;

    update CONTROL set control_number = control_number + 1
    where control_company = ctrl_comp and control_key = ctrl_key;

    set rc=ROW_COUNT();
    COMMIT;

    select control_number into rc1 from CONTROL
    where control_company = ctrl_comp and control_key = ctrl_key;

    select rc1;
    
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `wp_addPartHist` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
CREATE DEFINER=`wms`@`localhost` PROCEDURE `wp_addPartHist`(   
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
         ( paud_id,                     
	   paud_shadow,
	   paud_company,
    	   paud_date,
           paud_source,                 
           paud_ref,                    
           paud_ext_ref,                
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
                           

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `wp_updQty` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
CREATE DEFINER=`wms`@`localhost` PROCEDURE `wp_updQty`(   
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

    
    update WHSEQTY set primary_bin = bin 
    where ms_company = company
    and ms_shadow = shadow
    and primary_bin = "";

   INSERT INTO PARTHIST
         ( paud_id,                     
	   paud_shadow,
	   paud_company,
    	   paud_date,
           paud_source,                 
	   paud_user,
           paud_ref,                    
           paud_ext_ref,                
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
                           

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Final view structure for view `CUSTOMERS`
--

/*!50001 DROP TABLE IF EXISTS `CUSTOMERS`*/;
/*!50001 DROP VIEW IF EXISTS `CUSTOMERS`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`wms`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `CUSTOMERS` AS select `ENTITY`.`host_id` AS `customer`,`ENTITY`.`name` AS `name`,`ENTITY`.`addr1` AS `addr1`,`ENTITY`.`addr2` AS `addr2`,`ENTITY`.`city` AS `city`,`ENTITY`.`state` AS `state`,`ENTITY`.`zip` AS `zip`,`ENTITY`.`ctry` AS `ctry`,`ENTITY`.`contact` AS `contact`,`ENTITY`.`phone` AS `phone`,`ENTITY`.`email` AS `email`,`ENTITY`.`ship_via` AS `ship_via`,`ENTITY`.`num_notes` AS `num_notes`,`ENTITY`.`last_trans` AS `last_trans`,`ENTITY`.`allow_bo` AS `allow_bo` from `ENTITY` where (`ENTITY`.`entity_type` = 'C') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `DUPEALT`
--

/*!50001 DROP TABLE IF EXISTS `DUPEALT`*/;
/*!50001 DROP VIEW IF EXISTS `DUPEALT`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`wms`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `DUPEALT` AS (select `C`.`alt_part_number` AS `upc`,`C`.`alt_shadow_num` AS `shad`,count(0) AS `cnt` from `ALTERNAT` `C` where (`C`.`alt_type_code` < 0) group by `C`.`alt_part_number` having (count(0) > 1)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `VENDORS`
--

/*!50001 DROP TABLE IF EXISTS `VENDORS`*/;
/*!50001 DROP VIEW IF EXISTS `VENDORS`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`wms`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `VENDORS` AS select `ENTITY`.`host_id` AS `vendor`,`ENTITY`.`name` AS `name`,`ENTITY`.`addr1` AS `addr1`,`ENTITY`.`addr2` AS `addr2`,`ENTITY`.`city` AS `city`,`ENTITY`.`state` AS `state`,`ENTITY`.`zip` AS `zip`,`ENTITY`.`ctry` AS `ctry`,`ENTITY`.`contact` AS `contact`,`ENTITY`.`phone` AS `phone`,`ENTITY`.`email` AS `email`,`ENTITY`.`num_notes` AS `num_notes`,`ENTITY`.`last_trans` AS `last_rcpt`,`ENTITY`.`allow_bo` AS `allow_bo`,`ENTITY`.`allow_to_bin` AS `allow_to_bin`,`ENTITY`.`allow_inplace` AS `allow_inplace` from `ENTITY` where (`ENTITY`.`entity_type` = 'V') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `coptions`
--

/*!50001 DROP TABLE IF EXISTS `coptions`*/;
/*!50001 DROP VIEW IF EXISTS `coptions`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`wms`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `coptions` AS select `A`.`cop_company` AS `cop_company`,`A`.`cop_option` AS `cop_option`,`B`.`copt_desc` AS `copt_desc`,`A`.`cop_flag` AS `cop_flag`,`B`.`copt_cat` AS `copt_cat` from (`COPTIONS` `A` join `COPTDESC` `B`) where (`B`.`copt_number` = `A`.`cop_option`) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `shipLabel`
--

/*!50001 DROP TABLE IF EXISTS `shipLabel`*/;
/*!50001 DROP VIEW IF EXISTS `shipLabel`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`wms`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `shipLabel` AS select distinct `A`.`order_num` AS `order_num`,`C`.`carton_num` AS `carton_num`,`A`.`host_order_num` AS `host_order_num`,`A`.`customer_id` AS `customer_id`,`A`.`cust_po_num` AS `cust_po_num`,`COMPANY`.`company_number` AS `company_number`,`COMPANY`.`company_name` AS `company_name`,`COMPANY`.`company_address` AS `company_address`,`COMPANY`.`company_city` AS `company_city`,`COMPANY`.`company_state` AS `company_state`,`COMPANY`.`company_zip` AS `company_zip`,`A`.`priority` AS `priority`,`A`.`num_lines` AS `num_lines`,`A`.`ship_via` AS `ship_via`,`A`.`special_instr` AS `special_instr`,`A`.`shipping_instr` AS `shipping_instr`,`B`.`name` AS `name`,`B`.`addr1` AS `addr1`,`B`.`addr2` AS `addr2`,`B`.`city` AS `city`,`B`.`state` AS `state`,`B`.`zip` AS `zip`,`B`.`ctry` AS `ctry` from (((`ORDERS` `A` join `CUSTOMERS` `B`) join `ORDPACK` `C`) join `COMPANY`) where ((`B`.`customer` = `A`.`customer_id`) and (`C`.`order_num` = `A`.`order_num`) and (`COMPANY`.`company_number` = `A`.`company`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `web_usergrp`
--

/*!50001 DROP TABLE IF EXISTS `web_usergrp`*/;
/*!50001 DROP VIEW IF EXISTS `web_usergrp`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`wms`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `web_usergrp` AS select `A`.`user_id` AS `user_id`,`A`.`username` AS `username`,`A`.`passwd` AS `passwd`,`A`.`first_name` AS `first_name`,`A`.`last_name` AS `last_name`,`A`.`priv_from` AS `priv_from`,`A`.`priv_thru` AS `priv_thru`,`A`.`sales_rep` AS `sales_rep`,`A`.`company_num` AS `company_num`,`A`.`home_menu` AS `home_menu`,`A`.`status_flag` AS `status_flag`,`A`.`group_id` AS `group_id`,`B`.`group_desc` AS `group_desc`,`A`.`theme_id` AS `theme_id`,`A`.`operator` AS `operator`,`A`.`host_user_id` AS `host_user_id` from (`WEB_USERS` `A` join `WEB_GROUPS` `B`) where (`B`.`group_id` = `A`.`group_id`) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-08-02 13:01:09
