<?php
/**
* Allows logging of CalDAV actions (PUT/DELETE) for possible export or sync
* through some other glue.
*
* @package   davical
* @category Technical
* @subpackage   logging
* @author    Andrew McMillan <andrew@morphoss.com>
* @copyright Morphoss Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2
*
* This file is intended to be used as a template, perhaps the user of this service
* will want to log actions in a very different manner and this can be used as an
* example of how to go about doing that.
*/

/**
* Log the action
* @param string $action_type INSERT / UPDATE or DELETE
* @param string $uid The UID of the modified item
* @param integer $user_no The user owning the containing collection.
* @param integer $collection_id The ID of the containing collection.
* @param string $dav_name The DAV path of the item, relative to the DAViCal base path
*/
function log_caldav_action( $action_type, $uid, $user_no, $collection_id, $dav_name ) {
  global $c;

  $logline = sprintf( '%s %s %s %s %s %s', gmdate('Ymd\THis\Z'), $action_type, $uid, $user_no, $collection_id, $dav_name );
  if ( !isset($c->action_log_name) ) {
    error_log( $logline );
    return;
  }

  $logline .= "\n";

  $logfile = fopen( $c->action_log_name, "a+" );
  fwrite( $logfile, $logline );
  fclose($logfile);
}
