## FAQ

__How are the audits generated? Are any external services used?__

The audits are generated using Node.js in the background by running Googles Lighthouse tool against all the visited/tracked pages within the last 30 days. Everything is executed locally on your server, no data is transferred to external services.

__How do I change to audit only mobile or only desktop environments of my site?__

Under `General Settings` > `Websites` > `Manage` click on the edit button of the site you want to change the setting of and scroll to the setting `Performance simulation environment` selection to change it.

__I just installed the plugin or I just switched the website settings of the performance simulation environment and I cannot see any performance reports, why is that?__

You need to wait at least 24h until the reports are getting generated in the background with the scheduled tasks of your Matomo cron job.

__I shutdown/restarted my server and now the performance reports won't get generated anymore, what can I do?__

If the site audit gets unexpectedly interrupted, so the plugin can't properly finish its code execution it will have a problem to restart. It would be possible to automatically fix this problematic state, but very long running site audits would run into issues then. So in this case you either wait until the next week starts (an internal plugin reset will happen then), or you can run the Matomo console command `console performanceaudit:clear-task-running-flag` which will reset the plugin state, so the audits will be scheduled correctly again at the next possible time.

__I want to use a custom HTTP header for authentication, so the audit plugin can access my protected pages, how do I do that?__

Under `General Settings` > `Websites` > `Manage` click on the edit button of the site you want to change the setting of and scroll to the checkbox `Use Custom HTTP header` and activate it in order to change the HTTP header key and its value. The audit tool will send then the entered header value paired with the selected header key to the server with every request.

__The plugin cannot install because Chromium cannot be installed due to a missing library?__

Make sure [all necessary dependencies](https://github.com/puppeteer/puppeteer/blob/main/docs/troubleshooting.md#chrome-headless-doesnt-launch-on-unix) for Chromium are installed.
