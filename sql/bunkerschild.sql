-- MySQL dump 10.13  Distrib 5.1.73, for openwrt-linux-gnu (mips)
--
-- Host: 127.0.0.1    Database: bunkerschild_main
-- ------------------------------------------------------
-- Server version	5.1.73

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
-- Table structure for table `acl`
--

DROP TABLE IF EXISTS `acl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acl` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `timestamp_registration` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `requesting_device_uid` int(10) unsigned NOT NULL,
  `accessing_device_uid` int(10) unsigned NOT NULL,
  `active_timing` varchar(32) NOT NULL DEFAULT '* * * * *',
  `flag_read` tinyint(1) NOT NULL DEFAULT '1',
  `flag_write` tinyint(1) NOT NULL DEFAULT '1',
  `flag_enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`uid`),
  KEY `requesting_device_uid` (`requesting_device_uid`,`accessing_device_uid`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `acl`
--

LOCK TABLES `acl` WRITE;
/*!40000 ALTER TABLE `acl` DISABLE KEYS */;
/*!40000 ALTER TABLE `acl` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `actor`
--

DROP TABLE IF EXISTS `actor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actor` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `timestamp_last_set_actor` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `timestamp_last_set_remote` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `timestamp_last_error` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `timestamp_registration` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `device_uid` int(10) unsigned NOT NULL,
  `channel` smallint(5) unsigned NOT NULL DEFAULT '1',
  `name` varchar(48) NOT NULL,
  `value` varchar(16) NOT NULL,
  `flag_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `last_error` varchar(96) DEFAULT NULL,
  PRIMARY KEY (`uid`),
  KEY `device_uid` (`device_uid`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `actor`
--

LOCK TABLES `actor` WRITE;
/*!40000 ALTER TABLE `actor` DISABLE KEYS */;
/*!40000 ALTER TABLE `actor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `device`
--

DROP TABLE IF EXISTS `device`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `device` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `timestamp_last_seen` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `timestamp_registration` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `timestamp_device_rtc` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `model_uid` int(10) unsigned NOT NULL,
  `room_uid` int(10) unsigned DEFAULT NULL,
  `serial` varchar(32) NOT NULL,
  `hwaddress` varchar(17) NOT NULL,
  `ipaddress` varchar(64) NOT NULL,
  `hostname` varchar(48) NOT NULL,
  `topicname` varchar(48) DEFAULT NULL,
  `groupname` varchar(48) DEFAULT NULL,
  `friendlyname` varchar(48) DEFAULT NULL,
  `name` varchar(48) NOT NULL,
  `firmware` varchar(48) DEFAULT NULL,
  `uptime` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `bootcount` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `type` set('actor','sensor','audio-hid','fingerprinter-hid','rfid-hid','nfc-hid','camera','display') NOT NULL DEFAULT 'actor',
  `emulation` enum('none','wemo','hue') NOT NULL DEFAULT 'none',
  `flag_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `flag_online` tinyint(1) NOT NULL DEFAULT '0',
  `flag_physical` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`uid`),
  KEY `vendor_uid` (`model_uid`),
  KEY `room_uid` (`room_uid`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `device`
--

LOCK TABLES `device` WRITE;
/*!40000 ALTER TABLE `device` DISABLE KEYS */;
/*!40000 ALTER TABLE `device` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group`
--

DROP TABLE IF EXISTS `group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `timestamp_registration` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `name` varchar(64) NOT NULL,
  `flag_enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group`
--

LOCK TABLES `group` WRITE;
/*!40000 ALTER TABLE `group` DISABLE KEYS */;
/*!40000 ALTER TABLE `group` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER group_insert BEFORE INSERT ON `group`
  FOR EACH ROW
  BEGIN
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
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER group_update AFTER UPDATE ON `group`
  FOR EACH ROW
  BEGIN
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
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER group_delete BEFORE DELETE ON `group`
  FOR EACH ROW
  BEGIN
  END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `level`
--

DROP TABLE IF EXISTS `level`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `level` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `timestamp_registration` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `location_uid` int(11) unsigned NOT NULL,
  `value` smallint(6) NOT NULL,
  `name` varchar(64) NOT NULL,
  `flag_enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `location_uid_2` (`location_uid`,`value`,`name`),
  KEY `location_uid` (`location_uid`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `level`
--

LOCK TABLES `level` WRITE;
/*!40000 ALTER TABLE `level` DISABLE KEYS */;
/*!40000 ALTER TABLE `level` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `location`
--

DROP TABLE IF EXISTS `location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `timestamp_registration` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `name` varchar(64) NOT NULL,
  `address` varchar(96) NOT NULL,
  `zipcode` varchar(5) NOT NULL,
  `city` varchar(64) NOT NULL,
  `country` varchar(48) NOT NULL,
  `flag_enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location`
--

LOCK TABLES `location` WRITE;
/*!40000 ALTER TABLE `location` DISABLE KEYS */;
/*!40000 ALTER TABLE `location` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `timestamp_registration` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `table` varchar(48) NOT NULL,
  `action` enum('insert','update','delete') NOT NULL,
  `log` varchar(160) NOT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log`
--

LOCK TABLES `log` WRITE;
/*!40000 ALTER TABLE `log` DISABLE KEYS */;
/*!40000 ALTER TABLE `log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model`
--

DROP TABLE IF EXISTS `model`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_uid` int(10) unsigned NOT NULL,
  `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `timestamp_registration` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `name` varchar(64) NOT NULL,
  `flag_enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`uid`),
  KEY `vendor_uid` (`vendor_uid`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model`
--

LOCK TABLES `model` WRITE;
/*!40000 ALTER TABLE `model` DISABLE KEYS */;
/*!40000 ALTER TABLE `model` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `queue`
--

DROP TABLE IF EXISTS `queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `queue` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp_registration` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actor_uid` int(10) unsigned NOT NULL,
  `value` varchar(16) NOT NULL,
  PRIMARY KEY (`uid`),
  KEY `actor_uid` (`actor_uid`)
) ENGINE=MyISAM AUTO_INCREMENT=551 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `queue`
--

LOCK TABLES `queue` WRITE;
/*!40000 ALTER TABLE `queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room`
--

DROP TABLE IF EXISTS `room`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `timestamp_registration` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `level_uid` int(11) unsigned NOT NULL,
  `name` varchar(64) NOT NULL,
  `flag_enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`uid`),
  KEY `level_uid` (`level_uid`)
) ENGINE=MyISAM AUTO_INCREMENT=24 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room`
--

LOCK TABLES `room` WRITE;
/*!40000 ALTER TABLE `room` DISABLE KEYS */;
/*!40000 ALTER TABLE `room` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sensor`
--

DROP TABLE IF EXISTS `sensor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sensor` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `timestamp_registration` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `device_uid` int(10) unsigned NOT NULL,
  `sensor_index` smallint(5) unsigned NOT NULL DEFAULT '0',
  `groupname` varchar(48) NOT NULL,
  `keyname` varchar(48) NOT NULL,
  `name` varchar(48) NOT NULL,
  `unit` varchar(16) DEFAULT NULL,
  `value` varchar(16) DEFAULT NULL,
  `flag_enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`uid`),
  KEY `device_uid` (`device_uid`)
) ENGINE=MyISAM AUTO_INCREMENT=59 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sensor`
--

LOCK TABLES `sensor` WRITE;
/*!40000 ALTER TABLE `sensor` DISABLE KEYS */;
/*!40000 ALTER TABLE `sensor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `timestamp_registration` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `name` varchar(64) NOT NULL,
  `username` varchar(64) NOT NULL,
  `password` varchar(40) NOT NULL,
  `pin` varchar(40) NOT NULL,
  `flag_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `flag_admin` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usergroup`
--

DROP TABLE IF EXISTS `usergroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usergroup` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `timestamp_registration` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_uid` int(10) unsigned NOT NULL,
  `group_uid` int(10) unsigned NOT NULL,
  `flag_enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `user_uid` (`user_uid`,`group_uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usergroup`
--

LOCK TABLES `usergroup` WRITE;
/*!40000 ALTER TABLE `usergroup` DISABLE KEYS */;
/*!40000 ALTER TABLE `usergroup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor`
--

DROP TABLE IF EXISTS `vendor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendor` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `timestamp_registration` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `name` varchar(64) NOT NULL,
  `flag_enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor`
--

LOCK TABLES `vendor` WRITE;
/*!40000 ALTER TABLE `vendor` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wifi`
--

DROP TABLE IF EXISTS `wifi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wifi` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp_last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `timestamp_registration` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `device_uid` int(10) unsigned NOT NULL,
  `ssid` varchar(64) NOT NULL,
  `hwaddr_ap` varchar(17) NOT NULL,
  `rssi` smallint(5) unsigned NOT NULL,
  `flag_enabled` bigint(20) NOT NULL DEFAULT '1',
  `flag_ap` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  KEY `device_uid` (`device_uid`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wifi`
--

LOCK TABLES `wifi` WRITE;
/*!40000 ALTER TABLE `wifi` DISABLE KEYS */;
/*!40000 ALTER TABLE `wifi` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-03-12  2:45:21
