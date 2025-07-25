## ChangeLog for MatomoAnalytics


### 1.5.0 (03-07-2025)
* Migrate to QueryBuilder
* Remove optional parameters
* Type hint all methods
* General cleanup

### 1.4.0 (30-06-2025)
* Migrates from CustomVars to CustomDimensions. Also put behind a config allowing control over whether it is enabled or not. The change is breaking for those who use this.

### 1.3.5 (08-03-2025)
* Maintenance scripts are now required to be ran with run.php.

### 1.3.4 (04-02-2025)
* Dropped support for MediaWiki 1.42.

### 1.3.3 (17-11-2024)
* Added support for virtual database domains
* Removed support for wgMatomoAnalyticsDatabase

### 1.3.2 (12-11-2024)
* Introduced 'viewanalytics' right to fine-tune who can view analytics

### 1.3.1 (9-11-2024)
* Added caching support for results
* Began using ConfigNames class

### 1.3.0 (1-11-2024)
* Revamped Analytics UI and provided chart visualization support

### 1.2.0 (23-10-2024)
* Introduced support for viewing number of page views via ?action=info
* Namespaced the extension

### 1.1.3 (26-02-2024)
* Replace deprecated Xml::encodeJsVar with Html::encodeJsVar
* Now requires MediaWiki 1.41.0 or later

### 1.1.2 (04-02-2023)
* Add a CleanupMatomos maintenance script
* Add requireExtension() to all maintenance scripts
* Cleanup the ModifyMatomo maintenance script, and use MatomoAnalytics
      directly rather than going through MatomoAnalyticsHooks

### 1.1.1 (29-01-2023)
* Don't override $text within MatomoAnalyticsHooks::matomoScript

### 1.1.0 (29-01-2023)
* Ensure matomo script isn't added more than once
* Change error to warning for "could not find db in matomo table" logging
* Require MediaWiki 1.39.0

### 1.0.9 (12-01-2023)
* Replace deprecated wfGetDB()

### 1.0.8 (27-09-2022)
* Don't use Maintenance::$mDescription directly

### 1.0.7 (12-07-2022)
* Fix installing via composer version 2.2.1 and later

### 1.0.6 (29-06-2022)
* Require MediaWiki 1.38.0
* Modernise MatomoAnalyticsOOUIForm

### 1.0.5.14 (07-09-2021)
* Add CI for MediaWiki standards and security

### 1.0.5.13 (04-09-2021)
* Lower minimum MediaWiki version requirement to 1.35.3

### 1.0.5.12 (15-06-2021)
* Require MediaWiki 1.36.0
* DB_MASTER -> DB_PRIMARY

### 1.0.5.11 (26-05-2021)
* Stop outputting unnecessary HTML comments and 'type="text/javascript"'

### 1.0.5.10 (08-04-2021)
* Fix undefined variable

### 1.0.5.9 (05-04-2021)
* Add fixMissingMatomos script for syncing between cw_wikis and matomo on sites using CreateWiki

### 1.0.5.8 (14-03-2021)
* Use User::isRegistered instead of User::isLoggedIn

### 1.0.5.7 (14-03-2021)
* Add some more logging and also disable cache when deleting/rename a wik

### 1.0.5.6 (12-03-2021)
* add license-name

### 1.0.5.5 (28-02-2021)
* MatomoAnalytics: Add check within rename/add site to prevent deleting default id

### 1.0.5.4 (28-02-2021)
* add extra check for $id

### 1.0.5.3 (27-02-2021)
* MatomoAnalytics::getSiteID: Fix default value for when wiki isn't found 

### 1.0.5.2 (08-02-2021)
* Delete cache when deleting or renaming wikis.

### 1.0.5.1 (12-12-2020)
* Fix config name wgMatomoAnalyticsForGetRequest -> wgMatomoAnalyticsForceGetRequest.

### 1.0.5 (12-12-2020)
* Introduce wgMatomoAnalyticsForGetRequest config to force GET requests and reverts back to matomo 3 way of tracking.

### 1.0.4 (20-07-2020)
* Updates Matomo javascript to the version used in Matomo 3.14.0.

### 1.0.3 (03-01-2020)
* Adds `$wgMatomoAnalyticsDisableCookie` to allow disabling cookies set by Matomo. Useful in the European Union.

### 1.0.2 (30-05-2019)
* Adds `$wgMatomoAnalyticsDisableJS` to allow disabling of JS tracking code globally.

### 1.0.1 (30-05-2019)
* Drop unnecessary variable assignments.

### 1.0.0 (29-05-2019)
* Turn extension into a versioned project.
* Redesign whole code base (essentially an initial commit).
