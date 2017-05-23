.. _releasenotes:

Release notes
=============

.. _v2.2.0:
2.2.0
-----

* PHP 5.6 or greater is required.
* Silex 2.0 is now being used, so any modifications prepared for AgenDAV 2.1.x (which was based
  on Silex 1.x) will probably be broken.

.. _v2.0.0:

2.0.0 (2016-11-19)
------------------------

Relevant release notes from all 2.0.0 release candidates and betas:

* Note that AgenDAV now requires PHP 5.5.9 or greater.
* AgenDAV needs `Composer <http://getcomposer.org>`_. If you are using the
  tarball from the website you don't have to worry at all. Otherwise, you'll
  have to install it and fetch AgenDAV dependencies. You'll find instructions on
  :ref:`download_dependencies`.
* AgenDAV now uses a single ``settings.php`` file. Details on this file are provided on
  :ref:`configuration`.
* Deep database changes are required. Instructions are provided on :ref:`dbupgrade`
* If you want to upgrade from AgenDAV 1.2.6.2 and you use calendar sharing, note that running an SQL
  statement is required to complete the upgrade.  More details can be found in the
  :doc:`admin/upgrading` section.
* Read the `CHANGELOG
  <https://github.com/adobo/agendav/blob/develop/CHANGELOG.md>`_ for a detailed list of changes.

.. _v2.0.0-rc2:

2.0.0-rc2 (2016-11-05)
------------------------

* This release includes updated translations. They were missing on rc1


.. _v2.0.0-rc1:

2.0.0-rc1 (2016-11-05)
------------------------

* This release candidate handles shared calendars again. If you want to upgrade from AgenDAV 1.2.6.2 and
  you use calendar sharing, note that running an SQL statement is required to complete the upgrade.
  More details can be found in the :doc:`admin/upgrading` section.
* Read the `CHANGELOG
  <https://github.com/adobo/agendav/blob/develop/CHANGELOG.md>`_ for a detailed list of changes.

.. _v2.0.0-beta2:

2.0.0-beta2 (2016-04-20)
------------------------

* Note that AgenDAV now requires PHP 5.5.0 or greater.
* **This beta release cannot handle shared calendars yet**, and your existing shares will
  be removed from the database. Please, make sure you are not upgrading a production
  environment which uses calendar sharing.
* Read the `CHANGELOG
  <https://github.com/adobo/agendav/blob/develop/CHANGELOG.md>`_ for a detailed list of changes.

.. _v2.0.0-beta1:

2.0.0-beta1 (2015-08-26)
------------------------

* AgenDAV needs `Composer <http://getcomposer.org>`_. If you are using the
  tarball from the website you don't have to worry at all. Otherwise, you'll
  have to install it and fetch AgenDAV dependencies. You'll find instructions on
  :ref:`download_dependencies`.
* AgenDAV now uses a single ``settings.php`` file. Details on this file are provided on
  :ref:`configuration`.
* Deep database changes are required. Instructions are provided on :ref:`dbupgrade`
* **This beta release cannot handle shared calendars**, and your existing shares will
  be removed from the database. Please, make sure you are not upgrading a production
  environment which uses calendar sharing.
* Read the `CHANGELOG
  <https://github.com/adobo/agendav/blob/develop/CHANGELOG.md>`_ for a detailed list of changes.

.. _v1.2.6.2:

1.2.6.1 and 1.2.6.2 (2012-10-15)
--------------------------------

This release just fixes a problem with some timezones which have three
components, such as America/Argentina/Cordoba. AgenDAV 1.2.6 refused to parse calendars
that contained events with those kind of timezones.

.. _v1.2.6:


1.2.6 (2012-09-03)
------------------

* This release requires neither configuration changes nor DB schema updates.
* Reminders feature has been added. Reminders will be attached to events, but this version of AgenDAV is not capable of showing popups for them yet. Other CalDAV clients, such as smartphones and desktop applications, will understand them.
* A new ``log_create_permissions`` option has been added to ``advanced.php`` configuration file. Default is ``0640``

.. _v1.2.5.1:

1.2.5.1 (2012-06-11)
--------------------

.. note::

   This version has a bug that will make :ref:`dbupgrade` to fail if you
   have AgenDAV configured to use a language other than English. Please, set
   :confval:`default_language` to ``en`` before running ``agendav
   dbupdate``

* This release fixes some invalid SQL queries included in the database update process


.. _v1.2.5:

1.2.5 (2012-06-07)
------------------

* DB schema needs to be updated, but a new database upgrade process is now available. No more ``*.sql`` files, just read :ref:`dbupgrade` section.

* This release requires PHP cURL support. Make sure your PHP installation
  has the cURL module enabled

* Now you can share a calendar in read-only mode. You're advised to create a
  new ``caldav.php`` configuration file using the provided template

* Date and time format options have been moved inside ``config.php``. Prior
  to this release they were set inside lang files, which didn't make much
  sense. Have a look at new options :confval:`format_full_date`,
  :confval:`format_column_month`, :confval:`format_column_week`,
  :confval:`format_column_day`, :confval:`format_column_table`,
  :confval:`format_title_month`, :confval:`format_title_week`,
  :confval:`format_title_day` and :confval:`format_title_table`

* Translations are now managed using `Transifex <https://www.transifex.net/projects/p/agendav/>`_. Note that default language has been renamed from ``en_US`` to ``en``, as I think it's a neutral English

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
