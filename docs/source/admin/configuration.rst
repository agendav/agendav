.. _configuration:

Configuration
=============

Configuring AgenDAV requires creating a ``settings.php`` file in the
``config/`` directory.

The quickest way to get started is to copy the provided template::

  $ cp config/settings.template.php config/settings.php

The template contains the most common settings with sensible defaults and
inline comments. You only need to set ``csrf.secret``, ``db.options``, and
``caldav.baseurl`` to get a working installation.

All available settings with full documentation are listed in
``default.settings.php``. Do not modify ``default.settings.php`` - it is
overwritten on upgrades.

.. confval:: site.title

   Title of every page

.. confval:: site.logo

   Image filename which will be used as a logo. Has to be a valid filename
   placed inside ``public/img/`` directory.

.. confval:: site.favicon

   Icon filename which will be used as favicon. Has to be placed inside
   ``public/img/``.

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

   .. warning::

      When using an SQLite database, note that there is a `bug when using URL
      based configurations <http://www.doctrine-project.org/jira/browse/DBAL-1164>`_. Use
      the alternative syntax (`path` and `driver`) instead.

.. confval:: csrf.secret

   Per-installation secret used by the CSRF token manager. **Required**: AgenDAV
   refuses to start if this is missing or empty. Generate a value with::

      php -r 'echo bin2hex(random_bytes(32));'

   and set it in ``settings.php``. Treat it like any other secret — do not
   commit it, do not share it, and rotate it if you suspect a leak (rotation
   invalidates outstanding sessions and CSRF tokens).

.. confval:: log.level

   Level of logging. Can be one from DEBUG, INFO, WARNING, ERROR.

   Default setting::

    $app['log.level'] = 'INFO';

.. confval:: log.path

   Directory where log files are created. Add a trailing slash. Example::

    $app['log.path'] = '/var/log/agendav/';

   Make sure the user that runs your web server has write permission on that
   directory.

.. confval:: log.file

   Full path to the log file. When left empty (the default), AgenDAV generates
   a dated file inside ``log.path`` (e.g. ``2024-01-15.log``).

   Set to ``php://stdout`` to write logs to standard output instead of a file,
   which is the recommended approach for container and Docker deployments::

    $app['log.file'] = 'php://stdout';

   When set to a file path, make sure the web server user has write permission
   on that file.

.. confval:: caldav.baseurl

   Base CalDAV URL used to build all CalDAV URLs.

   If you want to pass the username you enter on login into AgenDAV,
   then add a '%u' placeholder to your baseurl. It will be replaced dynamically.

   Examples::

    // SabreDAV
    $app['caldav.baseurl'] = 'http://caldav.server/cal.php';

    // DAViCal
    $app['caldav.baseurl'] = 'http://caldav.server/caldav.php';

    // Radicale
    $app['caldav.baseurl'] = 'http://caldav.server/%u';


   .. note::
      If you are configuring AgenDAV to connect to a CalDAV server using HTTPS,
      certificate validation will be performed. Both CA and hostname will be verified. If you are
      having trouble with your certificate, make sure you have your CA recognized by your system.
      See `OpenSSL changes in PHP 5.6.x <http://php.net/manual/en/migration56.openssl.php>`_ for
      more details.


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

.. confval:: caldav.connect.timeout

   Timeout in seconds for CalDAV connections. A value of 0 means to wait indefinitely, which is the
   default behaviour.

   Eample::

    // Timeout after 5 seconds if connection to the CalDAV server is not ready
    $app['caldav.connect.timeout'] = 5;

.. confval:: caldav.response.timeout

   Timeout in seconds while waiting for responses after a CalDAV request is sent. A value of 0 means
   to wait indefinitely, which is the default behaviour.

   Example::

    // Timeout after 10 seconds if server hasn't answered
    $app['caldav.response.timeout'] = 10;

.. confval:: caldav.certificate.verify

   Whether to verify the SSL certificate using available CA bundles. Defaults to yes, which is
   recommended, but can be disabled if the CalDAV server is using a self-signed certificate or a
   certificate issued by a non-trusted CA.

   Example::

    // Do not verify SSL certificate, it is self signed
    $app['caldav.certificate.verify'] = false;

.. confval:: calendar.sharing

   .. deprecated::

      This feature is only known to work with DAViCal. On all other CalDAV
      servers (Nextcloud, Baikal, Radicale, etc.) use server-side delegation
      instead - see :ref:`delegations`.

   Enables AgenDAV-managed WebDAV ACL sharing between users. Requires full
   WebDAV ACL support on the CalDAV server.

.. confval:: calendar.subscriptions

   Allows users to subscribe to external iCal feeds. Disabled by default
   because it causes the server to fetch arbitrary external URLs on behalf
   of users. Set to ``true`` to enable the subscriptions box in the sidebar.

.. confval:: calendar.sharing.permissions

   .. deprecated::

      Only relevant when ``calendar.sharing`` is enabled. See deprecation
      note on that setting.

   Configures ACL permissions for calendar sharing. The default values will
   work with DAViCal.

.. confval:: calendar.start_hidden

   Hides all calendars when the page loads, so no events are fetched until
   the user enables a calendar in the sidebar. Useful for accounts with many
   calendars, to reduce the load on the CalDAV server. Disabled by default.

.. confval:: calendar.read_only

   Makes the whole instance read-only for every session, regardless of the
   login method: disables creating, editing and deleting events and
   calendars, and hides the preferences page. Useful for a team calendar
   where users can see their own calendars but not change them.

.. confval:: calendar.kiosk_mode

   Kiosk mode for an unattended wall screen: hides the navbar, the sidebar
   and the calendar controls, disables clicks on events and reloads the
   page every night shortly after midnight. Always enables
   :confval:`calendar.read_only` automatically.

   Recommended setup: a dedicated AgenDAV instance with
   :confval:`autologin.username` and :confval:`autologin.password`, using
   a low-privilege CalDAV account that only has read access to the calendars to show,
   since all calendars of this account are shown. Set the default view and other
   preferences with the ``defaults.*`` settings, e.g.
   :confval:`defaults.default_view`.
   Fullscreen view is handled by the browser, e.g. ``chromium --kiosk``.

.. confval:: autologin.username

   Username for the automatic login. Together with
   :confval:`autologin.password`, AgenDAV logs in this account without
   showing the login page, and hides the logout button. Leave both empty
   to disable the autologin feature. Use a dedicated, low-privilege CalDAV account.

.. confval:: autologin.password

   Password for :confval:`autologin.username`.

.. confval:: defaults.timezone

   Timezone to be used by default.

   Make sure you use a valid timezone from http://php.net/timezones

.. confval:: defaults.language

   Default language to be used on AgenDAV interface.

   Have a look at directory ``resources/private/lang/`` for a list of available languages.

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

.. confval:: defaults.show_week_nb

   Whether or not to show week numbers in views by default

   Specify it as a boolean ("true" or "false").

.. confval:: defaults.show_now_indicator

   Whether or not to show a marker indicating current time

   Specify it as a boolean ("true" or "false").

.. confval:: defaults.list_days

   Default number of days covered by the "list" (agenda) view. Allowed values: 7, 14 or 31

   Specify it as an integer.

.. confval:: defaults.default_view

   Default calendar view when accessing AgenDAV. Allowed values: month, week, day and list

   Specify it as a string.

.. confval:: logout.redirection

   When logging out from AgenDAV, the URL the user will be redirected to.

   Can be left empty to redirect user to login page again.


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
