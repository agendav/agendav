.. _upgrading:

Upgrading
=========

General upgrade procedure
--------------------------

Follow these steps for every upgrade, regardless of version. Version-specific
steps (if any) are listed in the sections below - check those before starting.

1. **Back up your data**

   Back up the ``config/`` directory and take a dump of your database. Do not
   continue without both backups.

2. **Download the new release**

   Download the new release from `GitHub <https://github.com/agendav/agendav/releases>`_
   and extract it next to your current installation::

     $ tar xf agendav-X.Y.Z.tar.gz
     $ cd agendav-X.Y.Z/

3. **Restore your configuration**

   Copy your existing settings file into the new directory::

     $ cp /path/to/old_agendav/config/settings.php config/settings.php

4. **Install dependencies**

   ::

     $ composer install --no-dev

5. **Fix directory permissions**

   ::

     # chown -R www-data:www-data .
     # chmod -R 750 var/

6. **Run database migrations**

   ::

     $ php bin/agendavcli migrations:migrate

7. **Clear caches and sessions**

   ::

     $ php bin/agendavcli cache:purge

   To clear only caches without ending active sessions::

     $ php bin/agendavcli cache:clear

8. **Review new settings**

   Check the :doc:`configuration` section and ``config/default.settings.php``
   for any new optional settings introduced in the release (new features,
   new CalDAV options, etc.).


Version-specific steps
-----------------------

Upgrading from 3.3.x
*********************

**Custom logo and dark color scheme**

Users may now choose a dark color scheme. In this case AgenDAV shows
:confval:`site.logo_dark` instead of :confval:`site.logo`. It defaults to a
dark variant of the AgenDAV logo. If you use a custom logo,
set a matching dark logo, or use your regular logo in both color schemes::

  'site.logo_dark' => '',

Upgrading from 2.x to 3.x
**************************

3.0 contains several breaking changes that require manual steps in addition
to the general procedure above.

**PHP version**

PHP 8.5 or later is now required. Verify your server version before upgrading::

  $ php --version

**Web server document root**

The ``web/`` subdirectory was removed. Change your web server document root
from ``web/public/`` to ``public/``. See :ref:`webserver` for updated
configuration examples.

**Configuration file location**

Move your settings file from the old path to the new one::

  $ mv web/config/settings.php config/settings.php

You only need to store settings which differ from defaults in ``config/default.settings.php``.
You may copy and adapt the ``config/settings.template.php`` file instead of moving your old settings file if you prefer.

**Database connection configuration**

The ``url`` shorthand in ``db.options`` is no longer supported. Replace it
with explicit keys::

  'db.options' => [
      'driver'   => 'pdo_mysql',
      'host'     => 'localhost',
      'dbname'   => 'agendav',
      'user'     => 'agendav',
      'password' => 'secret',
  ],

See ``config/default.settings.php`` for all available options.

**Clear the var/ directory**

The internal cache format changed. Delete the contents of ``var/`` before
starting the new version::

  $ rm -rf var/*


Upgrading from 1.x to 2.x
**************************

AgenDAV 2.0 was a complete rewrite. The configuration format changed
entirely - you will need to create a new ``config/settings.php`` from
scratch using ``config/settings.template.php`` as a starting point.

Make sure you are running the latest 1.x release before upgrading.

**Calendar shares migration**

If you were using calendar sharing, the ``shares`` table requires a manual
data migration after running the database migrations. The queries below assume
a DAViCal server - adjust the URL prefix for other CalDAV servers.

MySQL::

    UPDATE `shares`
    SET owner    = CONCAT('/caldav.php/', owner, '/'),
        calendar = CONCAT(owner, calendar, '/'),
        `with`   = CONCAT('/caldav.php/', `with`, '/');

    UPDATE `shares` SET options = 'a:0:{}' WHERE options = '';

PostgreSQL::

    UPDATE shares
    SET owner    = '/caldav.php/' || owner || '/',
        calendar = '/caldav.php/' || owner || '/' || calendar || '/',
        "with"   = '/caldav.php/' || "with" || '/';

    UPDATE shares SET options = 'a:0:{}' WHERE options = '';
