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
 * Valid values are 'basic' and 'digest'
 *
 * Examples:
 *  SabreDAV: $config['caldav_http_auth_method'] = 'digest';
 *  DAViCal: $config['caldav_http_auth_method'] = 'basic';
 */

$config['caldav_http_auth_method'] = 'digest';


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

$config['caldav_base_url'] = 'http://localhost:81/cal.php';

/*
 * CalDAV principal PATH template
 * ==============================
 *
 * Do not use a full URL, use a relative path to be appended to your caldav_base_url
 *
 * Remember to add your CalDAV server relative path if needed
 *
 * %u will be replaced by an username. Please, add trailing slash
 *
 * Some examples:
 * - DAViCal: /caldav.php/%u/
 * - DAViCal under non-root path: /davical/caldav.php/%u/
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
 * Remember to add your CalDAV server relative path if needed
 *
 * %u will be replaced by a username. Please, add trailing slash
 *
 * Some samples:
 *
 * - DAViCal: /caldav.php/%u/
 * - DAViCal under non-root path: /davical/caldav.php/%u/
 * - SabreDAV: /calendarserver.php/calendars/%u/
 * - Apple Calendar Server: /calendars/users/%u/
 */

$config['caldav_calendar_homeset_template'] = '/cal.php/calendars/%u/';

/*
 * Public CalDAV base URL for calendars
 * ====================================
 *
 * Please, do NOT add trailing slash.
 * If your CalDAV server is placed under a relative path, don't specify it here
 * Will be shown to users only when 'show_public_caldav_url' is enabled
 */

$config['caldav_public_base_url'] = 'http://localhost:8081';


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

$config['permissions'] = array(
    // Permissions for calendar owner
    'owner' => array(
        array('DAV:', 'all'),
        array('DAV:', 'read'),
        array('DAV:', 'unlock'),
        array('DAV:', 'read-acl'),
        array('DAV:', 'read-current-user-privilege-set'),
        array('DAV:', 'write-acl'),
        array('urn:ietf:params:xml:ns:caldav', 'read-free-busy'),
        array('DAV:', 'write'),
        array('DAV:', 'write-properties'),
        array('DAV:', 'write-content'),
        array('DAV:', 'bind'),
        array('DAV:', 'unbind'),
    ),

    // Permissions for sharing calendars using the 'read' profile
    'read' => array(
        array('DAV:', 'read'),
        array('urn:ietf:params:xml:ns:caldav', 'read-free-busy'),
    ),

    // Permissions for sharing calendars using the 'read+write' profile
    'read_write' => array(
        array('DAV:', 'read'),
        array('DAV:', 'write'),
        array('urn:ietf:params:xml:ns:caldav', 'read-free-busy'),
    ),

    // Authenticated users default permissions
    'default' => array(
        array('urn:ietf:params:xml:ns:caldav', 'read-free-busy'),
    )
);
