CREATE TABLE /*$wgDBprefix*/matomo (
  `matomo_id` INT unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `matomo_wiki` VARCHR(64) NOT NULL,
) /*$wgDBTableOptions*/;
