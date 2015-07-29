CREATE TABLE IF NOT EXISTS `#__sefurls` (
  `id` int(11) NOT NULL auto_increment,
  `cpt` int(11) NOT NULL default '0',
  `sefurl` varchar(255) NOT NULL,
  `origurl` varchar(255) NOT NULL,
  `Itemid` varchar(20) default NULL,
  `metadesc` varchar(255) default '',
  `metakey` varchar(255) default '',
  `metatitle` varchar(255) default '',
  `metalang` varchar(30) default '',
  `metarobots` varchar(30) default '',
  `metagoogle` varchar(30) default '',
  `metacustom` text,
  `metaauthor` varchar(30) default '',
  `canonicallink` varchar(255) default '',
  `dateadd` date NOT NULL default '0000-00-00',
  `priority` int(11) NOT NULL DEFAULT '0',
  `trace` text DEFAULT NULL,
  `enabled` TINYINT(1) NOT NULL DEFAULT '1',
  `locked` TINYINT(1) NOT NULL DEFAULT '0',
  `sef` TINYINT(1) NOT NULL DEFAULT '1',
  `sm_indexed` TINYINT(1) NOT NULL DEFAULT '0',
  `sm_date` DATE NOT NULL DEFAULT '0000-00-00',
  `sm_frequency` VARCHAR(20) NOT NULL DEFAULT 'weekly',
  `sm_priority` VARCHAR(10) NOT NULL DEFAULT '0.5',
  `flag` TINYINT(1) NOT NULL DEFAULT '0',
  `host` varchar(255) NOT NULL DEFAULT '',
  `showsitename` INTEGER(1) NOT NULL DEFAULT '3',
  PRIMARY KEY  (`id`),
  KEY `sefurl` (`sefurl`),
  KEY `origurl` (`origurl`, `Itemid`),
  KEY `idx_updates` (`locked`, `flag`)
) ENGINE=InnoDB CHARACTER SET 'utf8';

CREATE TABLE IF NOT EXISTS `#__sefexts` (
  `id` int(11) NOT NULL auto_increment,
  `file` varchar(100) NOT NULL,
  `filters` text,
  `params` text,
  `title` varchar(255),
  PRIMARY KEY  (`id`)
) CHARACTER SET 'utf8';

CREATE TABLE IF NOT EXISTS `#__sefmoved` (
  `id` int(11) NOT NULL auto_increment,
  `old` varchar(255) NOT NULL,
  `new` varchar(255) NOT NULL,
  `lastHit` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `old` (`old`)
) CHARACTER SET 'utf8';

CREATE TABLE IF NOT EXISTS `#__sefexttexts` (
  `id` int(11) NOT NULL auto_increment,
  `extension` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `value` varchar(100) NOT NULL,
  `lang_id` int(5) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`)
) CHARACTER SET 'utf8';

CREATE TABLE IF NOT EXISTS `#__sefwords` (
  `id` int(11) NOT NULL auto_increment,
  `word` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) CHARACTER SET 'utf8';

CREATE TABLE IF NOT EXISTS `#__sefurlword_xref` (
  `word` int(11) NOT NULL,
  `url` int(11) NOT NULL,
  PRIMARY KEY (`word`, `url`)
) CHARACTER SET 'utf8';

CREATE TABLE IF NOT EXISTS `#__sefaliases` (
  `id` int(11) NOT NULL auto_increment,
  `alias` varchar(255) NOT NULL DEFAULT '',
  `vars` text NOT NULL,
  `url` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `alias` (`alias`)
) CHARACTER SET 'utf8';

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
) CHARACTER SET 'utf8';

CREATE TABLE IF NOT EXISTS `#__seflog` (
  `id` INTEGER(11) NOT NULL AUTO_INCREMENT,
  `time` DATETIME NOT NULL,
  `message` VARCHAR(255) DEFAULT NULL,
  `url` VARCHAR(255) NOT NULL DEFAULT '',
  `component` VARCHAR(255) DEFAULT NULL,
  `page` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) CHARACTER SET 'utf8';

CREATE TABLE IF NOT EXISTS `#__sef_subdomains` (
  `subdomain` varchar(255) NOT NULL DEFAULT '',
  `Itemid` mediumtext NOT NULL,
  `Itemid_titlepage` int(10) NOT NULL,
  `option` varchar(255) NOT NULL,
  `lang` varchar(10) NOT NULL,
  PRIMARY KEY (`subdomain`,`lang`)
) CHARACTER SET 'utf8';