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

.. _development_environment:

Development environment
-----------------------

You can switch to ``development`` environment easily by setting the environment
variable ``AGENDAV_ENVIRONMENT`` to ``dev``.

Environment variables have to be set on your webserver configuration file.
Apache lets you do it using ``SetEnv``, or even better, using ``SetEnvIf`` to
enable the development environment just for some IPs. Example::

   <Location />
      SetEnvIf Remote_Addr ^1\.2\.3\.4$ AGENDAV_ENVIRONMENT=dev
   </Location>

Then point your browser to ``http://your.agendav.host/``. A debugging
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
