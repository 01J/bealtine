CREATE TABLE IF NOT EXISTS `#__sef_statistics` (
  `url_id` int(5) NOT NULL,
  `page_rank` int(3) NOT NULL,
  `total_indexed` int(10) NOT NULL,
  `popularity` int(10) NOT NULL,
  `facebook_indexed` int(10) NOT NULL,
  `twitter_indexed` int(10) NOT NULL,
  `validation_score` varchar(255) NOT NULL,
  `page_speed_score` mediumtext NOT NULL,
  PRIMARY KEY (`url_id`)
);

CREATE TABLE IF NOT EXISTS `#__seflog` (
  `id` INTEGER(11) NOT NULL AUTO_INCREMENT,
  `time` DATETIME NOT NULL,
  `message` VARCHAR(255) DEFAULT NULL,
  `url` VARCHAR(255) NOT NULL DEFAULT '',
  `component` VARCHAR(255) DEFAULT NULL,
  `page` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `#__sef_subdomains` (
  `subdomain` varchar(255) NOT NULL DEFAULT '',
  `Itemid` mediumtext NOT NULL,
  `Itemid_titlepage` int(10) NOT NULL,
  `option` varchar(255) NOT NULL,
  `menuitems` mediumtext NOT NULL,
  `menuitem_titlepage` varchar(255) NOT NULL,
  PRIMARY KEY (`subdomain`)
); 

ALTER TABLE `#__sefurls` ADD COLUMN `host` varchar(255) NOT NULL DEFAULT '';