# Changelog
## 2.0.0-Beta1
- Added: Support for Matomo 4 🎉
- Updated: Bumped the minimum PHP version to 7.2.5 for this new major plugin version, just as Matomo 4 itself

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
