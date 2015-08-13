.. _configuration:

Configuration
=============

Configuring AgenDAV requires creating a ``settings.php`` file in the
``web/config/`` directory.

You will find all available settings inside the file ``prod.php``. Please, do not
modify ``prod.php``, as any changes will be lost if you upgrade AgenDAV.

Save a copy of the ``prod.php`` file as ``settings.php`` and start configuring
your installation.

.. confval:: site.title

   Title of every page

.. confval:: site.logo

   Image filename which will be used as a logo. Has to be a valid filename
   placed inside ``web/public/img/`` directory.

.. confval:: site.footer

   Text to be placed in the footer.

.. confval:: proxies

   Array of IPs of trusted proxies, on which the HTTP_X_FORWARDED_FOR header will be honored.

.. confval:: db.options

   Database connection parameters. Uses Doctrine DBAL syntax, so follow the guide at
   http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html
   for a better reference. Example::

        $app['db.options'] = [
            'dbname' => 'agendav',
            'user' => 'user',
            'password' => 'password',
            'host' => 'localhost',
            'driver' => 'pdo_mysql',
         ];

.. confval:: encryption.key

   Key that will be used to encrypt passwords when storing them on the database, so
   use a long one.

.. confval:: csrf.secret

   Name that the CSRF parameter will have.

.. confval:: log.path

   Full path where logs will be created. Add a trailing slash. Example::

    $app['log.path'] = '/var/log/agendav/';

   Make sure the user that runs your web server has write permission on that
   directory.

.. confval:: caldav.baseurl

   Base CalDAV URL used to build all CalDAV URLs.

   Examples::

    // SabreDAV
    $app['caldav_base_url'] = 'http://caldav.server/cal.php';

    // DAViCal
    $app['caldav_base_url'] = 'http://caldav.server/caldav.php';

.. confval:: caldav.authmethod

   You have to specify which HTTP authentication method does your CalDAV server
   require. Both basic and digest authentication methods are supported.

   Example::

    // SabreDAV
    $app['caldav.authmethod'] = 'digest';

    // DAViCal
    $app['caldav.authmethod'] = 'basic';

.. confval:: caldav.publicurls

   Whether to show CalDAV URL links or not in the edit dialog

   .. seealso:: :confval:`caldav.baseurl.public`

.. confval:: caldav.baseurl.public

   The base URL that will be shown to users if :confval:`caldav.publicurls` is
   enabled. It's not used for internal connections.

   Do not add a trailing slash.

   Example::

    $app['caldav.baseurl.public'] = 'https://public.caldav.tld';

.. confval:: calendar.sharing

   Enables calendar sharing between users

   Note that calendar sharing requires full WebDAV ACL support on your
   CalDAV server. Some servers, such as SabreDAV (at least on current
   release, 1.5.6), don't have full ACL support, so you should set this option
   to ``false`` if your server can't handle ACLs.

.. confval:: defaults.timezone

   Timezone to be used by default.

   Make sure you use a valid timezone from http://php.net/timezones

.. confval:: defaults.language

   Default language to be used on AgenDAV interface.

   Have a look at directory ``web/lang`` for a list of available languages.

.. confval:: defaults.time_format

   Preferred time format: 12 hours (e.g. 3pm / 2:30am) or 24 hours
   (e.g. 15:00 / 2:30).

   Set this option using a **string** (``'12'`` or ``'24'``).

.. confval:: defaults.date_format

   Default date format.

   Available options are:

   * ``ymd``: e.g. 2011/10/22
   * ``dmy``: e.g. 22/10/2011
   * ``mdy``: e.g. 10/22/2011

.. confval:: defaults.weekstart

   Which day should be considered the default first day of week.

   0 means Sunday, 1 means Monday

   Specify it as a number.

.. confval:: logout.redirection

   When logging out from AgenDAV, the URL the user will be redirected to.

   Can be left empty to redirect user to login page again.

.. confval:: owner_permissions

   List of DAV permissions used for the calendar owner when sharing a
   calendar. As DAV ACLs are used, when editing a calendar sharing options a
   full ACL has to be built with the following structure:

   * Permissions given to the owner (this option)
   * Permissions given to users with read-only profile (:confval:`read_profile_permissions`)
   * Permissions given to users with read and write profile (:confval:`read_write_profile_permissions`)
   * Permissions given to the rest of users (:confval:`default_permissions`)

   Please, refer to your CalDAV server documentation to know which
   permissions does it support.

   For DAViCal you can follow `Permissions page on DAViCal wiki
   <http://wiki.davical.org/w/Permissions>`_. Default values of this option
   will work all right for DAViCal.

.. confval:: read_profile_permissions

   List of DAV permissions used for users given read-only permission on a
   calendar.

   .. versionadded:: 1.2.5

.. confval:: read_write_profile_permissions

   List of DAV permissions used for users given read and write permission on
   a calendar.

   .. versionadded:: 1.2.5

.. confval:: default_permissions

   List of DAV permissions used for users which are not owner neither
   granted users when some user shares a calendar with other ones.

   Please, refer to your CalDAV server documentation to know which
   permissions does it support.

   Default value lets users just to make free/busy queries in DAViCal.

.. confval:: share_permissions

   .. deprecated:: 1.2.5

   .. seealso:: See :confval:`read_profile_permissions` and
      :confval:`read_write_profile_permissions`


Sessions
--------

AgenDAV uses `php.ini session settings <http://php.net/session.configuration>`_. You can override
most of them inside `settings.php` by using the `session.storage.options`. Just copy it from
`prod.php` and set any parameters you wish.

The following example makes sessions expire after 20 minutes of closing your browser in a low
traffic instance::

    $app['session.storage.options'] = [
       'name' => 'agendav_sess',
       'cookie_lifetime' => 0,
       // Every request has 10% chance of triggering session GC
       'gc_probability' => 1,
       'gc_divisor' => 10,
       'gc_maxlifetime' => 1200,
       'lifetime' => 1200,
    ];
