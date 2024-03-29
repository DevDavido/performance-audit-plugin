# Changelog
## 3.1.0
- Added: Search for Node executable additionally in paths of `PATH` environment variable
- Improved: Provide directory search path in error message if executable cannot be found

## 3.0.0
- Release stable version for Matomo 5
- Fixed: CORS template issue

## 3.0.0-Beta2
- Fixed: Issue with migrated Request handling

## 3.0.0-Beta1
- Added: Support for Matomo 5 🎉
- Updated: Bumped the minimum Node version to 20.x LTS for this new major plugin version
- Improved: Migrated some deprecated Matomo Plugin code

## 2.1.0
- Added: Site setting to group URLs by anchor which removes auditing duplicates of URLs which only differ in their anchors
- Added: Support to find latest Node executable in Plesk environment
- Added: Initial (partial) translation available for Albanian, Basque, Bokmål, Brazilian Portuguese, Bulgarian, Catalan, Chinese (Traditional), Dutch, French, German, Greek, Indonesian, Italian, Japanese, Portuguese, Spanish, Swedish, Turkish, Ukrainian
- Updated: Bumped `symfony/process`, `ducks-project/spl-types`, `symfony/polyfill-mbstring` and `friendsofphp/php-cs-fixer` dependency version
- Updated donation links

## 2.0.0
- Release stable version for Matomo 4
- Updated: Bumped `symfony/process` and `symfony/polyfill-mbstring` dependency version

## 2.0.0-Beta3
- Improved: Merged changes from 1.1.4 into 2.0.0-Beta2

## 2.0.0-Beta2
- Improved: Merged changes from 1.0.8 into 2.0.0-Beta2

## 2.0.0-Beta1
- Added: Support for Matomo 4 🎉
- Updated: Bumped the minimum PHP version to 7.2.5 for this new major plugin version, just as Matomo 4 itself

## 1.1.4
- Improved: Exception handling for failed audit due to too many requests response
- Fixed: One performance audit setting has been displayed after disabling audits for the site in settings
- Fixed: Disabled performance audits for site renders dashboard empty

## 1.1.3
- Added: Possibility to set extended audit timeout for each site
- Improved: Minor internal refactoring for site settings
- Updated: Bumped `symfony/process` dependency version

## 1.1.2
- Added: Possibility to enable or disable audit for each site
- Improved: Set timeout for installation process to 5 minutes

## 1.1.1
- Added: Scheduled weekly clearing of task running flag (in case of unexpected audit cancellation)
- Added: Console command to clear task running flag
- Fixed: Avoid race condition in case of which audit was called multiple times simultaneously
- Improved: Garbage collection after each audit
- Improved: Certain timeout/runtime exceptions don't stop following page audits anymore
- Improved: Set timeout for (audit) processes to 1 minute

## 1.1.0
- Fixed: Regular users cannot login anymore if plugin is activated
- Fixed: Renamed and fixed option to remove query strings from audited URLs which is now named group URLs and it doesn't throw SQL warnings anymore in certain (edge) cases
- Improved: Added security information for applied no sandbox mode of Chromium
- Improved: Increase database connection timeouts for longer running site audits
- Improved: Increase timeout for (audit) processes to 5 minutes
- Improved: Throw warning instead error for audited pages with HTTP 403 / 404 response
- Improved: Added FAQ entry for missing Chromium dependencies
- Improved: Error message during installation if directory permissions are incorrect

## 1.0.11
- Added: Option to remove query strings from audited URLs
- Improved: Small refactoring in settings

## 1.0.10
- Fixed: Issue with previous release as version number was incompatible with plugin marketplace

## 1.0.9
- Fixed: Schedule Reports and mobile app threw exception due to missing name attribute

## 1.0.8
- Improved: Refactored plugin base class, additionally removed now unneeded update classes
- Fixed: Updates could remove Node dependencies which now get reinstalled (regression bug)
- Fixed: Plugin cleanup now removes also symlinked directories

## 1.0.7
- Improved: Plugin check is now also running pre-checks first
- Improved: Plugin check is now independent of regular audit flow
- Improved: Wording in README text regarding tests
- Fixed: Updates could remove Node dependencies which now get reinstalled

## 1.0.6
- Fixed: Correct file require bug from previous release

## 1.0.5
- Added: Possibility to check plugin audit functionality

## 1.0.4
- Fixed: Switched to require `piwik` instead `matomo` in `plugin.json` for Matomo 3.x compatibility
- Improved: `plugin.json` plugin description

## 1.0.3
- Added: PerformanceAudit is live on the Matomo plugin marketplace now 🎉
- Added: Donation information in `plugin.json`
- Fixed: License name value in `plugin.json` was rewritten to a supported name value by Matomo Marketplace

## 1.0.2
- Fixed: Regression bug which made only first website get audited
- Improved: Logging of exceptions for audits
- Updated: Bumped `symfony/polyfill-mbstring` dependency version

## 1.0.1
- Fixed: Made sure tasks aren't executed concurrently
- Fixed: composer.json is compatible with Composer v2 now

## 1.0
- Initial release
