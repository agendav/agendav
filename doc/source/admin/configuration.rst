Configuration
=============

Configuring AgenDAV requires modifying some PHP text files located in the
``web/config/`` directory.

The following files are usually found as ``filename.php.template``, so make
a copy of them with the correct file name to make them work.

General configuration (config.php)
----------------------------------

``config.php`` file specifies general options about AgenDAV environment.
Please, do not change variables which don't appear below unles you really
know what you are doing.

.. confval:: base_url

   Specify here your full public URL to access AgenDAV, adding a trailing
   slash. Example::

    $config['base_url'] = 'https://agendav.host/';

.. confval:: show_in_log

   Array of logging levels which will appear in logs. Possible logging
   levels are:

   * ``ERROR``
   * ``INFO``
   * ``AUTHERR``
   * ``AUTHOK``
   * ``INTERNALS``

   Example::

    $config['show_in_log']= array('ERROR','INFO','AUTHERR', 'AUTHOK');

.. confval:: log_path

   Full path where logs will be created. Example::

    $config['log_path'] = '';

.. confval:: encryption_key

   Random string which will be used to encrypt some cookie values.

.. confval:: cookie_domain

   Domain the cookie will be defined for. Use ``.domain.tld`` or
   ``full.host.domain.tld``, depending on what you want.

.. confval:: cookie_path

   Path the cookie will be defined for.

.. confval:: cookie_secure

   Create cookies only for use in https environments. Set it TRUE if your
   users access AgenDAV via https.

.. confval:: LC_ALL

   Really not a configuration variable, but a locale setting. Look for a
   ``setlocale`` directive and set it to the desired locale.

.. confval:: site_title

   Title of every page

.. confval:: logo

   Image filename which will be used as a logo. Has to be a valid filename
   placed inside ``web/public/img/`` directory.

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

.. confval:: default_calendar_color

   Default background and foreground colors for calendars. Has to be
   specified as an associative array. Example::

    // Default background color: #B5C7EB
    // Default foreground (text) color: #000000
    $config['default_calendar_color'] = array('B5C7EB' => '000000');

.. confval:: additional_calendar_colors

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

Database configuration (database.php)
-------------------------------------

``database.php`` contains how to connect to your MySQL database. Just follow
inline comments, it's pretty straight forward.

LDAP configuration (ldap.php)
-----------------------------

``ldap.php`` specifies how to connect to a LDAP server to authenticate users
before they enter the application. At this moment, AgenDAV authenticates
users against LDAP and lets them enter only if succeeded. Will disappear in
future releases because it's an unnecessary double check.

.. confval:: ldap_host

   Host to connect

.. confval:: ldap_port

   Port on which LDAP is being served at ``ldap_host``

.. confval:: ldap_admin_dn

   DN to be used to bind on LDAP which has search privileges.

.. confval:: ldap_admin_passwd

   Password used to bind with DN specified in ``ldap_admin_dn``.

.. confval:: ldap_base_dn

   Base DN to search for users

.. confval:: ldap_id_attr

   Attribute on LDAP that contains usernames.

.. confval:: ldap_search_filter

   Filter pattern used to search for users. The placeholder ``%u`` will be
   replaced by current username trying to log in.

   Example::

    $config['ldap_search_filter'] = '(&(uid=%u)(inetUserStatus=Active))';

   If user ``user1`` is trying to log in, will become::

    (&(uid=user1)(inetUserStatus=Active))

CalDAV specific options (caldav.php)
------------------------------------

Here you will configure every single aspect of your CalDAV server.

.. confval:: caldav_url

   The _internal_ URL used by AgenDAV to connect to your CalDAV server. The
   placeholder ``%u`` will be replaced by current user.

   Needs a trailing slash.

   Example::

    $config['caldav_url'] = 'http://10.0.0.12/caldav.php/%u/';
   
   For user4 Will become:

    http://10.0.12/caldav.php/user4/

.. confval:: public_caldav_url

   The URL that will be shown to users if :confval:`show_public_caldav_url` is
   enabled. It's not used for internal connections. Placeholder ``%s`` will
   be replaced by current username and calendar.
   
   Needs a trailing slash.

   Example::

    $config['public_caldav_url'] = 'https://public.caldav.tld/caldav.php/%s/';

   Will become for user ``user2`` and calendar ``myhomecalendar``:

    https://public.caldav.tld/caldav.php/user2/myhomecalendar/

.. confval:: owner_permissions

   List of DAV permissions used for the calendar owner when sharing a
   calendar. As DAV ACLs are used, when editing a calendar sharing options a
   full ACL has to be built with the following structure:

   * Permissions given to the owner (this option)
   * Permissions given to granted users (:confval:`share_permissions`)
   * Permissions given to other users (:confval:`default_permissions`)

   Please, refer to your CalDAV server documentation to know which
   permissions does it support.

   For DAViCal you can follow `Permissions page on DAViCal wiki
   <http://wiki.davical.org/w/Permissions>`_. Default values of this option
   will work all right for DAViCal.

   .. seealso:: Used in conjunction with options :confval:`share_permissions`
      and :confval:`default_permissions`.

.. confval:: share_permissions

   List of DAV permissions used for granted users when another user shares a
   calendar with them.

   Please, refer to your CalDAV server documentation to know which
   permissions does it support.

   Default value lets users to read and write on shared calendars. AgenDAV
   doesn't support at this moment to select whether you want read or shared
   rights.

   .. seealso:: Used in conjunction with options :confval:`owner_permissions`
      and :confval:`default_permissions`.


.. confval:: default_permissions

   List of DAV permissions used for users which are not owner neither
   granted users when some user shares a calendar with other ones.

   Please, refer to your CalDAV server documentation to know which
   permissions does it support.

   Default value lets users just to make free/busy queries in DAViCal.

   .. seealso:: Used in conjunction with options :confval:`owner_permissions`
      and :confval:`share_permissions`.
