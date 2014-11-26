Configuration
=============

Configuring AgenDAV requires modifying some PHP text files located in the
``web/config/`` directory.

The following files are usually found as ``filename.php.template``, so make
a copy of them with the correct file name to make them work.

.. note::

   ``ldap.php`` was removed in AgenDAV 1.1.1

General configuration (config.php)
----------------------------------

``config.php`` file specifies general options about AgenDAV environment. It
loads a set of default option values from ``defaults.php``, but it is
recommended to configure all of the following variables.

Please, do not modify ``defaults.php``, as it is a file that updates on
every AgenDAV upgrade to avoid problems if you forget any configuration
setting.

.. confval:: base_url

   Specify here your full public URL to access AgenDAV, adding a trailing
   slash. Example::

    $config['base_url'] = 'https://agendav.host/';

.. confval:: enable_debug

   Enables debug logs for AgenDAV.

   Debug log will be written at :confval:`log_path` on a single file called ``debug.log``. Example::

     $config['enable_debug'] = FALSE;

   .. versionadded:: 1.3.0

.. confval:: show_in_log

   .. deprecated:: 1.3.0

   .. seealso:: :confval:`enable_debug`

.. confval:: log_path

   Full path where logs will be created. Add a trailing slash. Example::

    $config['log_path'] = '/var/log/agendav/';

   Make sure the user that runs your web server has write permission on that
   directory.

.. confval:: encryption_key

   Random string which will be used to encrypt some cookie values.

.. confval:: cookie_prefix

   Prefix that should be prepended to your cookie names. Useful if you have
   several sites hosted on the same hostname and you want to avoid name
   collisions

.. confval:: cookie_domain

   Domain the cookie will be defined for. Use ``.domain.tld`` or
   ``full.host.domain.tld``, depending on what you want.

.. confval:: cookie_path

   Path the cookie will be defined for.

.. confval:: cookie_secure

   Create cookies only for use in https environments. Set it TRUE if your
   users access AgenDAV via https.

.. confval:: proxy_ips

   Comma delimited IPs of your proxies, which will make CodeIgniter
   framework to trust the HTTP_X_FORWARDED_FOR header. Leave it blank if
   your AgenDAV installation isn't being accessed via HTTP proxy.

.. confval:: site_title

   Title of every page

.. confval:: logo

   Image filename which will be used as a logo. Has to be a valid filename
   placed inside ``web/public/img/`` directory.

.. confval:: login_page_logo

   Image filename which will be used as a logo only for login page. It's
   usually bigger than the normal logo. Has to be a valid filename placed
   inside ``web/public/img/`` directory.

   .. versionadded:: 1.2.6

.. confval:: footer

   Text to be placed in the footer.

.. confval:: logout_redirect_to

   When logging out from AgenDAV, the URL the user will be redirected to.

   Can be left empty to redirect user to login page again.

.. confval:: additional_js

   Array of additional JavaScript files which you will be loading on every
   page. They have to be placed inside ``web/public/js``

.. confval:: show_public_caldav_url

   Whether to show CalDAV URL links or not in the edit dialog

   .. seealso:: :confval:`public_caldav_url`

.. confval:: default_language

   Language to be used in AgenDAV interface.

   Have a look at directory ``web/lang`` for a list of available languages.

   Note that the value given to this setting will be used as application
   locale with ``setlocale()``.

   .. versionadded:: 1.2

.. confval:: default_time_format

   Preferred time format: 12 hours (e.g. 3pm / 2:30am) or 24 hours
   (e.g. 15:00 / 2:30).

   Set this option using a **string** (``'12'`` or ``'24'``).

   .. versionadded:: 1.2

.. confval:: default_date_format

   Preferred date format to be used inside date fields (only in forms).
   Possible values are:

   * ``ymd``: e.g. 2011/10/22
   * ``dmy``: e.g. 22/10/2011
   * ``mdy``: e.g. 10/22/2011

   .. versionadded:: 1.2

.. confval:: format_full_date

   .. deprecated:: 1.3.0

.. confval:: format_column_month

   .. deprecated:: 1.3.0

.. confval:: format_column_week

   .. deprecated:: 1.3.0

.. confval:: format_column_day

   .. deprecated:: 1.3.0

.. confval:: format_column_table

   .. deprecated:: 1.3.0

.. confval:: format_title_month

   .. deprecated:: 1.3.0

.. confval:: format_title_week

   .. deprecated:: 1.3.0

.. confval:: format_title_day

   .. deprecated:: 1.3.0

.. confval:: format_title_table

   .. deprecated:: 1.3.0

.. confval:: default_first_day
   
   Which day should be considered the first of the week. Starting with 0
   (Sunday), 1 means Monday and so on.

   Use a numerical value, not an integer.

   .. versionadded:: 1.2

.. confval:: default_timezone

   Timezone to be used internally. Will be used for recalculating other
   timezone dates and hours to be sent to the browser, ignoring browser
   configured timezone.

   Make sure you use a valid timezone from http://php.net/timezones

   .. versionadded:: 1.2

.. confval:: default_calendar_color

   .. deprecated:: 1.2.3

   Default background and foreground colors for calendars. Has to be
   specified as an associative array. Example::

    // Default background color: #B5C7EB
    // Default foreground (text) color: #000000
    $config['default_calendar_color'] = array('B5C7EB' => '000000');

.. confval:: additional_calendar_colors

   .. deprecated:: 1.2.3

   List of selectable background and foreground color combinations. Specify
   them as an associative array. Example::

        // background color => foreground color
        $config['additional_calendar_colors'] = array(
                'FAC5C0' => '000000',
                'B7E3C0' => '000000',
                'CAB2FC' => '000000',
                'F8F087' => '000000',
                'E6D5C1' => '000000',
                'FFC48C' => '000000',
                'DAF5FF' => '000000',
                'C4C4BC' => '000000',
        );

.. confval:: calendar_colors

   List of selectable background colors. Foreground color will be 
   automatically calculated depending on the darkness of the color. Specify
   them as an array. Example::

        $config['calendar_colors'] = array(
		'9CC4E4',
		'3A89C9',
		'107FC9',
		'FAC5C0',
		'FF4E50',
		'BD3737',
		'C9DF8A',
		'77AB59',
		'36802D',
		'F8F087',
		'E6D5C1',
		'3E4147',
        );

.. confval:: db

   Database connection parameters. Uses Doctrine DBAL syntax, so follow the guide at
   http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html
   for a better reference. Example::

        $config['db'] = $config['db'] = array(
            'dbname' => 'agendav',
            'user' => 'user',
            'password' => 'password',
            'host' => 'localhost',
            'driver' => 'pdo_mysql',
         );




.. _caldavphpconf:

CalDAV specific options (caldav.php)
------------------------------------

Here you will configure every single aspect of your CalDAV server.


.. confval:: caldav_base_url

   Base CalDAV URL used to build all CalDAV URLs. If your CalDAV server is located under a relative path don't specify
   it here. Do not add trailing slash.

   Examples::

    // This will work for CalDAV servers placed on root URL or under any relative path
    $config['caldav_base_url'] = 'http://my.caldav.server';

   .. versionadded:: 1.3.0

.. confval:: caldav_http_auth_method

   You have to specify which HTTP authentication method does your CalDAV server
   require. Both basic and digest authentication methods are supported.

   Example::
   
    // SabreDAV
    $config['caldav_http_auth_method'] = 'digest';

    // DAViCal
    $config['caldav_http_auth_method'] = 'basic';

   .. versionchanged:: 2.0.0
      Old `CURL*` values are not supported anymore. Needs ``basic`` or ``digest``.


.. confval:: caldav_principal_template

   Used by AgenDAV to generate a principal URL for your CalDAV server. The
   placeholder ``%u`` will be replaced with a username.

   This is just a path, not a full URL. Your :confval:`caldav_base_url` will be prepended to this value.

   Remember to add the relative path to your CalDAV server if it's not located under your root URL.

   Needs a trailing slash.

   Example::

    // Example 1: DAViCal
    $config['caldav_principal_template'] = '/caldav.php/%u/';

    // Example 2: DAViCal on http://my.caldav.server/davical/...
    $config['caldav_principal_template'] = '/davical/caldav.php/%u/';

    // Example 3: SabreDAV
    $config['caldav_principal_template'] = '/calendarserver.php/principals/%u/';

    // Example 4: Calendar server
    $config['caldav_principal_template'] = '/calendars/users/%u/';

   .. versionadded:: 1.3.0

   .. seealso:: :confval:`caldav_base_url` and :confval:`caldav_calendar_homeset_template`

.. confval:: caldav_calendar_homeset_template

   Used by AgenDAV to search for calendars for current user. ``%u`` will be replaced with a username.

   This is just a path, not a full URL. Your :confval:`caldav_base_url` will be prepended to this value.

   Remember to add the relative path to your CalDAV server if it's not located under your root URL.

   Example::

    // DAViCal
    $config['caldav_calendar_homeset_template'] = '/caldav.php/%u/';

    // DAViCal under /davical
    $config['caldav_calendar_homeset_template'] = '/davical/caldav.php/%u/';
   
   .. versionadded:: 1.3.0

   .. seealso:: :confval:`caldav_base_url` and :confval:`caldav_principal_template`

.. confval:: caldav_public_base_url

   The base URL that will be shown to users if :confval:`show_public_caldav_url` is
   enabled. It's not used for internal connections.

   If your CalDAV server is located under a relative path don't specify it here. Do not add trailing slash.

   Do not add a trailing slash.

   Example::

    $config['public_caldav_url'] = 'https://public.caldav.tld';

   For a DAViCal server placed on /davical will become for user ``user2`` and calendar ``myhomecalendar``:

    https://public.caldav.tld/davical/caldav.php/user2/myhomecalendar/

.. confval:: enable_calendar_sharing

   Enables an option to share calendars between users.
   
   Note that calendar sharing requires full WebDAV ACL support on your
   CalDAV server. Some servers, such as SabreDAV (at least on current
   release, 1.5.6), don't support them, so you should set this option
   to FALSE if your server can't handle ACLs.

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

.. confval:: caldav_url

   .. deprecated:: 1.2.4

   .. seealso:: :confval:`caldav_principal_template` and :confval:`caldav_calendar_homeset_template`

.. confval:: caldav_principal_url

   .. deprecated:: 1.3.0
   .. seealso:: :confval:`caldav_principal_template`


.. confval:: public_caldav_url

   .. deprecated:: 1.3.0

   .. seealso:: :confval:`caldav_public_base_url`

.. confval:: share_permissions

   .. deprecated:: 1.2.5

   .. seealso:: See :confval:`read_profile_permissions` and
      :confval:`read_write_profile_permissions`

.. confval:: caldav_calendar_url

   .. deprecated:: 1.3.0

   .. seealso:: :confval:`caldav_calendar_homeset_template`



Other configuration files
-------------------------

Custom CSS
^^^^^^^^^^

You can place your custom css in web/public/css/custom.css.

Advanced options (advanced.php)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

This file should be kept untouched unless you know what you are trying to
modify. It contains several options that make AgenDAV work by default.
