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

Check configuration and installation environment
------------------------------------------------

AgenDAV ships, since version 1.2.4, a simple script that checks installation
environment and configuration files to make sure you meet all basic
requisites.

To run it, edit file :file:`web/public/configtest.php` to set the constant
``ENABLE_SETUP_TESTS`` to ``TRUE``.

Once you save the file with that change, point your browser to
``http://host/path/agendav/configtest.php`` and look for red cells. You'll
find some suggestions to fix the problems.

Remember to set ``ENABLE_SETUP_TESTS`` back to ``FALSE`` inside
``configtest.php``.

More verbose logs
-----------------

Edit ``web/config/config.php`` and add the value ``INTERNALS`` inside
``show_in_log`` variable. For example::

  $config['show_in_log']= array('ERROR','INFO','AUTHERR', 'AUTHOK','INTERNALS');

Check AgenDAV logs (make sure you have Check AgenDAV logs (make sure you
have a valid path configured in :confval:`log_path` and the user which runs
the webserver has writing access to it) and your webserver logs.

You can add the value ``DEBUG`` to make CodeIgniter (the PHP framework) log
some more lines.

Show errors
-----------

You can switch to ``development`` environment to force PHP to print errors
on generated pages. By default AgenDAV is configured to hide errors to
users.

To achieve that just edit the file ``web/public/index.php`` and replace the
following line::

	define('ENVIRONMENT', 'production');

by::

	define('ENVIRONMENT', 'development');


Capture traffic
---------------

Sometimes CalDAV servers send unexpected data to AgenDAV or AgenDAV tries to
do an unsupported operation on your CalDAV server. When this happens it's a
good idea to run a traffic capture tool (like ``tcpdump`` or ``Wireshark``)
to see what's happening under the hood. This is only possible if you use
plain HTTP on your AgenDAV<->CalDAV server communication.

Debug your browser status
-------------------------

Most browsers can show you network activity and JavaScript errors using its
own interfaces. They can be very handful if you happen to find a bug on
AgenDAV. Some examples of browser which include this support are:

* Mozilla Firefox with Firebug extension
* Google Chrome/Chromium with Developer Tools (no addon required)
