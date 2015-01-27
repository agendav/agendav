Troubleshooting AgenDAV
=======================

If you are having problems with AgenDAV, check you have met all the
requisites and search AgenDAV logs/web server logs for error lines.

You can write to `AgenDAV general list
<http://groups.google.com/group/agendav-general>`_ asking for help. Make
sure you include the following information:

* Software details (OS, PHP version, web server you're using, CalDAV server)
* Clear description of your problem
* Important log lines

Try the following before writing:

Switch to development mode
--------------------------

You can switch to ``development`` environment easily using the provided
``index_dev.php`` front controller.

In order to use it, the environment variable ``ENABLE_AGENDAV_DEVELOPMENT`` has to be
set. Set it on your webserver configuration file. Apache lets you do it using ``SetEnv``
or ``SetEnvIf``.

Then point your browser to ``http://your.agendav.host/index_dev.php``. A debugging
toolbar will appear, logs will be more verbose and a new HTTP debug log will be
generated.

Note that your application will be more slow and logs will grow really fast.

Debug your browser status
-------------------------

Most browsers can show you network activity and JavaScript errors using its
own interfaces. They can be very handful if you happen to find a bug on
AgenDAV. Some examples of browser which include this support are:

* Mozilla Firefox with Firebug extension
* Google Chrome/Chromium with Developer Tools (no addon required)
