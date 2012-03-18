Release notes
=============

.. _v1.2.4:

X.X.X (2012-XX-YY)
------------------

This release requires PHP cURL support. Make sure your PHP installation has
the cURL module enabled.

caldav.php rewrite (TODO)

translation (TODO), default language -> en

.. _v1.2.4:

1.2.4 (2012-01-16)
------------------

This release requires no database changes. Contains a fix for localization
support for IE7, and lots of aesthetical changes, besides upgraded libraries
(jQuery 1.7.1, qTip2 and iCalcreator). Session cookies are now smaller and
contain only session identifiers.

* You have to create a new ``caldav.php`` config file using the provided template (``caldav.php.template``) because :confval:`caldav_url` has been split into two new configuration parameters. Some CalDAV servers use different URLs for principals and calendar collections/resources, and the previous model didn't work with them:

 * :confval:`caldav_principal_url` will be used to generate principal URLs
 * :confval:`caldav_calendar_url` will be used to generate calendar and resource URLs

* A new :confval:`enable_calendar_sharing` setting has been added to ``caldav.php`` to disable calendar sharing for those servers that don't support WebDAV ACLs

* ``advanced.php`` configuration file has been updated, make sure you don't overwrite your own changes.

* Note that required PHP version is 5.3.0 and not 5.2, there was an error on the requisites list.

* A setup test script has been included to check if your system meets some basic requisites. Follow the :doc:`admin/troubleshooting` section for more details.

.. _v1.2.3:

1.2.3 (2011-11-08)
------------------

This release fixes some bugs with am/pm indicators under some circumstances,
editing recurring events, include_path problems and other bugs.

It also introduces dynamic text color calculation and new default calendar
colors, so it's recommended to remove the following settings from
``config.php``:

* :confval:`default_calendar_color`: now the first color from :confval:`calendar_colors` is used
* :confval:`additional_calendar_colors`: this option has been replaced by :confval:`calendar_colors` 

Copy :confval:`calendar_colors` definition from ``config.php.template`` to your ``config.php`` 

This release requires no database upgrades.

.. _v1.2.2:

1.2.2 (2011-10-25)
------------------

This release fixes important issues with recurrent events. It also fixes
the am/pm indicator on times.

It requires no database upgrades.

.. _v1.2.1:

1.2.1 (2011-10-24)
------------------

This release mainly fixes issues with timezone differences and Daylight Save
Time problems.

Also updates iCalcreator library to latest version (2.10.15) and qTip2.

It requires no database upgrades.


.. _v1.2:

1.2 (2011-10-17)
----------------

* DB schema needs to be altered. UTF8 wasn't being used by default, and
  sessions table wasn't using InnoDB. Apply the changes on
  ``sql/changes/1.1.1_to_1.2.mysql``, which are the following::

        ALTER DATABASE agendav CHARACTER SET utf8 COLLATE utf8_general_ci;
        ALTER TABLE sessions CONVERT TO CHARACTER SET utf8;
        ALTER TABLE sessions ENGINE InnoDB;
        ALTER TABLE shared CONVERT TO CHARACTER SET utf8;

* Main configuration file (``config.php``) has been completely **rewritten**
  to make it easier to write. Please, use the provided ``config.php.template``
  as the base for a new ``config.php``

* Interface translation and timezone configuration is now possible in
  AgenDAV. Please, make sure you set correct values on ``config.php``

* AgenDAV has lots of corrections and fixes. See the ``CHANGELOG``

.. _v1.1.1:

1.1.1 (2011-09-24)
------------------

* Fix DB schema. Wasn't properlty updated on sql/schema.sql, which
  caused a problem with sessions

  To correct this issue without completely recreating your current database,
  run the following two queries::

        CREATE INDEX last_activity_idx ON sessions(last_activity);
        ALTER TABLE sessions MODIFY user_agent VARCHAR(120); 
   
* Remove LDAP dependency. AgenDAV now authenticates against CalDAV
  server.

  Before this change, AgenDAV authenticated users at first using LDAP, and
  then your CalDAV server had to authenticate them again. With this change,
  AgenDAV completely relies on your CalDAV server.
