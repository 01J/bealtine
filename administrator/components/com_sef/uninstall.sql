DROP TABLE IF EXISTS `#__sefurls_bak`;
RENAME TABLE `#__sefurls` TO `#__sefurls_bak`;

DROP TABLE IF EXISTS `#__sefexts_bak`;
RENAME TABLE `#__sefexts` TO `#__sefexts_bak`;

DROP TABLE IF EXISTS `#__sefmoved_bak`;
RENAME TABLE `#__sefmoved` TO `#__sefmoved_bak`;

DROP TABLE IF EXISTS `#__sefexttexts_bak`;
RENAME TABLE `#__sefexttexts` TO `#__sefexttexts_bak`;

DROP TABLE IF EXISTS `#__sefwords_bak`;
RENAME TABLE `#__sefwords` TO `#__sefwords_bak`;

DROP TABLE IF EXISTS `#__sefurlword_xref_bak`;
RENAME TABLE `#__sefurlword_xref` TO `#__sefurlword_xref_bak`;

DROP TABLE IF EXISTS `#__sefaliases_bak`;
RENAME TABLE `#__sefaliases` TO `#__sefaliases_bak`;

DROP TABLE IF EXISTS `#__sef_statistics_bak`;
RENAME TABLE `#__sef_statistics` TO `#__sef_statistics_bak`;

DROP TABLE IF EXISTS `#__seflog_bak`;
RENAME TABLE `#__seflog` TO `#__seflog_bak`;

DROP TABLE IF EXISTS `#__sef_subdomains_bak`;
RENAME TABLE `#__sef_subdomains` TO `#__sef_subdomains_bak`;
