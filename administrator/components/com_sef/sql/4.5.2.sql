ALTER TABLE `#__sefurls` ADD KEY `idx_updates` (`locked`, `flag`);
ALTER TABLE `#__sefurls` ENGINE=InnoDB;