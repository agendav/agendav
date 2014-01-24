<?php
// vim: ft=php

/**********************
 * Connection options *
 **********************/

/*
 * HTTP auth method
 * ================
 *
 * Specify which HTTP authentication method does your server need. Most
 * CalDAV servers support Basic Authentication, but you should check your
 * server documentation.
 *
 * It is recommended to use a fixed value for this. If you don't set it,
 * CalDAV client will have to negotiate with server on each request and
 * perfomance will be worse (mainly when Basic auth is used)
 *
 * Possible values can be found at
 * http://www.php.net/manual/es/function.curl-setopt.php (CURLOPT_HTTPAUTH)
 *
 * Examples:
 *  Automatic guess: $config['caldav_http_auth_method'] = null;
 *  SabreDAV: $config['caldav_http_auth_method'] = CURLAUTH_DIGEST;
 *  DAViCal: $config['caldav_http_auth_method'] = CURLAUTH_BASIC;
 */

$config['caldav_http_auth_method'] = CURLAUTH_DIGEST;


/*******************
 * CalDAV URLs     *
 *******************/

/*
 * CalDAV server base URL
 * ======================
 *
 * This URL will be used to build the rest of URLs (principals and
 * calendars)
 *
 * Please, do NOT add trailing slash
 */

$config['caldav_base_url'] = 'http://localhost/baikal/html';

/*
 * CalDAV principal PATH template
 * ==============================
 *
 * Do not use a full URL, use a relative path to be appended to your caldav_base_url
 *
 * %u will be replaced by an username. Please, add trailing slash
 *
 * Some examples:
 * - DAViCal: /caldav.php/%u/
 * - SabreDAV: /calendarserver.php/principals/%u/
 * - Apple Calendar Server: /users/%u/
 */

$config['caldav_principal_template'] = '/cal.php/principals/%u/';

/*
 * CalDAV calendar home set PATH template
 * ======================================
 *
 * Do not use a full URL, use a relative path to be appended to your caldav_base_url
 *
 * %u will be replaced by an username. Please, add trailing slash
 *
 * Some samples:
 *
 * - DAViCal: /caldav.php/%u/
 * - SabreDAV: /calendarserver.php/calendars/%u/
 * - Apple Calendar Server: /calendars/users/%u/
 */

$config['caldav_calendar_homeset_template'] = '/cal.php/calendars/%u/';

/*
 * Public CalDAV base URL for calendars
 * ====================================
 *
 * Please, do NOT add trailing slash.
 * Will be shown to users only when 'show_public_caldav_url' is enabled
 */

$config['caldav_public_base_url'] = 'http://192.168.100.10/baikal/html';


/*******************
 * Share options   *
 *******************/

/*
 * Allow calendar sharing
 * ======================
 *
 * You can enable or disable calendar sharing. If your CalDAV server does not
 * support WebDAV ACLs disable sharing
 */

$config['enable_calendar_sharing'] = false;

// Default permissions for calendar owner
$config['owner_permissions'] = array('all', 'read', 'unlock', 'read-acl',
		'read-current-user-privilege-set', 'write-acl', 'C:read-free-busy',
		'write', 'write-properties', 'write-content', 'bind', 'unbind');

// Permissions for sharing calendars using the 'read' profile
$config['read_profile_permissions'] = array('C:read-free-busy', 'read');

// Permissions for sharing calendars using the 'read+write' profile
$config['read_write_profile_permissions'] = array('C:read-free-busy',
		'read', 'write');

// Authenticated users default permissions
$config['default_permissions'] = array('C:read-free-busy');

/**
 * Permissions for calendar sharing
 *
 * Read your CalDAV server documentation about permissions before changing this option.
 * Default values should work OK 
 *
 * 'owner' entry contains the permissions for the resource owner when sharing a calendar
 * 'authenticated' entry contains the default permissions for every authenticated user
 * 'unauthenticated' entry contains the permissions for anonymous users (empty by default)
 * 'share_read' entry contains permissions for read-only users on shared calendars
 * 'share_rw' entry contains permissions for read+write users on shared calendars
 *
 * You can use the following namespaces:
 *   Default namespace: DAV
 *   Other namespaces: C: (urn:ietf:params:xml:ns:caldav)
 */
$config['acl_permissions'] = array(
    'owner' => array(
        'all',
    ),

    'authenticated' => array(
        'read-current-user-privilege-set',
        'C:read-free-busy'
    ),
    'unauthenticated' => array(
    ),
    'share_read' => array(
        'read-current-user-privilege-set',
        'C:read-free-busy',
        'read'
    ),
    'share_rw' => array(
        'read-current-user-privilege-set',
        'C:read-free-busy',
        'read',
        'write'
    ),
);
