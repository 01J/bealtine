ALTER TABLE `#__sef_subdomains` ADD `lang` varchar(10) NOT NULL;
ALTER TABLE `#__sef_subdomains` DROP `menuitems`;
ALTER TABLE `#__sef_subdomains` DROP `menuitem_titlepage`;
ALTER TABLE `#__sef_subdomains` DROP PRIMARY KEY;
ALTER TABLE `#__sef_subdomains` ADD PRIMARY KEY(`subdomain`, `lang`);