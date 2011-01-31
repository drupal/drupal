-- MySQL dump 10.13  Distrib 5.1.24-rc, for portbld-freebsd6.3 (i386)
--
-- Host: localhost    Database: vvs_xlite_cms
-- ------------------------------------------------------
-- Server version	5.1.24-rc

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
-- Table structure for table `drupal_actions`
--

DROP TABLE IF EXISTS `drupal_actions`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_actions` (
  `aid` varchar(255) NOT NULL DEFAULT '0' COMMENT 'Primary Key: Unique actions ID.',
  `type` varchar(32) NOT NULL DEFAULT '' COMMENT 'The object that that action acts on (node, user, comment, system or custom types.)',
  `callback` varchar(255) NOT NULL DEFAULT '' COMMENT 'The callback function that executes when the action runs.',
  `parameters` longblob NOT NULL COMMENT 'Parameters to be passed to the callback function.',
  `label` varchar(255) NOT NULL DEFAULT '0' COMMENT 'Label of the action.',
  PRIMARY KEY (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores action information.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_actions`
--

LOCK TABLES `drupal_actions` WRITE;
/*!40000 ALTER TABLE `drupal_actions` DISABLE KEYS */;
INSERT INTO `drupal_actions` VALUES ('comment_publish_action','comment','comment_publish_action','','Publish comment');
INSERT INTO `drupal_actions` VALUES ('comment_save_action','comment','comment_save_action','','Save comment');
INSERT INTO `drupal_actions` VALUES ('comment_unpublish_action','comment','comment_unpublish_action','','Unpublish comment');
INSERT INTO `drupal_actions` VALUES ('node_make_sticky_action','node','node_make_sticky_action','','Make content sticky');
INSERT INTO `drupal_actions` VALUES ('node_make_unsticky_action','node','node_make_unsticky_action','','Make content unsticky');
INSERT INTO `drupal_actions` VALUES ('node_promote_action','node','node_promote_action','','Promote content to front page');
INSERT INTO `drupal_actions` VALUES ('node_publish_action','node','node_publish_action','','Publish content');
INSERT INTO `drupal_actions` VALUES ('node_save_action','node','node_save_action','','Save content');
INSERT INTO `drupal_actions` VALUES ('node_unpromote_action','node','node_unpromote_action','','Remove content from front page');
INSERT INTO `drupal_actions` VALUES ('node_unpublish_action','node','node_unpublish_action','','Unpublish content');
INSERT INTO `drupal_actions` VALUES ('system_block_ip_action','user','system_block_ip_action','','Ban IP address of current user');
INSERT INTO `drupal_actions` VALUES ('user_block_user_action','user','user_block_user_action','','Block current user');
/*!40000 ALTER TABLE `drupal_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_authmap`
--

DROP TABLE IF EXISTS `drupal_authmap`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_authmap` (
  `aid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary Key: Unique authmap ID.',
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT 'User’s drupal_users.uid.',
  `authname` varchar(128) NOT NULL DEFAULT '' COMMENT 'Unique authentication name.',
  `module` varchar(128) NOT NULL DEFAULT '' COMMENT 'Module which is controlling the authentication.',
  PRIMARY KEY (`aid`),
  UNIQUE KEY `authname` (`authname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores distributed authentication mapping.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_authmap`
--

LOCK TABLES `drupal_authmap` WRITE;
/*!40000 ALTER TABLE `drupal_authmap` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_authmap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_batch`
--

DROP TABLE IF EXISTS `drupal_batch`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_batch` (
  `bid` int(10) unsigned NOT NULL COMMENT 'Primary Key: Unique batch ID.',
  `token` varchar(64) NOT NULL COMMENT 'A string token generated against the current user’s session id and the batch id, used to ensure that only the user who submitted the batch can effectively access it.',
  `timestamp` int(11) NOT NULL COMMENT 'A Unix timestamp indicating when this batch was submitted for processing. Stale batches are purged at cron time.',
  `batch` longblob COMMENT 'A serialized array containing the processing data for the batch.',
  PRIMARY KEY (`bid`),
  KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores details about batches (processes that run in...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_batch`
--

LOCK TABLES `drupal_batch` WRITE;
/*!40000 ALTER TABLE `drupal_batch` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_batch` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_block`
--

DROP TABLE IF EXISTS `drupal_block`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_block` (
  `bid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary Key: Unique block ID.',
  `module` varchar(64) NOT NULL DEFAULT '' COMMENT 'The module from which the block originates; for example, ’user’ for the Who’s Online block, and ’block’ for any custom blocks.',
  `delta` varchar(32) NOT NULL DEFAULT '0' COMMENT 'Unique ID for block within a module.',
  `theme` varchar(64) NOT NULL DEFAULT '' COMMENT 'The theme under which the block settings apply.',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Block enabled status. (1 = enabled, 0 = disabled)',
  `weight` int(11) NOT NULL DEFAULT '0' COMMENT 'Block weight within region.',
  `region` varchar(64) NOT NULL DEFAULT '' COMMENT 'Theme region within which the block is set.',
  `custom` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Flag to indicate how users may control visibility of the block. (0 = Users cannot control, 1 = On by default, but can be hidden, 2 = Hidden by default, but can be shown)',
  `visibility` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Flag to indicate how to show blocks on pages. (0 = Show on all pages except listed pages, 1 = Show only on listed pages, 2 = Use custom PHP code to determine visibility)',
  `pages` text NOT NULL COMMENT 'Contents of the "Pages" block; contains either a list of paths on which to include/exclude the block or PHP code, depending on "visibility" setting.',
  `title` varchar(64) NOT NULL DEFAULT '' COMMENT 'Custom title for the block. (Empty string will use block default title, <none> will remove the title, text will cause block to use specified title.)',
  `cache` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Binary flag to indicate block cache mode. (-2: Custom cache, -1: Do not cache, 1: Cache per role, 2: Cache per user, 4: Cache per page, 8: Block cache global) See DRUPAL_CACHE_* constants in ../includes/common.inc for more detailed information.',
  PRIMARY KEY (`bid`),
  UNIQUE KEY `tmd` (`theme`,`module`,`delta`),
  KEY `list` (`theme`,`status`,`region`,`weight`,`module`)
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8 COMMENT='Stores block settings, such as region and visibility...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_block`
--

LOCK TABLES `drupal_block` WRITE;
/*!40000 ALTER TABLE `drupal_block` DISABLE KEYS */;
INSERT INTO `drupal_block` VALUES (1,'system','main','bartik',1,0,'content',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (2,'search','form','bartik',1,-1,'sidebar_first',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (3,'node','recent','seven',1,10,'dashboard_main',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (4,'user','login','bartik',1,0,'sidebar_first',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (5,'system','navigation','bartik',1,0,'sidebar_first',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (6,'system','powered-by','bartik',1,10,'footer',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (7,'system','help','bartik',1,0,'help',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (8,'system','main','seven',1,0,'content',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (9,'system','help','seven',1,0,'help',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (10,'user','login','seven',1,10,'content',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (11,'user','new','seven',1,0,'dashboard_sidebar',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (12,'search','form','seven',1,-10,'dashboard_sidebar',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (13,'comment','recent','bartik',0,0,'-1',0,0,'','',1);
INSERT INTO `drupal_block` VALUES (14,'node','syndicate','bartik',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (15,'node','recent','bartik',0,0,'-1',0,0,'','',1);
INSERT INTO `drupal_block` VALUES (16,'shortcut','shortcuts','bartik',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (17,'system','management','bartik',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (18,'system','user-menu','bartik',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (19,'system','main-menu','bartik',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (20,'user','new','bartik',0,0,'-1',0,0,'','',1);
INSERT INTO `drupal_block` VALUES (21,'user','online','bartik',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (22,'comment','recent','seven',1,0,'dashboard_inactive',0,0,'','',1);
INSERT INTO `drupal_block` VALUES (23,'node','syndicate','seven',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (24,'shortcut','shortcuts','seven',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (25,'system','powered-by','seven',0,10,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (26,'system','navigation','seven',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (27,'system','management','seven',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (28,'system','user-menu','seven',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (29,'system','main-menu','seven',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (30,'user','online','seven',1,0,'dashboard_inactive',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (31,'forum','active','bartik',0,0,'-1',0,0,'','',-2);
INSERT INTO `drupal_block` VALUES (32,'forum','new','bartik',0,0,'-1',0,0,'','',-2);
INSERT INTO `drupal_block` VALUES (33,'print','print-links','bartik',0,0,'-1',0,0,'','',4);
INSERT INTO `drupal_block` VALUES (34,'print','print-top','bartik',0,0,'-1',0,0,'','',8);
INSERT INTO `drupal_block` VALUES (35,'forum','active','seven',1,0,'dashboard_inactive',0,0,'','',-2);
INSERT INTO `drupal_block` VALUES (36,'forum','new','seven',1,0,'dashboard_inactive',0,0,'','',-2);
INSERT INTO `drupal_block` VALUES (37,'print','print-links','seven',0,0,'-1',0,0,'','',4);
INSERT INTO `drupal_block` VALUES (38,'print','print-top','seven',0,0,'-1',0,0,'','',8);
INSERT INTO `drupal_block` VALUES (39,'comment','recent','lc3_clean',0,-7,'-1',0,0,'','',1);
INSERT INTO `drupal_block` VALUES (40,'forum','active','lc3_clean',0,-14,'-1',0,0,'','',-2);
INSERT INTO `drupal_block` VALUES (41,'forum','new','lc3_clean',0,-10,'-1',0,0,'','',-2);
INSERT INTO `drupal_block` VALUES (42,'node','recent','lc3_clean',0,-6,'-1',0,0,'','',1);
INSERT INTO `drupal_block` VALUES (43,'node','syndicate','lc3_clean',0,-4,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (44,'print','print-links','lc3_clean',0,-8,'-1',0,0,'','',4);
INSERT INTO `drupal_block` VALUES (45,'print','print-top','lc3_clean',0,-11,'-1',0,0,'','',8);
INSERT INTO `drupal_block` VALUES (46,'search','form','lc3_clean',0,-10,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (47,'shortcut','shortcuts','lc3_clean',0,-5,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (48,'system','help','lc3_clean',1,0,'help',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (49,'system','main','lc3_clean',1,-14,'content',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (50,'system','main-menu','lc3_clean',0,-13,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (51,'system','management','lc3_clean',0,-12,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (52,'system','navigation','lc3_clean',1,-13,'sidebar_first',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (53,'system','powered-by','lc3_clean',0,-9,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (54,'system','user-menu','lc3_clean',1,-3,'sidebar_first',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (55,'user','login','lc3_clean',1,-12,'sidebar_first',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (56,'user','new','lc3_clean',0,-2,'-1',0,0,'','',1);
INSERT INTO `drupal_block` VALUES (57,'user','online','lc3_clean',0,-1,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (58,'block','1','bartik',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (59,'block','1','lc3_clean',1,-14,'sidebar_first',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (60,'block','1','seven',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (61,'block','2','bartik',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (62,'block','2','lc3_clean',1,-13,'content',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (63,'block','2','seven',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (64,'block','3','bartik',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (65,'block','3','lc3_clean',1,-12,'content',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (66,'block','3','seven',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (67,'block','4','bartik',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (68,'block','4','lc3_clean',1,0,'footer',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (69,'block','4','seven',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (70,'block','5','bartik',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (71,'block','5','lc3_clean',1,0,'search',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (72,'block','5','seven',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (73,'block','6','bartik',0,0,'-1',0,0,'','Search',-1);
INSERT INTO `drupal_block` VALUES (74,'block','6','lc3_clean',1,-11,'content',0,0,'','Search',-1);
INSERT INTO `drupal_block` VALUES (75,'block','6','seven',0,0,'-1',0,0,'','Search',-1);
INSERT INTO `drupal_block` VALUES (76,'block','7','bartik',0,0,'-1',0,0,'','Bestsellers',-1);
INSERT INTO `drupal_block` VALUES (77,'block','7','lc3_clean',1,-10,'content',0,0,'','Bestsellers',-1);
INSERT INTO `drupal_block` VALUES (78,'block','7','seven',0,0,'-1',0,0,'','Bestsellers',-1);
INSERT INTO `drupal_block` VALUES (79,'block','8','bartik',0,0,'-1',0,0,'','Featured products',-1);
INSERT INTO `drupal_block` VALUES (80,'block','8','lc3_clean',1,-9,'content',0,0,'','Featured products',-1);
INSERT INTO `drupal_block` VALUES (81,'block','8','seven',0,0,'-1',0,0,'','Featured products',-1);
INSERT INTO `drupal_block` VALUES (82,'block','9','bartik',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (83,'block','9','lc3_clean',1,0,'header',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (84,'block','9','seven',0,0,'-1',0,0,'','',-1);
INSERT INTO `drupal_block` VALUES (85,'blog','recent','bartik',0,0,'-1',0,0,'','',1);
INSERT INTO `drupal_block` VALUES (86,'blog','recent','lc3_clean',0,0,'-1',0,0,'','',1);
INSERT INTO `drupal_block` VALUES (87,'blog','recent','seven',1,0,'dashboard_inactive',0,0,'','',1);
/*!40000 ALTER TABLE `drupal_block` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_block_custom`
--

DROP TABLE IF EXISTS `drupal_block_custom`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_block_custom` (
  `bid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'The block’s drupal_block.bid.',
  `body` longtext COMMENT 'Block contents.',
  `info` varchar(128) NOT NULL DEFAULT '' COMMENT 'Block description.',
  `format` varchar(255) DEFAULT NULL COMMENT 'The drupal_filter_format.format of the block body.',
  `lc_class` varchar(255) DEFAULT NULL COMMENT 'LC class',
  PRIMARY KEY (`bid`),
  UNIQUE KEY `info` (`info`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COMMENT='Stores contents of custom-made blocks.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_block_custom`
--

LOCK TABLES `drupal_block_custom` WRITE;
/*!40000 ALTER TABLE `drupal_block_custom` DISABLE KEYS */;
INSERT INTO `drupal_block_custom` VALUES (1,'____FROM_LC____','Top categories','filtered_html','\\XLite\\View\\TopCategories');
INSERT INTO `drupal_block_custom` VALUES (2,'____FROM_LC____','Subcategories','filtered_html','\\XLite\\View\\Subcategories');
INSERT INTO `drupal_block_custom` VALUES (3,'____FROM_LC____','Category products','filtered_html','\\XLite\\View\\ItemsList\\Product\\Customer\\Category');
INSERT INTO `drupal_block_custom` VALUES (4,'____FROM_LC____','Powered by LC CMS','filtered_html','\\XLite\\View\\PoweredBy');
INSERT INTO `drupal_block_custom` VALUES (5,'____FROM_LC____','Simple search form','filtered_html','\\XLite\\View\\Form\\Product\\Search\\Customer\\Simple');
INSERT INTO `drupal_block_custom` VALUES (6,'____FROM_LC____','Search products list','filtered_html','\\XLite\\View\\Search');
INSERT INTO `drupal_block_custom` VALUES (7,'____FROM_LC____','Bestsellers','filtered_html','\\XLite\\Module\\CDev\\Bestsellers\\View\\Bestsellers');
INSERT INTO `drupal_block_custom` VALUES (8,'____FROM_LC____','Featured products','filtered_html','\\XLite\\Module\\CDev\\FeaturedProducts\\View\\Customer\\FeaturedProducts');
INSERT INTO `drupal_block_custom` VALUES (9,'____FROM_LC____','Minicart','filtered_html','\\XLite\\View\\Minicart');
/*!40000 ALTER TABLE `drupal_block_custom` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_block_lc_widget_settings`
--

DROP TABLE IF EXISTS `drupal_block_lc_widget_settings`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_block_lc_widget_settings` (
  `bid` int(11) NOT NULL DEFAULT '0' COMMENT 'Block id',
  `name` char(32) NOT NULL DEFAULT '' COMMENT 'Setting code',
  `value` varchar(255) DEFAULT NULL COMMENT 'Setting value',
  UNIQUE KEY `bid_name` (`bid`,`name`),
  KEY `bid` (`bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='List of LC widget settings';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_block_lc_widget_settings`
--

LOCK TABLES `drupal_block_lc_widget_settings` WRITE;
/*!40000 ALTER TABLE `drupal_block_lc_widget_settings` DISABLE KEYS */;
INSERT INTO `drupal_block_lc_widget_settings` VALUES (1,'displayMode','list');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (1,'rootId','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (2,'displayMode','icons');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (2,'iconHeight','170');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (2,'iconWidth','170');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (3,'displayMode','grid');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (3,'gridColumns','3');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (3,'iconHeight','180');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (3,'iconWidth','180');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (3,'itemsPerPage','10');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (3,'showAdd2Cart','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (3,'showAllItemsPerPage','0');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (3,'showDescription','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (3,'showDisplayModeSelector','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (3,'showItemsPerPageSelector','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (3,'showPrice','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (3,'showSortBySelector','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (3,'showThumbnail','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (3,'sidebarMaxItems','5');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (3,'widgetType','center');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (7,'displayMode','grid');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (7,'gridColumns','3');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (7,'iconHeight','180');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (7,'iconWidth','180');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (7,'itemsPerPage','0');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (7,'rootId','0');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (7,'showAdd2Cart','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (7,'showAllItemsPerPage','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (7,'showDescription','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (7,'showDisplayModeSelector','0');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (7,'showItemsPerPageSelector','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (7,'showPrice','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (7,'showSortBySelector','0');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (7,'showThumbnail','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (7,'sidebarMaxItems','5');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (7,'useNode','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (7,'widgetType','center');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (8,'displayMode','grid');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (8,'gridColumns','3');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (8,'iconHeight','180');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (8,'iconWidth','180');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (8,'itemsPerPage','0');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (8,'showAdd2Cart','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (8,'showAllItemsPerPage','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (8,'showDescription','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (8,'showDisplayModeSelector','0');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (8,'showItemsPerPageSelector','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (8,'showPrice','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (8,'showSortBySelector','0');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (8,'showThumbnail','1');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (8,'sidebarMaxItems','5');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (8,'widgetType','center');
INSERT INTO `drupal_block_lc_widget_settings` VALUES (9,'displayMode','horizontal');
/*!40000 ALTER TABLE `drupal_block_lc_widget_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_block_node_type`
--

DROP TABLE IF EXISTS `drupal_block_node_type`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_block_node_type` (
  `module` varchar(64) NOT NULL COMMENT 'The block’s origin module, from drupal_block.module.',
  `delta` varchar(32) NOT NULL COMMENT 'The block’s unique delta within module, from drupal_block.delta.',
  `type` varchar(32) NOT NULL COMMENT 'The machine-readable name of this type from drupal_node_type.type.',
  PRIMARY KEY (`module`,`delta`,`type`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Sets up display criteria for blocks based on content types';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_block_node_type`
--

LOCK TABLES `drupal_block_node_type` WRITE;
/*!40000 ALTER TABLE `drupal_block_node_type` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_block_node_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_block_role`
--

DROP TABLE IF EXISTS `drupal_block_role`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_block_role` (
  `module` varchar(64) NOT NULL COMMENT 'The block’s origin module, from drupal_block.module.',
  `delta` varchar(32) NOT NULL COMMENT 'The block’s unique delta within module, from drupal_block.delta.',
  `rid` int(10) unsigned NOT NULL COMMENT 'The user’s role ID from drupal_users_roles.rid.',
  PRIMARY KEY (`module`,`delta`,`rid`),
  KEY `rid` (`rid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Sets up access permissions for blocks based on user roles';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_block_role`
--

LOCK TABLES `drupal_block_role` WRITE;
/*!40000 ALTER TABLE `drupal_block_role` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_block_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_blocked_ips`
--

DROP TABLE IF EXISTS `drupal_blocked_ips`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_blocked_ips` (
  `iid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary Key: unique ID for IP addresses.',
  `ip` varchar(40) NOT NULL DEFAULT '' COMMENT 'IP address',
  PRIMARY KEY (`iid`),
  KEY `blocked_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores blocked IP addresses.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_blocked_ips`
--

LOCK TABLES `drupal_blocked_ips` WRITE;
/*!40000 ALTER TABLE `drupal_blocked_ips` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_blocked_ips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_cache`
--

DROP TABLE IF EXISTS `drupal_cache`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_cache` (
  `cid` varchar(255) NOT NULL DEFAULT '' COMMENT 'Primary Key: Unique cache ID.',
  `data` longblob COMMENT 'A collection of data to cache.',
  `expire` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry should expire, or 0 for never.',
  `created` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry was created.',
  `serialized` smallint(6) NOT NULL DEFAULT '0' COMMENT 'A flag to indicate whether content is serialized (1) or not (0).',
  PRIMARY KEY (`cid`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Generic cache table for caching things not separated out...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_cache`
--

LOCK TABLES `drupal_cache` WRITE;
/*!40000 ALTER TABLE `drupal_cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_cache_block`
--

DROP TABLE IF EXISTS `drupal_cache_block`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_cache_block` (
  `cid` varchar(255) NOT NULL DEFAULT '' COMMENT 'Primary Key: Unique cache ID.',
  `data` longblob COMMENT 'A collection of data to cache.',
  `expire` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry should expire, or 0 for never.',
  `created` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry was created.',
  `serialized` smallint(6) NOT NULL DEFAULT '0' COMMENT 'A flag to indicate whether content is serialized (1) or not (0).',
  PRIMARY KEY (`cid`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cache table for the Block module to store already built...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_cache_block`
--

LOCK TABLES `drupal_cache_block` WRITE;
/*!40000 ALTER TABLE `drupal_cache_block` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_cache_block` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_cache_bootstrap`
--

DROP TABLE IF EXISTS `drupal_cache_bootstrap`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_cache_bootstrap` (
  `cid` varchar(255) NOT NULL DEFAULT '' COMMENT 'Primary Key: Unique cache ID.',
  `data` longblob COMMENT 'A collection of data to cache.',
  `expire` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry should expire, or 0 for never.',
  `created` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry was created.',
  `serialized` smallint(6) NOT NULL DEFAULT '0' COMMENT 'A flag to indicate whether content is serialized (1) or not (0).',
  PRIMARY KEY (`cid`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cache table for data required to bootstrap Drupal, may be...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_cache_bootstrap`
--

LOCK TABLES `drupal_cache_bootstrap` WRITE;
/*!40000 ALTER TABLE `drupal_cache_bootstrap` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_cache_bootstrap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_cache_field`
--

DROP TABLE IF EXISTS `drupal_cache_field`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_cache_field` (
  `cid` varchar(255) NOT NULL DEFAULT '' COMMENT 'Primary Key: Unique cache ID.',
  `data` longblob COMMENT 'A collection of data to cache.',
  `expire` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry should expire, or 0 for never.',
  `created` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry was created.',
  `serialized` smallint(6) NOT NULL DEFAULT '0' COMMENT 'A flag to indicate whether content is serialized (1) or not (0).',
  PRIMARY KEY (`cid`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Generic cache table for caching things not separated out...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_cache_field`
--

LOCK TABLES `drupal_cache_field` WRITE;
/*!40000 ALTER TABLE `drupal_cache_field` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_cache_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_cache_filter`
--

DROP TABLE IF EXISTS `drupal_cache_filter`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_cache_filter` (
  `cid` varchar(255) NOT NULL DEFAULT '' COMMENT 'Primary Key: Unique cache ID.',
  `data` longblob COMMENT 'A collection of data to cache.',
  `expire` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry should expire, or 0 for never.',
  `created` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry was created.',
  `serialized` smallint(6) NOT NULL DEFAULT '0' COMMENT 'A flag to indicate whether content is serialized (1) or not (0).',
  PRIMARY KEY (`cid`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cache table for the Filter module to store already...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_cache_filter`
--

LOCK TABLES `drupal_cache_filter` WRITE;
/*!40000 ALTER TABLE `drupal_cache_filter` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_cache_filter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_cache_form`
--

DROP TABLE IF EXISTS `drupal_cache_form`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_cache_form` (
  `cid` varchar(255) NOT NULL DEFAULT '' COMMENT 'Primary Key: Unique cache ID.',
  `data` longblob COMMENT 'A collection of data to cache.',
  `expire` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry should expire, or 0 for never.',
  `created` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry was created.',
  `serialized` smallint(6) NOT NULL DEFAULT '0' COMMENT 'A flag to indicate whether content is serialized (1) or not (0).',
  PRIMARY KEY (`cid`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cache table for the form system to store recently built...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_cache_form`
--

LOCK TABLES `drupal_cache_form` WRITE;
/*!40000 ALTER TABLE `drupal_cache_form` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_cache_form` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_cache_image`
--

DROP TABLE IF EXISTS `drupal_cache_image`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_cache_image` (
  `cid` varchar(255) NOT NULL DEFAULT '' COMMENT 'Primary Key: Unique cache ID.',
  `data` longblob COMMENT 'A collection of data to cache.',
  `expire` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry should expire, or 0 for never.',
  `created` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry was created.',
  `serialized` smallint(6) NOT NULL DEFAULT '0' COMMENT 'A flag to indicate whether content is serialized (1) or not (0).',
  PRIMARY KEY (`cid`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cache table used to store information about image...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_cache_image`
--

LOCK TABLES `drupal_cache_image` WRITE;
/*!40000 ALTER TABLE `drupal_cache_image` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_cache_image` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_cache_menu`
--

DROP TABLE IF EXISTS `drupal_cache_menu`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_cache_menu` (
  `cid` varchar(255) NOT NULL DEFAULT '' COMMENT 'Primary Key: Unique cache ID.',
  `data` longblob COMMENT 'A collection of data to cache.',
  `expire` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry should expire, or 0 for never.',
  `created` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry was created.',
  `serialized` smallint(6) NOT NULL DEFAULT '0' COMMENT 'A flag to indicate whether content is serialized (1) or not (0).',
  PRIMARY KEY (`cid`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cache table for the menu system to store router...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_cache_menu`
--

LOCK TABLES `drupal_cache_menu` WRITE;
/*!40000 ALTER TABLE `drupal_cache_menu` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_cache_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_cache_page`
--

DROP TABLE IF EXISTS `drupal_cache_page`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_cache_page` (
  `cid` varchar(255) NOT NULL DEFAULT '' COMMENT 'Primary Key: Unique cache ID.',
  `data` longblob COMMENT 'A collection of data to cache.',
  `expire` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry should expire, or 0 for never.',
  `created` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry was created.',
  `serialized` smallint(6) NOT NULL DEFAULT '0' COMMENT 'A flag to indicate whether content is serialized (1) or not (0).',
  PRIMARY KEY (`cid`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cache table used to store compressed pages for anonymous...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_cache_page`
--

LOCK TABLES `drupal_cache_page` WRITE;
/*!40000 ALTER TABLE `drupal_cache_page` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_cache_page` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_cache_path`
--

DROP TABLE IF EXISTS `drupal_cache_path`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_cache_path` (
  `cid` varchar(255) NOT NULL DEFAULT '' COMMENT 'Primary Key: Unique cache ID.',
  `data` longblob COMMENT 'A collection of data to cache.',
  `expire` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry should expire, or 0 for never.',
  `created` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry was created.',
  `serialized` smallint(6) NOT NULL DEFAULT '0' COMMENT 'A flag to indicate whether content is serialized (1) or not (0).',
  PRIMARY KEY (`cid`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cache table for path alias lookup.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_cache_path`
--

LOCK TABLES `drupal_cache_path` WRITE;
/*!40000 ALTER TABLE `drupal_cache_path` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_cache_path` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_cache_token`
--

DROP TABLE IF EXISTS `drupal_cache_token`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_cache_token` (
  `cid` varchar(255) NOT NULL DEFAULT '' COMMENT 'Primary Key: Unique cache ID.',
  `data` longblob COMMENT 'A collection of data to cache.',
  `expire` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry should expire, or 0 for never.',
  `created` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when the cache entry was created.',
  `serialized` smallint(6) NOT NULL DEFAULT '0' COMMENT 'A flag to indicate whether content is serialized (1) or not (0).',
  PRIMARY KEY (`cid`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cache table for token information.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_cache_token`
--

LOCK TABLES `drupal_cache_token` WRITE;
/*!40000 ALTER TABLE `drupal_cache_token` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_cache_token` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_comment`
--

DROP TABLE IF EXISTS `drupal_comment`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_comment` (
  `cid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary Key: Unique comment ID.',
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT 'The drupal_comment.cid to which this comment is a reply. If set to 0, this comment is not a reply to an existing comment.',
  `nid` int(11) NOT NULL DEFAULT '0' COMMENT 'The drupal_node.nid to which this comment is a reply.',
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT 'The drupal_users.uid who authored the comment. If set to 0, this comment was created by an anonymous user.',
  `subject` varchar(64) NOT NULL DEFAULT '' COMMENT 'The comment title.',
  `hostname` varchar(128) NOT NULL DEFAULT '' COMMENT 'The author’s host name.',
  `created` int(11) NOT NULL DEFAULT '0' COMMENT 'The time that the comment was created, as a Unix timestamp.',
  `changed` int(11) NOT NULL DEFAULT '0' COMMENT 'The time that the comment was last edited, as a Unix timestamp.',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT 'The published status of a comment. (0 = Not Published, 1 = Published)',
  `thread` varchar(255) NOT NULL COMMENT 'The vancode representation of the comment’s place in a thread.',
  `name` varchar(60) DEFAULT NULL COMMENT 'The comment author’s name. Uses drupal_users.name if the user is logged in, otherwise uses the value typed into the comment form.',
  `mail` varchar(64) DEFAULT NULL COMMENT 'The comment author’s e-mail address from the comment form, if user is anonymous, and the ’Anonymous users may/must leave their contact information’ setting is turned on.',
  `homepage` varchar(255) DEFAULT NULL COMMENT 'The comment author’s home page address from the comment form, if user is anonymous, and the ’Anonymous users may/must leave their contact information’ setting is turned on.',
  `language` varchar(12) NOT NULL DEFAULT '' COMMENT 'The drupal_languages.language of this comment.',
  PRIMARY KEY (`cid`),
  KEY `comment_status_pid` (`pid`,`status`),
  KEY `comment_num_new` (`nid`,`status`,`created`,`cid`,`thread`),
  KEY `comment_uid` (`uid`),
  KEY `comment_nid_language` (`nid`,`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores comments and associated data.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_comment`
--

LOCK TABLES `drupal_comment` WRITE;
/*!40000 ALTER TABLE `drupal_comment` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_comment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_contact`
--

DROP TABLE IF EXISTS `drupal_contact`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_contact` (
  `cid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary Key: Unique category ID.',
  `category` varchar(255) NOT NULL DEFAULT '' COMMENT 'Category name.',
  `recipients` longtext NOT NULL COMMENT 'Comma-separated list of recipient e-mail addresses.',
  `reply` longtext NOT NULL COMMENT 'Text of the auto-reply message.',
  `weight` int(11) NOT NULL DEFAULT '0' COMMENT 'The category’s weight.',
  `selected` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Flag to indicate whether or not category is selected by default. (1 = Yes, 0 = No)',
  PRIMARY KEY (`cid`),
  UNIQUE KEY `category` (`category`),
  KEY `list` (`weight`,`category`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='Contact form category settings.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_contact`
--

LOCK TABLES `drupal_contact` WRITE;
/*!40000 ALTER TABLE `drupal_contact` DISABLE KEYS */;
INSERT INTO `drupal_contact` VALUES (1,'Website feedback','rnd_tester@cdev.ru','',0,1);
/*!40000 ALTER TABLE `drupal_contact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_date_format_locale`
--

DROP TABLE IF EXISTS `drupal_date_format_locale`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_date_format_locale` (
  `format` varchar(100) NOT NULL COMMENT 'The date format string.',
  `type` varchar(64) NOT NULL COMMENT 'The date format type, e.g. medium.',
  `language` varchar(12) NOT NULL COMMENT 'A drupal_languages.language for this format to be used with.',
  PRIMARY KEY (`type`,`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores configured date formats for each locale.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_date_format_locale`
--

LOCK TABLES `drupal_date_format_locale` WRITE;
/*!40000 ALTER TABLE `drupal_date_format_locale` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_date_format_locale` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_date_format_type`
--

DROP TABLE IF EXISTS `drupal_date_format_type`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_date_format_type` (
  `type` varchar(64) NOT NULL COMMENT 'The date format type, e.g. medium.',
  `title` varchar(255) NOT NULL COMMENT 'The human readable name of the format type.',
  `locked` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not this is a system provided format.',
  PRIMARY KEY (`type`),
  KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores configured date format types.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_date_format_type`
--

LOCK TABLES `drupal_date_format_type` WRITE;
/*!40000 ALTER TABLE `drupal_date_format_type` DISABLE KEYS */;
INSERT INTO `drupal_date_format_type` VALUES ('long','Long',1);
INSERT INTO `drupal_date_format_type` VALUES ('medium','Medium',1);
INSERT INTO `drupal_date_format_type` VALUES ('short','Short',1);
/*!40000 ALTER TABLE `drupal_date_format_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_date_formats`
--

DROP TABLE IF EXISTS `drupal_date_formats`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_date_formats` (
  `dfid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'The date format identifier.',
  `format` varchar(100) NOT NULL COMMENT 'The date format string.',
  `type` varchar(64) NOT NULL COMMENT 'The date format type, e.g. medium.',
  `locked` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether or not this format can be modified.',
  PRIMARY KEY (`dfid`),
  UNIQUE KEY `formats` (`format`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8 COMMENT='Stores configured date formats.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_date_formats`
--

LOCK TABLES `drupal_date_formats` WRITE;
/*!40000 ALTER TABLE `drupal_date_formats` DISABLE KEYS */;
INSERT INTO `drupal_date_formats` VALUES (1,'Y-m-d H:i','short',1);
INSERT INTO `drupal_date_formats` VALUES (2,'m/d/Y - H:i','short',1);
INSERT INTO `drupal_date_formats` VALUES (3,'d/m/Y - H:i','short',1);
INSERT INTO `drupal_date_formats` VALUES (4,'Y/m/d - H:i','short',1);
INSERT INTO `drupal_date_formats` VALUES (5,'d.m.Y - H:i','short',1);
INSERT INTO `drupal_date_formats` VALUES (6,'m/d/Y - g:ia','short',1);
INSERT INTO `drupal_date_formats` VALUES (7,'d/m/Y - g:ia','short',1);
INSERT INTO `drupal_date_formats` VALUES (8,'Y/m/d - g:ia','short',1);
INSERT INTO `drupal_date_formats` VALUES (9,'M j Y - H:i','short',1);
INSERT INTO `drupal_date_formats` VALUES (10,'j M Y - H:i','short',1);
INSERT INTO `drupal_date_formats` VALUES (11,'Y M j - H:i','short',1);
INSERT INTO `drupal_date_formats` VALUES (12,'M j Y - g:ia','short',1);
INSERT INTO `drupal_date_formats` VALUES (13,'j M Y - g:ia','short',1);
INSERT INTO `drupal_date_formats` VALUES (14,'Y M j - g:ia','short',1);
INSERT INTO `drupal_date_formats` VALUES (15,'D, Y-m-d H:i','medium',1);
INSERT INTO `drupal_date_formats` VALUES (16,'D, m/d/Y - H:i','medium',1);
INSERT INTO `drupal_date_formats` VALUES (17,'D, d/m/Y - H:i','medium',1);
INSERT INTO `drupal_date_formats` VALUES (18,'D, Y/m/d - H:i','medium',1);
INSERT INTO `drupal_date_formats` VALUES (19,'F j, Y - H:i','medium',1);
INSERT INTO `drupal_date_formats` VALUES (20,'j F, Y - H:i','medium',1);
INSERT INTO `drupal_date_formats` VALUES (21,'Y, F j - H:i','medium',1);
INSERT INTO `drupal_date_formats` VALUES (22,'D, m/d/Y - g:ia','medium',1);
INSERT INTO `drupal_date_formats` VALUES (23,'D, d/m/Y - g:ia','medium',1);
INSERT INTO `drupal_date_formats` VALUES (24,'D, Y/m/d - g:ia','medium',1);
INSERT INTO `drupal_date_formats` VALUES (25,'F j, Y - g:ia','medium',1);
INSERT INTO `drupal_date_formats` VALUES (26,'j F Y - g:ia','medium',1);
INSERT INTO `drupal_date_formats` VALUES (27,'Y, F j - g:ia','medium',1);
INSERT INTO `drupal_date_formats` VALUES (28,'j. F Y - G:i','medium',1);
INSERT INTO `drupal_date_formats` VALUES (29,'l, F j, Y - H:i','long',1);
INSERT INTO `drupal_date_formats` VALUES (30,'l, j F, Y - H:i','long',1);
INSERT INTO `drupal_date_formats` VALUES (31,'l, Y,  F j - H:i','long',1);
INSERT INTO `drupal_date_formats` VALUES (32,'l, F j, Y - g:ia','long',1);
INSERT INTO `drupal_date_formats` VALUES (33,'l, j F Y - g:ia','long',1);
INSERT INTO `drupal_date_formats` VALUES (34,'l, Y,  F j - g:ia','long',1);
INSERT INTO `drupal_date_formats` VALUES (35,'l, j. F Y - G:i','long',1);
/*!40000 ALTER TABLE `drupal_date_formats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_field_config`
--

DROP TABLE IF EXISTS `drupal_field_config`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_field_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The primary identifier for a field',
  `field_name` varchar(32) NOT NULL COMMENT 'The name of this field. Non-deleted field names are unique, but multiple deleted fields can have the same name.',
  `type` varchar(128) NOT NULL COMMENT 'The type of this field.',
  `module` varchar(128) NOT NULL DEFAULT '' COMMENT 'The module that implements the field type.',
  `active` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Boolean indicating whether the module that implements the field type is enabled.',
  `storage_type` varchar(128) NOT NULL COMMENT 'The storage backend for the field.',
  `storage_module` varchar(128) NOT NULL DEFAULT '' COMMENT 'The module that implements the storage backend.',
  `storage_active` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Boolean indicating whether the module that implements the storage backend is enabled.',
  `locked` tinyint(4) NOT NULL DEFAULT '0' COMMENT '@TODO',
  `data` longblob NOT NULL COMMENT 'Serialized data containing the field properties that do not warrant a dedicated column.',
  `cardinality` tinyint(4) NOT NULL DEFAULT '0',
  `translatable` tinyint(4) NOT NULL DEFAULT '0',
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `field_name` (`field_name`),
  KEY `active` (`active`),
  KEY `storage_active` (`storage_active`),
  KEY `deleted` (`deleted`),
  KEY `module` (`module`),
  KEY `storage_module` (`storage_module`),
  KEY `type` (`type`),
  KEY `storage_type` (`storage_type`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_field_config`
--

LOCK TABLES `drupal_field_config` WRITE;
/*!40000 ALTER TABLE `drupal_field_config` DISABLE KEYS */;
INSERT INTO `drupal_field_config` VALUES (1,'comment_body','text_long','text',1,'field_sql_storage','field_sql_storage',1,0,'a:7:{s:12:\"entity_types\";a:1:{i:0;s:7:\"comment\";}s:12:\"translatable\";b:0;s:8:\"settings\";a:0:{}s:7:\"storage\";a:4:{s:4:\"type\";s:17:\"field_sql_storage\";s:8:\"settings\";a:0:{}s:6:\"module\";s:17:\"field_sql_storage\";s:6:\"active\";i:1;}s:12:\"foreign keys\";a:1:{s:6:\"format\";a:2:{s:5:\"table\";s:13:\"filter_format\";s:7:\"columns\";a:1:{s:6:\"format\";s:6:\"format\";}}}s:7:\"indexes\";a:1:{s:6:\"format\";a:1:{i:0;s:6:\"format\";}}s:7:\"columns\";N;}',1,0,0);
INSERT INTO `drupal_field_config` VALUES (2,'body','text_with_summary','text',1,'field_sql_storage','field_sql_storage',1,0,'a:7:{s:12:\"entity_types\";a:1:{i:0;s:4:\"node\";}s:12:\"translatable\";b:1;s:8:\"settings\";a:0:{}s:7:\"storage\";a:4:{s:4:\"type\";s:17:\"field_sql_storage\";s:8:\"settings\";a:0:{}s:6:\"module\";s:17:\"field_sql_storage\";s:6:\"active\";i:1;}s:12:\"foreign keys\";a:1:{s:6:\"format\";a:2:{s:5:\"table\";s:13:\"filter_format\";s:7:\"columns\";a:1:{s:6:\"format\";s:6:\"format\";}}}s:7:\"indexes\";a:1:{s:6:\"format\";a:1:{i:0;s:6:\"format\";}}s:7:\"columns\";N;}',1,1,0);
INSERT INTO `drupal_field_config` VALUES (3,'field_tags','taxonomy_term_reference','taxonomy',1,'field_sql_storage','field_sql_storage',1,0,'a:7:{s:8:\"settings\";a:1:{s:14:\"allowed_values\";a:1:{i:0;a:2:{s:10:\"vocabulary\";s:4:\"tags\";s:6:\"parent\";i:0;}}}s:12:\"entity_types\";a:0:{}s:12:\"translatable\";b:0;s:7:\"storage\";a:4:{s:4:\"type\";s:17:\"field_sql_storage\";s:8:\"settings\";a:0:{}s:6:\"module\";s:17:\"field_sql_storage\";s:6:\"active\";i:1;}s:12:\"foreign keys\";a:1:{s:3:\"tid\";a:2:{s:5:\"table\";s:18:\"taxonomy_term_data\";s:7:\"columns\";a:1:{s:3:\"tid\";s:3:\"tid\";}}}s:7:\"indexes\";a:1:{s:3:\"tid\";a:1:{i:0;s:3:\"tid\";}}s:7:\"columns\";N;}',-1,0,0);
INSERT INTO `drupal_field_config` VALUES (4,'field_image','image','image',1,'field_sql_storage','field_sql_storage',1,0,'a:7:{s:12:\"translatable\";b:1;s:7:\"indexes\";a:1:{s:3:\"fid\";a:1:{i:0;s:3:\"fid\";}}s:8:\"settings\";a:2:{s:10:\"uri_scheme\";s:6:\"public\";s:13:\"default_image\";b:0;}s:7:\"storage\";a:4:{s:4:\"type\";s:17:\"field_sql_storage\";s:8:\"settings\";a:0:{}s:6:\"module\";s:17:\"field_sql_storage\";s:6:\"active\";i:1;}s:12:\"entity_types\";a:0:{}s:12:\"foreign keys\";a:1:{s:3:\"fid\";a:2:{s:5:\"table\";s:12:\"file_managed\";s:7:\"columns\";a:1:{s:3:\"fid\";s:3:\"fid\";}}}s:7:\"columns\";N;}',1,1,0);
INSERT INTO `drupal_field_config` VALUES (5,'taxonomy_forums','taxonomy_term_reference','taxonomy',1,'field_sql_storage','field_sql_storage',1,0,'a:7:{s:8:\"settings\";a:1:{s:14:\"allowed_values\";a:1:{i:0;a:2:{s:10:\"vocabulary\";s:6:\"forums\";s:6:\"parent\";i:0;}}}s:12:\"entity_types\";a:0:{}s:12:\"translatable\";b:0;s:7:\"storage\";a:4:{s:4:\"type\";s:17:\"field_sql_storage\";s:8:\"settings\";a:0:{}s:6:\"module\";s:17:\"field_sql_storage\";s:6:\"active\";i:1;}s:12:\"foreign keys\";a:1:{s:3:\"tid\";a:2:{s:5:\"table\";s:18:\"taxonomy_term_data\";s:7:\"columns\";a:1:{s:3:\"tid\";s:3:\"tid\";}}}s:7:\"indexes\";a:1:{s:3:\"tid\";a:1:{i:0;s:3:\"tid\";}}s:7:\"columns\";N;}',1,0,0);
/*!40000 ALTER TABLE `drupal_field_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_field_config_instance`
--

DROP TABLE IF EXISTS `drupal_field_config_instance`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_field_config_instance` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'The primary identifier for a field instance',
  `field_id` int(11) NOT NULL COMMENT 'The identifier of the field attached by this instance',
  `field_name` varchar(32) NOT NULL DEFAULT '',
  `entity_type` varchar(32) NOT NULL DEFAULT '',
  `bundle` varchar(128) NOT NULL DEFAULT '',
  `data` longblob NOT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `field_name_bundle` (`field_name`,`entity_type`,`bundle`),
  KEY `deleted` (`deleted`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_field_config_instance`
--

LOCK TABLES `drupal_field_config_instance` WRITE;
/*!40000 ALTER TABLE `drupal_field_config_instance` DISABLE KEYS */;
INSERT INTO `drupal_field_config_instance` VALUES (1,1,'comment_body','comment','comment_node_page','a:6:{s:5:\"label\";s:7:\"Comment\";s:8:\"settings\";a:2:{s:15:\"text_processing\";i:1;s:18:\"user_register_form\";b:0;}s:8:\"required\";b:1;s:7:\"display\";a:1:{s:7:\"default\";a:5:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:12:\"text_default\";s:6:\"weight\";i:0;s:8:\"settings\";a:0:{}s:6:\"module\";s:4:\"text\";}}s:6:\"widget\";a:4:{s:4:\"type\";s:13:\"text_textarea\";s:8:\"settings\";a:1:{s:4:\"rows\";i:5;}s:6:\"weight\";i:0;s:6:\"module\";s:4:\"text\";}s:11:\"description\";s:0:\"\";}',0);
INSERT INTO `drupal_field_config_instance` VALUES (2,2,'body','node','page','a:7:{s:5:\"label\";s:4:\"Body\";s:11:\"widget_type\";s:26:\"text_textarea_with_summary\";s:8:\"settings\";a:3:{s:15:\"display_summary\";b:1;s:15:\"text_processing\";i:1;s:18:\"user_register_form\";b:0;}s:7:\"display\";a:2:{s:7:\"default\";a:5:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:12:\"text_default\";s:8:\"settings\";a:0:{}s:6:\"module\";s:4:\"text\";s:6:\"weight\";i:0;}s:6:\"teaser\";a:5:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:23:\"text_summary_or_trimmed\";s:8:\"settings\";a:1:{s:11:\"trim_length\";i:600;}s:6:\"module\";s:4:\"text\";s:6:\"weight\";i:0;}}s:6:\"widget\";a:4:{s:4:\"type\";s:26:\"text_textarea_with_summary\";s:8:\"settings\";a:2:{s:4:\"rows\";i:20;s:12:\"summary_rows\";i:5;}s:6:\"weight\";i:-4;s:6:\"module\";s:4:\"text\";}s:8:\"required\";b:0;s:11:\"description\";s:0:\"\";}',0);
INSERT INTO `drupal_field_config_instance` VALUES (3,1,'comment_body','comment','comment_node_article','a:6:{s:5:\"label\";s:7:\"Comment\";s:8:\"settings\";a:2:{s:15:\"text_processing\";i:1;s:18:\"user_register_form\";b:0;}s:8:\"required\";b:1;s:7:\"display\";a:1:{s:7:\"default\";a:5:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:12:\"text_default\";s:6:\"weight\";i:0;s:8:\"settings\";a:0:{}s:6:\"module\";s:4:\"text\";}}s:6:\"widget\";a:4:{s:4:\"type\";s:13:\"text_textarea\";s:8:\"settings\";a:1:{s:4:\"rows\";i:5;}s:6:\"weight\";i:0;s:6:\"module\";s:4:\"text\";}s:11:\"description\";s:0:\"\";}',0);
INSERT INTO `drupal_field_config_instance` VALUES (4,2,'body','node','article','a:7:{s:5:\"label\";s:4:\"Body\";s:11:\"widget_type\";s:26:\"text_textarea_with_summary\";s:8:\"settings\";a:3:{s:15:\"display_summary\";b:1;s:15:\"text_processing\";i:1;s:18:\"user_register_form\";b:0;}s:7:\"display\";a:2:{s:7:\"default\";a:5:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:12:\"text_default\";s:8:\"settings\";a:0:{}s:6:\"module\";s:4:\"text\";s:6:\"weight\";i:0;}s:6:\"teaser\";a:5:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:23:\"text_summary_or_trimmed\";s:8:\"settings\";a:1:{s:11:\"trim_length\";i:600;}s:6:\"module\";s:4:\"text\";s:6:\"weight\";i:0;}}s:6:\"widget\";a:4:{s:4:\"type\";s:26:\"text_textarea_with_summary\";s:8:\"settings\";a:2:{s:4:\"rows\";i:20;s:12:\"summary_rows\";i:5;}s:6:\"weight\";i:-4;s:6:\"module\";s:4:\"text\";}s:8:\"required\";b:0;s:11:\"description\";s:0:\"\";}',0);
INSERT INTO `drupal_field_config_instance` VALUES (5,3,'field_tags','node','article','a:6:{s:5:\"label\";s:4:\"Tags\";s:11:\"description\";s:63:\"Enter a comma-separated list of words to describe your content.\";s:6:\"widget\";a:4:{s:4:\"type\";s:21:\"taxonomy_autocomplete\";s:6:\"weight\";i:-4;s:8:\"settings\";a:2:{s:4:\"size\";i:60;s:17:\"autocomplete_path\";s:21:\"taxonomy/autocomplete\";}s:6:\"module\";s:8:\"taxonomy\";}s:7:\"display\";a:2:{s:7:\"default\";a:5:{s:4:\"type\";s:28:\"taxonomy_term_reference_link\";s:6:\"weight\";i:10;s:5:\"label\";s:5:\"above\";s:8:\"settings\";a:0:{}s:6:\"module\";s:8:\"taxonomy\";}s:6:\"teaser\";a:5:{s:4:\"type\";s:28:\"taxonomy_term_reference_link\";s:6:\"weight\";i:10;s:5:\"label\";s:5:\"above\";s:8:\"settings\";a:0:{}s:6:\"module\";s:8:\"taxonomy\";}}s:8:\"settings\";a:1:{s:18:\"user_register_form\";b:0;}s:8:\"required\";b:0;}',0);
INSERT INTO `drupal_field_config_instance` VALUES (6,4,'field_image','node','article','a:6:{s:5:\"label\";s:5:\"Image\";s:11:\"description\";s:40:\"Upload an image to go with this article.\";s:8:\"required\";b:0;s:8:\"settings\";a:8:{s:14:\"file_directory\";s:11:\"field/image\";s:15:\"file_extensions\";s:16:\"png gif jpg jpeg\";s:12:\"max_filesize\";s:0:\"\";s:14:\"max_resolution\";s:0:\"\";s:14:\"min_resolution\";s:0:\"\";s:9:\"alt_field\";b:1;s:11:\"title_field\";s:0:\"\";s:18:\"user_register_form\";b:0;}s:6:\"widget\";a:4:{s:4:\"type\";s:11:\"image_image\";s:8:\"settings\";a:2:{s:18:\"progress_indicator\";s:8:\"throbber\";s:19:\"preview_image_style\";s:9:\"thumbnail\";}s:6:\"weight\";i:-1;s:6:\"module\";s:5:\"image\";}s:7:\"display\";a:2:{s:7:\"default\";a:5:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:5:\"image\";s:8:\"settings\";a:2:{s:11:\"image_style\";s:5:\"large\";s:10:\"image_link\";s:0:\"\";}s:6:\"weight\";i:-1;s:6:\"module\";s:5:\"image\";}s:6:\"teaser\";a:5:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:5:\"image\";s:8:\"settings\";a:2:{s:11:\"image_style\";s:6:\"medium\";s:10:\"image_link\";s:7:\"content\";}s:6:\"weight\";i:-1;s:6:\"module\";s:5:\"image\";}}}',0);
INSERT INTO `drupal_field_config_instance` VALUES (7,5,'taxonomy_forums','node','forum','a:6:{s:5:\"label\";s:6:\"Forums\";s:8:\"required\";b:1;s:6:\"widget\";a:4:{s:4:\"type\";s:14:\"options_select\";s:8:\"settings\";a:0:{}s:6:\"weight\";i:0;s:6:\"module\";s:7:\"options\";}s:7:\"display\";a:2:{s:7:\"default\";a:5:{s:4:\"type\";s:28:\"taxonomy_term_reference_link\";s:6:\"weight\";i:10;s:5:\"label\";s:5:\"above\";s:8:\"settings\";a:0:{}s:6:\"module\";s:8:\"taxonomy\";}s:6:\"teaser\";a:5:{s:4:\"type\";s:28:\"taxonomy_term_reference_link\";s:6:\"weight\";i:10;s:5:\"label\";s:5:\"above\";s:8:\"settings\";a:0:{}s:6:\"module\";s:8:\"taxonomy\";}}s:8:\"settings\";a:1:{s:18:\"user_register_form\";b:0;}s:11:\"description\";s:0:\"\";}',0);
INSERT INTO `drupal_field_config_instance` VALUES (8,1,'comment_body','comment','comment_node_forum','a:6:{s:5:\"label\";s:7:\"Comment\";s:8:\"settings\";a:2:{s:15:\"text_processing\";i:1;s:18:\"user_register_form\";b:0;}s:8:\"required\";b:1;s:7:\"display\";a:1:{s:7:\"default\";a:5:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:12:\"text_default\";s:6:\"weight\";i:0;s:8:\"settings\";a:0:{}s:6:\"module\";s:4:\"text\";}}s:6:\"widget\";a:4:{s:4:\"type\";s:13:\"text_textarea\";s:8:\"settings\";a:1:{s:4:\"rows\";i:5;}s:6:\"weight\";i:0;s:6:\"module\";s:4:\"text\";}s:11:\"description\";s:0:\"\";}',0);
INSERT INTO `drupal_field_config_instance` VALUES (9,2,'body','node','forum','a:7:{s:5:\"label\";s:4:\"Body\";s:11:\"widget_type\";s:26:\"text_textarea_with_summary\";s:8:\"settings\";a:3:{s:15:\"display_summary\";b:1;s:15:\"text_processing\";i:1;s:18:\"user_register_form\";b:0;}s:7:\"display\";a:2:{s:7:\"default\";a:5:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:12:\"text_default\";s:8:\"settings\";a:0:{}s:6:\"module\";s:4:\"text\";s:6:\"weight\";i:11;}s:6:\"teaser\";a:5:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:23:\"text_summary_or_trimmed\";s:8:\"settings\";a:1:{s:11:\"trim_length\";i:600;}s:6:\"module\";s:4:\"text\";s:6:\"weight\";i:11;}}s:6:\"widget\";a:4:{s:4:\"type\";s:26:\"text_textarea_with_summary\";s:8:\"settings\";a:2:{s:4:\"rows\";i:20;s:12:\"summary_rows\";i:5;}s:6:\"weight\";i:1;s:6:\"module\";s:4:\"text\";}s:8:\"required\";b:0;s:11:\"description\";s:0:\"\";}',0);
INSERT INTO `drupal_field_config_instance` VALUES (10,1,'comment_body','comment','comment_node_blog','a:6:{s:5:\"label\";s:7:\"Comment\";s:8:\"settings\";a:2:{s:15:\"text_processing\";i:1;s:18:\"user_register_form\";b:0;}s:8:\"required\";b:1;s:7:\"display\";a:1:{s:7:\"default\";a:5:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:12:\"text_default\";s:6:\"weight\";i:0;s:8:\"settings\";a:0:{}s:6:\"module\";s:4:\"text\";}}s:6:\"widget\";a:4:{s:4:\"type\";s:13:\"text_textarea\";s:8:\"settings\";a:1:{s:4:\"rows\";i:5;}s:6:\"weight\";i:0;s:6:\"module\";s:4:\"text\";}s:11:\"description\";s:0:\"\";}',0);
INSERT INTO `drupal_field_config_instance` VALUES (11,2,'body','node','blog','a:7:{s:5:\"label\";s:4:\"Body\";s:11:\"widget_type\";s:26:\"text_textarea_with_summary\";s:8:\"settings\";a:3:{s:15:\"display_summary\";b:1;s:15:\"text_processing\";i:1;s:18:\"user_register_form\";b:0;}s:7:\"display\";a:2:{s:7:\"default\";a:5:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:12:\"text_default\";s:8:\"settings\";a:0:{}s:6:\"module\";s:4:\"text\";s:6:\"weight\";i:0;}s:6:\"teaser\";a:5:{s:5:\"label\";s:6:\"hidden\";s:4:\"type\";s:23:\"text_summary_or_trimmed\";s:8:\"settings\";a:1:{s:11:\"trim_length\";i:600;}s:6:\"module\";s:4:\"text\";s:6:\"weight\";i:0;}}s:6:\"widget\";a:4:{s:4:\"type\";s:26:\"text_textarea_with_summary\";s:8:\"settings\";a:2:{s:4:\"rows\";i:20;s:12:\"summary_rows\";i:5;}s:6:\"weight\";i:-4;s:6:\"module\";s:4:\"text\";}s:8:\"required\";b:0;s:11:\"description\";s:0:\"\";}',0);
/*!40000 ALTER TABLE `drupal_field_config_instance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_field_data_body`
--

DROP TABLE IF EXISTS `drupal_field_data_body`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_field_data_body` (
  `bundle` varchar(128) NOT NULL DEFAULT '' COMMENT 'The field instance bundle to which this row belongs, used when deleting a field instance',
  `deleted` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'A boolean indicating whether this data item has been deleted',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'The entity id this data is attached to',
  `revision_id` int(10) unsigned DEFAULT NULL COMMENT 'The entity revision id this data is attached to, or NULL if the entity type is not versioned',
  `language` varchar(32) NOT NULL DEFAULT '' COMMENT 'The language for this data item.',
  `delta` int(10) unsigned NOT NULL COMMENT 'The sequence number for this data item, used for multi-value fields',
  `body_value` longtext,
  `body_summary` longtext,
  `body_format` varchar(255) DEFAULT NULL,
  `entity_type` varchar(128) NOT NULL DEFAULT '' COMMENT 'The entity type this data is attached to.',
  PRIMARY KEY (`entity_type`,`entity_id`,`deleted`,`delta`,`language`),
  KEY `bundle` (`bundle`),
  KEY `deleted` (`deleted`),
  KEY `entity_id` (`entity_id`),
  KEY `revision_id` (`revision_id`),
  KEY `language` (`language`),
  KEY `body_format` (`body_format`),
  KEY `entity_type` (`entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Data storage for field 2 (body)';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_field_data_body`
--

LOCK TABLES `drupal_field_data_body` WRITE;
/*!40000 ALTER TABLE `drupal_field_data_body` DISABLE KEYS */;
INSERT INTO `drupal_field_data_body` VALUES ('page',0,1,1,'und',0,'<div class=\"two-column-page\">\r\n<div class=\"column\">\r\n<h2>Setup &amp; Architecture</h2>\r\n<ul class=\"features\">\r\n<li>Free open-source PHP/MySQL solution</li>\r\n<li>Can work as a standalone online store and in connection with Drupal CMS with tons of ready-made Drupal modules</li>\r\n<li>Web-based installation wizard that installs both LiteCommerce and Drupal (when run from the <a href=\"download-free.html\">Ecommerce CMS package</a>)</li>\r\n<li>Storefront sections and blocks can be managed in the Drupal user interface</li>\r\n<li>Modern object-oriented MVC architecture</li>\r\n<li>Flexible modular system allows customizing without hacks to core files and simplifies upgrades</li>\r\n<li>HTTPS/SSL support</li>\r\n<li>Fully customizable design &amp; layout</li>\r\n<li>Compatible with popular browsers:<br />IE 6+, Firefox 2+, Chrome 4+, Safari 3+, Opera 10+</li>\r\n</ul>\r\n<h2>Catalog Management</h2>\r\n<ul class=\"features\">\r\n<li>Unlimited number of products and categories</li>\r\n<li>Product options with optional price modifiers</li>\r\n<li>Inventory tracking per product or product variation</li>\r\n<li>Automatic thumbnail generation with image sharpening</li>\r\n<li>A product can belong to more than one category</li>\r\n<li>Product import from CSV files</li>\r\n</ul>\r\n<h2>Marketing &amp; Promotion</h2>\r\n<ul class=\"features\">\r\n<li>Custom search engine friendly URLs (when connected to Drupal)</li>\r\n<li>Custom META tags for products, categories and other website pages (when connected to Drupal)</li>\r\n<li>Discounts</li>\r\n<li>Wholesale pricing</li>\r\n<li>Featured products</li>\r\n<li>Bestsellers</li>\r\n<li>Recently added products</li>\r\n<li>Gift Certificates</li>\r\n</ul>\r\n</div>\r\n<div class=\"column\">\r\n<h2>Shopping Experience</h2>\r\n<ul class=\"features\">\r\n<li>Storefront sections and blocks transparently integrate with Drupal pages into a single ecommerce website</li>\r\n<li>Catalog pages are updated via AJAX without page reloading</li>\r\n<li>Image galleries with a popup image browser and an <br />in-page zoom function</li>\r\n<li>Previous and Next links on product pages</li>\r\n<li>Mouse wheel updates product quantities</li>\r\n<li>Wish List</li>\r\n<li>List of recently viewed products</li>\r\n<li>Horizontal and vertical \"minicart\" widgets</li>\r\n<li>Quick search form</li>\r\n</ul>\r\n<ul class=\"features\">\r\n</ul>\r\n<ul class=\"features\">\r\n</ul>\r\n<h2>Orders, Shipping and Tax</h2>\r\n<ul class=\"features\">\r\n<li>Customizable e-mail notifications</li>\r\n<li>Order history for customers and administrator</li>\r\n<li>Payment and shipping status tracking</li>\r\n<li>Invoice printing</li>\r\n<li>Support for PayPal Standard, PayPal Express Checkout, Google Checkout and Authorize.NET (SIM)</li>\r\n<li>Configurable min/max order amount limits</li>\r\n<li>Real-time UPS and USPS shipping rates</li>\r\n<li>Unlimited number of admin-defined delivery methods</li>\r\n<li>Flat, weight, order total and range based shipping rates</li>\r\n<li>International, domestic and local shipping</li>\r\n<li>Customizable tax calculation</li>\r\n<li>Import/export predefined tax schemas</li>\r\n<li>Product-specific taxes</li>\r\n<li>Taxes &amp; shipping fees depending on the customer location</li>\r\n<li>Tax Exempt feature</li>\r\n<li>GST/PST (Canadian tax system)</li>\r\n<li>Configurable measurement units, date/time formats and currency symbol</li>\r\n<li>Export sales &amp; customer data for use in a spreadsheet</li>\r\n<li>Export orders to MS Excel XP format</li>\r\n</ul>\r\n<ul class=\"features\">\r\n</ul>\r\n</div>\r\n</div>\r\n<h2>LiteCommerce is compatible with:</h2>\r\n<div class=\"feature-compatibility\">\r\n<div class=\"clear-fix\"><img class=\"inline-image\" style=\"padding-top: 12px;\" src=\"http://www.paypal.com/en_US/i/logo/PayPal_mark_60x38.gif\" alt=\"PayPal\" width=\"60\" height=\"38\" /><img class=\"inline-image\" style=\"padding-top: 10px; margin-right: 16px;\" src=\"http://xcart2-530.crtdev.local/~xplorer/xlite_cms/src/sites/default/files/google-checkout.jpg\" alt=\"Google Checkout\" width=\"107\" height=\"40\" /><img class=\"inline-image\" style=\"padding-top: 8px;\" src=\"http://drupal.org/files/powered-blue-135x42.png\" alt=\"Drupal\" width=\"135\" height=\"42\" /><img class=\"inline-image\" src=\"http://xcart2-530.crtdev.local/~xplorer/xlite_cms/src/sites/default/files/ups-logo.gif\" alt=\"UPS\" width=\"48\" height=\"58\" /><img class=\"inline-image\" style=\"padding-top: 3px;\" src=\"http://xcart2-530.crtdev.local/~xplorer/xlite_cms/src/sites/default/files/usps-logo.gif\" alt=\"USPS\" width=\"63\" height=\"52\" /></div>\r\n</div>','','filtered_html','node');
INSERT INTO `drupal_field_data_body` VALUES ('page',0,2,2,'und',0,'<div id=\"download-scheme\">\r\n<div id=\"download-steps\">\r\n<div id=\"download-step-1\">\r\n<div class=\"download-section-title\"><a href=\"http://www.litecommerce.com/download/drupal-lc-3.0.0-alpha.tgz\">Download</a></div>\r\nthe Ecommerce CMS<br /> package</div>\r\n<div id=\"download-step-2\">\r\n<div class=\"download-section-title\">Install</div>\r\nthe package on your<br /> web server</div>\r\n<div id=\"download-step-3\">\r\n<div class=\"download-section-title\">Customize</div>\r\nits design and functionality<br />to your needs</div>\r\n<div id=\"download-step-4\">\r\n<div class=\"download-section-title\">Configure</div>\r\nshipping methods, add products,<br /> select a payment gateway</div>\r\n<div id=\"download-step-5\">\r\n<div class=\"download-section-title\">Start selling!</div>\r\n</div>\r\n</div>\r\n</div>\r\n<div class=\"section-downloads\">\r\n<div class=\"title-section\">Drupal + LiteCommerce in a single package</div>\r\n<p>A single Ecommerce CMS package includes the latest stable version of Drupal (with \"LiteSky CMS\" theme and a set of popular modules) integrated with the alpha version of LiteCommerce v3 shopping cart. LiteCommerce v3 alpha version is not recommended for production sites. However users wishing to help test and develop LiteCommerce are encouraged to use this package instead of downloading and installing all the components individually.</p>\r\n<div id=\"download-now\">\r\n<div class=\"clear-fix\"><a id=\"btn-download-now\" href=\"http://www.litecommerce.com/download/drupal-lc-3.0.0-alpha.tgz\">Download</a>\r\n<div id=\"download-now-info\">Drupal v. 6.16 <span class=\"note\">(including LC Connector, LC theme and popular modules)</span><br /> LiteCommerce v3 alpha</div>\r\n</div>\r\n</div>\r\n<div class=\"title-subsection\">LiteCommerce v3 (standalone)</div>\r\n<p>Standalone LiteCommerce v3 alpha. Can be integrated with an existing Drupal-based site via LC Connector module. Since it is an alpha version, it is not recommended for production sites. However users wishing to help test and develop LiteCommerce may use it either in a standalone mode or in connection with Drupal.<br /> <a class=\"download\" href=\"http://www.litecommerce.com/download/litecommerce-3.0.0-alpha.tgz\">Download LiteCommerce v3 alpha</a></p>\r\n<div class=\"title-subsection\">LC Connector module for Drupal 6.x</div>\r\n<p>This Drupal module integrates LiteCommerce storefront pages and blocks into a Drupal-based website.<br /> <a class=\"download\" href=\"http://www.litecommerce.com/download/lc_connector-3.0.0-alpha.tgz\">Download \"LC Connector\" module</a></p>\r\n<div class=\"title-subsection\">Bettercrumbs module for Drupal 6.x</div>\r\n<p>This Drupal module allows you to hide/show breadcrumbs for each Drupal node.<br /> <a class=\"download\" href=\"http://www.litecommerce.com/download/bettercrumbs-3.0.0-alpha.tgz\">Download \"Bettercrumbs\" module</a></p>\r\n<div class=\"title-subsection\">\"LiteSky CMS\" theme for Drupal 6.x</div>\r\n<p>This Drupal 6.x theme includes CSS styles needed to display LiteCommerce pages and blocks properly and can be used for a \"regular\" Drupal-based website as well as for an ecommerce website based on Drupal + LiteCommerce.<br /> <a class=\"download\" href=\"http://www.litecommerce.com/download/lccms_theme-3.0.0-alpha.tgz\">Download \"LiteSky CMS\" theme</a></p>\r\n</div>\r\n<div class=\"section-system-req\">\r\n<h2>System requirements</h2>\r\n<ul>\r\n<li id=\"sr-php\">PHP ver.5.2.0 <span class=\"note\">or higher</span></li>\r\n<li id=\"sr-mysql\">MySQL ver.5.0.3 <span class=\"note\">or higher</span></li>\r\n<li id=\"sr-gd\">GDlib ver.2.0 <span class=\"note\">or higher</span></li>\r\n<li id=\"sr-ssl\">SSL sertificate <span class=\"note\">(recommended)</span><br /><a href=\"http://marketplace.x-cart.com/Security/ssl-certificates/\">Purchase</a></li>\r\n</ul>\r\n<a href=\"http://help.qtmsoft.com/index.php?title=LiteCommerce:Server_Requirements_(LC_3.0)\">The complete list of requirements</a></div>','','filtered_html','node');
INSERT INTO `drupal_field_data_body` VALUES ('page',0,3,3,'und',0,'<?php\r\n$block = module_invoke(\'block\', \'block\', \'view\', 10);\r\nprint $block[\'content\'];\r\n?>\r\n<div class=\"clear-fix\">\r\n<?php\r\n$block = module_invoke(\'block\', \'block\', \'view\', 11);\r\nprint $block[\'content\'];\r\n?>\r\n<div id=\"support-bt-kb\">\r\n<h2 class=\"myriad\">Found a Bug?</h2>\r\n<div class=\"report-bug clear-fix\"><form action=\"http://bt.litecommerce.com/bug_report_page.php\" method=\"post\">\r\n<div class=\"clear-fix\"><input name=\"summary\" type=\"text\" value=\"Report bug\" onfocus=\"if (this.value == \'Report bug\') this.value = \'\'\" onblur=\"if (this.value == \'\') this.value = \'Report bug\'\" /> <button id=\"submit\"><span>Report</span></button></div>\r\n</form></div>\r\n<div class=\"search-ticket clear-fix\"><form action=\"http://bt.litecommerce.com/jump_to_bug.php\" method=\"post\">\r\n<div class=\"clear-fix\"><input name=\"bug_id\" type=\"text\" value=\"Ticket search\" onfocus=\"if (this.value == \'Ticket search\') this.value = \'\'\" onblur=\"if (this.value == \'\') this.value = \'Ticket search\'\" /> <button id=\"submit\"><span>Search</span></button></div>\r\n</form></div>\r\n<!--\r\n<h2 class=\"myriad\">LiteCommerce Knowledge Base</h2>\r\n<ul class=\"features\">\r\n<li><a href=\"?q=\">FAQs</a></li>\r\n<li><a href=\"?q=\">User manuals</a></li>\r\n<li><a href=\"?q=\">PDFs</a></li>\r\n<li><a href=\"?q=\">Programmer\'s guide</a></li>\r\n</ul>\r\n-->\r\n</div>\r\n</div>','','php_code','node');
INSERT INTO `drupal_field_data_body` VALUES ('page',0,5,5,'und',0,'<ul>\r\n  <li><a href=\"about.html\">About us</a>\r\n  <li><a href=\"q=blog\">Blog</a>\r\n  <li><a href=\"q=contact\">Contact us</a>\r\n</ul>','','filtered_html','node');
INSERT INTO `drupal_field_data_body` VALUES ('page',0,6,6,'und',0,'<p>LiteCommerce v3 is a product of Creative Development DBA Qualiteam. Our company has been making e-commerce software since 2001. Our main focus is on commercial open source PHP shopping cart software. Our products are: F-Cart, X-Cart, LiteCommerce and Ecwid. Started with just three employees, our company has grown to over 140 people. Over 50,000 web stores are based on solutions designed by Creative Development.</p>\r\n<p>The mission of our company is to make the web both convenient and effective business environment, for us, our clients and the clients of our clients.</p>\r\n<div id=\"more-info\" class=\"myriad-light\">If you need more information <a href=\"contact\">contact us</a> or read <a href=\"blog\">our blog</a></div>','','filtered_html','node');
/*!40000 ALTER TABLE `drupal_field_data_body` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_field_data_comment_body`
--

DROP TABLE IF EXISTS `drupal_field_data_comment_body`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_field_data_comment_body` (
  `bundle` varchar(128) NOT NULL DEFAULT '' COMMENT 'The field instance bundle to which this row belongs, used when deleting a field instance',
  `deleted` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'A boolean indicating whether this data item has been deleted',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'The entity id this data is attached to',
  `revision_id` int(10) unsigned DEFAULT NULL COMMENT 'The entity revision id this data is attached to, or NULL if the entity type is not versioned',
  `language` varchar(32) NOT NULL DEFAULT '' COMMENT 'The language for this data item.',
  `delta` int(10) unsigned NOT NULL COMMENT 'The sequence number for this data item, used for multi-value fields',
  `comment_body_value` longtext,
  `comment_body_format` varchar(255) DEFAULT NULL,
  `entity_type` varchar(128) NOT NULL DEFAULT '' COMMENT 'The entity type this data is attached to.',
  PRIMARY KEY (`entity_type`,`entity_id`,`deleted`,`delta`,`language`),
  KEY `bundle` (`bundle`),
  KEY `deleted` (`deleted`),
  KEY `entity_id` (`entity_id`),
  KEY `revision_id` (`revision_id`),
  KEY `language` (`language`),
  KEY `comment_body_format` (`comment_body_format`),
  KEY `entity_type` (`entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Data storage for field 1 (comment_body)';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_field_data_comment_body`
--

LOCK TABLES `drupal_field_data_comment_body` WRITE;
/*!40000 ALTER TABLE `drupal_field_data_comment_body` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_field_data_comment_body` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_field_data_field_image`
--

DROP TABLE IF EXISTS `drupal_field_data_field_image`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_field_data_field_image` (
  `bundle` varchar(128) NOT NULL DEFAULT '' COMMENT 'The field instance bundle to which this row belongs, used when deleting a field instance',
  `deleted` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'A boolean indicating whether this data item has been deleted',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'The entity id this data is attached to',
  `revision_id` int(10) unsigned DEFAULT NULL COMMENT 'The entity revision id this data is attached to, or NULL if the entity type is not versioned',
  `language` varchar(32) NOT NULL DEFAULT '' COMMENT 'The language for this data item.',
  `delta` int(10) unsigned NOT NULL COMMENT 'The sequence number for this data item, used for multi-value fields',
  `field_image_fid` int(10) unsigned DEFAULT NULL COMMENT 'The drupal_file_managed.fid being referenced in this field.',
  `field_image_alt` varchar(128) DEFAULT NULL COMMENT 'Alternative image text, for the image’s ’alt’ attribute.',
  `field_image_title` varchar(128) DEFAULT NULL COMMENT 'Image title text, for the image’s ’title’ attribute.',
  `entity_type` varchar(128) NOT NULL DEFAULT '' COMMENT 'The entity type this data is attached to.',
  PRIMARY KEY (`entity_type`,`entity_id`,`deleted`,`delta`,`language`),
  KEY `bundle` (`bundle`),
  KEY `deleted` (`deleted`),
  KEY `entity_id` (`entity_id`),
  KEY `revision_id` (`revision_id`),
  KEY `language` (`language`),
  KEY `field_image_fid` (`field_image_fid`),
  KEY `entity_type` (`entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Data storage for field 4 (field_image)';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_field_data_field_image`
--

LOCK TABLES `drupal_field_data_field_image` WRITE;
/*!40000 ALTER TABLE `drupal_field_data_field_image` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_field_data_field_image` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_field_data_field_tags`
--

DROP TABLE IF EXISTS `drupal_field_data_field_tags`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_field_data_field_tags` (
  `bundle` varchar(128) NOT NULL DEFAULT '' COMMENT 'The field instance bundle to which this row belongs, used when deleting a field instance',
  `deleted` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'A boolean indicating whether this data item has been deleted',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'The entity id this data is attached to',
  `revision_id` int(10) unsigned DEFAULT NULL COMMENT 'The entity revision id this data is attached to, or NULL if the entity type is not versioned',
  `language` varchar(32) NOT NULL DEFAULT '' COMMENT 'The language for this data item.',
  `delta` int(10) unsigned NOT NULL COMMENT 'The sequence number for this data item, used for multi-value fields',
  `field_tags_tid` int(10) unsigned DEFAULT NULL,
  `entity_type` varchar(128) NOT NULL DEFAULT '' COMMENT 'The entity type this data is attached to.',
  PRIMARY KEY (`entity_type`,`entity_id`,`deleted`,`delta`,`language`),
  KEY `bundle` (`bundle`),
  KEY `deleted` (`deleted`),
  KEY `entity_id` (`entity_id`),
  KEY `revision_id` (`revision_id`),
  KEY `language` (`language`),
  KEY `field_tags_tid` (`field_tags_tid`),
  KEY `entity_type` (`entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Data storage for field 3 (field_tags)';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_field_data_field_tags`
--

LOCK TABLES `drupal_field_data_field_tags` WRITE;
/*!40000 ALTER TABLE `drupal_field_data_field_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_field_data_field_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_field_data_taxonomy_forums`
--

DROP TABLE IF EXISTS `drupal_field_data_taxonomy_forums`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_field_data_taxonomy_forums` (
  `bundle` varchar(128) NOT NULL DEFAULT '' COMMENT 'The field instance bundle to which this row belongs, used when deleting a field instance',
  `deleted` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'A boolean indicating whether this data item has been deleted',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'The entity id this data is attached to',
  `revision_id` int(10) unsigned DEFAULT NULL COMMENT 'The entity revision id this data is attached to, or NULL if the entity type is not versioned',
  `language` varchar(32) NOT NULL DEFAULT '' COMMENT 'The language for this data item.',
  `delta` int(10) unsigned NOT NULL COMMENT 'The sequence number for this data item, used for multi-value fields',
  `taxonomy_forums_tid` int(10) unsigned DEFAULT NULL,
  `entity_type` varchar(128) NOT NULL DEFAULT '' COMMENT 'The entity type this data is attached to.',
  PRIMARY KEY (`entity_type`,`entity_id`,`deleted`,`delta`,`language`),
  KEY `bundle` (`bundle`),
  KEY `deleted` (`deleted`),
  KEY `entity_id` (`entity_id`),
  KEY `revision_id` (`revision_id`),
  KEY `language` (`language`),
  KEY `taxonomy_forums_tid` (`taxonomy_forums_tid`),
  KEY `entity_type` (`entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Data storage for field 5 (taxonomy_forums)';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_field_data_taxonomy_forums`
--

LOCK TABLES `drupal_field_data_taxonomy_forums` WRITE;
/*!40000 ALTER TABLE `drupal_field_data_taxonomy_forums` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_field_data_taxonomy_forums` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_field_revision_body`
--

DROP TABLE IF EXISTS `drupal_field_revision_body`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_field_revision_body` (
  `bundle` varchar(128) NOT NULL DEFAULT '' COMMENT 'The field instance bundle to which this row belongs, used when deleting a field instance',
  `deleted` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'A boolean indicating whether this data item has been deleted',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'The entity id this data is attached to',
  `revision_id` int(10) unsigned NOT NULL COMMENT 'The entity revision id this data is attached to',
  `language` varchar(32) NOT NULL DEFAULT '' COMMENT 'The language for this data item.',
  `delta` int(10) unsigned NOT NULL COMMENT 'The sequence number for this data item, used for multi-value fields',
  `body_value` longtext,
  `body_summary` longtext,
  `body_format` varchar(255) DEFAULT NULL,
  `entity_type` varchar(128) NOT NULL DEFAULT '' COMMENT 'The entity type this data is attached to.',
  PRIMARY KEY (`entity_type`,`entity_id`,`revision_id`,`deleted`,`delta`,`language`),
  KEY `bundle` (`bundle`),
  KEY `deleted` (`deleted`),
  KEY `entity_id` (`entity_id`),
  KEY `revision_id` (`revision_id`),
  KEY `language` (`language`),
  KEY `body_format` (`body_format`),
  KEY `entity_type` (`entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Revision archive storage for field 2 (body)';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_field_revision_body`
--

LOCK TABLES `drupal_field_revision_body` WRITE;
/*!40000 ALTER TABLE `drupal_field_revision_body` DISABLE KEYS */;
INSERT INTO `drupal_field_revision_body` VALUES ('page',0,1,1,'und',0,'<div class=\"two-column-page\">\r\n<div class=\"column\">\r\n<h2>Setup &amp; Architecture</h2>\r\n<ul class=\"features\">\r\n<li>Free open-source PHP/MySQL solution</li>\r\n<li>Can work as a standalone online store and in connection with Drupal CMS with tons of ready-made Drupal modules</li>\r\n<li>Web-based installation wizard that installs both LiteCommerce and Drupal (when run from the <a href=\"download-free.html\">Ecommerce CMS package</a>)</li>\r\n<li>Storefront sections and blocks can be managed in the Drupal user interface</li>\r\n<li>Modern object-oriented MVC architecture</li>\r\n<li>Flexible modular system allows customizing without hacks to core files and simplifies upgrades</li>\r\n<li>HTTPS/SSL support</li>\r\n<li>Fully customizable design &amp; layout</li>\r\n<li>Compatible with popular browsers:<br />IE 6+, Firefox 2+, Chrome 4+, Safari 3+, Opera 10+</li>\r\n</ul>\r\n<h2>Catalog Management</h2>\r\n<ul class=\"features\">\r\n<li>Unlimited number of products and categories</li>\r\n<li>Product options with optional price modifiers</li>\r\n<li>Inventory tracking per product or product variation</li>\r\n<li>Automatic thumbnail generation with image sharpening</li>\r\n<li>A product can belong to more than one category</li>\r\n<li>Product import from CSV files</li>\r\n</ul>\r\n<h2>Marketing &amp; Promotion</h2>\r\n<ul class=\"features\">\r\n<li>Custom search engine friendly URLs (when connected to Drupal)</li>\r\n<li>Custom META tags for products, categories and other website pages (when connected to Drupal)</li>\r\n<li>Discounts</li>\r\n<li>Wholesale pricing</li>\r\n<li>Featured products</li>\r\n<li>Bestsellers</li>\r\n<li>Recently added products</li>\r\n<li>Gift Certificates</li>\r\n</ul>\r\n</div>\r\n<div class=\"column\">\r\n<h2>Shopping Experience</h2>\r\n<ul class=\"features\">\r\n<li>Storefront sections and blocks transparently integrate with Drupal pages into a single ecommerce website</li>\r\n<li>Catalog pages are updated via AJAX without page reloading</li>\r\n<li>Image galleries with a popup image browser and an <br />in-page zoom function</li>\r\n<li>Previous and Next links on product pages</li>\r\n<li>Mouse wheel updates product quantities</li>\r\n<li>Wish List</li>\r\n<li>List of recently viewed products</li>\r\n<li>Horizontal and vertical \"minicart\" widgets</li>\r\n<li>Quick search form</li>\r\n</ul>\r\n<ul class=\"features\">\r\n</ul>\r\n<ul class=\"features\">\r\n</ul>\r\n<h2>Orders, Shipping and Tax</h2>\r\n<ul class=\"features\">\r\n<li>Customizable e-mail notifications</li>\r\n<li>Order history for customers and administrator</li>\r\n<li>Payment and shipping status tracking</li>\r\n<li>Invoice printing</li>\r\n<li>Support for PayPal Standard, PayPal Express Checkout, Google Checkout and Authorize.NET (SIM)</li>\r\n<li>Configurable min/max order amount limits</li>\r\n<li>Real-time UPS and USPS shipping rates</li>\r\n<li>Unlimited number of admin-defined delivery methods</li>\r\n<li>Flat, weight, order total and range based shipping rates</li>\r\n<li>International, domestic and local shipping</li>\r\n<li>Customizable tax calculation</li>\r\n<li>Import/export predefined tax schemas</li>\r\n<li>Product-specific taxes</li>\r\n<li>Taxes &amp; shipping fees depending on the customer location</li>\r\n<li>Tax Exempt feature</li>\r\n<li>GST/PST (Canadian tax system)</li>\r\n<li>Configurable measurement units, date/time formats and currency symbol</li>\r\n<li>Export sales &amp; customer data for use in a spreadsheet</li>\r\n<li>Export orders to MS Excel XP format</li>\r\n</ul>\r\n<ul class=\"features\">\r\n</ul>\r\n</div>\r\n</div>\r\n<h2>LiteCommerce is compatible with:</h2>\r\n<div class=\"feature-compatibility\">\r\n<div class=\"clear-fix\"><img class=\"inline-image\" style=\"padding-top: 12px;\" src=\"http://www.paypal.com/en_US/i/logo/PayPal_mark_60x38.gif\" alt=\"PayPal\" width=\"60\" height=\"38\" /><img class=\"inline-image\" style=\"padding-top: 10px; margin-right: 16px;\" src=\"http://xcart2-530.crtdev.local/~xplorer/xlite_cms/src/sites/default/files/google-checkout.jpg\" alt=\"Google Checkout\" width=\"107\" height=\"40\" /><img class=\"inline-image\" style=\"padding-top: 8px;\" src=\"http://drupal.org/files/powered-blue-135x42.png\" alt=\"Drupal\" width=\"135\" height=\"42\" /><img class=\"inline-image\" src=\"http://xcart2-530.crtdev.local/~xplorer/xlite_cms/src/sites/default/files/ups-logo.gif\" alt=\"UPS\" width=\"48\" height=\"58\" /><img class=\"inline-image\" style=\"padding-top: 3px;\" src=\"http://xcart2-530.crtdev.local/~xplorer/xlite_cms/src/sites/default/files/usps-logo.gif\" alt=\"USPS\" width=\"63\" height=\"52\" /></div>\r\n</div>','','filtered_html','node');
INSERT INTO `drupal_field_revision_body` VALUES ('page',0,2,2,'und',0,'<div id=\"download-scheme\">\r\n<div id=\"download-steps\">\r\n<div id=\"download-step-1\">\r\n<div class=\"download-section-title\"><a href=\"http://www.litecommerce.com/download/drupal-lc-3.0.0-alpha.tgz\">Download</a></div>\r\nthe Ecommerce CMS<br /> package</div>\r\n<div id=\"download-step-2\">\r\n<div class=\"download-section-title\">Install</div>\r\nthe package on your<br /> web server</div>\r\n<div id=\"download-step-3\">\r\n<div class=\"download-section-title\">Customize</div>\r\nits design and functionality<br />to your needs</div>\r\n<div id=\"download-step-4\">\r\n<div class=\"download-section-title\">Configure</div>\r\nshipping methods, add products,<br /> select a payment gateway</div>\r\n<div id=\"download-step-5\">\r\n<div class=\"download-section-title\">Start selling!</div>\r\n</div>\r\n</div>\r\n</div>\r\n<div class=\"section-downloads\">\r\n<div class=\"title-section\">Drupal + LiteCommerce in a single package</div>\r\n<p>A single Ecommerce CMS package includes the latest stable version of Drupal (with \"LiteSky CMS\" theme and a set of popular modules) integrated with the alpha version of LiteCommerce v3 shopping cart. LiteCommerce v3 alpha version is not recommended for production sites. However users wishing to help test and develop LiteCommerce are encouraged to use this package instead of downloading and installing all the components individually.</p>\r\n<div id=\"download-now\">\r\n<div class=\"clear-fix\"><a id=\"btn-download-now\" href=\"http://www.litecommerce.com/download/drupal-lc-3.0.0-alpha.tgz\">Download</a>\r\n<div id=\"download-now-info\">Drupal v. 6.16 <span class=\"note\">(including LC Connector, LC theme and popular modules)</span><br /> LiteCommerce v3 alpha</div>\r\n</div>\r\n</div>\r\n<div class=\"title-subsection\">LiteCommerce v3 (standalone)</div>\r\n<p>Standalone LiteCommerce v3 alpha. Can be integrated with an existing Drupal-based site via LC Connector module. Since it is an alpha version, it is not recommended for production sites. However users wishing to help test and develop LiteCommerce may use it either in a standalone mode or in connection with Drupal.<br /> <a class=\"download\" href=\"http://www.litecommerce.com/download/litecommerce-3.0.0-alpha.tgz\">Download LiteCommerce v3 alpha</a></p>\r\n<div class=\"title-subsection\">LC Connector module for Drupal 6.x</div>\r\n<p>This Drupal module integrates LiteCommerce storefront pages and blocks into a Drupal-based website.<br /> <a class=\"download\" href=\"http://www.litecommerce.com/download/lc_connector-3.0.0-alpha.tgz\">Download \"LC Connector\" module</a></p>\r\n<div class=\"title-subsection\">Bettercrumbs module for Drupal 6.x</div>\r\n<p>This Drupal module allows you to hide/show breadcrumbs for each Drupal node.<br /> <a class=\"download\" href=\"http://www.litecommerce.com/download/bettercrumbs-3.0.0-alpha.tgz\">Download \"Bettercrumbs\" module</a></p>\r\n<div class=\"title-subsection\">\"LiteSky CMS\" theme for Drupal 6.x</div>\r\n<p>This Drupal 6.x theme includes CSS styles needed to display LiteCommerce pages and blocks properly and can be used for a \"regular\" Drupal-based website as well as for an ecommerce website based on Drupal + LiteCommerce.<br /> <a class=\"download\" href=\"http://www.litecommerce.com/download/lccms_theme-3.0.0-alpha.tgz\">Download \"LiteSky CMS\" theme</a></p>\r\n</div>\r\n<div class=\"section-system-req\">\r\n<h2>System requirements</h2>\r\n<ul>\r\n<li id=\"sr-php\">PHP ver.5.2.0 <span class=\"note\">or higher</span></li>\r\n<li id=\"sr-mysql\">MySQL ver.5.0.3 <span class=\"note\">or higher</span></li>\r\n<li id=\"sr-gd\">GDlib ver.2.0 <span class=\"note\">or higher</span></li>\r\n<li id=\"sr-ssl\">SSL sertificate <span class=\"note\">(recommended)</span><br /><a href=\"http://marketplace.x-cart.com/Security/ssl-certificates/\">Purchase</a></li>\r\n</ul>\r\n<a href=\"http://help.qtmsoft.com/index.php?title=LiteCommerce:Server_Requirements_(LC_3.0)\">The complete list of requirements</a></div>','','filtered_html','node');
INSERT INTO `drupal_field_revision_body` VALUES ('page',0,3,3,'und',0,'<?php\r\n$block = module_invoke(\'block\', \'block\', \'view\', 10);\r\nprint $block[\'content\'];\r\n?>\r\n<div class=\"clear-fix\">\r\n<?php\r\n$block = module_invoke(\'block\', \'block\', \'view\', 11);\r\nprint $block[\'content\'];\r\n?>\r\n<div id=\"support-bt-kb\">\r\n<h2 class=\"myriad\">Found a Bug?</h2>\r\n<div class=\"report-bug clear-fix\"><form action=\"http://bt.litecommerce.com/bug_report_page.php\" method=\"post\">\r\n<div class=\"clear-fix\"><input name=\"summary\" type=\"text\" value=\"Report bug\" onfocus=\"if (this.value == \'Report bug\') this.value = \'\'\" onblur=\"if (this.value == \'\') this.value = \'Report bug\'\" /> <button id=\"submit\"><span>Report</span></button></div>\r\n</form></div>\r\n<div class=\"search-ticket clear-fix\"><form action=\"http://bt.litecommerce.com/jump_to_bug.php\" method=\"post\">\r\n<div class=\"clear-fix\"><input name=\"bug_id\" type=\"text\" value=\"Ticket search\" onfocus=\"if (this.value == \'Ticket search\') this.value = \'\'\" onblur=\"if (this.value == \'\') this.value = \'Ticket search\'\" /> <button id=\"submit\"><span>Search</span></button></div>\r\n</form></div>\r\n<!--\r\n<h2 class=\"myriad\">LiteCommerce Knowledge Base</h2>\r\n<ul class=\"features\">\r\n<li><a href=\"?q=\">FAQs</a></li>\r\n<li><a href=\"?q=\">User manuals</a></li>\r\n<li><a href=\"?q=\">PDFs</a></li>\r\n<li><a href=\"?q=\">Programmer\'s guide</a></li>\r\n</ul>\r\n-->\r\n</div>\r\n</div>','','php_code','node');
INSERT INTO `drupal_field_revision_body` VALUES ('page',0,5,5,'und',0,'<ul>\r\n  <li><a href=\"about.html\">About us</a>\r\n  <li><a href=\"q=blog\">Blog</a>\r\n  <li><a href=\"q=contact\">Contact us</a>\r\n</ul>','','filtered_html','node');
INSERT INTO `drupal_field_revision_body` VALUES ('page',0,6,6,'und',0,'<p>LiteCommerce v3 is a product of Creative Development DBA Qualiteam. Our company has been making e-commerce software since 2001. Our main focus is on commercial open source PHP shopping cart software. Our products are: F-Cart, X-Cart, LiteCommerce and Ecwid. Started with just three employees, our company has grown to over 140 people. Over 50,000 web stores are based on solutions designed by Creative Development.</p>\r\n<p>The mission of our company is to make the web both convenient and effective business environment, for us, our clients and the clients of our clients.</p>\r\n<div id=\"more-info\" class=\"myriad-light\">If you need more information <a href=\"contact\">contact us</a> or read <a href=\"blog\">our blog</a></div>','','filtered_html','node');
/*!40000 ALTER TABLE `drupal_field_revision_body` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_field_revision_comment_body`
--

DROP TABLE IF EXISTS `drupal_field_revision_comment_body`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_field_revision_comment_body` (
  `bundle` varchar(128) NOT NULL DEFAULT '' COMMENT 'The field instance bundle to which this row belongs, used when deleting a field instance',
  `deleted` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'A boolean indicating whether this data item has been deleted',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'The entity id this data is attached to',
  `revision_id` int(10) unsigned NOT NULL COMMENT 'The entity revision id this data is attached to',
  `language` varchar(32) NOT NULL DEFAULT '' COMMENT 'The language for this data item.',
  `delta` int(10) unsigned NOT NULL COMMENT 'The sequence number for this data item, used for multi-value fields',
  `comment_body_value` longtext,
  `comment_body_format` varchar(255) DEFAULT NULL,
  `entity_type` varchar(128) NOT NULL DEFAULT '' COMMENT 'The entity type this data is attached to.',
  PRIMARY KEY (`entity_type`,`entity_id`,`revision_id`,`deleted`,`delta`,`language`),
  KEY `bundle` (`bundle`),
  KEY `deleted` (`deleted`),
  KEY `entity_id` (`entity_id`),
  KEY `revision_id` (`revision_id`),
  KEY `language` (`language`),
  KEY `comment_body_format` (`comment_body_format`),
  KEY `entity_type` (`entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Revision archive storage for field 1 (comment_body)';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_field_revision_comment_body`
--

LOCK TABLES `drupal_field_revision_comment_body` WRITE;
/*!40000 ALTER TABLE `drupal_field_revision_comment_body` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_field_revision_comment_body` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_field_revision_field_image`
--

DROP TABLE IF EXISTS `drupal_field_revision_field_image`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_field_revision_field_image` (
  `bundle` varchar(128) NOT NULL DEFAULT '' COMMENT 'The field instance bundle to which this row belongs, used when deleting a field instance',
  `deleted` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'A boolean indicating whether this data item has been deleted',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'The entity id this data is attached to',
  `revision_id` int(10) unsigned NOT NULL COMMENT 'The entity revision id this data is attached to',
  `language` varchar(32) NOT NULL DEFAULT '' COMMENT 'The language for this data item.',
  `delta` int(10) unsigned NOT NULL COMMENT 'The sequence number for this data item, used for multi-value fields',
  `field_image_fid` int(10) unsigned DEFAULT NULL COMMENT 'The drupal_file_managed.fid being referenced in this field.',
  `field_image_alt` varchar(128) DEFAULT NULL COMMENT 'Alternative image text, for the image’s ’alt’ attribute.',
  `field_image_title` varchar(128) DEFAULT NULL COMMENT 'Image title text, for the image’s ’title’ attribute.',
  `entity_type` varchar(128) NOT NULL DEFAULT '' COMMENT 'The entity type this data is attached to.',
  PRIMARY KEY (`entity_type`,`entity_id`,`revision_id`,`deleted`,`delta`,`language`),
  KEY `bundle` (`bundle`),
  KEY `deleted` (`deleted`),
  KEY `entity_id` (`entity_id`),
  KEY `revision_id` (`revision_id`),
  KEY `language` (`language`),
  KEY `field_image_fid` (`field_image_fid`),
  KEY `entity_type` (`entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Revision archive storage for field 4 (field_image)';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_field_revision_field_image`
--

LOCK TABLES `drupal_field_revision_field_image` WRITE;
/*!40000 ALTER TABLE `drupal_field_revision_field_image` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_field_revision_field_image` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_field_revision_field_tags`
--

DROP TABLE IF EXISTS `drupal_field_revision_field_tags`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_field_revision_field_tags` (
  `bundle` varchar(128) NOT NULL DEFAULT '' COMMENT 'The field instance bundle to which this row belongs, used when deleting a field instance',
  `deleted` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'A boolean indicating whether this data item has been deleted',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'The entity id this data is attached to',
  `revision_id` int(10) unsigned NOT NULL COMMENT 'The entity revision id this data is attached to',
  `language` varchar(32) NOT NULL DEFAULT '' COMMENT 'The language for this data item.',
  `delta` int(10) unsigned NOT NULL COMMENT 'The sequence number for this data item, used for multi-value fields',
  `field_tags_tid` int(10) unsigned DEFAULT NULL,
  `entity_type` varchar(128) NOT NULL DEFAULT '' COMMENT 'The entity type this data is attached to.',
  PRIMARY KEY (`entity_type`,`entity_id`,`revision_id`,`deleted`,`delta`,`language`),
  KEY `bundle` (`bundle`),
  KEY `deleted` (`deleted`),
  KEY `entity_id` (`entity_id`),
  KEY `revision_id` (`revision_id`),
  KEY `language` (`language`),
  KEY `field_tags_tid` (`field_tags_tid`),
  KEY `entity_type` (`entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Revision archive storage for field 3 (field_tags)';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_field_revision_field_tags`
--

LOCK TABLES `drupal_field_revision_field_tags` WRITE;
/*!40000 ALTER TABLE `drupal_field_revision_field_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_field_revision_field_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_field_revision_taxonomy_forums`
--

DROP TABLE IF EXISTS `drupal_field_revision_taxonomy_forums`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_field_revision_taxonomy_forums` (
  `bundle` varchar(128) NOT NULL DEFAULT '' COMMENT 'The field instance bundle to which this row belongs, used when deleting a field instance',
  `deleted` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'A boolean indicating whether this data item has been deleted',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'The entity id this data is attached to',
  `revision_id` int(10) unsigned NOT NULL COMMENT 'The entity revision id this data is attached to',
  `language` varchar(32) NOT NULL DEFAULT '' COMMENT 'The language for this data item.',
  `delta` int(10) unsigned NOT NULL COMMENT 'The sequence number for this data item, used for multi-value fields',
  `taxonomy_forums_tid` int(10) unsigned DEFAULT NULL,
  `entity_type` varchar(128) NOT NULL DEFAULT '' COMMENT 'The entity type this data is attached to.',
  PRIMARY KEY (`entity_type`,`entity_id`,`revision_id`,`deleted`,`delta`,`language`),
  KEY `bundle` (`bundle`),
  KEY `deleted` (`deleted`),
  KEY `entity_id` (`entity_id`),
  KEY `revision_id` (`revision_id`),
  KEY `language` (`language`),
  KEY `taxonomy_forums_tid` (`taxonomy_forums_tid`),
  KEY `entity_type` (`entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Revision archive storage for field 5 (taxonomy_forums)';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_field_revision_taxonomy_forums`
--

LOCK TABLES `drupal_field_revision_taxonomy_forums` WRITE;
/*!40000 ALTER TABLE `drupal_field_revision_taxonomy_forums` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_field_revision_taxonomy_forums` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_file_managed`
--

DROP TABLE IF EXISTS `drupal_file_managed`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_file_managed` (
  `fid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'File ID.',
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The drupal_users.uid of the user who is associated with the file.',
  `filename` varchar(255) NOT NULL DEFAULT '' COMMENT 'Name of the file with no path components. This may differ from the basename of the URI if the file is renamed to avoid overwriting an existing file.',
  `uri` varchar(255) NOT NULL DEFAULT '' COMMENT 'The URI to access the file (either local or remote).',
  `filemime` varchar(255) NOT NULL DEFAULT '' COMMENT 'The file’s MIME type.',
  `filesize` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The size of the file in bytes.',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'A field indicating the status of the file. Two status are defined in core: temporary (0) and permanent (1). Temporary files older than DRUPAL_MAXIMUM_TEMP_FILE_AGE will be removed during a cron run.',
  `timestamp` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'UNIX timestamp for when the file was added.',
  PRIMARY KEY (`fid`),
  UNIQUE KEY `uri` (`uri`),
  KEY `uid` (`uid`),
  KEY `status` (`status`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores information for uploaded files.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_file_managed`
--

LOCK TABLES `drupal_file_managed` WRITE;
/*!40000 ALTER TABLE `drupal_file_managed` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_file_managed` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_file_usage`
--

DROP TABLE IF EXISTS `drupal_file_usage`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_file_usage` (
  `fid` int(10) unsigned NOT NULL COMMENT 'File ID.',
  `module` varchar(255) NOT NULL DEFAULT '' COMMENT 'The name of the module that is using the file.',
  `type` varchar(64) NOT NULL DEFAULT '' COMMENT 'The name of the object type in which the file is used.',
  `id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The primary key of the object using the file.',
  `count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The number of times this file is used by this object.',
  PRIMARY KEY (`fid`,`type`,`id`,`module`),
  KEY `type_id` (`type`,`id`),
  KEY `fid_count` (`fid`,`count`),
  KEY `fid_module` (`fid`,`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Track where a file is used.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_file_usage`
--

LOCK TABLES `drupal_file_usage` WRITE;
/*!40000 ALTER TABLE `drupal_file_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_file_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_filter`
--

DROP TABLE IF EXISTS `drupal_filter`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_filter` (
  `format` varchar(255) NOT NULL COMMENT 'Foreign key: The drupal_filter_format.format to which this filter is assigned.',
  `module` varchar(64) NOT NULL DEFAULT '' COMMENT 'The origin module of the filter.',
  `name` varchar(32) NOT NULL DEFAULT '' COMMENT 'Name of the filter being referenced.',
  `weight` int(11) NOT NULL DEFAULT '0' COMMENT 'Weight of filter within format.',
  `status` int(11) NOT NULL DEFAULT '0' COMMENT 'Filter enabled status. (1 = enabled, 0 = disabled)',
  `settings` longblob COMMENT 'A serialized array of name value pairs that store the filter settings for the specific format.',
  PRIMARY KEY (`format`,`name`),
  KEY `list` (`weight`,`module`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table that maps filters (HTML corrector) to text formats ...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_filter`
--

LOCK TABLES `drupal_filter` WRITE;
/*!40000 ALTER TABLE `drupal_filter` DISABLE KEYS */;
INSERT INTO `drupal_filter` VALUES ('filtered_html','filter','filter_autop',2,1,'a:0:{}');
INSERT INTO `drupal_filter` VALUES ('filtered_html','filter','filter_html',1,1,'a:3:{s:12:\"allowed_html\";s:74:\"<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd>\";s:16:\"filter_html_help\";i:1;s:20:\"filter_html_nofollow\";i:0;}');
INSERT INTO `drupal_filter` VALUES ('filtered_html','filter','filter_htmlcorrector',10,1,'a:0:{}');
INSERT INTO `drupal_filter` VALUES ('filtered_html','filter','filter_html_escape',10,0,'a:0:{}');
INSERT INTO `drupal_filter` VALUES ('filtered_html','filter','filter_url',0,1,'a:1:{s:17:\"filter_url_length\";i:72;}');
INSERT INTO `drupal_filter` VALUES ('full_html','filter','filter_autop',1,1,'a:0:{}');
INSERT INTO `drupal_filter` VALUES ('full_html','filter','filter_html',10,0,'a:3:{s:12:\"allowed_html\";s:74:\"<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd>\";s:16:\"filter_html_help\";i:1;s:20:\"filter_html_nofollow\";i:0;}');
INSERT INTO `drupal_filter` VALUES ('full_html','filter','filter_htmlcorrector',10,1,'a:0:{}');
INSERT INTO `drupal_filter` VALUES ('full_html','filter','filter_html_escape',10,0,'a:0:{}');
INSERT INTO `drupal_filter` VALUES ('full_html','filter','filter_url',0,1,'a:1:{s:17:\"filter_url_length\";i:72;}');
INSERT INTO `drupal_filter` VALUES ('php_code','filter','filter_autop',10,0,'a:0:{}');
INSERT INTO `drupal_filter` VALUES ('php_code','filter','filter_html',10,0,'a:3:{s:12:\"allowed_html\";s:74:\"<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd>\";s:16:\"filter_html_help\";i:1;s:20:\"filter_html_nofollow\";i:0;}');
INSERT INTO `drupal_filter` VALUES ('php_code','filter','filter_htmlcorrector',10,0,'a:0:{}');
INSERT INTO `drupal_filter` VALUES ('php_code','filter','filter_html_escape',10,0,'a:0:{}');
INSERT INTO `drupal_filter` VALUES ('php_code','filter','filter_url',10,0,'a:1:{s:17:\"filter_url_length\";i:72;}');
INSERT INTO `drupal_filter` VALUES ('php_code','php','php_code',0,1,'a:0:{}');
INSERT INTO `drupal_filter` VALUES ('plain_text','filter','filter_autop',2,1,'a:0:{}');
INSERT INTO `drupal_filter` VALUES ('plain_text','filter','filter_html',10,0,'a:3:{s:12:\"allowed_html\";s:74:\"<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd>\";s:16:\"filter_html_help\";i:1;s:20:\"filter_html_nofollow\";i:0;}');
INSERT INTO `drupal_filter` VALUES ('plain_text','filter','filter_htmlcorrector',10,0,'a:0:{}');
INSERT INTO `drupal_filter` VALUES ('plain_text','filter','filter_html_escape',0,1,'a:0:{}');
INSERT INTO `drupal_filter` VALUES ('plain_text','filter','filter_url',1,1,'a:1:{s:17:\"filter_url_length\";i:72;}');
/*!40000 ALTER TABLE `drupal_filter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_filter_format`
--

DROP TABLE IF EXISTS `drupal_filter_format`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_filter_format` (
  `format` varchar(255) NOT NULL COMMENT 'Primary Key: Unique machine name of the format.',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT 'Name of the text format (Filtered HTML).',
  `cache` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Flag to indicate whether format is cacheable. (1 = cacheable, 0 = not cacheable)',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT 'The status of the text format. (1 = enabled, 0 = disabled)',
  `weight` int(11) NOT NULL DEFAULT '0' COMMENT 'Weight of text format to use when listing.',
  PRIMARY KEY (`format`),
  UNIQUE KEY `name` (`name`),
  KEY `status_weight` (`status`,`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores text formats: custom groupings of filters, such as...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_filter_format`
--

LOCK TABLES `drupal_filter_format` WRITE;
/*!40000 ALTER TABLE `drupal_filter_format` DISABLE KEYS */;
INSERT INTO `drupal_filter_format` VALUES ('filtered_html','Filtered HTML',1,1,0);
INSERT INTO `drupal_filter_format` VALUES ('full_html','Full HTML',1,1,1);
INSERT INTO `drupal_filter_format` VALUES ('php_code','PHP code',0,1,11);
INSERT INTO `drupal_filter_format` VALUES ('plain_text','Plain text',1,1,10);
/*!40000 ALTER TABLE `drupal_filter_format` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_flood`
--

DROP TABLE IF EXISTS `drupal_flood`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_flood` (
  `fid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique flood event ID.',
  `event` varchar(64) NOT NULL DEFAULT '' COMMENT 'Name of event (e.g. contact).',
  `identifier` varchar(128) NOT NULL DEFAULT '' COMMENT 'Identifier of the visitor, such as an IP address or hostname.',
  `timestamp` int(11) NOT NULL DEFAULT '0' COMMENT 'Timestamp of the event.',
  `expiration` int(11) NOT NULL DEFAULT '0' COMMENT 'Expiration timestamp. Expired events are purged on cron run.',
  PRIMARY KEY (`fid`),
  KEY `allow` (`event`,`identifier`,`timestamp`),
  KEY `purge` (`expiration`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='Flood controls the threshold of events, such as the...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_flood`
--

LOCK TABLES `drupal_flood` WRITE;
/*!40000 ALTER TABLE `drupal_flood` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_flood` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_forum`
--

DROP TABLE IF EXISTS `drupal_forum`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_forum` (
  `nid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The drupal_node.nid of the node.',
  `vid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Primary Key: The drupal_node.vid of the node.',
  `tid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The drupal_taxonomy_term_data.tid of the forum term assigned to the node.',
  PRIMARY KEY (`vid`),
  KEY `forum_topic` (`nid`,`tid`),
  KEY `tid` (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores the relationship of nodes to forum terms.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_forum`
--

LOCK TABLES `drupal_forum` WRITE;
/*!40000 ALTER TABLE `drupal_forum` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_forum` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_forum_index`
--

DROP TABLE IF EXISTS `drupal_forum_index`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_forum_index` (
  `nid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The drupal_node.nid this record tracks.',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT 'The title of this node, always treated as non-markup plain text.',
  `tid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The term ID.',
  `sticky` tinyint(4) DEFAULT '0' COMMENT 'Boolean indicating whether the node is sticky.',
  `created` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The Unix timestamp when the node was created.',
  `last_comment_timestamp` int(11) NOT NULL DEFAULT '0' COMMENT 'The Unix timestamp of the last comment that was posted within this node, from drupal_comment.timestamp.',
  `comment_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The total number of comments on this node.',
  KEY `forum_topics` (`tid`,`sticky`,`last_comment_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Maintains denormalized information about node/term...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_forum_index`
--

LOCK TABLES `drupal_forum_index` WRITE;
/*!40000 ALTER TABLE `drupal_forum_index` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_forum_index` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_history`
--

DROP TABLE IF EXISTS `drupal_history`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_history` (
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT 'The drupal_users.uid that read the drupal_node nid.',
  `nid` int(11) NOT NULL DEFAULT '0' COMMENT 'The drupal_node.nid that was read.',
  `timestamp` int(11) NOT NULL DEFAULT '0' COMMENT 'The Unix timestamp at which the read occurred.',
  PRIMARY KEY (`uid`,`nid`),
  KEY `nid` (`nid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='A record of which drupal_users have read which drupal_nodes.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_history`
--

LOCK TABLES `drupal_history` WRITE;
/*!40000 ALTER TABLE `drupal_history` DISABLE KEYS */;
INSERT INTO `drupal_history` VALUES (1,1,1294958238);
INSERT INTO `drupal_history` VALUES (1,2,1293058970);
INSERT INTO `drupal_history` VALUES (1,3,1293063168);
INSERT INTO `drupal_history` VALUES (1,5,1293061797);
INSERT INTO `drupal_history` VALUES (1,6,1293063103);
/*!40000 ALTER TABLE `drupal_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_image_effects`
--

DROP TABLE IF EXISTS `drupal_image_effects`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_image_effects` (
  `ieid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'The primary identifier for an image effect.',
  `isid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The drupal_image_styles.isid for an image style.',
  `weight` int(11) NOT NULL DEFAULT '0' COMMENT 'The weight of the effect in the style.',
  `name` varchar(255) NOT NULL COMMENT 'The unique name of the effect to be executed.',
  `data` longblob NOT NULL COMMENT 'The configuration data for the effect.',
  PRIMARY KEY (`ieid`),
  KEY `isid` (`isid`),
  KEY `weight` (`weight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores configuration options for image effects.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_image_effects`
--

LOCK TABLES `drupal_image_effects` WRITE;
/*!40000 ALTER TABLE `drupal_image_effects` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_image_effects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_image_styles`
--

DROP TABLE IF EXISTS `drupal_image_styles`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_image_styles` (
  `isid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'The primary identifier for an image style.',
  `name` varchar(255) NOT NULL COMMENT 'The style name.',
  PRIMARY KEY (`isid`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores configuration options for image styles.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_image_styles`
--

LOCK TABLES `drupal_image_styles` WRITE;
/*!40000 ALTER TABLE `drupal_image_styles` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_image_styles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_menu_custom`
--

DROP TABLE IF EXISTS `drupal_menu_custom`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_menu_custom` (
  `menu_name` varchar(32) NOT NULL DEFAULT '' COMMENT 'Primary Key: Unique key for menu. This is used as a block delta so length is 32.',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT 'Menu title; displayed at top of block.',
  `description` text COMMENT 'Menu description.',
  PRIMARY KEY (`menu_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Holds definitions for top-level custom menus (for example...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_menu_custom`
--

LOCK TABLES `drupal_menu_custom` WRITE;
/*!40000 ALTER TABLE `drupal_menu_custom` DISABLE KEYS */;
INSERT INTO `drupal_menu_custom` VALUES ('main-menu','Main menu','The <em>Main</em> menu is used on many sites to show the major sections of the site, often in a top navigation bar.');
INSERT INTO `drupal_menu_custom` VALUES ('management','Management','The <em>Management</em> menu contains links for administrative tasks.');
INSERT INTO `drupal_menu_custom` VALUES ('navigation','Navigation','The <em>Navigation</em> menu contains links intended for site visitors. Links are added to the <em>Navigation</em> menu automatically by some modules.');
INSERT INTO `drupal_menu_custom` VALUES ('user-menu','User menu','The <em>User</em> menu contains links related to the user\'s account, as well as the \'Log out\' link.');
/*!40000 ALTER TABLE `drupal_menu_custom` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_menu_links`
--

DROP TABLE IF EXISTS `drupal_menu_links`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_menu_links` (
  `menu_name` varchar(32) NOT NULL DEFAULT '' COMMENT 'The menu name. All links with the same menu name (such as ’navigation’) are part of the same menu.',
  `mlid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'The menu link ID (mlid) is the integer primary key.',
  `plid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The parent link ID (plid) is the mlid of the link above in the hierarchy, or zero if the link is at the top level in its menu.',
  `link_path` varchar(255) NOT NULL DEFAULT '' COMMENT 'The Drupal path or external path this link points to.',
  `router_path` varchar(255) NOT NULL DEFAULT '' COMMENT 'For links corresponding to a Drupal path (external = 0), this connects the link to a drupal_menu_router.path for joins.',
  `link_title` varchar(255) NOT NULL DEFAULT '' COMMENT 'The text displayed for the link, which may be modified by a title callback stored in drupal_menu_router.',
  `options` blob COMMENT 'A serialized array of options to be passed to the url() or l() function, such as a query string or HTML attributes.',
  `module` varchar(255) NOT NULL DEFAULT 'system' COMMENT 'The name of the module that generated this link.',
  `hidden` smallint(6) NOT NULL DEFAULT '0' COMMENT 'A flag for whether the link should be rendered in menus. (1 = a disabled menu item that may be shown on admin screens, -1 = a menu callback, 0 = a normal, visible link)',
  `external` smallint(6) NOT NULL DEFAULT '0' COMMENT 'A flag to indicate if the link points to a full URL starting with a protocol, like http:// (1 = external, 0 = internal).',
  `has_children` smallint(6) NOT NULL DEFAULT '0' COMMENT 'Flag indicating whether any links have this link as a parent (1 = children exist, 0 = no children).',
  `expanded` smallint(6) NOT NULL DEFAULT '0' COMMENT 'Flag for whether this link should be rendered as expanded in menus - expanded links always have their child links displayed, instead of only when the link is in the active trail (1 = expanded, 0 = not expanded)',
  `weight` int(11) NOT NULL DEFAULT '0' COMMENT 'Link weight among links in the same menu at the same depth.',
  `depth` smallint(6) NOT NULL DEFAULT '0' COMMENT 'The depth relative to the top level. A link with plid == 0 will have depth == 1.',
  `customized` smallint(6) NOT NULL DEFAULT '0' COMMENT 'A flag to indicate that the user has manually created or edited the link (1 = customized, 0 = not customized).',
  `p1` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The first mlid in the materialized path. If N = depth, then pN must equal the mlid. If depth > 1 then p(N-1) must equal the plid. All pX where X > depth must equal zero. The columns p1 .. p9 are also called the parents.',
  `p2` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The second mlid in the materialized path. See p1.',
  `p3` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The third mlid in the materialized path. See p1.',
  `p4` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The fourth mlid in the materialized path. See p1.',
  `p5` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The fifth mlid in the materialized path. See p1.',
  `p6` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The sixth mlid in the materialized path. See p1.',
  `p7` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The seventh mlid in the materialized path. See p1.',
  `p8` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The eighth mlid in the materialized path. See p1.',
  `p9` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The ninth mlid in the materialized path. See p1.',
  `updated` smallint(6) NOT NULL DEFAULT '0' COMMENT 'Flag that indicates that this link was generated during the update from Drupal 5.',
  PRIMARY KEY (`mlid`),
  KEY `path_menu` (`link_path`(128),`menu_name`),
  KEY `menu_plid_expand_child` (`menu_name`,`plid`,`expanded`,`has_children`),
  KEY `menu_parents` (`menu_name`,`p1`,`p2`,`p3`,`p4`,`p5`,`p6`,`p7`,`p8`,`p9`),
  KEY `router_path` (`router_path`(128))
) ENGINE=InnoDB AUTO_INCREMENT=606 DEFAULT CHARSET=utf8 COMMENT='Contains the individual links within a menu.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_menu_links`
--

LOCK TABLES `drupal_menu_links` WRITE;
/*!40000 ALTER TABLE `drupal_menu_links` DISABLE KEYS */;
INSERT INTO `drupal_menu_links` VALUES ('management',1,0,'admin','admin','Administration','a:0:{}','system',0,0,1,0,9,1,0,1,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('user-menu',2,0,'user','user','User account','a:1:{s:5:\"alter\";b:1;}','system',0,0,0,0,-50,1,1,2,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',3,0,'comment/%','comment/%','Comment permalink','a:0:{}','system',0,0,1,0,0,1,0,3,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',4,0,'filter/tips','filter/tips','Compose tips','a:0:{}','system',1,0,0,0,0,1,0,4,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',5,0,'node/%','node/%','','a:0:{}','system',0,0,0,0,0,1,0,5,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',6,0,'node/add','node/add','Add content','a:0:{}','system',1,0,0,0,0,1,1,6,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',7,1,'admin/appearance','admin/appearance','Appearance','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:33:\"Select and configure your themes.\";}}','system',0,0,0,0,-6,2,0,1,7,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',8,1,'admin/config','admin/config','Configuration','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:20:\"Administer settings.\";}}','system',0,0,1,0,0,2,0,1,8,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',9,1,'admin/content','admin/content','Content','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:32:\"Administer content and comments.\";}}','system',0,0,1,0,-10,2,0,1,9,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('user-menu',10,2,'user/register','user/register','Create new account','a:0:{}','system',-1,0,0,0,0,2,0,2,10,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',11,1,'admin/dashboard','admin/dashboard','Dashboard','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:34:\"View and customize your dashboard.\";}}','system',0,0,0,0,-15,2,0,1,11,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',12,1,'admin/help','admin/help','Help','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:48:\"Reference for usage, configuration, and modules.\";}}','system',0,0,0,0,9,2,0,1,12,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',13,1,'admin/index','admin/index','Index','a:0:{}','system',-1,0,0,0,-18,2,0,1,13,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('user-menu',14,2,'user/login','user/login','Log in','a:0:{}','system',-1,0,0,0,0,2,0,2,14,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('user-menu',15,0,'user/logout','user/logout','Log out','a:0:{}','system',0,0,0,0,-48,1,1,15,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',16,1,'admin/modules','admin/modules','Modules','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:26:\"Enable or disable modules.\";}}','system',0,0,1,0,-2,2,0,1,16,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',17,0,'user/%','user/%','My account','a:0:{}','system',0,0,1,0,0,1,0,17,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',18,1,'admin/people','admin/people','People','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:45:\"Manage user accounts, roles, and permissions.\";}}','system',0,0,0,0,-4,2,0,1,18,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',19,1,'admin/reports','admin/reports','Reports','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:34:\"View reports, updates, and errors.\";}}','system',0,0,1,0,5,2,0,1,19,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('user-menu',20,2,'user/password','user/password','Request new password','a:0:{}','system',-1,0,0,0,0,2,0,2,20,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',21,1,'admin/structure','admin/structure','Structure','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:45:\"Administer blocks, content types, menus, etc.\";}}','system',0,0,1,0,-8,2,0,1,21,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',22,1,'admin/tasks','admin/tasks','Tasks','a:0:{}','system',-1,0,0,0,-20,2,0,1,22,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',23,0,'comment/reply/%','comment/reply/%','Add new comment','a:0:{}','system',0,0,0,0,0,1,0,23,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',24,3,'comment/%/approve','comment/%/approve','Approve','a:0:{}','system',0,0,0,0,1,2,0,3,24,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',25,3,'comment/%/delete','comment/%/delete','Delete','a:0:{}','system',-1,0,0,0,2,2,0,3,25,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',26,3,'comment/%/edit','comment/%/edit','Edit','a:0:{}','system',-1,0,0,0,0,2,0,3,26,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',27,0,'taxonomy/term/%','taxonomy/term/%','Taxonomy term','a:0:{}','system',0,0,0,0,0,1,0,27,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',28,3,'comment/%/view','comment/%/view','View comment','a:0:{}','system',-1,0,0,0,-10,2,0,3,28,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',29,18,'admin/people/create','admin/people/create','Add user','a:0:{}','system',-1,0,0,0,0,3,0,1,18,29,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',30,21,'admin/structure/block','admin/structure/block','Blocks','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:79:\"Configure what block content appears in your site\'s sidebars and other regions.\";}}','system',0,0,1,0,0,3,0,1,21,30,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',31,17,'user/%/cancel','user/%/cancel','Cancel account','a:0:{}','system',0,0,1,0,0,2,0,17,31,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',32,9,'admin/content/comment','admin/content/comment','Comments','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:59:\"List and edit site comments and the comment approval queue.\";}}','system',0,0,0,0,0,3,0,1,9,32,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',33,11,'admin/dashboard/configure','admin/dashboard/configure','Configure available dashboard blocks','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:53:\"Configure which blocks can be shown on the dashboard.\";}}','system',-1,0,0,0,0,3,0,1,11,33,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',34,9,'admin/content/node','admin/content/node','Content','a:0:{}','system',-1,0,0,0,-10,3,0,1,9,34,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',35,8,'admin/config/content','admin/config/content','Content authoring','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:53:\"Settings related to formatting and authoring content.\";}}','system',0,0,1,0,-15,3,0,1,8,35,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',36,21,'admin/structure/types','admin/structure/types','Content types','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:92:\"Manage content types, including default status, front page promotion, comment settings, etc.\";}}','system',0,0,1,0,0,3,0,1,21,36,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',37,11,'admin/dashboard/customize','admin/dashboard/customize','Customize dashboard','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:25:\"Customize your dashboard.\";}}','system',-1,0,0,0,0,3,0,1,11,37,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',38,5,'node/%/delete','node/%/delete','Delete','a:0:{}','system',-1,0,0,0,1,2,0,5,38,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',39,8,'admin/config/development','admin/config/development','Development','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:18:\"Development tools.\";}}','system',0,0,1,0,-10,3,0,1,8,39,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',40,17,'user/%/edit','user/%/edit','Edit','a:0:{}','system',-1,0,0,0,0,2,0,17,40,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',41,5,'node/%/edit','node/%/edit','Edit','a:0:{}','system',-1,0,0,0,0,2,0,5,41,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',42,19,'admin/reports/fields','admin/reports/fields','Field list','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:39:\"Overview of fields on all entity types.\";}}','system',0,0,0,0,0,3,0,1,19,42,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',43,7,'admin/appearance/list','admin/appearance/list','List','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:31:\"Select and configure your theme\";}}','system',-1,0,0,0,-1,3,0,1,7,43,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',44,16,'admin/modules/list','admin/modules/list','List','a:0:{}','system',-1,0,0,0,0,3,0,1,16,44,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',45,18,'admin/people/people','admin/people/people','List','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:50:\"Find and manage people interacting with your site.\";}}','system',-1,0,0,0,-10,3,0,1,18,45,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',46,8,'admin/config/media','admin/config/media','Media','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:12:\"Media tools.\";}}','system',0,0,1,0,-10,3,0,1,8,46,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',47,21,'admin/structure/menu','admin/structure/menu','Menus','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:86:\"Add new menus to your site, edit existing menus, and rename and reorganize menu links.\";}}','system',0,0,1,0,0,3,0,1,21,47,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',48,8,'admin/config/people','admin/config/people','People','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:24:\"Configure user accounts.\";}}','system',0,0,1,0,-20,3,0,1,8,48,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',49,18,'admin/people/permissions','admin/people/permissions','Permissions','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:64:\"Determine access to features by selecting permissions for roles.\";}}','system',-1,0,0,0,0,3,0,1,18,49,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',50,19,'admin/reports/dblog','admin/reports/dblog','Recent log messages','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:43:\"View events that have recently been logged.\";}}','system',0,0,0,0,-1,3,0,1,19,50,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',51,8,'admin/config/regional','admin/config/regional','Regional and language','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:48:\"Regional settings, localization and translation.\";}}','system',0,0,1,0,-5,3,0,1,8,51,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',52,5,'node/%/revisions','node/%/revisions','Revisions','a:0:{}','system',-1,0,1,0,2,2,0,5,52,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',53,8,'admin/config/search','admin/config/search','Search and metadata','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:36:\"Local site search, metadata and SEO.\";}}','system',0,0,1,0,-10,3,0,1,8,53,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',54,7,'admin/appearance/settings','admin/appearance/settings','Settings','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:46:\"Configure default and theme specific settings.\";}}','system',-1,0,0,0,20,3,0,1,7,54,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',55,19,'admin/reports/status','admin/reports/status','Status report','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:74:\"Get a status report about your site\'s operation and any detected problems.\";}}','system',0,0,0,0,-60,3,0,1,19,55,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',56,8,'admin/config/system','admin/config/system','System','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:37:\"General system related configuration.\";}}','system',0,0,1,0,-20,3,0,1,8,56,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',57,21,'admin/structure/taxonomy','admin/structure/taxonomy','Taxonomy','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:67:\"Manage tagging, categorization, and classification of your content.\";}}','system',0,0,1,0,0,3,0,1,21,57,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',58,19,'admin/reports/access-denied','admin/reports/access-denied','Top \'access denied\' errors','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:35:\"View \'access denied\' errors (403s).\";}}','system',0,0,0,0,0,3,0,1,19,58,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',59,19,'admin/reports/page-not-found','admin/reports/page-not-found','Top \'page not found\' errors','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:36:\"View \'page not found\' errors (404s).\";}}','system',0,0,0,0,0,3,0,1,19,59,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',60,16,'admin/modules/uninstall','admin/modules/uninstall','Uninstall','a:0:{}','system',-1,0,0,0,20,3,0,1,16,60,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',61,8,'admin/config/user-interface','admin/config/user-interface','User interface','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:38:\"Tools that enhance the user interface.\";}}','system',0,0,0,0,-15,3,0,1,8,61,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',62,5,'node/%/view','node/%/view','View','a:0:{}','system',-1,0,0,0,-10,2,0,5,62,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',63,17,'user/%/view','user/%/view','View','a:0:{}','system',-1,0,0,0,-10,2,0,17,63,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',64,8,'admin/config/services','admin/config/services','Web services','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:30:\"Tools related to web services.\";}}','system',0,0,1,0,0,3,0,1,8,64,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',65,8,'admin/config/workflow','admin/config/workflow','Workflow','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:43:\"Content workflow, editorial workflow tools.\";}}','system',0,0,0,0,5,3,0,1,8,65,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',66,12,'admin/help/block','admin/help/block','block','a:0:{}','system',-1,0,0,0,0,3,0,1,12,66,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',67,12,'admin/help/color','admin/help/color','color','a:0:{}','system',-1,0,0,0,0,3,0,1,12,67,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',68,12,'admin/help/comment','admin/help/comment','comment','a:0:{}','system',-1,0,0,0,0,3,0,1,12,68,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',69,12,'admin/help/contextual','admin/help/contextual','contextual','a:0:{}','system',-1,0,0,0,0,3,0,1,12,69,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',70,12,'admin/help/dashboard','admin/help/dashboard','dashboard','a:0:{}','system',-1,0,0,0,0,3,0,1,12,70,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',71,12,'admin/help/dblog','admin/help/dblog','dblog','a:0:{}','system',-1,0,0,0,0,3,0,1,12,71,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',72,12,'admin/help/field','admin/help/field','field','a:0:{}','system',-1,0,0,0,0,3,0,1,12,72,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',73,12,'admin/help/field_sql_storage','admin/help/field_sql_storage','field_sql_storage','a:0:{}','system',-1,0,0,0,0,3,0,1,12,73,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',74,12,'admin/help/field_ui','admin/help/field_ui','field_ui','a:0:{}','system',-1,0,0,0,0,3,0,1,12,74,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',75,12,'admin/help/file','admin/help/file','file','a:0:{}','system',-1,0,0,0,0,3,0,1,12,75,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',76,12,'admin/help/filter','admin/help/filter','filter','a:0:{}','system',-1,0,0,0,0,3,0,1,12,76,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',77,12,'admin/help/help','admin/help/help','help','a:0:{}','system',-1,0,0,0,0,3,0,1,12,77,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',78,12,'admin/help/image','admin/help/image','image','a:0:{}','system',-1,0,0,0,0,3,0,1,12,78,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',79,12,'admin/help/list','admin/help/list','list','a:0:{}','system',-1,0,0,0,0,3,0,1,12,79,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',80,12,'admin/help/menu','admin/help/menu','menu','a:0:{}','system',-1,0,0,0,0,3,0,1,12,80,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',81,12,'admin/help/node','admin/help/node','node','a:0:{}','system',-1,0,0,0,0,3,0,1,12,81,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',82,12,'admin/help/options','admin/help/options','options','a:0:{}','system',-1,0,0,0,0,3,0,1,12,82,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',83,12,'admin/help/system','admin/help/system','system','a:0:{}','system',-1,0,0,0,0,3,0,1,12,83,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',84,12,'admin/help/taxonomy','admin/help/taxonomy','taxonomy','a:0:{}','system',-1,0,0,0,0,3,0,1,12,84,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',85,12,'admin/help/text','admin/help/text','text','a:0:{}','system',-1,0,0,0,0,3,0,1,12,85,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',86,12,'admin/help/user','admin/help/user','user','a:0:{}','system',-1,0,0,0,0,3,0,1,12,86,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',87,27,'taxonomy/term/%/edit','taxonomy/term/%/edit','Edit','a:0:{}','system',-1,0,0,0,10,2,0,27,87,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',88,27,'taxonomy/term/%/view','taxonomy/term/%/view','View','a:0:{}','system',-1,0,0,0,0,2,0,27,88,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',89,48,'admin/config/people/accounts','admin/config/people/accounts','Account settings','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:109:\"Configure default behavior of users, including registration requirements, e-mails, fields, and user pictures.\";}}','system',0,0,0,0,-10,4,0,1,8,48,89,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',90,56,'admin/config/system/actions','admin/config/system/actions','Actions','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:41:\"Manage the actions defined for your site.\";}}','system',0,0,1,0,0,4,0,1,8,56,90,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',91,30,'admin/structure/block/add','admin/structure/block/add','Add block','a:0:{}','system',-1,0,0,0,0,4,0,1,21,30,91,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',92,36,'admin/structure/types/add','admin/structure/types/add','Add content type','a:0:{}','system',-1,0,0,0,0,4,0,1,21,36,92,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',93,47,'admin/structure/menu/add','admin/structure/menu/add','Add menu','a:0:{}','system',-1,0,0,0,0,4,0,1,21,47,93,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',94,57,'admin/structure/taxonomy/add','admin/structure/taxonomy/add','Add vocabulary','a:0:{}','system',-1,0,0,0,0,4,0,1,21,57,94,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',95,54,'admin/appearance/settings/bartik','admin/appearance/settings/bartik','Bartik','a:0:{}','system',-1,0,0,0,0,4,0,1,7,54,95,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',96,53,'admin/config/search/clean-urls','admin/config/search/clean-urls','Clean URLs','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:43:\"Enable or disable clean URLs for your site.\";}}','system',0,0,0,0,5,4,0,1,8,53,96,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',97,56,'admin/config/system/cron','admin/config/system/cron','Cron','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:40:\"Manage automatic site maintenance tasks.\";}}','system',0,0,0,0,20,4,0,1,8,56,97,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',98,51,'admin/config/regional/date-time','admin/config/regional/date-time','Date and time','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:44:\"Configure display formats for date and time.\";}}','system',0,0,0,0,-15,4,0,1,8,51,98,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',99,19,'admin/reports/event/%','admin/reports/event/%','Details','a:0:{}','system',0,0,0,0,0,3,0,1,19,99,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',100,46,'admin/config/media/file-system','admin/config/media/file-system','File system','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:68:\"Tell Drupal where to store uploaded files and how they are accessed.\";}}','system',0,0,0,0,-10,4,0,1,8,46,100,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',101,54,'admin/appearance/settings/garland','admin/appearance/settings/garland','Garland','a:0:{}','system',-1,0,0,0,0,4,0,1,7,54,101,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',102,54,'admin/appearance/settings/global','admin/appearance/settings/global','Global settings','a:0:{}','system',-1,0,0,0,-1,4,0,1,7,54,102,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',103,48,'admin/config/people/ip-blocking','admin/config/people/ip-blocking','IP address blocking','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:28:\"Manage blocked IP addresses.\";}}','system',0,0,1,0,10,4,0,1,8,48,103,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',104,46,'admin/config/media/image-styles','admin/config/media/image-styles','Image styles','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:78:\"Configure styles that can be used for resizing or adjusting images on display.\";}}','system',0,0,1,0,0,4,0,1,8,46,104,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',105,46,'admin/config/media/image-toolkit','admin/config/media/image-toolkit','Image toolkit','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:74:\"Choose which image toolkit to use if you have installed optional toolkits.\";}}','system',0,0,0,0,20,4,0,1,8,46,105,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',106,44,'admin/modules/list/confirm','admin/modules/list/confirm','List','a:0:{}','system',-1,0,0,0,0,4,0,1,16,44,106,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',107,36,'admin/structure/types/list','admin/structure/types/list','List','a:0:{}','system',-1,0,0,0,-10,4,0,1,21,36,107,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',108,57,'admin/structure/taxonomy/list','admin/structure/taxonomy/list','List','a:0:{}','system',-1,0,0,0,-10,4,0,1,21,57,108,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',109,47,'admin/structure/menu/list','admin/structure/menu/list','List menus','a:0:{}','system',-1,0,0,0,-10,4,0,1,21,47,109,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',110,39,'admin/config/development/logging','admin/config/development/logging','Logging and errors','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:154:\"Settings for logging and alerts modules. Various modules can route Drupal\'s system events to different destinations, such as syslog, database, email, etc.\";}}','system',0,0,0,0,-15,4,0,1,8,39,110,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',111,39,'admin/config/development/maintenance','admin/config/development/maintenance','Maintenance mode','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:62:\"Take the site offline for maintenance or bring it back online.\";}}','system',0,0,0,0,-10,4,0,1,8,39,111,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',112,39,'admin/config/development/performance','admin/config/development/performance','Performance','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:101:\"Enable or disable page caching for anonymous users and set CSS and JS bandwidth optimization options.\";}}','system',0,0,0,0,-20,4,0,1,8,39,112,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',113,49,'admin/people/permissions/list','admin/people/permissions/list','Permissions','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:64:\"Determine access to features by selecting permissions for roles.\";}}','system',-1,0,0,0,-8,4,0,1,18,49,113,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',114,32,'admin/content/comment/new','admin/content/comment/new','Published comments','a:0:{}','system',-1,0,0,0,-10,4,0,1,9,32,114,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',115,64,'admin/config/services/rss-publishing','admin/config/services/rss-publishing','RSS publishing','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:114:\"Configure the site description, the number of items per feed and whether feeds should be titles/teasers/full-text.\";}}','system',0,0,0,0,0,4,0,1,8,64,115,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',116,51,'admin/config/regional/settings','admin/config/regional/settings','Regional settings','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:54:\"Settings for the site\'s default time zone and country.\";}}','system',0,0,0,0,-20,4,0,1,8,51,116,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',117,49,'admin/people/permissions/roles','admin/people/permissions/roles','Roles','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:30:\"List, edit, or add user roles.\";}}','system',-1,0,1,0,-5,4,0,1,18,49,117,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',118,47,'admin/structure/menu/settings','admin/structure/menu/settings','Settings','a:0:{}','system',-1,0,0,0,5,4,0,1,21,47,118,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',119,54,'admin/appearance/settings/seven','admin/appearance/settings/seven','Seven','a:0:{}','system',-1,0,0,0,0,4,0,1,7,54,119,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',120,56,'admin/config/system/site-information','admin/config/system/site-information','Site information','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:104:\"Change site name, e-mail address, slogan, default front page, and number of posts per page, error pages.\";}}','system',0,0,0,0,-20,4,0,1,8,56,120,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',121,54,'admin/appearance/settings/stark','admin/appearance/settings/stark','Stark','a:0:{}','system',-1,0,0,0,0,4,0,1,7,54,121,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',122,54,'admin/appearance/settings/test_theme','admin/appearance/settings/test_theme','Test theme','a:0:{}','system',-1,0,0,0,0,4,0,1,7,54,122,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',123,35,'admin/config/content/formats','admin/config/content/formats','Text formats','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:127:\"Configure how content input by users is filtered, including allowed HTML tags. Also allows enabling of module-provided filters.\";}}','system',0,0,1,0,0,4,0,1,8,35,123,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',124,32,'admin/content/comment/approval','admin/content/comment/approval','Unapproved comments','a:0:{}','system',-1,0,0,0,0,4,0,1,9,32,124,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',125,60,'admin/modules/uninstall/confirm','admin/modules/uninstall/confirm','Uninstall','a:0:{}','system',-1,0,0,0,0,4,0,1,16,60,125,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',126,54,'admin/appearance/settings/update_test_basetheme','admin/appearance/settings/update_test_basetheme','Update test base theme','a:0:{}','system',-1,0,0,0,0,4,0,1,7,54,126,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',127,57,'admin/structure/taxonomy/%','admin/structure/taxonomy/%','','a:0:{}','system',0,0,0,0,0,4,0,1,21,57,127,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',128,54,'admin/appearance/settings/update_test_subtheme','admin/appearance/settings/update_test_subtheme','Update test subtheme','a:0:{}','system',-1,0,0,0,0,4,0,1,7,54,128,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',129,40,'user/%/edit/account','user/%/edit/account','Account','a:0:{}','system',-1,0,0,0,0,3,0,17,40,129,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',130,123,'admin/config/content/formats/%','admin/config/content/formats/%','','a:0:{}','system',0,0,1,0,0,5,0,1,8,35,123,130,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',131,104,'admin/config/media/image-styles/add','admin/config/media/image-styles/add','Add style','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:22:\"Add a new image style.\";}}','system',-1,0,0,0,2,5,0,1,8,46,104,131,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',132,127,'admin/structure/taxonomy/%/add','admin/structure/taxonomy/%/add','Add term','a:0:{}','system',-1,0,0,0,0,5,0,1,21,57,127,132,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',133,123,'admin/config/content/formats/add','admin/config/content/formats/add','Add text format','a:0:{}','system',-1,0,0,0,1,5,0,1,8,35,123,133,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',134,30,'admin/structure/block/list/bartik','admin/structure/block/list/bartik','Bartik','a:0:{}','system',-1,0,0,0,0,4,0,1,21,30,134,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',135,90,'admin/config/system/actions/configure','admin/config/system/actions/configure','Configure an advanced action','a:0:{}','system',-1,0,0,0,0,5,0,1,8,56,90,135,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',136,47,'admin/structure/menu/manage/%','admin/structure/menu/manage/%','Customize menu','a:0:{}','system',0,0,1,0,0,4,0,1,21,47,136,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',137,127,'admin/structure/taxonomy/%/edit','admin/structure/taxonomy/%/edit','Edit','a:0:{}','system',-1,0,0,0,-10,5,0,1,21,57,127,137,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',138,36,'admin/structure/types/manage/%','admin/structure/types/manage/%','Edit content type','a:0:{}','system',0,0,1,0,0,4,0,1,21,36,138,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',139,98,'admin/config/regional/date-time/formats','admin/config/regional/date-time/formats','Formats','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:51:\"Configure display format strings for date and time.\";}}','system',-1,0,1,0,-9,5,0,1,8,51,98,139,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',140,30,'admin/structure/block/list/garland','admin/structure/block/list/garland','Garland','a:0:{}','system',-1,0,0,0,0,4,0,1,21,30,140,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',141,123,'admin/config/content/formats/list','admin/config/content/formats/list','List','a:0:{}','system',-1,0,0,0,0,5,0,1,8,35,123,141,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',142,127,'admin/structure/taxonomy/%/list','admin/structure/taxonomy/%/list','List','a:0:{}','system',-1,0,0,0,-20,5,0,1,21,57,127,142,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',143,104,'admin/config/media/image-styles/list','admin/config/media/image-styles/list','List','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:42:\"List the current image styles on the site.\";}}','system',-1,0,0,0,1,5,0,1,8,46,104,143,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',144,90,'admin/config/system/actions/manage','admin/config/system/actions/manage','Manage actions','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:41:\"Manage the actions defined for your site.\";}}','system',-1,0,0,0,-2,5,0,1,8,56,90,144,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',145,89,'admin/config/people/accounts/settings','admin/config/people/accounts/settings','Settings','a:0:{}','system',-1,0,0,0,-10,5,0,1,8,48,89,145,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',146,30,'admin/structure/block/list/seven','admin/structure/block/list/seven','Seven','a:0:{}','system',-1,0,0,0,0,4,0,1,21,30,146,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',147,30,'admin/structure/block/list/stark','admin/structure/block/list/stark','Stark','a:0:{}','system',-1,0,0,0,0,4,0,1,21,30,147,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',148,30,'admin/structure/block/list/test_theme','admin/structure/block/list/test_theme','Test theme','a:0:{}','system',-1,0,0,0,0,4,0,1,21,30,148,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',149,98,'admin/config/regional/date-time/types','admin/config/regional/date-time/types','Types','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:44:\"Configure display formats for date and time.\";}}','system',-1,0,1,0,-10,5,0,1,8,51,98,149,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',150,30,'admin/structure/block/list/update_test_basetheme','admin/structure/block/list/update_test_basetheme','Update test base theme','a:0:{}','system',-1,0,0,0,0,4,0,1,21,30,150,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',151,30,'admin/structure/block/list/update_test_subtheme','admin/structure/block/list/update_test_subtheme','Update test subtheme','a:0:{}','system',-1,0,0,0,0,4,0,1,21,30,151,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',152,52,'node/%/revisions/%/delete','node/%/revisions/%/delete','Delete earlier revision','a:0:{}','system',0,0,0,0,0,3,0,5,52,152,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',153,52,'node/%/revisions/%/revert','node/%/revisions/%/revert','Revert to earlier revision','a:0:{}','system',0,0,0,0,0,3,0,5,52,153,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',154,52,'node/%/revisions/%/view','node/%/revisions/%/view','Revisions','a:0:{}','system',0,0,0,0,0,3,0,5,52,154,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',155,140,'admin/structure/block/list/garland/add','admin/structure/block/list/garland/add','Add block','a:0:{}','system',-1,0,0,0,0,5,0,1,21,30,140,155,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',156,146,'admin/structure/block/list/seven/add','admin/structure/block/list/seven/add','Add block','a:0:{}','system',-1,0,0,0,0,5,0,1,21,30,146,156,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',157,147,'admin/structure/block/list/stark/add','admin/structure/block/list/stark/add','Add block','a:0:{}','system',-1,0,0,0,0,5,0,1,21,30,147,157,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',158,148,'admin/structure/block/list/test_theme/add','admin/structure/block/list/test_theme/add','Add block','a:0:{}','system',-1,0,0,0,0,5,0,1,21,30,148,158,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',159,150,'admin/structure/block/list/update_test_basetheme/add','admin/structure/block/list/update_test_basetheme/add','Add block','a:0:{}','system',-1,0,0,0,0,5,0,1,21,30,150,159,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',160,151,'admin/structure/block/list/update_test_subtheme/add','admin/structure/block/list/update_test_subtheme/add','Add block','a:0:{}','system',-1,0,0,0,0,5,0,1,21,30,151,160,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',161,149,'admin/config/regional/date-time/types/add','admin/config/regional/date-time/types/add','Add date type','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:18:\"Add new date type.\";}}','system',-1,0,0,0,-10,6,0,1,8,51,98,149,161,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',162,139,'admin/config/regional/date-time/formats/add','admin/config/regional/date-time/formats/add','Add format','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:43:\"Allow users to add additional date formats.\";}}','system',-1,0,0,0,-10,6,0,1,8,51,98,139,162,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',163,136,'admin/structure/menu/manage/%/add','admin/structure/menu/manage/%/add','Add link','a:0:{}','system',-1,0,0,0,0,5,0,1,21,47,136,163,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',164,30,'admin/structure/block/manage/%/%','admin/structure/block/manage/%/%','Configure block','a:0:{}','system',0,0,0,0,0,4,0,1,21,30,164,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',165,31,'user/%/cancel/confirm/%/%','user/%/cancel/confirm/%/%','Confirm account cancellation','a:0:{}','system',0,0,0,0,0,3,0,17,31,165,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',166,138,'admin/structure/types/manage/%/delete','admin/structure/types/manage/%/delete','Delete','a:0:{}','system',0,0,0,0,0,5,0,1,21,36,138,166,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',167,103,'admin/config/people/ip-blocking/delete/%','admin/config/people/ip-blocking/delete/%','Delete IP address','a:0:{}','system',0,0,0,0,0,5,0,1,8,48,103,167,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',168,90,'admin/config/system/actions/delete/%','admin/config/system/actions/delete/%','Delete action','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:17:\"Delete an action.\";}}','system',0,0,0,0,0,5,0,1,8,56,90,168,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',169,136,'admin/structure/menu/manage/%/delete','admin/structure/menu/manage/%/delete','Delete menu','a:0:{}','system',0,0,0,0,0,5,0,1,21,47,136,169,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',170,47,'admin/structure/menu/item/%/delete','admin/structure/menu/item/%/delete','Delete menu link','a:0:{}','system',0,0,0,0,0,4,0,1,21,47,170,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',171,117,'admin/people/permissions/roles/delete/%','admin/people/permissions/roles/delete/%','Delete role','a:0:{}','system',0,0,0,0,0,5,0,1,18,49,117,171,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',172,130,'admin/config/content/formats/%/disable','admin/config/content/formats/%/disable','Disable text format','a:0:{}','system',0,0,0,0,0,6,0,1,8,35,123,130,172,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',173,138,'admin/structure/types/manage/%/edit','admin/structure/types/manage/%/edit','Edit','a:0:{}','system',-1,0,0,0,0,5,0,1,21,36,138,173,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',174,136,'admin/structure/menu/manage/%/edit','admin/structure/menu/manage/%/edit','Edit menu','a:0:{}','system',-1,0,0,0,0,5,0,1,21,47,136,174,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',175,47,'admin/structure/menu/item/%/edit','admin/structure/menu/item/%/edit','Edit menu link','a:0:{}','system',0,0,0,0,0,4,0,1,21,47,175,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',176,117,'admin/people/permissions/roles/edit/%','admin/people/permissions/roles/edit/%','Edit role','a:0:{}','system',0,0,0,0,0,5,0,1,18,49,117,176,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',177,104,'admin/config/media/image-styles/edit/%','admin/config/media/image-styles/edit/%','Edit style','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:25:\"Configure an image style.\";}}','system',0,0,1,0,0,5,0,1,8,46,104,177,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',178,136,'admin/structure/menu/manage/%/list','admin/structure/menu/manage/%/list','List links','a:0:{}','system',-1,0,0,0,-10,5,0,1,21,47,136,178,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',179,47,'admin/structure/menu/item/%/reset','admin/structure/menu/item/%/reset','Reset menu link','a:0:{}','system',0,0,0,0,0,4,0,1,21,47,179,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',180,104,'admin/config/media/image-styles/delete/%','admin/config/media/image-styles/delete/%','Delete style','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:22:\"Delete an image style.\";}}','system',0,0,0,0,0,5,0,1,8,46,104,180,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',181,104,'admin/config/media/image-styles/revert/%','admin/config/media/image-styles/revert/%','Revert style','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:22:\"Revert an image style.\";}}','system',0,0,0,0,0,5,0,1,8,46,104,181,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',182,138,'admin/structure/types/manage/%/comment/display','admin/structure/types/manage/%/comment/display','Comment display','a:0:{}','system',-1,0,0,0,4,5,0,1,21,36,138,182,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',183,138,'admin/structure/types/manage/%/comment/fields','admin/structure/types/manage/%/comment/fields','Comment fields','a:0:{}','system',-1,0,1,0,3,5,0,1,21,36,138,183,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',184,164,'admin/structure/block/manage/%/%/configure','admin/structure/block/manage/%/%/configure','Configure block','a:0:{}','system',-1,0,0,0,0,5,0,1,21,30,164,184,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',185,164,'admin/structure/block/manage/%/%/delete','admin/structure/block/manage/%/%/delete','Delete block','a:0:{}','system',-1,0,0,0,0,5,0,1,21,30,164,185,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',186,139,'admin/config/regional/date-time/formats/%/delete','admin/config/regional/date-time/formats/%/delete','Delete date format','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:47:\"Allow users to delete a configured date format.\";}}','system',0,0,0,0,0,6,0,1,8,51,98,139,186,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',187,149,'admin/config/regional/date-time/types/%/delete','admin/config/regional/date-time/types/%/delete','Delete date type','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:45:\"Allow users to delete a configured date type.\";}}','system',0,0,0,0,0,6,0,1,8,51,98,149,187,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',188,139,'admin/config/regional/date-time/formats/%/edit','admin/config/regional/date-time/formats/%/edit','Edit date format','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:45:\"Allow users to edit a configured date format.\";}}','system',0,0,0,0,0,6,0,1,8,51,98,139,188,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',189,177,'admin/config/media/image-styles/edit/%/add/%','admin/config/media/image-styles/edit/%/add/%','Add image effect','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:28:\"Add a new effect to a style.\";}}','system',0,0,0,0,0,6,0,1,8,46,104,177,189,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',190,177,'admin/config/media/image-styles/edit/%/effects/%','admin/config/media/image-styles/edit/%/effects/%','Edit image effect','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:39:\"Edit an existing effect within a style.\";}}','system',0,0,1,0,0,6,0,1,8,46,104,177,190,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',191,190,'admin/config/media/image-styles/edit/%/effects/%/delete','admin/config/media/image-styles/edit/%/effects/%/delete','Delete image effect','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:39:\"Delete an existing effect from a style.\";}}','system',0,0,0,0,0,7,0,1,8,46,104,177,190,191,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',192,47,'admin/structure/menu/manage/main-menu','admin/structure/menu/manage/%','Main menu','a:0:{}','menu',0,0,0,0,0,4,0,1,21,47,192,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',193,47,'admin/structure/menu/manage/management','admin/structure/menu/manage/%','Management','a:0:{}','menu',0,0,0,0,0,4,0,1,21,47,193,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',194,47,'admin/structure/menu/manage/navigation','admin/structure/menu/manage/%','Navigation','a:0:{}','menu',0,0,0,0,0,4,0,1,21,47,194,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',195,47,'admin/structure/menu/manage/user-menu','admin/structure/menu/manage/%','User menu','a:0:{}','menu',0,0,0,0,0,4,0,1,21,47,195,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('shortcut-set-1',196,0,'node/add','node/add','Add content','a:0:{}','menu',0,0,0,0,-20,1,0,196,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('shortcut-set-1',197,0,'admin/content','admin/content','Find content','a:0:{}','menu',0,0,0,0,-19,1,0,197,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('main-menu',198,0,'store/main','store/%','Demo shop','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:0:\"\";}}','menu',0,0,0,0,5,1,1,198,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',199,0,'search','search','Search','a:0:{}','system',1,0,0,0,0,1,0,199,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',200,199,'search/node','search/node','Content','a:0:{}','system',-1,0,0,0,-10,2,0,199,200,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',201,199,'search/user','search/user','Users','a:0:{}','system',-1,0,0,0,0,2,0,199,201,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',202,6,'node/add/article','node/add/article','Article','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:89:\"Use <em>articles</em> for time-sensitive content like news, press releases or blog posts.\";}}','system',1,0,0,0,0,2,1,6,202,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',203,6,'node/add/page','node/add/page','Basic page','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:77:\"Use <em>basic pages</em> for your static content, such as an \'About us\' page.\";}}','system',1,0,0,0,0,2,1,6,203,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',204,200,'search/node/%','search/node/%','Content','a:0:{}','system',-1,0,0,0,0,3,0,199,200,204,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',206,19,'admin/reports/search','admin/reports/search','Top search phrases','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:33:\"View most popular search phrases.\";}}','system',0,0,0,0,0,3,0,1,19,206,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',207,201,'search/user/%','search/user/%','Users','a:0:{}','system',-1,0,0,0,0,3,0,199,201,207,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',208,12,'admin/help/number','admin/help/number','number','a:0:{}','system',-1,0,0,0,0,3,0,1,12,208,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',209,12,'admin/help/overlay','admin/help/overlay','overlay','a:0:{}','system',-1,0,0,0,0,3,0,1,12,209,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',210,12,'admin/help/path','admin/help/path','path','a:0:{}','system',-1,0,0,0,0,3,0,1,12,210,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',211,12,'admin/help/rdf','admin/help/rdf','rdf','a:0:{}','system',-1,0,0,0,0,3,0,1,12,211,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',212,12,'admin/help/search','admin/help/search','search','a:0:{}','system',-1,0,0,0,0,3,0,1,12,212,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',214,12,'admin/help/toolbar','admin/help/toolbar','toolbar','a:0:{}','system',-1,0,0,0,0,3,0,1,12,214,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',215,53,'admin/config/search/settings','admin/config/search/settings','Search settings','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:67:\"Configure relevance settings for search and other indexing options.\";}}','system',0,0,0,0,-10,4,0,1,8,53,215,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',217,53,'admin/config/search/path','admin/config/search/path','URL aliases','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:46:\"Change your site\'s URL paths by aliasing them.\";}}','system',0,0,1,0,-10,4,0,1,8,53,217,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',218,217,'admin/config/search/path/add','admin/config/search/path/add','Add alias','a:0:{}','system',-1,0,0,0,0,5,0,1,8,53,217,218,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',220,215,'admin/config/search/settings/reindex','admin/config/search/settings/reindex','Clear index','a:0:{}','system',-1,0,0,0,0,5,0,1,8,53,215,220,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',222,217,'admin/config/search/path/list','admin/config/search/path/list','List','a:0:{}','system',-1,0,0,0,-10,5,0,1,8,53,217,222,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',224,217,'admin/config/search/path/delete/%','admin/config/search/path/delete/%','Delete alias','a:0:{}','system',0,0,0,0,0,5,0,1,8,53,217,224,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',226,217,'admin/config/search/path/edit/%','admin/config/search/path/edit/%','Edit alias','a:0:{}','system',0,0,0,0,0,5,0,1,8,53,217,226,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',307,0,'forum','forum','Forums','a:0:{}','system',1,0,1,0,0,1,1,307,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',308,0,'print/print','print/print','','a:0:{}','system',0,0,0,0,0,1,0,308,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',309,307,'forum/%','forum/%','Forums','a:0:{}','system',0,0,0,0,0,2,0,307,309,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',310,6,'node/add/forum','node/add/forum','Forum topic','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:69:\"A <em>forum topic</em> starts a new discussion thread within a forum.\";}}','system',1,0,0,0,0,2,1,6,310,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',311,21,'admin/structure/forum','admin/structure/forum','Forums','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:33:\"Control forum hierarchy settings.\";}}','system',0,0,1,0,0,3,0,1,21,311,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',312,8,'admin/config/print','admin/config/print','Printer, e-mail and PDF versions','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:73:\"Adds a printer-friendly version link to content and administrative pages.\";}}','system',0,0,0,0,0,3,0,1,8,312,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',313,12,'admin/help/forum','admin/help/forum','forum','a:0:{}','system',-1,0,0,0,0,3,0,1,12,313,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',314,12,'admin/help/print','admin/help/print','print','a:0:{}','system',-1,0,0,0,0,3,0,1,12,314,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',315,12,'admin/help/token','admin/help/token','token','a:0:{}','system',-1,0,0,0,0,3,0,1,12,315,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',316,311,'admin/structure/forum/list','admin/structure/forum/list','List','a:0:{}','system',-1,0,0,0,-10,4,0,1,21,311,316,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',317,312,'admin/config/print/common','admin/config/print/common','Settings','a:0:{}','system',-1,0,0,0,10,4,0,1,8,312,317,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',318,312,'admin/config/print/html','admin/config/print/html','Web page','a:0:{}','system',-1,0,0,0,1,4,0,1,8,312,318,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',319,311,'admin/structure/forum/settings','admin/structure/forum/settings','Settings','a:0:{}','system',-1,0,0,0,5,4,0,1,21,311,319,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',320,317,'admin/config/print/common/options','admin/config/print/common/options','Options','a:0:{}','system',-1,0,0,0,1,5,0,1,8,312,317,320,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',321,318,'admin/config/print/html/options','admin/config/print/html/options','Options','a:0:{}','system',-1,0,0,0,1,5,0,1,8,312,318,321,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',322,318,'admin/config/print/html/strings','admin/config/print/html/strings','Text strings','a:0:{}','system',-1,0,0,0,2,5,0,1,8,312,318,322,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',323,317,'admin/config/print/common/strings','admin/config/print/common/strings','Text strings','a:0:{}','system',-1,0,0,0,2,5,0,1,8,312,317,323,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',324,311,'admin/structure/forum/add/container','admin/structure/forum/add/container','Add container','a:0:{}','system',-1,0,0,0,0,4,0,1,21,311,324,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',325,311,'admin/structure/forum/add/forum','admin/structure/forum/add/forum','Add forum','a:0:{}','system',-1,0,0,0,0,4,0,1,21,311,325,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',326,311,'admin/structure/forum/edit/container/%','admin/structure/forum/edit/container/%','Edit container','a:0:{}','system',0,0,0,0,0,4,0,1,21,311,326,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',327,311,'admin/structure/forum/edit/forum/%','admin/structure/forum/edit/forum/%','Edit forum','a:0:{}','system',0,0,0,0,0,4,0,1,21,311,327,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',328,54,'admin/appearance/settings/lc3_clean','admin/appearance/settings/lc3_clean','Clean LC3 theme','a:0:{}','system',-1,0,0,0,0,4,0,1,7,54,328,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',329,30,'admin/structure/block/list/lc3_clean','admin/structure/block/list/lc3_clean','Clean LC3 theme','a:0:{}','system',-1,0,0,0,-10,4,0,1,21,30,329,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',332,134,'admin/structure/block/list/bartik/add','admin/structure/block/list/bartik/add','Add block','a:0:{}','system',-1,0,0,0,0,5,0,1,21,30,134,332,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',376,0,'contact','contact','Contact','a:0:{}','system',1,0,0,0,0,1,0,376,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',377,17,'user/%/contact','user/%/contact','Contact','a:0:{}','system',-1,0,0,0,2,2,0,17,377,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',378,21,'admin/structure/contact','admin/structure/contact','Contact form','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:71:\"Create a system contact form and set up categories for the form to use.\";}}','system',0,0,1,0,0,3,0,1,21,378,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',379,12,'admin/help/contact','admin/help/contact','contact','a:0:{}','system',-1,0,0,0,0,3,0,1,12,379,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',380,378,'admin/structure/contact/add','admin/structure/contact/add','Add category','a:0:{}','system',-1,0,0,0,1,4,0,1,21,378,380,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',381,378,'admin/structure/contact/delete/%','admin/structure/contact/delete/%','Delete contact','a:0:{}','system',0,0,0,0,0,4,0,1,21,378,381,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',382,378,'admin/structure/contact/edit/%','admin/structure/contact/edit/%','Edit contact category','a:0:{}','system',0,0,0,0,0,4,0,1,21,378,382,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('main-menu',384,0,'node/1','node/%','Features','a:0:{}','menu',0,0,0,0,10,1,1,384,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('main-menu',385,0,'node/2','node/%','Download','a:0:{}','menu',0,0,0,0,15,1,1,385,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('main-menu',386,0,'node/3','node/%','Community','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:0:\"\";}}','menu',0,0,1,1,20,1,1,386,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',387,12,'admin/help/php','admin/help/php','php','a:0:{}','system',-1,0,0,0,0,3,0,1,12,387,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('main-menu',388,386,'forum','forum','Forums','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:0:\"\";}}','menu',0,0,0,0,5,2,1,386,388,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('main-menu',389,386,'http://ideas.litecommerce.com','','Ideas','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:0:\"\";}}','menu',0,1,0,0,10,2,1,386,389,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('main-menu',390,386,'http://bugtracker.litecommerce.com','','Bugtracker','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:0:\"\";}}','menu',0,1,0,0,15,2,1,386,390,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('main-menu',392,0,'node/5','node/%','Company','a:1:{s:10:\"attributes\";a:0:{}}','menu',0,0,1,1,20,1,1,392,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('main-menu',393,392,'node/6','node/%','About us','a:0:{}','menu',0,0,0,0,5,2,0,392,393,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',394,0,'blog','blog','Blogs','a:0:{}','system',1,0,0,0,0,1,0,394,0,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',395,394,'blog/%','blog/%','My blog','a:0:{}','system',1,0,0,0,0,2,1,394,395,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',396,6,'node/add/blog','node/add/blog','Blog entry','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:58:\"Use for multi-user blogs. Every user gets a personal blog.\";}}','system',1,0,0,0,0,2,1,6,396,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',397,12,'admin/help/blog','admin/help/blog','blog','a:0:{}','system',-1,0,0,0,0,3,0,1,12,397,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',398,12,'admin/help/wysiwyg','admin/help/wysiwyg','wysiwyg','a:0:{}','system',-1,0,0,0,0,3,0,1,12,398,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',399,35,'admin/config/content/wysiwyg','admin/config/content/wysiwyg','Wysiwyg profiles','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:30:\"Configure client-side editors.\";}}','system',0,0,0,0,0,4,0,1,8,35,399,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',400,399,'admin/config/content/wysiwyg/profile','admin/config/content/wysiwyg/profile','List','a:0:{}','system',-1,0,0,0,0,5,0,1,8,35,399,400,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',401,400,'admin/config/content/wysiwyg/profile/%/edit','admin/config/content/wysiwyg/profile/%/edit','Edit','a:0:{}','system',-1,0,0,0,0,6,0,1,8,35,399,400,401,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',402,400,'admin/config/content/wysiwyg/profile/%/delete','admin/config/content/wysiwyg/profile/%/delete','Remove','a:0:{}','system',-1,0,0,0,10,6,0,1,8,35,399,400,402,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('main-menu',403,392,'blog','blog','Blog','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:0:\"\";}}','menu',0,0,0,0,10,2,1,392,403,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('main-menu',404,392,'contact','contact','Contact','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:0:\"\";}}','menu',0,0,0,0,15,2,1,392,404,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',406,127,'admin/structure/taxonomy/%/display','admin/structure/taxonomy/%/display','Manage display','a:0:{}','system',-1,0,0,0,2,5,0,1,21,57,127,406,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',407,89,'admin/config/people/accounts/display','admin/config/people/accounts/display','Manage display','a:0:{}','system',-1,0,0,0,2,5,0,1,8,48,89,407,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',408,127,'admin/structure/taxonomy/%/fields','admin/structure/taxonomy/%/fields','Manage fields','a:0:{}','system',-1,0,1,0,1,5,0,1,21,57,127,408,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',409,89,'admin/config/people/accounts/fields','admin/config/people/accounts/fields','Manage fields','a:0:{}','system',-1,0,1,0,1,5,0,1,8,48,89,409,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',410,406,'admin/structure/taxonomy/%/display/default','admin/structure/taxonomy/%/display/default','Default','a:0:{}','system',-1,0,0,0,-10,6,0,1,21,57,127,406,410,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',411,407,'admin/config/people/accounts/display/default','admin/config/people/accounts/display/default','Default','a:0:{}','system',-1,0,0,0,-10,6,0,1,8,48,89,407,411,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',412,138,'admin/structure/types/manage/%/display','admin/structure/types/manage/%/display','Manage display','a:0:{}','system',-1,0,0,0,2,5,0,1,21,36,138,412,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',413,138,'admin/structure/types/manage/%/fields','admin/structure/types/manage/%/fields','Manage fields','a:0:{}','system',-1,0,1,0,1,5,0,1,21,36,138,413,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',414,406,'admin/structure/taxonomy/%/display/full','admin/structure/taxonomy/%/display/full','Taxonomy term page','a:0:{}','system',-1,0,0,0,0,6,0,1,21,57,127,406,414,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',415,407,'admin/config/people/accounts/display/full','admin/config/people/accounts/display/full','User account','a:0:{}','system',-1,0,0,0,0,6,0,1,8,48,89,407,415,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',416,408,'admin/structure/taxonomy/%/fields/%','admin/structure/taxonomy/%/fields/%','','a:0:{}','system',0,0,0,0,0,6,0,1,21,57,127,408,416,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',417,409,'admin/config/people/accounts/fields/%','admin/config/people/accounts/fields/%','','a:0:{}','system',0,0,0,0,0,6,0,1,8,48,89,409,417,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',418,412,'admin/structure/types/manage/%/display/default','admin/structure/types/manage/%/display/default','Default','a:0:{}','system',-1,0,0,0,-10,6,0,1,21,36,138,412,418,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',419,412,'admin/structure/types/manage/%/display/full','admin/structure/types/manage/%/display/full','Full content','a:0:{}','system',-1,0,0,0,0,6,0,1,21,36,138,412,419,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',420,412,'admin/structure/types/manage/%/display/rss','admin/structure/types/manage/%/display/rss','RSS','a:0:{}','system',-1,0,0,0,2,6,0,1,21,36,138,412,420,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',421,412,'admin/structure/types/manage/%/display/search_index','admin/structure/types/manage/%/display/search_index','Search index','a:0:{}','system',-1,0,0,0,3,6,0,1,21,36,138,412,421,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',422,412,'admin/structure/types/manage/%/display/search_result','admin/structure/types/manage/%/display/search_result','Search result','a:0:{}','system',-1,0,0,0,4,6,0,1,21,36,138,412,422,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',423,412,'admin/structure/types/manage/%/display/teaser','admin/structure/types/manage/%/display/teaser','Teaser','a:0:{}','system',-1,0,0,0,1,6,0,1,21,36,138,412,423,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',424,413,'admin/structure/types/manage/%/fields/%','admin/structure/types/manage/%/fields/%','','a:0:{}','system',0,0,0,0,0,6,0,1,21,36,138,413,424,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',425,416,'admin/structure/taxonomy/%/fields/%/delete','admin/structure/taxonomy/%/fields/%/delete','Delete','a:0:{}','system',-1,0,0,0,10,7,0,1,21,57,127,408,416,425,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',426,416,'admin/structure/taxonomy/%/fields/%/edit','admin/structure/taxonomy/%/fields/%/edit','Edit','a:0:{}','system',-1,0,0,0,0,7,0,1,21,57,127,408,416,426,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',427,416,'admin/structure/taxonomy/%/fields/%/field-settings','admin/structure/taxonomy/%/fields/%/field-settings','Field settings','a:0:{}','system',-1,0,0,0,0,7,0,1,21,57,127,408,416,427,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',428,416,'admin/structure/taxonomy/%/fields/%/widget-type','admin/structure/taxonomy/%/fields/%/widget-type','Widget type','a:0:{}','system',-1,0,0,0,0,7,0,1,21,57,127,408,416,428,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',429,417,'admin/config/people/accounts/fields/%/delete','admin/config/people/accounts/fields/%/delete','Delete','a:0:{}','system',-1,0,0,0,10,7,0,1,8,48,89,409,417,429,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',430,417,'admin/config/people/accounts/fields/%/edit','admin/config/people/accounts/fields/%/edit','Edit','a:0:{}','system',-1,0,0,0,0,7,0,1,8,48,89,409,417,430,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',431,417,'admin/config/people/accounts/fields/%/field-settings','admin/config/people/accounts/fields/%/field-settings','Field settings','a:0:{}','system',-1,0,0,0,0,7,0,1,8,48,89,409,417,431,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',432,417,'admin/config/people/accounts/fields/%/widget-type','admin/config/people/accounts/fields/%/widget-type','Widget type','a:0:{}','system',-1,0,0,0,0,7,0,1,8,48,89,409,417,432,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',433,182,'admin/structure/types/manage/%/comment/display/default','admin/structure/types/manage/%/comment/display/default','Default','a:0:{}','system',-1,0,0,0,-10,6,0,1,21,36,138,182,433,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',434,182,'admin/structure/types/manage/%/comment/display/full','admin/structure/types/manage/%/comment/display/full','Full comment','a:0:{}','system',-1,0,0,0,0,6,0,1,21,36,138,182,434,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',435,183,'admin/structure/types/manage/%/comment/fields/%','admin/structure/types/manage/%/comment/fields/%','','a:0:{}','system',0,0,0,0,0,6,0,1,21,36,138,183,435,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',436,424,'admin/structure/types/manage/%/fields/%/delete','admin/structure/types/manage/%/fields/%/delete','Delete','a:0:{}','system',-1,0,0,0,10,7,0,1,21,36,138,413,424,436,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',437,424,'admin/structure/types/manage/%/fields/%/edit','admin/structure/types/manage/%/fields/%/edit','Edit','a:0:{}','system',-1,0,0,0,0,7,0,1,21,36,138,413,424,437,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',438,424,'admin/structure/types/manage/%/fields/%/field-settings','admin/structure/types/manage/%/fields/%/field-settings','Field settings','a:0:{}','system',-1,0,0,0,0,7,0,1,21,36,138,413,424,438,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',439,424,'admin/structure/types/manage/%/fields/%/widget-type','admin/structure/types/manage/%/fields/%/widget-type','Widget type','a:0:{}','system',-1,0,0,0,0,7,0,1,21,36,138,413,424,439,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',440,435,'admin/structure/types/manage/%/comment/fields/%/delete','admin/structure/types/manage/%/comment/fields/%/delete','Delete','a:0:{}','system',-1,0,0,0,10,7,0,1,21,36,138,183,435,440,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',441,435,'admin/structure/types/manage/%/comment/fields/%/edit','admin/structure/types/manage/%/comment/fields/%/edit','Edit','a:0:{}','system',-1,0,0,0,0,7,0,1,21,36,138,183,435,441,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',442,435,'admin/structure/types/manage/%/comment/fields/%/field-settings','admin/structure/types/manage/%/comment/fields/%/field-settings','Field settings','a:0:{}','system',-1,0,0,0,0,7,0,1,21,36,138,183,435,442,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',443,435,'admin/structure/types/manage/%/comment/fields/%/widget-type','admin/structure/types/manage/%/comment/fields/%/widget-type','Widget type','a:0:{}','system',-1,0,0,0,0,7,0,1,21,36,138,183,435,443,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',525,16,'admin/modules/lc_connector','admin/modules/lc_connector','LC Connector','a:1:{s:10:\"attributes\";a:1:{s:5:\"title\";s:37:\"Settings for the LC connector module.\";}}','system',0,0,0,0,0,3,0,1,16,525,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('management',602,1,'admin/lc_admin_area','admin/lc_admin_area','LC admin area','a:0:{}','system',0,0,0,0,0,2,0,1,602,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',603,17,'user/%/orders','user/%/orders','Order history','a:0:{}','system',-1,0,0,0,0,2,0,17,603,0,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',604,603,'user/%/orders/%','user/%/orders/%','','a:0:{}','system',-1,0,0,0,0,3,0,17,603,604,0,0,0,0,0,0,0);
INSERT INTO `drupal_menu_links` VALUES ('navigation',605,604,'user/%/orders/%/invoice','user/%/orders/%/invoice','','a:0:{}','system',-1,0,0,0,0,4,0,17,603,604,605,0,0,0,0,0,0);
/*!40000 ALTER TABLE `drupal_menu_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_menu_router`
--

DROP TABLE IF EXISTS `drupal_menu_router`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_menu_router` (
  `path` varchar(255) NOT NULL DEFAULT '' COMMENT 'Primary Key: the Drupal path this entry describes',
  `load_functions` blob NOT NULL COMMENT 'A serialized array of function names (like node_load) to be called to load an object corresponding to a part of the current path.',
  `to_arg_functions` blob NOT NULL COMMENT 'A serialized array of function names (like user_uid_optional_to_arg) to be called to replace a part of the router path with another string.',
  `access_callback` varchar(255) NOT NULL DEFAULT '' COMMENT 'The callback which determines the access to this router path. Defaults to user_access.',
  `access_arguments` blob COMMENT 'A serialized array of arguments for the access callback.',
  `page_callback` varchar(255) NOT NULL DEFAULT '' COMMENT 'The name of the function that renders the page.',
  `page_arguments` blob COMMENT 'A serialized array of arguments for the page callback.',
  `delivery_callback` varchar(255) NOT NULL DEFAULT '' COMMENT 'The name of the function that sends the result of the page_callback function to the browser.',
  `fit` int(11) NOT NULL DEFAULT '0' COMMENT 'A numeric representation of how specific the path is.',
  `number_parts` smallint(6) NOT NULL DEFAULT '0' COMMENT 'Number of parts in this router path.',
  `context` int(11) NOT NULL DEFAULT '0' COMMENT 'Only for local tasks (tabs) - the context of a local task to control its placement.',
  `tab_parent` varchar(255) NOT NULL DEFAULT '' COMMENT 'Only for local tasks (tabs) - the router path of the parent page (which may also be a local task).',
  `tab_root` varchar(255) NOT NULL DEFAULT '' COMMENT 'Router path of the closest non-tab parent page. For pages that are not local tasks, this will be the same as the path.',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT 'The title for the current page, or the title for the tab if this is a local task.',
  `title_callback` varchar(255) NOT NULL DEFAULT '' COMMENT 'A function which will alter the title. Defaults to t()',
  `title_arguments` varchar(255) NOT NULL DEFAULT '' COMMENT 'A serialized array of arguments for the title callback. If empty, the title will be used as the sole argument for the title callback.',
  `theme_callback` varchar(255) NOT NULL DEFAULT '' COMMENT 'A function which returns the name of the theme that will be used to render this page. If left empty, the default theme will be used.',
  `theme_arguments` varchar(255) NOT NULL DEFAULT '' COMMENT 'A serialized array of arguments for the theme callback.',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT 'Numeric representation of the type of the menu item, like MENU_LOCAL_TASK.',
  `description` text NOT NULL COMMENT 'A description of this item.',
  `position` varchar(255) NOT NULL DEFAULT '' COMMENT 'The position of the block (left or right) on the system administration page for this item.',
  `weight` int(11) NOT NULL DEFAULT '0' COMMENT 'Weight of the element. Lighter weights are higher up, heavier weights go down.',
  `include_file` mediumtext COMMENT 'The file to include for this element, usually the page callback function lives in this file.',
  PRIMARY KEY (`path`),
  KEY `fit` (`fit`),
  KEY `tab_parent` (`tab_parent`(64),`weight`,`title`),
  KEY `tab_root_weight_title` (`tab_root`(64),`weight`,`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Maps paths to various callbacks (access, page and title)';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_menu_router`
--

LOCK TABLES `drupal_menu_router` WRITE;
/*!40000 ALTER TABLE `drupal_menu_router` DISABLE KEYS */;
INSERT INTO `drupal_menu_router` VALUES ('admin','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','system_admin_menu_block_page','a:0:{}','',1,1,0,'','admin','Administration','t','','','a:0:{}',6,'','',9,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/appearance','','','user_access','a:1:{i:0;s:17:\"administer themes\";}','system_themes_page','a:0:{}','',3,2,0,'','admin/appearance','Appearance','t','','','a:0:{}',6,'Select and configure your themes.','left',-6,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/appearance/default','','','user_access','a:1:{i:0;s:17:\"administer themes\";}','system_theme_default','a:0:{}','',7,3,0,'','admin/appearance/default','Set default theme','t','','','a:0:{}',0,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/appearance/disable','','','user_access','a:1:{i:0;s:17:\"administer themes\";}','system_theme_disable','a:0:{}','',7,3,0,'','admin/appearance/disable','Disable theme','t','','','a:0:{}',0,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/appearance/enable','','','user_access','a:1:{i:0;s:17:\"administer themes\";}','system_theme_enable','a:0:{}','',7,3,0,'','admin/appearance/enable','Enable theme','t','','','a:0:{}',0,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/appearance/list','','','user_access','a:1:{i:0;s:17:\"administer themes\";}','system_themes_page','a:0:{}','',7,3,1,'admin/appearance','admin/appearance','List','t','','','a:0:{}',140,'Select and configure your theme','',-1,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/appearance/settings','','','user_access','a:1:{i:0;s:17:\"administer themes\";}','drupal_get_form','a:1:{i:0;s:21:\"system_theme_settings\";}','',7,3,1,'admin/appearance','admin/appearance','Settings','t','','','a:0:{}',132,'Configure default and theme specific settings.','',20,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/appearance/settings/bartik','','','_system_themes_access','a:1:{i:0;O:8:\"stdClass\":11:{s:8:\"filename\";s:25:\"themes/bartik/bartik.info\";s:4:\"name\";s:6:\"bartik\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"1\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:18:{s:4:\"name\";s:6:\"Bartik\";s:11:\"description\";s:48:\"A flexible, recolorable theme with many regions.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:2:{s:3:\"all\";a:3:{s:14:\"css/layout.css\";s:28:\"themes/bartik/css/layout.css\";s:13:\"css/style.css\";s:27:\"themes/bartik/css/style.css\";s:14:\"css/colors.css\";s:28:\"themes/bartik/css/colors.css\";}s:5:\"print\";a:1:{s:13:\"css/print.css\";s:27:\"themes/bartik/css/print.css\";}}s:7:\"regions\";a:20:{s:6:\"header\";s:6:\"Header\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:11:\"highlighted\";s:11:\"Highlighted\";s:8:\"featured\";s:8:\"Featured\";s:7:\"content\";s:7:\"Content\";s:13:\"sidebar_first\";s:13:\"Sidebar first\";s:14:\"sidebar_second\";s:14:\"Sidebar second\";s:14:\"triptych_first\";s:14:\"Triptych first\";s:15:\"triptych_middle\";s:15:\"Triptych middle\";s:13:\"triptych_last\";s:13:\"Triptych last\";s:18:\"footer_firstcolumn\";s:19:\"Footer first column\";s:19:\"footer_secondcolumn\";s:20:\"Footer second column\";s:18:\"footer_thirdcolumn\";s:19:\"Footer third column\";s:19:\"footer_fourthcolumn\";s:20:\"Footer fourth column\";s:6:\"footer\";s:6:\"Footer\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"settings\";a:1:{s:20:\"shortcut_module_link\";s:1:\"0\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:28:\"themes/bartik/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:11:\"stylesheets\";a:2:{s:3:\"all\";a:3:{s:14:\"css/layout.css\";s:28:\"themes/bartik/css/layout.css\";s:13:\"css/style.css\";s:27:\"themes/bartik/css/style.css\";s:14:\"css/colors.css\";s:28:\"themes/bartik/css/colors.css\";}s:5:\"print\";a:1:{s:13:\"css/print.css\";s:27:\"themes/bartik/css/print.css\";}}s:6:\"engine\";s:11:\"phptemplate\";}}','drupal_get_form','a:2:{i:0;s:21:\"system_theme_settings\";i:1;s:6:\"bartik\";}','',15,4,1,'admin/appearance/settings','admin/appearance','Bartik','t','','','a:0:{}',132,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/appearance/settings/garland','','','_system_themes_access','a:1:{i:0;O:8:\"stdClass\":11:{s:8:\"filename\";s:27:\"themes/garland/garland.info\";s:4:\"name\";s:7:\"garland\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"0\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:18:{s:4:\"name\";s:7:\"Garland\";s:11:\"description\";s:111:\"A multi-column theme which can be configured to modify colors and switch between fixed and fluid width layouts.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:2:{s:3:\"all\";a:1:{s:9:\"style.css\";s:24:\"themes/garland/style.css\";}s:5:\"print\";a:1:{s:9:\"print.css\";s:24:\"themes/garland/print.css\";}}s:8:\"settings\";a:1:{s:13:\"garland_width\";s:5:\"fluid\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:7:\"regions\";a:12:{s:13:\"sidebar_first\";s:12:\"Left sidebar\";s:14:\"sidebar_second\";s:13:\"Right sidebar\";s:7:\"content\";s:7:\"Content\";s:6:\"header\";s:6:\"Header\";s:6:\"footer\";s:6:\"Footer\";s:11:\"highlighted\";s:11:\"Highlighted\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:29:\"themes/garland/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:11:\"stylesheets\";a:2:{s:3:\"all\";a:1:{s:9:\"style.css\";s:24:\"themes/garland/style.css\";}s:5:\"print\";a:1:{s:9:\"print.css\";s:24:\"themes/garland/print.css\";}}s:6:\"engine\";s:11:\"phptemplate\";}}','drupal_get_form','a:2:{i:0;s:21:\"system_theme_settings\";i:1;s:7:\"garland\";}','',15,4,1,'admin/appearance/settings','admin/appearance','Garland','t','','','a:0:{}',132,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/appearance/settings/global','','','user_access','a:1:{i:0;s:17:\"administer themes\";}','drupal_get_form','a:1:{i:0;s:21:\"system_theme_settings\";}','',15,4,1,'admin/appearance/settings','admin/appearance','Global settings','t','','','a:0:{}',140,'','',-1,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/appearance/settings/lc3_clean','','','_system_themes_access','a:1:{i:0;O:8:\"stdClass\":12:{s:8:\"filename\";s:41:\"sites/all/themes/lc3_clean/lc3_clean.info\";s:4:\"name\";s:9:\"lc3_clean\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"1\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:14:{s:4:\"name\";s:15:\"Clean LC3 theme\";s:11:\"description\";s:36:\"A clean theme for LiteCommerce shops\";s:10:\"screenshot\";s:41:\"sites/all/themes/lc3_clean/screenshot.png\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:1:{s:3:\"all\";a:5:{s:13:\"css/reset.css\";s:40:\"sites/all/themes/lc3_clean/css/reset.css\";s:14:\"css/layout.css\";s:41:\"sites/all/themes/lc3_clean/css/layout.css\";s:13:\"css/style.css\";s:40:\"sites/all/themes/lc3_clean/css/style.css\";s:11:\"css/lc3.css\";s:38:\"sites/all/themes/lc3_clean/css/lc3.css\";s:16:\"system.menus.css\";s:43:\"sites/all/themes/lc3_clean/system.menus.css\";}}s:7:\"scripts\";a:3:{s:20:\"js/jquery.blockUI.js\";s:47:\"sites/all/themes/lc3_clean/js/jquery.blockUI.js\";s:11:\"js/popup.js\";s:38:\"sites/all/themes/lc3_clean/js/popup.js\";s:17:\"js/topMessages.js\";s:44:\"sites/all/themes/lc3_clean/js/topMessages.js\";}s:7:\"regions\";a:13:{s:6:\"header\";s:6:\"Header\";s:6:\"search\";s:6:\"Search\";s:4:\"help\";s:4:\"Help\";s:11:\"highlighted\";s:11:\"Highlighted\";s:13:\"sidebar_first\";s:14:\"Sidebar (left)\";s:7:\"content\";s:7:\"Content\";s:14:\"sidebar_second\";s:15:\"Sidebar (right)\";s:6:\"footer\";s:6:\"Footer\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"settings\";a:2:{s:26:\"theme_social_link_facebook\";s:12:\"litecommerce\";s:25:\"theme_social_link_twitter\";s:12:\"litecommerce\";}s:6:\"engine\";s:11:\"phptemplate\";s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:3:\"php\";s:5:\"5.2.4\";s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:11:\"stylesheets\";a:1:{s:3:\"all\";a:5:{s:13:\"css/reset.css\";s:40:\"sites/all/themes/lc3_clean/css/reset.css\";s:14:\"css/layout.css\";s:41:\"sites/all/themes/lc3_clean/css/layout.css\";s:13:\"css/style.css\";s:40:\"sites/all/themes/lc3_clean/css/style.css\";s:11:\"css/lc3.css\";s:38:\"sites/all/themes/lc3_clean/css/lc3.css\";s:16:\"system.menus.css\";s:43:\"sites/all/themes/lc3_clean/system.menus.css\";}}s:7:\"scripts\";a:3:{s:20:\"js/jquery.blockUI.js\";s:47:\"sites/all/themes/lc3_clean/js/jquery.blockUI.js\";s:11:\"js/popup.js\";s:38:\"sites/all/themes/lc3_clean/js/popup.js\";s:17:\"js/topMessages.js\";s:44:\"sites/all/themes/lc3_clean/js/topMessages.js\";}s:6:\"engine\";s:11:\"phptemplate\";}}','drupal_get_form','a:2:{i:0;s:21:\"system_theme_settings\";i:1;s:9:\"lc3_clean\";}','',15,4,1,'admin/appearance/settings','admin/appearance','Clean LC3 theme','t','','','a:0:{}',132,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/appearance/settings/seven','','','_system_themes_access','a:1:{i:0;O:8:\"stdClass\":11:{s:8:\"filename\";s:23:\"themes/seven/seven.info\";s:4:\"name\";s:5:\"seven\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"1\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:18:{s:4:\"name\";s:5:\"Seven\";s:11:\"description\";s:65:\"A simple one-column, tableless, fluid width administration theme.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:1:{s:6:\"screen\";a:2:{s:9:\"reset.css\";s:22:\"themes/seven/reset.css\";s:9:\"style.css\";s:22:\"themes/seven/style.css\";}}s:8:\"settings\";a:1:{s:20:\"shortcut_module_link\";s:1:\"1\";}s:7:\"regions\";a:8:{s:7:\"content\";s:7:\"Content\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:13:\"sidebar_first\";s:13:\"First sidebar\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:14:\"regions_hidden\";a:3:{i:0;s:13:\"sidebar_first\";i:1;s:8:\"page_top\";i:2;s:11:\"page_bottom\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:27:\"themes/seven/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:11:\"stylesheets\";a:1:{s:6:\"screen\";a:2:{s:9:\"reset.css\";s:22:\"themes/seven/reset.css\";s:9:\"style.css\";s:22:\"themes/seven/style.css\";}}s:6:\"engine\";s:11:\"phptemplate\";}}','drupal_get_form','a:2:{i:0;s:21:\"system_theme_settings\";i:1;s:5:\"seven\";}','',15,4,1,'admin/appearance/settings','admin/appearance','Seven','t','','','a:0:{}',132,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/appearance/settings/stark','','','_system_themes_access','a:1:{i:0;O:8:\"stdClass\":11:{s:8:\"filename\";s:23:\"themes/stark/stark.info\";s:4:\"name\";s:5:\"stark\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"0\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:17:{s:4:\"name\";s:5:\"Stark\";s:11:\"description\";s:208:\"This theme demonstrates Drupal\'s default HTML markup and CSS styles. To learn how to build your own theme and override Drupal\'s default code, see the <a href=\"http://drupal.org/theme-guide\">Theming Guide</a>.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:10:\"layout.css\";s:23:\"themes/stark/layout.css\";}}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:7:\"regions\";a:12:{s:13:\"sidebar_first\";s:12:\"Left sidebar\";s:14:\"sidebar_second\";s:13:\"Right sidebar\";s:7:\"content\";s:7:\"Content\";s:6:\"header\";s:6:\"Header\";s:6:\"footer\";s:6:\"Footer\";s:11:\"highlighted\";s:11:\"Highlighted\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:27:\"themes/stark/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:10:\"layout.css\";s:23:\"themes/stark/layout.css\";}}s:6:\"engine\";s:11:\"phptemplate\";}}','drupal_get_form','a:2:{i:0;s:21:\"system_theme_settings\";i:1;s:5:\"stark\";}','',15,4,1,'admin/appearance/settings','admin/appearance','Stark','t','','','a:0:{}',132,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/appearance/settings/test_theme','','','_system_themes_access','a:1:{i:0;O:8:\"stdClass\":11:{s:8:\"filename\";s:39:\"themes/tests/test_theme/test_theme.info\";s:4:\"name\";s:10:\"test_theme\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"0\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:17:{s:4:\"name\";s:10:\"Test theme\";s:11:\"description\";s:34:\"Theme for testing the theme system\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:15:\"system.base.css\";s:39:\"themes/tests/test_theme/system.base.css\";}}s:7:\"version\";s:3:\"7.0\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:7:\"regions\";a:12:{s:13:\"sidebar_first\";s:12:\"Left sidebar\";s:14:\"sidebar_second\";s:13:\"Right sidebar\";s:7:\"content\";s:7:\"Content\";s:6:\"header\";s:6:\"Header\";s:6:\"footer\";s:6:\"Footer\";s:11:\"highlighted\";s:11:\"Highlighted\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:38:\"themes/tests/test_theme/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:15:\"system.base.css\";s:39:\"themes/tests/test_theme/system.base.css\";}}s:6:\"engine\";s:11:\"phptemplate\";}}','drupal_get_form','a:2:{i:0;s:21:\"system_theme_settings\";i:1;s:10:\"test_theme\";}','',15,4,1,'admin/appearance/settings','admin/appearance','Test theme','t','','','a:0:{}',132,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/appearance/settings/update_test_basetheme','','','_system_themes_access','a:1:{i:0;O:8:\"stdClass\":10:{s:8:\"filename\";s:61:\"themes/tests/update_test_basetheme/update_test_basetheme.info\";s:4:\"name\";s:21:\"update_test_basetheme\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"0\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:17:{s:4:\"name\";s:22:\"Update test base theme\";s:11:\"description\";s:63:\"Test theme which acts as a base theme for other test subthemes.\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"version\";s:3:\"7.0\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:7:\"regions\";a:12:{s:13:\"sidebar_first\";s:12:\"Left sidebar\";s:14:\"sidebar_second\";s:13:\"Right sidebar\";s:7:\"content\";s:7:\"Content\";s:6:\"header\";s:6:\"Header\";s:6:\"footer\";s:6:\"Footer\";s:11:\"highlighted\";s:11:\"Highlighted\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:49:\"themes/tests/update_test_basetheme/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:11:\"stylesheets\";a:0:{}s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:6:\"engine\";s:11:\"phptemplate\";}}','drupal_get_form','a:2:{i:0;s:21:\"system_theme_settings\";i:1;s:21:\"update_test_basetheme\";}','',15,4,1,'admin/appearance/settings','admin/appearance','Update test base theme','t','','','a:0:{}',132,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/appearance/settings/update_test_subtheme','','','_system_themes_access','a:1:{i:0;O:8:\"stdClass\":11:{s:8:\"filename\";s:59:\"themes/tests/update_test_subtheme/update_test_subtheme.info\";s:4:\"name\";s:20:\"update_test_subtheme\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"0\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:18:{s:4:\"name\";s:20:\"Update test subtheme\";s:11:\"description\";s:62:\"Test theme which uses update_test_basetheme as the base theme.\";s:4:\"core\";s:3:\"7.x\";s:10:\"base theme\";s:21:\"update_test_basetheme\";s:6:\"hidden\";b:1;s:7:\"version\";s:3:\"7.0\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:7:\"regions\";a:12:{s:13:\"sidebar_first\";s:12:\"Left sidebar\";s:14:\"sidebar_second\";s:13:\"Right sidebar\";s:7:\"content\";s:7:\"Content\";s:6:\"header\";s:6:\"Header\";s:6:\"footer\";s:6:\"Footer\";s:11:\"highlighted\";s:11:\"Highlighted\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:48:\"themes/tests/update_test_subtheme/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:11:\"stylesheets\";a:0:{}s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:6:\"engine\";s:11:\"phptemplate\";s:10:\"base_theme\";s:21:\"update_test_basetheme\";}}','drupal_get_form','a:2:{i:0;s:21:\"system_theme_settings\";i:1;s:20:\"update_test_subtheme\";}','',15,4,1,'admin/appearance/settings','admin/appearance','Update test subtheme','t','','','a:0:{}',132,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/compact','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','system_admin_compact_page','a:0:{}','',3,2,0,'','admin/compact','Compact mode','t','','','a:0:{}',0,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','system_admin_config_page','a:0:{}','',3,2,0,'','admin/config','Configuration','t','','','a:0:{}',6,'Administer settings.','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/content','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','system_admin_menu_block_page','a:0:{}','',7,3,0,'','admin/config/content','Content authoring','t','','','a:0:{}',6,'Settings related to formatting and authoring content.','left',-15,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/content/formats','','','user_access','a:1:{i:0;s:18:\"administer filters\";}','drupal_get_form','a:1:{i:0;s:21:\"filter_admin_overview\";}','',15,4,0,'','admin/config/content/formats','Text formats','t','','','a:0:{}',6,'Configure how content input by users is filtered, including allowed HTML tags. Also allows enabling of module-provided filters.','',0,'modules/filter/filter.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/content/formats/%','a:1:{i:4;s:18:\"filter_format_load\";}','','user_access','a:1:{i:0;s:18:\"administer filters\";}','filter_admin_format_page','a:1:{i:0;i:4;}','',30,5,0,'','admin/config/content/formats/%','','filter_admin_format_title','a:1:{i:0;i:4;}','','a:0:{}',6,'','',0,'modules/filter/filter.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/content/formats/%/disable','a:1:{i:4;s:18:\"filter_format_load\";}','','_filter_disable_format_access','a:1:{i:0;i:4;}','drupal_get_form','a:2:{i:0;s:20:\"filter_admin_disable\";i:1;i:4;}','',61,6,0,'','admin/config/content/formats/%/disable','Disable text format','t','','','a:0:{}',6,'','',0,'modules/filter/filter.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/content/formats/add','','','user_access','a:1:{i:0;s:18:\"administer filters\";}','filter_admin_format_page','a:0:{}','',31,5,1,'admin/config/content/formats','admin/config/content/formats','Add text format','t','','','a:0:{}',388,'','',1,'modules/filter/filter.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/content/formats/list','','','user_access','a:1:{i:0;s:18:\"administer filters\";}','drupal_get_form','a:1:{i:0;s:21:\"filter_admin_overview\";}','',31,5,1,'admin/config/content/formats','admin/config/content/formats','List','t','','','a:0:{}',140,'','',0,'modules/filter/filter.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/content/wysiwyg','','','user_access','a:1:{i:0;s:18:\"administer filters\";}','drupal_get_form','a:1:{i:0;s:24:\"wysiwyg_profile_overview\";}','',15,4,0,'','admin/config/content/wysiwyg','Wysiwyg profiles','t','','','a:0:{}',6,'Configure client-side editors.','',0,'sites/all/modules/wysiwyg/wysiwyg.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/content/wysiwyg/profile','','','user_access','a:1:{i:0;s:18:\"administer filters\";}','drupal_get_form','a:1:{i:0;s:24:\"wysiwyg_profile_overview\";}','',31,5,1,'admin/config/content/wysiwyg','admin/config/content/wysiwyg','List','t','','','a:0:{}',140,'','',0,'sites/all/modules/wysiwyg/wysiwyg.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/content/wysiwyg/profile/%/delete','a:1:{i:5;s:20:\"wysiwyg_profile_load\";}','','user_access','a:1:{i:0;s:18:\"administer filters\";}','drupal_get_form','a:2:{i:0;s:30:\"wysiwyg_profile_delete_confirm\";i:1;i:5;}','',125,7,1,'admin/config/content/wysiwyg/profile/%wysiwyg_profile','admin/config/content/wysiwyg/profile','Remove','t','','','a:0:{}',132,'','',10,'sites/all/modules/wysiwyg/wysiwyg.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/content/wysiwyg/profile/%/edit','a:1:{i:5;s:20:\"wysiwyg_profile_load\";}','','user_access','a:1:{i:0;s:18:\"administer filters\";}','drupal_get_form','a:2:{i:0;s:20:\"wysiwyg_profile_form\";i:1;i:5;}','',125,7,1,'admin/config/content/wysiwyg/profile/%wysiwyg_profile','admin/config/content/wysiwyg/profile','Edit','t','','','a:0:{}',132,'','',0,'sites/all/modules/wysiwyg/wysiwyg.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/development','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','system_admin_menu_block_page','a:0:{}','',7,3,0,'','admin/config/development','Development','t','','','a:0:{}',6,'Development tools.','right',-10,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/development/logging','','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','drupal_get_form','a:1:{i:0;s:23:\"system_logging_settings\";}','',15,4,0,'','admin/config/development/logging','Logging and errors','t','','','a:0:{}',6,'Settings for logging and alerts modules. Various modules can route Drupal\'s system events to different destinations, such as syslog, database, email, etc.','',-15,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/development/maintenance','','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','drupal_get_form','a:1:{i:0;s:28:\"system_site_maintenance_mode\";}','',15,4,0,'','admin/config/development/maintenance','Maintenance mode','t','','','a:0:{}',6,'Take the site offline for maintenance or bring it back online.','',-10,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/development/performance','','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','drupal_get_form','a:1:{i:0;s:27:\"system_performance_settings\";}','',15,4,0,'','admin/config/development/performance','Performance','t','','','a:0:{}',6,'Enable or disable page caching for anonymous users and set CSS and JS bandwidth optimization options.','',-20,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/media','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','system_admin_menu_block_page','a:0:{}','',7,3,0,'','admin/config/media','Media','t','','','a:0:{}',6,'Media tools.','left',-10,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/media/file-system','','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','drupal_get_form','a:1:{i:0;s:27:\"system_file_system_settings\";}','',15,4,0,'','admin/config/media/file-system','File system','t','','','a:0:{}',6,'Tell Drupal where to store uploaded files and how they are accessed.','',-10,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/media/image-styles','','','user_access','a:1:{i:0;s:23:\"administer image styles\";}','image_style_list','a:0:{}','',15,4,0,'','admin/config/media/image-styles','Image styles','t','','','a:0:{}',6,'Configure styles that can be used for resizing or adjusting images on display.','',0,'modules/image/image.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/media/image-styles/add','','','user_access','a:1:{i:0;s:23:\"administer image styles\";}','drupal_get_form','a:1:{i:0;s:20:\"image_style_add_form\";}','',31,5,1,'admin/config/media/image-styles','admin/config/media/image-styles','Add style','t','','','a:0:{}',388,'Add a new image style.','',2,'modules/image/image.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/media/image-styles/delete/%','a:1:{i:5;a:1:{s:16:\"image_style_load\";a:2:{i:0;N;i:1;s:1:\"1\";}}}','','user_access','a:1:{i:0;s:23:\"administer image styles\";}','drupal_get_form','a:2:{i:0;s:23:\"image_style_delete_form\";i:1;i:5;}','',62,6,0,'','admin/config/media/image-styles/delete/%','Delete style','t','','','a:0:{}',6,'Delete an image style.','',0,'modules/image/image.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/media/image-styles/edit/%','a:1:{i:5;s:16:\"image_style_load\";}','','user_access','a:1:{i:0;s:23:\"administer image styles\";}','drupal_get_form','a:2:{i:0;s:16:\"image_style_form\";i:1;i:5;}','',62,6,0,'','admin/config/media/image-styles/edit/%','Edit style','t','','','a:0:{}',6,'Configure an image style.','',0,'modules/image/image.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/media/image-styles/edit/%/add/%','a:2:{i:5;a:1:{s:16:\"image_style_load\";a:1:{i:0;i:5;}}i:7;a:1:{s:28:\"image_effect_definition_load\";a:1:{i:0;i:5;}}}','','user_access','a:1:{i:0;s:23:\"administer image styles\";}','drupal_get_form','a:3:{i:0;s:17:\"image_effect_form\";i:1;i:5;i:2;i:7;}','',250,8,0,'','admin/config/media/image-styles/edit/%/add/%','Add image effect','t','','','a:0:{}',6,'Add a new effect to a style.','',0,'modules/image/image.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/media/image-styles/edit/%/effects/%','a:2:{i:5;a:1:{s:16:\"image_style_load\";a:2:{i:0;i:5;i:1;s:1:\"3\";}}i:7;a:1:{s:17:\"image_effect_load\";a:2:{i:0;i:5;i:1;s:1:\"3\";}}}','','user_access','a:1:{i:0;s:23:\"administer image styles\";}','drupal_get_form','a:3:{i:0;s:17:\"image_effect_form\";i:1;i:5;i:2;i:7;}','',250,8,0,'','admin/config/media/image-styles/edit/%/effects/%','Edit image effect','t','','','a:0:{}',6,'Edit an existing effect within a style.','',0,'modules/image/image.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/media/image-styles/edit/%/effects/%/delete','a:2:{i:5;a:1:{s:16:\"image_style_load\";a:2:{i:0;i:5;i:1;s:1:\"3\";}}i:7;a:1:{s:17:\"image_effect_load\";a:2:{i:0;i:5;i:1;s:1:\"3\";}}}','','user_access','a:1:{i:0;s:23:\"administer image styles\";}','drupal_get_form','a:3:{i:0;s:24:\"image_effect_delete_form\";i:1;i:5;i:2;i:7;}','',501,9,0,'','admin/config/media/image-styles/edit/%/effects/%/delete','Delete image effect','t','','','a:0:{}',6,'Delete an existing effect from a style.','',0,'modules/image/image.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/media/image-styles/list','','','user_access','a:1:{i:0;s:23:\"administer image styles\";}','image_style_list','a:0:{}','',31,5,1,'admin/config/media/image-styles','admin/config/media/image-styles','List','t','','','a:0:{}',140,'List the current image styles on the site.','',1,'modules/image/image.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/media/image-styles/revert/%','a:1:{i:5;a:1:{s:16:\"image_style_load\";a:2:{i:0;N;i:1;s:1:\"2\";}}}','','user_access','a:1:{i:0;s:23:\"administer image styles\";}','drupal_get_form','a:2:{i:0;s:23:\"image_style_revert_form\";i:1;i:5;}','',62,6,0,'','admin/config/media/image-styles/revert/%','Revert style','t','','','a:0:{}',6,'Revert an image style.','',0,'modules/image/image.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/media/image-toolkit','','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','drupal_get_form','a:1:{i:0;s:29:\"system_image_toolkit_settings\";}','',15,4,0,'','admin/config/media/image-toolkit','Image toolkit','t','','','a:0:{}',6,'Choose which image toolkit to use if you have installed optional toolkits.','',20,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/people','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','system_admin_menu_block_page','a:0:{}','',7,3,0,'','admin/config/people','People','t','','','a:0:{}',6,'Configure user accounts.','left',-20,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/people/accounts','','','user_access','a:1:{i:0;s:16:\"administer users\";}','drupal_get_form','a:1:{i:0;s:19:\"user_admin_settings\";}','',15,4,0,'','admin/config/people/accounts','Account settings','t','','','a:0:{}',6,'Configure default behavior of users, including registration requirements, e-mails, fields, and user pictures.','',-10,'modules/user/user.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/people/accounts/display','','','user_access','a:1:{i:0;s:16:\"administer users\";}','drupal_get_form','a:4:{i:0;s:30:\"field_ui_display_overview_form\";i:1;s:4:\"user\";i:2;s:4:\"user\";i:3;s:7:\"default\";}','',31,5,1,'admin/config/people/accounts','admin/config/people/accounts','Manage display','t','','','a:0:{}',132,'','',2,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/people/accounts/display/default','','','_field_ui_view_mode_menu_access','a:5:{i:0;s:4:\"user\";i:1;s:4:\"user\";i:2;s:7:\"default\";i:3;s:11:\"user_access\";i:4;s:16:\"administer users\";}','drupal_get_form','a:4:{i:0;s:30:\"field_ui_display_overview_form\";i:1;s:4:\"user\";i:2;s:4:\"user\";i:3;s:7:\"default\";}','',63,6,1,'admin/config/people/accounts/display','admin/config/people/accounts','Default','t','','','a:0:{}',140,'','',-10,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/people/accounts/display/full','','','_field_ui_view_mode_menu_access','a:5:{i:0;s:4:\"user\";i:1;s:4:\"user\";i:2;s:4:\"full\";i:3;s:11:\"user_access\";i:4;s:16:\"administer users\";}','drupal_get_form','a:4:{i:0;s:30:\"field_ui_display_overview_form\";i:1;s:4:\"user\";i:2;s:4:\"user\";i:3;s:4:\"full\";}','',63,6,1,'admin/config/people/accounts/display','admin/config/people/accounts','User account','t','','','a:0:{}',132,'','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/people/accounts/fields','','','user_access','a:1:{i:0;s:16:\"administer users\";}','drupal_get_form','a:3:{i:0;s:28:\"field_ui_field_overview_form\";i:1;s:4:\"user\";i:2;s:4:\"user\";}','',31,5,1,'admin/config/people/accounts','admin/config/people/accounts','Manage fields','t','','','a:0:{}',132,'','',1,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/people/accounts/fields/%','a:1:{i:5;a:1:{s:18:\"field_ui_menu_load\";a:4:{i:0;s:4:\"user\";i:1;s:4:\"user\";i:2;s:1:\"0\";i:3;s:4:\"%map\";}}}','','user_access','a:1:{i:0;s:16:\"administer users\";}','drupal_get_form','a:2:{i:0;s:24:\"field_ui_field_edit_form\";i:1;i:5;}','',62,6,0,'','admin/config/people/accounts/fields/%','','field_ui_menu_title','a:1:{i:0;i:5;}','','a:0:{}',6,'','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/people/accounts/fields/%/delete','a:1:{i:5;a:1:{s:18:\"field_ui_menu_load\";a:4:{i:0;s:4:\"user\";i:1;s:4:\"user\";i:2;s:1:\"0\";i:3;s:4:\"%map\";}}}','','user_access','a:1:{i:0;s:16:\"administer users\";}','drupal_get_form','a:2:{i:0;s:26:\"field_ui_field_delete_form\";i:1;i:5;}','',125,7,1,'admin/config/people/accounts/fields/%','admin/config/people/accounts/fields/%','Delete','t','','','a:0:{}',132,'','',10,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/people/accounts/fields/%/edit','a:1:{i:5;a:1:{s:18:\"field_ui_menu_load\";a:4:{i:0;s:4:\"user\";i:1;s:4:\"user\";i:2;s:1:\"0\";i:3;s:4:\"%map\";}}}','','user_access','a:1:{i:0;s:16:\"administer users\";}','drupal_get_form','a:2:{i:0;s:24:\"field_ui_field_edit_form\";i:1;i:5;}','',125,7,1,'admin/config/people/accounts/fields/%','admin/config/people/accounts/fields/%','Edit','t','','','a:0:{}',140,'','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/people/accounts/fields/%/field-settings','a:1:{i:5;a:1:{s:18:\"field_ui_menu_load\";a:4:{i:0;s:4:\"user\";i:1;s:4:\"user\";i:2;s:1:\"0\";i:3;s:4:\"%map\";}}}','','user_access','a:1:{i:0;s:16:\"administer users\";}','drupal_get_form','a:2:{i:0;s:28:\"field_ui_field_settings_form\";i:1;i:5;}','',125,7,1,'admin/config/people/accounts/fields/%','admin/config/people/accounts/fields/%','Field settings','t','','','a:0:{}',132,'','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/people/accounts/fields/%/widget-type','a:1:{i:5;a:1:{s:18:\"field_ui_menu_load\";a:4:{i:0;s:4:\"user\";i:1;s:4:\"user\";i:2;s:1:\"0\";i:3;s:4:\"%map\";}}}','','user_access','a:1:{i:0;s:16:\"administer users\";}','drupal_get_form','a:2:{i:0;s:25:\"field_ui_widget_type_form\";i:1;i:5;}','',125,7,1,'admin/config/people/accounts/fields/%','admin/config/people/accounts/fields/%','Widget type','t','','','a:0:{}',132,'','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/people/accounts/settings','','','user_access','a:1:{i:0;s:16:\"administer users\";}','drupal_get_form','a:1:{i:0;s:19:\"user_admin_settings\";}','',31,5,1,'admin/config/people/accounts','admin/config/people/accounts','Settings','t','','','a:0:{}',140,'','',-10,'modules/user/user.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/people/ip-blocking','','','user_access','a:1:{i:0;s:18:\"block IP addresses\";}','system_ip_blocking','a:0:{}','',15,4,0,'','admin/config/people/ip-blocking','IP address blocking','t','','','a:0:{}',6,'Manage blocked IP addresses.','',10,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/people/ip-blocking/delete/%','a:1:{i:5;s:15:\"blocked_ip_load\";}','','user_access','a:1:{i:0;s:18:\"block IP addresses\";}','drupal_get_form','a:2:{i:0;s:25:\"system_ip_blocking_delete\";i:1;i:5;}','',62,6,0,'','admin/config/people/ip-blocking/delete/%','Delete IP address','t','','','a:0:{}',6,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/print','','','user_access','a:1:{i:0;s:16:\"administer print\";}','drupal_get_form','a:1:{i:0;s:19:\"print_html_settings\";}','',7,3,0,'','admin/config/print','Printer, e-mail and PDF versions','t','','','a:0:{}',6,'Adds a printer-friendly version link to content and administrative pages.','',0,'modules/print/print.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/print/common','','','user_access','a:1:{i:0;s:16:\"administer print\";}','drupal_get_form','a:1:{i:0;s:19:\"print_main_settings\";}','',15,4,1,'admin/config/print','admin/config/print','Settings','t','','','a:0:{}',132,'','',10,'modules/print/print.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/print/common/options','','','user_access','a:1:{i:0;s:16:\"administer print\";}','drupal_get_form','a:1:{i:0;s:19:\"print_main_settings\";}','',31,5,1,'admin/config/print/common','admin/config/print','Options','t','','','a:0:{}',140,'','',1,'modules/print/print.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/print/common/strings','','','user_access','a:1:{i:0;s:16:\"administer print\";}','drupal_get_form','a:1:{i:0;s:27:\"print_main_strings_settings\";}','',31,5,1,'admin/config/print/common','admin/config/print','Text strings','t','','','a:0:{}',132,'','',2,'modules/print/print.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/print/html','','','user_access','a:1:{i:0;s:16:\"administer print\";}','drupal_get_form','a:1:{i:0;s:19:\"print_html_settings\";}','',15,4,1,'admin/config/print','admin/config/print','Web page','t','','','a:0:{}',140,'','',1,'modules/print/print.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/print/html/options','','','user_access','a:1:{i:0;s:16:\"administer print\";}','drupal_get_form','a:1:{i:0;s:19:\"print_html_settings\";}','',31,5,1,'admin/config/print/html','admin/config/print','Options','t','','','a:0:{}',140,'','',1,'modules/print/print.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/print/html/strings','','','user_access','a:1:{i:0;s:16:\"administer print\";}','drupal_get_form','a:1:{i:0;s:27:\"print_html_strings_settings\";}','',31,5,1,'admin/config/print/html','admin/config/print','Text strings','t','','','a:0:{}',132,'','',2,'modules/print/print.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/regional','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','system_admin_menu_block_page','a:0:{}','',7,3,0,'','admin/config/regional','Regional and language','t','','','a:0:{}',6,'Regional settings, localization and translation.','left',-5,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/regional/date-time','','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','drupal_get_form','a:1:{i:0;s:25:\"system_date_time_settings\";}','',15,4,0,'','admin/config/regional/date-time','Date and time','t','','','a:0:{}',6,'Configure display formats for date and time.','',-15,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/regional/date-time/formats','','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','system_date_time_formats','a:0:{}','',31,5,1,'admin/config/regional/date-time','admin/config/regional/date-time','Formats','t','','','a:0:{}',132,'Configure display format strings for date and time.','',-9,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/regional/date-time/formats/%/delete','a:1:{i:5;N;}','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','drupal_get_form','a:2:{i:0;s:30:\"system_date_delete_format_form\";i:1;i:5;}','',125,7,0,'','admin/config/regional/date-time/formats/%/delete','Delete date format','t','','','a:0:{}',6,'Allow users to delete a configured date format.','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/regional/date-time/formats/%/edit','a:1:{i:5;N;}','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','drupal_get_form','a:2:{i:0;s:34:\"system_configure_date_formats_form\";i:1;i:5;}','',125,7,0,'','admin/config/regional/date-time/formats/%/edit','Edit date format','t','','','a:0:{}',6,'Allow users to edit a configured date format.','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/regional/date-time/formats/add','','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','drupal_get_form','a:1:{i:0;s:34:\"system_configure_date_formats_form\";}','',63,6,1,'admin/config/regional/date-time/formats','admin/config/regional/date-time','Add format','t','','','a:0:{}',388,'Allow users to add additional date formats.','',-10,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/regional/date-time/formats/lookup','','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','system_date_time_lookup','a:0:{}','',63,6,0,'','admin/config/regional/date-time/formats/lookup','Date and time lookup','t','','','a:0:{}',0,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/regional/date-time/types','','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','drupal_get_form','a:1:{i:0;s:25:\"system_date_time_settings\";}','',31,5,1,'admin/config/regional/date-time','admin/config/regional/date-time','Types','t','','','a:0:{}',140,'Configure display formats for date and time.','',-10,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/regional/date-time/types/%/delete','a:1:{i:5;N;}','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','drupal_get_form','a:2:{i:0;s:35:\"system_delete_date_format_type_form\";i:1;i:5;}','',125,7,0,'','admin/config/regional/date-time/types/%/delete','Delete date type','t','','','a:0:{}',6,'Allow users to delete a configured date type.','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/regional/date-time/types/add','','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','drupal_get_form','a:1:{i:0;s:32:\"system_add_date_format_type_form\";}','',63,6,1,'admin/config/regional/date-time/types','admin/config/regional/date-time','Add date type','t','','','a:0:{}',388,'Add new date type.','',-10,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/regional/settings','','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','drupal_get_form','a:1:{i:0;s:24:\"system_regional_settings\";}','',15,4,0,'','admin/config/regional/settings','Regional settings','t','','','a:0:{}',6,'Settings for the site\'s default time zone and country.','',-20,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/search','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','system_admin_menu_block_page','a:0:{}','',7,3,0,'','admin/config/search','Search and metadata','t','','','a:0:{}',6,'Local site search, metadata and SEO.','left',-10,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/search/clean-urls','','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','drupal_get_form','a:1:{i:0;s:25:\"system_clean_url_settings\";}','',15,4,0,'','admin/config/search/clean-urls','Clean URLs','t','','','a:0:{}',6,'Enable or disable clean URLs for your site.','',5,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/search/clean-urls/check','','','1','a:0:{}','drupal_json_output','a:1:{i:0;a:1:{s:6:\"status\";b:1;}}','',31,5,0,'','admin/config/search/clean-urls/check','Clean URL check','t','','','a:0:{}',0,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/search/path','','','user_access','a:1:{i:0;s:22:\"administer url aliases\";}','path_admin_overview','a:0:{}','',15,4,0,'','admin/config/search/path','URL aliases','t','','','a:0:{}',6,'Change your site\'s URL paths by aliasing them.','',-10,'modules/path/path.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/search/path/add','','','user_access','a:1:{i:0;s:22:\"administer url aliases\";}','path_admin_edit','a:0:{}','',31,5,1,'admin/config/search/path','admin/config/search/path','Add alias','t','','','a:0:{}',388,'','',0,'modules/path/path.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/search/path/delete/%','a:1:{i:5;s:9:\"path_load\";}','','user_access','a:1:{i:0;s:22:\"administer url aliases\";}','drupal_get_form','a:2:{i:0;s:25:\"path_admin_delete_confirm\";i:1;i:5;}','',62,6,0,'','admin/config/search/path/delete/%','Delete alias','t','','','a:0:{}',6,'','',0,'modules/path/path.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/search/path/edit/%','a:1:{i:5;s:9:\"path_load\";}','','user_access','a:1:{i:0;s:22:\"administer url aliases\";}','path_admin_edit','a:1:{i:0;i:5;}','',62,6,0,'','admin/config/search/path/edit/%','Edit alias','t','','','a:0:{}',6,'','',0,'modules/path/path.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/search/path/list','','','user_access','a:1:{i:0;s:22:\"administer url aliases\";}','path_admin_overview','a:0:{}','',31,5,1,'admin/config/search/path','admin/config/search/path','List','t','','','a:0:{}',140,'','',-10,'modules/path/path.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/search/settings','','','user_access','a:1:{i:0;s:17:\"administer search\";}','drupal_get_form','a:1:{i:0;s:21:\"search_admin_settings\";}','',15,4,0,'','admin/config/search/settings','Search settings','t','','','a:0:{}',6,'Configure relevance settings for search and other indexing options.','',-10,'modules/search/search.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/search/settings/reindex','','','user_access','a:1:{i:0;s:17:\"administer search\";}','drupal_get_form','a:1:{i:0;s:22:\"search_reindex_confirm\";}','',31,5,0,'','admin/config/search/settings/reindex','Clear index','t','','','a:0:{}',4,'','',0,'modules/search/search.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/services','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','system_admin_menu_block_page','a:0:{}','',7,3,0,'','admin/config/services','Web services','t','','','a:0:{}',6,'Tools related to web services.','right',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/services/rss-publishing','','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','drupal_get_form','a:1:{i:0;s:25:\"system_rss_feeds_settings\";}','',15,4,0,'','admin/config/services/rss-publishing','RSS publishing','t','','','a:0:{}',6,'Configure the site description, the number of items per feed and whether feeds should be titles/teasers/full-text.','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/system','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','system_admin_menu_block_page','a:0:{}','',7,3,0,'','admin/config/system','System','t','','','a:0:{}',6,'General system related configuration.','right',-20,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/system/actions','','','user_access','a:1:{i:0;s:18:\"administer actions\";}','system_actions_manage','a:0:{}','',15,4,0,'','admin/config/system/actions','Actions','t','','','a:0:{}',6,'Manage the actions defined for your site.','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/system/actions/configure','','','user_access','a:1:{i:0;s:18:\"administer actions\";}','drupal_get_form','a:1:{i:0;s:24:\"system_actions_configure\";}','',31,5,0,'','admin/config/system/actions/configure','Configure an advanced action','t','','','a:0:{}',4,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/system/actions/delete/%','a:1:{i:5;s:12:\"actions_load\";}','','user_access','a:1:{i:0;s:18:\"administer actions\";}','drupal_get_form','a:2:{i:0;s:26:\"system_actions_delete_form\";i:1;i:5;}','',62,6,0,'','admin/config/system/actions/delete/%','Delete action','t','','','a:0:{}',6,'Delete an action.','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/system/actions/manage','','','user_access','a:1:{i:0;s:18:\"administer actions\";}','system_actions_manage','a:0:{}','',31,5,1,'admin/config/system/actions','admin/config/system/actions','Manage actions','t','','','a:0:{}',140,'Manage the actions defined for your site.','',-2,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/system/actions/orphan','','','user_access','a:1:{i:0;s:18:\"administer actions\";}','system_actions_remove_orphans','a:0:{}','',31,5,0,'','admin/config/system/actions/orphan','Remove orphans','t','','','a:0:{}',0,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/system/cron','','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','drupal_get_form','a:1:{i:0;s:20:\"system_cron_settings\";}','',15,4,0,'','admin/config/system/cron','Cron','t','','','a:0:{}',6,'Manage automatic site maintenance tasks.','',20,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/system/site-information','','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','drupal_get_form','a:1:{i:0;s:32:\"system_site_information_settings\";}','',15,4,0,'','admin/config/system/site-information','Site information','t','','','a:0:{}',6,'Change site name, e-mail address, slogan, default front page, and number of posts per page, error pages.','',-20,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/user-interface','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','system_admin_menu_block_page','a:0:{}','',7,3,0,'','admin/config/user-interface','User interface','t','','','a:0:{}',6,'Tools that enhance the user interface.','right',-15,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/config/workflow','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','system_admin_menu_block_page','a:0:{}','',7,3,0,'','admin/config/workflow','Workflow','t','','','a:0:{}',6,'Content workflow, editorial workflow tools.','right',5,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/content','','','user_access','a:1:{i:0;s:23:\"access content overview\";}','drupal_get_form','a:1:{i:0;s:18:\"node_admin_content\";}','',3,2,0,'','admin/content','Content','t','','','a:0:{}',6,'Administer content and comments.','',-10,'modules/node/node.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/content/comment','','','user_access','a:1:{i:0;s:19:\"administer comments\";}','comment_admin','a:0:{}','',7,3,1,'admin/content','admin/content','Comments','t','','','a:0:{}',134,'List and edit site comments and the comment approval queue.','',0,'modules/comment/comment.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/content/comment/approval','','','user_access','a:1:{i:0;s:19:\"administer comments\";}','comment_admin','a:1:{i:0;s:8:\"approval\";}','',15,4,1,'admin/content/comment','admin/content','Unapproved comments','comment_count_unpublished','','','a:0:{}',132,'','',0,'modules/comment/comment.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/content/comment/new','','','user_access','a:1:{i:0;s:19:\"administer comments\";}','comment_admin','a:0:{}','',15,4,1,'admin/content/comment','admin/content','Published comments','t','','','a:0:{}',140,'','',-10,'modules/comment/comment.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/content/node','','','user_access','a:1:{i:0;s:23:\"access content overview\";}','drupal_get_form','a:1:{i:0;s:18:\"node_admin_content\";}','',7,3,1,'admin/content','admin/content','Content','t','','','a:0:{}',140,'','',-10,'modules/node/node.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/dashboard','','','user_access','a:1:{i:0;s:16:\"access dashboard\";}','dashboard_admin','a:0:{}','',3,2,0,'','admin/dashboard','Dashboard','t','','','a:0:{}',6,'View and customize your dashboard.','',-15,'');
INSERT INTO `drupal_menu_router` VALUES ('admin/dashboard/block-content/%/%','a:2:{i:3;N;i:4;N;}','','user_access','a:1:{i:0;s:17:\"administer blocks\";}','dashboard_show_block_content','a:2:{i:0;i:3;i:1;i:4;}','',28,5,0,'','admin/dashboard/block-content/%/%','','t','','','a:0:{}',0,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('admin/dashboard/configure','','','user_access','a:1:{i:0;s:17:\"administer blocks\";}','dashboard_admin_blocks','a:0:{}','',7,3,0,'','admin/dashboard/configure','Configure available dashboard blocks','t','','','a:0:{}',4,'Configure which blocks can be shown on the dashboard.','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('admin/dashboard/customize','','','user_access','a:1:{i:0;s:16:\"access dashboard\";}','dashboard_admin','a:1:{i:0;b:1;}','',7,3,0,'','admin/dashboard/customize','Customize dashboard','t','','','a:0:{}',4,'Customize your dashboard.','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('admin/dashboard/drawer','','','user_access','a:1:{i:0;s:17:\"administer blocks\";}','dashboard_show_disabled','a:0:{}','',7,3,0,'','admin/dashboard/drawer','','t','','','a:0:{}',0,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('admin/dashboard/update','','','user_access','a:1:{i:0;s:17:\"administer blocks\";}','dashboard_update','a:0:{}','',7,3,0,'','admin/dashboard/update','','t','','','a:0:{}',0,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('admin/help','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_main','a:0:{}','',3,2,0,'','admin/help','Help','t','','','a:0:{}',6,'Reference for usage, configuration, and modules.','',9,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/block','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/block','block','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/blog','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/blog','blog','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/color','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/color','color','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/comment','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/comment','comment','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/contact','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/contact','contact','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/contextual','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/contextual','contextual','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/dashboard','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/dashboard','dashboard','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/dblog','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/dblog','dblog','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/field','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/field','field','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/field_sql_storage','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/field_sql_storage','field_sql_storage','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/field_ui','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/field_ui','field_ui','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/file','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/file','file','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/filter','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/filter','filter','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/forum','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/forum','forum','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/help','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/help','help','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/image','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/image','image','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/list','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/list','list','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/menu','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/menu','menu','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/node','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/node','node','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/number','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/number','number','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/options','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/options','options','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/overlay','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/overlay','overlay','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/path','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/path','path','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/php','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/php','php','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/print','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/print','print','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/rdf','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/rdf','rdf','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/search','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/search','search','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/system','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/system','system','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/taxonomy','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/taxonomy','taxonomy','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/text','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/text','text','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/token','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/token','token','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/toolbar','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/toolbar','toolbar','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/user','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/user','user','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/help/wysiwyg','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','help_page','a:1:{i:0;i:2;}','',7,3,0,'','admin/help/wysiwyg','wysiwyg','t','','','a:0:{}',4,'','',0,'modules/help/help.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/index','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','system_admin_index','a:0:{}','',3,2,1,'admin','admin','Index','t','','','a:0:{}',132,'','',-18,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/lc_admin_area','','','lc_connector_check_controller_access','a:0:{}','lcConnectorGetControllerContent','a:0:{}','',3,2,0,'','admin/lc_admin_area','LC admin area','t','','','a:0:{}',6,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('admin/modules','','','user_access','a:1:{i:0;s:18:\"administer modules\";}','drupal_get_form','a:1:{i:0;s:14:\"system_modules\";}','',3,2,0,'','admin/modules','Modules','t','','','a:0:{}',6,'Enable or disable modules.','',-2,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/modules/lc_connector','','','user_access','a:1:{i:0;s:16:\"administer users\";}','drupal_get_form','a:1:{i:0;s:30:\"lc_connector_get_settings_form\";}','',7,3,0,'','admin/modules/lc_connector','LC Connector','t','','','a:0:{}',6,'Settings for the LC connector module.','',0,'modules/lc_connector/lc_connector.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/modules/list','','','user_access','a:1:{i:0;s:18:\"administer modules\";}','drupal_get_form','a:1:{i:0;s:14:\"system_modules\";}','',7,3,1,'admin/modules','admin/modules','List','t','','','a:0:{}',140,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/modules/list/confirm','','','user_access','a:1:{i:0;s:18:\"administer modules\";}','drupal_get_form','a:1:{i:0;s:14:\"system_modules\";}','',15,4,0,'','admin/modules/list/confirm','List','t','','','a:0:{}',4,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/modules/uninstall','','','user_access','a:1:{i:0;s:18:\"administer modules\";}','drupal_get_form','a:1:{i:0;s:24:\"system_modules_uninstall\";}','',7,3,1,'admin/modules','admin/modules','Uninstall','t','','','a:0:{}',132,'','',20,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/modules/uninstall/confirm','','','user_access','a:1:{i:0;s:18:\"administer modules\";}','drupal_get_form','a:1:{i:0;s:24:\"system_modules_uninstall\";}','',15,4,0,'','admin/modules/uninstall/confirm','Uninstall','t','','','a:0:{}',4,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/people','','','user_access','a:1:{i:0;s:16:\"administer users\";}','user_admin','a:1:{i:0;s:4:\"list\";}','',3,2,0,'','admin/people','People','t','','','a:0:{}',6,'Manage user accounts, roles, and permissions.','left',-4,'modules/user/user.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/people/create','','','user_access','a:1:{i:0;s:16:\"administer users\";}','user_admin','a:1:{i:0;s:6:\"create\";}','',7,3,1,'admin/people','admin/people','Add user','t','','','a:0:{}',388,'','',0,'modules/user/user.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/people/people','','','user_access','a:1:{i:0;s:16:\"administer users\";}','user_admin','a:1:{i:0;s:4:\"list\";}','',7,3,1,'admin/people','admin/people','List','t','','','a:0:{}',140,'Find and manage people interacting with your site.','',-10,'modules/user/user.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/people/permissions','','','user_access','a:1:{i:0;s:22:\"administer permissions\";}','drupal_get_form','a:1:{i:0;s:22:\"user_admin_permissions\";}','',7,3,1,'admin/people','admin/people','Permissions','t','','','a:0:{}',132,'Determine access to features by selecting permissions for roles.','',0,'modules/user/user.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/people/permissions/list','','','user_access','a:1:{i:0;s:22:\"administer permissions\";}','drupal_get_form','a:1:{i:0;s:22:\"user_admin_permissions\";}','',15,4,1,'admin/people/permissions','admin/people','Permissions','t','','','a:0:{}',140,'Determine access to features by selecting permissions for roles.','',-8,'modules/user/user.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/people/permissions/roles','','','user_access','a:1:{i:0;s:22:\"administer permissions\";}','drupal_get_form','a:1:{i:0;s:16:\"user_admin_roles\";}','',15,4,1,'admin/people/permissions','admin/people','Roles','t','','','a:0:{}',132,'List, edit, or add user roles.','',-5,'modules/user/user.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/people/permissions/roles/delete/%','a:1:{i:5;s:14:\"user_role_load\";}','','user_role_edit_access','a:1:{i:0;i:5;}','drupal_get_form','a:2:{i:0;s:30:\"user_admin_role_delete_confirm\";i:1;i:5;}','',62,6,0,'','admin/people/permissions/roles/delete/%','Delete role','t','','','a:0:{}',6,'','',0,'modules/user/user.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/people/permissions/roles/edit/%','a:1:{i:5;s:14:\"user_role_load\";}','','user_role_edit_access','a:1:{i:0;i:5;}','drupal_get_form','a:2:{i:0;s:15:\"user_admin_role\";i:1;i:5;}','',62,6,0,'','admin/people/permissions/roles/edit/%','Edit role','t','','','a:0:{}',6,'','',0,'modules/user/user.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/reports','','','user_access','a:1:{i:0;s:19:\"access site reports\";}','system_admin_menu_block_page','a:0:{}','',3,2,0,'','admin/reports','Reports','t','','','a:0:{}',6,'View reports, updates, and errors.','left',5,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/reports/access-denied','','','user_access','a:1:{i:0;s:19:\"access site reports\";}','dblog_top','a:1:{i:0;s:13:\"access denied\";}','',7,3,0,'','admin/reports/access-denied','Top \'access denied\' errors','t','','','a:0:{}',6,'View \'access denied\' errors (403s).','',0,'modules/dblog/dblog.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/reports/dblog','','','user_access','a:1:{i:0;s:19:\"access site reports\";}','dblog_overview','a:0:{}','',7,3,0,'','admin/reports/dblog','Recent log messages','t','','','a:0:{}',6,'View events that have recently been logged.','',-1,'modules/dblog/dblog.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/reports/event/%','a:1:{i:3;N;}','','user_access','a:1:{i:0;s:19:\"access site reports\";}','dblog_event','a:1:{i:0;i:3;}','',14,4,0,'','admin/reports/event/%','Details','t','','','a:0:{}',6,'','',0,'modules/dblog/dblog.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/reports/fields','','','user_access','a:1:{i:0;s:24:\"administer content types\";}','field_ui_fields_list','a:0:{}','',7,3,0,'','admin/reports/fields','Field list','t','','','a:0:{}',6,'Overview of fields on all entity types.','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/reports/page-not-found','','','user_access','a:1:{i:0;s:19:\"access site reports\";}','dblog_top','a:1:{i:0;s:14:\"page not found\";}','',7,3,0,'','admin/reports/page-not-found','Top \'page not found\' errors','t','','','a:0:{}',6,'View \'page not found\' errors (404s).','',0,'modules/dblog/dblog.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/reports/search','','','user_access','a:1:{i:0;s:19:\"access site reports\";}','dblog_top','a:1:{i:0;s:6:\"search\";}','',7,3,0,'','admin/reports/search','Top search phrases','t','','','a:0:{}',6,'View most popular search phrases.','',0,'modules/dblog/dblog.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/reports/status','','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','system_status','a:0:{}','',7,3,0,'','admin/reports/status','Status report','t','','','a:0:{}',6,'Get a status report about your site\'s operation and any detected problems.','',-60,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/reports/status/php','','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','system_php','a:0:{}','',15,4,0,'','admin/reports/status/php','PHP','t','','','a:0:{}',0,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/reports/status/rebuild','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','drupal_get_form','a:1:{i:0;s:30:\"node_configure_rebuild_confirm\";}','',15,4,0,'','admin/reports/status/rebuild','Rebuild permissions','t','','','a:0:{}',0,'','',0,'modules/node/node.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/reports/status/run-cron','','','user_access','a:1:{i:0;s:29:\"administer site configuration\";}','system_run_cron','a:0:{}','',15,4,0,'','admin/reports/status/run-cron','Run cron','t','','','a:0:{}',0,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','system_admin_menu_block_page','a:0:{}','',3,2,0,'','admin/structure','Structure','t','','','a:0:{}',6,'Administer blocks, content types, menus, etc.','right',-8,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block','','','user_access','a:1:{i:0;s:17:\"administer blocks\";}','block_admin_display','a:1:{i:0;s:9:\"lc3_clean\";}','',7,3,0,'','admin/structure/block','Blocks','t','','','a:0:{}',6,'Configure what block content appears in your site\'s sidebars and other regions.','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/add','','','user_access','a:1:{i:0;s:17:\"administer blocks\";}','drupal_get_form','a:1:{i:0;s:20:\"block_add_block_form\";}','',15,4,1,'admin/structure/block','admin/structure/block','Add block','t','','','a:0:{}',388,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/demo/bartik','','','_block_themes_access','a:1:{i:0;O:8:\"stdClass\":11:{s:8:\"filename\";s:25:\"themes/bartik/bartik.info\";s:4:\"name\";s:6:\"bartik\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"1\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:18:{s:4:\"name\";s:6:\"Bartik\";s:11:\"description\";s:48:\"A flexible, recolorable theme with many regions.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:2:{s:3:\"all\";a:3:{s:14:\"css/layout.css\";s:28:\"themes/bartik/css/layout.css\";s:13:\"css/style.css\";s:27:\"themes/bartik/css/style.css\";s:14:\"css/colors.css\";s:28:\"themes/bartik/css/colors.css\";}s:5:\"print\";a:1:{s:13:\"css/print.css\";s:27:\"themes/bartik/css/print.css\";}}s:7:\"regions\";a:20:{s:6:\"header\";s:6:\"Header\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:11:\"highlighted\";s:11:\"Highlighted\";s:8:\"featured\";s:8:\"Featured\";s:7:\"content\";s:7:\"Content\";s:13:\"sidebar_first\";s:13:\"Sidebar first\";s:14:\"sidebar_second\";s:14:\"Sidebar second\";s:14:\"triptych_first\";s:14:\"Triptych first\";s:15:\"triptych_middle\";s:15:\"Triptych middle\";s:13:\"triptych_last\";s:13:\"Triptych last\";s:18:\"footer_firstcolumn\";s:19:\"Footer first column\";s:19:\"footer_secondcolumn\";s:20:\"Footer second column\";s:18:\"footer_thirdcolumn\";s:19:\"Footer third column\";s:19:\"footer_fourthcolumn\";s:20:\"Footer fourth column\";s:6:\"footer\";s:6:\"Footer\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"settings\";a:1:{s:20:\"shortcut_module_link\";s:1:\"0\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:28:\"themes/bartik/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:11:\"stylesheets\";a:2:{s:3:\"all\";a:3:{s:14:\"css/layout.css\";s:28:\"themes/bartik/css/layout.css\";s:13:\"css/style.css\";s:27:\"themes/bartik/css/style.css\";s:14:\"css/colors.css\";s:28:\"themes/bartik/css/colors.css\";}s:5:\"print\";a:1:{s:13:\"css/print.css\";s:27:\"themes/bartik/css/print.css\";}}s:6:\"engine\";s:11:\"phptemplate\";}}','block_admin_demo','a:1:{i:0;s:6:\"bartik\";}','',31,5,0,'','admin/structure/block/demo/bartik','Bartik','t','','_block_custom_theme','a:1:{i:0;s:6:\"bartik\";}',0,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/demo/garland','','','_block_themes_access','a:1:{i:0;O:8:\"stdClass\":11:{s:8:\"filename\";s:27:\"themes/garland/garland.info\";s:4:\"name\";s:7:\"garland\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"0\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:18:{s:4:\"name\";s:7:\"Garland\";s:11:\"description\";s:111:\"A multi-column theme which can be configured to modify colors and switch between fixed and fluid width layouts.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:2:{s:3:\"all\";a:1:{s:9:\"style.css\";s:24:\"themes/garland/style.css\";}s:5:\"print\";a:1:{s:9:\"print.css\";s:24:\"themes/garland/print.css\";}}s:8:\"settings\";a:1:{s:13:\"garland_width\";s:5:\"fluid\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:7:\"regions\";a:12:{s:13:\"sidebar_first\";s:12:\"Left sidebar\";s:14:\"sidebar_second\";s:13:\"Right sidebar\";s:7:\"content\";s:7:\"Content\";s:6:\"header\";s:6:\"Header\";s:6:\"footer\";s:6:\"Footer\";s:11:\"highlighted\";s:11:\"Highlighted\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:29:\"themes/garland/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:11:\"stylesheets\";a:2:{s:3:\"all\";a:1:{s:9:\"style.css\";s:24:\"themes/garland/style.css\";}s:5:\"print\";a:1:{s:9:\"print.css\";s:24:\"themes/garland/print.css\";}}s:6:\"engine\";s:11:\"phptemplate\";}}','block_admin_demo','a:1:{i:0;s:7:\"garland\";}','',31,5,0,'','admin/structure/block/demo/garland','Garland','t','','_block_custom_theme','a:1:{i:0;s:7:\"garland\";}',0,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/demo/lc3_clean','','','_block_themes_access','a:1:{i:0;O:8:\"stdClass\":12:{s:8:\"filename\";s:41:\"sites/all/themes/lc3_clean/lc3_clean.info\";s:4:\"name\";s:9:\"lc3_clean\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"1\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:14:{s:4:\"name\";s:15:\"Clean LC3 theme\";s:11:\"description\";s:36:\"A clean theme for LiteCommerce shops\";s:10:\"screenshot\";s:41:\"sites/all/themes/lc3_clean/screenshot.png\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:1:{s:3:\"all\";a:5:{s:13:\"css/reset.css\";s:40:\"sites/all/themes/lc3_clean/css/reset.css\";s:14:\"css/layout.css\";s:41:\"sites/all/themes/lc3_clean/css/layout.css\";s:13:\"css/style.css\";s:40:\"sites/all/themes/lc3_clean/css/style.css\";s:11:\"css/lc3.css\";s:38:\"sites/all/themes/lc3_clean/css/lc3.css\";s:16:\"system.menus.css\";s:43:\"sites/all/themes/lc3_clean/system.menus.css\";}}s:7:\"scripts\";a:3:{s:20:\"js/jquery.blockUI.js\";s:47:\"sites/all/themes/lc3_clean/js/jquery.blockUI.js\";s:11:\"js/popup.js\";s:38:\"sites/all/themes/lc3_clean/js/popup.js\";s:17:\"js/topMessages.js\";s:44:\"sites/all/themes/lc3_clean/js/topMessages.js\";}s:7:\"regions\";a:13:{s:6:\"header\";s:6:\"Header\";s:6:\"search\";s:6:\"Search\";s:4:\"help\";s:4:\"Help\";s:11:\"highlighted\";s:11:\"Highlighted\";s:13:\"sidebar_first\";s:14:\"Sidebar (left)\";s:7:\"content\";s:7:\"Content\";s:14:\"sidebar_second\";s:15:\"Sidebar (right)\";s:6:\"footer\";s:6:\"Footer\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"settings\";a:2:{s:26:\"theme_social_link_facebook\";s:12:\"litecommerce\";s:25:\"theme_social_link_twitter\";s:12:\"litecommerce\";}s:6:\"engine\";s:11:\"phptemplate\";s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:3:\"php\";s:5:\"5.2.4\";s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:11:\"stylesheets\";a:1:{s:3:\"all\";a:5:{s:13:\"css/reset.css\";s:40:\"sites/all/themes/lc3_clean/css/reset.css\";s:14:\"css/layout.css\";s:41:\"sites/all/themes/lc3_clean/css/layout.css\";s:13:\"css/style.css\";s:40:\"sites/all/themes/lc3_clean/css/style.css\";s:11:\"css/lc3.css\";s:38:\"sites/all/themes/lc3_clean/css/lc3.css\";s:16:\"system.menus.css\";s:43:\"sites/all/themes/lc3_clean/system.menus.css\";}}s:7:\"scripts\";a:3:{s:20:\"js/jquery.blockUI.js\";s:47:\"sites/all/themes/lc3_clean/js/jquery.blockUI.js\";s:11:\"js/popup.js\";s:38:\"sites/all/themes/lc3_clean/js/popup.js\";s:17:\"js/topMessages.js\";s:44:\"sites/all/themes/lc3_clean/js/topMessages.js\";}s:6:\"engine\";s:11:\"phptemplate\";}}','block_admin_demo','a:1:{i:0;s:9:\"lc3_clean\";}','',31,5,0,'','admin/structure/block/demo/lc3_clean','Clean LC3 theme','t','','_block_custom_theme','a:1:{i:0;s:9:\"lc3_clean\";}',0,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/demo/seven','','','_block_themes_access','a:1:{i:0;O:8:\"stdClass\":11:{s:8:\"filename\";s:23:\"themes/seven/seven.info\";s:4:\"name\";s:5:\"seven\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"1\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:18:{s:4:\"name\";s:5:\"Seven\";s:11:\"description\";s:65:\"A simple one-column, tableless, fluid width administration theme.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:1:{s:6:\"screen\";a:2:{s:9:\"reset.css\";s:22:\"themes/seven/reset.css\";s:9:\"style.css\";s:22:\"themes/seven/style.css\";}}s:8:\"settings\";a:1:{s:20:\"shortcut_module_link\";s:1:\"1\";}s:7:\"regions\";a:8:{s:7:\"content\";s:7:\"Content\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:13:\"sidebar_first\";s:13:\"First sidebar\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:14:\"regions_hidden\";a:3:{i:0;s:13:\"sidebar_first\";i:1;s:8:\"page_top\";i:2;s:11:\"page_bottom\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:27:\"themes/seven/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:11:\"stylesheets\";a:1:{s:6:\"screen\";a:2:{s:9:\"reset.css\";s:22:\"themes/seven/reset.css\";s:9:\"style.css\";s:22:\"themes/seven/style.css\";}}s:6:\"engine\";s:11:\"phptemplate\";}}','block_admin_demo','a:1:{i:0;s:5:\"seven\";}','',31,5,0,'','admin/structure/block/demo/seven','Seven','t','','_block_custom_theme','a:1:{i:0;s:5:\"seven\";}',0,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/demo/stark','','','_block_themes_access','a:1:{i:0;O:8:\"stdClass\":11:{s:8:\"filename\";s:23:\"themes/stark/stark.info\";s:4:\"name\";s:5:\"stark\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"0\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:17:{s:4:\"name\";s:5:\"Stark\";s:11:\"description\";s:208:\"This theme demonstrates Drupal\'s default HTML markup and CSS styles. To learn how to build your own theme and override Drupal\'s default code, see the <a href=\"http://drupal.org/theme-guide\">Theming Guide</a>.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:10:\"layout.css\";s:23:\"themes/stark/layout.css\";}}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:7:\"regions\";a:12:{s:13:\"sidebar_first\";s:12:\"Left sidebar\";s:14:\"sidebar_second\";s:13:\"Right sidebar\";s:7:\"content\";s:7:\"Content\";s:6:\"header\";s:6:\"Header\";s:6:\"footer\";s:6:\"Footer\";s:11:\"highlighted\";s:11:\"Highlighted\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:27:\"themes/stark/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:10:\"layout.css\";s:23:\"themes/stark/layout.css\";}}s:6:\"engine\";s:11:\"phptemplate\";}}','block_admin_demo','a:1:{i:0;s:5:\"stark\";}','',31,5,0,'','admin/structure/block/demo/stark','Stark','t','','_block_custom_theme','a:1:{i:0;s:5:\"stark\";}',0,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/demo/test_theme','','','_block_themes_access','a:1:{i:0;O:8:\"stdClass\":11:{s:8:\"filename\";s:39:\"themes/tests/test_theme/test_theme.info\";s:4:\"name\";s:10:\"test_theme\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"0\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:17:{s:4:\"name\";s:10:\"Test theme\";s:11:\"description\";s:34:\"Theme for testing the theme system\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:15:\"system.base.css\";s:39:\"themes/tests/test_theme/system.base.css\";}}s:7:\"version\";s:3:\"7.0\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:7:\"regions\";a:12:{s:13:\"sidebar_first\";s:12:\"Left sidebar\";s:14:\"sidebar_second\";s:13:\"Right sidebar\";s:7:\"content\";s:7:\"Content\";s:6:\"header\";s:6:\"Header\";s:6:\"footer\";s:6:\"Footer\";s:11:\"highlighted\";s:11:\"Highlighted\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:38:\"themes/tests/test_theme/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:15:\"system.base.css\";s:39:\"themes/tests/test_theme/system.base.css\";}}s:6:\"engine\";s:11:\"phptemplate\";}}','block_admin_demo','a:1:{i:0;s:10:\"test_theme\";}','',31,5,0,'','admin/structure/block/demo/test_theme','Test theme','t','','_block_custom_theme','a:1:{i:0;s:10:\"test_theme\";}',0,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/demo/update_test_basetheme','','','_block_themes_access','a:1:{i:0;O:8:\"stdClass\":10:{s:8:\"filename\";s:61:\"themes/tests/update_test_basetheme/update_test_basetheme.info\";s:4:\"name\";s:21:\"update_test_basetheme\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"0\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:17:{s:4:\"name\";s:22:\"Update test base theme\";s:11:\"description\";s:63:\"Test theme which acts as a base theme for other test subthemes.\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"version\";s:3:\"7.0\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:7:\"regions\";a:12:{s:13:\"sidebar_first\";s:12:\"Left sidebar\";s:14:\"sidebar_second\";s:13:\"Right sidebar\";s:7:\"content\";s:7:\"Content\";s:6:\"header\";s:6:\"Header\";s:6:\"footer\";s:6:\"Footer\";s:11:\"highlighted\";s:11:\"Highlighted\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:49:\"themes/tests/update_test_basetheme/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:11:\"stylesheets\";a:0:{}s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:6:\"engine\";s:11:\"phptemplate\";}}','block_admin_demo','a:1:{i:0;s:21:\"update_test_basetheme\";}','',31,5,0,'','admin/structure/block/demo/update_test_basetheme','Update test base theme','t','','_block_custom_theme','a:1:{i:0;s:21:\"update_test_basetheme\";}',0,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/demo/update_test_subtheme','','','_block_themes_access','a:1:{i:0;O:8:\"stdClass\":11:{s:8:\"filename\";s:59:\"themes/tests/update_test_subtheme/update_test_subtheme.info\";s:4:\"name\";s:20:\"update_test_subtheme\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"0\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:18:{s:4:\"name\";s:20:\"Update test subtheme\";s:11:\"description\";s:62:\"Test theme which uses update_test_basetheme as the base theme.\";s:4:\"core\";s:3:\"7.x\";s:10:\"base theme\";s:21:\"update_test_basetheme\";s:6:\"hidden\";b:1;s:7:\"version\";s:3:\"7.0\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:7:\"regions\";a:12:{s:13:\"sidebar_first\";s:12:\"Left sidebar\";s:14:\"sidebar_second\";s:13:\"Right sidebar\";s:7:\"content\";s:7:\"Content\";s:6:\"header\";s:6:\"Header\";s:6:\"footer\";s:6:\"Footer\";s:11:\"highlighted\";s:11:\"Highlighted\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:48:\"themes/tests/update_test_subtheme/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:11:\"stylesheets\";a:0:{}s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:6:\"engine\";s:11:\"phptemplate\";s:10:\"base_theme\";s:21:\"update_test_basetheme\";}}','block_admin_demo','a:1:{i:0;s:20:\"update_test_subtheme\";}','',31,5,0,'','admin/structure/block/demo/update_test_subtheme','Update test subtheme','t','','_block_custom_theme','a:1:{i:0;s:20:\"update_test_subtheme\";}',0,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/list/bartik','','','_block_themes_access','a:1:{i:0;O:8:\"stdClass\":11:{s:8:\"filename\";s:25:\"themes/bartik/bartik.info\";s:4:\"name\";s:6:\"bartik\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"1\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:18:{s:4:\"name\";s:6:\"Bartik\";s:11:\"description\";s:48:\"A flexible, recolorable theme with many regions.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:2:{s:3:\"all\";a:3:{s:14:\"css/layout.css\";s:28:\"themes/bartik/css/layout.css\";s:13:\"css/style.css\";s:27:\"themes/bartik/css/style.css\";s:14:\"css/colors.css\";s:28:\"themes/bartik/css/colors.css\";}s:5:\"print\";a:1:{s:13:\"css/print.css\";s:27:\"themes/bartik/css/print.css\";}}s:7:\"regions\";a:20:{s:6:\"header\";s:6:\"Header\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:11:\"highlighted\";s:11:\"Highlighted\";s:8:\"featured\";s:8:\"Featured\";s:7:\"content\";s:7:\"Content\";s:13:\"sidebar_first\";s:13:\"Sidebar first\";s:14:\"sidebar_second\";s:14:\"Sidebar second\";s:14:\"triptych_first\";s:14:\"Triptych first\";s:15:\"triptych_middle\";s:15:\"Triptych middle\";s:13:\"triptych_last\";s:13:\"Triptych last\";s:18:\"footer_firstcolumn\";s:19:\"Footer first column\";s:19:\"footer_secondcolumn\";s:20:\"Footer second column\";s:18:\"footer_thirdcolumn\";s:19:\"Footer third column\";s:19:\"footer_fourthcolumn\";s:20:\"Footer fourth column\";s:6:\"footer\";s:6:\"Footer\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"settings\";a:1:{s:20:\"shortcut_module_link\";s:1:\"0\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:28:\"themes/bartik/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:11:\"stylesheets\";a:2:{s:3:\"all\";a:3:{s:14:\"css/layout.css\";s:28:\"themes/bartik/css/layout.css\";s:13:\"css/style.css\";s:27:\"themes/bartik/css/style.css\";s:14:\"css/colors.css\";s:28:\"themes/bartik/css/colors.css\";}s:5:\"print\";a:1:{s:13:\"css/print.css\";s:27:\"themes/bartik/css/print.css\";}}s:6:\"engine\";s:11:\"phptemplate\";}}','block_admin_display','a:1:{i:0;s:6:\"bartik\";}','',31,5,1,'admin/structure/block','admin/structure/block','Bartik','t','','','a:0:{}',132,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/list/bartik/add','','','user_access','a:1:{i:0;s:17:\"administer blocks\";}','drupal_get_form','a:1:{i:0;s:20:\"block_add_block_form\";}','',63,6,1,'admin/structure/block/list/bartik','admin/structure/block','Add block','t','','','a:0:{}',388,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/list/garland','','','_block_themes_access','a:1:{i:0;O:8:\"stdClass\":11:{s:8:\"filename\";s:27:\"themes/garland/garland.info\";s:4:\"name\";s:7:\"garland\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"0\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:18:{s:4:\"name\";s:7:\"Garland\";s:11:\"description\";s:111:\"A multi-column theme which can be configured to modify colors and switch between fixed and fluid width layouts.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:2:{s:3:\"all\";a:1:{s:9:\"style.css\";s:24:\"themes/garland/style.css\";}s:5:\"print\";a:1:{s:9:\"print.css\";s:24:\"themes/garland/print.css\";}}s:8:\"settings\";a:1:{s:13:\"garland_width\";s:5:\"fluid\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:7:\"regions\";a:12:{s:13:\"sidebar_first\";s:12:\"Left sidebar\";s:14:\"sidebar_second\";s:13:\"Right sidebar\";s:7:\"content\";s:7:\"Content\";s:6:\"header\";s:6:\"Header\";s:6:\"footer\";s:6:\"Footer\";s:11:\"highlighted\";s:11:\"Highlighted\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:29:\"themes/garland/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:11:\"stylesheets\";a:2:{s:3:\"all\";a:1:{s:9:\"style.css\";s:24:\"themes/garland/style.css\";}s:5:\"print\";a:1:{s:9:\"print.css\";s:24:\"themes/garland/print.css\";}}s:6:\"engine\";s:11:\"phptemplate\";}}','block_admin_display','a:1:{i:0;s:7:\"garland\";}','',31,5,1,'admin/structure/block','admin/structure/block','Garland','t','','','a:0:{}',132,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/list/garland/add','','','user_access','a:1:{i:0;s:17:\"administer blocks\";}','drupal_get_form','a:1:{i:0;s:20:\"block_add_block_form\";}','',63,6,1,'admin/structure/block/list/garland','admin/structure/block','Add block','t','','','a:0:{}',388,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/list/lc3_clean','','','_block_themes_access','a:1:{i:0;O:8:\"stdClass\":12:{s:8:\"filename\";s:41:\"sites/all/themes/lc3_clean/lc3_clean.info\";s:4:\"name\";s:9:\"lc3_clean\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"1\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:14:{s:4:\"name\";s:15:\"Clean LC3 theme\";s:11:\"description\";s:36:\"A clean theme for LiteCommerce shops\";s:10:\"screenshot\";s:41:\"sites/all/themes/lc3_clean/screenshot.png\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:1:{s:3:\"all\";a:5:{s:13:\"css/reset.css\";s:40:\"sites/all/themes/lc3_clean/css/reset.css\";s:14:\"css/layout.css\";s:41:\"sites/all/themes/lc3_clean/css/layout.css\";s:13:\"css/style.css\";s:40:\"sites/all/themes/lc3_clean/css/style.css\";s:11:\"css/lc3.css\";s:38:\"sites/all/themes/lc3_clean/css/lc3.css\";s:16:\"system.menus.css\";s:43:\"sites/all/themes/lc3_clean/system.menus.css\";}}s:7:\"scripts\";a:3:{s:20:\"js/jquery.blockUI.js\";s:47:\"sites/all/themes/lc3_clean/js/jquery.blockUI.js\";s:11:\"js/popup.js\";s:38:\"sites/all/themes/lc3_clean/js/popup.js\";s:17:\"js/topMessages.js\";s:44:\"sites/all/themes/lc3_clean/js/topMessages.js\";}s:7:\"regions\";a:13:{s:6:\"header\";s:6:\"Header\";s:6:\"search\";s:6:\"Search\";s:4:\"help\";s:4:\"Help\";s:11:\"highlighted\";s:11:\"Highlighted\";s:13:\"sidebar_first\";s:14:\"Sidebar (left)\";s:7:\"content\";s:7:\"Content\";s:14:\"sidebar_second\";s:15:\"Sidebar (right)\";s:6:\"footer\";s:6:\"Footer\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"settings\";a:2:{s:26:\"theme_social_link_facebook\";s:12:\"litecommerce\";s:25:\"theme_social_link_twitter\";s:12:\"litecommerce\";}s:6:\"engine\";s:11:\"phptemplate\";s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:3:\"php\";s:5:\"5.2.4\";s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:11:\"stylesheets\";a:1:{s:3:\"all\";a:5:{s:13:\"css/reset.css\";s:40:\"sites/all/themes/lc3_clean/css/reset.css\";s:14:\"css/layout.css\";s:41:\"sites/all/themes/lc3_clean/css/layout.css\";s:13:\"css/style.css\";s:40:\"sites/all/themes/lc3_clean/css/style.css\";s:11:\"css/lc3.css\";s:38:\"sites/all/themes/lc3_clean/css/lc3.css\";s:16:\"system.menus.css\";s:43:\"sites/all/themes/lc3_clean/system.menus.css\";}}s:7:\"scripts\";a:3:{s:20:\"js/jquery.blockUI.js\";s:47:\"sites/all/themes/lc3_clean/js/jquery.blockUI.js\";s:11:\"js/popup.js\";s:38:\"sites/all/themes/lc3_clean/js/popup.js\";s:17:\"js/topMessages.js\";s:44:\"sites/all/themes/lc3_clean/js/topMessages.js\";}s:6:\"engine\";s:11:\"phptemplate\";}}','block_admin_display','a:1:{i:0;s:9:\"lc3_clean\";}','',31,5,1,'admin/structure/block','admin/structure/block','Clean LC3 theme','t','','','a:0:{}',140,'','',-10,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/list/seven','','','_block_themes_access','a:1:{i:0;O:8:\"stdClass\":11:{s:8:\"filename\";s:23:\"themes/seven/seven.info\";s:4:\"name\";s:5:\"seven\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"1\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:18:{s:4:\"name\";s:5:\"Seven\";s:11:\"description\";s:65:\"A simple one-column, tableless, fluid width administration theme.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:1:{s:6:\"screen\";a:2:{s:9:\"reset.css\";s:22:\"themes/seven/reset.css\";s:9:\"style.css\";s:22:\"themes/seven/style.css\";}}s:8:\"settings\";a:1:{s:20:\"shortcut_module_link\";s:1:\"1\";}s:7:\"regions\";a:8:{s:7:\"content\";s:7:\"Content\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:13:\"sidebar_first\";s:13:\"First sidebar\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:14:\"regions_hidden\";a:3:{i:0;s:13:\"sidebar_first\";i:1;s:8:\"page_top\";i:2;s:11:\"page_bottom\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:27:\"themes/seven/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:11:\"stylesheets\";a:1:{s:6:\"screen\";a:2:{s:9:\"reset.css\";s:22:\"themes/seven/reset.css\";s:9:\"style.css\";s:22:\"themes/seven/style.css\";}}s:6:\"engine\";s:11:\"phptemplate\";}}','block_admin_display','a:1:{i:0;s:5:\"seven\";}','',31,5,1,'admin/structure/block','admin/structure/block','Seven','t','','','a:0:{}',132,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/list/seven/add','','','user_access','a:1:{i:0;s:17:\"administer blocks\";}','drupal_get_form','a:1:{i:0;s:20:\"block_add_block_form\";}','',63,6,1,'admin/structure/block/list/seven','admin/structure/block','Add block','t','','','a:0:{}',388,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/list/stark','','','_block_themes_access','a:1:{i:0;O:8:\"stdClass\":11:{s:8:\"filename\";s:23:\"themes/stark/stark.info\";s:4:\"name\";s:5:\"stark\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"0\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:17:{s:4:\"name\";s:5:\"Stark\";s:11:\"description\";s:208:\"This theme demonstrates Drupal\'s default HTML markup and CSS styles. To learn how to build your own theme and override Drupal\'s default code, see the <a href=\"http://drupal.org/theme-guide\">Theming Guide</a>.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:10:\"layout.css\";s:23:\"themes/stark/layout.css\";}}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:7:\"regions\";a:12:{s:13:\"sidebar_first\";s:12:\"Left sidebar\";s:14:\"sidebar_second\";s:13:\"Right sidebar\";s:7:\"content\";s:7:\"Content\";s:6:\"header\";s:6:\"Header\";s:6:\"footer\";s:6:\"Footer\";s:11:\"highlighted\";s:11:\"Highlighted\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:27:\"themes/stark/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:10:\"layout.css\";s:23:\"themes/stark/layout.css\";}}s:6:\"engine\";s:11:\"phptemplate\";}}','block_admin_display','a:1:{i:0;s:5:\"stark\";}','',31,5,1,'admin/structure/block','admin/structure/block','Stark','t','','','a:0:{}',132,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/list/stark/add','','','user_access','a:1:{i:0;s:17:\"administer blocks\";}','drupal_get_form','a:1:{i:0;s:20:\"block_add_block_form\";}','',63,6,1,'admin/structure/block/list/stark','admin/structure/block','Add block','t','','','a:0:{}',388,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/list/test_theme','','','_block_themes_access','a:1:{i:0;O:8:\"stdClass\":11:{s:8:\"filename\";s:39:\"themes/tests/test_theme/test_theme.info\";s:4:\"name\";s:10:\"test_theme\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"0\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:17:{s:4:\"name\";s:10:\"Test theme\";s:11:\"description\";s:34:\"Theme for testing the theme system\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:15:\"system.base.css\";s:39:\"themes/tests/test_theme/system.base.css\";}}s:7:\"version\";s:3:\"7.0\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:7:\"regions\";a:12:{s:13:\"sidebar_first\";s:12:\"Left sidebar\";s:14:\"sidebar_second\";s:13:\"Right sidebar\";s:7:\"content\";s:7:\"Content\";s:6:\"header\";s:6:\"Header\";s:6:\"footer\";s:6:\"Footer\";s:11:\"highlighted\";s:11:\"Highlighted\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:38:\"themes/tests/test_theme/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:15:\"system.base.css\";s:39:\"themes/tests/test_theme/system.base.css\";}}s:6:\"engine\";s:11:\"phptemplate\";}}','block_admin_display','a:1:{i:0;s:10:\"test_theme\";}','',31,5,1,'admin/structure/block','admin/structure/block','Test theme','t','','','a:0:{}',132,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/list/test_theme/add','','','user_access','a:1:{i:0;s:17:\"administer blocks\";}','drupal_get_form','a:1:{i:0;s:20:\"block_add_block_form\";}','',63,6,1,'admin/structure/block/list/test_theme','admin/structure/block','Add block','t','','','a:0:{}',388,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/list/update_test_basetheme','','','_block_themes_access','a:1:{i:0;O:8:\"stdClass\":10:{s:8:\"filename\";s:61:\"themes/tests/update_test_basetheme/update_test_basetheme.info\";s:4:\"name\";s:21:\"update_test_basetheme\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"0\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:17:{s:4:\"name\";s:22:\"Update test base theme\";s:11:\"description\";s:63:\"Test theme which acts as a base theme for other test subthemes.\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"version\";s:3:\"7.0\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:7:\"regions\";a:12:{s:13:\"sidebar_first\";s:12:\"Left sidebar\";s:14:\"sidebar_second\";s:13:\"Right sidebar\";s:7:\"content\";s:7:\"Content\";s:6:\"header\";s:6:\"Header\";s:6:\"footer\";s:6:\"Footer\";s:11:\"highlighted\";s:11:\"Highlighted\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:49:\"themes/tests/update_test_basetheme/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:11:\"stylesheets\";a:0:{}s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:6:\"engine\";s:11:\"phptemplate\";}}','block_admin_display','a:1:{i:0;s:21:\"update_test_basetheme\";}','',31,5,1,'admin/structure/block','admin/structure/block','Update test base theme','t','','','a:0:{}',132,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/list/update_test_basetheme/add','','','user_access','a:1:{i:0;s:17:\"administer blocks\";}','drupal_get_form','a:1:{i:0;s:20:\"block_add_block_form\";}','',63,6,1,'admin/structure/block/list/update_test_basetheme','admin/structure/block','Add block','t','','','a:0:{}',388,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/list/update_test_subtheme','','','_block_themes_access','a:1:{i:0;O:8:\"stdClass\":11:{s:8:\"filename\";s:59:\"themes/tests/update_test_subtheme/update_test_subtheme.info\";s:4:\"name\";s:20:\"update_test_subtheme\";s:4:\"type\";s:5:\"theme\";s:5:\"owner\";s:45:\"themes/engines/phptemplate/phptemplate.engine\";s:6:\"status\";s:1:\"0\";s:9:\"bootstrap\";s:1:\"0\";s:14:\"schema_version\";s:2:\"-1\";s:6:\"weight\";s:1:\"0\";s:4:\"info\";a:18:{s:4:\"name\";s:20:\"Update test subtheme\";s:11:\"description\";s:62:\"Test theme which uses update_test_basetheme as the base theme.\";s:4:\"core\";s:3:\"7.x\";s:10:\"base theme\";s:21:\"update_test_basetheme\";s:6:\"hidden\";b:1;s:7:\"version\";s:3:\"7.0\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:7:\"regions\";a:12:{s:13:\"sidebar_first\";s:12:\"Left sidebar\";s:14:\"sidebar_second\";s:13:\"Right sidebar\";s:7:\"content\";s:7:\"Content\";s:6:\"header\";s:6:\"Header\";s:6:\"footer\";s:6:\"Footer\";s:11:\"highlighted\";s:11:\"Highlighted\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:48:\"themes/tests/update_test_subtheme/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:11:\"stylesheets\";a:0:{}s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}s:6:\"engine\";s:11:\"phptemplate\";s:10:\"base_theme\";s:21:\"update_test_basetheme\";}}','block_admin_display','a:1:{i:0;s:20:\"update_test_subtheme\";}','',31,5,1,'admin/structure/block','admin/structure/block','Update test subtheme','t','','','a:0:{}',132,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/list/update_test_subtheme/add','','','user_access','a:1:{i:0;s:17:\"administer blocks\";}','drupal_get_form','a:1:{i:0;s:20:\"block_add_block_form\";}','',63,6,1,'admin/structure/block/list/update_test_subtheme','admin/structure/block','Add block','t','','','a:0:{}',388,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/manage/%/%','a:2:{i:4;N;i:5;N;}','','user_access','a:1:{i:0;s:17:\"administer blocks\";}','drupal_get_form','a:3:{i:0;s:21:\"block_admin_configure\";i:1;i:4;i:2;i:5;}','',60,6,0,'','admin/structure/block/manage/%/%','Configure block','t','','','a:0:{}',6,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/manage/%/%/configure','a:2:{i:4;N;i:5;N;}','','user_access','a:1:{i:0;s:17:\"administer blocks\";}','drupal_get_form','a:3:{i:0;s:21:\"block_admin_configure\";i:1;i:4;i:2;i:5;}','',121,7,2,'admin/structure/block/manage/%/%','admin/structure/block/manage/%/%','Configure block','t','','','a:0:{}',140,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/block/manage/%/%/delete','a:2:{i:4;N;i:5;N;}','','user_access','a:1:{i:0;s:17:\"administer blocks\";}','drupal_get_form','a:3:{i:0;s:25:\"block_custom_block_delete\";i:1;i:4;i:2;i:5;}','',121,7,0,'admin/structure/block/manage/%/%','admin/structure/block/manage/%/%','Delete block','t','','','a:0:{}',132,'','',0,'modules/block/block.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/contact','','','user_access','a:1:{i:0;s:24:\"administer contact forms\";}','contact_category_list','a:0:{}','',7,3,0,'','admin/structure/contact','Contact form','t','','','a:0:{}',6,'Create a system contact form and set up categories for the form to use.','',0,'modules/contact/contact.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/contact/add','','','user_access','a:1:{i:0;s:24:\"administer contact forms\";}','drupal_get_form','a:1:{i:0;s:26:\"contact_category_edit_form\";}','',15,4,1,'admin/structure/contact','admin/structure/contact','Add category','t','','','a:0:{}',388,'','',1,'modules/contact/contact.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/contact/delete/%','a:1:{i:4;s:12:\"contact_load\";}','','user_access','a:1:{i:0;s:24:\"administer contact forms\";}','drupal_get_form','a:2:{i:0;s:28:\"contact_category_delete_form\";i:1;i:4;}','',30,5,0,'','admin/structure/contact/delete/%','Delete contact','t','','','a:0:{}',6,'','',0,'modules/contact/contact.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/contact/edit/%','a:1:{i:4;s:12:\"contact_load\";}','','user_access','a:1:{i:0;s:24:\"administer contact forms\";}','drupal_get_form','a:2:{i:0;s:26:\"contact_category_edit_form\";i:1;i:4;}','',30,5,0,'','admin/structure/contact/edit/%','Edit contact category','t','','','a:0:{}',6,'','',0,'modules/contact/contact.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/forum','','','user_access','a:1:{i:0;s:17:\"administer forums\";}','drupal_get_form','a:1:{i:0;s:14:\"forum_overview\";}','',7,3,0,'','admin/structure/forum','Forums','t','','','a:0:{}',6,'Control forum hierarchy settings.','',0,'modules/forum/forum.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/forum/add/container','','','user_access','a:1:{i:0;s:17:\"administer forums\";}','forum_form_main','a:1:{i:0;s:9:\"container\";}','',31,5,1,'admin/structure/forum','admin/structure/forum','Add container','t','','','a:0:{}',388,'','',0,'modules/forum/forum.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/forum/add/forum','','','user_access','a:1:{i:0;s:17:\"administer forums\";}','forum_form_main','a:1:{i:0;s:5:\"forum\";}','',31,5,1,'admin/structure/forum','admin/structure/forum','Add forum','t','','','a:0:{}',388,'','',0,'modules/forum/forum.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/forum/edit/container/%','a:1:{i:5;s:18:\"taxonomy_term_load\";}','','user_access','a:1:{i:0;s:17:\"administer forums\";}','forum_form_main','a:2:{i:0;s:9:\"container\";i:1;i:5;}','',62,6,0,'','admin/structure/forum/edit/container/%','Edit container','t','','','a:0:{}',6,'','',0,'modules/forum/forum.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/forum/edit/forum/%','a:1:{i:5;s:18:\"taxonomy_term_load\";}','','user_access','a:1:{i:0;s:17:\"administer forums\";}','forum_form_main','a:2:{i:0;s:5:\"forum\";i:1;i:5;}','',62,6,0,'','admin/structure/forum/edit/forum/%','Edit forum','t','','','a:0:{}',6,'','',0,'modules/forum/forum.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/forum/list','','','user_access','a:1:{i:0;s:17:\"administer forums\";}','drupal_get_form','a:1:{i:0;s:14:\"forum_overview\";}','',15,4,1,'admin/structure/forum','admin/structure/forum','List','t','','','a:0:{}',140,'','',-10,'modules/forum/forum.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/forum/settings','','','user_access','a:1:{i:0;s:17:\"administer forums\";}','drupal_get_form','a:1:{i:0;s:20:\"forum_admin_settings\";}','',15,4,1,'admin/structure/forum','admin/structure/forum','Settings','t','','','a:0:{}',132,'','',5,'modules/forum/forum.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/menu','','','user_access','a:1:{i:0;s:15:\"administer menu\";}','menu_overview_page','a:0:{}','',7,3,0,'','admin/structure/menu','Menus','t','','','a:0:{}',6,'Add new menus to your site, edit existing menus, and rename and reorganize menu links.','',0,'modules/menu/menu.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/menu/add','','','user_access','a:1:{i:0;s:15:\"administer menu\";}','drupal_get_form','a:2:{i:0;s:14:\"menu_edit_menu\";i:1;s:3:\"add\";}','',15,4,1,'admin/structure/menu','admin/structure/menu','Add menu','t','','','a:0:{}',388,'','',0,'modules/menu/menu.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/menu/item/%/delete','a:1:{i:4;s:14:\"menu_link_load\";}','','user_access','a:1:{i:0;s:15:\"administer menu\";}','menu_item_delete_page','a:1:{i:0;i:4;}','',61,6,0,'','admin/structure/menu/item/%/delete','Delete menu link','t','','','a:0:{}',6,'','',0,'modules/menu/menu.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/menu/item/%/edit','a:1:{i:4;s:14:\"menu_link_load\";}','','user_access','a:1:{i:0;s:15:\"administer menu\";}','drupal_get_form','a:4:{i:0;s:14:\"menu_edit_item\";i:1;s:4:\"edit\";i:2;i:4;i:3;N;}','',61,6,0,'','admin/structure/menu/item/%/edit','Edit menu link','t','','','a:0:{}',6,'','',0,'modules/menu/menu.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/menu/item/%/reset','a:1:{i:4;s:14:\"menu_link_load\";}','','user_access','a:1:{i:0;s:15:\"administer menu\";}','drupal_get_form','a:2:{i:0;s:23:\"menu_reset_item_confirm\";i:1;i:4;}','',61,6,0,'','admin/structure/menu/item/%/reset','Reset menu link','t','','','a:0:{}',6,'','',0,'modules/menu/menu.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/menu/list','','','user_access','a:1:{i:0;s:15:\"administer menu\";}','menu_overview_page','a:0:{}','',15,4,1,'admin/structure/menu','admin/structure/menu','List menus','t','','','a:0:{}',140,'','',-10,'modules/menu/menu.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/menu/manage/%','a:1:{i:4;s:9:\"menu_load\";}','','user_access','a:1:{i:0;s:15:\"administer menu\";}','drupal_get_form','a:2:{i:0;s:18:\"menu_overview_form\";i:1;i:4;}','',30,5,0,'','admin/structure/menu/manage/%','Customize menu','menu_overview_title','a:1:{i:0;i:4;}','','a:0:{}',6,'','',0,'modules/menu/menu.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/menu/manage/%/add','a:1:{i:4;s:9:\"menu_load\";}','','user_access','a:1:{i:0;s:15:\"administer menu\";}','drupal_get_form','a:4:{i:0;s:14:\"menu_edit_item\";i:1;s:3:\"add\";i:2;N;i:3;i:4;}','',61,6,1,'admin/structure/menu/manage/%','admin/structure/menu/manage/%','Add link','t','','','a:0:{}',388,'','',0,'modules/menu/menu.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/menu/manage/%/delete','a:1:{i:4;s:9:\"menu_load\";}','','user_access','a:1:{i:0;s:15:\"administer menu\";}','menu_delete_menu_page','a:1:{i:0;i:4;}','',61,6,0,'','admin/structure/menu/manage/%/delete','Delete menu','t','','','a:0:{}',6,'','',0,'modules/menu/menu.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/menu/manage/%/edit','a:1:{i:4;s:9:\"menu_load\";}','','user_access','a:1:{i:0;s:15:\"administer menu\";}','drupal_get_form','a:3:{i:0;s:14:\"menu_edit_menu\";i:1;s:4:\"edit\";i:2;i:4;}','',61,6,3,'admin/structure/menu/manage/%','admin/structure/menu/manage/%','Edit menu','t','','','a:0:{}',132,'','',0,'modules/menu/menu.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/menu/manage/%/list','a:1:{i:4;s:9:\"menu_load\";}','','user_access','a:1:{i:0;s:15:\"administer menu\";}','drupal_get_form','a:2:{i:0;s:18:\"menu_overview_form\";i:1;i:4;}','',61,6,3,'admin/structure/menu/manage/%','admin/structure/menu/manage/%','List links','t','','','a:0:{}',140,'','',-10,'modules/menu/menu.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/menu/parents','','','user_access','a:1:{i:0;b:1;}','menu_parent_options_js','a:0:{}','',15,4,0,'','admin/structure/menu/parents','Parent menu items','t','','','a:0:{}',0,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/menu/settings','','','user_access','a:1:{i:0;s:15:\"administer menu\";}','drupal_get_form','a:1:{i:0;s:14:\"menu_configure\";}','',15,4,1,'admin/structure/menu','admin/structure/menu','Settings','t','','','a:0:{}',132,'','',5,'modules/menu/menu.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/taxonomy','','','user_access','a:1:{i:0;s:19:\"administer taxonomy\";}','drupal_get_form','a:1:{i:0;s:30:\"taxonomy_overview_vocabularies\";}','',7,3,0,'','admin/structure/taxonomy','Taxonomy','t','','','a:0:{}',6,'Manage tagging, categorization, and classification of your content.','',0,'modules/taxonomy/taxonomy.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/taxonomy/%','a:1:{i:3;s:37:\"taxonomy_vocabulary_machine_name_load\";}','','user_access','a:1:{i:0;s:19:\"administer taxonomy\";}','drupal_get_form','a:2:{i:0;s:23:\"taxonomy_overview_terms\";i:1;i:3;}','',14,4,0,'','admin/structure/taxonomy/%','','taxonomy_admin_vocabulary_title_callback','a:1:{i:0;i:3;}','','a:0:{}',6,'','',0,'modules/taxonomy/taxonomy.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/taxonomy/%/add','a:1:{i:3;s:37:\"taxonomy_vocabulary_machine_name_load\";}','','user_access','a:1:{i:0;s:19:\"administer taxonomy\";}','drupal_get_form','a:3:{i:0;s:18:\"taxonomy_form_term\";i:1;a:0:{}i:2;i:3;}','',29,5,1,'admin/structure/taxonomy/%','admin/structure/taxonomy/%','Add term','t','','','a:0:{}',388,'','',0,'modules/taxonomy/taxonomy.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/taxonomy/%/display','a:1:{i:3;s:37:\"taxonomy_vocabulary_machine_name_load\";}','','user_access','a:1:{i:0;s:19:\"administer taxonomy\";}','drupal_get_form','a:4:{i:0;s:30:\"field_ui_display_overview_form\";i:1;s:13:\"taxonomy_term\";i:2;i:3;i:3;s:7:\"default\";}','',29,5,1,'admin/structure/taxonomy/%','admin/structure/taxonomy/%','Manage display','t','','','a:0:{}',132,'','',2,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/taxonomy/%/display/default','a:1:{i:3;s:37:\"taxonomy_vocabulary_machine_name_load\";}','','_field_ui_view_mode_menu_access','a:5:{i:0;s:13:\"taxonomy_term\";i:1;i:3;i:2;s:7:\"default\";i:3;s:11:\"user_access\";i:4;s:19:\"administer taxonomy\";}','drupal_get_form','a:4:{i:0;s:30:\"field_ui_display_overview_form\";i:1;s:13:\"taxonomy_term\";i:2;i:3;i:3;s:7:\"default\";}','',59,6,1,'admin/structure/taxonomy/%/display','admin/structure/taxonomy/%','Default','t','','','a:0:{}',140,'','',-10,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/taxonomy/%/display/full','a:1:{i:3;s:37:\"taxonomy_vocabulary_machine_name_load\";}','','_field_ui_view_mode_menu_access','a:5:{i:0;s:13:\"taxonomy_term\";i:1;i:3;i:2;s:4:\"full\";i:3;s:11:\"user_access\";i:4;s:19:\"administer taxonomy\";}','drupal_get_form','a:4:{i:0;s:30:\"field_ui_display_overview_form\";i:1;s:13:\"taxonomy_term\";i:2;i:3;i:3;s:4:\"full\";}','',59,6,1,'admin/structure/taxonomy/%/display','admin/structure/taxonomy/%','Taxonomy term page','t','','','a:0:{}',132,'','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/taxonomy/%/edit','a:1:{i:3;s:37:\"taxonomy_vocabulary_machine_name_load\";}','','user_access','a:1:{i:0;s:19:\"administer taxonomy\";}','drupal_get_form','a:2:{i:0;s:24:\"taxonomy_form_vocabulary\";i:1;i:3;}','',29,5,1,'admin/structure/taxonomy/%','admin/structure/taxonomy/%','Edit','t','','','a:0:{}',132,'','',-10,'modules/taxonomy/taxonomy.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/taxonomy/%/fields','a:1:{i:3;s:37:\"taxonomy_vocabulary_machine_name_load\";}','','user_access','a:1:{i:0;s:19:\"administer taxonomy\";}','drupal_get_form','a:3:{i:0;s:28:\"field_ui_field_overview_form\";i:1;s:13:\"taxonomy_term\";i:2;i:3;}','',29,5,1,'admin/structure/taxonomy/%','admin/structure/taxonomy/%','Manage fields','t','','','a:0:{}',132,'','',1,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/taxonomy/%/fields/%','a:2:{i:3;a:1:{s:37:\"taxonomy_vocabulary_machine_name_load\";a:4:{i:0;s:13:\"taxonomy_term\";i:1;i:3;i:2;s:1:\"3\";i:3;s:4:\"%map\";}}i:5;a:1:{s:18:\"field_ui_menu_load\";a:4:{i:0;s:13:\"taxonomy_term\";i:1;i:3;i:2;s:1:\"3\";i:3;s:4:\"%map\";}}}','','user_access','a:1:{i:0;s:19:\"administer taxonomy\";}','drupal_get_form','a:2:{i:0;s:24:\"field_ui_field_edit_form\";i:1;i:5;}','',58,6,0,'','admin/structure/taxonomy/%/fields/%','','field_ui_menu_title','a:1:{i:0;i:5;}','','a:0:{}',6,'','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/taxonomy/%/fields/%/delete','a:2:{i:3;a:1:{s:37:\"taxonomy_vocabulary_machine_name_load\";a:4:{i:0;s:13:\"taxonomy_term\";i:1;i:3;i:2;s:1:\"3\";i:3;s:4:\"%map\";}}i:5;a:1:{s:18:\"field_ui_menu_load\";a:4:{i:0;s:13:\"taxonomy_term\";i:1;i:3;i:2;s:1:\"3\";i:3;s:4:\"%map\";}}}','','user_access','a:1:{i:0;s:19:\"administer taxonomy\";}','drupal_get_form','a:2:{i:0;s:26:\"field_ui_field_delete_form\";i:1;i:5;}','',117,7,1,'admin/structure/taxonomy/%/fields/%','admin/structure/taxonomy/%/fields/%','Delete','t','','','a:0:{}',132,'','',10,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/taxonomy/%/fields/%/edit','a:2:{i:3;a:1:{s:37:\"taxonomy_vocabulary_machine_name_load\";a:4:{i:0;s:13:\"taxonomy_term\";i:1;i:3;i:2;s:1:\"3\";i:3;s:4:\"%map\";}}i:5;a:1:{s:18:\"field_ui_menu_load\";a:4:{i:0;s:13:\"taxonomy_term\";i:1;i:3;i:2;s:1:\"3\";i:3;s:4:\"%map\";}}}','','user_access','a:1:{i:0;s:19:\"administer taxonomy\";}','drupal_get_form','a:2:{i:0;s:24:\"field_ui_field_edit_form\";i:1;i:5;}','',117,7,1,'admin/structure/taxonomy/%/fields/%','admin/structure/taxonomy/%/fields/%','Edit','t','','','a:0:{}',140,'','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/taxonomy/%/fields/%/field-settings','a:2:{i:3;a:1:{s:37:\"taxonomy_vocabulary_machine_name_load\";a:4:{i:0;s:13:\"taxonomy_term\";i:1;i:3;i:2;s:1:\"3\";i:3;s:4:\"%map\";}}i:5;a:1:{s:18:\"field_ui_menu_load\";a:4:{i:0;s:13:\"taxonomy_term\";i:1;i:3;i:2;s:1:\"3\";i:3;s:4:\"%map\";}}}','','user_access','a:1:{i:0;s:19:\"administer taxonomy\";}','drupal_get_form','a:2:{i:0;s:28:\"field_ui_field_settings_form\";i:1;i:5;}','',117,7,1,'admin/structure/taxonomy/%/fields/%','admin/structure/taxonomy/%/fields/%','Field settings','t','','','a:0:{}',132,'','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/taxonomy/%/fields/%/widget-type','a:2:{i:3;a:1:{s:37:\"taxonomy_vocabulary_machine_name_load\";a:4:{i:0;s:13:\"taxonomy_term\";i:1;i:3;i:2;s:1:\"3\";i:3;s:4:\"%map\";}}i:5;a:1:{s:18:\"field_ui_menu_load\";a:4:{i:0;s:13:\"taxonomy_term\";i:1;i:3;i:2;s:1:\"3\";i:3;s:4:\"%map\";}}}','','user_access','a:1:{i:0;s:19:\"administer taxonomy\";}','drupal_get_form','a:2:{i:0;s:25:\"field_ui_widget_type_form\";i:1;i:5;}','',117,7,1,'admin/structure/taxonomy/%/fields/%','admin/structure/taxonomy/%/fields/%','Widget type','t','','','a:0:{}',132,'','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/taxonomy/%/list','a:1:{i:3;s:37:\"taxonomy_vocabulary_machine_name_load\";}','','user_access','a:1:{i:0;s:19:\"administer taxonomy\";}','drupal_get_form','a:2:{i:0;s:23:\"taxonomy_overview_terms\";i:1;i:3;}','',29,5,1,'admin/structure/taxonomy/%','admin/structure/taxonomy/%','List','t','','','a:0:{}',140,'','',-20,'modules/taxonomy/taxonomy.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/taxonomy/add','','','user_access','a:1:{i:0;s:19:\"administer taxonomy\";}','drupal_get_form','a:1:{i:0;s:24:\"taxonomy_form_vocabulary\";}','',15,4,1,'admin/structure/taxonomy','admin/structure/taxonomy','Add vocabulary','t','','','a:0:{}',388,'','',0,'modules/taxonomy/taxonomy.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/taxonomy/list','','','user_access','a:1:{i:0;s:19:\"administer taxonomy\";}','drupal_get_form','a:1:{i:0;s:30:\"taxonomy_overview_vocabularies\";}','',15,4,1,'admin/structure/taxonomy','admin/structure/taxonomy','List','t','','','a:0:{}',140,'','',-10,'modules/taxonomy/taxonomy.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types','','','user_access','a:1:{i:0;s:24:\"administer content types\";}','node_overview_types','a:0:{}','',7,3,0,'','admin/structure/types','Content types','t','','','a:0:{}',6,'Manage content types, including default status, front page promotion, comment settings, etc.','',0,'modules/node/content_types.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/add','','','user_access','a:1:{i:0;s:24:\"administer content types\";}','drupal_get_form','a:1:{i:0;s:14:\"node_type_form\";}','',15,4,1,'admin/structure/types','admin/structure/types','Add content type','t','','','a:0:{}',388,'','',0,'modules/node/content_types.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/list','','','user_access','a:1:{i:0;s:24:\"administer content types\";}','node_overview_types','a:0:{}','',15,4,1,'admin/structure/types','admin/structure/types','List','t','','','a:0:{}',140,'','',-10,'modules/node/content_types.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%','a:1:{i:4;s:14:\"node_type_load\";}','','user_access','a:1:{i:0;s:24:\"administer content types\";}','drupal_get_form','a:2:{i:0;s:14:\"node_type_form\";i:1;i:4;}','',30,5,0,'','admin/structure/types/manage/%','Edit content type','node_type_page_title','a:1:{i:0;i:4;}','','a:0:{}',6,'','',0,'modules/node/content_types.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/comment/display','a:1:{i:4;s:22:\"comment_node_type_load\";}','','user_access','a:1:{i:0;s:24:\"administer content types\";}','drupal_get_form','a:4:{i:0;s:30:\"field_ui_display_overview_form\";i:1;s:7:\"comment\";i:2;i:4;i:3;s:7:\"default\";}','',123,7,1,'admin/structure/types/manage/%','admin/structure/types/manage/%','Comment display','t','','','a:0:{}',132,'','',4,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/comment/display/default','a:1:{i:4;s:22:\"comment_node_type_load\";}','','_field_ui_view_mode_menu_access','a:5:{i:0;s:7:\"comment\";i:1;i:4;i:2;s:7:\"default\";i:3;s:11:\"user_access\";i:4;s:24:\"administer content types\";}','drupal_get_form','a:4:{i:0;s:30:\"field_ui_display_overview_form\";i:1;s:7:\"comment\";i:2;i:4;i:3;s:7:\"default\";}','',247,8,1,'admin/structure/types/manage/%/comment/display','admin/structure/types/manage/%','Default','t','','','a:0:{}',140,'','',-10,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/comment/display/full','a:1:{i:4;s:22:\"comment_node_type_load\";}','','_field_ui_view_mode_menu_access','a:5:{i:0;s:7:\"comment\";i:1;i:4;i:2;s:4:\"full\";i:3;s:11:\"user_access\";i:4;s:24:\"administer content types\";}','drupal_get_form','a:4:{i:0;s:30:\"field_ui_display_overview_form\";i:1;s:7:\"comment\";i:2;i:4;i:3;s:4:\"full\";}','',247,8,1,'admin/structure/types/manage/%/comment/display','admin/structure/types/manage/%','Full comment','t','','','a:0:{}',132,'','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/comment/fields','a:1:{i:4;s:22:\"comment_node_type_load\";}','','user_access','a:1:{i:0;s:24:\"administer content types\";}','drupal_get_form','a:3:{i:0;s:28:\"field_ui_field_overview_form\";i:1;s:7:\"comment\";i:2;i:4;}','',123,7,1,'admin/structure/types/manage/%','admin/structure/types/manage/%','Comment fields','t','','','a:0:{}',132,'','',3,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/comment/fields/%','a:2:{i:4;a:1:{s:22:\"comment_node_type_load\";a:4:{i:0;s:7:\"comment\";i:1;i:4;i:2;s:1:\"4\";i:3;s:4:\"%map\";}}i:7;a:1:{s:18:\"field_ui_menu_load\";a:4:{i:0;s:7:\"comment\";i:1;i:4;i:2;s:1:\"4\";i:3;s:4:\"%map\";}}}','','user_access','a:1:{i:0;s:24:\"administer content types\";}','drupal_get_form','a:2:{i:0;s:24:\"field_ui_field_edit_form\";i:1;i:7;}','',246,8,0,'','admin/structure/types/manage/%/comment/fields/%','','field_ui_menu_title','a:1:{i:0;i:7;}','','a:0:{}',6,'','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/comment/fields/%/delete','a:2:{i:4;a:1:{s:22:\"comment_node_type_load\";a:4:{i:0;s:7:\"comment\";i:1;i:4;i:2;s:1:\"4\";i:3;s:4:\"%map\";}}i:7;a:1:{s:18:\"field_ui_menu_load\";a:4:{i:0;s:7:\"comment\";i:1;i:4;i:2;s:1:\"4\";i:3;s:4:\"%map\";}}}','','user_access','a:1:{i:0;s:24:\"administer content types\";}','drupal_get_form','a:2:{i:0;s:26:\"field_ui_field_delete_form\";i:1;i:7;}','',493,9,1,'admin/structure/types/manage/%/comment/fields/%','admin/structure/types/manage/%/comment/fields/%','Delete','t','','','a:0:{}',132,'','',10,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/comment/fields/%/edit','a:2:{i:4;a:1:{s:22:\"comment_node_type_load\";a:4:{i:0;s:7:\"comment\";i:1;i:4;i:2;s:1:\"4\";i:3;s:4:\"%map\";}}i:7;a:1:{s:18:\"field_ui_menu_load\";a:4:{i:0;s:7:\"comment\";i:1;i:4;i:2;s:1:\"4\";i:3;s:4:\"%map\";}}}','','user_access','a:1:{i:0;s:24:\"administer content types\";}','drupal_get_form','a:2:{i:0;s:24:\"field_ui_field_edit_form\";i:1;i:7;}','',493,9,1,'admin/structure/types/manage/%/comment/fields/%','admin/structure/types/manage/%/comment/fields/%','Edit','t','','','a:0:{}',140,'','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/comment/fields/%/field-settings','a:2:{i:4;a:1:{s:22:\"comment_node_type_load\";a:4:{i:0;s:7:\"comment\";i:1;i:4;i:2;s:1:\"4\";i:3;s:4:\"%map\";}}i:7;a:1:{s:18:\"field_ui_menu_load\";a:4:{i:0;s:7:\"comment\";i:1;i:4;i:2;s:1:\"4\";i:3;s:4:\"%map\";}}}','','user_access','a:1:{i:0;s:24:\"administer content types\";}','drupal_get_form','a:2:{i:0;s:28:\"field_ui_field_settings_form\";i:1;i:7;}','',493,9,1,'admin/structure/types/manage/%/comment/fields/%','admin/structure/types/manage/%/comment/fields/%','Field settings','t','','','a:0:{}',132,'','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/comment/fields/%/widget-type','a:2:{i:4;a:1:{s:22:\"comment_node_type_load\";a:4:{i:0;s:7:\"comment\";i:1;i:4;i:2;s:1:\"4\";i:3;s:4:\"%map\";}}i:7;a:1:{s:18:\"field_ui_menu_load\";a:4:{i:0;s:7:\"comment\";i:1;i:4;i:2;s:1:\"4\";i:3;s:4:\"%map\";}}}','','user_access','a:1:{i:0;s:24:\"administer content types\";}','drupal_get_form','a:2:{i:0;s:25:\"field_ui_widget_type_form\";i:1;i:7;}','',493,9,1,'admin/structure/types/manage/%/comment/fields/%','admin/structure/types/manage/%/comment/fields/%','Widget type','t','','','a:0:{}',132,'','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/delete','a:1:{i:4;s:14:\"node_type_load\";}','','user_access','a:1:{i:0;s:24:\"administer content types\";}','drupal_get_form','a:2:{i:0;s:24:\"node_type_delete_confirm\";i:1;i:4;}','',61,6,0,'','admin/structure/types/manage/%/delete','Delete','t','','','a:0:{}',6,'','',0,'modules/node/content_types.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/display','a:1:{i:4;s:14:\"node_type_load\";}','','user_access','a:1:{i:0;s:24:\"administer content types\";}','drupal_get_form','a:4:{i:0;s:30:\"field_ui_display_overview_form\";i:1;s:4:\"node\";i:2;i:4;i:3;s:7:\"default\";}','',61,6,1,'admin/structure/types/manage/%','admin/structure/types/manage/%','Manage display','t','','','a:0:{}',132,'','',2,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/display/default','a:1:{i:4;s:14:\"node_type_load\";}','','_field_ui_view_mode_menu_access','a:5:{i:0;s:4:\"node\";i:1;i:4;i:2;s:7:\"default\";i:3;s:11:\"user_access\";i:4;s:24:\"administer content types\";}','drupal_get_form','a:4:{i:0;s:30:\"field_ui_display_overview_form\";i:1;s:4:\"node\";i:2;i:4;i:3;s:7:\"default\";}','',123,7,1,'admin/structure/types/manage/%/display','admin/structure/types/manage/%','Default','t','','','a:0:{}',140,'','',-10,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/display/full','a:1:{i:4;s:14:\"node_type_load\";}','','_field_ui_view_mode_menu_access','a:5:{i:0;s:4:\"node\";i:1;i:4;i:2;s:4:\"full\";i:3;s:11:\"user_access\";i:4;s:24:\"administer content types\";}','drupal_get_form','a:4:{i:0;s:30:\"field_ui_display_overview_form\";i:1;s:4:\"node\";i:2;i:4;i:3;s:4:\"full\";}','',123,7,1,'admin/structure/types/manage/%/display','admin/structure/types/manage/%','Full content','t','','','a:0:{}',132,'','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/display/rss','a:1:{i:4;s:14:\"node_type_load\";}','','_field_ui_view_mode_menu_access','a:5:{i:0;s:4:\"node\";i:1;i:4;i:2;s:3:\"rss\";i:3;s:11:\"user_access\";i:4;s:24:\"administer content types\";}','drupal_get_form','a:4:{i:0;s:30:\"field_ui_display_overview_form\";i:1;s:4:\"node\";i:2;i:4;i:3;s:3:\"rss\";}','',123,7,1,'admin/structure/types/manage/%/display','admin/structure/types/manage/%','RSS','t','','','a:0:{}',132,'','',2,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/display/search_index','a:1:{i:4;s:14:\"node_type_load\";}','','_field_ui_view_mode_menu_access','a:5:{i:0;s:4:\"node\";i:1;i:4;i:2;s:12:\"search_index\";i:3;s:11:\"user_access\";i:4;s:24:\"administer content types\";}','drupal_get_form','a:4:{i:0;s:30:\"field_ui_display_overview_form\";i:1;s:4:\"node\";i:2;i:4;i:3;s:12:\"search_index\";}','',123,7,1,'admin/structure/types/manage/%/display','admin/structure/types/manage/%','Search index','t','','','a:0:{}',132,'','',3,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/display/search_result','a:1:{i:4;s:14:\"node_type_load\";}','','_field_ui_view_mode_menu_access','a:5:{i:0;s:4:\"node\";i:1;i:4;i:2;s:13:\"search_result\";i:3;s:11:\"user_access\";i:4;s:24:\"administer content types\";}','drupal_get_form','a:4:{i:0;s:30:\"field_ui_display_overview_form\";i:1;s:4:\"node\";i:2;i:4;i:3;s:13:\"search_result\";}','',123,7,1,'admin/structure/types/manage/%/display','admin/structure/types/manage/%','Search result','t','','','a:0:{}',132,'','',4,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/display/teaser','a:1:{i:4;s:14:\"node_type_load\";}','','_field_ui_view_mode_menu_access','a:5:{i:0;s:4:\"node\";i:1;i:4;i:2;s:6:\"teaser\";i:3;s:11:\"user_access\";i:4;s:24:\"administer content types\";}','drupal_get_form','a:4:{i:0;s:30:\"field_ui_display_overview_form\";i:1;s:4:\"node\";i:2;i:4;i:3;s:6:\"teaser\";}','',123,7,1,'admin/structure/types/manage/%/display','admin/structure/types/manage/%','Teaser','t','','','a:0:{}',132,'','',1,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/edit','a:1:{i:4;s:14:\"node_type_load\";}','','user_access','a:1:{i:0;s:24:\"administer content types\";}','drupal_get_form','a:2:{i:0;s:14:\"node_type_form\";i:1;i:4;}','',61,6,1,'admin/structure/types/manage/%','admin/structure/types/manage/%','Edit','t','','','a:0:{}',140,'','',0,'modules/node/content_types.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/fields','a:1:{i:4;s:14:\"node_type_load\";}','','user_access','a:1:{i:0;s:24:\"administer content types\";}','drupal_get_form','a:3:{i:0;s:28:\"field_ui_field_overview_form\";i:1;s:4:\"node\";i:2;i:4;}','',61,6,1,'admin/structure/types/manage/%','admin/structure/types/manage/%','Manage fields','t','','','a:0:{}',132,'','',1,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/fields/%','a:2:{i:4;a:1:{s:14:\"node_type_load\";a:4:{i:0;s:4:\"node\";i:1;i:4;i:2;s:1:\"4\";i:3;s:4:\"%map\";}}i:6;a:1:{s:18:\"field_ui_menu_load\";a:4:{i:0;s:4:\"node\";i:1;i:4;i:2;s:1:\"4\";i:3;s:4:\"%map\";}}}','','user_access','a:1:{i:0;s:24:\"administer content types\";}','drupal_get_form','a:2:{i:0;s:24:\"field_ui_field_edit_form\";i:1;i:6;}','',122,7,0,'','admin/structure/types/manage/%/fields/%','','field_ui_menu_title','a:1:{i:0;i:6;}','','a:0:{}',6,'','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/fields/%/delete','a:2:{i:4;a:1:{s:14:\"node_type_load\";a:4:{i:0;s:4:\"node\";i:1;i:4;i:2;s:1:\"4\";i:3;s:4:\"%map\";}}i:6;a:1:{s:18:\"field_ui_menu_load\";a:4:{i:0;s:4:\"node\";i:1;i:4;i:2;s:1:\"4\";i:3;s:4:\"%map\";}}}','','user_access','a:1:{i:0;s:24:\"administer content types\";}','drupal_get_form','a:2:{i:0;s:26:\"field_ui_field_delete_form\";i:1;i:6;}','',245,8,1,'admin/structure/types/manage/%/fields/%','admin/structure/types/manage/%/fields/%','Delete','t','','','a:0:{}',132,'','',10,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/fields/%/edit','a:2:{i:4;a:1:{s:14:\"node_type_load\";a:4:{i:0;s:4:\"node\";i:1;i:4;i:2;s:1:\"4\";i:3;s:4:\"%map\";}}i:6;a:1:{s:18:\"field_ui_menu_load\";a:4:{i:0;s:4:\"node\";i:1;i:4;i:2;s:1:\"4\";i:3;s:4:\"%map\";}}}','','user_access','a:1:{i:0;s:24:\"administer content types\";}','drupal_get_form','a:2:{i:0;s:24:\"field_ui_field_edit_form\";i:1;i:6;}','',245,8,1,'admin/structure/types/manage/%/fields/%','admin/structure/types/manage/%/fields/%','Edit','t','','','a:0:{}',140,'','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/fields/%/field-settings','a:2:{i:4;a:1:{s:14:\"node_type_load\";a:4:{i:0;s:4:\"node\";i:1;i:4;i:2;s:1:\"4\";i:3;s:4:\"%map\";}}i:6;a:1:{s:18:\"field_ui_menu_load\";a:4:{i:0;s:4:\"node\";i:1;i:4;i:2;s:1:\"4\";i:3;s:4:\"%map\";}}}','','user_access','a:1:{i:0;s:24:\"administer content types\";}','drupal_get_form','a:2:{i:0;s:28:\"field_ui_field_settings_form\";i:1;i:6;}','',245,8,1,'admin/structure/types/manage/%/fields/%','admin/structure/types/manage/%/fields/%','Field settings','t','','','a:0:{}',132,'','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/structure/types/manage/%/fields/%/widget-type','a:2:{i:4;a:1:{s:14:\"node_type_load\";a:4:{i:0;s:4:\"node\";i:1;i:4;i:2;s:1:\"4\";i:3;s:4:\"%map\";}}i:6;a:1:{s:18:\"field_ui_menu_load\";a:4:{i:0;s:4:\"node\";i:1;i:4;i:2;s:1:\"4\";i:3;s:4:\"%map\";}}}','','user_access','a:1:{i:0;s:24:\"administer content types\";}','drupal_get_form','a:2:{i:0;s:25:\"field_ui_widget_type_form\";i:1;i:6;}','',245,8,1,'admin/structure/types/manage/%/fields/%','admin/structure/types/manage/%/fields/%','Widget type','t','','','a:0:{}',132,'','',0,'modules/field_ui/field_ui.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('admin/tasks','','','user_access','a:1:{i:0;s:27:\"access administration pages\";}','system_admin_menu_block_page','a:0:{}','',3,2,1,'admin','admin','Tasks','t','','','a:0:{}',140,'','',-20,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('batch','','','1','a:0:{}','system_batch_page','a:0:{}','',1,1,0,'','batch','','t','','_system_batch_theme','a:0:{}',0,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('blog','','','user_access','a:1:{i:0;s:14:\"access content\";}','blog_page_last','a:0:{}','',1,1,0,'','blog','Blogs','t','','','a:0:{}',20,'','',0,'modules/blog/blog.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('blog/%','a:1:{i:1;s:22:\"user_uid_optional_load\";}','a:1:{i:1;s:24:\"user_uid_optional_to_arg\";}','blog_page_user_access','a:1:{i:0;i:1;}','blog_page_user','a:1:{i:0;i:1;}','',2,2,0,'','blog/%','My blog','t','','','a:0:{}',6,'','',0,'modules/blog/blog.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('blog/%/feed','a:1:{i:1;s:9:\"user_load\";}','','blog_page_user_access','a:1:{i:0;i:1;}','blog_feed_user','a:1:{i:0;i:1;}','',5,3,0,'','blog/%/feed','Blogs','t','','','a:0:{}',0,'','',0,'modules/blog/blog.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('blog/feed','','','user_access','a:1:{i:0;s:14:\"access content\";}','blog_feed_last','a:0:{}','',3,2,0,'','blog/feed','Blogs','t','','','a:0:{}',0,'','',0,'modules/blog/blog.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('comment/%','a:1:{i:1;N;}','','user_access','a:1:{i:0;s:15:\"access comments\";}','comment_permalink','a:1:{i:0;i:1;}','',2,2,0,'','comment/%','Comment permalink','t','','','a:0:{}',6,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('comment/%/approve','a:1:{i:1;N;}','','user_access','a:1:{i:0;s:19:\"administer comments\";}','comment_approve','a:1:{i:0;i:1;}','',5,3,0,'','comment/%/approve','Approve','t','','','a:0:{}',6,'','',1,'modules/comment/comment.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('comment/%/delete','a:1:{i:1;N;}','','user_access','a:1:{i:0;s:19:\"administer comments\";}','comment_confirm_delete_page','a:1:{i:0;i:1;}','',5,3,1,'comment/%','comment/%','Delete','t','','','a:0:{}',132,'','',2,'modules/comment/comment.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('comment/%/edit','a:1:{i:1;s:12:\"comment_load\";}','','comment_access','a:2:{i:0;s:4:\"edit\";i:1;i:1;}','comment_edit_page','a:1:{i:0;i:1;}','',5,3,1,'comment/%','comment/%','Edit','t','','','a:0:{}',132,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('comment/%/view','a:1:{i:1;N;}','','user_access','a:1:{i:0;s:15:\"access comments\";}','comment_permalink','a:1:{i:0;i:1;}','',5,3,1,'comment/%','comment/%','View comment','t','','','a:0:{}',140,'','',-10,'');
INSERT INTO `drupal_menu_router` VALUES ('comment/reply/%','a:1:{i:2;s:9:\"node_load\";}','','node_access','a:2:{i:0;s:4:\"view\";i:1;i:2;}','comment_reply','a:1:{i:0;i:2;}','',6,3,0,'','comment/reply/%','Add new comment','t','','','a:0:{}',6,'','',0,'modules/comment/comment.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('contact','','','user_access','a:1:{i:0;s:29:\"access site-wide contact form\";}','drupal_get_form','a:1:{i:0;s:17:\"contact_site_form\";}','',1,1,0,'','contact','Contact','t','','','a:0:{}',20,'','',0,'modules/contact/contact.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('file/ajax','','','user_access','a:1:{i:0;s:14:\"access content\";}','file_ajax_upload','a:0:{}','ajax_deliver',3,2,0,'','file/ajax','','t','','ajax_base_page_theme','a:0:{}',0,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('file/progress','','','user_access','a:1:{i:0;s:14:\"access content\";}','file_ajax_progress','a:0:{}','ajax_deliver',3,2,0,'','file/progress','','t','','ajax_base_page_theme','a:0:{}',0,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('filter/tips','','','1','a:0:{}','filter_tips_long','a:0:{}','',3,2,0,'','filter/tips','Compose tips','t','','','a:0:{}',20,'','',0,'modules/filter/filter.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('forum','','','user_access','a:1:{i:0;s:14:\"access content\";}','forum_page','a:0:{}','',1,1,0,'','forum','Forums','t','','','a:0:{}',6,'','',0,'modules/forum/forum.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('forum/%','a:1:{i:1;s:16:\"forum_forum_load\";}','','user_access','a:1:{i:0;s:14:\"access content\";}','forum_page','a:1:{i:0;i:1;}','',2,2,0,'','forum/%','Forums','t','','','a:0:{}',6,'','',0,'modules/forum/forum.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('node','','','user_access','a:1:{i:0;s:14:\"access content\";}','node_page_default','a:0:{}','',1,1,0,'','node','','t','','','a:0:{}',0,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('node/%','a:1:{i:1;s:9:\"node_load\";}','','node_access','a:2:{i:0;s:4:\"view\";i:1;i:1;}','node_page_view','a:1:{i:0;i:1;}','',2,2,0,'','node/%','','node_page_title','a:1:{i:0;i:1;}','','a:0:{}',6,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('node/%/delete','a:1:{i:1;s:9:\"node_load\";}','','node_access','a:2:{i:0;s:6:\"delete\";i:1;i:1;}','drupal_get_form','a:2:{i:0;s:19:\"node_delete_confirm\";i:1;i:1;}','',5,3,2,'node/%','node/%','Delete','t','','','a:0:{}',132,'','',1,'modules/node/node.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('node/%/edit','a:1:{i:1;s:9:\"node_load\";}','','node_access','a:2:{i:0;s:6:\"update\";i:1;i:1;}','node_page_edit','a:1:{i:0;i:1;}','',5,3,3,'node/%','node/%','Edit','t','','','a:0:{}',132,'','',0,'modules/node/node.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('node/%/revisions','a:1:{i:1;s:9:\"node_load\";}','','_node_revision_access','a:1:{i:0;i:1;}','node_revision_overview','a:1:{i:0;i:1;}','',5,3,1,'node/%','node/%','Revisions','t','','','a:0:{}',132,'','',2,'modules/node/node.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('node/%/revisions/%/delete','a:2:{i:1;a:1:{s:9:\"node_load\";a:1:{i:0;i:3;}}i:3;N;}','','_node_revision_access','a:2:{i:0;i:1;i:1;s:6:\"delete\";}','drupal_get_form','a:2:{i:0;s:28:\"node_revision_delete_confirm\";i:1;i:1;}','',21,5,0,'','node/%/revisions/%/delete','Delete earlier revision','t','','','a:0:{}',6,'','',0,'modules/node/node.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('node/%/revisions/%/revert','a:2:{i:1;a:1:{s:9:\"node_load\";a:1:{i:0;i:3;}}i:3;N;}','','_node_revision_access','a:2:{i:0;i:1;i:1;s:6:\"update\";}','drupal_get_form','a:2:{i:0;s:28:\"node_revision_revert_confirm\";i:1;i:1;}','',21,5,0,'','node/%/revisions/%/revert','Revert to earlier revision','t','','','a:0:{}',6,'','',0,'modules/node/node.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('node/%/revisions/%/view','a:2:{i:1;a:1:{s:9:\"node_load\";a:1:{i:0;i:3;}}i:3;N;}','','_node_revision_access','a:1:{i:0;i:1;}','node_show','a:2:{i:0;i:1;i:1;b:1;}','',21,5,0,'','node/%/revisions/%/view','Revisions','t','','','a:0:{}',6,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('node/%/view','a:1:{i:1;s:9:\"node_load\";}','','node_access','a:2:{i:0;s:4:\"view\";i:1;i:1;}','node_page_view','a:1:{i:0;i:1;}','',5,3,1,'node/%','node/%','View','t','','','a:0:{}',140,'','',-10,'');
INSERT INTO `drupal_menu_router` VALUES ('node/add','','','_node_add_access','a:0:{}','node_add_page','a:0:{}','',3,2,0,'','node/add','Add content','t','','','a:0:{}',6,'','',0,'modules/node/node.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('node/add/article','','','node_access','a:2:{i:0;s:6:\"create\";i:1;s:7:\"article\";}','node_add','a:1:{i:0;s:7:\"article\";}','',7,3,0,'','node/add/article','Article','check_plain','','','a:0:{}',6,'Use <em>articles</em> for time-sensitive content like news, press releases or blog posts.','',0,'modules/node/node.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('node/add/blog','','','node_access','a:2:{i:0;s:6:\"create\";i:1;s:4:\"blog\";}','node_add','a:1:{i:0;s:4:\"blog\";}','',7,3,0,'','node/add/blog','Blog entry','check_plain','','','a:0:{}',6,'Use for multi-user blogs. Every user gets a personal blog.','',0,'modules/node/node.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('node/add/forum','','','node_access','a:2:{i:0;s:6:\"create\";i:1;s:5:\"forum\";}','node_add','a:1:{i:0;s:5:\"forum\";}','',7,3,0,'','node/add/forum','Forum topic','check_plain','','','a:0:{}',6,'A <em>forum topic</em> starts a new discussion thread within a forum.','',0,'modules/node/node.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('node/add/page','','','node_access','a:2:{i:0;s:6:\"create\";i:1;s:4:\"page\";}','node_add','a:1:{i:0;s:4:\"page\";}','',7,3,0,'','node/add/page','Basic page','check_plain','','','a:0:{}',6,'Use <em>basic pages</em> for your static content, such as an \'About us\' page.','',0,'modules/node/node.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('overlay-ajax/%','a:1:{i:1;N;}','','user_access','a:1:{i:0;s:14:\"access overlay\";}','overlay_ajax_render_region','a:1:{i:0;i:1;}','',2,2,0,'','overlay-ajax/%','','t','','','a:0:{}',0,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('overlay/dismiss-message','','','user_access','a:1:{i:0;s:14:\"access overlay\";}','overlay_user_dismiss_message','a:0:{}','',3,2,0,'','overlay/dismiss-message','','t','','','a:0:{}',0,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('print','','','user_access','a:1:{i:0;s:12:\"access print\";}','print_controller_html','a:0:{}','',1,1,0,'','print','Printer-friendly','t','','','a:0:{}',0,'','',0,'modules/print/print.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('print/print','','','0','a:0:{}','print_controller_html','a:0:{}','',3,2,0,'','print/print','','t','','','a:0:{}',6,'','',0,'modules/print/print.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('rss.xml','','','user_access','a:1:{i:0;s:14:\"access content\";}','node_feed','a:0:{}','',1,1,0,'','rss.xml','RSS feed','t','','','a:0:{}',0,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('search','','','search_is_active','a:0:{}','search_view','a:0:{}','',1,1,0,'','search','Search','t','','','a:0:{}',20,'','',0,'modules/search/search.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('search/node','','','_search_menu_access','a:1:{i:0;s:4:\"node\";}','search_view','a:2:{i:0;s:4:\"node\";i:1;s:0:\"\";}','',3,2,1,'search','search','Content','t','','','a:0:{}',132,'','',-10,'modules/search/search.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('search/node/%','a:1:{i:2;a:1:{s:14:\"menu_tail_load\";a:2:{i:0;s:4:\"%map\";i:1;s:6:\"%index\";}}}','a:1:{i:2;s:16:\"menu_tail_to_arg\";}','_search_menu_access','a:1:{i:0;s:4:\"node\";}','search_view','a:2:{i:0;s:4:\"node\";i:1;i:2;}','',6,3,1,'search/node','search/node/%','Content','t','','','a:0:{}',132,'','',0,'modules/search/search.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('search/user','','','_search_menu_access','a:1:{i:0;s:4:\"user\";}','search_view','a:2:{i:0;s:4:\"user\";i:1;s:0:\"\";}','',3,2,1,'search','search','Users','t','','','a:0:{}',132,'','',0,'modules/search/search.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('search/user/%','a:1:{i:2;a:1:{s:14:\"menu_tail_load\";a:2:{i:0;s:4:\"%map\";i:1;s:6:\"%index\";}}}','a:1:{i:2;s:16:\"menu_tail_to_arg\";}','_search_menu_access','a:1:{i:0;s:4:\"user\";}','search_view','a:2:{i:0;s:4:\"user\";i:1;i:2;}','',6,3,1,'search/node','search/node/%','Users','t','','','a:0:{}',132,'','',0,'modules/search/search.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('sites/default/files/styles/%','a:1:{i:4;s:16:\"image_style_load\";}','','1','a:0:{}','image_style_deliver','a:1:{i:0;i:4;}','',30,5,0,'','sites/default/files/styles/%','Generate image style','t','','','a:0:{}',0,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('store/%','a:1:{i:1;N;}','','lc_connector_check_controller_access','a:0:{}','lcConnectorGetControllerContent','a:0:{}','',2,2,0,'','store/%','Store','lcConnectorGetControllerTitle','','','a:0:{}',0,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('system/ajax','','','1','a:0:{}','ajax_form_callback','a:0:{}','ajax_deliver',3,2,0,'','system/ajax','AHAH callback','t','','ajax_base_page_theme','a:0:{}',0,'','',0,'includes/form.inc');
INSERT INTO `drupal_menu_router` VALUES ('system/files','','','1','a:0:{}','file_download','a:1:{i:0;s:7:\"private\";}','',3,2,0,'','system/files','File download','t','','','a:0:{}',0,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('system/files/styles/%','a:1:{i:3;s:16:\"image_style_load\";}','','1','a:0:{}','image_style_deliver','a:1:{i:0;i:3;}','',14,4,0,'','system/files/styles/%','Generate image style','t','','','a:0:{}',0,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('system/temporary','','','1','a:0:{}','file_download','a:1:{i:0;s:9:\"temporary\";}','',3,2,0,'','system/temporary','Temporary files','t','','','a:0:{}',0,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('system/timezone','','','1','a:0:{}','system_timezone','a:0:{}','',3,2,0,'','system/timezone','Time zone','t','','','a:0:{}',0,'','',0,'modules/system/system.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('taxonomy/autocomplete','','','user_access','a:1:{i:0;s:14:\"access content\";}','taxonomy_autocomplete','a:0:{}','',3,2,0,'','taxonomy/autocomplete','Autocomplete taxonomy','t','','','a:0:{}',0,'','',0,'modules/taxonomy/taxonomy.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('taxonomy/term/%','a:1:{i:2;s:18:\"taxonomy_term_load\";}','','user_access','a:1:{i:0;s:14:\"access content\";}','taxonomy_term_page','a:1:{i:0;i:2;}','',6,3,0,'','taxonomy/term/%','Taxonomy term','taxonomy_term_title','a:1:{i:0;i:2;}','','a:0:{}',6,'','',0,'modules/taxonomy/taxonomy.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('taxonomy/term/%/edit','a:1:{i:2;s:18:\"taxonomy_term_load\";}','','taxonomy_term_edit_access','a:1:{i:0;i:2;}','drupal_get_form','a:2:{i:0;s:18:\"taxonomy_form_term\";i:1;i:2;}','',13,4,1,'taxonomy/term/%','taxonomy/term/%','Edit','t','','','a:0:{}',132,'','',10,'modules/taxonomy/taxonomy.admin.inc');
INSERT INTO `drupal_menu_router` VALUES ('taxonomy/term/%/feed','a:1:{i:2;s:18:\"taxonomy_term_load\";}','','user_access','a:1:{i:0;s:14:\"access content\";}','taxonomy_term_feed','a:1:{i:0;i:2;}','',13,4,0,'','taxonomy/term/%/feed','Taxonomy term','taxonomy_term_title','a:1:{i:0;i:2;}','','a:0:{}',0,'','',0,'modules/taxonomy/taxonomy.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('taxonomy/term/%/view','a:1:{i:2;s:18:\"taxonomy_term_load\";}','','user_access','a:1:{i:0;s:14:\"access content\";}','taxonomy_term_page','a:1:{i:0;i:2;}','',13,4,1,'taxonomy/term/%','taxonomy/term/%','View','t','','','a:0:{}',140,'','',0,'modules/taxonomy/taxonomy.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('token/autocomplete/%','a:1:{i:2;s:15:\"token_type_load\";}','','1','a:0:{}','token_autocomplete_token','a:1:{i:0;i:2;}','',6,3,0,'','token/autocomplete/%','','t','','','a:0:{}',0,'','',0,'modules/token/token.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('toolbar/toggle','','','user_access','a:1:{i:0;s:14:\"access toolbar\";}','toolbar_toggle_page','a:0:{}','',3,2,0,'','toolbar/toggle','Toggle drawer visibility','t','','','a:0:{}',0,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('user','','','1','a:0:{}','user_page','a:0:{}','',1,1,0,'','user','User account','user_menu_title','','','a:0:{}',6,'','',-10,'modules/user/user.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('user/%','a:1:{i:1;s:9:\"user_load\";}','','user_view_access','a:1:{i:0;i:1;}','user_view_page','a:1:{i:0;i:1;}','',2,2,0,'','user/%','My account','user_page_title','a:1:{i:0;i:1;}','','a:0:{}',6,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('user/%/cancel','a:1:{i:1;s:9:\"user_load\";}','','user_cancel_access','a:1:{i:0;i:1;}','drupal_get_form','a:2:{i:0;s:24:\"user_cancel_confirm_form\";i:1;i:1;}','',5,3,0,'','user/%/cancel','Cancel account','t','','','a:0:{}',6,'','',0,'modules/user/user.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('user/%/cancel/confirm/%/%','a:3:{i:1;s:9:\"user_load\";i:4;N;i:5;N;}','','user_cancel_access','a:1:{i:0;i:1;}','user_cancel_confirm','a:3:{i:0;i:1;i:1;i:4;i:2;i:5;}','',44,6,0,'','user/%/cancel/confirm/%/%','Confirm account cancellation','t','','','a:0:{}',6,'','',0,'modules/user/user.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('user/%/contact','a:1:{i:1;s:9:\"user_load\";}','','_contact_personal_tab_access','a:1:{i:0;i:1;}','drupal_get_form','a:2:{i:0;s:21:\"contact_personal_form\";i:1;i:1;}','',5,3,1,'user/%','user/%','Contact','t','','','a:0:{}',132,'','',2,'modules/contact/contact.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('user/%/edit','a:1:{i:1;s:9:\"user_load\";}','','user_edit_access','a:1:{i:0;i:1;}','drupal_get_form','a:2:{i:0;s:17:\"user_profile_form\";i:1;i:1;}','',5,3,1,'user/%','user/%','Edit','t','','','a:0:{}',132,'','',0,'modules/user/user.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('user/%/edit/account','a:1:{i:1;a:1:{s:18:\"user_category_load\";a:2:{i:0;s:4:\"%map\";i:1;s:6:\"%index\";}}}','','user_edit_access','a:1:{i:0;i:1;}','drupal_get_form','a:2:{i:0;s:17:\"user_profile_form\";i:1;i:1;}','',11,4,1,'user/%/edit','user/%','Account','t','','','a:0:{}',140,'','',0,'modules/user/user.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('user/%/orders','a:1:{i:1;N;}','','lc_connector_check_controller_access','a:0:{}','lcConnectorGetControllerContent','a:0:{}','',5,3,1,'user/%','user/%','Order history','t','','','a:0:{}',132,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('user/%/orders/%','a:2:{i:1;N;i:3;N;}','','lc_connector_check_controller_access','a:0:{}','lcConnectorGetControllerContent','a:0:{}','',10,4,1,'user/%/orders','user/%','','t','','','a:0:{}',132,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('user/%/orders/%/invoice','a:2:{i:1;N;i:3;N;}','','lc_connector_check_controller_access','a:0:{}','lcConnectorGetControllerContent','a:0:{}','',21,5,1,'user/%/orders/%','user/%','','t','','','a:0:{}',132,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('user/%/view','a:1:{i:1;s:9:\"user_load\";}','','user_view_access','a:1:{i:0;i:1;}','user_view_page','a:1:{i:0;i:1;}','',5,3,1,'user/%','user/%','View','t','','','a:0:{}',140,'','',-10,'');
INSERT INTO `drupal_menu_router` VALUES ('user/autocomplete','','','user_access','a:1:{i:0;s:20:\"access user profiles\";}','user_autocomplete','a:0:{}','',3,2,0,'','user/autocomplete','User autocomplete','t','','','a:0:{}',0,'','',0,'modules/user/user.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('user/login','','','user_is_anonymous','a:0:{}','user_page','a:0:{}','',3,2,1,'user','user','Log in','t','','','a:0:{}',140,'','',0,'modules/user/user.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('user/logout','','','user_is_logged_in','a:0:{}','user_logout','a:0:{}','',3,2,0,'','user/logout','Log out','t','','','a:0:{}',6,'','',10,'modules/user/user.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('user/password','','','1','a:0:{}','drupal_get_form','a:1:{i:0;s:9:\"user_pass\";}','',3,2,1,'user','user','Request new password','t','','','a:0:{}',132,'','',0,'modules/user/user.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('user/register','','','user_register_access','a:0:{}','drupal_get_form','a:1:{i:0;s:18:\"user_register_form\";}','',3,2,1,'user','user','Create new account','t','','','a:0:{}',132,'','',0,'');
INSERT INTO `drupal_menu_router` VALUES ('user/reset/%/%/%','a:3:{i:2;N;i:3;N;i:4;N;}','','1','a:0:{}','drupal_get_form','a:4:{i:0;s:15:\"user_pass_reset\";i:1;i:2;i:2;i:3;i:3;i:4;}','',24,5,0,'','user/reset/%/%/%','Reset password','t','','','a:0:{}',0,'','',0,'modules/user/user.pages.inc');
INSERT INTO `drupal_menu_router` VALUES ('wysiwyg/%','a:1:{i:1;N;}','','user_access','a:1:{i:0;s:14:\"access content\";}','wysiwyg_dialog','a:1:{i:0;i:1;}','',2,2,0,'','wysiwyg/%','','t','','','a:0:{}',0,'','',0,'sites/all/modules/wysiwyg/wysiwyg.dialog.inc');
/*!40000 ALTER TABLE `drupal_menu_router` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_node`
--

DROP TABLE IF EXISTS `drupal_node`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_node` (
  `nid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'The primary identifier for a node.',
  `vid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The current drupal_node_revision.vid version identifier.',
  `type` varchar(32) NOT NULL DEFAULT '' COMMENT 'The drupal_node_type.type of this node.',
  `language` varchar(12) NOT NULL DEFAULT '' COMMENT 'The drupal_languages.language of this node.',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT 'The title of this node, always treated as non-markup plain text.',
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT 'The drupal_users.uid that owns this node; initially, this is the user that created it.',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT 'Boolean indicating whether the node is published (visible to non-administrators).',
  `created` int(11) NOT NULL DEFAULT '0' COMMENT 'The Unix timestamp when the node was created.',
  `changed` int(11) NOT NULL DEFAULT '0' COMMENT 'The Unix timestamp when the node was most recently saved.',
  `comment` int(11) NOT NULL DEFAULT '0' COMMENT 'Whether comments are allowed on this node: 0 = no, 1 = closed (read only), 2 = open (read/write).',
  `promote` int(11) NOT NULL DEFAULT '0' COMMENT 'Boolean indicating whether the node should be displayed on the front page.',
  `sticky` int(11) NOT NULL DEFAULT '0' COMMENT 'Boolean indicating whether the node should be displayed at the top of lists in which it appears.',
  `tnid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The translation set id for this node, which equals the node id of the source post in each set.',
  `translate` int(11) NOT NULL DEFAULT '0' COMMENT 'A boolean indicating whether this translation page needs to be updated.',
  PRIMARY KEY (`nid`),
  UNIQUE KEY `vid` (`vid`),
  KEY `node_changed` (`changed`),
  KEY `node_created` (`created`),
  KEY `node_frontpage` (`promote`,`status`,`sticky`,`created`),
  KEY `node_status_type` (`status`,`type`,`nid`),
  KEY `node_title_type` (`title`,`type`(4)),
  KEY `node_type` (`type`(4)),
  KEY `uid` (`uid`),
  KEY `tnid` (`tnid`),
  KEY `translate` (`translate`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COMMENT='The base table for nodes.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_node`
--

LOCK TABLES `drupal_node` WRITE;
/*!40000 ALTER TABLE `drupal_node` DISABLE KEYS */;
INSERT INTO `drupal_node` VALUES (1,1,'page','und','Features list',1,1,1293057169,1293057169,1,0,0,0,0);
INSERT INTO `drupal_node` VALUES (2,2,'page','und','Download',1,1,1293058835,1293058835,1,0,0,0,0);
INSERT INTO `drupal_node` VALUES (3,3,'page','und','Community',1,1,1293059348,1293059732,1,0,0,0,0);
INSERT INTO `drupal_node` VALUES (5,5,'page','und','Company',1,1,1293061753,1293062143,1,0,0,0,0);
INSERT INTO `drupal_node` VALUES (6,6,'page','und','About us',1,1,1293062097,1293062097,1,0,0,0,0);
/*!40000 ALTER TABLE `drupal_node` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_node_access`
--

DROP TABLE IF EXISTS `drupal_node_access`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_node_access` (
  `nid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The drupal_node.nid this record affects.',
  `gid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The grant ID a user must possess in the specified realm to gain this row’s privileges on the node.',
  `realm` varchar(255) NOT NULL DEFAULT '' COMMENT 'The realm in which the user must possess the grant ID. Each node access node can define one or more realms.',
  `grant_view` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'Boolean indicating whether a user with the realm/grant pair can view this node.',
  `grant_update` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'Boolean indicating whether a user with the realm/grant pair can edit this node.',
  `grant_delete` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'Boolean indicating whether a user with the realm/grant pair can delete this node.',
  PRIMARY KEY (`nid`,`gid`,`realm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Identifies which realm/grant pairs a user must possess in...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_node_access`
--

LOCK TABLES `drupal_node_access` WRITE;
/*!40000 ALTER TABLE `drupal_node_access` DISABLE KEYS */;
INSERT INTO `drupal_node_access` VALUES (0,0,'all',1,0,0);
/*!40000 ALTER TABLE `drupal_node_access` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_node_comment_statistics`
--

DROP TABLE IF EXISTS `drupal_node_comment_statistics`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_node_comment_statistics` (
  `nid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The drupal_node.nid for which the statistics are compiled.',
  `cid` int(11) NOT NULL DEFAULT '0' COMMENT 'The drupal_comment.cid of the last comment.',
  `last_comment_timestamp` int(11) NOT NULL DEFAULT '0' COMMENT 'The Unix timestamp of the last comment that was posted within this node, from drupal_comment.timestamp.',
  `last_comment_name` varchar(60) DEFAULT NULL COMMENT 'The name of the latest author to post a comment on this node, from drupal_comment.name.',
  `last_comment_uid` int(11) NOT NULL DEFAULT '0' COMMENT 'The user ID of the latest author to post a comment on this node, from drupal_comment.uid.',
  `comment_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The total number of comments on this node.',
  PRIMARY KEY (`nid`),
  KEY `node_comment_timestamp` (`last_comment_timestamp`),
  KEY `comment_count` (`comment_count`),
  KEY `last_comment_uid` (`last_comment_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Maintains statistics of node and comments posts to show ...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_node_comment_statistics`
--

LOCK TABLES `drupal_node_comment_statistics` WRITE;
/*!40000 ALTER TABLE `drupal_node_comment_statistics` DISABLE KEYS */;
INSERT INTO `drupal_node_comment_statistics` VALUES (1,0,1293057169,NULL,1,0);
INSERT INTO `drupal_node_comment_statistics` VALUES (2,0,1293058835,NULL,1,0);
INSERT INTO `drupal_node_comment_statistics` VALUES (3,0,1293059348,NULL,1,0);
INSERT INTO `drupal_node_comment_statistics` VALUES (5,0,1293061753,NULL,1,0);
INSERT INTO `drupal_node_comment_statistics` VALUES (6,0,1293062097,NULL,1,0);
/*!40000 ALTER TABLE `drupal_node_comment_statistics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_node_revision`
--

DROP TABLE IF EXISTS `drupal_node_revision`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_node_revision` (
  `nid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The drupal_node this version belongs to.',
  `vid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'The primary identifier for this version.',
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT 'The drupal_users.uid that created this version.',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT 'The title of this version.',
  `log` longtext NOT NULL COMMENT 'The log entry explaining the changes in this version.',
  `timestamp` int(11) NOT NULL DEFAULT '0' COMMENT 'A Unix timestamp indicating when this version was created.',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT 'Boolean indicating whether the node (at the time of this revision) is published (visible to non-administrators).',
  `comment` int(11) NOT NULL DEFAULT '0' COMMENT 'Whether comments are allowed on this node (at the time of this revision): 0 = no, 1 = closed (read only), 2 = open (read/write).',
  `promote` int(11) NOT NULL DEFAULT '0' COMMENT 'Boolean indicating whether the node (at the time of this revision) should be displayed on the front page.',
  `sticky` int(11) NOT NULL DEFAULT '0' COMMENT 'Boolean indicating whether the node (at the time of this revision) should be displayed at the top of lists in which it appears.',
  PRIMARY KEY (`vid`),
  KEY `nid` (`nid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COMMENT='Stores information about each saved version of a drupal...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_node_revision`
--

LOCK TABLES `drupal_node_revision` WRITE;
/*!40000 ALTER TABLE `drupal_node_revision` DISABLE KEYS */;
INSERT INTO `drupal_node_revision` VALUES (1,1,1,'Features list','',1293057169,1,1,0,0);
INSERT INTO `drupal_node_revision` VALUES (2,2,1,'Download','',1293058835,1,1,0,0);
INSERT INTO `drupal_node_revision` VALUES (3,3,1,'Community','',1293059732,1,1,0,0);
INSERT INTO `drupal_node_revision` VALUES (5,5,1,'Company','',1293062143,1,1,0,0);
INSERT INTO `drupal_node_revision` VALUES (6,6,1,'About us','',1293062097,1,1,0,0);
/*!40000 ALTER TABLE `drupal_node_revision` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_node_type`
--

DROP TABLE IF EXISTS `drupal_node_type`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_node_type` (
  `type` varchar(32) NOT NULL COMMENT 'The machine-readable name of this type.',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT 'The human-readable name of this type.',
  `base` varchar(255) NOT NULL COMMENT 'The base string used to construct callbacks corresponding to this node type.',
  `module` varchar(255) NOT NULL COMMENT 'The module defining this node type.',
  `description` mediumtext NOT NULL COMMENT 'A brief description of this type.',
  `help` mediumtext NOT NULL COMMENT 'Help information shown to the user when creating a drupal_node of this type.',
  `has_title` tinyint(3) unsigned NOT NULL COMMENT 'Boolean indicating whether this type uses the drupal_node.title field.',
  `title_label` varchar(255) NOT NULL DEFAULT '' COMMENT 'The label displayed for the title field on the edit form.',
  `custom` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'A boolean indicating whether this type is defined by a module (FALSE) or by a user via Add content type (TRUE).',
  `modified` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'A boolean indicating whether this type has been modified by an administrator; currently not used in any way.',
  `locked` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'A boolean indicating whether the administrator can change the machine name of this type.',
  `disabled` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'A boolean indicating whether the node type is disabled.',
  `orig_type` varchar(255) NOT NULL DEFAULT '' COMMENT 'The original machine-readable name of this node type. This may be different from the current type name if the locked field is 0.',
  PRIMARY KEY (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores information about all defined drupal_node types.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_node_type`
--

LOCK TABLES `drupal_node_type` WRITE;
/*!40000 ALTER TABLE `drupal_node_type` DISABLE KEYS */;
INSERT INTO `drupal_node_type` VALUES ('article','Article','node_content','node','Use <em>articles</em> for time-sensitive content like news, press releases or blog posts.','',1,'Title',1,1,0,0,'article');
INSERT INTO `drupal_node_type` VALUES ('blog','Blog entry','blog','blog','Use for multi-user blogs. Every user gets a personal blog.','',1,'Title',0,0,1,0,'blog');
INSERT INTO `drupal_node_type` VALUES ('forum','Forum topic','forum','forum','A <em>forum topic</em> starts a new discussion thread within a forum.','',1,'Subject',0,0,1,0,'forum');
INSERT INTO `drupal_node_type` VALUES ('page','Basic page','node_content','node','Use <em>basic pages</em> for your static content, such as an \'About us\' page.','',1,'Title',1,1,0,0,'page');
/*!40000 ALTER TABLE `drupal_node_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_print_node_conf`
--

DROP TABLE IF EXISTS `drupal_print_node_conf`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_print_node_conf` (
  `nid` int(10) unsigned NOT NULL COMMENT 'The drupal_node.nid of the node.',
  `link` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT 'Show link',
  `comments` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT 'Show link in individual comments',
  `url_list` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT 'Show Printer-friendly URLs list',
  PRIMARY KEY (`nid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Printer-friendly version node-specific configuration...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_print_node_conf`
--

LOCK TABLES `drupal_print_node_conf` WRITE;
/*!40000 ALTER TABLE `drupal_print_node_conf` DISABLE KEYS */;
INSERT INTO `drupal_print_node_conf` VALUES (1,1,0,1);
INSERT INTO `drupal_print_node_conf` VALUES (2,1,0,1);
INSERT INTO `drupal_print_node_conf` VALUES (3,1,0,1);
INSERT INTO `drupal_print_node_conf` VALUES (5,1,0,1);
INSERT INTO `drupal_print_node_conf` VALUES (6,1,0,1);
/*!40000 ALTER TABLE `drupal_print_node_conf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_print_page_counter`
--

DROP TABLE IF EXISTS `drupal_print_page_counter`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_print_page_counter` (
  `path` varchar(128) NOT NULL COMMENT 'Page path',
  `totalcount` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT 'Number of page accesses',
  `timestamp` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Last access',
  PRIMARY KEY (`path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Printer-friendly version access counter';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_print_page_counter`
--

LOCK TABLES `drupal_print_page_counter` WRITE;
/*!40000 ALTER TABLE `drupal_print_page_counter` DISABLE KEYS */;
INSERT INTO `drupal_print_page_counter` VALUES ('forum',1,1294870119);
INSERT INTO `drupal_print_page_counter` VALUES ('node/1',1,1293058668);
INSERT INTO `drupal_print_page_counter` VALUES ('user',1,1294870084);
INSERT INTO `drupal_print_page_counter` VALUES ('user/1/orders/1/invoice',1,1294870425);
INSERT INTO `drupal_print_page_counter` VALUES ('user/1/orders/1/invoice/printable-1',1,1294870058);
INSERT INTO `drupal_print_page_counter` VALUES ('user/2/orders/2/invoice',1,1294871772);
/*!40000 ALTER TABLE `drupal_print_page_counter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_queue`
--

DROP TABLE IF EXISTS `drupal_queue`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_queue` (
  `item_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary Key: Unique item ID.',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT 'The queue name.',
  `data` longblob COMMENT 'The arbitrary data for the item.',
  `expire` int(11) NOT NULL DEFAULT '0' COMMENT 'Timestamp when the claim lease expires on the item.',
  `created` int(11) NOT NULL DEFAULT '0' COMMENT 'Timestamp when the item was created.',
  PRIMARY KEY (`item_id`),
  KEY `name_created` (`name`,`created`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8 COMMENT='Stores items in queues.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_queue`
--

LOCK TABLES `drupal_queue` WRITE;
/*!40000 ALTER TABLE `drupal_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_rdf_mapping`
--

DROP TABLE IF EXISTS `drupal_rdf_mapping`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_rdf_mapping` (
  `type` varchar(128) NOT NULL COMMENT 'The name of the entity type a mapping applies to (node, user, comment, etc.).',
  `bundle` varchar(128) NOT NULL COMMENT 'The name of the bundle a mapping applies to.',
  `mapping` longblob COMMENT 'The serialized mapping of the bundle type and fields to RDF terms.',
  PRIMARY KEY (`type`,`bundle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores custom RDF mappings for user defined content types...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_rdf_mapping`
--

LOCK TABLES `drupal_rdf_mapping` WRITE;
/*!40000 ALTER TABLE `drupal_rdf_mapping` DISABLE KEYS */;
INSERT INTO `drupal_rdf_mapping` VALUES ('node','article','a:11:{s:11:\"field_image\";a:2:{s:10:\"predicates\";a:2:{i:0;s:8:\"og:image\";i:1;s:12:\"rdfs:seeAlso\";}s:4:\"type\";s:3:\"rel\";}s:10:\"field_tags\";a:2:{s:10:\"predicates\";a:1:{i:0;s:10:\"dc:subject\";}s:4:\"type\";s:3:\"rel\";}s:7:\"rdftype\";a:2:{i:0;s:9:\"sioc:Item\";i:1;s:13:\"foaf:Document\";}s:5:\"title\";a:1:{s:10:\"predicates\";a:1:{i:0;s:8:\"dc:title\";}}s:7:\"created\";a:3:{s:10:\"predicates\";a:2:{i:0;s:7:\"dc:date\";i:1;s:10:\"dc:created\";}s:8:\"datatype\";s:12:\"xsd:dateTime\";s:8:\"callback\";s:12:\"date_iso8601\";}s:7:\"changed\";a:3:{s:10:\"predicates\";a:1:{i:0;s:11:\"dc:modified\";}s:8:\"datatype\";s:12:\"xsd:dateTime\";s:8:\"callback\";s:12:\"date_iso8601\";}s:4:\"body\";a:1:{s:10:\"predicates\";a:1:{i:0;s:15:\"content:encoded\";}}s:3:\"uid\";a:2:{s:10:\"predicates\";a:1:{i:0;s:16:\"sioc:has_creator\";}s:4:\"type\";s:3:\"rel\";}s:4:\"name\";a:1:{s:10:\"predicates\";a:1:{i:0;s:9:\"foaf:name\";}}s:13:\"comment_count\";a:2:{s:10:\"predicates\";a:1:{i:0;s:16:\"sioc:num_replies\";}s:8:\"datatype\";s:11:\"xsd:integer\";}s:13:\"last_activity\";a:3:{s:10:\"predicates\";a:1:{i:0;s:23:\"sioc:last_activity_date\";}s:8:\"datatype\";s:12:\"xsd:dateTime\";s:8:\"callback\";s:12:\"date_iso8601\";}}');
INSERT INTO `drupal_rdf_mapping` VALUES ('node','blog','a:9:{s:7:\"rdftype\";a:2:{i:0;s:9:\"sioc:Post\";i:1;s:14:\"sioct:BlogPost\";}s:5:\"title\";a:1:{s:10:\"predicates\";a:1:{i:0;s:8:\"dc:title\";}}s:7:\"created\";a:3:{s:10:\"predicates\";a:2:{i:0;s:7:\"dc:date\";i:1;s:10:\"dc:created\";}s:8:\"datatype\";s:12:\"xsd:dateTime\";s:8:\"callback\";s:12:\"date_iso8601\";}s:7:\"changed\";a:3:{s:10:\"predicates\";a:1:{i:0;s:11:\"dc:modified\";}s:8:\"datatype\";s:12:\"xsd:dateTime\";s:8:\"callback\";s:12:\"date_iso8601\";}s:4:\"body\";a:1:{s:10:\"predicates\";a:1:{i:0;s:15:\"content:encoded\";}}s:3:\"uid\";a:2:{s:10:\"predicates\";a:1:{i:0;s:16:\"sioc:has_creator\";}s:4:\"type\";s:3:\"rel\";}s:4:\"name\";a:1:{s:10:\"predicates\";a:1:{i:0;s:9:\"foaf:name\";}}s:13:\"comment_count\";a:2:{s:10:\"predicates\";a:1:{i:0;s:16:\"sioc:num_replies\";}s:8:\"datatype\";s:11:\"xsd:integer\";}s:13:\"last_activity\";a:3:{s:10:\"predicates\";a:1:{i:0;s:23:\"sioc:last_activity_date\";}s:8:\"datatype\";s:12:\"xsd:dateTime\";s:8:\"callback\";s:12:\"date_iso8601\";}}');
INSERT INTO `drupal_rdf_mapping` VALUES ('node','forum','a:10:{s:7:\"rdftype\";a:2:{i:0;s:9:\"sioc:Post\";i:1;s:15:\"sioct:BoardPost\";}s:15:\"taxonomy_forums\";a:2:{s:10:\"predicates\";a:1:{i:0;s:18:\"sioc:has_container\";}s:4:\"type\";s:3:\"rel\";}s:5:\"title\";a:1:{s:10:\"predicates\";a:1:{i:0;s:8:\"dc:title\";}}s:7:\"created\";a:3:{s:10:\"predicates\";a:2:{i:0;s:7:\"dc:date\";i:1;s:10:\"dc:created\";}s:8:\"datatype\";s:12:\"xsd:dateTime\";s:8:\"callback\";s:12:\"date_iso8601\";}s:7:\"changed\";a:3:{s:10:\"predicates\";a:1:{i:0;s:11:\"dc:modified\";}s:8:\"datatype\";s:12:\"xsd:dateTime\";s:8:\"callback\";s:12:\"date_iso8601\";}s:4:\"body\";a:1:{s:10:\"predicates\";a:1:{i:0;s:15:\"content:encoded\";}}s:3:\"uid\";a:2:{s:10:\"predicates\";a:1:{i:0;s:16:\"sioc:has_creator\";}s:4:\"type\";s:3:\"rel\";}s:4:\"name\";a:1:{s:10:\"predicates\";a:1:{i:0;s:9:\"foaf:name\";}}s:13:\"comment_count\";a:2:{s:10:\"predicates\";a:1:{i:0;s:16:\"sioc:num_replies\";}s:8:\"datatype\";s:11:\"xsd:integer\";}s:13:\"last_activity\";a:3:{s:10:\"predicates\";a:1:{i:0;s:23:\"sioc:last_activity_date\";}s:8:\"datatype\";s:12:\"xsd:dateTime\";s:8:\"callback\";s:12:\"date_iso8601\";}}');
INSERT INTO `drupal_rdf_mapping` VALUES ('node','page','a:9:{s:7:\"rdftype\";a:1:{i:0;s:13:\"foaf:Document\";}s:5:\"title\";a:1:{s:10:\"predicates\";a:1:{i:0;s:8:\"dc:title\";}}s:7:\"created\";a:3:{s:10:\"predicates\";a:2:{i:0;s:7:\"dc:date\";i:1;s:10:\"dc:created\";}s:8:\"datatype\";s:12:\"xsd:dateTime\";s:8:\"callback\";s:12:\"date_iso8601\";}s:7:\"changed\";a:3:{s:10:\"predicates\";a:1:{i:0;s:11:\"dc:modified\";}s:8:\"datatype\";s:12:\"xsd:dateTime\";s:8:\"callback\";s:12:\"date_iso8601\";}s:4:\"body\";a:1:{s:10:\"predicates\";a:1:{i:0;s:15:\"content:encoded\";}}s:3:\"uid\";a:2:{s:10:\"predicates\";a:1:{i:0;s:16:\"sioc:has_creator\";}s:4:\"type\";s:3:\"rel\";}s:4:\"name\";a:1:{s:10:\"predicates\";a:1:{i:0;s:9:\"foaf:name\";}}s:13:\"comment_count\";a:2:{s:10:\"predicates\";a:1:{i:0;s:16:\"sioc:num_replies\";}s:8:\"datatype\";s:11:\"xsd:integer\";}s:13:\"last_activity\";a:3:{s:10:\"predicates\";a:1:{i:0;s:23:\"sioc:last_activity_date\";}s:8:\"datatype\";s:12:\"xsd:dateTime\";s:8:\"callback\";s:12:\"date_iso8601\";}}');
INSERT INTO `drupal_rdf_mapping` VALUES ('taxonomy_term','forums','a:5:{s:7:\"rdftype\";a:2:{i:0;s:14:\"sioc:Container\";i:1;s:10:\"sioc:Forum\";}s:4:\"name\";a:1:{s:10:\"predicates\";a:2:{i:0;s:10:\"rdfs:label\";i:1;s:14:\"skos:prefLabel\";}}s:11:\"description\";a:1:{s:10:\"predicates\";a:1:{i:0;s:15:\"skos:definition\";}}s:3:\"vid\";a:2:{s:10:\"predicates\";a:1:{i:0;s:13:\"skos:inScheme\";}s:4:\"type\";s:3:\"rel\";}s:6:\"parent\";a:2:{s:10:\"predicates\";a:1:{i:0;s:12:\"skos:broader\";}s:4:\"type\";s:3:\"rel\";}}');
/*!40000 ALTER TABLE `drupal_rdf_mapping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_registry`
--

DROP TABLE IF EXISTS `drupal_registry`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_registry` (
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT 'The name of the function, class, or interface.',
  `type` varchar(9) NOT NULL DEFAULT '' COMMENT 'Either function or class or interface.',
  `filename` varchar(255) NOT NULL COMMENT 'Name of the file.',
  `module` varchar(255) NOT NULL DEFAULT '' COMMENT 'Name of the module the file belongs to.',
  `weight` int(11) NOT NULL DEFAULT '0' COMMENT 'The order in which this module’s hooks should be invoked relative to other modules. Equal-weighted modules are ordered by name.',
  PRIMARY KEY (`name`,`type`),
  KEY `hook` (`type`,`weight`,`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Each record is a function, class, or interface name and...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_registry`
--

LOCK TABLES `drupal_registry` WRITE;
/*!40000 ALTER TABLE `drupal_registry` DISABLE KEYS */;
INSERT INTO `drupal_registry` VALUES ('AccessDeniedTestCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('AdminMetaTagTestCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('ArchiverInterface','interface','includes/archiver.inc','',0);
INSERT INTO `drupal_registry` VALUES ('ArchiverTar','class','modules/system/system.archiver.inc','system',0);
INSERT INTO `drupal_registry` VALUES ('ArchiverZip','class','modules/system/system.archiver.inc','system',0);
INSERT INTO `drupal_registry` VALUES ('Archive_Tar','class','modules/system/system.tar.inc','system',0);
INSERT INTO `drupal_registry` VALUES ('BatchMemoryQueue','class','includes/batch.queue.inc','',0);
INSERT INTO `drupal_registry` VALUES ('BatchQueue','class','includes/batch.queue.inc','',0);
INSERT INTO `drupal_registry` VALUES ('BlockAdminThemeTestCase','class','modules/block/block.test','block',0);
INSERT INTO `drupal_registry` VALUES ('BlockCacheTestCase','class','modules/block/block.test','block',0);
INSERT INTO `drupal_registry` VALUES ('BlockHTMLIdTestCase','class','modules/block/block.test','block',0);
INSERT INTO `drupal_registry` VALUES ('BlockTestCase','class','modules/block/block.test','block',0);
INSERT INTO `drupal_registry` VALUES ('BlogTestCase','class','modules/blog/blog.test','blog',0);
INSERT INTO `drupal_registry` VALUES ('ColorTestCase','class','modules/color/color.test','color',0);
INSERT INTO `drupal_registry` VALUES ('CommentActionsTestCase','class','modules/comment/comment.test','comment',0);
INSERT INTO `drupal_registry` VALUES ('CommentAnonymous','class','modules/comment/comment.test','comment',0);
INSERT INTO `drupal_registry` VALUES ('CommentApprovalTest','class','modules/comment/comment.test','comment',0);
INSERT INTO `drupal_registry` VALUES ('CommentBlockFunctionalTest','class','modules/comment/comment.test','comment',0);
INSERT INTO `drupal_registry` VALUES ('CommentContentRebuild','class','modules/comment/comment.test','comment',0);
INSERT INTO `drupal_registry` VALUES ('CommentController','class','modules/comment/comment.module','comment',0);
INSERT INTO `drupal_registry` VALUES ('CommentFieldsTest','class','modules/comment/comment.test','comment',0);
INSERT INTO `drupal_registry` VALUES ('CommentHelperCase','class','modules/comment/comment.test','comment',0);
INSERT INTO `drupal_registry` VALUES ('CommentInterfaceTest','class','modules/comment/comment.test','comment',0);
INSERT INTO `drupal_registry` VALUES ('CommentNodeAccessTest','class','modules/comment/comment.test','comment',0);
INSERT INTO `drupal_registry` VALUES ('CommentPagerTest','class','modules/comment/comment.test','comment',0);
INSERT INTO `drupal_registry` VALUES ('CommentPreviewTest','class','modules/comment/comment.test','comment',0);
INSERT INTO `drupal_registry` VALUES ('CommentRSSUnitTest','class','modules/comment/comment.test','comment',0);
INSERT INTO `drupal_registry` VALUES ('CommentTokenReplaceTestCase','class','modules/comment/comment.test','comment',0);
INSERT INTO `drupal_registry` VALUES ('ContactPersonalTestCase','class','modules/contact/contact.test','contact',0);
INSERT INTO `drupal_registry` VALUES ('ContactSitewideTestCase','class','modules/contact/contact.test','contact',0);
INSERT INTO `drupal_registry` VALUES ('CronRunTestCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('DashboardBlocksTestCase','class','modules/dashboard/dashboard.test','dashboard',0);
INSERT INTO `drupal_registry` VALUES ('Database','class','includes/database/database.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseCondition','class','includes/database/query.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseConnection','class','includes/database/database.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseConnectionNotDefinedException','class','includes/database/database.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseConnection_mysql','class','includes/database/mysql/database.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseConnection_pgsql','class','includes/database/pgsql/database.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseConnection_sqlite','class','includes/database/sqlite/database.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseDriverNotSpecifiedException','class','includes/database/database.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseLog','class','includes/database/log.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseSchema','class','includes/database/schema.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseSchemaObjectDoesNotExistException','class','includes/database/schema.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseSchemaObjectExistsException','class','includes/database/schema.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseSchema_mysql','class','includes/database/mysql/schema.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseSchema_pgsql','class','includes/database/pgsql/schema.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseSchema_sqlite','class','includes/database/sqlite/schema.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseStatementBase','class','includes/database/database.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseStatementEmpty','class','includes/database/database.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseStatementInterface','interface','includes/database/database.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseStatementPrefetch','class','includes/database/prefetch.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseStatement_sqlite','class','includes/database/sqlite/database.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseTaskException','class','includes/install.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseTasks','class','includes/install.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseTasks_mysql','class','includes/database/mysql/install.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseTasks_pgsql','class','includes/database/pgsql/install.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseTasks_sqlite','class','includes/database/sqlite/install.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseTransaction','class','includes/database/database.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseTransactionCommitFailedException','class','includes/database/database.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseTransactionExplicitCommitNotAllowedException','class','includes/database/database.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseTransactionNameNonUniqueException','class','includes/database/database.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DatabaseTransactionNoActiveException','class','includes/database/database.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DateTimeFunctionalTest','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('DBLogTestCase','class','modules/dblog/dblog.test','dblog',0);
INSERT INTO `drupal_registry` VALUES ('DefaultMailSystem','class','modules/system/system.mail.inc','system',0);
INSERT INTO `drupal_registry` VALUES ('DeleteQuery','class','includes/database/query.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DeleteQuery_sqlite','class','includes/database/sqlite/query.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DrupalCacheInterface','interface','includes/cache.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DrupalDatabaseCache','class','includes/cache.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DrupalDefaultEntityController','class','includes/entity.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DrupalEntityControllerInterface','interface','includes/entity.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DrupalFakeCache','class','includes/cache-install.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DrupalLocalStreamWrapper','class','includes/stream_wrappers.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DrupalPrivateStreamWrapper','class','includes/stream_wrappers.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DrupalPublicStreamWrapper','class','includes/stream_wrappers.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DrupalQueue','class','modules/system/system.queue.inc','system',0);
INSERT INTO `drupal_registry` VALUES ('DrupalQueueInterface','interface','modules/system/system.queue.inc','system',0);
INSERT INTO `drupal_registry` VALUES ('DrupalReliableQueueInterface','interface','modules/system/system.queue.inc','system',0);
INSERT INTO `drupal_registry` VALUES ('DrupalStreamWrapperInterface','interface','includes/stream_wrappers.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DrupalTemporaryStreamWrapper','class','includes/stream_wrappers.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DrupalUpdateException','class','includes/update.inc','',0);
INSERT INTO `drupal_registry` VALUES ('DrupalUpdaterInterface','interface','includes/updater.inc','',0);
INSERT INTO `drupal_registry` VALUES ('EnableDisableTestCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('EntityFieldQuery','class','includes/entity.inc','',0);
INSERT INTO `drupal_registry` VALUES ('EntityFieldQueryException','class','includes/entity.inc','',0);
INSERT INTO `drupal_registry` VALUES ('EntityPropertiesTestCase','class','modules/field/tests/field.test','field',0);
INSERT INTO `drupal_registry` VALUES ('FieldAttachOtherTestCase','class','modules/field/tests/field.test','field',0);
INSERT INTO `drupal_registry` VALUES ('FieldAttachStorageTestCase','class','modules/field/tests/field.test','field',0);
INSERT INTO `drupal_registry` VALUES ('FieldAttachTestCase','class','modules/field/tests/field.test','field',0);
INSERT INTO `drupal_registry` VALUES ('FieldBulkDeleteTestCase','class','modules/field/tests/field.test','field',0);
INSERT INTO `drupal_registry` VALUES ('FieldCrudTestCase','class','modules/field/tests/field.test','field',0);
INSERT INTO `drupal_registry` VALUES ('FieldDisplayAPITestCase','class','modules/field/tests/field.test','field',0);
INSERT INTO `drupal_registry` VALUES ('FieldException','class','modules/field/field.module','field',0);
INSERT INTO `drupal_registry` VALUES ('FieldFormTestCase','class','modules/field/tests/field.test','field',0);
INSERT INTO `drupal_registry` VALUES ('FieldInfoTestCase','class','modules/field/tests/field.test','field',0);
INSERT INTO `drupal_registry` VALUES ('FieldInstanceCrudTestCase','class','modules/field/tests/field.test','field',0);
INSERT INTO `drupal_registry` VALUES ('FieldsOverlapException','class','includes/database/database.inc','',0);
INSERT INTO `drupal_registry` VALUES ('FieldSqlStorageTestCase','class','modules/field/modules/field_sql_storage/field_sql_storage.test','field_sql_storage',0);
INSERT INTO `drupal_registry` VALUES ('FieldTestCase','class','modules/field/tests/field.test','field',0);
INSERT INTO `drupal_registry` VALUES ('FieldTranslationsTestCase','class','modules/field/tests/field.test','field',0);
INSERT INTO `drupal_registry` VALUES ('FieldUIManageDisplayTestCase','class','modules/field_ui/field_ui.test','field_ui',0);
INSERT INTO `drupal_registry` VALUES ('FieldUIManageFieldsTestCase','class','modules/field_ui/field_ui.test','field_ui',0);
INSERT INTO `drupal_registry` VALUES ('FieldUITestCase','class','modules/field_ui/field_ui.test','field_ui',0);
INSERT INTO `drupal_registry` VALUES ('FieldUpdateForbiddenException','class','modules/field/field.module','field',0);
INSERT INTO `drupal_registry` VALUES ('FieldValidationException','class','modules/field/field.attach.inc','field',0);
INSERT INTO `drupal_registry` VALUES ('FileFieldDisplayTestCase','class','modules/file/tests/file.test','file',0);
INSERT INTO `drupal_registry` VALUES ('FileFieldPathTestCase','class','modules/file/tests/file.test','file',0);
INSERT INTO `drupal_registry` VALUES ('FileFieldRevisionTestCase','class','modules/file/tests/file.test','file',0);
INSERT INTO `drupal_registry` VALUES ('FileFieldTestCase','class','modules/file/tests/file.test','file',0);
INSERT INTO `drupal_registry` VALUES ('FileFieldValidateTestCase','class','modules/file/tests/file.test','file',0);
INSERT INTO `drupal_registry` VALUES ('FileFieldWidgetTestCase','class','modules/file/tests/file.test','file',0);
INSERT INTO `drupal_registry` VALUES ('FileManagedFileElementTestCase','class','modules/file/tests/file.test','file',0);
INSERT INTO `drupal_registry` VALUES ('FileTokenReplaceTestCase','class','modules/file/tests/file.test','file',0);
INSERT INTO `drupal_registry` VALUES ('FileTransfer','class','includes/filetransfer/filetransfer.inc','',0);
INSERT INTO `drupal_registry` VALUES ('FileTransferChmodInterface','interface','includes/filetransfer/filetransfer.inc','',0);
INSERT INTO `drupal_registry` VALUES ('FileTransferException','class','includes/filetransfer/filetransfer.inc','',0);
INSERT INTO `drupal_registry` VALUES ('FileTransferFTP','class','includes/filetransfer/ftp.inc','',0);
INSERT INTO `drupal_registry` VALUES ('FileTransferFTPExtension','class','includes/filetransfer/ftp.inc','',0);
INSERT INTO `drupal_registry` VALUES ('FileTransferLocal','class','includes/filetransfer/local.inc','',0);
INSERT INTO `drupal_registry` VALUES ('FileTransferSSH','class','includes/filetransfer/ssh.inc','',0);
INSERT INTO `drupal_registry` VALUES ('FilterAdminTestCase','class','modules/filter/filter.test','filter',0);
INSERT INTO `drupal_registry` VALUES ('FilterCRUDTestCase','class','modules/filter/filter.test','filter',0);
INSERT INTO `drupal_registry` VALUES ('FilterDefaultFormatTestCase','class','modules/filter/filter.test','filter',0);
INSERT INTO `drupal_registry` VALUES ('FilterFormatAccessTestCase','class','modules/filter/filter.test','filter',0);
INSERT INTO `drupal_registry` VALUES ('FilterHooksTestCase','class','modules/filter/filter.test','filter',0);
INSERT INTO `drupal_registry` VALUES ('FilterNoFormatTestCase','class','modules/filter/filter.test','filter',0);
INSERT INTO `drupal_registry` VALUES ('FilterSecurityTestCase','class','modules/filter/filter.test','filter',0);
INSERT INTO `drupal_registry` VALUES ('FilterUnitTestCase','class','modules/filter/filter.test','filter',0);
INSERT INTO `drupal_registry` VALUES ('FloodFunctionalTest','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('ForumTestCase','class','modules/forum/forum.test','forum',0);
INSERT INTO `drupal_registry` VALUES ('FrontPageTestCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('HelpTestCase','class','modules/help/help.test','help',0);
INSERT INTO `drupal_registry` VALUES ('HookRequirementsTestCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('ImageAdminStylesUnitTest','class','modules/image/image.test','image',0);
INSERT INTO `drupal_registry` VALUES ('ImageEffectsUnitTest','class','modules/image/image.test','image',0);
INSERT INTO `drupal_registry` VALUES ('ImageFieldDisplayTestCase','class','modules/image/image.test','image',0);
INSERT INTO `drupal_registry` VALUES ('ImageFieldTestCase','class','modules/image/image.test','image',0);
INSERT INTO `drupal_registry` VALUES ('ImageFieldValidateTestCase','class','modules/image/image.test','image',0);
INSERT INTO `drupal_registry` VALUES ('ImageStylesPathAndUrlUnitTest','class','modules/image/image.test','image',0);
INSERT INTO `drupal_registry` VALUES ('InfoFileParserTestCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('InsertQuery','class','includes/database/query.inc','',0);
INSERT INTO `drupal_registry` VALUES ('InsertQuery_mysql','class','includes/database/mysql/query.inc','',0);
INSERT INTO `drupal_registry` VALUES ('InsertQuery_pgsql','class','includes/database/pgsql/query.inc','',0);
INSERT INTO `drupal_registry` VALUES ('InsertQuery_sqlite','class','includes/database/sqlite/query.inc','',0);
INSERT INTO `drupal_registry` VALUES ('InvalidMergeQueryException','class','includes/database/database.inc','',0);
INSERT INTO `drupal_registry` VALUES ('IPAddressBlockingTestCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('LCConnector_Abstract','class','modules/lc_connector/classes/Abstract.php','lc_connector',0);
INSERT INTO `drupal_registry` VALUES ('LCConnector_Admin','class','modules/lc_connector/classes/Admin.php','lc_connector',0);
INSERT INTO `drupal_registry` VALUES ('LCConnector_Handler','class','modules/lc_connector/classes/Handler.php','lc_connector',0);
INSERT INTO `drupal_registry` VALUES ('LCConnector_Install','class','modules/lc_connector/classes/Install.php','lc_connector',0);
INSERT INTO `drupal_registry` VALUES ('ListFieldTestCase','class','modules/field/modules/list/tests/list.test','list',0);
INSERT INTO `drupal_registry` VALUES ('ListFieldUITestCase','class','modules/field/modules/list/tests/list.test','list',0);
INSERT INTO `drupal_registry` VALUES ('MailSystemInterface','interface','includes/mail.inc','',0);
INSERT INTO `drupal_registry` VALUES ('MemoryQueue','class','modules/system/system.queue.inc','system',0);
INSERT INTO `drupal_registry` VALUES ('MenuNodeTestCase','class','modules/menu/menu.test','menu',0);
INSERT INTO `drupal_registry` VALUES ('MenuTestCase','class','modules/menu/menu.test','menu',0);
INSERT INTO `drupal_registry` VALUES ('MergeQuery','class','includes/database/query.inc','',0);
INSERT INTO `drupal_registry` VALUES ('ModuleDependencyTestCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('ModuleRequiredTestCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('ModuleTestCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('ModuleUpdater','class','modules/system/system.updater.inc','system',0);
INSERT INTO `drupal_registry` VALUES ('ModuleVersionTestCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('MultiStepNodeFormBasicOptionsTest','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NewDefaultThemeBlocks','class','modules/block/block.test','block',0);
INSERT INTO `drupal_registry` VALUES ('NodeAccessRebuildTestCase','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeAccessRecordsUnitTest','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeAccessUnitTest','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeAdminTestCase','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeBlockFunctionalTest','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeBlockTestCase','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeBuildContent','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeController','class','modules/node/node.module','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeCreationTestCase','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeEntityFieldQueryAlter','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeFeedTestCase','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeLoadHooksTestCase','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeLoadMultipleUnitTest','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodePostSettingsTestCase','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeQueryAlter','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeRevisionsTestCase','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeRSSContentTestCase','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeSaveTestCase','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeTitleTestCase','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeTitleXSSTestCase','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeTokenReplaceTestCase','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeTypePersistenceTestCase','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NodeTypeTestCase','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('NoFieldsException','class','includes/database/database.inc','',0);
INSERT INTO `drupal_registry` VALUES ('NoHelpTestCase','class','modules/help/help.test','help',0);
INSERT INTO `drupal_registry` VALUES ('NonDefaultBlockAdmin','class','modules/block/block.test','block',0);
INSERT INTO `drupal_registry` VALUES ('NumberFieldTestCase','class','modules/field/modules/number/number.test','number',0);
INSERT INTO `drupal_registry` VALUES ('OptionsWidgetsTestCase','class','modules/field/modules/options/options.test','options',0);
INSERT INTO `drupal_registry` VALUES ('PageEditTestCase','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('PageNotFoundTestCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('PagePreviewTestCase','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('PagerDefault','class','includes/pager.inc','',0);
INSERT INTO `drupal_registry` VALUES ('PageTitleFiltering','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('PageViewTestCase','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('PathLanguageTestCase','class','modules/path/path.test','path',0);
INSERT INTO `drupal_registry` VALUES ('PathLanguageUITestCase','class','modules/path/path.test','path',0);
INSERT INTO `drupal_registry` VALUES ('PathMonolingualTestCase','class','modules/path/path.test','path',0);
INSERT INTO `drupal_registry` VALUES ('PathTaxonomyTermTestCase','class','modules/path/path.test','path',0);
INSERT INTO `drupal_registry` VALUES ('PathTestCase','class','modules/path/path.test','path',0);
INSERT INTO `drupal_registry` VALUES ('PHPAccessTestCase','class','modules/php/php.test','php',0);
INSERT INTO `drupal_registry` VALUES ('PHPFilterTestCase','class','modules/php/php.test','php',0);
INSERT INTO `drupal_registry` VALUES ('PHPTestCase','class','modules/php/php.test','php',0);
INSERT INTO `drupal_registry` VALUES ('Query','class','includes/database/query.inc','',0);
INSERT INTO `drupal_registry` VALUES ('QueryAlterableInterface','interface','includes/database/query.inc','',0);
INSERT INTO `drupal_registry` VALUES ('QueryConditionInterface','interface','includes/database/query.inc','',0);
INSERT INTO `drupal_registry` VALUES ('QueryExtendableInterface','interface','includes/database/select.inc','',0);
INSERT INTO `drupal_registry` VALUES ('QueryPlaceholderInterface','interface','includes/database/query.inc','',0);
INSERT INTO `drupal_registry` VALUES ('QueueTestCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('RdfCommentAttributesTestCase','class','modules/rdf/rdf.test','rdf',0);
INSERT INTO `drupal_registry` VALUES ('RdfCrudTestCase','class','modules/rdf/rdf.test','rdf',0);
INSERT INTO `drupal_registry` VALUES ('RdfGetRdfNamespacesTestCase','class','modules/rdf/rdf.test','rdf',0);
INSERT INTO `drupal_registry` VALUES ('RdfMappingDefinitionTestCase','class','modules/rdf/rdf.test','rdf',0);
INSERT INTO `drupal_registry` VALUES ('RdfMappingHookTestCase','class','modules/rdf/rdf.test','rdf',0);
INSERT INTO `drupal_registry` VALUES ('RdfRdfaMarkupTestCase','class','modules/rdf/rdf.test','rdf',0);
INSERT INTO `drupal_registry` VALUES ('RdfTrackerAttributesTestCase','class','modules/rdf/rdf.test','rdf',0);
INSERT INTO `drupal_registry` VALUES ('RetrieveFileTestCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('SearchAdvancedSearchForm','class','modules/search/search.test','search',0);
INSERT INTO `drupal_registry` VALUES ('SearchBlockTestCase','class','modules/search/search.test','search',0);
INSERT INTO `drupal_registry` VALUES ('SearchCommentCountToggleTestCase','class','modules/search/search.test','search',0);
INSERT INTO `drupal_registry` VALUES ('SearchCommentTestCase','class','modules/search/search.test','search',0);
INSERT INTO `drupal_registry` VALUES ('SearchConfigSettingsForm','class','modules/search/search.test','search',0);
INSERT INTO `drupal_registry` VALUES ('SearchEmbedForm','class','modules/search/search.test','search',0);
INSERT INTO `drupal_registry` VALUES ('SearchExactTestCase','class','modules/search/search.test','search',0);
INSERT INTO `drupal_registry` VALUES ('SearchExcerptTestCase','class','modules/search/search.test','search',0);
INSERT INTO `drupal_registry` VALUES ('SearchExpressionInsertExtractTestCase','class','modules/search/search.test','search',0);
INSERT INTO `drupal_registry` VALUES ('SearchKeywordsConditions','class','modules/search/search.test','search',0);
INSERT INTO `drupal_registry` VALUES ('SearchLanguageTestCase','class','modules/search/search.test','search',0);
INSERT INTO `drupal_registry` VALUES ('SearchMatchTestCase','class','modules/search/search.test','search',0);
INSERT INTO `drupal_registry` VALUES ('SearchNumberMatchingTestCase','class','modules/search/search.test','search',0);
INSERT INTO `drupal_registry` VALUES ('SearchNumbersTestCase','class','modules/search/search.test','search',0);
INSERT INTO `drupal_registry` VALUES ('SearchPageOverride','class','modules/search/search.test','search',0);
INSERT INTO `drupal_registry` VALUES ('SearchPageText','class','modules/search/search.test','search',0);
INSERT INTO `drupal_registry` VALUES ('SearchQuery','class','modules/search/search.extender.inc','search',0);
INSERT INTO `drupal_registry` VALUES ('SearchRankingTestCase','class','modules/search/search.test','search',0);
INSERT INTO `drupal_registry` VALUES ('SearchSimplifyTestCase','class','modules/search/search.test','search',0);
INSERT INTO `drupal_registry` VALUES ('SearchTokenizerTestCase','class','modules/search/search.test','search',0);
INSERT INTO `drupal_registry` VALUES ('SelectQuery','class','includes/database/select.inc','',0);
INSERT INTO `drupal_registry` VALUES ('SelectQueryExtender','class','includes/database/select.inc','',0);
INSERT INTO `drupal_registry` VALUES ('SelectQueryInterface','interface','includes/database/select.inc','',0);
INSERT INTO `drupal_registry` VALUES ('SelectQuery_pgsql','class','includes/database/pgsql/select.inc','',0);
INSERT INTO `drupal_registry` VALUES ('SelectQuery_sqlite','class','includes/database/sqlite/select.inc','',0);
INSERT INTO `drupal_registry` VALUES ('ShutdownFunctionsTest','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('SiteMaintenanceTestCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('SkipDotsRecursiveDirectoryIterator','class','includes/filetransfer/filetransfer.inc','',0);
INSERT INTO `drupal_registry` VALUES ('StreamWrapperInterface','interface','includes/stream_wrappers.inc','',0);
INSERT INTO `drupal_registry` VALUES ('SummaryLengthTestCase','class','modules/node/node.test','node',0);
INSERT INTO `drupal_registry` VALUES ('SystemAdminTestCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('SystemAuthorizeCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('SystemBlockTestCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('SystemInfoAlterTestCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('SystemMainContentFallback','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('SystemQueue','class','modules/system/system.queue.inc','system',0);
INSERT INTO `drupal_registry` VALUES ('SystemThemeFunctionalTest','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('TableSort','class','includes/tablesort.inc','',0);
INSERT INTO `drupal_registry` VALUES ('TaxonomyHooksTestCase','class','modules/taxonomy/taxonomy.test','taxonomy',0);
INSERT INTO `drupal_registry` VALUES ('TaxonomyLegacyTestCase','class','modules/taxonomy/taxonomy.test','taxonomy',0);
INSERT INTO `drupal_registry` VALUES ('TaxonomyLoadMultipleUnitTest','class','modules/taxonomy/taxonomy.test','taxonomy',0);
INSERT INTO `drupal_registry` VALUES ('TaxonomyTermController','class','modules/taxonomy/taxonomy.module','taxonomy',0);
INSERT INTO `drupal_registry` VALUES ('TaxonomyTermFieldTestCase','class','modules/taxonomy/taxonomy.test','taxonomy',0);
INSERT INTO `drupal_registry` VALUES ('TaxonomyTermTestCase','class','modules/taxonomy/taxonomy.test','taxonomy',0);
INSERT INTO `drupal_registry` VALUES ('TaxonomyTermUnitTest','class','modules/taxonomy/taxonomy.test','taxonomy',0);
INSERT INTO `drupal_registry` VALUES ('TaxonomyThemeTestCase','class','modules/taxonomy/taxonomy.test','taxonomy',0);
INSERT INTO `drupal_registry` VALUES ('TaxonomyTokenReplaceTestCase','class','modules/taxonomy/taxonomy.test','taxonomy',0);
INSERT INTO `drupal_registry` VALUES ('TaxonomyVocabularyController','class','modules/taxonomy/taxonomy.module','taxonomy',0);
INSERT INTO `drupal_registry` VALUES ('TaxonomyVocabularyFunctionalTest','class','modules/taxonomy/taxonomy.test','taxonomy',0);
INSERT INTO `drupal_registry` VALUES ('TaxonomyVocabularyUnitTest','class','modules/taxonomy/taxonomy.test','taxonomy',0);
INSERT INTO `drupal_registry` VALUES ('TaxonomyWebTestCase','class','modules/taxonomy/taxonomy.test','taxonomy',0);
INSERT INTO `drupal_registry` VALUES ('TestingMailSystem','class','modules/system/system.mail.inc','system',0);
INSERT INTO `drupal_registry` VALUES ('TextFieldTestCase','class','modules/field/modules/text/text.test','text',0);
INSERT INTO `drupal_registry` VALUES ('TextSummaryTestCase','class','modules/field/modules/text/text.test','text',0);
INSERT INTO `drupal_registry` VALUES ('TextTranslationTestCase','class','modules/field/modules/text/text.test','text',0);
INSERT INTO `drupal_registry` VALUES ('ThemeUpdater','class','modules/system/system.updater.inc','system',0);
INSERT INTO `drupal_registry` VALUES ('TokenCurrentPageTestCase','class','modules/token/token.test','token',0);
INSERT INTO `drupal_registry` VALUES ('TokenEntityTestCase','class','modules/token/token.test','token',0);
INSERT INTO `drupal_registry` VALUES ('TokenMenuTestCase','class','modules/token/token.test','token',0);
INSERT INTO `drupal_registry` VALUES ('TokenNodeTestCase','class','modules/token/token.test','token',0);
INSERT INTO `drupal_registry` VALUES ('TokenProfileTestCase','class','modules/token/token.test','token',0);
INSERT INTO `drupal_registry` VALUES ('TokenReplaceTestCase','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('TokenTaxonomyTestCase','class','modules/token/token.test','token',0);
INSERT INTO `drupal_registry` VALUES ('TokenTestHelper','class','modules/token/token.test','token',0);
INSERT INTO `drupal_registry` VALUES ('TokenUnitTestCase','class','modules/token/token.test','token',0);
INSERT INTO `drupal_registry` VALUES ('TokenUserTestCase','class','modules/token/token.test','token',0);
INSERT INTO `drupal_registry` VALUES ('TruncateQuery','class','includes/database/query.inc','',0);
INSERT INTO `drupal_registry` VALUES ('TruncateQuery_mysql','class','includes/database/mysql/query.inc','',0);
INSERT INTO `drupal_registry` VALUES ('TruncateQuery_sqlite','class','includes/database/sqlite/query.inc','',0);
INSERT INTO `drupal_registry` VALUES ('UpdateQuery','class','includes/database/query.inc','',0);
INSERT INTO `drupal_registry` VALUES ('UpdateQuery_pgsql','class','includes/database/pgsql/query.inc','',0);
INSERT INTO `drupal_registry` VALUES ('UpdateQuery_sqlite','class','includes/database/sqlite/query.inc','',0);
INSERT INTO `drupal_registry` VALUES ('Updater','class','includes/updater.inc','',0);
INSERT INTO `drupal_registry` VALUES ('UpdaterException','class','includes/updater.inc','',0);
INSERT INTO `drupal_registry` VALUES ('UpdaterFileTransferException','class','includes/updater.inc','',0);
INSERT INTO `drupal_registry` VALUES ('UpdateScriptFunctionalTest','class','modules/system/system.test','system',0);
INSERT INTO `drupal_registry` VALUES ('UserAccountLinksUnitTests','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserAdminTestCase','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserAuthmapAssignmentTestCase','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserAutocompleteTestCase','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserBlocksUnitTests','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserCancelTestCase','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserController','class','modules/user/user.module','user',0);
INSERT INTO `drupal_registry` VALUES ('UserCreateTestCase','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserEditedOwnAccountTestCase','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserEditTestCase','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserLoginTestCase','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserPermissionsTestCase','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserPictureTestCase','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserRegistrationTestCase','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserRoleAdminTestCase','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserRolesAssignmentTestCase','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserSaveTestCase','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserSignatureTestCase','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserTimeZoneFunctionalTest','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserTokenReplaceTestCase','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserUserSearchTestCase','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserValidateCurrentPassCustomForm','class','modules/user/user.test','user',0);
INSERT INTO `drupal_registry` VALUES ('UserValidationTestCase','class','modules/user/user.test','user',0);
/*!40000 ALTER TABLE `drupal_registry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_registry_file`
--

DROP TABLE IF EXISTS `drupal_registry_file`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_registry_file` (
  `filename` varchar(255) NOT NULL COMMENT 'Path to the file.',
  `hash` varchar(64) NOT NULL COMMENT 'sha-256 hash of the file’s contents when last parsed.',
  PRIMARY KEY (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Files parsed to build the registry.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_registry_file`
--

LOCK TABLES `drupal_registry_file` WRITE;
/*!40000 ALTER TABLE `drupal_registry_file` DISABLE KEYS */;
INSERT INTO `drupal_registry_file` VALUES ('includes/actions.inc','d68bb4366bb9bc7491d186955ee074a7207f9847e08c5075db68474187bca598');
INSERT INTO `drupal_registry_file` VALUES ('includes/ajax.inc','acc303f60b414ed883732b3e62ad914ae4f6dfea847c44646892e9291b67c391');
INSERT INTO `drupal_registry_file` VALUES ('includes/archiver.inc','e7e158c61eea075431d9b676ba2f4310e0dfa09f48d5728104593b85dc19d87e');
INSERT INTO `drupal_registry_file` VALUES ('includes/authorize.inc','7f4e7988143a3682bfc43d71c1d208a62bf1fcafd56584fbd673969de6daf235');
INSERT INTO `drupal_registry_file` VALUES ('includes/batch.inc','5119053bd4e0be1fee9ef4908fd09a793bbdab2878c078020af89c592ac41e1d');
INSERT INTO `drupal_registry_file` VALUES ('includes/batch.queue.inc','0240ed60fa157b5a94d55616639707fe043407c4da82e86439b9127a8fad89b2');
INSERT INTO `drupal_registry_file` VALUES ('includes/bootstrap.inc','ea8f4587161dfaa7e7bb2d5cb755ec1d35cc877816c45fd111aceb5198b7e446');
INSERT INTO `drupal_registry_file` VALUES ('includes/cache-install.inc','50d0536ca06840f2a2fbff7cdf7586feaa54dc157253e20332a4fda677c2e570');
INSERT INTO `drupal_registry_file` VALUES ('includes/cache.inc','f8c12b9182a4aed94d5c900759a110a80a6cefb296466d4980aa8d0853e6678f');
INSERT INTO `drupal_registry_file` VALUES ('includes/common.inc','cc4da1c6591198e814393ee1ffbef3893b9d27ab247d258fff137f370f6e1380');
INSERT INTO `drupal_registry_file` VALUES ('includes/database/database.inc','c504c40c9322f5cbcd83eb4f2373ded34026ec28198630d8a22664d83ea3a060');
INSERT INTO `drupal_registry_file` VALUES ('includes/database/log.inc','b083e54c109710e8df03e007e8886f98fcb76a19c42b0ca34da81e807c8f071b');
INSERT INTO `drupal_registry_file` VALUES ('includes/database/mysql/database.inc','60247becc28c4d4979a298db2197ea2feb2a42d69d6a966e137920a1ec4f6ad6');
INSERT INTO `drupal_registry_file` VALUES ('includes/database/mysql/install.inc','40f1b0e9508885e9d847943287ab0562a85e5b6208576973c5e981746e509164');
INSERT INTO `drupal_registry_file` VALUES ('includes/database/mysql/query.inc','052aff3c58999dc5e9d4f3de629f8f3d1ffef7ded2a741058bbb92561e96da76');
INSERT INTO `drupal_registry_file` VALUES ('includes/database/mysql/schema.inc','ae64a026b02be87f0253393a00e0828a12a2739feb365c39430480570e30d4a3');
INSERT INTO `drupal_registry_file` VALUES ('includes/database/pgsql/database.inc','060019700b14624b8d89f6d6d019614d6f8b05f1f42d56af7ae52ac57df4bd79');
INSERT INTO `drupal_registry_file` VALUES ('includes/database/pgsql/install.inc','c0fc3a84db76e486fce3b32852d5c7af8bbc011444f7c4fe5b9fbb9299dcca24');
INSERT INTO `drupal_registry_file` VALUES ('includes/database/pgsql/query.inc','827edaedced6b9748474b52040189a4300e4b71f840ad8efeb906ced6bc9ef17');
INSERT INTO `drupal_registry_file` VALUES ('includes/database/pgsql/schema.inc','d85400fecba44436ee3631d71751e5614cf9ce9bfd9ff24a566a7e247da11a49');
INSERT INTO `drupal_registry_file` VALUES ('includes/database/pgsql/select.inc','d4039efeefc2cfbf79ffd0bed7c73401599b24fe86ac6f18a88e07b5a0bea3a1');
INSERT INTO `drupal_registry_file` VALUES ('includes/database/prefetch.inc','17947cb7756c74624deda14b56a49914aee35a4ec43602749aeb16d3d8f96be1');
INSERT INTO `drupal_registry_file` VALUES ('includes/database/query.inc','aa051157e7f0543fb7c735c9798c5fd226d899ce50ae85b55efb1a2e177e40dc');
INSERT INTO `drupal_registry_file` VALUES ('includes/database/schema.inc','4b4465d76e9970d3f2b51e50f2d65cb690153ae096fa19bf880668bfffcd0dec');
INSERT INTO `drupal_registry_file` VALUES ('includes/database/select.inc','068828b9ee5837028b39f2582901c2b1d0f43da0d52d28a4eaa614a053f53ea7');
INSERT INTO `drupal_registry_file` VALUES ('includes/database/sqlite/database.inc','1d29eadc367ce57291130abf0d877352f887c303aa15ddd359d0bd40cf112717');
INSERT INTO `drupal_registry_file` VALUES ('includes/database/sqlite/install.inc','35f9c119792f340c179e5ff98957c69ca2d805f9b726f9f147a47fb290ee8cff');
INSERT INTO `drupal_registry_file` VALUES ('includes/database/sqlite/query.inc','c3da2233371358d2ea6eeb35d47538633732bc7bfeca480ba1033c1735cd7be2');
INSERT INTO `drupal_registry_file` VALUES ('includes/database/sqlite/schema.inc','ea71bb781780401e0e2fdfde6655cd2f6b100e6eff71c745dde91fa6840cf666');
INSERT INTO `drupal_registry_file` VALUES ('includes/database/sqlite/select.inc','c02763f87da660c18f96c34e1522ca9e7ebc3f01d443cf4732b0305b6a66310f');
INSERT INTO `drupal_registry_file` VALUES ('includes/date.inc','21f1e4585f13cac31388ae361f640302bcce5b3fbbc58bb1950bc2ed1ca990a2');
INSERT INTO `drupal_registry_file` VALUES ('includes/entity.inc','4efed71a8fd8148629d0a31ef791f042d145967d5ba0a564089f4629bb642e47');
INSERT INTO `drupal_registry_file` VALUES ('includes/errors.inc','70e21136c72bea3ba916fd9eca59898068d44561e1e197d87e85973aec16bfcf');
INSERT INTO `drupal_registry_file` VALUES ('includes/file.inc','16363e0efba823e09d49a93118cc6c3cdf349598a3369f4c592b7842c564e592');
INSERT INTO `drupal_registry_file` VALUES ('includes/file.mimetypes.inc','cd382ba4ad4df36e39b066b055de97f948d938703435e9f8ada8878f85051ffd');
INSERT INTO `drupal_registry_file` VALUES ('includes/filetransfer/filetransfer.inc','8deb15c1d879393d39defda0e441a47a39cdd006993dda4a8345324221a9dd3c');
INSERT INTO `drupal_registry_file` VALUES ('includes/filetransfer/ftp.inc','8a3f6aad5479db8f847a80c45362c7680e8bc6ee3680ec9865291b8aa7aae423');
INSERT INTO `drupal_registry_file` VALUES ('includes/filetransfer/local.inc','3a3bb97596719cada7c6afb6045e1c42b7beb1d686958d61c338348ecc4d1e28');
INSERT INTO `drupal_registry_file` VALUES ('includes/filetransfer/ssh.inc','a44d81ca5bdb2d4cffcf22c7b8f75e8dd0190a046d7f4684f15c67aabfa15048');
INSERT INTO `drupal_registry_file` VALUES ('includes/form.inc','40451cb427ad139a1cdb5420dbc9481116cf3451f213d0832c3076162959f04f');
INSERT INTO `drupal_registry_file` VALUES ('includes/graph.inc','85b88b600537762960532feae0c6581b0dcc5aa32fdeb9b997e1b1bb41925e89');
INSERT INTO `drupal_registry_file` VALUES ('includes/image.inc','3c57a46d4babc288bfc969d49182465c802f29bc265b0b5fcaa47eeca185b2b8');
INSERT INTO `drupal_registry_file` VALUES ('includes/install.core.inc','9602ae3c4861679623f6fc3413b191fe9227354a259312ccc6c7eaaeb7c6e616');
INSERT INTO `drupal_registry_file` VALUES ('includes/install.inc','fec8b44f644a7eca84753162791168efe4b1664ea5643390af74e7002d991248');
INSERT INTO `drupal_registry_file` VALUES ('includes/iso.inc','9b7fc133606da86f0dbc6eb095c92bd8c2649cb1e4d52da43f0c577b5bf79798');
INSERT INTO `drupal_registry_file` VALUES ('includes/language.inc','5adf3cea15514a6bbd2e99dcfd5848758369417100a713b3405961bd542e91ce');
INSERT INTO `drupal_registry_file` VALUES ('includes/locale.inc','283b19ac9c90f378b85c8e758cf77e0dd13b2311530a89d3c33ca6f43528d34c');
INSERT INTO `drupal_registry_file` VALUES ('includes/lock.inc','6b45ffa6c18645208396e246560095c533f928dfc4b1dbeb945155bbea45f4a9');
INSERT INTO `drupal_registry_file` VALUES ('includes/mail.inc','d7140b446910446b0d11665fb5e5d4048cc025122c6f756ea6dd407aa03439d9');
INSERT INTO `drupal_registry_file` VALUES ('includes/menu.inc','9c120c08a81842229c7e2c879f9f5f129fda6557a69b39bb2b9c69451c413a0f');
INSERT INTO `drupal_registry_file` VALUES ('includes/module.inc','517906587efd983703ed8a018eac32e59890e22a7abc6e9823b70d93ec3ce925');
INSERT INTO `drupal_registry_file` VALUES ('includes/pager.inc','46e4c8f1fa3900ac32275a9402677d5fb0c6f4ee1a0a55814e1b40c566e91133');
INSERT INTO `drupal_registry_file` VALUES ('includes/password.inc','127588cd8554dc150eb855c27708c30c90f5f416d2535fef53d2ab034099fa94');
INSERT INTO `drupal_registry_file` VALUES ('includes/path.inc','202bf07fd1559a0d99a70e8e58f65070260e702f740596880b98628fc7f880df');
INSERT INTO `drupal_registry_file` VALUES ('includes/registry.inc','df1ddfe786f163fbd1f684496b7304332c61adcfa7110a3a40d55190097bb8f9');
INSERT INTO `drupal_registry_file` VALUES ('includes/session.inc','05d5bbaeddf470d404c8c3442ad0eb9de867020c14c0395348e331a8f09b1fdb');
INSERT INTO `drupal_registry_file` VALUES ('includes/stream_wrappers.inc','511184a360b81600f680f414abbaf813a17a497cf42b7931192832a955c86d00');
INSERT INTO `drupal_registry_file` VALUES ('includes/tablesort.inc','e2a6fd10306c59ee3361a49ceca0d88830fbe0b84ebe4836d3161cba552d9c99');
INSERT INTO `drupal_registry_file` VALUES ('includes/theme.inc','a001f94d41e0cc9acec49a5627dee636abde07202a44fb89faa10355e97cd440');
INSERT INTO `drupal_registry_file` VALUES ('includes/theme.maintenance.inc','9e9038460da4eca88dbda1dcc6fc008af482b03211fc17e2619825435920fa91');
INSERT INTO `drupal_registry_file` VALUES ('includes/token.inc','d853242ca38b6c89f04083d0400275d80231f6012a772cb4eb5df9d731b32df5');
INSERT INTO `drupal_registry_file` VALUES ('includes/unicode.entities.inc','2790764999f231c6f53b5a60917740f486a5d0b2a627b9163004908c41ae865c');
INSERT INTO `drupal_registry_file` VALUES ('includes/unicode.inc','333334b6fb9653b3ae222593b955b16f7b87965178c5db34c8da7d2f1284a5a2');
INSERT INTO `drupal_registry_file` VALUES ('includes/update.inc','6e85fc1cd93920346c027f2e5677b719051f110fc8ddbbca6703eb97a64495a2');
INSERT INTO `drupal_registry_file` VALUES ('includes/updater.inc','be7723e8c04ef1b486ee347801bb908f024b10d7654cb26b87be229515327120');
INSERT INTO `drupal_registry_file` VALUES ('includes/utility.inc','a8d27e6b9e63892a031bd6fcf5a5fce29775b201df180708e498b19c132a7cad');
INSERT INTO `drupal_registry_file` VALUES ('includes/xmlrpc.inc','2010b194dc5c8002d187226de03c051a1f5fb55f7e24a705a41b5016e826f110');
INSERT INTO `drupal_registry_file` VALUES ('includes/xmlrpcs.inc','2c7ab3e727fc1d866fd02b3821da90697966b979096dcac3dd4774c647047a3a');
INSERT INTO `drupal_registry_file` VALUES ('modules/block/block.test','9e9e67968e9948bf5b3379d361ef8997bec483561094252fd2d08a2e67a79b12');
INSERT INTO `drupal_registry_file` VALUES ('modules/blog/blog.test','bd4435d2160e4cd1013a27c83c296cdcaffa0bef1f9d18f9e52ec9899a121f24');
INSERT INTO `drupal_registry_file` VALUES ('modules/color/color.test','9a77e07e83b26ff9430f1d9a5561036fd05cd69c8091dccf707f618bb2b34c07');
INSERT INTO `drupal_registry_file` VALUES ('modules/comment/comment.module','be8c22e6337942837dca31a73c540783c5ba7e0dabb110adcb3d70cae402a381');
INSERT INTO `drupal_registry_file` VALUES ('modules/comment/comment.test','b010252b76cef194d138048290631f2b90939784367b5fdf9666930f6d8da9a8');
INSERT INTO `drupal_registry_file` VALUES ('modules/contact/contact.test','65fd81f39b5e87888e85a4fb497277edd365885b5610c214f86dec9cf1d07355');
INSERT INTO `drupal_registry_file` VALUES ('modules/dashboard/dashboard.test','1a0a1cee03f7083c10a5d7f4b662f33981710cdde2487a2ae72260a0ad3f3d4a');
INSERT INTO `drupal_registry_file` VALUES ('modules/dblog/dblog.test','b20506222f7c219dbf8e640327555beaf9abd96f9b1fc3d71c4c68af619f62b5');
INSERT INTO `drupal_registry_file` VALUES ('modules/field/field.attach.inc','d9b11d949b239e4d06f19bd8d8b1fc6cb55235381ea4baa0eef6ece8eeead647');
INSERT INTO `drupal_registry_file` VALUES ('modules/field/field.module','7067f33317dab323d0a333fe6fc96aed297a478e09892c2a249dabada30e570a');
INSERT INTO `drupal_registry_file` VALUES ('modules/field/modules/field_sql_storage/field_sql_storage.test','0638eceea41a277f8805ac575b7c8f93e4768635eb72d097f8c918bbbee65822');
INSERT INTO `drupal_registry_file` VALUES ('modules/field/modules/list/tests/list.test','00367918c7374bad15ab02997f42996badc538ff32e9806ad4dd8015f05f335a');
INSERT INTO `drupal_registry_file` VALUES ('modules/field/modules/number/number.test','8849359ffa6dc47c65b6c0f3700b2d2f71b241a9163142842844b3d7031ebdcb');
INSERT INTO `drupal_registry_file` VALUES ('modules/field/modules/options/options.test','55411cca9f6ae68b8bc8502f3637f9e1ff8e29024cdb86f0be6d6aa8c6b9efb4');
INSERT INTO `drupal_registry_file` VALUES ('modules/field/modules/text/text.test','854036cc23e24a86fd349bc9f59d28f21a5bc0faa8336a38a7fa56b5cbe12a2e');
INSERT INTO `drupal_registry_file` VALUES ('modules/field/tests/field.test','bb3ad85f228da7a1dbb940b51f6b21d7a7c7e1c74b06aaef5b20594ba0661e08');
INSERT INTO `drupal_registry_file` VALUES ('modules/field_ui/field_ui.test','d3b8509c77f0601602235724750280260935780fd35058b64a721cb8e562f483');
INSERT INTO `drupal_registry_file` VALUES ('modules/file/tests/file.test','7074f0982d04d89ce7769f57b63cdf879fcc520bc4f8493b16219fd4f957d47e');
INSERT INTO `drupal_registry_file` VALUES ('modules/filter/filter.test','6cbb682c5e48acd9d93987683adfda331821611e151b911bac40857114a9de76');
INSERT INTO `drupal_registry_file` VALUES ('modules/forum/forum.test','9e384bd7a089d52a73c5242114236f32056e19f279a6a3f38a2d74cbf9cfb0c4');
INSERT INTO `drupal_registry_file` VALUES ('modules/help/help.test','e373228593f82ee8f92471bc7f6b03556c73ed44e88c74247dcdafc54263620c');
INSERT INTO `drupal_registry_file` VALUES ('modules/image/image.test','d902375fdfaec335ec59cb9f7713625ca197e379e8ca28c6cfd936d238d50db4');
INSERT INTO `drupal_registry_file` VALUES ('modules/lc_connector/classes/Abstract.php','7fdab344b72b5856fd95973d0b82e3a8cbff0911f2be7eaa250b4f4b07a85430');
INSERT INTO `drupal_registry_file` VALUES ('modules/lc_connector/classes/Admin.php','3bb3990d5c0f44892f7a49d5d729b301ed84ce43f9af667b30a3c8ecb0121c7d');
INSERT INTO `drupal_registry_file` VALUES ('modules/lc_connector/classes/Handler.php','e854c6d371c4f830c19be1d3e3009d346e6d9eadaf16d0d9e3aedd654c75bfee');
INSERT INTO `drupal_registry_file` VALUES ('modules/lc_connector/classes/Install.php','8759814316102ab187b079619f10f8a3077f3f88f8ee89b1a3d3621be24ebed6');
INSERT INTO `drupal_registry_file` VALUES ('modules/menu/menu.test','e442a44a827fb7f90c3de705e928635c4a3291d4e246c0aff4f49d8719d31717');
INSERT INTO `drupal_registry_file` VALUES ('modules/node/node.module','789298809eb45b29ff86b717339d5d85664316fcd4b66c01a06b65020b1fe313');
INSERT INTO `drupal_registry_file` VALUES ('modules/node/node.test','3d2693be97e2d11a6a647daa262bda868b05acbf395f54a4cbd4767166b13fda');
INSERT INTO `drupal_registry_file` VALUES ('modules/path/path.test','6eaab00a789697243f2bd6a107ed129499af1589e1658857cad2d2ab5127ac87');
INSERT INTO `drupal_registry_file` VALUES ('modules/php/php.test','fae57cb34e4c790b1c0216626575e319f790c5fb25ff5fcee12725bf5a2625c2');
INSERT INTO `drupal_registry_file` VALUES ('modules/print/print.admin.inc','08d711bf48412b22d2b4ca2e12b122c8392d632867a9772d237f6b461d60ed5b');
INSERT INTO `drupal_registry_file` VALUES ('modules/print/print.install','6c9bd755fb27a3fb81d9eeac8a344b3ffe3912b712e1cae5ad47801f0b2beff2');
INSERT INTO `drupal_registry_file` VALUES ('modules/print/print.module','c1d0912949dd30d5881750afdbb3f497c775f7337d0487fe5eba4966c725a078');
INSERT INTO `drupal_registry_file` VALUES ('modules/print/print.pages.inc','f91a3847984bf08d36ab18864f38d74c877540609aa00b61d64df9958a54fa0a');
INSERT INTO `drupal_registry_file` VALUES ('modules/rdf/rdf.test','14b456ef5a546c623a553e4a67307bebe0b0c3c58357986dedb17c5f7f512405');
INSERT INTO `drupal_registry_file` VALUES ('modules/search/search.extender.inc','1fccc2b3ce36f7806017a7939e7e4dd759b9c62733923d77be8bd1be84288e60');
INSERT INTO `drupal_registry_file` VALUES ('modules/search/search.test','a4fde9f944b5cc23a4da816d7ab98e007c8ae1b1741dbe05f94411ea14c66fdf');
INSERT INTO `drupal_registry_file` VALUES ('modules/system/system.archiver.inc','444738f81695cdd1c0b31712d9fd26a5fd13385aff6cd0567719eca477148f89');
INSERT INTO `drupal_registry_file` VALUES ('modules/system/system.mail.inc','01a628e54cd5aa88270983154e4225d9b7b667f0406869244ad98eff171b7ff1');
INSERT INTO `drupal_registry_file` VALUES ('modules/system/system.queue.inc','6e90eeb62a24072cc1fd3bec106682876fa5843e68a7f01656e1dcc865723449');
INSERT INTO `drupal_registry_file` VALUES ('modules/system/system.tar.inc','743529eab763be74e8169c945e875381fd77d71e336c6d77b7d28e2dfd023a21');
INSERT INTO `drupal_registry_file` VALUES ('modules/system/system.test','6c0c809ebec973fddac69932e756d52f6b52e0d2080507260001f20138ce3eae');
INSERT INTO `drupal_registry_file` VALUES ('modules/system/system.updater.inc','3ead16843d9ad8560cbcafffd5eb056d4c37c3a78bc40a74e7a76ed7ca41b174');
INSERT INTO `drupal_registry_file` VALUES ('modules/taxonomy/taxonomy.module','1693100186874ac4caba4fcd466adb95e683534db8ac9a8dde7f474ff1bfbd83');
INSERT INTO `drupal_registry_file` VALUES ('modules/taxonomy/taxonomy.test','3ae75b19e8a3860b37314f4144874f8a9545d95a754580831bd47a4934aef298');
INSERT INTO `drupal_registry_file` VALUES ('modules/token/token.install','2a8bf880b4998fab7000e6b84a7e3702b8b116b3a1e2a05cb119e5026bd01a3b');
INSERT INTO `drupal_registry_file` VALUES ('modules/token/token.module','7f0ca9aeb9e0ec57ac65f75dc5a8055b4aea6f63dc171cc62c0b9e05e99bdffb');
INSERT INTO `drupal_registry_file` VALUES ('modules/token/token.pages.inc','ec0460a4c8a0db51bad1c09a9963b87b8e966006ed069c4019fbda39f92d7217');
INSERT INTO `drupal_registry_file` VALUES ('modules/token/token.test','5c790dffb15a7753aa3dd0ccd42d3af23cf639e8a11730d41d05bb87a240685e');
INSERT INTO `drupal_registry_file` VALUES ('modules/token/token.tokens.inc','56e2344fa74749b527c789dc0952457d042f996b98ce6d906c72c946ab8554e9');
INSERT INTO `drupal_registry_file` VALUES ('modules/user/user.module','a78b17fdfa7dc2c203022c59920997767871ea5a3648e7af55b210ab04d1fe8b');
INSERT INTO `drupal_registry_file` VALUES ('modules/user/user.test','aa25a2ba1a629e29d617bcbe9e8de60e7db5e64ba0d59cfcd5912c1bd5a5d68a');
INSERT INTO `drupal_registry_file` VALUES ('profiles/standard/standard.profile','0efca35d500895cc03748ded2513e8837a46884b1b61208982a4e8269e088140');
INSERT INTO `drupal_registry_file` VALUES ('sites/all/modules/wysiwyg/wysiwyg.admin.inc','3c0200d15cda9eb90e8c5859b7407c9546642712b80a375d7562f7793bec9782');
INSERT INTO `drupal_registry_file` VALUES ('sites/all/modules/wysiwyg/wysiwyg.dialog.inc','c44845456093e48fd2d98d0601599ff63b9ba0e6e44443d07557f01ef856f8b9');
INSERT INTO `drupal_registry_file` VALUES ('sites/all/modules/wysiwyg/wysiwyg.install','bb52da0115775de5f42447ea588ef51286bc52aaa89a3e76c9effc9e2da7d2d9');
INSERT INTO `drupal_registry_file` VALUES ('sites/all/modules/wysiwyg/wysiwyg.module','f5718904c9a1399a30ed84bc2451e6faf3265e94f8d6fb5a0b0a69c0934e5f3a');
/*!40000 ALTER TABLE `drupal_registry_file` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_role`
--

DROP TABLE IF EXISTS `drupal_role`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_role` (
  `rid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary Key: Unique role ID.',
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT 'Unique role name.',
  `weight` int(11) NOT NULL DEFAULT '0' COMMENT 'The weight of this role in listings and the user interface.',
  PRIMARY KEY (`rid`),
  UNIQUE KEY `name` (`name`),
  KEY `name_weight` (`name`,`weight`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='Stores user roles.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_role`
--

LOCK TABLES `drupal_role` WRITE;
/*!40000 ALTER TABLE `drupal_role` DISABLE KEYS */;
INSERT INTO `drupal_role` VALUES (1,'anonymous user',0);
INSERT INTO `drupal_role` VALUES (2,'authenticated user',1);
INSERT INTO `drupal_role` VALUES (3,'administrator',2);
/*!40000 ALTER TABLE `drupal_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_role_permission`
--

DROP TABLE IF EXISTS `drupal_role_permission`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_role_permission` (
  `rid` int(10) unsigned NOT NULL COMMENT 'Foreign Key: drupal_role.rid.',
  `permission` varchar(128) NOT NULL DEFAULT '' COMMENT 'A single permission granted to the role identified by rid.',
  `module` varchar(255) NOT NULL DEFAULT '' COMMENT 'The module declaring the permission.',
  PRIMARY KEY (`rid`,`permission`),
  KEY `permission` (`permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores the permissions assigned to user roles.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_role_permission`
--

LOCK TABLES `drupal_role_permission` WRITE;
/*!40000 ALTER TABLE `drupal_role_permission` DISABLE KEYS */;
INSERT INTO `drupal_role_permission` VALUES (1,'access comments','comment');
INSERT INTO `drupal_role_permission` VALUES (1,'access content','node');
INSERT INTO `drupal_role_permission` VALUES (1,'use text format filtered_html','filter');
INSERT INTO `drupal_role_permission` VALUES (2,'access comments','comment');
INSERT INTO `drupal_role_permission` VALUES (2,'access content','node');
INSERT INTO `drupal_role_permission` VALUES (2,'post comments','comment');
INSERT INTO `drupal_role_permission` VALUES (2,'skip comment approval','comment');
INSERT INTO `drupal_role_permission` VALUES (2,'use text format filtered_html','filter');
INSERT INTO `drupal_role_permission` VALUES (3,'access administration pages','system');
INSERT INTO `drupal_role_permission` VALUES (3,'access comments','comment');
INSERT INTO `drupal_role_permission` VALUES (3,'access content','node');
INSERT INTO `drupal_role_permission` VALUES (3,'access content overview','node');
INSERT INTO `drupal_role_permission` VALUES (3,'access contextual links','contextual');
INSERT INTO `drupal_role_permission` VALUES (3,'access dashboard','dashboard');
INSERT INTO `drupal_role_permission` VALUES (3,'access overlay','overlay');
INSERT INTO `drupal_role_permission` VALUES (3,'access print','print');
INSERT INTO `drupal_role_permission` VALUES (3,'access site in maintenance mode','system');
INSERT INTO `drupal_role_permission` VALUES (3,'access site reports','system');
INSERT INTO `drupal_role_permission` VALUES (3,'access site-wide contact form','contact');
INSERT INTO `drupal_role_permission` VALUES (3,'access toolbar','toolbar');
INSERT INTO `drupal_role_permission` VALUES (3,'access user contact forms','contact');
INSERT INTO `drupal_role_permission` VALUES (3,'access user profiles','user');
INSERT INTO `drupal_role_permission` VALUES (3,'administer actions','system');
INSERT INTO `drupal_role_permission` VALUES (3,'administer blocks','block');
INSERT INTO `drupal_role_permission` VALUES (3,'administer comments','comment');
INSERT INTO `drupal_role_permission` VALUES (3,'administer contact forms','contact');
INSERT INTO `drupal_role_permission` VALUES (3,'administer content types','node');
INSERT INTO `drupal_role_permission` VALUES (3,'administer filters','filter');
INSERT INTO `drupal_role_permission` VALUES (3,'administer forums','forum');
INSERT INTO `drupal_role_permission` VALUES (3,'administer image styles','image');
INSERT INTO `drupal_role_permission` VALUES (3,'administer menu','menu');
INSERT INTO `drupal_role_permission` VALUES (3,'administer modules','system');
INSERT INTO `drupal_role_permission` VALUES (3,'administer nodes','node');
INSERT INTO `drupal_role_permission` VALUES (3,'administer permissions','user');
INSERT INTO `drupal_role_permission` VALUES (3,'administer print','print');
INSERT INTO `drupal_role_permission` VALUES (3,'administer search','search');
INSERT INTO `drupal_role_permission` VALUES (3,'administer shortcuts','shortcut');
INSERT INTO `drupal_role_permission` VALUES (3,'administer site configuration','system');
INSERT INTO `drupal_role_permission` VALUES (3,'administer software updates','system');
INSERT INTO `drupal_role_permission` VALUES (3,'administer taxonomy','taxonomy');
INSERT INTO `drupal_role_permission` VALUES (3,'administer themes','system');
INSERT INTO `drupal_role_permission` VALUES (3,'administer url aliases','path');
INSERT INTO `drupal_role_permission` VALUES (3,'administer users','user');
INSERT INTO `drupal_role_permission` VALUES (3,'block IP addresses','system');
INSERT INTO `drupal_role_permission` VALUES (3,'bypass node access','node');
INSERT INTO `drupal_role_permission` VALUES (3,'cancel account','user');
INSERT INTO `drupal_role_permission` VALUES (3,'change own username','user');
INSERT INTO `drupal_role_permission` VALUES (3,'create article content','node');
INSERT INTO `drupal_role_permission` VALUES (3,'create page content','node');
INSERT INTO `drupal_role_permission` VALUES (3,'create url aliases','path');
INSERT INTO `drupal_role_permission` VALUES (3,'customize shortcut links','shortcut');
INSERT INTO `drupal_role_permission` VALUES (3,'delete any article content','node');
INSERT INTO `drupal_role_permission` VALUES (3,'delete any page content','node');
INSERT INTO `drupal_role_permission` VALUES (3,'delete own article content','node');
INSERT INTO `drupal_role_permission` VALUES (3,'delete own page content','node');
INSERT INTO `drupal_role_permission` VALUES (3,'delete revisions','node');
INSERT INTO `drupal_role_permission` VALUES (3,'delete terms in 1','taxonomy');
INSERT INTO `drupal_role_permission` VALUES (3,'edit any article content','node');
INSERT INTO `drupal_role_permission` VALUES (3,'edit any page content','node');
INSERT INTO `drupal_role_permission` VALUES (3,'edit own article content','node');
INSERT INTO `drupal_role_permission` VALUES (3,'edit own comments','comment');
INSERT INTO `drupal_role_permission` VALUES (3,'edit own page content','node');
INSERT INTO `drupal_role_permission` VALUES (3,'edit terms in 1','taxonomy');
INSERT INTO `drupal_role_permission` VALUES (3,'node-specific print configuration','print');
INSERT INTO `drupal_role_permission` VALUES (3,'post comments','comment');
INSERT INTO `drupal_role_permission` VALUES (3,'revert revisions','node');
INSERT INTO `drupal_role_permission` VALUES (3,'search content','search');
INSERT INTO `drupal_role_permission` VALUES (3,'select account cancellation method','user');
INSERT INTO `drupal_role_permission` VALUES (3,'skip comment approval','comment');
INSERT INTO `drupal_role_permission` VALUES (3,'switch shortcut sets','shortcut');
INSERT INTO `drupal_role_permission` VALUES (3,'use advanced search','search');
INSERT INTO `drupal_role_permission` VALUES (3,'use PHP for settings','php');
INSERT INTO `drupal_role_permission` VALUES (3,'use text format filtered_html','filter');
INSERT INTO `drupal_role_permission` VALUES (3,'use text format full_html','filter');
INSERT INTO `drupal_role_permission` VALUES (3,'view own unpublished content','node');
INSERT INTO `drupal_role_permission` VALUES (3,'view revisions','node');
INSERT INTO `drupal_role_permission` VALUES (3,'view the administration theme','system');
/*!40000 ALTER TABLE `drupal_role_permission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_search_dataset`
--

DROP TABLE IF EXISTS `drupal_search_dataset`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_search_dataset` (
  `sid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Search item ID, e.g. node ID for nodes.',
  `type` varchar(16) NOT NULL COMMENT 'Type of item, e.g. node.',
  `data` longtext NOT NULL COMMENT 'List of space-separated words from the item.',
  `reindex` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Set to force node reindexing.',
  PRIMARY KEY (`sid`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores items that will be searched.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_search_dataset`
--

LOCK TABLES `drupal_search_dataset` WRITE;
/*!40000 ALTER TABLE `drupal_search_dataset` DISABLE KEYS */;
INSERT INTO `drupal_search_dataset` VALUES (1,'node',' features list setup architecture free opensource php mysql solution can work as a standalone online store and in connection with drupal cms with tons of readymade drupal modules webbased installation wizard that installs both litecommerce and drupal when run from the ecommerce cms package  storefront sections and blocks can be managed in the drupal user interface modern objectoriented mvc architecture flexible modular system allows customizing without hacks to core files and simplifies upgrades https ssl support fully customizable design layout compatible with popular browsers ie 6 firefox 2 chrome 4 safari 3 opera 10 catalog management unlimited number of products and categories product options with optional price modifiers inventory tracking per product or product variation automatic thumbnail generation with image sharpening a product can belong to more than one category product import from csv files marketing promotion custom search engine friendly urls when connected to drupal custom meta tags for products categories and other website pages when connected to drupal discounts wholesale pricing featured products bestsellers recently added products gift certificates shopping experience storefront sections and blocks transparently integrate with drupal pages into a single ecommerce website catalog pages are updated via ajax without page reloading image galleries with a popup image browser and an inpage zoom function previous and next links on product pages mouse wheel updates product quantities wish list list of recently viewed products horizontal and vertical minicart widgets quick search form orders shipping and tax customizable email notifications order history for customers and administrator payment and shipping status tracking invoice printing support for paypal standard paypal express checkout google checkout and authorizenet sim configurable min max order amount limits realtime ups and usps shipping rates unlimited number of admindefined delivery methods flat weight order total and range based shipping rates international domestic and local shipping customizable tax calculation import export predefined tax schemas productspecific taxes taxes shipping fees depending on the customer location tax exempt feature gst pst canadian tax system configurable measurement units date time formats and currency symbol export sales customer data for use in a spreadsheet export orders to ms excel xp format litecommerce is compatible with  ',0);
INSERT INTO `drupal_search_dataset` VALUES (2,'node',' download download the ecommerce cms package install the package on your web server customize its design and functionalityto your needs configure shipping methods add products select a payment gateway start selling drupal litecommerce in a single package a single ecommerce cms package includes the latest stable version of drupal with litesky cms theme and a set of popular modules integrated with the alpha version of litecommerce v3 shopping cart litecommerce v3 alpha version is not recommended for production sites however users wishing to help test and develop litecommerce are encouraged to use this package instead of downloading and installing all the components individually download drupal v 616 including lc connector lc theme and popular modules litecommerce v3 alpha litecommerce v3 standalone standalone litecommerce v3 alpha can be integrated with an existing drupalbased site via lc connector module since it is an alpha version it is not recommended for production sites however users wishing to help test and develop litecommerce may use it either in a standalone mode or in connection with drupal download litecommerce v3 alpha lc connector module for drupal 6x this drupal module integrates litecommerce storefront pages and blocks into a drupalbased website download lc connector module bettercrumbs module for drupal 6x this drupal module allows you to hide show breadcrumbs for each drupal node download bettercrumbs module  litesky cms theme for drupal 6x this drupal 6x theme includes css styles needed to display litecommerce pages and blocks properly and can be used for a regular drupalbased website as well as for an ecommerce website based on drupal litecommerce download litesky cms theme system requirements php ver520 or higher mysql ver503 or higher gdlib ver20 or higher ssl sertificate recommended  purchase the complete list of requirements ',0);
INSERT INTO `drupal_search_dataset` VALUES (3,'node',' community found a bug  report search  ',0);
INSERT INTO `drupal_search_dataset` VALUES (5,'node',' company about us blog contact us ',0);
INSERT INTO `drupal_search_dataset` VALUES (6,'node',' about us litecommerce v3 is a product of creative development dba qualiteam our company has been making ecommerce software since 2001 our main focus is on commercial open source php shopping cart software our products are fcart xcart litecommerce and ecwid started with just three employees our company has grown to over 140 people over 50000 web stores are based on solutions designed by creative development the mission of our company is to make the web both convenient and effective business environment for us our clients and the clients of our clients if you need more information contact us or read our blog ',0);
/*!40000 ALTER TABLE `drupal_search_dataset` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_search_index`
--

DROP TABLE IF EXISTS `drupal_search_index`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_search_index` (
  `word` varchar(50) NOT NULL DEFAULT '' COMMENT 'The drupal_search_total.word that is associated with the search item.',
  `sid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The drupal_search_dataset.sid of the searchable item to which the word belongs.',
  `type` varchar(16) NOT NULL COMMENT 'The drupal_search_dataset.type of the searchable item to which the word belongs.',
  `score` float DEFAULT NULL COMMENT 'The numeric score of the word, higher being more important.',
  PRIMARY KEY (`word`,`sid`,`type`),
  KEY `sid_type` (`sid`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores the search index, associating words, items and...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_search_index`
--

LOCK TABLES `drupal_search_index` WRITE;
/*!40000 ALTER TABLE `drupal_search_index` DISABLE KEYS */;
INSERT INTO `drupal_search_index` VALUES ('10',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('140',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('2',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('2001',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('3',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('4',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('50000',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('6',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('616',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('about',5,'node',11);
INSERT INTO `drupal_search_index` VALUES ('about',6,'node',26);
INSERT INTO `drupal_search_index` VALUES ('add',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('added',1,'node',0.927431);
INSERT INTO `drupal_search_index` VALUES ('admindefined',1,'node',0.740689);
INSERT INTO `drupal_search_index` VALUES ('administrator',1,'node',0.792123);
INSERT INTO `drupal_search_index` VALUES ('ajax',1,'node',0.886095);
INSERT INTO `drupal_search_index` VALUES ('all',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('allows',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('allows',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('alpha',2,'node',16);
INSERT INTO `drupal_search_index` VALUES ('amount',1,'node',0.754681);
INSERT INTO `drupal_search_index` VALUES ('and',1,'node',15.4408);
INSERT INTO `drupal_search_index` VALUES ('and',2,'node',9);
INSERT INTO `drupal_search_index` VALUES ('and',6,'node',3);
INSERT INTO `drupal_search_index` VALUES ('architecture',1,'node',2);
INSERT INTO `drupal_search_index` VALUES ('are',1,'node',0.896076);
INSERT INTO `drupal_search_index` VALUES ('are',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('are',6,'node',2);
INSERT INTO `drupal_search_index` VALUES ('authorizenet',1,'node',0.766757);
INSERT INTO `drupal_search_index` VALUES ('automatic',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('based',1,'node',0.725015);
INSERT INTO `drupal_search_index` VALUES ('based',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('based',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('been',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('belong',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('bestsellers',1,'node',0.934703);
INSERT INTO `drupal_search_index` VALUES ('bettercrumbs',2,'node',12);
INSERT INTO `drupal_search_index` VALUES ('blocks',1,'node',1.90974);
INSERT INTO `drupal_search_index` VALUES ('blocks',2,'node',2);
INSERT INTO `drupal_search_index` VALUES ('blog',5,'node',11);
INSERT INTO `drupal_search_index` VALUES ('blog',6,'node',11);
INSERT INTO `drupal_search_index` VALUES ('both',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('both',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('breadcrumbs',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('browser',1,'node',0.869951);
INSERT INTO `drupal_search_index` VALUES ('browsers',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('bug',3,'node',19);
INSERT INTO `drupal_search_index` VALUES ('business',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('calculation',1,'node',0.716357);
INSERT INTO `drupal_search_index` VALUES ('can',1,'node',3);
INSERT INTO `drupal_search_index` VALUES ('can',2,'node',2);
INSERT INTO `drupal_search_index` VALUES ('canadian',1,'node',0.687638);
INSERT INTO `drupal_search_index` VALUES ('cart',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('cart',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('catalog',1,'node',1.89608);
INSERT INTO `drupal_search_index` VALUES ('categories',1,'node',1.96109);
INSERT INTO `drupal_search_index` VALUES ('category',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('certificates',1,'node',0.920273);
INSERT INTO `drupal_search_index` VALUES ('checkout',1,'node',1.53845);
INSERT INTO `drupal_search_index` VALUES ('chrome',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('clients',6,'node',3);
INSERT INTO `drupal_search_index` VALUES ('cms',1,'node',12);
INSERT INTO `drupal_search_index` VALUES ('cms',2,'node',15);
INSERT INTO `drupal_search_index` VALUES ('commercial',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('community',3,'node',26);
INSERT INTO `drupal_search_index` VALUES ('company',5,'node',26);
INSERT INTO `drupal_search_index` VALUES ('company',6,'node',3);
INSERT INTO `drupal_search_index` VALUES ('compatible',1,'node',1.66116);
INSERT INTO `drupal_search_index` VALUES ('complete',2,'node',10.5295);
INSERT INTO `drupal_search_index` VALUES ('components',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('configurable',1,'node',1.44755);
INSERT INTO `drupal_search_index` VALUES ('configure',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('connected',1,'node',1.92645);
INSERT INTO `drupal_search_index` VALUES ('connection',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('connection',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('connector',2,'node',14);
INSERT INTO `drupal_search_index` VALUES ('contact',5,'node',11);
INSERT INTO `drupal_search_index` VALUES ('contact',6,'node',11);
INSERT INTO `drupal_search_index` VALUES ('convenient',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('core',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('creative',6,'node',2);
INSERT INTO `drupal_search_index` VALUES ('css',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('csv',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('currency',1,'node',0.676032);
INSERT INTO `drupal_search_index` VALUES ('custom',1,'node',1.97017);
INSERT INTO `drupal_search_index` VALUES ('customer',1,'node',1.37003);
INSERT INTO `drupal_search_index` VALUES ('customers',1,'node',0.794753);
INSERT INTO `drupal_search_index` VALUES ('customizable',1,'node',2.52181);
INSERT INTO `drupal_search_index` VALUES ('customize',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('customizing',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('data',1,'node',0.670377);
INSERT INTO `drupal_search_index` VALUES ('date',1,'node',0.681785);
INSERT INTO `drupal_search_index` VALUES ('dba',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('delivery',1,'node',0.738408);
INSERT INTO `drupal_search_index` VALUES ('depending',1,'node',0.7017);
INSERT INTO `drupal_search_index` VALUES ('design',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('design',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('designed',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('develop',2,'node',2);
INSERT INTO `drupal_search_index` VALUES ('development',6,'node',2);
INSERT INTO `drupal_search_index` VALUES ('discounts',1,'node',0.949597);
INSERT INTO `drupal_search_index` VALUES ('display',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('domestic',1,'node',0.72066);
INSERT INTO `drupal_search_index` VALUES ('download',2,'node',92);
INSERT INTO `drupal_search_index` VALUES ('downloading',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('drupal',1,'node',6.82532);
INSERT INTO `drupal_search_index` VALUES ('drupal',2,'node',12);
INSERT INTO `drupal_search_index` VALUES ('drupalbased',2,'node',3);
INSERT INTO `drupal_search_index` VALUES ('each',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('ecommerce',1,'node',11.8961);
INSERT INTO `drupal_search_index` VALUES ('ecommerce',2,'node',3);
INSERT INTO `drupal_search_index` VALUES ('ecommerce',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('ecwid',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('effective',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('either',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('email',1,'node',0.805455);
INSERT INTO `drupal_search_index` VALUES ('employees',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('encouraged',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('engine',1,'node',0.989021);
INSERT INTO `drupal_search_index` VALUES ('environment',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('excel',1,'node',0.664818);
INSERT INTO `drupal_search_index` VALUES ('exempt',1,'node',0.695602);
INSERT INTO `drupal_search_index` VALUES ('existing',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('experience',1,'node',0.913226);
INSERT INTO `drupal_search_index` VALUES ('export',1,'node',2.0513);
INSERT INTO `drupal_search_index` VALUES ('express',1,'node',0.774192);
INSERT INTO `drupal_search_index` VALUES ('fcart',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('feature',1,'node',0.693594);
INSERT INTO `drupal_search_index` VALUES ('featured',1,'node',0.938382);
INSERT INTO `drupal_search_index` VALUES ('features',1,'node',26);
INSERT INTO `drupal_search_index` VALUES ('fees',1,'node',0.703756);
INSERT INTO `drupal_search_index` VALUES ('files',1,'node',2);
INSERT INTO `drupal_search_index` VALUES ('firefox',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('flat',1,'node',0.733888);
INSERT INTO `drupal_search_index` VALUES ('flexible',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('focus',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('for',1,'node',3.20748);
INSERT INTO `drupal_search_index` VALUES ('for',2,'node',8);
INSERT INTO `drupal_search_index` VALUES ('for',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('form',1,'node',0.816452);
INSERT INTO `drupal_search_index` VALUES ('format',1,'node',0.662985);
INSERT INTO `drupal_search_index` VALUES ('formats',1,'node',0.677939);
INSERT INTO `drupal_search_index` VALUES ('found',3,'node',19);
INSERT INTO `drupal_search_index` VALUES ('free',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('friendly',1,'node',0.98493);
INSERT INTO `drupal_search_index` VALUES ('from',1,'node',2);
INSERT INTO `drupal_search_index` VALUES ('fully',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('function',1,'node',0.860547);
INSERT INTO `drupal_search_index` VALUES ('functionalityto',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('galleries',1,'node',0.876337);
INSERT INTO `drupal_search_index` VALUES ('gateway',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('gdlib',2,'node',0.976851);
INSERT INTO `drupal_search_index` VALUES ('generation',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('gift',1,'node',0.923838);
INSERT INTO `drupal_search_index` VALUES ('google',1,'node',0.769219);
INSERT INTO `drupal_search_index` VALUES ('grown',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('gst',1,'node',0.691597);
INSERT INTO `drupal_search_index` VALUES ('hacks',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('has',6,'node',2);
INSERT INTO `drupal_search_index` VALUES ('help',2,'node',2);
INSERT INTO `drupal_search_index` VALUES ('hide',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('higher',2,'node',2.93478);
INSERT INTO `drupal_search_index` VALUES ('history',1,'node',0.797402);
INSERT INTO `drupal_search_index` VALUES ('horizontal',1,'node',0.830633);
INSERT INTO `drupal_search_index` VALUES ('however',2,'node',2);
INSERT INTO `drupal_search_index` VALUES ('https',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('image',1,'node',2.74629);
INSERT INTO `drupal_search_index` VALUES ('import',1,'node',1.71423);
INSERT INTO `drupal_search_index` VALUES ('includes',2,'node',2);
INSERT INTO `drupal_search_index` VALUES ('including',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('individually',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('information',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('inpage',1,'node',0.866793);
INSERT INTO `drupal_search_index` VALUES ('install',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('installation',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('installing',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('installs',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('instead',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('integrate',1,'node',0.906287);
INSERT INTO `drupal_search_index` VALUES ('integrated',2,'node',2);
INSERT INTO `drupal_search_index` VALUES ('integrates',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('interface',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('international',1,'node',0.722831);
INSERT INTO `drupal_search_index` VALUES ('into',1,'node',0.902857);
INSERT INTO `drupal_search_index` VALUES ('into',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('inventory',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('invoice',1,'node',0.784336);
INSERT INTO `drupal_search_index` VALUES ('its',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('just',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('latest',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('layout',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('limits',1,'node',0.752312);
INSERT INTO `drupal_search_index` VALUES ('links',1,'node',0.851346);
INSERT INTO `drupal_search_index` VALUES ('list',1,'node',27.6671);
INSERT INTO `drupal_search_index` VALUES ('list',2,'node',10.4874);
INSERT INTO `drupal_search_index` VALUES ('litecommerce',1,'node',1.66116);
INSERT INTO `drupal_search_index` VALUES ('litecommerce',2,'node',22);
INSERT INTO `drupal_search_index` VALUES ('litecommerce',6,'node',2);
INSERT INTO `drupal_search_index` VALUES ('litesky',2,'node',13);
INSERT INTO `drupal_search_index` VALUES ('local',1,'node',0.718502);
INSERT INTO `drupal_search_index` VALUES ('location',1,'node',0.697623);
INSERT INTO `drupal_search_index` VALUES ('main',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('make',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('making',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('managed',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('management',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('marketing',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('max',1,'node',0.757065);
INSERT INTO `drupal_search_index` VALUES ('may',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('measurement',1,'node',0.685676);
INSERT INTO `drupal_search_index` VALUES ('meta',1,'node',0.972861);
INSERT INTO `drupal_search_index` VALUES ('methods',1,'node',0.736141);
INSERT INTO `drupal_search_index` VALUES ('methods',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('min',1,'node',0.759465);
INSERT INTO `drupal_search_index` VALUES ('minicart',1,'node',0.824901);
INSERT INTO `drupal_search_index` VALUES ('mission',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('mode',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('modern',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('modifiers',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('modular',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('module',2,'node',27);
INSERT INTO `drupal_search_index` VALUES ('modules',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('modules',2,'node',2);
INSERT INTO `drupal_search_index` VALUES ('more',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('more',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('mouse',1,'node',0.848323);
INSERT INTO `drupal_search_index` VALUES ('mvc',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('mysql',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('mysql',2,'node',0.98493);
INSERT INTO `drupal_search_index` VALUES ('need',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('needed',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('needs',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('next',1,'node',0.854391);
INSERT INTO `drupal_search_index` VALUES ('node',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('not',2,'node',2);
INSERT INTO `drupal_search_index` VALUES ('notifications',1,'node',0.802752);
INSERT INTO `drupal_search_index` VALUES ('number',1,'node',1.74069);
INSERT INTO `drupal_search_index` VALUES ('objectoriented',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('one',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('online',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('open',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('opensource',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('opera',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('optional',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('options',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('order',1,'node',2.28417);
INSERT INTO `drupal_search_index` VALUES ('orders',1,'node',1.47849);
INSERT INTO `drupal_search_index` VALUES ('other',1,'node',0.961087);
INSERT INTO `drupal_search_index` VALUES ('our',6,'node',18);
INSERT INTO `drupal_search_index` VALUES ('over',6,'node',2);
INSERT INTO `drupal_search_index` VALUES ('package',1,'node',11);
INSERT INTO `drupal_search_index` VALUES ('package',2,'node',5);
INSERT INTO `drupal_search_index` VALUES ('page',1,'node',0.882818);
INSERT INTO `drupal_search_index` VALUES ('pages',1,'node',3.60065);
INSERT INTO `drupal_search_index` VALUES ('pages',2,'node',2);
INSERT INTO `drupal_search_index` VALUES ('payment',1,'node',0.78951);
INSERT INTO `drupal_search_index` VALUES ('payment',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('paypal',1,'node',1.55342);
INSERT INTO `drupal_search_index` VALUES ('people',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('per',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('php',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('php',2,'node',0.997306);
INSERT INTO `drupal_search_index` VALUES ('php',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('popular',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('popular',2,'node',2);
INSERT INTO `drupal_search_index` VALUES ('popup',1,'node',0.873132);
INSERT INTO `drupal_search_index` VALUES ('predefined',1,'node',0.712106);
INSERT INTO `drupal_search_index` VALUES ('previous',1,'node',0.857458);
INSERT INTO `drupal_search_index` VALUES ('price',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('pricing',1,'node',0.942091);
INSERT INTO `drupal_search_index` VALUES ('printing',1,'node',0.781775);
INSERT INTO `drupal_search_index` VALUES ('product',1,'node',6.68771);
INSERT INTO `drupal_search_index` VALUES ('product',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('production',2,'node',2);
INSERT INTO `drupal_search_index` VALUES ('products',1,'node',4.65026);
INSERT INTO `drupal_search_index` VALUES ('products',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('products',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('productspecific',1,'node',0.707906);
INSERT INTO `drupal_search_index` VALUES ('promotion',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('properly',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('pst',1,'node',0.689612);
INSERT INTO `drupal_search_index` VALUES ('purchase',2,'node',10.572);
INSERT INTO `drupal_search_index` VALUES ('qualiteam',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('quantities',1,'node',0.839384);
INSERT INTO `drupal_search_index` VALUES ('quick',1,'node',0.819249);
INSERT INTO `drupal_search_index` VALUES ('range',1,'node',0.727213);
INSERT INTO `drupal_search_index` VALUES ('rates',1,'node',1.46581);
INSERT INTO `drupal_search_index` VALUES ('read',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('readymade',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('realtime',1,'node',0.749958);
INSERT INTO `drupal_search_index` VALUES ('recently',1,'node',1.76458);
INSERT INTO `drupal_search_index` VALUES ('recommended',2,'node',2.96109);
INSERT INTO `drupal_search_index` VALUES ('regular',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('reloading',1,'node',0.879565);
INSERT INTO `drupal_search_index` VALUES ('report',3,'node',1);
INSERT INTO `drupal_search_index` VALUES ('requirements',2,'node',11.4456);
INSERT INTO `drupal_search_index` VALUES ('run',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('safari',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('sales',1,'node',0.672252);
INSERT INTO `drupal_search_index` VALUES ('schemas',1,'node',0.71);
INSERT INTO `drupal_search_index` VALUES ('search',1,'node',1.8096);
INSERT INTO `drupal_search_index` VALUES ('search',3,'node',1);
INSERT INTO `drupal_search_index` VALUES ('sections',1,'node',1.90974);
INSERT INTO `drupal_search_index` VALUES ('select',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('selling',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('sertificate',2,'node',0.96498);
INSERT INTO `drupal_search_index` VALUES ('server',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('set',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('setup',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('sharpening',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('shipping',1,'node',4.48376);
INSERT INTO `drupal_search_index` VALUES ('shipping',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('shopping',1,'node',0.916736);
INSERT INTO `drupal_search_index` VALUES ('shopping',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('shopping',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('show',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('sim',1,'node',0.76431);
INSERT INTO `drupal_search_index` VALUES ('simplifies',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('since',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('since',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('single',1,'node',0.899454);
INSERT INTO `drupal_search_index` VALUES ('single',2,'node',2);
INSERT INTO `drupal_search_index` VALUES ('site',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('sites',2,'node',2);
INSERT INTO `drupal_search_index` VALUES ('software',6,'node',2);
INSERT INTO `drupal_search_index` VALUES ('solution',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('solutions',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('source',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('spreadsheet',1,'node',0.66666);
INSERT INTO `drupal_search_index` VALUES ('ssl',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('ssl',2,'node',0.968904);
INSERT INTO `drupal_search_index` VALUES ('stable',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('standalone',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('standalone',2,'node',3);
INSERT INTO `drupal_search_index` VALUES ('standard',1,'node',0.776703);
INSERT INTO `drupal_search_index` VALUES ('start',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('started',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('status',1,'node',0.786915);
INSERT INTO `drupal_search_index` VALUES ('store',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('storefront',1,'node',1.90974);
INSERT INTO `drupal_search_index` VALUES ('storefront',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('stores',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('styles',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('support',1,'node',1.77923);
INSERT INTO `drupal_search_index` VALUES ('symbol',1,'node',0.674137);
INSERT INTO `drupal_search_index` VALUES ('system',1,'node',1.68568);
INSERT INTO `drupal_search_index` VALUES ('system',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('tags',1,'node',0.968904);
INSERT INTO `drupal_search_index` VALUES ('tax',1,'node',3.61581);
INSERT INTO `drupal_search_index` VALUES ('taxes',1,'node',1.40958);
INSERT INTO `drupal_search_index` VALUES ('test',2,'node',2);
INSERT INTO `drupal_search_index` VALUES ('than',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('that',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('the',1,'node',2.69966);
INSERT INTO `drupal_search_index` VALUES ('the',2,'node',15.5295);
INSERT INTO `drupal_search_index` VALUES ('the',6,'node',3);
INSERT INTO `drupal_search_index` VALUES ('theme',2,'node',15);
INSERT INTO `drupal_search_index` VALUES ('this',2,'node',4);
INSERT INTO `drupal_search_index` VALUES ('three',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('thumbnail',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('time',1,'node',0.679856);
INSERT INTO `drupal_search_index` VALUES ('tons',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('total',1,'node',0.729424);
INSERT INTO `drupal_search_index` VALUES ('tracking',1,'node',1.78434);
INSERT INTO `drupal_search_index` VALUES ('transparently',1,'node',0.909743);
INSERT INTO `drupal_search_index` VALUES ('units',1,'node',0.683725);
INSERT INTO `drupal_search_index` VALUES ('unlimited',1,'node',1.74069);
INSERT INTO `drupal_search_index` VALUES ('updated',1,'node',0.892724);
INSERT INTO `drupal_search_index` VALUES ('updates',1,'node',0.842342);
INSERT INTO `drupal_search_index` VALUES ('upgrades',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('ups',1,'node',0.747619);
INSERT INTO `drupal_search_index` VALUES ('urls',1,'node',0.980874);
INSERT INTO `drupal_search_index` VALUES ('use',1,'node',0.668514);
INSERT INTO `drupal_search_index` VALUES ('use',2,'node',2);
INSERT INTO `drupal_search_index` VALUES ('used',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('user',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('users',2,'node',2);
INSERT INTO `drupal_search_index` VALUES ('usps',1,'node',0.745294);
INSERT INTO `drupal_search_index` VALUES ('variation',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('ver20',2,'node',0.972861);
INSERT INTO `drupal_search_index` VALUES ('ver503',2,'node',0.980874);
INSERT INTO `drupal_search_index` VALUES ('ver520',2,'node',0.993146);
INSERT INTO `drupal_search_index` VALUES ('version',2,'node',4);
INSERT INTO `drupal_search_index` VALUES ('vertical',1,'node',0.827757);
INSERT INTO `drupal_search_index` VALUES ('via',1,'node',0.889397);
INSERT INTO `drupal_search_index` VALUES ('via',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('viewed',1,'node',0.833529);
INSERT INTO `drupal_search_index` VALUES ('web',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('web',6,'node',2);
INSERT INTO `drupal_search_index` VALUES ('webbased',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('website',1,'node',1.8533);
INSERT INTO `drupal_search_index` VALUES ('website',2,'node',3);
INSERT INTO `drupal_search_index` VALUES ('weight',1,'node',0.731649);
INSERT INTO `drupal_search_index` VALUES ('well',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('wheel',1,'node',0.845322);
INSERT INTO `drupal_search_index` VALUES ('when',1,'node',2.92645);
INSERT INTO `drupal_search_index` VALUES ('wholesale',1,'node',0.945829);
INSERT INTO `drupal_search_index` VALUES ('widgets',1,'node',0.822065);
INSERT INTO `drupal_search_index` VALUES ('wish',1,'node',0.836446);
INSERT INTO `drupal_search_index` VALUES ('wishing',2,'node',2);
INSERT INTO `drupal_search_index` VALUES ('with',1,'node',7.43715);
INSERT INTO `drupal_search_index` VALUES ('with',2,'node',4);
INSERT INTO `drupal_search_index` VALUES ('with',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('without',1,'node',1.88282);
INSERT INTO `drupal_search_index` VALUES ('wizard',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('work',1,'node',1);
INSERT INTO `drupal_search_index` VALUES ('xcart',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('you',2,'node',1);
INSERT INTO `drupal_search_index` VALUES ('you',6,'node',1);
INSERT INTO `drupal_search_index` VALUES ('your',2,'node',2);
INSERT INTO `drupal_search_index` VALUES ('zoom',1,'node',0.863659);
/*!40000 ALTER TABLE `drupal_search_index` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_search_node_links`
--

DROP TABLE IF EXISTS `drupal_search_node_links`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_search_node_links` (
  `sid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The drupal_search_dataset.sid of the searchable item containing the link to the node.',
  `type` varchar(16) NOT NULL DEFAULT '' COMMENT 'The drupal_search_dataset.type of the searchable item containing the link to the node.',
  `nid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The drupal_node.nid that this item links to.',
  `caption` longtext COMMENT 'The text used to link to the drupal_node.nid.',
  PRIMARY KEY (`sid`,`type`,`nid`),
  KEY `nid` (`nid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores items (like nodes) that link to other nodes, used...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_search_node_links`
--

LOCK TABLES `drupal_search_node_links` WRITE;
/*!40000 ALTER TABLE `drupal_search_node_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_search_node_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_search_total`
--

DROP TABLE IF EXISTS `drupal_search_total`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_search_total` (
  `word` varchar(50) NOT NULL DEFAULT '' COMMENT 'Primary Key: Unique word in the search index.',
  `count` float DEFAULT NULL COMMENT 'The count of the word in the index using Zipf’s law to equalize the probability distribution.',
  PRIMARY KEY (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores search totals for words.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_search_total`
--

LOCK TABLES `drupal_search_total` WRITE;
/*!40000 ALTER TABLE `drupal_search_total` DISABLE KEYS */;
INSERT INTO `drupal_search_total` VALUES ('10',0.30103);
INSERT INTO `drupal_search_total` VALUES ('140',0.30103);
INSERT INTO `drupal_search_total` VALUES ('2',0.30103);
INSERT INTO `drupal_search_total` VALUES ('2001',0.30103);
INSERT INTO `drupal_search_total` VALUES ('3',0.30103);
INSERT INTO `drupal_search_total` VALUES ('4',0.30103);
INSERT INTO `drupal_search_total` VALUES ('50000',0.30103);
INSERT INTO `drupal_search_total` VALUES ('6',0.30103);
INSERT INTO `drupal_search_total` VALUES ('616',0.30103);
INSERT INTO `drupal_search_total` VALUES ('about',0.011582);
INSERT INTO `drupal_search_total` VALUES ('add',0.30103);
INSERT INTO `drupal_search_total` VALUES ('added',0.30103);
INSERT INTO `drupal_search_total` VALUES ('admindefined',0.30103);
INSERT INTO `drupal_search_total` VALUES ('administrator',0.30103);
INSERT INTO `drupal_search_total` VALUES ('ajax',0.30103);
INSERT INTO `drupal_search_total` VALUES ('all',0.30103);
INSERT INTO `drupal_search_total` VALUES ('allows',0.176091);
INSERT INTO `drupal_search_total` VALUES ('alpha',0.026329);
INSERT INTO `drupal_search_total` VALUES ('amount',0.30103);
INSERT INTO `drupal_search_total` VALUES ('and',0.015545);
INSERT INTO `drupal_search_total` VALUES ('architecture',0.176091);
INSERT INTO `drupal_search_total` VALUES ('are',0.099221);
INSERT INTO `drupal_search_total` VALUES ('authorizenet',0.30103);
INSERT INTO `drupal_search_total` VALUES ('automatic',0.30103);
INSERT INTO `drupal_search_total` VALUES ('based',0.135759);
INSERT INTO `drupal_search_total` VALUES ('been',0.30103);
INSERT INTO `drupal_search_total` VALUES ('belong',0.30103);
INSERT INTO `drupal_search_total` VALUES ('bestsellers',0.30103);
INSERT INTO `drupal_search_total` VALUES ('bettercrumbs',0.034762);
INSERT INTO `drupal_search_total` VALUES ('blocks',0.098911);
INSERT INTO `drupal_search_total` VALUES ('blog',0.019305);
INSERT INTO `drupal_search_total` VALUES ('both',0.176091);
INSERT INTO `drupal_search_total` VALUES ('breadcrumbs',0.30103);
INSERT INTO `drupal_search_total` VALUES ('browser',0.30103);
INSERT INTO `drupal_search_total` VALUES ('browsers',0.30103);
INSERT INTO `drupal_search_total` VALUES ('bug',0.022276);
INSERT INTO `drupal_search_total` VALUES ('business',0.30103);
INSERT INTO `drupal_search_total` VALUES ('calculation',0.30103);
INSERT INTO `drupal_search_total` VALUES ('can',0.079181);
INSERT INTO `drupal_search_total` VALUES ('canadian',0.30103);
INSERT INTO `drupal_search_total` VALUES ('cart',0.176091);
INSERT INTO `drupal_search_total` VALUES ('catalog',0.183954);
INSERT INTO `drupal_search_total` VALUES ('categories',0.178954);
INSERT INTO `drupal_search_total` VALUES ('category',0.30103);
INSERT INTO `drupal_search_total` VALUES ('certificates',0.30103);
INSERT INTO `drupal_search_total` VALUES ('checkout',0.217485);
INSERT INTO `drupal_search_total` VALUES ('chrome',0.30103);
INSERT INTO `drupal_search_total` VALUES ('clients',0.124939);
INSERT INTO `drupal_search_total` VALUES ('cms',0.015794);
INSERT INTO `drupal_search_total` VALUES ('commercial',0.30103);
INSERT INTO `drupal_search_total` VALUES ('community',0.01639);
INSERT INTO `drupal_search_total` VALUES ('company',0.014723);
INSERT INTO `drupal_search_total` VALUES ('compatible',0.204659);
INSERT INTO `drupal_search_total` VALUES ('complete',0.039403);
INSERT INTO `drupal_search_total` VALUES ('components',0.30103);
INSERT INTO `drupal_search_total` VALUES ('configurable',0.228097);
INSERT INTO `drupal_search_total` VALUES ('configure',0.30103);
INSERT INTO `drupal_search_total` VALUES ('connected',0.181584);
INSERT INTO `drupal_search_total` VALUES ('connection',0.176091);
INSERT INTO `drupal_search_total` VALUES ('connector',0.029963);
INSERT INTO `drupal_search_total` VALUES ('contact',0.019305);
INSERT INTO `drupal_search_total` VALUES ('convenient',0.30103);
INSERT INTO `drupal_search_total` VALUES ('core',0.30103);
INSERT INTO `drupal_search_total` VALUES ('creative',0.176091);
INSERT INTO `drupal_search_total` VALUES ('css',0.30103);
INSERT INTO `drupal_search_total` VALUES ('csv',0.30103);
INSERT INTO `drupal_search_total` VALUES ('currency',0.30103);
INSERT INTO `drupal_search_total` VALUES ('custom',0.178278);
INSERT INTO `drupal_search_total` VALUES ('customer',0.238023);
INSERT INTO `drupal_search_total` VALUES ('customers',0.30103);
INSERT INTO `drupal_search_total` VALUES ('customizable',0.145053);
INSERT INTO `drupal_search_total` VALUES ('customize',0.30103);
INSERT INTO `drupal_search_total` VALUES ('customizing',0.30103);
INSERT INTO `drupal_search_total` VALUES ('data',0.30103);
INSERT INTO `drupal_search_total` VALUES ('date',0.30103);
INSERT INTO `drupal_search_total` VALUES ('dba',0.30103);
INSERT INTO `drupal_search_total` VALUES ('delivery',0.30103);
INSERT INTO `drupal_search_total` VALUES ('depending',0.30103);
INSERT INTO `drupal_search_total` VALUES ('design',0.176091);
INSERT INTO `drupal_search_total` VALUES ('designed',0.30103);
INSERT INTO `drupal_search_total` VALUES ('develop',0.176091);
INSERT INTO `drupal_search_total` VALUES ('development',0.176091);
INSERT INTO `drupal_search_total` VALUES ('discounts',0.30103);
INSERT INTO `drupal_search_total` VALUES ('display',0.30103);
INSERT INTO `drupal_search_total` VALUES ('domestic',0.30103);
INSERT INTO `drupal_search_total` VALUES ('download',0.004695);
INSERT INTO `drupal_search_total` VALUES ('downloading',0.30103);
INSERT INTO `drupal_search_total` VALUES ('drupal',0.022478);
INSERT INTO `drupal_search_total` VALUES ('drupalbased',0.124939);
INSERT INTO `drupal_search_total` VALUES ('each',0.30103);
INSERT INTO `drupal_search_total` VALUES ('ecommerce',0.026496);
INSERT INTO `drupal_search_total` VALUES ('ecwid',0.30103);
INSERT INTO `drupal_search_total` VALUES ('effective',0.30103);
INSERT INTO `drupal_search_total` VALUES ('either',0.30103);
INSERT INTO `drupal_search_total` VALUES ('email',0.30103);
INSERT INTO `drupal_search_total` VALUES ('employees',0.30103);
INSERT INTO `drupal_search_total` VALUES ('encouraged',0.30103);
INSERT INTO `drupal_search_total` VALUES ('engine',0.30103);
INSERT INTO `drupal_search_total` VALUES ('environment',0.30103);
INSERT INTO `drupal_search_total` VALUES ('excel',0.30103);
INSERT INTO `drupal_search_total` VALUES ('exempt',0.30103);
INSERT INTO `drupal_search_total` VALUES ('existing',0.30103);
INSERT INTO `drupal_search_total` VALUES ('experience',0.30103);
INSERT INTO `drupal_search_total` VALUES ('export',0.172456);
INSERT INTO `drupal_search_total` VALUES ('express',0.30103);
INSERT INTO `drupal_search_total` VALUES ('fcart',0.30103);
INSERT INTO `drupal_search_total` VALUES ('feature',0.30103);
INSERT INTO `drupal_search_total` VALUES ('featured',0.30103);
INSERT INTO `drupal_search_total` VALUES ('features',0.01639);
INSERT INTO `drupal_search_total` VALUES ('fees',0.30103);
INSERT INTO `drupal_search_total` VALUES ('files',0.176091);
INSERT INTO `drupal_search_total` VALUES ('firefox',0.30103);
INSERT INTO `drupal_search_total` VALUES ('flat',0.30103);
INSERT INTO `drupal_search_total` VALUES ('flexible',0.30103);
INSERT INTO `drupal_search_total` VALUES ('focus',0.30103);
INSERT INTO `drupal_search_total` VALUES ('for',0.034194);
INSERT INTO `drupal_search_total` VALUES ('form',0.30103);
INSERT INTO `drupal_search_total` VALUES ('format',0.30103);
INSERT INTO `drupal_search_total` VALUES ('formats',0.30103);
INSERT INTO `drupal_search_total` VALUES ('found',0.022276);
INSERT INTO `drupal_search_total` VALUES ('free',0.30103);
INSERT INTO `drupal_search_total` VALUES ('friendly',0.30103);
INSERT INTO `drupal_search_total` VALUES ('from',0.176091);
INSERT INTO `drupal_search_total` VALUES ('fully',0.30103);
INSERT INTO `drupal_search_total` VALUES ('function',0.30103);
INSERT INTO `drupal_search_total` VALUES ('functionalityto',0.30103);
INSERT INTO `drupal_search_total` VALUES ('galleries',0.30103);
INSERT INTO `drupal_search_total` VALUES ('gateway',0.30103);
INSERT INTO `drupal_search_total` VALUES ('gdlib',0.30103);
INSERT INTO `drupal_search_total` VALUES ('generation',0.30103);
INSERT INTO `drupal_search_total` VALUES ('gift',0.30103);
INSERT INTO `drupal_search_total` VALUES ('google',0.30103);
INSERT INTO `drupal_search_total` VALUES ('grown',0.30103);
INSERT INTO `drupal_search_total` VALUES ('gst',0.30103);
INSERT INTO `drupal_search_total` VALUES ('hacks',0.30103);
INSERT INTO `drupal_search_total` VALUES ('has',0.176091);
INSERT INTO `drupal_search_total` VALUES ('help',0.176091);
INSERT INTO `drupal_search_total` VALUES ('hide',0.30103);
INSERT INTO `drupal_search_total` VALUES ('higher',0.127345);
INSERT INTO `drupal_search_total` VALUES ('history',0.30103);
INSERT INTO `drupal_search_total` VALUES ('horizontal',0.30103);
INSERT INTO `drupal_search_total` VALUES ('however',0.176091);
INSERT INTO `drupal_search_total` VALUES ('https',0.30103);
INSERT INTO `drupal_search_total` VALUES ('image',0.134855);
INSERT INTO `drupal_search_total` VALUES ('import',0.199578);
INSERT INTO `drupal_search_total` VALUES ('includes',0.176091);
INSERT INTO `drupal_search_total` VALUES ('including',0.30103);
INSERT INTO `drupal_search_total` VALUES ('individually',0.30103);
INSERT INTO `drupal_search_total` VALUES ('information',0.30103);
INSERT INTO `drupal_search_total` VALUES ('inpage',0.30103);
INSERT INTO `drupal_search_total` VALUES ('install',0.30103);
INSERT INTO `drupal_search_total` VALUES ('installation',0.30103);
INSERT INTO `drupal_search_total` VALUES ('installing',0.30103);
INSERT INTO `drupal_search_total` VALUES ('installs',0.30103);
INSERT INTO `drupal_search_total` VALUES ('instead',0.30103);
INSERT INTO `drupal_search_total` VALUES ('integrate',0.30103);
INSERT INTO `drupal_search_total` VALUES ('integrated',0.176091);
INSERT INTO `drupal_search_total` VALUES ('integrates',0.30103);
INSERT INTO `drupal_search_total` VALUES ('interface',0.30103);
INSERT INTO `drupal_search_total` VALUES ('international',0.30103);
INSERT INTO `drupal_search_total` VALUES ('into',0.183419);
INSERT INTO `drupal_search_total` VALUES ('inventory',0.30103);
INSERT INTO `drupal_search_total` VALUES ('invoice',0.30103);
INSERT INTO `drupal_search_total` VALUES ('its',0.30103);
INSERT INTO `drupal_search_total` VALUES ('just',0.30103);
INSERT INTO `drupal_search_total` VALUES ('latest',0.30103);
INSERT INTO `drupal_search_total` VALUES ('layout',0.30103);
INSERT INTO `drupal_search_total` VALUES ('limits',0.30103);
INSERT INTO `drupal_search_total` VALUES ('links',0.30103);
INSERT INTO `drupal_search_total` VALUES ('list',0.011236);
INSERT INTO `drupal_search_total` VALUES ('litecommerce',0.016603);
INSERT INTO `drupal_search_total` VALUES ('litesky',0.032185);
INSERT INTO `drupal_search_total` VALUES ('local',0.30103);
INSERT INTO `drupal_search_total` VALUES ('location',0.30103);
INSERT INTO `drupal_search_total` VALUES ('main',0.30103);
INSERT INTO `drupal_search_total` VALUES ('make',0.30103);
INSERT INTO `drupal_search_total` VALUES ('making',0.30103);
INSERT INTO `drupal_search_total` VALUES ('managed',0.30103);
INSERT INTO `drupal_search_total` VALUES ('management',0.30103);
INSERT INTO `drupal_search_total` VALUES ('marketing',0.30103);
INSERT INTO `drupal_search_total` VALUES ('max',0.30103);
INSERT INTO `drupal_search_total` VALUES ('may',0.30103);
INSERT INTO `drupal_search_total` VALUES ('measurement',0.30103);
INSERT INTO `drupal_search_total` VALUES ('meta',0.30103);
INSERT INTO `drupal_search_total` VALUES ('methods',0.197553);
INSERT INTO `drupal_search_total` VALUES ('min',0.30103);
INSERT INTO `drupal_search_total` VALUES ('minicart',0.30103);
INSERT INTO `drupal_search_total` VALUES ('mission',0.30103);
INSERT INTO `drupal_search_total` VALUES ('mode',0.30103);
INSERT INTO `drupal_search_total` VALUES ('modern',0.30103);
INSERT INTO `drupal_search_total` VALUES ('modifiers',0.30103);
INSERT INTO `drupal_search_total` VALUES ('modular',0.30103);
INSERT INTO `drupal_search_total` VALUES ('module',0.015794);
INSERT INTO `drupal_search_total` VALUES ('modules',0.124939);
INSERT INTO `drupal_search_total` VALUES ('more',0.176091);
INSERT INTO `drupal_search_total` VALUES ('mouse',0.30103);
INSERT INTO `drupal_search_total` VALUES ('mvc',0.30103);
INSERT INTO `drupal_search_total` VALUES ('mysql',0.177189);
INSERT INTO `drupal_search_total` VALUES ('need',0.30103);
INSERT INTO `drupal_search_total` VALUES ('needed',0.30103);
INSERT INTO `drupal_search_total` VALUES ('needs',0.30103);
INSERT INTO `drupal_search_total` VALUES ('next',0.30103);
INSERT INTO `drupal_search_total` VALUES ('node',0.30103);
INSERT INTO `drupal_search_total` VALUES ('not',0.176091);
INSERT INTO `drupal_search_total` VALUES ('notifications',0.30103);
INSERT INTO `drupal_search_total` VALUES ('number',0.197139);
INSERT INTO `drupal_search_total` VALUES ('objectoriented',0.30103);
INSERT INTO `drupal_search_total` VALUES ('one',0.30103);
INSERT INTO `drupal_search_total` VALUES ('online',0.30103);
INSERT INTO `drupal_search_total` VALUES ('open',0.30103);
INSERT INTO `drupal_search_total` VALUES ('opensource',0.30103);
INSERT INTO `drupal_search_total` VALUES ('opera',0.30103);
INSERT INTO `drupal_search_total` VALUES ('optional',0.30103);
INSERT INTO `drupal_search_total` VALUES ('options',0.30103);
INSERT INTO `drupal_search_total` VALUES ('order',0.157697);
INSERT INTO `drupal_search_total` VALUES ('orders',0.224369);
INSERT INTO `drupal_search_total` VALUES ('other',0.30103);
INSERT INTO `drupal_search_total` VALUES ('our',0.023481);
INSERT INTO `drupal_search_total` VALUES ('over',0.176091);
INSERT INTO `drupal_search_total` VALUES ('package',0.026329);
INSERT INTO `drupal_search_total` VALUES ('page',0.30103);
INSERT INTO `drupal_search_total` VALUES ('pages',0.071348);
INSERT INTO `drupal_search_total` VALUES ('payment',0.192794);
INSERT INTO `drupal_search_total` VALUES ('paypal',0.215833);
INSERT INTO `drupal_search_total` VALUES ('people',0.30103);
INSERT INTO `drupal_search_total` VALUES ('per',0.30103);
INSERT INTO `drupal_search_total` VALUES ('php',0.125036);
INSERT INTO `drupal_search_total` VALUES ('popular',0.124939);
INSERT INTO `drupal_search_total` VALUES ('popup',0.30103);
INSERT INTO `drupal_search_total` VALUES ('predefined',0.30103);
INSERT INTO `drupal_search_total` VALUES ('previous',0.30103);
INSERT INTO `drupal_search_total` VALUES ('price',0.30103);
INSERT INTO `drupal_search_total` VALUES ('pricing',0.30103);
INSERT INTO `drupal_search_total` VALUES ('printing',0.30103);
INSERT INTO `drupal_search_total` VALUES ('product',0.053108);
INSERT INTO `drupal_search_total` VALUES ('production',0.176091);
INSERT INTO `drupal_search_total` VALUES ('products',0.060838);
INSERT INTO `drupal_search_total` VALUES ('productspecific',0.30103);
INSERT INTO `drupal_search_total` VALUES ('promotion',0.30103);
INSERT INTO `drupal_search_total` VALUES ('properly',0.30103);
INSERT INTO `drupal_search_total` VALUES ('pst',0.30103);
INSERT INTO `drupal_search_total` VALUES ('purchase',0.039251);
INSERT INTO `drupal_search_total` VALUES ('qualiteam',0.30103);
INSERT INTO `drupal_search_total` VALUES ('quantities',0.30103);
INSERT INTO `drupal_search_total` VALUES ('quick',0.30103);
INSERT INTO `drupal_search_total` VALUES ('range',0.30103);
INSERT INTO `drupal_search_total` VALUES ('rates',0.225881);
INSERT INTO `drupal_search_total` VALUES ('read',0.30103);
INSERT INTO `drupal_search_total` VALUES ('readymade',0.30103);
INSERT INTO `drupal_search_total` VALUES ('realtime',0.30103);
INSERT INTO `drupal_search_total` VALUES ('recently',0.194988);
INSERT INTO `drupal_search_total` VALUES ('recommended',0.126363);
INSERT INTO `drupal_search_total` VALUES ('regular',0.30103);
INSERT INTO `drupal_search_total` VALUES ('reloading',0.30103);
INSERT INTO `drupal_search_total` VALUES ('report',0.30103);
INSERT INTO `drupal_search_total` VALUES ('requirements',0.036377);
INSERT INTO `drupal_search_total` VALUES ('run',0.30103);
INSERT INTO `drupal_search_total` VALUES ('safari',0.30103);
INSERT INTO `drupal_search_total` VALUES ('sales',0.30103);
INSERT INTO `drupal_search_total` VALUES ('schemas',0.30103);
INSERT INTO `drupal_search_total` VALUES ('search',0.132235);
INSERT INTO `drupal_search_total` VALUES ('sections',0.18288);
INSERT INTO `drupal_search_total` VALUES ('select',0.30103);
INSERT INTO `drupal_search_total` VALUES ('selling',0.30103);
INSERT INTO `drupal_search_total` VALUES ('sertificate',0.30103);
INSERT INTO `drupal_search_total` VALUES ('server',0.30103);
INSERT INTO `drupal_search_total` VALUES ('set',0.30103);
INSERT INTO `drupal_search_total` VALUES ('setup',0.30103);
INSERT INTO `drupal_search_total` VALUES ('sharpening',0.30103);
INSERT INTO `drupal_search_total` VALUES ('shipping',0.072749);
INSERT INTO `drupal_search_total` VALUES ('shopping',0.128027);
INSERT INTO `drupal_search_total` VALUES ('show',0.30103);
INSERT INTO `drupal_search_total` VALUES ('sim',0.30103);
INSERT INTO `drupal_search_total` VALUES ('simplifies',0.30103);
INSERT INTO `drupal_search_total` VALUES ('since',0.176091);
INSERT INTO `drupal_search_total` VALUES ('single',0.128688);
INSERT INTO `drupal_search_total` VALUES ('site',0.30103);
INSERT INTO `drupal_search_total` VALUES ('sites',0.176091);
INSERT INTO `drupal_search_total` VALUES ('software',0.176091);
INSERT INTO `drupal_search_total` VALUES ('solution',0.30103);
INSERT INTO `drupal_search_total` VALUES ('solutions',0.30103);
INSERT INTO `drupal_search_total` VALUES ('source',0.30103);
INSERT INTO `drupal_search_total` VALUES ('spreadsheet',0.30103);
INSERT INTO `drupal_search_total` VALUES ('ssl',0.178372);
INSERT INTO `drupal_search_total` VALUES ('stable',0.30103);
INSERT INTO `drupal_search_total` VALUES ('standalone',0.09691);
INSERT INTO `drupal_search_total` VALUES ('standard',0.30103);
INSERT INTO `drupal_search_total` VALUES ('start',0.30103);
INSERT INTO `drupal_search_total` VALUES ('started',0.30103);
INSERT INTO `drupal_search_total` VALUES ('status',0.30103);
INSERT INTO `drupal_search_total` VALUES ('store',0.30103);
INSERT INTO `drupal_search_total` VALUES ('storefront',0.128294);
INSERT INTO `drupal_search_total` VALUES ('stores',0.30103);
INSERT INTO `drupal_search_total` VALUES ('styles',0.30103);
INSERT INTO `drupal_search_total` VALUES ('support',0.193692);
INSERT INTO `drupal_search_total` VALUES ('symbol',0.30103);
INSERT INTO `drupal_search_total` VALUES ('system',0.137464);
INSERT INTO `drupal_search_total` VALUES ('tags',0.30103);
INSERT INTO `drupal_search_total` VALUES ('tax',0.106042);
INSERT INTO `drupal_search_total` VALUES ('taxes',0.232851);
INSERT INTO `drupal_search_total` VALUES ('test',0.176091);
INSERT INTO `drupal_search_total` VALUES ('than',0.30103);
INSERT INTO `drupal_search_total` VALUES ('that',0.30103);
INSERT INTO `drupal_search_total` VALUES ('the',0.01999);
INSERT INTO `drupal_search_total` VALUES ('theme',0.028029);
INSERT INTO `drupal_search_total` VALUES ('this',0.09691);
INSERT INTO `drupal_search_total` VALUES ('three',0.30103);
INSERT INTO `drupal_search_total` VALUES ('thumbnail',0.30103);
INSERT INTO `drupal_search_total` VALUES ('time',0.30103);
INSERT INTO `drupal_search_total` VALUES ('tons',0.30103);
INSERT INTO `drupal_search_total` VALUES ('total',0.30103);
INSERT INTO `drupal_search_total` VALUES ('tracking',0.193245);
INSERT INTO `drupal_search_total` VALUES ('transparently',0.30103);
INSERT INTO `drupal_search_total` VALUES ('units',0.30103);
INSERT INTO `drupal_search_total` VALUES ('unlimited',0.197139);
INSERT INTO `drupal_search_total` VALUES ('updated',0.30103);
INSERT INTO `drupal_search_total` VALUES ('updates',0.30103);
INSERT INTO `drupal_search_total` VALUES ('upgrades',0.30103);
INSERT INTO `drupal_search_total` VALUES ('ups',0.30103);
INSERT INTO `drupal_search_total` VALUES ('urls',0.30103);
INSERT INTO `drupal_search_total` VALUES ('use',0.138221);
INSERT INTO `drupal_search_total` VALUES ('used',0.30103);
INSERT INTO `drupal_search_total` VALUES ('user',0.30103);
INSERT INTO `drupal_search_total` VALUES ('users',0.176091);
INSERT INTO `drupal_search_total` VALUES ('usps',0.30103);
INSERT INTO `drupal_search_total` VALUES ('variation',0.30103);
INSERT INTO `drupal_search_total` VALUES ('ver20',0.30103);
INSERT INTO `drupal_search_total` VALUES ('ver503',0.30103);
INSERT INTO `drupal_search_total` VALUES ('ver520',0.30103);
INSERT INTO `drupal_search_total` VALUES ('version',0.09691);
INSERT INTO `drupal_search_total` VALUES ('vertical',0.30103);
INSERT INTO `drupal_search_total` VALUES ('via',0.184484);
INSERT INTO `drupal_search_total` VALUES ('viewed',0.30103);
INSERT INTO `drupal_search_total` VALUES ('web',0.124939);
INSERT INTO `drupal_search_total` VALUES ('webbased',0.30103);
INSERT INTO `drupal_search_total` VALUES ('website',0.081364);
INSERT INTO `drupal_search_total` VALUES ('weight',0.30103);
INSERT INTO `drupal_search_total` VALUES ('well',0.30103);
INSERT INTO `drupal_search_total` VALUES ('wheel',0.30103);
INSERT INTO `drupal_search_total` VALUES ('when',0.127659);
INSERT INTO `drupal_search_total` VALUES ('wholesale',0.30103);
INSERT INTO `drupal_search_total` VALUES ('widgets',0.30103);
INSERT INTO `drupal_search_total` VALUES ('wish',0.30103);
INSERT INTO `drupal_search_total` VALUES ('wishing',0.176091);
INSERT INTO `drupal_search_total` VALUES ('with',0.033586);
INSERT INTO `drupal_search_total` VALUES ('without',0.185009);
INSERT INTO `drupal_search_total` VALUES ('wizard',0.30103);
INSERT INTO `drupal_search_total` VALUES ('work',0.30103);
INSERT INTO `drupal_search_total` VALUES ('xcart',0.30103);
INSERT INTO `drupal_search_total` VALUES ('you',0.176091);
INSERT INTO `drupal_search_total` VALUES ('your',0.176091);
INSERT INTO `drupal_search_total` VALUES ('zoom',0.30103);
/*!40000 ALTER TABLE `drupal_search_total` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_semaphore`
--

DROP TABLE IF EXISTS `drupal_semaphore`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_semaphore` (
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT 'Primary Key: Unique name.',
  `value` varchar(255) NOT NULL DEFAULT '' COMMENT 'A value for the semaphore.',
  `expire` double NOT NULL COMMENT 'A Unix timestamp with microseconds indicating when the semaphore should expire.',
  PRIMARY KEY (`name`),
  KEY `value` (`value`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table for holding semaphores, locks, flags, etc. that...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_semaphore`
--

LOCK TABLES `drupal_semaphore` WRITE;
/*!40000 ALTER TABLE `drupal_semaphore` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_semaphore` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_sequences`
--

DROP TABLE IF EXISTS `drupal_sequences`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_sequences` (
  `value` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'The value of the sequence.',
  PRIMARY KEY (`value`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='Stores IDs.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_sequences`
--

LOCK TABLES `drupal_sequences` WRITE;
/*!40000 ALTER TABLE `drupal_sequences` DISABLE KEYS */;
INSERT INTO `drupal_sequences` VALUES (4);
/*!40000 ALTER TABLE `drupal_sequences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_sessions`
--

DROP TABLE IF EXISTS `drupal_sessions`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_sessions` (
  `uid` int(10) unsigned NOT NULL COMMENT 'The drupal_users.uid corresponding to a session, or 0 for anonymous user.',
  `sid` varchar(128) NOT NULL COMMENT 'A session ID. The value is generated by Drupal’s session handlers.',
  `ssid` varchar(128) NOT NULL DEFAULT '' COMMENT 'Secure session ID. The value is generated by Drupal’s session handlers.',
  `hostname` varchar(128) NOT NULL DEFAULT '' COMMENT 'The IP address that last used this session ID (sid).',
  `timestamp` int(11) NOT NULL DEFAULT '0' COMMENT 'The Unix timestamp when this session last requested a page. Old records are purged by PHP automatically.',
  `cache` int(11) NOT NULL DEFAULT '0' COMMENT 'The time of this user’s last post. This is used when the site has specified a minimum_cache_lifetime. See cache_get().',
  `session` longblob COMMENT 'The serialized contents of $_SESSION, an array of name/value pairs that persists across page requests by this session ID. Drupal loads $_SESSION from here at the start of each request and saves it at the end.',
  PRIMARY KEY (`sid`,`ssid`),
  KEY `timestamp` (`timestamp`),
  KEY `uid` (`uid`),
  KEY `ssid` (`ssid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Drupal’s session handlers read and write into the...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_sessions`
--

LOCK TABLES `drupal_sessions` WRITE;
/*!40000 ALTER TABLE `drupal_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_shortcut_set`
--

DROP TABLE IF EXISTS `drupal_shortcut_set`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_shortcut_set` (
  `set_name` varchar(32) NOT NULL DEFAULT '' COMMENT 'Primary Key: The drupal_menu_links.menu_name under which the set’s links are stored.',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT 'The title of the set.',
  PRIMARY KEY (`set_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores information about sets of shortcuts links.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_shortcut_set`
--

LOCK TABLES `drupal_shortcut_set` WRITE;
/*!40000 ALTER TABLE `drupal_shortcut_set` DISABLE KEYS */;
INSERT INTO `drupal_shortcut_set` VALUES ('shortcut-set-1','Default');
/*!40000 ALTER TABLE `drupal_shortcut_set` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_shortcut_set_users`
--

DROP TABLE IF EXISTS `drupal_shortcut_set_users`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_shortcut_set_users` (
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The drupal_users.uid for this set.',
  `set_name` varchar(32) NOT NULL DEFAULT '' COMMENT 'The drupal_shortcut_set.set_name that will be displayed for this user.',
  PRIMARY KEY (`uid`),
  KEY `set_name` (`set_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Maps users to shortcut sets.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_shortcut_set_users`
--

LOCK TABLES `drupal_shortcut_set_users` WRITE;
/*!40000 ALTER TABLE `drupal_shortcut_set_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_shortcut_set_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_system`
--

DROP TABLE IF EXISTS `drupal_system`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_system` (
  `filename` varchar(255) NOT NULL DEFAULT '' COMMENT 'The path of the primary file for this item, relative to the Drupal root; e.g. modules/node/node.module.',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT 'The name of the item; e.g. node.',
  `type` varchar(12) NOT NULL DEFAULT '' COMMENT 'The type of the item, either module, theme, theme_engine, or profile.',
  `owner` varchar(255) NOT NULL DEFAULT '' COMMENT 'A theme’s ’parent’ . Can be either a theme or an engine.',
  `status` int(11) NOT NULL DEFAULT '0' COMMENT 'Boolean indicating whether or not this item is enabled.',
  `bootstrap` int(11) NOT NULL DEFAULT '0' COMMENT 'Boolean indicating whether this module is loaded during Drupal’s early bootstrapping phase (e.g. even before the page cache is consulted).',
  `schema_version` smallint(6) NOT NULL DEFAULT '-1' COMMENT 'The module’s database schema version number. -1 if the module is not installed (its tables do not exist); 0 or the largest N of the module’s hook_update_N() function that has either been run or existed when the module was first installed.',
  `weight` int(11) NOT NULL DEFAULT '0' COMMENT 'The order in which this module’s hooks should be invoked relative to other modules. Equal-weighted modules are ordered by name.',
  `info` blob COMMENT 'A serialized array containing information from the module’s .info file; keys can include name, description, package, version, core, dependencies, and php.',
  PRIMARY KEY (`filename`),
  KEY `system_list` (`status`,`bootstrap`,`type`,`weight`,`name`),
  KEY `type_name` (`type`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='A list of all modules, themes, and theme engines that are...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_system`
--

LOCK TABLES `drupal_system` WRITE;
/*!40000 ALTER TABLE `drupal_system` DISABLE KEYS */;
INSERT INTO `drupal_system` VALUES ('modules/aggregator/aggregator.module','aggregator','module','',0,0,-1,0,'a:13:{s:4:\"name\";s:10:\"Aggregator\";s:11:\"description\";s:57:\"Aggregates syndicated content (RSS, RDF, and Atom feeds).\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:15:\"aggregator.test\";}s:9:\"configure\";s:41:\"admin/config/services/aggregator/settings\";s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:14:\"aggregator.css\";s:33:\"modules/aggregator/aggregator.css\";}}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/aggregator/tests/aggregator_test.module','aggregator_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:23:\"Aggregator module tests\";s:11:\"description\";s:46:\"Support module for aggregator related testing.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/block/block.module','block','module','',1,0,7007,-5,'a:12:{s:4:\"name\";s:5:\"Block\";s:11:\"description\";s:140:\"Controls the visual building blocks a page is constructed with. Blocks are boxes of content rendered into an area, or region, of a web page.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:10:\"block.test\";}s:9:\"configure\";s:21:\"admin/structure/block\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/block/tests/block_test.module','block_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:10:\"Block test\";s:11:\"description\";s:21:\"Provides test blocks.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/blog/blog.module','blog','module','',1,0,0,0,'a:11:{s:4:\"name\";s:4:\"Blog\";s:11:\"description\";s:25:\"Enables multi-user blogs.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:9:\"blog.test\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/book/book.module','book','module','',0,0,-1,0,'a:13:{s:4:\"name\";s:4:\"Book\";s:11:\"description\";s:66:\"Allows users to create and organize related content in an outline.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:9:\"book.test\";}s:9:\"configure\";s:27:\"admin/content/book/settings\";s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:8:\"book.css\";s:21:\"modules/book/book.css\";}}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/color/color.module','color','module','',1,0,0,0,'a:11:{s:4:\"name\";s:5:\"Color\";s:11:\"description\";s:70:\"Allows administrators to change the color scheme of compatible themes.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:10:\"color.test\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/comment/comment.module','comment','module','',1,0,7006,0,'a:13:{s:4:\"name\";s:7:\"Comment\";s:11:\"description\";s:57:\"Allows users to comment on and discuss published content.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:12:\"dependencies\";a:1:{i:0;s:4:\"text\";}s:5:\"files\";a:2:{i:0;s:14:\"comment.module\";i:1;s:12:\"comment.test\";}s:9:\"configure\";s:21:\"admin/content/comment\";s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:11:\"comment.css\";s:27:\"modules/comment/comment.css\";}}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/contact/contact.module','contact','module','',1,0,7003,0,'a:12:{s:4:\"name\";s:7:\"Contact\";s:11:\"description\";s:61:\"Enables the use of both personal and site-wide contact forms.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:12:\"contact.test\";}s:9:\"configure\";s:23:\"admin/structure/contact\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/contextual/contextual.module','contextual','module','',1,0,0,0,'a:11:{s:4:\"name\";s:16:\"Contextual links\";s:11:\"description\";s:75:\"Provides contextual links to perform actions related to elements on a page.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/dashboard/dashboard.module','dashboard','module','',1,0,0,0,'a:12:{s:4:\"name\";s:9:\"Dashboard\";s:11:\"description\";s:136:\"Provides a dashboard page in the administrative interface for organizing administrative tasks and tracking information within your site.\";s:4:\"core\";s:3:\"7.x\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:5:\"files\";a:1:{i:0;s:14:\"dashboard.test\";}s:12:\"dependencies\";a:1:{i:0;s:5:\"block\";}s:9:\"configure\";s:25:\"admin/dashboard/customize\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/dblog/dblog.module','dblog','module','',1,1,7001,0,'a:11:{s:4:\"name\";s:16:\"Database logging\";s:11:\"description\";s:47:\"Logs and records system events to the database.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:10:\"dblog.test\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/field/field.module','field','module','',1,0,7001,0,'a:13:{s:4:\"name\";s:5:\"Field\";s:11:\"description\";s:57:\"Field API to add fields to entities like nodes and users.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:3:{i:0;s:12:\"field.module\";i:1;s:16:\"field.attach.inc\";i:2;s:16:\"tests/field.test\";}s:12:\"dependencies\";a:1:{i:0;s:17:\"field_sql_storage\";}s:8:\"required\";b:1;s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:15:\"theme/field.css\";s:29:\"modules/field/theme/field.css\";}}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/field/modules/field_sql_storage/field_sql_storage.module','field_sql_storage','module','',1,0,7002,0,'a:12:{s:4:\"name\";s:17:\"Field SQL storage\";s:11:\"description\";s:37:\"Stores field data in an SQL database.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:12:\"dependencies\";a:1:{i:0;s:5:\"field\";}s:5:\"files\";a:1:{i:0;s:22:\"field_sql_storage.test\";}s:8:\"required\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/field/modules/list/list.module','list','module','',1,0,7001,0,'a:11:{s:4:\"name\";s:4:\"List\";s:11:\"description\";s:69:\"Defines list field types. Use with Options to create selection lists.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:12:\"dependencies\";a:2:{i:0;s:5:\"field\";i:1;s:7:\"options\";}s:5:\"files\";a:1:{i:0;s:15:\"tests/list.test\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/field/modules/list/tests/list_test.module','list_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:9:\"List test\";s:11:\"description\";s:41:\"Support module for the List module tests.\";s:4:\"core\";s:3:\"7.x\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/field/modules/number/number.module','number','module','',1,0,0,0,'a:11:{s:4:\"name\";s:6:\"Number\";s:11:\"description\";s:28:\"Defines numeric field types.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:12:\"dependencies\";a:1:{i:0;s:5:\"field\";}s:5:\"files\";a:1:{i:0;s:11:\"number.test\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/field/modules/options/options.module','options','module','',1,0,0,0,'a:11:{s:4:\"name\";s:7:\"Options\";s:11:\"description\";s:82:\"Defines selection, check box and radio button widgets for text and numeric fields.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:12:\"dependencies\";a:1:{i:0;s:5:\"field\";}s:5:\"files\";a:1:{i:0;s:12:\"options.test\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/field/modules/text/text.module','text','module','',1,0,7000,0,'a:12:{s:4:\"name\";s:4:\"Text\";s:11:\"description\";s:32:\"Defines simple text field types.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:12:\"dependencies\";a:1:{i:0;s:5:\"field\";}s:5:\"files\";a:1:{i:0;s:9:\"text.test\";}s:8:\"required\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/field/tests/field_test.module','field_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:14:\"Field API Test\";s:11:\"description\";s:39:\"Support module for the Field API tests.\";s:4:\"core\";s:3:\"7.x\";s:7:\"package\";s:7:\"Testing\";s:5:\"files\";a:1:{i:0;s:21:\"field_test.entity.inc\";}s:7:\"version\";s:3:\"7.0\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/field_ui/field_ui.module','field_ui','module','',1,0,0,0,'a:11:{s:4:\"name\";s:8:\"Field UI\";s:11:\"description\";s:33:\"User interface for the Field API.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:12:\"dependencies\";a:1:{i:0;s:5:\"field\";}s:5:\"files\";a:1:{i:0;s:13:\"field_ui.test\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/file/file.module','file','module','',1,0,0,0,'a:11:{s:4:\"name\";s:4:\"File\";s:11:\"description\";s:26:\"Defines a file field type.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:12:\"dependencies\";a:1:{i:0;s:5:\"field\";}s:5:\"files\";a:1:{i:0;s:15:\"tests/file.test\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/file/tests/file_module_test.module','file_module_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:9:\"File test\";s:11:\"description\";s:53:\"Provides hooks for testing File module functionality.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/filter/filter.module','filter','module','',1,0,7010,0,'a:13:{s:4:\"name\";s:6:\"Filter\";s:11:\"description\";s:43:\"Filters content in preparation for display.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:11:\"filter.test\";}s:8:\"required\";b:1;s:9:\"configure\";s:28:\"admin/config/content/formats\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/forum/forum.module','forum','module','',1,0,7001,1,'a:13:{s:4:\"name\";s:5:\"Forum\";s:11:\"description\";s:27:\"Provides discussion forums.\";s:12:\"dependencies\";a:2:{i:0;s:8:\"taxonomy\";i:1;s:7:\"comment\";}s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:10:\"forum.test\";}s:9:\"configure\";s:21:\"admin/structure/forum\";s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:9:\"forum.css\";s:23:\"modules/forum/forum.css\";}}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/help/help.module','help','module','',1,0,0,0,'a:11:{s:4:\"name\";s:4:\"Help\";s:11:\"description\";s:35:\"Manages the display of online help.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:9:\"help.test\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/image/image.module','image','module','',1,0,7000,0,'a:12:{s:4:\"name\";s:5:\"Image\";s:11:\"description\";s:34:\"Provides image manipulation tools.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:12:\"dependencies\";a:1:{i:0;s:4:\"file\";}s:5:\"files\";a:1:{i:0;s:10:\"image.test\";}s:9:\"configure\";s:31:\"admin/config/media/image-styles\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/image/tests/image_module_test.module','image_module_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:10:\"Image test\";s:11:\"description\";s:69:\"Provides hook implementations for testing Image module functionality.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:24:\"image_module_test.module\";}s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/lc_connector/lc_connector.module','lc_connector','module','',1,0,0,0,'a:12:{s:4:\"name\";s:12:\"LC connector\";s:11:\"description\";s:19:\"LC connector module\";s:9:\"configure\";s:26:\"admin/modules/lc_connector\";s:5:\"files\";a:4:{i:0;s:20:\"classes/Abstract.php\";i:1;s:19:\"classes/Handler.php\";i:2;s:17:\"classes/Admin.php\";i:3;s:19:\"classes/Install.php\";}s:7:\"scripts\";a:1:{s:18:\"js/block_manage.js\";s:39:\"modules/lc_connector/js/block_manage.js\";}s:12:\"dependencies\";a:6:{i:0;s:5:\"token\";i:1;s:5:\"print\";i:2;s:4:\"menu\";i:3;s:4:\"user\";i:4;s:5:\"block\";i:5;s:4:\"node\";}s:4:\"core\";s:3:\"7.x\";s:3:\"php\";s:5:\"5.3.0\";s:14:\"lc_dir_default\";s:15:\"../../xlite/src\";s:7:\"package\";s:5:\"Other\";s:7:\"version\";N;s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/locale/locale.module','locale','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:6:\"Locale\";s:11:\"description\";s:119:\"Adds language handling functionality and enables the translation of the user interface to languages other than English.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:11:\"locale.test\";}s:9:\"configure\";s:30:\"admin/config/regional/language\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/locale/tests/locale_test.module','locale_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:11:\"Locale Test\";s:11:\"description\";s:42:\"Support module for the locale layer tests.\";s:4:\"core\";s:3:\"7.x\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/menu/menu.module','menu','module','',1,0,0,0,'a:12:{s:4:\"name\";s:4:\"Menu\";s:11:\"description\";s:60:\"Allows administrators to customize the site navigation menu.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:9:\"menu.test\";}s:9:\"configure\";s:20:\"admin/structure/menu\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/node/node.module','node','module','',1,0,7010,0,'a:14:{s:4:\"name\";s:4:\"Node\";s:11:\"description\";s:66:\"Allows content to be submitted to the site and displayed on pages.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:2:{i:0;s:11:\"node.module\";i:1;s:9:\"node.test\";}s:8:\"required\";b:1;s:9:\"configure\";s:21:\"admin/structure/types\";s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:8:\"node.css\";s:21:\"modules/node/node.css\";}}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/node/tests/node_access_test.module','node_access_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:24:\"Node module access tests\";s:11:\"description\";s:43:\"Support module for node permission testing.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/node/tests/node_test.module','node_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:17:\"Node module tests\";s:11:\"description\";s:40:\"Support module for node related testing.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/node/tests/node_test_exception.module','node_test_exception','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:27:\"Node module exception tests\";s:11:\"description\";s:50:\"Support module for node related exception testing.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/openid/openid.module','openid','module','',0,0,-1,0,'a:11:{s:4:\"name\";s:6:\"OpenID\";s:11:\"description\";s:48:\"Allows users to log into your site using OpenID.\";s:7:\"version\";s:3:\"7.0\";s:7:\"package\";s:4:\"Core\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:11:\"openid.test\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/openid/tests/openid_test.module','openid_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:21:\"OpenID dummy provider\";s:11:\"description\";s:33:\"OpenID provider used for testing.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:12:\"dependencies\";a:1:{i:0;s:6:\"openid\";}s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/overlay/overlay.module','overlay','module','',1,1,0,0,'a:11:{s:4:\"name\";s:7:\"Overlay\";s:11:\"description\";s:59:\"Displays the Drupal administration interface in an overlay.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/path/path.module','path','module','',1,0,0,0,'a:12:{s:4:\"name\";s:4:\"Path\";s:11:\"description\";s:28:\"Allows users to rename URLs.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:9:\"path.test\";}s:9:\"configure\";s:24:\"admin/config/search/path\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/php/php.module','php','module','',1,0,0,0,'a:11:{s:4:\"name\";s:10:\"PHP filter\";s:11:\"description\";s:50:\"Allows embedded PHP code/snippets to be evaluated.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:8:\"php.test\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/poll/poll.module','poll','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:4:\"Poll\";s:11:\"description\";s:95:\"Allows your site to capture votes on different topics in the form of multiple choice questions.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:9:\"poll.test\";}s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:8:\"poll.css\";s:21:\"modules/poll/poll.css\";}}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/print/print.module','print','module','',1,0,7000,0,'a:12:{s:4:\"name\";s:22:\"Printer-friendly pages\";s:11:\"description\";s:73:\"Adds a printer-friendly version link to content and administrative pages.\";s:4:\"core\";s:3:\"7.x\";s:7:\"package\";s:32:\"Printer, e-mail and PDF versions\";s:5:\"files\";a:4:{i:0;s:12:\"print.module\";i:1;s:15:\"print.admin.inc\";i:2;s:15:\"print.pages.inc\";i:3;s:13:\"print.install\";}s:9:\"configure\";s:18:\"admin/config/print\";s:7:\"version\";s:11:\"7.x-1.x-dev\";s:7:\"project\";s:5:\"print\";s:9:\"datestamp\";s:10:\"1286842603\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/print/print_mail/print_mail.module','print_mail','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:14:\"Send by e-mail\";s:11:\"description\";s:54:\"Provides the capability to send the web page by e-mail\";s:4:\"core\";s:3:\"7.x\";s:7:\"package\";s:32:\"Printer, e-mail and PDF versions\";s:12:\"dependencies\";a:1:{i:0;s:5:\"print\";}s:5:\"files\";a:4:{i:0;s:17:\"print_mail.module\";i:1;s:14:\"print_mail.inc\";i:2;s:20:\"print_mail.admin.inc\";i:3;s:18:\"print_mail.install\";}s:9:\"configure\";s:24:\"admin/config/print/email\";s:7:\"version\";s:11:\"7.x-1.x-dev\";s:7:\"project\";s:5:\"print\";s:9:\"datestamp\";s:10:\"1286842603\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/print/print_pdf/print_pdf.module','print_pdf','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:11:\"PDF version\";s:11:\"description\";s:43:\"Adds the capability to export pages as PDF.\";s:4:\"core\";s:3:\"7.x\";s:7:\"package\";s:32:\"Printer, e-mail and PDF versions\";s:12:\"dependencies\";a:1:{i:0;s:5:\"print\";}s:5:\"files\";a:4:{i:0;s:16:\"print_pdf.module\";i:1;s:19:\"print_pdf.admin.inc\";i:2;s:19:\"print_pdf.pages.inc\";i:3;s:17:\"print_pdf.install\";}s:9:\"configure\";s:22:\"admin/config/print/pdf\";s:7:\"version\";s:11:\"7.x-1.x-dev\";s:7:\"project\";s:5:\"print\";s:9:\"datestamp\";s:10:\"1286842603\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/profile/profile.module','profile','module','',0,0,-1,0,'a:13:{s:4:\"name\";s:7:\"Profile\";s:11:\"description\";s:36:\"Supports configurable user profiles.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:12:\"profile.test\";}s:9:\"configure\";s:27:\"admin/config/people/profile\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/rdf/rdf.module','rdf','module','',1,0,0,0,'a:11:{s:4:\"name\";s:3:\"RDF\";s:11:\"description\";s:148:\"Enriches your content with metadata to let other applications (e.g. search engines, aggregators) better understand its relationships and attributes.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:8:\"rdf.test\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/rdf/tests/rdf_test.module','rdf_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:16:\"RDF module tests\";s:11:\"description\";s:38:\"Support module for RDF module testing.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/search/search.module','search','module','',1,0,7000,0,'a:13:{s:4:\"name\";s:6:\"Search\";s:11:\"description\";s:36:\"Enables site-wide keyword searching.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:2:{i:0;s:19:\"search.extender.inc\";i:1;s:11:\"search.test\";}s:9:\"configure\";s:28:\"admin/config/search/settings\";s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:10:\"search.css\";s:25:\"modules/search/search.css\";}}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/search/tests/search_embedded_form.module','search_embedded_form','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:20:\"Search embedded form\";s:11:\"description\";s:59:\"Support module for search module testing of embedded forms.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/search/tests/search_extra_type.module','search_extra_type','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:16:\"Test search type\";s:11:\"description\";s:41:\"Support module for search module testing.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/shortcut/shortcut.module','shortcut','module','',0,0,0,0,'a:12:{s:4:\"name\";s:8:\"Shortcut\";s:11:\"description\";s:60:\"Allows users to manage customizable lists of shortcut links.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:13:\"shortcut.test\";}s:9:\"configure\";s:36:\"admin/config/user-interface/shortcut\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/simpletest.module','simpletest','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:7:\"Testing\";s:11:\"description\";s:53:\"Provides a framework for unit and functional testing.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:37:{i:0;s:15:\"simpletest.test\";i:1;s:24:\"drupal_web_test_case.php\";i:2;s:18:\"tests/actions.test\";i:3;s:15:\"tests/ajax.test\";i:4;s:16:\"tests/batch.test\";i:5;s:20:\"tests/bootstrap.test\";i:6;s:16:\"tests/cache.test\";i:7;s:17:\"tests/common.test\";i:8;s:24:\"tests/database_test.test\";i:9;s:32:\"tests/entity_crud_hook_test.test\";i:10;s:23:\"tests/entity_query.test\";i:11;s:16:\"tests/error.test\";i:12;s:15:\"tests/file.test\";i:13;s:23:\"tests/filetransfer.test\";i:14;s:15:\"tests/form.test\";i:15;s:16:\"tests/graph.test\";i:16;s:16:\"tests/image.test\";i:17;s:15:\"tests/lock.test\";i:18;s:15:\"tests/mail.test\";i:19;s:15:\"tests/menu.test\";i:20;s:17:\"tests/module.test\";i:21;s:19:\"tests/password.test\";i:22;s:15:\"tests/path.test\";i:23;s:19:\"tests/registry.test\";i:24;s:17:\"tests/schema.test\";i:25;s:18:\"tests/session.test\";i:26;s:16:\"tests/theme.test\";i:27;s:18:\"tests/unicode.test\";i:28;s:17:\"tests/update.test\";i:29;s:17:\"tests/xmlrpc.test\";i:30;s:26:\"tests/upgrade/upgrade.test\";i:31;s:34:\"tests/upgrade/upgrade.comment.test\";i:32;s:33:\"tests/upgrade/upgrade.filter.test\";i:33;s:31:\"tests/upgrade/upgrade.node.test\";i:34;s:35:\"tests/upgrade/upgrade.taxonomy.test\";i:35;s:33:\"tests/upgrade/upgrade.upload.test\";i:36;s:33:\"tests/upgrade/upgrade.locale.test\";}s:9:\"configure\";s:41:\"admin/config/development/testing/settings\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/actions_loop_test.module','actions_loop_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:17:\"Actions loop test\";s:11:\"description\";s:39:\"Support module for action loop testing.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/ajax_forms_test.module','ajax_forms_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:26:\"AJAX form test mock module\";s:11:\"description\";s:25:\"Test for AJAX form calls.\";s:4:\"core\";s:3:\"7.x\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/ajax_test.module','ajax_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:9:\"AJAX Test\";s:11:\"description\";s:40:\"Support module for AJAX framework tests.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/batch_test.module','batch_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:14:\"Batch API test\";s:11:\"description\";s:35:\"Support module for Batch API tests.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/common_test.module','common_test','module','',0,0,-1,0,'a:13:{s:4:\"name\";s:11:\"Common Test\";s:11:\"description\";s:32:\"Support module for Common tests.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:2:{s:3:\"all\";a:1:{s:15:\"common_test.css\";s:40:\"modules/simpletest/tests/common_test.css\";}s:5:\"print\";a:1:{s:21:\"common_test.print.css\";s:46:\"modules/simpletest/tests/common_test.print.css\";}}s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/database_test.module','database_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:13:\"Database Test\";s:11:\"description\";s:40:\"Support module for Database layer tests.\";s:4:\"core\";s:3:\"7.x\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/drupal_system_listing_compatible_test/drupal_system_listing_compatible_test.module','drupal_system_listing_compatible_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:37:\"Drupal system listing compatible test\";s:11:\"description\";s:62:\"Support module for testing the drupal_system_listing function.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/drupal_system_listing_incompatible_test/drupal_system_listing_incompatible_test.module','drupal_system_listing_incompatible_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:39:\"Drupal system listing incompatible test\";s:11:\"description\";s:62:\"Support module for testing the drupal_system_listing function.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/entity_cache_test.module','entity_cache_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:17:\"Entity cache test\";s:11:\"description\";s:40:\"Support module for testing entity cache.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:12:\"dependencies\";a:1:{i:0;s:28:\"entity_cache_test_dependency\";}s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/entity_cache_test_dependency.module','entity_cache_test_dependency','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:28:\"Entity cache test dependency\";s:11:\"description\";s:51:\"Support dependency module for testing entity cache.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/entity_crud_hook_test.module','entity_crud_hook_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:22:\"Entity CRUD Hooks Test\";s:11:\"description\";s:35:\"Support module for CRUD hook tests.\";s:4:\"core\";s:3:\"7.x\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/error_test.module','error_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:10:\"Error test\";s:11:\"description\";s:47:\"Support module for error and exception testing.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/file_test.module','file_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:9:\"File test\";s:11:\"description\";s:39:\"Support module for file handling tests.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:16:\"file_test.module\";}s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/filter_test.module','filter_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:18:\"Filter test module\";s:11:\"description\";s:33:\"Tests filter hooks and functions.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/form_test.module','form_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:12:\"FormAPI Test\";s:11:\"description\";s:34:\"Support module for Form API tests.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/image_test.module','image_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:10:\"Image test\";s:11:\"description\";s:39:\"Support module for image toolkit tests.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/menu_test.module','menu_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:15:\"Hook menu tests\";s:11:\"description\";s:37:\"Support module for menu hook testing.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/module_test.module','module_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:11:\"Module test\";s:11:\"description\";s:41:\"Support module for module system testing.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/requirements1_test.module','requirements1_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:19:\"Requirements 1 Test\";s:11:\"description\";s:80:\"Tests that a module is not installed when it fails hook_requirements(\'install\').\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/requirements2_test.module','requirements2_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:19:\"Requirements 2 Test\";s:11:\"description\";s:98:\"Tests that a module is not installed when the one it depends on fails hook_requirements(\'install).\";s:12:\"dependencies\";a:2:{i:0;s:18:\"requirements1_test\";i:1;s:7:\"comment\";}s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/session_test.module','session_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:12:\"Session test\";s:11:\"description\";s:40:\"Support module for session data testing.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/system_dependencies_test.module','system_dependencies_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:22:\"System dependency test\";s:11:\"description\";s:47:\"Support module for testing system dependencies.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:12:\"dependencies\";a:1:{i:0;s:19:\"_missing_dependency\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/system_test.module','system_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:11:\"System test\";s:11:\"description\";s:34:\"Support module for system testing.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:18:\"system_test.module\";}s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/taxonomy_test.module','taxonomy_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:20:\"Taxonomy test module\";s:11:\"description\";s:45:\"\"Tests functions and hooks not used in core\".\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:12:\"dependencies\";a:1:{i:0;s:8:\"taxonomy\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/theme_test.module','theme_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:10:\"Theme test\";s:11:\"description\";s:40:\"Support module for theme system testing.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/update_test_1.module','update_test_1','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:11:\"Update test\";s:11:\"description\";s:34:\"Support module for update testing.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/update_test_2.module','update_test_2','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:11:\"Update test\";s:11:\"description\";s:34:\"Support module for update testing.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/update_test_3.module','update_test_3','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:11:\"Update test\";s:11:\"description\";s:34:\"Support module for update testing.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/url_alter_test.module','url_alter_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:15:\"Url_alter tests\";s:11:\"description\";s:45:\"A support modules for url_alter hook testing.\";s:4:\"core\";s:3:\"7.x\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/simpletest/tests/xmlrpc_test.module','xmlrpc_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:12:\"XML-RPC Test\";s:11:\"description\";s:75:\"Support module for XML-RPC tests according to the validator1 specification.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/statistics/statistics.module','statistics','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:10:\"Statistics\";s:11:\"description\";s:37:\"Logs access statistics for your site.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:15:\"statistics.test\";}s:9:\"configure\";s:30:\"admin/config/system/statistics\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/syslog/syslog.module','syslog','module','',0,0,-1,0,'a:11:{s:4:\"name\";s:6:\"Syslog\";s:11:\"description\";s:41:\"Logs and records system events to syslog.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:11:\"syslog.test\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/system/system.module','system','module','',1,0,7069,0,'a:13:{s:4:\"name\";s:6:\"System\";s:11:\"description\";s:54:\"Handles general site configuration for administrators.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:6:{i:0;s:19:\"system.archiver.inc\";i:1;s:15:\"system.mail.inc\";i:2;s:16:\"system.queue.inc\";i:3;s:14:\"system.tar.inc\";i:4;s:18:\"system.updater.inc\";i:5;s:11:\"system.test\";}s:8:\"required\";b:1;s:9:\"configure\";s:19:\"admin/config/system\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/taxonomy/taxonomy.module','taxonomy','module','',1,0,7010,0,'a:12:{s:4:\"name\";s:8:\"Taxonomy\";s:11:\"description\";s:38:\"Enables the categorization of content.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:12:\"dependencies\";a:1:{i:0;s:7:\"options\";}s:5:\"files\";a:2:{i:0;s:15:\"taxonomy.module\";i:1;s:13:\"taxonomy.test\";}s:9:\"configure\";s:24:\"admin/structure/taxonomy\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/token/tests/token_test.module','token_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:10:\"Token Test\";s:11:\"description\";s:39:\"Testing module for token functionality.\";s:7:\"package\";s:7:\"Testing\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:17:\"token_test.module\";}s:6:\"hidden\";b:1;s:7:\"version\";s:11:\"7.x-1.x-dev\";s:7:\"project\";s:5:\"token\";s:9:\"datestamp\";s:10:\"1290385323\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/token/token.module','token','module','',1,0,7001,0,'a:11:{s:4:\"name\";s:5:\"Token\";s:11:\"description\";s:73:\"Provides a user interface for the Token API and some missing core tokens.\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:5:{i:0;s:12:\"token.module\";i:1;s:13:\"token.install\";i:2;s:16:\"token.tokens.inc\";i:3;s:15:\"token.pages.inc\";i:4;s:10:\"token.test\";}s:7:\"version\";s:11:\"7.x-1.x-dev\";s:7:\"project\";s:5:\"token\";s:9:\"datestamp\";s:10:\"1290385323\";s:12:\"dependencies\";a:0:{}s:7:\"package\";s:5:\"Other\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/toolbar/toolbar.module','toolbar','module','',1,0,0,0,'a:11:{s:4:\"name\";s:7:\"Toolbar\";s:11:\"description\";s:99:\"Provides a toolbar that shows the top-level administration menu items and links from other modules.\";s:4:\"core\";s:3:\"7.x\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/tracker/tracker.module','tracker','module','',0,0,-1,0,'a:11:{s:4:\"name\";s:7:\"Tracker\";s:11:\"description\";s:45:\"Enables tracking of recent content for users.\";s:12:\"dependencies\";a:1:{i:0;s:7:\"comment\";}s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:12:\"tracker.test\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/translation/tests/translation_test.module','translation_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:24:\"Content Translation Test\";s:11:\"description\";s:49:\"Support module for the content translation tests.\";s:4:\"core\";s:3:\"7.x\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/translation/translation.module','translation','module','',0,0,-1,0,'a:11:{s:4:\"name\";s:19:\"Content translation\";s:11:\"description\";s:57:\"Allows content to be translated into different languages.\";s:12:\"dependencies\";a:1:{i:0;s:6:\"locale\";}s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:16:\"translation.test\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/trigger/tests/trigger_test.module','trigger_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:12:\"Trigger Test\";s:11:\"description\";s:33:\"Support module for Trigger tests.\";s:7:\"package\";s:7:\"Testing\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"version\";s:3:\"7.0\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/trigger/trigger.module','trigger','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:7:\"Trigger\";s:11:\"description\";s:90:\"Enables actions to be fired on certain system events, such as when new content is created.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:12:\"trigger.test\";}s:9:\"configure\";s:23:\"admin/structure/trigger\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/update/tests/aaa_update_test.module','aaa_update_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:15:\"AAA Update test\";s:11:\"description\";s:41:\"Support module for update module testing.\";s:7:\"package\";s:7:\"Testing\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"version\";s:3:\"7.0\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/update/tests/bbb_update_test.module','bbb_update_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:15:\"BBB Update test\";s:11:\"description\";s:41:\"Support module for update module testing.\";s:7:\"package\";s:7:\"Testing\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"version\";s:3:\"7.0\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/update/tests/ccc_update_test.module','ccc_update_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:15:\"CCC Update test\";s:11:\"description\";s:41:\"Support module for update module testing.\";s:7:\"package\";s:7:\"Testing\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"version\";s:3:\"7.0\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/update/tests/update_test.module','update_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:11:\"Update test\";s:11:\"description\";s:41:\"Support module for update module testing.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/update/update.module','update','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:14:\"Update manager\";s:11:\"description\";s:104:\"Checks for available updates, and can securely install or update modules and themes via a web interface.\";s:7:\"version\";s:3:\"7.0\";s:7:\"package\";s:4:\"Core\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:11:\"update.test\";}s:9:\"configure\";s:30:\"admin/reports/updates/settings\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/user/tests/user_form_test.module','user_form_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:22:\"User module form tests\";s:11:\"description\";s:37:\"Support module for user form testing.\";s:7:\"package\";s:7:\"Testing\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('modules/user/user.module','user','module','',1,0,7015,0,'a:14:{s:4:\"name\";s:4:\"User\";s:11:\"description\";s:47:\"Manages the user registration and login system.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:2:{i:0;s:11:\"user.module\";i:1;s:9:\"user.test\";}s:8:\"required\";b:1;s:9:\"configure\";s:19:\"admin/config/people\";s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:8:\"user.css\";s:21:\"modules/user/user.css\";}}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('profiles/standard/standard.profile','standard','module','',1,0,0,1000,'a:13:{s:4:\"name\";s:8:\"Standard\";s:11:\"description\";s:51:\"Install with commonly used features pre-configured.\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:12:\"dependencies\";a:21:{i:0;s:5:\"block\";i:1;s:5:\"color\";i:2;s:7:\"comment\";i:3;s:10:\"contextual\";i:4;s:9:\"dashboard\";i:5;s:4:\"help\";i:6;s:5:\"image\";i:7;s:4:\"list\";i:8;s:4:\"menu\";i:9;s:6:\"number\";i:10;s:7:\"options\";i:11;s:4:\"path\";i:12;s:8:\"taxonomy\";i:13;s:5:\"dblog\";i:14;s:6:\"search\";i:15;s:8:\"shortcut\";i:16;s:7:\"toolbar\";i:17;s:7:\"overlay\";i:18;s:8:\"field_ui\";i:19;s:4:\"file\";i:20;s:3:\"rdf\";}s:5:\"files\";a:1:{i:0;s:16:\"standard.profile\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:7:\"package\";s:5:\"Other\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;s:6:\"hidden\";b:1;s:8:\"required\";b:1;}');
INSERT INTO `drupal_system` VALUES ('sites/all/modules/captcha/captcha.module','captcha','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:7:\"CAPTCHA\";s:11:\"description\";s:61:\"Base CAPTCHA module for adding challenges to arbitrary forms.\";s:7:\"package\";s:12:\"Spam control\";s:4:\"core\";s:3:\"7.x\";s:9:\"configure\";s:27:\"admin/config/people/captcha\";s:5:\"files\";a:5:{i:0;s:14:\"captcha.module\";i:1;s:11:\"captcha.inc\";i:2;s:17:\"captcha.admin.inc\";i:3;s:15:\"captcha.install\";i:4;s:12:\"captcha.test\";}s:7:\"version\";s:14:\"7.x-1.0-alpha2\";s:7:\"project\";s:7:\"captcha\";s:9:\"datestamp\";s:10:\"1293755549\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('sites/all/modules/captcha/image_captcha/image_captcha.module','image_captcha','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:13:\"Image CAPTCHA\";s:11:\"description\";s:32:\"Provides an image based CAPTCHA.\";s:7:\"package\";s:12:\"Spam control\";s:12:\"dependencies\";a:1:{i:0;s:7:\"captcha\";}s:4:\"core\";s:3:\"7.x\";s:9:\"configure\";s:41:\"admin/config/people/captcha/image_captcha\";s:5:\"files\";a:4:{i:0;s:21:\"image_captcha.install\";i:1;s:20:\"image_captcha.module\";i:2;s:23:\"image_captcha.admin.inc\";i:3;s:22:\"image_captcha.user.inc\";}s:7:\"version\";s:14:\"7.x-1.0-alpha2\";s:7:\"project\";s:7:\"captcha\";s:9:\"datestamp\";s:10:\"1293755549\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('sites/all/modules/google_analytics/googleanalytics.module','googleanalytics','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:16:\"Google Analytics\";s:11:\"description\";s:102:\"Allows your site to be tracked by Google Analytics by adding a Javascript tracking code to every page.\";s:4:\"core\";s:3:\"7.x\";s:7:\"package\";s:10:\"Statistics\";s:9:\"configure\";s:35:\"admin/config/system/googleanalytics\";s:5:\"files\";a:3:{i:0;s:22:\"googleanalytics.module\";i:1;s:25:\"googleanalytics.admin.inc\";i:2;s:23:\"googleanalytics.install\";}s:7:\"version\";s:11:\"7.x-1.x-dev\";s:7:\"project\";s:16:\"google_analytics\";s:9:\"datestamp\";s:10:\"1293322471\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('sites/all/modules/simplenews/simplenews.module','simplenews','module','',0,0,-1,0,'a:11:{s:4:\"name\";s:10:\"Simplenews\";s:11:\"description\";s:47:\"Send newsletters to subscribed email addresses.\";s:7:\"package\";s:4:\"Mail\";s:4:\"core\";s:3:\"7.x\";s:12:\"dependencies\";a:1:{i:0;s:8:\"taxonomy\";}s:5:\"files\";a:20:{i:0;s:18:\"simplenews.install\";i:1;s:17:\"simplenews.module\";i:2;s:36:\"includes/simplenews.subscription.inc\";i:3;s:29:\"includes/simplenews.admin.inc\";i:4;s:28:\"includes/simplenews.mail.inc\";i:5;s:35:\"includes/views/simplenews.views.inc\";i:6;s:70:\"includes/views/handlers/simplenews_handler_field_newsletter_status.inc\";i:7;s:72:\"includes/views/handlers/simplenews_handler_field_newsletter_priority.inc\";i:8;s:72:\"includes/views/handlers/simplenews_handler_field_category_hyperlinks.inc\";i:9;s:73:\"includes/views/handlers/simplenews_handler_field_category_new_account.inc\";i:10;s:71:\"includes/views/handlers/simplenews_handler_field_category_opt_inout.inc\";i:11;s:71:\"includes/views/handlers/simplenews_handler_filter_newsletter_status.inc\";i:12;s:73:\"includes/views/handlers/simplenews_handler_filter_newsletter_priority.inc\";i:13;s:73:\"includes/views/handlers/simplenews_handler_filter_category_hyperlinks.inc\";i:14;s:74:\"includes/views/handlers/simplenews_handler_filter_category_new_account.inc\";i:15;s:72:\"includes/views/handlers/simplenews_handler_filter_category_opt_inout.inc\";i:16;s:30:\"theme/simplenews-block.tpl.php\";i:17;s:40:\"theme/simplenews-newsletter-body.tpl.php\";i:18;s:42:\"theme/simplenews-newsletter-footer.tpl.php\";i:19;s:21:\"tests/simplenews.test\";}s:7:\"version\";s:11:\"7.x-1.x-dev\";s:7:\"project\";s:10:\"simplenews\";s:9:\"datestamp\";s:10:\"1294446581\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('sites/all/modules/simplenews/simplenews_action/simplenews_action.module','simplenews_action','module','',0,0,-1,0,'a:11:{s:4:\"name\";s:17:\"Simplenews action\";s:11:\"description\";s:31:\"Provide actions for Simplenews.\";s:12:\"dependencies\";a:2:{i:0;s:10:\"simplenews\";i:1;s:7:\"trigger\";}s:7:\"package\";s:4:\"Mail\";s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:1:{i:0;s:24:\"simplenews_action.module\";}s:7:\"version\";s:11:\"7.x-1.x-dev\";s:7:\"project\";s:10:\"simplenews\";s:9:\"datestamp\";s:10:\"1294446581\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('sites/all/modules/simplenews/simplenews_test/simplenews_test.module','simplenews_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:15:\"Simplenews test\";s:11:\"description\";s:56:\"Simplenews helper module for automated simplenews tests.\";s:12:\"dependencies\";a:1:{i:0;s:10:\"simplenews\";}s:7:\"package\";s:7:\"Testing\";s:6:\"hidden\";b:1;s:4:\"core\";s:3:\"7.x\";s:5:\"files\";a:2:{i:0;s:23:\"simplenews_test.install\";i:1;s:22:\"simplenews_test.module\";}s:7:\"version\";s:11:\"7.x-1.x-dev\";s:7:\"project\";s:10:\"simplenews\";s:9:\"datestamp\";s:10:\"1294446581\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('sites/all/modules/views/tests/views_test.module','views_test','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:10:\"Views Test\";s:11:\"description\";s:22:\"Test module for Views.\";s:7:\"package\";s:5:\"Views\";s:4:\"core\";s:3:\"7.x\";s:12:\"dependencies\";a:1:{i:0;s:5:\"views\";}s:6:\"hidden\";b:1;s:7:\"version\";s:14:\"7.x-3.0-alpha1\";s:7:\"project\";s:5:\"views\";s:9:\"datestamp\";s:10:\"1294276880\";s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('sites/all/modules/views/views.module','views','module','',0,0,-1,0,'a:11:{s:4:\"name\";s:5:\"Views\";s:11:\"description\";s:55:\"Create customized lists and queries from your database.\";s:7:\"package\";s:5:\"Views\";s:4:\"core\";s:3:\"7.x\";s:3:\"php\";s:3:\"5.2\";s:5:\"files\";a:251:{i:0;s:12:\"views.module\";i:1;s:31:\"handlers/views_handler_area.inc\";i:2;s:36:\"handlers/views_handler_area_text.inc\";i:3;s:35:\"handlers/views_handler_argument.inc\";i:4;s:40:\"handlers/views_handler_argument_date.inc\";i:5;s:43:\"handlers/views_handler_argument_formula.inc\";i:6;s:47:\"handlers/views_handler_argument_many_to_one.inc\";i:7;s:40:\"handlers/views_handler_argument_null.inc\";i:8;s:43:\"handlers/views_handler_argument_numeric.inc\";i:9;s:42:\"handlers/views_handler_argument_string.inc\";i:10;s:52:\"handlers/views_handler_argument_group_by_numeric.inc\";i:11;s:32:\"handlers/views_handler_field.inc\";i:12;s:40:\"handlers/views_handler_field_counter.inc\";i:13;s:49:\"handlers/views_handler_field_group_by_numeric.inc\";i:14;s:40:\"handlers/views_handler_field_boolean.inc\";i:15;s:39:\"handlers/views_handler_field_custom.inc\";i:16;s:37:\"handlers/views_handler_field_date.inc\";i:17;s:39:\"handlers/views_handler_field_markup.inc\";i:18;s:37:\"handlers/views_handler_field_math.inc\";i:19;s:40:\"handlers/views_handler_field_numeric.inc\";i:20;s:47:\"handlers/views_handler_field_prerender_list.inc\";i:21;s:36:\"handlers/views_handler_field_url.inc\";i:22;s:33:\"handlers/views_handler_filter.inc\";i:23;s:50:\"handlers/views_handler_filter_group_by_numeric.inc\";i:24;s:50:\"handlers/views_handler_filter_boolean_operator.inc\";i:25;s:57:\"handlers/views_handler_filter_boolean_operator_string.inc\";i:26;s:38:\"handlers/views_handler_filter_date.inc\";i:27;s:42:\"handlers/views_handler_filter_equality.inc\";i:28;s:45:\"handlers/views_handler_filter_in_operator.inc\";i:29;s:45:\"handlers/views_handler_filter_many_to_one.inc\";i:30;s:41:\"handlers/views_handler_filter_numeric.inc\";i:31;s:40:\"handlers/views_handler_filter_string.inc\";i:32;s:39:\"handlers/views_handler_relationship.inc\";i:33;s:31:\"handlers/views_handler_sort.inc\";i:34;s:48:\"handlers/views_handler_sort_group_by_numeric.inc\";i:35;s:36:\"handlers/views_handler_sort_date.inc\";i:36;s:39:\"handlers/views_handler_sort_formula.inc\";i:37;s:46:\"handlers/views_handler_sort_menu_hierarchy.inc\";i:38;s:38:\"handlers/views_handler_sort_random.inc\";i:39;s:17:\"includes/base.inc\";i:40;s:21:\"includes/handlers.inc\";i:41;s:20:\"includes/plugins.inc\";i:42;s:17:\"includes/tabs.inc\";i:43;s:17:\"includes/view.inc\";i:44;s:60:\"modules/aggregator/views_handler_argument_aggregator_fid.inc\";i:45;s:60:\"modules/aggregator/views_handler_argument_aggregator_iid.inc\";i:46;s:69:\"modules/aggregator/views_handler_argument_aggregator_category_cid.inc\";i:47;s:64:\"modules/aggregator/views_handler_field_aggregator_title_link.inc\";i:48;s:62:\"modules/aggregator/views_handler_field_aggregator_category.inc\";i:49;s:70:\"modules/aggregator/views_handler_field_aggregator_item_description.inc\";i:50;s:57:\"modules/aggregator/views_handler_field_aggregator_xss.inc\";i:51;s:67:\"modules/aggregator/views_handler_filter_aggregator_category_cid.inc\";i:52;s:54:\"modules/aggregator/views_plugin_row_aggregator_rss.inc\";i:53;s:59:\"modules/comment/views_handler_argument_comment_user_uid.inc\";i:54;s:47:\"modules/comment/views_handler_field_comment.inc\";i:55;s:53:\"modules/comment/views_handler_field_comment_depth.inc\";i:56;s:52:\"modules/comment/views_handler_field_comment_link.inc\";i:57;s:59:\"modules/comment/views_handler_field_comment_link_delete.inc\";i:58;s:57:\"modules/comment/views_handler_field_comment_link_edit.inc\";i:59;s:58:\"modules/comment/views_handler_field_comment_link_reply.inc\";i:60;s:57:\"modules/comment/views_handler_field_comment_node_link.inc\";i:61;s:56:\"modules/comment/views_handler_field_comment_username.inc\";i:62;s:61:\"modules/comment/views_handler_field_ncs_last_comment_name.inc\";i:63;s:56:\"modules/comment/views_handler_field_ncs_last_updated.inc\";i:64;s:52:\"modules/comment/views_handler_field_node_comment.inc\";i:65;s:57:\"modules/comment/views_handler_field_node_new_comments.inc\";i:66;s:62:\"modules/comment/views_handler_field_last_comment_timestamp.inc\";i:67;s:57:\"modules/comment/views_handler_filter_comment_user_uid.inc\";i:68;s:57:\"modules/comment/views_handler_filter_ncs_last_updated.inc\";i:69;s:53:\"modules/comment/views_handler_filter_node_comment.inc\";i:70;s:53:\"modules/comment/views_handler_sort_comment_thread.inc\";i:71;s:60:\"modules/comment/views_handler_sort_ncs_last_comment_name.inc\";i:72;s:55:\"modules/comment/views_handler_sort_ncs_last_updated.inc\";i:73;s:48:\"modules/comment/views_plugin_row_comment_rss.inc\";i:74;s:49:\"modules/comment/views_plugin_row_comment_view.inc\";i:75;s:52:\"modules/contact/views_handler_field_contact_link.inc\";i:76;s:43:\"modules/field/views_handler_field_field.inc\";i:77;s:49:\"modules/field/views_handler_filter_field_list.inc\";i:78;s:57:\"modules/filter/views_handler_field_filter_format_name.inc\";i:79;s:54:\"modules/locale/views_handler_argument_locale_group.inc\";i:80;s:57:\"modules/locale/views_handler_argument_locale_language.inc\";i:81;s:51:\"modules/locale/views_handler_field_locale_group.inc\";i:82;s:54:\"modules/locale/views_handler_field_locale_language.inc\";i:83;s:55:\"modules/locale/views_handler_field_locale_link_edit.inc\";i:84;s:52:\"modules/locale/views_handler_filter_locale_group.inc\";i:85;s:55:\"modules/locale/views_handler_filter_locale_language.inc\";i:86;s:54:\"modules/locale/views_handler_filter_locale_version.inc\";i:87;s:53:\"modules/node/views_handler_argument_dates_various.inc\";i:88;s:53:\"modules/node/views_handler_argument_node_language.inc\";i:89;s:48:\"modules/node/views_handler_argument_node_nid.inc\";i:90;s:49:\"modules/node/views_handler_argument_node_type.inc\";i:91;s:48:\"modules/node/views_handler_argument_node_vid.inc\";i:92;s:59:\"modules/node/views_handler_field_history_user_timestamp.inc\";i:93;s:41:\"modules/node/views_handler_field_node.inc\";i:94;s:46:\"modules/node/views_handler_field_node_link.inc\";i:95;s:53:\"modules/node/views_handler_field_node_link_delete.inc\";i:96;s:51:\"modules/node/views_handler_field_node_link_edit.inc\";i:97;s:50:\"modules/node/views_handler_field_node_revision.inc\";i:98;s:62:\"modules/node/views_handler_field_node_revision_link_delete.inc\";i:99;s:62:\"modules/node/views_handler_field_node_revision_link_revert.inc\";i:100;s:46:\"modules/node/views_handler_field_node_path.inc\";i:101;s:46:\"modules/node/views_handler_field_node_type.inc\";i:102;s:60:\"modules/node/views_handler_filter_history_user_timestamp.inc\";i:103;s:49:\"modules/node/views_handler_filter_node_access.inc\";i:104;s:49:\"modules/node/views_handler_filter_node_status.inc\";i:105;s:47:\"modules/node/views_handler_filter_node_type.inc\";i:106;s:51:\"modules/node/views_plugin_argument_default_node.inc\";i:107;s:52:\"modules/node/views_plugin_argument_validate_node.inc\";i:108;s:42:\"modules/node/views_plugin_row_node_rss.inc\";i:109;s:43:\"modules/node/views_plugin_row_node_view.inc\";i:110;s:52:\"modules/profile/views_handler_field_profile_date.inc\";i:111;s:52:\"modules/profile/views_handler_field_profile_list.inc\";i:112;s:58:\"modules/profile/views_handler_filter_profile_selection.inc\";i:113;s:48:\"modules/search/views_handler_argument_search.inc\";i:114;s:51:\"modules/search/views_handler_field_search_score.inc\";i:115;s:46:\"modules/search/views_handler_filter_search.inc\";i:116;s:50:\"modules/search/views_handler_sort_search_score.inc\";i:117;s:47:\"modules/search/views_plugin_row_search_view.inc\";i:118;s:57:\"modules/statistics/views_handler_field_accesslog_path.inc\";i:119;s:50:\"modules/system/views_handler_argument_file_fid.inc\";i:120;s:43:\"modules/system/views_handler_field_file.inc\";i:121;s:53:\"modules/system/views_handler_field_file_extension.inc\";i:122;s:52:\"modules/system/views_handler_field_file_filemime.inc\";i:123;s:47:\"modules/system/views_handler_field_file_uri.inc\";i:124;s:50:\"modules/system/views_handler_field_file_status.inc\";i:125;s:51:\"modules/system/views_handler_filter_file_status.inc\";i:126;s:52:\"modules/taxonomy/views_handler_argument_taxonomy.inc\";i:127;s:57:\"modules/taxonomy/views_handler_argument_term_node_tid.inc\";i:128;s:63:\"modules/taxonomy/views_handler_argument_term_node_tid_depth.inc\";i:129;s:72:\"modules/taxonomy/views_handler_argument_term_node_tid_depth_modifier.inc\";i:130;s:58:\"modules/taxonomy/views_handler_argument_vocabulary_vid.inc\";i:131;s:49:\"modules/taxonomy/views_handler_field_taxonomy.inc\";i:132;s:54:\"modules/taxonomy/views_handler_field_term_node_tid.inc\";i:133;s:55:\"modules/taxonomy/views_handler_field_term_link_edit.inc\";i:134;s:55:\"modules/taxonomy/views_handler_filter_term_node_tid.inc\";i:135;s:61:\"modules/taxonomy/views_handler_filter_term_node_tid_depth.inc\";i:136;s:56:\"modules/taxonomy/views_handler_filter_vocabulary_vid.inc\";i:137;s:65:\"modules/taxonomy/views_handler_filter_vocabulary_machine_name.inc\";i:138;s:62:\"modules/taxonomy/views_handler_relationship_node_term_data.inc\";i:139;s:65:\"modules/taxonomy/views_plugin_argument_validate_taxonomy_term.inc\";i:140;s:63:\"modules/taxonomy/views_plugin_argument_default_taxonomy_tid.inc\";i:141;s:56:\"modules/translation/views_handler_argument_node_tnid.inc\";i:142;s:57:\"modules/translation/views_handler_field_node_language.inc\";i:143;s:63:\"modules/translation/views_handler_field_node_link_translate.inc\";i:144;s:65:\"modules/translation/views_handler_field_node_translation_link.inc\";i:145;s:58:\"modules/translation/views_handler_filter_node_language.inc\";i:146;s:54:\"modules/translation/views_handler_filter_node_tnid.inc\";i:147;s:60:\"modules/translation/views_handler_filter_node_tnid_child.inc\";i:148;s:62:\"modules/translation/views_handler_relationship_translation.inc\";i:149;s:57:\"modules/upload/views_handler_field_upload_description.inc\";i:150;s:49:\"modules/upload/views_handler_field_upload_fid.inc\";i:151;s:50:\"modules/upload/views_handler_filter_upload_fid.inc\";i:152;s:48:\"modules/user/views_handler_argument_user_uid.inc\";i:153;s:55:\"modules/user/views_handler_argument_users_roles_rid.inc\";i:154;s:41:\"modules/user/views_handler_field_user.inc\";i:155;s:50:\"modules/user/views_handler_field_user_language.inc\";i:156;s:46:\"modules/user/views_handler_field_user_link.inc\";i:157;s:53:\"modules/user/views_handler_field_user_link_delete.inc\";i:158;s:51:\"modules/user/views_handler_field_user_link_edit.inc\";i:159;s:46:\"modules/user/views_handler_field_user_mail.inc\";i:160;s:46:\"modules/user/views_handler_field_user_name.inc\";i:161;s:49:\"modules/user/views_handler_field_user_picture.inc\";i:162;s:47:\"modules/user/views_handler_field_user_roles.inc\";i:163;s:50:\"modules/user/views_handler_filter_user_current.inc\";i:164;s:47:\"modules/user/views_handler_filter_user_name.inc\";i:165;s:48:\"modules/user/views_handler_filter_user_roles.inc\";i:166;s:59:\"modules/user/views_plugin_argument_default_current_user.inc\";i:167;s:51:\"modules/user/views_plugin_argument_default_user.inc\";i:168;s:52:\"modules/user/views_plugin_argument_validate_user.inc\";i:169;s:31:\"plugins/views_plugin_access.inc\";i:170;s:36:\"plugins/views_plugin_access_none.inc\";i:171;s:36:\"plugins/views_plugin_access_perm.inc\";i:172;s:36:\"plugins/views_plugin_access_role.inc\";i:173;s:41:\"plugins/views_plugin_argument_default.inc\";i:174;s:45:\"plugins/views_plugin_argument_default_php.inc\";i:175;s:47:\"plugins/views_plugin_argument_default_fixed.inc\";i:176;s:42:\"plugins/views_plugin_argument_validate.inc\";i:177;s:50:\"plugins/views_plugin_argument_validate_numeric.inc\";i:178;s:46:\"plugins/views_plugin_argument_validate_php.inc\";i:179;s:30:\"plugins/views_plugin_cache.inc\";i:180;s:35:\"plugins/views_plugin_cache_none.inc\";i:181;s:35:\"plugins/views_plugin_cache_time.inc\";i:182;s:32:\"plugins/views_plugin_display.inc\";i:183;s:43:\"plugins/views_plugin_display_attachment.inc\";i:184;s:38:\"plugins/views_plugin_display_block.inc\";i:185;s:40:\"plugins/views_plugin_display_default.inc\";i:186;s:37:\"plugins/views_plugin_display_feed.inc\";i:187;s:43:\"plugins/views_plugin_exposed_form_basic.inc\";i:188;s:37:\"plugins/views_plugin_exposed_form.inc\";i:189;s:52:\"plugins/views_plugin_exposed_form_input_required.inc\";i:190;s:37:\"plugins/views_plugin_display_page.inc\";i:191;s:42:\"plugins/views_plugin_localization_core.inc\";i:192;s:37:\"plugins/views_plugin_localization.inc\";i:193;s:42:\"plugins/views_plugin_localization_none.inc\";i:194;s:30:\"plugins/views_plugin_pager.inc\";i:195;s:35:\"plugins/views_plugin_pager_full.inc\";i:196;s:35:\"plugins/views_plugin_pager_mini.inc\";i:197;s:35:\"plugins/views_plugin_pager_none.inc\";i:198;s:35:\"plugins/views_plugin_pager_some.inc\";i:199;s:30:\"plugins/views_plugin_query.inc\";i:200;s:38:\"plugins/views_plugin_query_default.inc\";i:201;s:28:\"plugins/views_plugin_row.inc\";i:202;s:35:\"plugins/views_plugin_row_fields.inc\";i:203;s:30:\"plugins/views_plugin_style.inc\";i:204;s:38:\"plugins/views_plugin_style_default.inc\";i:205;s:35:\"plugins/views_plugin_style_grid.inc\";i:206;s:35:\"plugins/views_plugin_style_list.inc\";i:207;s:40:\"plugins/views_plugin_style_jump_menu.inc\";i:208;s:34:\"plugins/views_plugin_style_rss.inc\";i:209;s:38:\"plugins/views_plugin_style_summary.inc\";i:210;s:48:\"plugins/views_plugin_style_summary_jump_menu.inc\";i:211;s:50:\"plugins/views_plugin_style_summary_unformatted.inc\";i:212;s:36:\"plugins/views_plugin_style_table.inc\";i:213;s:43:\"tests/handlers/views_handler_area_text.test\";i:214;s:47:\"tests/handlers/views_handler_argument_null.test\";i:215;s:47:\"tests/handlers/views_handler_field_boolean.test\";i:216;s:46:\"tests/handlers/views_handler_field_custom.test\";i:217;s:47:\"tests/handlers/views_handler_field_counter.test\";i:218;s:44:\"tests/handlers/views_handler_field_date.test\";i:219;s:49:\"tests/handlers/views_handler_field_file_size.test\";i:220;s:44:\"tests/handlers/views_handler_field_math.test\";i:221;s:43:\"tests/handlers/views_handler_field_url.test\";i:222;s:43:\"tests/handlers/views_handler_field_xss.test\";i:223;s:45:\"tests/handlers/views_handler_filter_date.test\";i:224;s:49:\"tests/handlers/views_handler_filter_equality.test\";i:225;s:52:\"tests/handlers/views_handler_filter_in_operator.test\";i:226;s:48:\"tests/handlers/views_handler_filter_numeric.test\";i:227;s:47:\"tests/handlers/views_handler_filter_string.test\";i:228;s:45:\"tests/handlers/views_handler_sort_random.test\";i:229;s:43:\"tests/handlers/views_handler_sort_date.test\";i:230;s:38:\"tests/handlers/views_handler_sort.test\";i:231;s:60:\"tests/test_plugins/views_test_plugin_access_test_dynamic.inc\";i:232;s:59:\"tests/test_plugins/views_test_plugin_access_test_static.inc\";i:233;s:23:\"tests/views_access.test\";i:234;s:24:\"tests/views_analyze.test\";i:235;s:22:\"tests/views_basic.test\";i:236;s:33:\"tests/views_argument_default.test\";i:237;s:35:\"tests/views_argument_validator.test\";i:238;s:29:\"tests/views_exposed_form.test\";i:239;s:25:\"tests/views_glossary.test\";i:240;s:24:\"tests/views_groupby.test\";i:241;s:25:\"tests/views_handlers.test\";i:242;s:23:\"tests/views_module.test\";i:243;s:22:\"tests/views_pager.test\";i:244;s:40:\"tests/views_plugin_localization_test.inc\";i:245;s:29:\"tests/views_translatable.test\";i:246;s:22:\"tests/views_query.test\";i:247;s:34:\"tests/views_test.views_default.inc\";i:248;s:43:\"tests/user/views_user_argument_default.test\";i:249;s:44:\"tests/user/views_user_argument_validate.test\";i:250;s:22:\"tests/views_cache.test\";}s:12:\"dependencies\";a:1:{i:0;s:6:\"ctools\";}s:7:\"version\";s:14:\"7.x-3.0-alpha1\";s:7:\"project\";s:5:\"views\";s:9:\"datestamp\";s:10:\"1294276880\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('sites/all/modules/views/views_export/views_export.module','views_export','module','',0,0,-1,0,'a:11:{s:4:\"name\";s:14:\"Views exporter\";s:11:\"description\";s:40:\"Allows exporting multiple views at once.\";s:7:\"package\";s:5:\"Views\";s:12:\"dependencies\";a:1:{i:0;s:5:\"views\";}s:4:\"core\";s:3:\"7.x\";s:7:\"version\";s:14:\"7.x-3.0-alpha1\";s:7:\"project\";s:5:\"views\";s:9:\"datestamp\";s:10:\"1294276880\";s:3:\"php\";s:5:\"5.2.4\";s:5:\"files\";a:0:{}s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('sites/all/modules/views/views_ui.module','views_ui','module','',0,0,-1,0,'a:12:{s:4:\"name\";s:8:\"Views UI\";s:11:\"description\";s:93:\"Administrative interface to views. Without this module, you cannot create or edit your views.\";s:7:\"package\";s:5:\"Views\";s:4:\"core\";s:3:\"7.x\";s:9:\"configure\";s:21:\"admin/structure/views\";s:12:\"dependencies\";a:1:{i:0;s:5:\"views\";}s:5:\"files\";a:1:{i:0;s:15:\"views_ui.module\";}s:7:\"version\";s:14:\"7.x-3.0-alpha1\";s:7:\"project\";s:5:\"views\";s:9:\"datestamp\";s:10:\"1294276880\";s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('sites/all/modules/wysiwyg/wysiwyg.module','wysiwyg','module','',1,0,7000,0,'a:12:{s:4:\"name\";s:7:\"Wysiwyg\";s:11:\"description\";s:55:\"Allows users to edit contents with client-side editors.\";s:7:\"package\";s:14:\"User interface\";s:4:\"core\";s:3:\"7.x\";s:9:\"configure\";s:28:\"admin/config/content/wysiwyg\";s:5:\"files\";a:4:{i:0;s:14:\"wysiwyg.module\";i:1;s:15:\"wysiwyg.install\";i:2;s:17:\"wysiwyg.admin.inc\";i:3;s:18:\"wysiwyg.dialog.inc\";}s:7:\"version\";s:11:\"7.x-2.x-dev\";s:7:\"project\";s:7:\"wysiwyg\";s:9:\"datestamp\";s:10:\"1292804702\";s:12:\"dependencies\";a:0:{}s:3:\"php\";s:5:\"5.2.4\";s:9:\"bootstrap\";i:0;}');
INSERT INTO `drupal_system` VALUES ('sites/all/themes/lc3_clean/lc3_clean.info','lc3_clean','theme','themes/engines/phptemplate/phptemplate.engine',1,0,-1,0,'a:14:{s:4:\"name\";s:15:\"Clean LC3 theme\";s:11:\"description\";s:36:\"A clean theme for LiteCommerce shops\";s:10:\"screenshot\";s:41:\"sites/all/themes/lc3_clean/screenshot.png\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:1:{s:3:\"all\";a:5:{s:13:\"css/reset.css\";s:40:\"sites/all/themes/lc3_clean/css/reset.css\";s:14:\"css/layout.css\";s:41:\"sites/all/themes/lc3_clean/css/layout.css\";s:13:\"css/style.css\";s:40:\"sites/all/themes/lc3_clean/css/style.css\";s:11:\"css/lc3.css\";s:38:\"sites/all/themes/lc3_clean/css/lc3.css\";s:16:\"system.menus.css\";s:43:\"sites/all/themes/lc3_clean/system.menus.css\";}}s:7:\"scripts\";a:3:{s:20:\"js/jquery.blockUI.js\";s:47:\"sites/all/themes/lc3_clean/js/jquery.blockUI.js\";s:11:\"js/popup.js\";s:38:\"sites/all/themes/lc3_clean/js/popup.js\";s:17:\"js/topMessages.js\";s:44:\"sites/all/themes/lc3_clean/js/topMessages.js\";}s:7:\"regions\";a:13:{s:6:\"header\";s:6:\"Header\";s:6:\"search\";s:6:\"Search\";s:4:\"help\";s:4:\"Help\";s:11:\"highlighted\";s:11:\"Highlighted\";s:13:\"sidebar_first\";s:14:\"Sidebar (left)\";s:7:\"content\";s:7:\"Content\";s:14:\"sidebar_second\";s:15:\"Sidebar (right)\";s:6:\"footer\";s:6:\"Footer\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"settings\";a:2:{s:26:\"theme_social_link_facebook\";s:12:\"litecommerce\";s:25:\"theme_social_link_twitter\";s:12:\"litecommerce\";}s:6:\"engine\";s:11:\"phptemplate\";s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:3:\"php\";s:5:\"5.2.4\";s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}');
INSERT INTO `drupal_system` VALUES ('themes/bartik/bartik.info','bartik','theme','themes/engines/phptemplate/phptemplate.engine',1,0,-1,0,'a:18:{s:4:\"name\";s:6:\"Bartik\";s:11:\"description\";s:48:\"A flexible, recolorable theme with many regions.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:2:{s:3:\"all\";a:3:{s:14:\"css/layout.css\";s:28:\"themes/bartik/css/layout.css\";s:13:\"css/style.css\";s:27:\"themes/bartik/css/style.css\";s:14:\"css/colors.css\";s:28:\"themes/bartik/css/colors.css\";}s:5:\"print\";a:1:{s:13:\"css/print.css\";s:27:\"themes/bartik/css/print.css\";}}s:7:\"regions\";a:20:{s:6:\"header\";s:6:\"Header\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:11:\"highlighted\";s:11:\"Highlighted\";s:8:\"featured\";s:8:\"Featured\";s:7:\"content\";s:7:\"Content\";s:13:\"sidebar_first\";s:13:\"Sidebar first\";s:14:\"sidebar_second\";s:14:\"Sidebar second\";s:14:\"triptych_first\";s:14:\"Triptych first\";s:15:\"triptych_middle\";s:15:\"Triptych middle\";s:13:\"triptych_last\";s:13:\"Triptych last\";s:18:\"footer_firstcolumn\";s:19:\"Footer first column\";s:19:\"footer_secondcolumn\";s:20:\"Footer second column\";s:18:\"footer_thirdcolumn\";s:19:\"Footer third column\";s:19:\"footer_fourthcolumn\";s:20:\"Footer fourth column\";s:6:\"footer\";s:6:\"Footer\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"settings\";a:1:{s:20:\"shortcut_module_link\";s:1:\"0\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:28:\"themes/bartik/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}');
INSERT INTO `drupal_system` VALUES ('themes/garland/garland.info','garland','theme','themes/engines/phptemplate/phptemplate.engine',0,0,-1,0,'a:18:{s:4:\"name\";s:7:\"Garland\";s:11:\"description\";s:111:\"A multi-column theme which can be configured to modify colors and switch between fixed and fluid width layouts.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:2:{s:3:\"all\";a:1:{s:9:\"style.css\";s:24:\"themes/garland/style.css\";}s:5:\"print\";a:1:{s:9:\"print.css\";s:24:\"themes/garland/print.css\";}}s:8:\"settings\";a:1:{s:13:\"garland_width\";s:5:\"fluid\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:7:\"regions\";a:12:{s:13:\"sidebar_first\";s:12:\"Left sidebar\";s:14:\"sidebar_second\";s:13:\"Right sidebar\";s:7:\"content\";s:7:\"Content\";s:6:\"header\";s:6:\"Header\";s:6:\"footer\";s:6:\"Footer\";s:11:\"highlighted\";s:11:\"Highlighted\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:29:\"themes/garland/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}');
INSERT INTO `drupal_system` VALUES ('themes/seven/seven.info','seven','theme','themes/engines/phptemplate/phptemplate.engine',1,0,-1,0,'a:18:{s:4:\"name\";s:5:\"Seven\";s:11:\"description\";s:65:\"A simple one-column, tableless, fluid width administration theme.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:1:{s:6:\"screen\";a:2:{s:9:\"reset.css\";s:22:\"themes/seven/reset.css\";s:9:\"style.css\";s:22:\"themes/seven/style.css\";}}s:8:\"settings\";a:1:{s:20:\"shortcut_module_link\";s:1:\"1\";}s:7:\"regions\";a:8:{s:7:\"content\";s:7:\"Content\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:13:\"sidebar_first\";s:13:\"First sidebar\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:14:\"regions_hidden\";a:3:{i:0;s:13:\"sidebar_first\";i:1;s:8:\"page_top\";i:2;s:11:\"page_bottom\";}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:27:\"themes/seven/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}');
INSERT INTO `drupal_system` VALUES ('themes/stark/stark.info','stark','theme','themes/engines/phptemplate/phptemplate.engine',0,0,-1,0,'a:17:{s:4:\"name\";s:5:\"Stark\";s:11:\"description\";s:208:\"This theme demonstrates Drupal\'s default HTML markup and CSS styles. To learn how to build your own theme and override Drupal\'s default code, see the <a href=\"http://drupal.org/theme-guide\">Theming Guide</a>.\";s:7:\"package\";s:4:\"Core\";s:7:\"version\";s:3:\"7.0\";s:4:\"core\";s:3:\"7.x\";s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:10:\"layout.css\";s:23:\"themes/stark/layout.css\";}}s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:7:\"regions\";a:12:{s:13:\"sidebar_first\";s:12:\"Left sidebar\";s:14:\"sidebar_second\";s:13:\"Right sidebar\";s:7:\"content\";s:7:\"Content\";s:6:\"header\";s:6:\"Header\";s:6:\"footer\";s:6:\"Footer\";s:11:\"highlighted\";s:11:\"Highlighted\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:27:\"themes/stark/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}');
INSERT INTO `drupal_system` VALUES ('themes/tests/test_theme/test_theme.info','test_theme','theme','themes/engines/phptemplate/phptemplate.engine',0,0,-1,0,'a:17:{s:4:\"name\";s:10:\"Test theme\";s:11:\"description\";s:34:\"Theme for testing the theme system\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:11:\"stylesheets\";a:1:{s:3:\"all\";a:1:{s:15:\"system.base.css\";s:39:\"themes/tests/test_theme/system.base.css\";}}s:7:\"version\";s:3:\"7.0\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:7:\"regions\";a:12:{s:13:\"sidebar_first\";s:12:\"Left sidebar\";s:14:\"sidebar_second\";s:13:\"Right sidebar\";s:7:\"content\";s:7:\"Content\";s:6:\"header\";s:6:\"Header\";s:6:\"footer\";s:6:\"Footer\";s:11:\"highlighted\";s:11:\"Highlighted\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:38:\"themes/tests/test_theme/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}');
INSERT INTO `drupal_system` VALUES ('themes/tests/update_test_basetheme/update_test_basetheme.info','update_test_basetheme','theme','themes/engines/phptemplate/phptemplate.engine',0,0,-1,0,'a:17:{s:4:\"name\";s:22:\"Update test base theme\";s:11:\"description\";s:63:\"Test theme which acts as a base theme for other test subthemes.\";s:4:\"core\";s:3:\"7.x\";s:6:\"hidden\";b:1;s:7:\"version\";s:3:\"7.0\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:7:\"regions\";a:12:{s:13:\"sidebar_first\";s:12:\"Left sidebar\";s:14:\"sidebar_second\";s:13:\"Right sidebar\";s:7:\"content\";s:7:\"Content\";s:6:\"header\";s:6:\"Header\";s:6:\"footer\";s:6:\"Footer\";s:11:\"highlighted\";s:11:\"Highlighted\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:49:\"themes/tests/update_test_basetheme/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:11:\"stylesheets\";a:0:{}s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}');
INSERT INTO `drupal_system` VALUES ('themes/tests/update_test_subtheme/update_test_subtheme.info','update_test_subtheme','theme','themes/engines/phptemplate/phptemplate.engine',0,0,-1,0,'a:18:{s:4:\"name\";s:20:\"Update test subtheme\";s:11:\"description\";s:62:\"Test theme which uses update_test_basetheme as the base theme.\";s:4:\"core\";s:3:\"7.x\";s:10:\"base theme\";s:21:\"update_test_basetheme\";s:6:\"hidden\";b:1;s:7:\"version\";s:3:\"7.0\";s:7:\"project\";s:6:\"drupal\";s:9:\"datestamp\";s:10:\"1294208756\";s:6:\"engine\";s:11:\"phptemplate\";s:7:\"regions\";a:12:{s:13:\"sidebar_first\";s:12:\"Left sidebar\";s:14:\"sidebar_second\";s:13:\"Right sidebar\";s:7:\"content\";s:7:\"Content\";s:6:\"header\";s:6:\"Header\";s:6:\"footer\";s:6:\"Footer\";s:11:\"highlighted\";s:11:\"Highlighted\";s:4:\"help\";s:4:\"Help\";s:8:\"page_top\";s:8:\"Page top\";s:11:\"page_bottom\";s:11:\"Page bottom\";s:14:\"dashboard_main\";s:16:\"Dashboard (main)\";s:17:\"dashboard_sidebar\";s:19:\"Dashboard (sidebar)\";s:18:\"dashboard_inactive\";s:20:\"Dashboard (inactive)\";}s:8:\"features\";a:9:{i:0;s:4:\"logo\";i:1;s:7:\"favicon\";i:2;s:4:\"name\";i:3;s:6:\"slogan\";i:4;s:17:\"node_user_picture\";i:5;s:20:\"comment_user_picture\";i:6;s:25:\"comment_user_verification\";i:7;s:9:\"main_menu\";i:8;s:14:\"secondary_menu\";}s:10:\"screenshot\";s:48:\"themes/tests/update_test_subtheme/screenshot.png\";s:3:\"php\";s:5:\"5.2.4\";s:11:\"stylesheets\";a:0:{}s:7:\"scripts\";a:0:{}s:15:\"overlay_regions\";a:5:{i:0;s:14:\"dashboard_main\";i:1;s:17:\"dashboard_sidebar\";i:2;s:18:\"dashboard_inactive\";i:3;s:7:\"content\";i:4;s:4:\"help\";}s:14:\"regions_hidden\";a:2:{i:0;s:8:\"page_top\";i:1;s:11:\"page_bottom\";}s:28:\"overlay_supplemental_regions\";a:1:{i:0;s:8:\"page_top\";}}');
/*!40000 ALTER TABLE `drupal_system` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_taxonomy_index`
--

DROP TABLE IF EXISTS `drupal_taxonomy_index`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_taxonomy_index` (
  `nid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The drupal_node.nid this record tracks.',
  `tid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The term ID.',
  `sticky` tinyint(4) DEFAULT '0' COMMENT 'Boolean indicating whether the node is sticky.',
  `created` int(11) NOT NULL DEFAULT '0' COMMENT 'The Unix timestamp when the node was created.',
  KEY `term_node` (`tid`,`sticky`,`created`),
  KEY `nid` (`nid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Maintains denormalized information about node/term...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_taxonomy_index`
--

LOCK TABLES `drupal_taxonomy_index` WRITE;
/*!40000 ALTER TABLE `drupal_taxonomy_index` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_taxonomy_index` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_taxonomy_term_data`
--

DROP TABLE IF EXISTS `drupal_taxonomy_term_data`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_taxonomy_term_data` (
  `tid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary Key: Unique term ID.',
  `vid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The drupal_taxonomy_vocabulary.vid of the vocabulary to which the term is assigned.',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT 'The term name.',
  `description` longtext COMMENT 'A description of the term.',
  `format` varchar(255) DEFAULT NULL COMMENT 'The drupal_filter_format.format of the description.',
  `weight` int(11) NOT NULL DEFAULT '0' COMMENT 'The weight of this term in relation to other terms.',
  PRIMARY KEY (`tid`),
  KEY `taxonomy_tree` (`vid`,`weight`,`name`),
  KEY `vid_name` (`vid`,`name`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='Stores term information.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_taxonomy_term_data`
--

LOCK TABLES `drupal_taxonomy_term_data` WRITE;
/*!40000 ALTER TABLE `drupal_taxonomy_term_data` DISABLE KEYS */;
INSERT INTO `drupal_taxonomy_term_data` VALUES (1,2,'General discussion','',NULL,0);
/*!40000 ALTER TABLE `drupal_taxonomy_term_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_taxonomy_term_hierarchy`
--

DROP TABLE IF EXISTS `drupal_taxonomy_term_hierarchy`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_taxonomy_term_hierarchy` (
  `tid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Primary Key: The drupal_taxonomy_term_data.tid of the term.',
  `parent` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Primary Key: The drupal_taxonomy_term_data.tid of the term’s parent. 0 indicates no parent.',
  PRIMARY KEY (`tid`,`parent`),
  KEY `parent` (`parent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores the hierarchical relationship between terms.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_taxonomy_term_hierarchy`
--

LOCK TABLES `drupal_taxonomy_term_hierarchy` WRITE;
/*!40000 ALTER TABLE `drupal_taxonomy_term_hierarchy` DISABLE KEYS */;
INSERT INTO `drupal_taxonomy_term_hierarchy` VALUES (1,0);
/*!40000 ALTER TABLE `drupal_taxonomy_term_hierarchy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_taxonomy_vocabulary`
--

DROP TABLE IF EXISTS `drupal_taxonomy_vocabulary`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_taxonomy_vocabulary` (
  `vid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary Key: Unique vocabulary ID.',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT 'Name of the vocabulary.',
  `machine_name` varchar(255) NOT NULL DEFAULT '' COMMENT 'The vocabulary machine name.',
  `description` longtext COMMENT 'Description of the vocabulary.',
  `hierarchy` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'The type of hierarchy allowed within the vocabulary. (0 = disabled, 1 = single, 2 = multiple)',
  `module` varchar(255) NOT NULL DEFAULT '' COMMENT 'The module which created the vocabulary.',
  `weight` int(11) NOT NULL DEFAULT '0' COMMENT 'The weight of this vocabulary in relation to other vocabularies.',
  PRIMARY KEY (`vid`),
  UNIQUE KEY `machine_name` (`machine_name`),
  KEY `list` (`weight`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='Stores vocabulary information.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_taxonomy_vocabulary`
--

LOCK TABLES `drupal_taxonomy_vocabulary` WRITE;
/*!40000 ALTER TABLE `drupal_taxonomy_vocabulary` DISABLE KEYS */;
INSERT INTO `drupal_taxonomy_vocabulary` VALUES (1,'Tags','tags','Use tags to group articles on similar topics into categories.',0,'taxonomy',0);
INSERT INTO `drupal_taxonomy_vocabulary` VALUES (2,'Forums','forums','Forum navigation vocabulary',1,'forum',-10);
/*!40000 ALTER TABLE `drupal_taxonomy_vocabulary` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_url_alias`
--

DROP TABLE IF EXISTS `drupal_url_alias`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_url_alias` (
  `pid` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'A unique path alias identifier.',
  `source` varchar(255) NOT NULL DEFAULT '' COMMENT 'The Drupal path this alias is for; e.g. node/12.',
  `alias` varchar(255) NOT NULL DEFAULT '' COMMENT 'The alias for this path; e.g. title-of-the-story.',
  `language` varchar(12) NOT NULL DEFAULT '' COMMENT 'The language this alias is for; if ’und’, the alias will be used for unknown languages. Each Drupal path can have an alias for each supported language.',
  PRIMARY KEY (`pid`),
  KEY `alias_language_pid` (`alias`,`language`,`pid`),
  KEY `source_language_pid` (`source`,`language`,`pid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COMMENT='A list of URL aliases for Drupal paths; a user may visit...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_url_alias`
--

LOCK TABLES `drupal_url_alias` WRITE;
/*!40000 ALTER TABLE `drupal_url_alias` DISABLE KEYS */;
INSERT INTO `drupal_url_alias` VALUES (1,'node/1','features/feature-list.html','und');
INSERT INTO `drupal_url_alias` VALUES (2,'node/2','download-free.html','und');
INSERT INTO `drupal_url_alias` VALUES (3,'node/3','community.html','und');
INSERT INTO `drupal_url_alias` VALUES (5,'node/6','about.html','und');
INSERT INTO `drupal_url_alias` VALUES (6,'node/5','company.html','und');
/*!40000 ALTER TABLE `drupal_url_alias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_users`
--

DROP TABLE IF EXISTS `drupal_users`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_users` (
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Primary Key: Unique user ID.',
  `name` varchar(60) NOT NULL DEFAULT '' COMMENT 'Unique user name.',
  `pass` varchar(128) NOT NULL DEFAULT '' COMMENT 'User’s password (hashed).',
  `mail` varchar(254) DEFAULT '' COMMENT 'User’s e-mail address.',
  `theme` varchar(255) NOT NULL DEFAULT '' COMMENT 'User’s default theme.',
  `signature` varchar(255) NOT NULL DEFAULT '' COMMENT 'User’s signature.',
  `signature_format` varchar(255) DEFAULT NULL COMMENT 'The drupal_filter_format.format of the signature.',
  `created` int(11) NOT NULL DEFAULT '0' COMMENT 'Timestamp for when user was created.',
  `access` int(11) NOT NULL DEFAULT '0' COMMENT 'Timestamp for previous time user accessed the site.',
  `login` int(11) NOT NULL DEFAULT '0' COMMENT 'Timestamp for user’s last login.',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Whether the user is active(1) or blocked(0).',
  `timezone` varchar(32) DEFAULT NULL COMMENT 'User’s time zone.',
  `language` varchar(12) NOT NULL DEFAULT '' COMMENT 'User’s default language.',
  `picture` int(11) NOT NULL DEFAULT '0' COMMENT 'Foreign key: drupal_file_managed.fid of user’s picture.',
  `init` varchar(254) DEFAULT '' COMMENT 'E-mail address used for initial account creation.',
  `data` longblob COMMENT 'A serialized array of name value pairs that are related to the user. Any form values posted during user edit are stored and are loaded into the $user object during user_load(). Use of this field is discouraged and it will likely disappear in a future...',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `name` (`name`),
  KEY `access` (`access`),
  KEY `created` (`created`),
  KEY `mail` (`mail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores user data.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_users`
--

LOCK TABLES `drupal_users` WRITE;
/*!40000 ALTER TABLE `drupal_users` DISABLE KEYS */;
INSERT INTO `drupal_users` VALUES (0,'','','','','',NULL,0,0,0,0,NULL,'',0,'',NULL);
INSERT INTO `drupal_users` VALUES (1,'master','$S$CzHYMdRaWk0/D5IPMpQOr6DmV4VsVrTR0OCbMg4bK1KArNvp6aBL','rnd_tester@cdev.ru','','',NULL,1291297620,1294958723,1294947881,1,NULL,'',0,'rnd_tester@cdev.ru',NULL);
INSERT INTO `drupal_users` VALUES (4,'guest','$S$CMFQF7hsc3LyRz1ILdcwFG7b.0g3VBsJH0skQxbboHjHhNv5/QXC','rnd_tester@rrf.ru','','',NULL,1294859986,0,0,0,NULL,'',0,'rnd_tester@rrf.ru','');
/*!40000 ALTER TABLE `drupal_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_users_roles`
--

DROP TABLE IF EXISTS `drupal_users_roles`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_users_roles` (
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Primary Key: drupal_users.uid for user.',
  `rid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Primary Key: drupal_role.rid for role.',
  PRIMARY KEY (`uid`,`rid`),
  KEY `rid` (`rid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Maps users to roles.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_users_roles`
--

LOCK TABLES `drupal_users_roles` WRITE;
/*!40000 ALTER TABLE `drupal_users_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_users_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_variable`
--

DROP TABLE IF EXISTS `drupal_variable`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_variable` (
  `name` varchar(128) NOT NULL DEFAULT '' COMMENT 'The name of the variable.',
  `value` longblob NOT NULL COMMENT 'The value of the variable.',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Named variable/value pairs created by Drupal core or any...';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_variable`
--

LOCK TABLES `drupal_variable` WRITE;
/*!40000 ALTER TABLE `drupal_variable` DISABLE KEYS */;
INSERT INTO `drupal_variable` VALUES ('admin_theme','s:5:\"seven\";');
INSERT INTO `drupal_variable` VALUES ('clean_url','s:1:\"0\";');
INSERT INTO `drupal_variable` VALUES ('comment_page','i:0;');
INSERT INTO `drupal_variable` VALUES ('cron_key','s:43:\"T-AEZGVRsjg-EDTQ7OI5JEcclqe8XyhMoor63MYFINE\";');
INSERT INTO `drupal_variable` VALUES ('cron_last','i:1294958605;');
INSERT INTO `drupal_variable` VALUES ('css_js_query_string','s:6:\"lez98k\";');
INSERT INTO `drupal_variable` VALUES ('date_default_timezone','s:13:\"Europe/Moscow\";');
INSERT INTO `drupal_variable` VALUES ('default_nodes_main','i:1;');
INSERT INTO `drupal_variable` VALUES ('drupal_http_request_fails','b:0;');
INSERT INTO `drupal_variable` VALUES ('drupal_private_key','s:43:\"gkW09efAgvXzwAAcbpdl107bTYfSc8sA_V8HxAOhwcs\";');
INSERT INTO `drupal_variable` VALUES ('file_temporary_path','s:4:\"/tmp\";');
INSERT INTO `drupal_variable` VALUES ('filter_fallback_format','s:10:\"plain_text\";');
INSERT INTO `drupal_variable` VALUES ('forum_nav_vocabulary','s:1:\"2\";');
INSERT INTO `drupal_variable` VALUES ('install_profile','s:8:\"standard\";');
INSERT INTO `drupal_variable` VALUES ('install_task','s:4:\"done\";');
INSERT INTO `drupal_variable` VALUES ('install_time','i:1291297705;');
INSERT INTO `drupal_variable` VALUES ('maintenance_mode','b:0;');
INSERT INTO `drupal_variable` VALUES ('menu_expanded','a:1:{i:0;s:9:\"main-menu\";}');
INSERT INTO `drupal_variable` VALUES ('menu_masks','a:35:{i:0;i:501;i:1;i:493;i:2;i:250;i:3;i:247;i:4;i:246;i:5;i:245;i:6;i:125;i:7;i:123;i:8;i:122;i:9;i:121;i:10;i:117;i:11;i:63;i:12;i:62;i:13;i:61;i:14;i:60;i:15;i:59;i:16;i:58;i:17;i:44;i:18;i:31;i:19;i:30;i:20;i:29;i:21;i:28;i:22;i:24;i:23;i:21;i:24;i:15;i:25;i:14;i:26;i:13;i:27;i:11;i:28;i:10;i:29;i:7;i:30;i:6;i:31;i:5;i:32;i:3;i:33;i:2;i:34;i:1;}');
INSERT INTO `drupal_variable` VALUES ('node_admin_theme','s:1:\"1\";');
INSERT INTO `drupal_variable` VALUES ('node_cron_last','s:10:\"1293062097\";');
INSERT INTO `drupal_variable` VALUES ('node_options_forum','a:1:{i:0;s:6:\"status\";}');
INSERT INTO `drupal_variable` VALUES ('node_options_page','a:1:{i:0;s:6:\"status\";}');
INSERT INTO `drupal_variable` VALUES ('node_submitted_page','b:0;');
INSERT INTO `drupal_variable` VALUES ('path_alias_whitelist','a:1:{s:4:\"node\";b:1;}');
INSERT INTO `drupal_variable` VALUES ('site_default_country','s:2:\"US\";');
INSERT INTO `drupal_variable` VALUES ('site_mail','s:18:\"rnd_tester@cdev.ru\";');
INSERT INTO `drupal_variable` VALUES ('site_name','s:23:\"xcart2-530.crtdev.local\";');
INSERT INTO `drupal_variable` VALUES ('theme_default','s:9:\"lc3_clean\";');
INSERT INTO `drupal_variable` VALUES ('theme_lc3_clean_settings','a:17:{s:11:\"toggle_logo\";i:1;s:11:\"toggle_name\";i:0;s:13:\"toggle_slogan\";i:1;s:24:\"toggle_node_user_picture\";i:1;s:27:\"toggle_comment_user_picture\";i:1;s:32:\"toggle_comment_user_verification\";i:1;s:14:\"toggle_favicon\";i:1;s:16:\"toggle_main_menu\";i:1;s:21:\"toggle_secondary_menu\";i:1;s:12:\"default_logo\";i:1;s:9:\"logo_path\";s:0:\"\";s:11:\"logo_upload\";s:0:\"\";s:15:\"default_favicon\";i:1;s:12:\"favicon_path\";s:0:\"\";s:14:\"favicon_upload\";s:0:\"\";s:26:\"theme_social_link_facebook\";s:12:\"litecommerce\";s:25:\"theme_social_link_twitter\";s:12:\"litecommerce\";}');
INSERT INTO `drupal_variable` VALUES ('user_admin_role','s:1:\"3\";');
INSERT INTO `drupal_variable` VALUES ('user_pictures','s:1:\"1\";');
INSERT INTO `drupal_variable` VALUES ('user_picture_dimensions','s:9:\"1024x1024\";');
INSERT INTO `drupal_variable` VALUES ('user_picture_file_size','s:3:\"800\";');
INSERT INTO `drupal_variable` VALUES ('user_picture_style','s:9:\"thumbnail\";');
INSERT INTO `drupal_variable` VALUES ('user_register','i:2;');
/*!40000 ALTER TABLE `drupal_variable` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_watchdog`
--

DROP TABLE IF EXISTS `drupal_watchdog`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_watchdog` (
  `wid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary Key: Unique watchdog event ID.',
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT 'The drupal_users.uid of the user who triggered the event.',
  `type` varchar(64) NOT NULL DEFAULT '' COMMENT 'Type of log message, for example "user" or "page not found."',
  `message` longtext NOT NULL COMMENT 'Text of log message to be passed into the t() function.',
  `variables` longblob NOT NULL COMMENT 'Serialized array of variables that match the message string and that is passed into the t() function.',
  `severity` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'The severity level of the event; ranges from 0 (Emergency) to 7 (Debug)',
  `link` varchar(255) DEFAULT '' COMMENT 'Link to view the result of the event.',
  `location` text NOT NULL COMMENT 'URL of the origin of the event.',
  `referer` text COMMENT 'URL of referring page.',
  `hostname` varchar(128) NOT NULL DEFAULT '' COMMENT 'Hostname of the user who triggered the event.',
  `timestamp` int(11) NOT NULL DEFAULT '0' COMMENT 'Unix timestamp of when event occurred.',
  PRIMARY KEY (`wid`),
  KEY `type` (`type`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=327 DEFAULT CHARSET=utf8 COMMENT='Table that contains logs of all system events.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_watchdog`
--

LOCK TABLES `drupal_watchdog` WRITE;
/*!40000 ALTER TABLE `drupal_watchdog` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_watchdog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drupal_wysiwyg`
--

DROP TABLE IF EXISTS `drupal_wysiwyg`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `drupal_wysiwyg` (
  `format` varchar(255) NOT NULL,
  `editor` varchar(128) NOT NULL DEFAULT '',
  `settings` text,
  PRIMARY KEY (`format`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores Wysiwyg profiles.';
SET character_set_client = @saved_cs_client;

--
-- Dumping data for table `drupal_wysiwyg`
--

LOCK TABLES `drupal_wysiwyg` WRITE;
/*!40000 ALTER TABLE `drupal_wysiwyg` DISABLE KEYS */;
/*!40000 ALTER TABLE `drupal_wysiwyg` ENABLE KEYS */;
UNLOCK TABLES;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2011-01-13 22:48:31
