# Performance Audit Plugin for Matomo

[![Stable Version](https://img.shields.io/github/v/release/DevDavido/performance-audit-plugin)](https://github.com/DevDavido/performance-audit-plugin/releases)
[![GPL Licensed](https://img.shields.io/github/license/DevDavido/performance-audit-plugin?color=yellow)](LICENSE.md)
[![Tests Badge](https://img.shields.io/github/actions/workflow/status/DevDavido/performance-audit-plugin/tests.yml)](https://github.com/DevDavido/performance-audit-plugin/actions/workflows/tests.yml)

## Description
Daily performance audits of all your sites in Matomo for the following metrics based on Google Lighthouse:
- First Contentful Paint
- Speed Index
- Largest Contentful Paint
- Time To Interactive
- Total Blocking Time
- Cumulative Layout Shift
- Overall Score

Continuously monitor those Web Vital metrics over time, allowing detection of underlying problems before they have an adverse effect for users or simply track changes made to the web application, allowing you to establish a baseline for comparison too.

**Note:** Requires minimum NPM v10.x (part of Node.js 20.x LTS) to be installed on your server, otherwise plugin cannot be activated.

## Support me
If you installed this plugin and it was useful for you or your business, please don't hesitate to make a donation, I would highly appreciate it. Thank you!

<a href="https://www.buymeacoffee.com/devdavido" target="_blank"><img src="https://github.com/DevDavido/performance-audit-plugin/assets/997605/0c4e6a4c-9852-4410-a153-fa379fa7aa17" width="180" alt="Buy me a coffee"></a>

## Installation
Preferably you install [this plugin](https://plugins.matomo.org/PerformanceAudit) automatically by:
1. Login to Matomo as Super User
2. Navigate in your Matomo Installation to `Administration` › `Marketplace` (categorized under `Platform`)
3. Search for `Performance Audit`
4. Click on `Install` and wait up to 5 minutes for the installation to finish

You can also install this plugin manually by:
1. [downloading the latest plugin zip file](https://github.com/DevDavido/performance-audit-plugin/releases/latest)
2. Login to Matomo as Super User
3. Navigate in your Matomo Installation to `Administration` › `Marketplace` (categorized under `Platform`)
4. Click on `upload a Plugin` and upload the zip file from step 1
5. Activate this plugin `PerformanceAudit` and wait up to 5 minutes for the process to finish

Note: If plugin upload is disabled, enable it in your `config/config.ini.php` like that:
```ini
[General]
enable_plugin_upload = 1
```
If any errors occur during activation, please follow the instruction or information of the error message.

## Minimum requirements
- Matomo 5.0
- PHP 7.2.5
- NPM v10.x (part of [Node.js](https://nodejs.org/en/download/) 20.x LTS) to be installed on your server, otherwise plugin cannot be activated.
- If you use a UNIX based OS [these dependencies are required](https://github.com/puppeteer/puppeteer/blob/main/docs/troubleshooting.md#chrome-doesnt-launch-on-linux)

## Impact on server
Please keep in mind that Performance Audit uses Lighthouse internally in order to perform performance audits in the background for every Matomo tracked URL within the last 1 month.
If the Matomo installation has more than 10,000 tracked unique URLs within the last 1 month, it's suggested to either reduce the number of performed audit iterations in the website settings,
group URLs in website settings or increase the server resources. For Matomo installations with over 100,000 tracked unique URLs within the last 1 month,
this plugin architecture is rather unsuitable and therefor not recommended.

## Screenshots
### Dashboard
![Dashboard](/screenshots/Dashboard.png?raw=true)

### Overall Scores
![Overall Scores](/screenshots/OverallScores.png?raw=true)

### First Contentful Paint
![First Contentful Paint](/screenshots/FirstContentfulPaint.png?raw=true)

For more screenshots, check out the [screenshot overview](/screenshots/OVERVIEW.md).

## Testing
Run the integration and unit tests with:

```shell
./console tests:run PerformanceAudit
```
## Changelog
Please see the [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contact
If you have any questions or inquiries, you can contact `github{at}diskoboss{døt}de`.

## Security
If you discover any security related issues, please contact `github{at}diskoboss{døt}de` instead of using the issue tracker.

Please note that the performance audits will be performed with Chromium which renders Matomo-tracked web pages on your server without the sandbox mode of Chromium. You can find more information about this possible issue on the [lighthouse-cli repository](https://github.com/GoogleChrome/lighthouse-ci/tree/master/docs/recipes/docker-client#--no-sandbox-issues-explained).

## License
Licensed under the [GPLv3 License](LICENSE.md).
