.. _configuration:

Configuration
=============

Configuring AgenDAV requires creating a ``settings.php`` file in the
``web/config/`` directory.

You will find all available settings inside the file ``default.settings.php``. Please, do not
modify ``default.settings.php``, as any changes will be lost if you upgrade AgenDAV.

Save a copy of the ``default.settings.php`` file as ``settings.php``, or just copy the settings you want to
modify,  and start configuring your instance.

.. confval:: site.title

   Title of every page

.. confval:: site.logo

   Image filename which will be used as a logo. Has to be a valid filename
   placed inside ``web/public/img/`` directory.

.. confval:: site.favicon

   Icon filename which will be used as favicon. Has to be placed inside
   ``web/public/img/``.

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

   Internal CSRF token id.

.. confval:: log.level

   Level of logging. Can be one from DEBUG, INFO, WARNING, ERROR.

   Default setting::

    $app['log.level'] = 'INFO';

.. confval:: log.path

   Full path where logs will be created. Add a trailing slash. Example::

    $app['log.path'] = '/var/log/agendav/';

   Make sure the user that runs your web server has write permission on that
   directory.

.. confval:: caldav.baseurl

   Base CalDAV URL used to build all CalDAV URLs.

   If you want to include the username add '%u' to your baseurl. It will be replaced with the username you enter on login.

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

   Enables calendar sharing between users

   Note that calendar sharing requires full WebDAV ACL support on your
   CalDAV server. Sharing has been fully tested only with DAViCal, so it is
   recommended to disable calendar sharing on other CalDAV servers unless
   you know what you are doing.

.. confval:: calendar.sharing.permissions

   Configures ACL permissions for calendar sharing. The default values will
   work with DAViCal.

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
