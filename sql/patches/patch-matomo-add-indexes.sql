-- Convert matomo table to use indexes for the db column
ALTER TABLE /*$wgDBprefix*/matomo
  ADD INDEX matomo_wiki (matomo_wiki);
