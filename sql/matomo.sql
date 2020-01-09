CREATE TABLE /*$wgDBprefix*/matomo (
  `matomo_id` INT unsigned NOT NULL PRIMARY KEY,
  `matomo_wiki` VARCHAR(64) NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/matomo_wiki ON /*_*/matomo (matomo_wiki);
