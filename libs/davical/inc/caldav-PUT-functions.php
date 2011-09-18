<?php
/**
* CalDAV Server - handle PUT method
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@morphoss.com>
* @copyright Morphoss Ltd - http://www.morphoss.com/
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later version
*/

/**
* Check if the user wants to put just one VEVENT/VTODO or a whole calendar
* if the collection = calendar = $request_container doesn't exist then create it
* return true if it's a whole calendar
*/

require_once('iCalendar.php');
require_once('WritableCollection.php');

$bad_events = null;

/**
* A regex which will match most reasonable timezones acceptable to PostgreSQL.
*/
$tz_regex = ':^(Africa|America|Antarctica|Arctic|Asia|Atlantic|Australia|Brazil|Canada|Chile|Etc|Europe|Indian|Mexico|Mideast|Pacific|US)/[a-z_]+$:i';

/**
* This function launches an error
* @param boolean $caldav_context Whether we are responding via CalDAV or interactively
* @param int $user_no the user who will receive this ics file
* @param string $path the $path where the PUT failed to store such as /user_foo/home/
* @param string $message An optional error message to return to the client
* @param int $error_no An optional value for the HTTP error code
*/
function rollback_on_error( $caldav_context, $user_no, $path, $message='', $error_no=500 ) {
  global $c, $bad_events;
  if ( !$message ) $message = translate('Database error');
  $qry = new AwlQuery();
  $qry->Rollback();
  if ( $caldav_context ) {
    if ( isset($bad_events) && isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import ) {
      $bad_events[] = $message;
    }
    else {
      global $request;
      $request->DoResponse( $error_no, $message );
    }
    // and we don't return from that, ever...
  }

  $c->messages[] = sprintf(translate('Status: %d, Message: %s, User: %d, Path: %s'), $error_no, $message, $user_no, $path);

}



/**
* Work out the location we are doing the PUT to, and check that we have the rights to
* do the needful.
* @param string $username The name of the destination user
* @param int $user_no The user making the change
* @param string $path The DAV path the resource is bing PUT to
* @param boolean $caldav_context Whether we are responding via CalDAV or interactively
* @param boolean $public Whether the collection will be public, should we need to create it
*/
function controlRequestContainer( $username, $user_no, $path, $caldav_context, $public = null ) {
  global $c, $request, $bad_events;

  // Check to see if the path is like /foo /foo/bar or /foo/bar/baz etc. (not ending with a '/', but contains at least one)
  if ( preg_match( '#^(.*/)([^/]+)$#', $path, $matches ) ) {//(
    $request_container = $matches[1];   // get everything up to the last '/'
  }
  else {
    // In this case we must have a URL with a trailing '/', so it must be a collection.
    $request_container = $path;
  }

  if ( isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import ) {
    $bad_events = array();
  }

  /**
  * Before we write the event, we check the container exists, creating it if it doesn't
  */
  if ( $request_container == "/$username/" ) {
    /**
    * Well, it exists, and we support it, but it is against the CalDAV spec
    */
    dbg_error_log( 'WARN', ' Storing events directly in user\'s base folders is not recommended!');
  }
  else {
    $sql = 'SELECT * FROM collection WHERE dav_name = :dav_name';
    $qry = new AwlQuery( $sql, array( ':dav_name' => $request_container) );
    if ( ! $qry->Exec('PUT',__LINE__,__FILE__) ) {
      rollback_on_error( $caldav_context, $user_no, $path );
    }
    if ( !isset($c->readonly_webdav_collections) || $c->readonly_webdav_collections == true ) {
      if ( $qry->rows() == 0 ) {
        $request->DoResponse( 405 ); // Method not allowed
      }
      return;
    }
    if ( $qry->rows() == 0 ) {
      if ( $public == true ) $public = 't'; else $public = 'f';
      if ( preg_match( '{^(.*/)([^/]+)/$}', $request_container, $matches ) ) {
        $parent_container = $matches[1];
        $displayname = $matches[2];
      }
      $sql = 'INSERT INTO collection ( user_no, parent_container, dav_name, dav_etag, dav_displayname, is_calendar, created, modified, publicly_readable, resourcetypes )
VALUES( :user_no, :parent_container, :dav_name, :dav_etag, :dav_displayname, TRUE, current_timestamp, current_timestamp, :is_public::boolean, :resourcetypes )';
      $params = array(
      ':user_no' => $user_no,
      ':parent_container' => $parent_container,
      ':dav_name' => $request_container,
      ':dav_etag' => md5($user_no. $request_container),
      ':dav_displayname' => $displayname,
      ':is_public' => $public,
      ':resourcetypes' => '<DAV::collection/><urn:ietf:params:xml:ns:caldav:calendar/>'
      );
      $qry->QDo( $sql, $params );
    }
    else if ( isset($public) ) {
      $collection = $qry->Fetch();
      $sql = 'UPDATE collection SET publicly_readable = :is_public::boolean WHERE collection_id = :collection_id';
      $params = array( ':is_public' => ($public?'t':'f'), ':collection_id' => $collection->collection_id );
      if ( ! $qry->QDo($sql,$params) ) {
        rollback_on_error( $caldav_context, $user_no, $path );
      }
    }
  }
}


/**
* Check if this collection should force all events to be PUBLIC.
* @param string $user_no the user that owns the collection
* @param string $dav_name the collection to check
* @return boolean Return true if public events only are allowed.
*/
function public_events_only( $user_no, $dav_name ) {
  global $c;

  $sql = 'SELECT public_events_only FROM collection WHERE dav_name = :dav_name';

  $qry = new AwlQuery($sql, array(':dav_name' => $dav_name) );

  if( $qry->Exec('PUT',__LINE__,__FILE__) && $qry->rows() == 1 ) {
    $collection = $qry->Fetch();

    if ($collection->public_events_only == 't') {
      return true;
    }
  }

  // Something went wrong, must be false.
  return false;
}


/**
* Deliver scheduling requests to attendees
* @param iCalComponent $ical the VCALENDAR to deliver
*/
function handle_schedule_request( $ical ) {
  global $c, $session, $request;
  $resources = $ical->GetComponents('VTIMEZONE',false);
  $ic = $resources[0];
  $etag = md5 ( $request->raw_post );
  $reply = new XMLDocument( array("DAV:" => "", "urn:ietf:params:xml:ns:caldav" => "C" ) );
  $responses = array();

  $attendees = $ic->GetProperties('ATTENDEE');
  $wr_attendees = $ic->GetProperties('X-WR-ATTENDEE');
  if ( count ( $wr_attendees ) > 0 ) {
    dbg_error_log( "POST", "Non-compliant iCal request.  Using X-WR-ATTENDEE property" );
    foreach( $wr_attendees AS $k => $v ) {
      $attendees[] = $v;
    }
  }
  dbg_error_log( "POST", "Attempting to deliver scheduling request for %d attendees", count($attendees) );

  foreach( $attendees AS $k => $attendee ) {
    $attendee_email = preg_replace( '/^mailto:/', '', $attendee->Value() );
    if ( $attendee_email == $request->principal->email ) {
      dbg_error_log( "POST", "not delivering to owner" );
      continue;
    }
    if ( $attendee->GetParameterValue ( 'PARTSTAT' ) != 'NEEDS-ACTION' || preg_match ( '/^[35]\.[3-9]/',  $attendee->GetParameterValue ( 'SCHEDULE-STATUS' ) ) ) {
      dbg_error_log( "POST", "attendee %s does not need action", $attendee_email );
      continue;
    }

    dbg_error_log( "POST", "Delivering to %s", $attendee_email );

    $attendee_principal = new CalDAVPrincipal ( array ('email'=>$attendee_email, 'options'=> array ( 'allow_by_email' => true ) ) );
    if ( $attendee_principal == false ){
      $attendee->SetParameterValue ('SCHEDULE-STATUS','3.7;Invalid Calendar User');
      continue;
    }
    $deliver_path = preg_replace ( '/^.*caldav.php/','', $attendee_principal->schedule_inbox_url );

    $ar = new DAVResource($deliver_path);
    $priv =  $ar->HavePrivilegeTo('schedule-deliver-invite' );
    if ( ! $ar->HavePrivilegeTo('schedule-deliver-invite' ) ){
      $reply = new XMLDocument( array('DAV:' => '') );
      $privnodes = array( $reply->href(ConstructURL($attendee_principal->schedule_inbox_url)), new XMLElement( 'privilege' ) );
      // RFC3744 specifies that we can only respond with one needed privilege, so we pick the first.
      $reply->NSElement( $privnodes[1], 'schedule-deliver-invite' );
      $xml = new XMLElement( 'need-privileges', new XMLElement( 'resource', $privnodes) );
      $xmldoc = $reply->Render('error',$xml);
      $request->DoResponse( 403, $xmldoc, 'text/xml; charset="utf-8"');
    }


    $attendee->SetParameterValue ('SCHEDULE-STATUS','1.2;Scheduling message has been delivered');
    $ncal = new iCalComponent (  );
    $ncal->VCalendar ();
    $ncal->AddProperty ( 'METHOD', 'REQUEST' );
    $ncal->AddComponent ( array_merge ( $ical->GetComponents('VEVENT',false) , array ($ic) ));
    $content = $ncal->Render();
    $cid = $ar->GetProperty('collection_id');
    dbg_error_log('DELIVER', 'to user: %s, to path: %s, collection: %s, from user: %s, caldata %s', $attendee_principal->user_no, $deliver_path, $cid, $request->user_no, $content );
    write_resource( $attendee_principal->user_no, $deliver_path . $etag . '.ics' ,
      $content , $ar->GetProperty('collection_id'), $request->user_no,
      md5($content), $ncal, $put_action_type='INSERT', $caldav_context=true, $log_action=true, $etag );
    $attendee->SetParameterValue ('SCHEDULE-STATUS','1.2;Scheduling message has been delivered');
  }
	// don't write an entry in the out box, ical doesn't delete it or ever read it again
  $ncal = new iCalComponent (  );
  $ncal->VCalendar ();
  $ncal->AddProperty ( 'METHOD', 'REQUEST' );
  $ncal->AddComponent ( array_merge ( $ical->GetComponents('VEVENT',false) , array ($ic) ));
  $content = $ncal->Render();
 	$deliver_path = preg_replace ( '/^.*caldav.php/','', $request->principal->schedule_inbox_url );
  $ar = new DAVResource($deliver_path);
  write_resource( $request->user_no, $deliver_path . $etag . '.ics' ,
    $content , $ar->GetProperty('collection_id'), $request->user_no,
    md5($content), $ncal, $put_action_type='INSERT', $caldav_context=true, $log_action=true, $etag );
  //$etag = md5($content);
  header('ETag: "'. $etag . '"' );
  header('Schedule-Tag: "'.$etag . '"' );
  $request->DoResponse( 201, 'Created' );
}

/**
* Deliver scheduling replies to organizer and other attendees
* @param iCalComponent $ical the VCALENDAR to deliver
* @return false on error
*/
function handle_schedule_reply ( $ical ) {
  global $c, $session, $request;
  $resources = $ical->GetComponents('VTIMEZONE',false);
  $ic = $resources[0];
  $etag = md5 ( $request->raw_post );
  $organizer = $ic->GetProperties('ORGANIZER');
  // for now we treat events with out organizers as an error
  if ( count ( $organizer ) < 1 ) return false;

  $attendees = array_merge($organizer,$ic->GetProperties('ATTENDEE'));
  $wr_attendees = $ic->GetProperties('X-WR-ATTENDEE');
  if ( count ( $wr_attendees ) > 0 ) {
    dbg_error_log( "POST", "Non-compliant iCal request.  Using X-WR-ATTENDEE property" );
    foreach( $wr_attendees AS $k => $v ) {
      $attendees[] = $v;
    }
  }
  dbg_error_log( "POST", "Attempting to deliver scheduling request for %d attendees", count($attendees) );

  foreach( $attendees AS $k => $attendee ) {
	  $attendee_email = preg_replace( '/^mailto:/', '', $attendee->Value() );
	  dbg_error_log( "POST", "Delivering to %s", $attendee_email );
	  $attendee_principal = new CalDAVPrincipal ( array ('email'=>$attendee_email, 'options'=> array ( 'allow_by_email' => true ) ) );
	  $deliver_path = preg_replace ( '/^.*caldav.php/','', $attendee_principal->schedule_inbox_url );
	  $attendee_email = preg_replace( '/^mailto:/', '', $attendee->Value() );
    if ( $attendee_email == $request->principal->email ) {
      dbg_error_log( "POST", "not delivering to owner" );
      continue;
    }
	  $ar = new DAVResource($deliver_path);
	  if ( ! $ar->HavePrivilegeTo('schedule-deliver-reply' ) ){
	    $reply = new XMLDocument( array('DAV:' => '') );
	     $privnodes = array( $reply->href(ConstructURL($attendee_principal->schedule_inbox_url)), new XMLElement( 'privilege' ) );
	     // RFC3744 specifies that we can only respond with one needed privilege, so we pick the first.
	     $reply->NSElement( $privnodes[1], 'schedule-deliver-reply' );
	     $xml = new XMLElement( 'need-privileges', new XMLElement( 'resource', $privnodes) );
	     $xmldoc = $reply->Render('error',$xml);
			 $request->DoResponse( 403, $xmldoc, 'text/xml; charset="utf-8"' );
			 continue;
	  }

	  $ncal = new iCalComponent (  );
	  $ncal->VCalendar ();
	  $ncal->AddProperty ( 'METHOD', 'REPLY' );
	  $ncal->AddComponent ( array_merge ( $ical->GetComponents('VEVENT',false) , array ($ic) ));
	  $content = $ncal->Render();
	  write_resource( $attendee_principal->user_no, $deliver_path . $etag . '.ics' ,
	    $content , $ar->GetProperty('collection_id'), $request->user_no,
	    md5($content), $ncal, $put_action_type='INSERT', $caldav_context=true, $log_action=true, $etag );
	}
  $request->DoResponse( 201, 'Created' );
}




/**
* Create a scheduling request in the schedule inbox for the
* @param iCalComponent $resource The VEVENT/VTODO/... resource we are scheduling
* @param iCalProp $attendee The attendee we are scheduling
* @return float The result of the scheduling request, per caldav-sched #3.5.4
*/
function write_scheduling_request( &$resource, $attendee_value, $create_resource ) {
  $email = preg_replace( '/^mailto:/', '', $attendee_value );
  $schedule_target = getUserByEmail($email);
  if ( isset($schedule_target) && is_object($schedule_target) ) {
    $attendee_inbox = new WritableCollection(array('path' => $schedule_target->dav_name.'.in/'));
    if ( ! $attendee_inbox->HavePrivilegeTo('schedule-deliver-invite') ) {
      $response = '3.8;'.translate('No authority to deliver invitations to user.');
    }
    if ( $attendee_inbox->WriteCalendarMember($resource, $create_resource) ) {
      $response = '2.0;'.translate('Scheduling invitation delivered successfully');
    }
    else {
      $response = '5.3;'.translate('No scheduling support for user');
    }
  }
  else {
    $response = '5.3;'.translate('No scheduling support for user');
  }
  return '"'.$response.'"';
}

/**
* Create scheduling requests in the schedule inbox for the
* @param iCalComponent $resource The VEVENT/VTODO/... resource we are scheduling
*/
function create_scheduling_requests( &$resource ) {
  if ( ! is_object($resource) ) {
    dbg_error_log( 'PUT', 'create_scheduling_requests called with non-object parameter (%s)', gettype($resource) );
    return;
  }

  $attendees = $resource->GetPropertiesByPath('/VCALENDAR/*/ATTENDEE');
	$wr_attendees = $resource->GetPropertiesByPath('/VCALENDAR/*/X-WR-ATTENDEE');
	if ( count ( $wr_attendees ) > 0 ) {
    dbg_error_log( 'POST', 'Non-compliant iCal request.  Using X-WR-ATTENDEE property' );
    foreach( $wr_attendees AS $k => $v ) {
      $attendees[] = $v;
    }
  }
  if ( count($attendees) == 0 ) {
    dbg_error_log( 'PUT', 'Event has no attendees - no scheduling required.', count($attendees) );
    return;
  }

  dbg_error_log( 'PUT', 'Adding to scheduling inbox %d attendees', count($attendees) );
  foreach( $attendees AS $attendee ) {
    $attendee->SetParameterValue( 'SCHEDULE-STATUS', write_scheduling_request( $resource, $attendee->Value(), true ) );
  }
}


/**
* Update scheduling requests in the schedule inbox for the
* @param iCalComponent $resource The VEVENT/VTODO/... resource we are scheduling
*/
function update_scheduling_requests( &$resource ) {
  if ( ! is_object($resource) ) {
    dbg_error_log( 'PUT', 'update_scheduling_requests called with non-object parameter (%s)', gettype($resource) );
    return;
  }

  $attendees = $resource->GetPropertiesByPath('/VCALENDAR/*/ATTENDEE');
	$wr_attendees = $resource->GetPropertiesByPath('/VCALENDAR/*/X-WR-ATTENDEE');
	if ( count ( $wr_attendees ) > 0 ) {
    dbg_error_log( 'POST', 'Non-compliant iCal request.  Using X-WR-ATTENDEE property' );
    foreach( $wr_attendees AS $k => $v ) {
      $attendees[] = $v;
    }
  }
  if ( count($attendees) == 0 ) {
    dbg_error_log( 'PUT', 'Event has no attendees - no scheduling required.', count($attendees) );
    return;
  }

  dbg_error_log( 'PUT', 'Adding to scheduling inbox %d attendees', count($attendees) );
  foreach( $attendees AS $attendee ) {
    $attendee->SetParameterValue( 'SCHEDULE-STATUS', write_scheduling_request( $resource, $attendee->Value(), false ) );
  }
}


/**
* This function will import a whole calendar
* @param string $ics_content the ics file to import
* @param int $user_no the user wich will receive this ics file
* @param string $path the $path where it will be store such as /user_foo/home/
* @param boolean $caldav_context Whether we are responding via CalDAV or interactively
*
* Any VEVENTs with the same UID will be concatenated together
*/
function import_collection( $ics_content, $user_no, $path, $caldav_context, $appending = false ) {
  global $c, $session, $tz_regex;

  if ( ! ini_get('open_basedir') && (isset($c->dbg['ALL']) || isset($c->dbg['put'])) ) {
    $fh = fopen('/tmp/PUT-2.txt','w');
    if ( $fh ) {
      fwrite($fh,$ics_content);
      fclose($fh);
    }
  }

  $calendar = new iCalComponent($ics_content);
  $timezones = $calendar->GetComponents('VTIMEZONE',true);
  $components = $calendar->GetComponents('VTIMEZONE',false);

  $displayname = $calendar->GetPValue('X-WR-CALNAME');
  if ( !$appending && isset($displayname) ) {
    $sql = 'UPDATE collection SET dav_displayname = :displayname WHERE dav_name = :dav_name';
    $qry = new AwlQuery( $sql, array( ':displayname' => $displayname, ':dav_name' => $path) );
    if ( ! $qry->Exec('PUT',__LINE__,__FILE__) ) rollback_on_error( $caldav_context, $user_no, $path );
  }

  $tz_ids    = array();
  foreach( $timezones AS $k => $tz ) {
    $tz_ids[$tz->GetPValue('TZID')] = $k;
  }

  /** Build an array of resources.  Each resource is an array of iCalComponent */
  $resources = array();
  foreach( $components AS $k => $comp ) {
    $uid = $comp->GetPValue('UID');
    if ( $uid == null || $uid == '' ) continue;
    if ( !isset($resources[$uid]) ) $resources[$uid] = array();
    $resources[$uid][] = $comp;

    /** Ensure we have the timezone component for this in our array as well */
    $tzid = $comp->GetPParamValue('DTSTART', 'TZID');
    if ( !isset($tzid) || $tzid == '' ) $tzid = $comp->GetPParamValue('DUE','TZID');
    if ( !isset($resources[$uid][$tzid]) && isset($tz_ids[$tzid]) ) {
      $resources[$uid][$tzid] = $timezones[$tz_ids[$tzid]];
    }
  }


  $sql = 'SELECT * FROM collection WHERE dav_name = :dav_name';
  $qry = new AwlQuery( $sql, array( ':dav_name' => $path) );
  if ( ! $qry->Exec('PUT',__LINE__,__FILE__) ) rollback_on_error( $caldav_context, $user_no, $path );
  if ( ! $qry->rows() == 1 ) {
    dbg_error_log( 'ERROR', ' PUT: Collection does not exist at "%s" for user %d', $path, $user_no );
    rollback_on_error( $caldav_context, $user_no, $path );
  }
  $collection = $qry->Fetch();

  if ( !(isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import) ) $qry->Begin();
  $base_params = array( ':collection_id' => $collection->collection_id );
  if ( !$appending ) {
    if ( !$qry->QDo('DELETE FROM calendar_item WHERE collection_id = :collection_id', $base_params)
      || !$qry->QDo('DELETE FROM caldav_data WHERE collection_id = :collection_id', $base_params) )
      rollback_on_error( $caldav_context, $user_no, $collection->collection_id );
  }

  $dav_data_insert = <<<EOSQL
INSERT INTO caldav_data ( user_no, dav_name, dav_etag, caldav_data, caldav_type, logged_user, created, modified, collection_id )
    VALUES( :user_no, :dav_name, :etag, :dav_data, :caldav_type, :session_user, current_timestamp, current_timestamp, :collection_id )
EOSQL;

  $calitem_insert = <<<EOSQL
INSERT INTO calendar_item (user_no, dav_name, dav_id, dav_etag, uid, dtstamp, dtstart, dtend, summary, location, class, transp,
                    description, rrule, tz_id, last_modified, url, priority, created, due, percent_complete, status, collection_id )
    VALUES ( :user_no, :dav_name, currval('dav_id_seq'), :etag, :uid, :dtstamp, :dtstart, ##dtend##, :summary, :location, :class, :transp,
                :description, :rrule, :tzid, :modified, :url, :priority, :created, :due, :percent_complete, :status, :collection_id)
EOSQL;

  $last_tz_locn = '';
  foreach( $resources AS $uid => $resource ) {
    if ( isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import ) $qry->Begin();

    /** Construct the VCALENDAR data */
    $vcal = new iCalComponent();
    $vcal->VCalendar();
    $vcal->SetComponents($resource);
    create_scheduling_requests($vcal);
    $icalendar = $vcal->Render();

    /** As ever, we mostly deal with the first resource component */
    $first = $resource[0];

    $dav_data_params = $base_params;
    $dav_data_params[':user_no'] = $user_no;
    $dav_data_params[':dav_name'] = sprintf( '%s%s.ics', $path, $uid );
    $dav_data_params[':etag'] = md5($icalendar);
    $calitem_params = $dav_data_params;
    $dav_data_params[':dav_data'] = $icalendar;
    $dav_data_params[':caldav_type'] = $first->GetType();
    $dav_data_params[':session_user'] = $session->user_no;
    if ( !$qry->QDo($dav_data_insert,$dav_data_params) ) rollback_on_error( $caldav_context, $user_no, $path );

    $qry->QDo('SELECT dav_id FROM caldav_data WHERE dav_name = :dav_name ', array(':dav_name' => $dav_data_params[':dav_name']));
    if ( $qry->rows() == 1 && $row = $qry->Fetch() ) {
      $dav_id = $row->dav_id;
    }

    $dtstart = $first->GetPValue('DTSTART');
    $calitem_params[':dtstart'] = $dtstart;
    if ( (!isset($dtstart) || $dtstart == '') && $first->GetPValue('DUE') != '' ) {
      $dtstart = $first->GetPValue('DUE');
    }

    $dtend = $first->GetPValue('DTEND');
    if ( isset($dtend) && $dtend != '' ) {
      dbg_error_log( 'PUT', ' DTEND: "%s", DTSTART: "%s", DURATION: "%s"', $dtend, $dtstart, $first->GetPValue('DURATION') );
      $calitem_params[':dtend'] = $dtend;
      $dtend = ':dtend';
    }
    else {
      $dtend = 'NULL';
      if ( $first->GetPValue('DURATION') != '' AND $dtstart != '' ) {
        $duration = trim(preg_replace( '#[PT]#', ' ', $first->GetPValue('DURATION') ));
        if ( $duration == '' ) $duration = '0 seconds';
        $dtend = '(:dtstart::timestamp with time zone + :duration::interval)';
        $calitem_params[':duration'] = $duration;
      }
      elseif ( $first->GetType() == 'VEVENT' ) {
        /**
        * From RFC2445 4.6.1:
        * For cases where a "VEVENT" calendar component specifies a "DTSTART"
        * property with a DATE data type but no "DTEND" property, the events
        * non-inclusive end is the end of the calendar date specified by the
        * "DTSTART" property. For cases where a "VEVENT" calendar component specifies
        * a "DTSTART" property with a DATE-TIME data type but no "DTEND" property,
        * the event ends on the same calendar date and time of day specified by the
        * "DTSTART" property.
        *
        * So we're looking for 'VALUE=DATE', to identify the duration, effectively.
        *
        */
        $value_type = $first->GetPParamValue('DTSTART','VALUE');
        dbg_error_log('PUT','DTSTART without DTEND. DTSTART value type is %s', $value_type );
        if ( isset($value_type) && $value_type == 'DATE' )
          $dtend = '(:dtstart::timestamp with time zone::date + \'1 day\'::interval)';
        else
          $dtend = ':dtstart';
      }
    }

    $last_modified = $first->GetPValue('LAST-MODIFIED');
    if ( !isset($last_modified) || $last_modified == '' ) $last_modified = gmdate( 'Ymd\THis\Z' );
    $calitem_params[':modified'] = $last_modified;

    $dtstamp = $first->GetPValue('DTSTAMP');
    if ( !isset($dtstamp) || $dtstamp == '' ) $dtstamp = $last_modified;
    $calitem_params[':dtstamp'] = $dtstamp;

    /** RFC2445, 4.8.1.3: Default is PUBLIC, or also if overridden by the collection settings */
    $class = ($collection->public_events_only == 't' ? 'PUBLIC' : $first->GetPValue('CLASS') );
    if ( !isset($class) || $class == '' ) $class = 'PUBLIC';
    $calitem_params[':class'] = $class;


    /** Calculate what timezone to set, first, if possible */
    $tzid = $first->GetPParamValue('DTSTART','TZID');
    if ( !isset($tzid) || $tzid == '' ) $tzid = $first->GetPParamValue('DUE','TZID');
    if ( isset($tzid) && $tzid != '' ) {
      if ( isset($resource[$tzid]) ) {
        $tz = $resource[$tzid];
        $tz_locn = $tz->GetPValue('X-LIC-LOCATION');
      }
      else {
        unset($tz);
        unset($tz_locn);
      }
      if ( ! isset($tz_locn) || ! preg_match( $tz_regex, $tz_locn ) ) {
        if ( preg_match( '#([^/]+/[^/]+)$#', $tzid, $matches ) ) {
          $tz_locn = $matches[1];
        }
      }
      dbg_error_log( 'PUT', ' Using TZID[%s] and location of [%s]', $tzid, (isset($tz_locn) ? $tz_locn : '') );
      if ( isset($tz_locn) && ($tz_locn != $last_tz_locn) && preg_match( $tz_regex, $tz_locn ) ) {
        dbg_error_log( 'PUT', ' Setting timezone to %s', $tz_locn );
        if ( $tz_locn != '' ) {
          $qry->QDo('SET TIMEZONE TO \''.$tz_locn."'" );
        }
        $last_tz_locn = $tz_locn;
      }
      $params = array( ':tzid' => $tzid);
      $qry = new AwlQuery('SELECT tz_locn FROM time_zone WHERE tz_id = :tzid', $params );
      if ( $qry->Exec('PUT',__LINE__,__FILE__) && $qry->rows() == 0 ) {
        $params[':tzlocn'] = $tz_locn;
        $params[':tzspec'] = (isset($tz) ? $tz->Render() : null );
        $qry->QDo('INSERT INTO time_zone (tz_id, tz_locn, tz_spec) VALUES(:tzid,:tzlocn,:tzspec)', $params );
      }
      if ( !isset($tz_locn) || $tz_locn == '' ) $tz_locn = $tzid;
    }
    else {
      $tzid = null;
    }

    $sql = str_replace( '##dtend##', $dtend, $calitem_insert );
    $calitem_params[':tzid'] = $tzid;
    $calitem_params[':uid'] = $first->GetPValue('UID');
    $calitem_params[':summary'] = $first->GetPValue('SUMMARY');
    $calitem_params[':location'] = $first->GetPValue('LOCATION');
    $calitem_params[':transp'] = $first->GetPValue('TRANSP');
    $calitem_params[':description'] = $first->GetPValue('DESCRIPTION');
    $calitem_params[':rrule'] = $first->GetPValue('RRULE');
    $calitem_params[':url'] = $first->GetPValue('URL');
    $calitem_params[':priority'] = $first->GetPValue('PRIORITY');
    $calitem_params[':due'] = $first->GetPValue('DUE');
    $calitem_params[':percent_complete'] = $first->GetPValue('PERCENT-COMPLETE');
    $calitem_params[':status'] = $first->GetPValue('STATUS');

    $created = $first->GetPValue('CREATED');
    if ( $created == '00001231T000000Z' ) $created = '20001231T000000Z';
    $calitem_params[':created'] = $created;

    if ( !$qry->QDo($sql,$calitem_params) ) rollback_on_error( $caldav_context, $user_no, $path);

    write_alarms($dav_id, $first);
    write_attendees($dav_id, $first);

    create_scheduling_requests( $vcal );
    if ( isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import ) $qry->Commit();
  }

  if ( !(isset($c->skip_bad_event_on_import) && $c->skip_bad_event_on_import) ) {
    if ( ! $qry->Commit() ) rollback_on_error( $caldav_context, $user_no, $path);
  }
}


/**
* Given a dav_id and an original iCalComponent, pull out each of the VALARMs
* and write the values into the calendar_alarm table.
*
* @param int $dav_id The dav_id of the caldav_data we're processing
* @param iCalComponent The VEVENT or VTODO containing the VALARM
* @return null
*/
function write_alarms( $dav_id, $ical ) {
  $qry = new AwlQuery('DELETE FROM calendar_alarm WHERE dav_id = '.$dav_id );
  $qry->Exec('PUT',__LINE__,__FILE__);

  $alarms = $ical->GetComponents('VALARM');
  if ( count($alarms) < 1 ) return;

  $qry->SetSql('INSERT INTO calendar_alarm ( dav_id, action, trigger, summary, description, component, next_trigger )
          VALUES( '.$dav_id.', :action, :trigger, :summary, :description, :component,
                                      :related::timestamp with time zone + :related_trigger::interval )' );
  $qry->Prepare();
  foreach( $alarms AS $v ) {
    $trigger = array_merge($v->GetProperties('TRIGGER'));
    $trigger = $trigger[0];
    $related = null;
    $related_trigger = '0M';
    $trigger_type = $trigger->GetParameterValue('VALUE');
    if ( !isset($trigger_type) || $trigger_type == 'DURATION' ) {
      switch ( $trigger->GetParameterValue('RELATED') ) {
        case 'DTEND':  $related = $ical->GetPValue('DTEND'); break;
        case 'DUE':    $related = $ical->GetPValue('DUE');   break;
        default:       $related = $ical->GetPValue('DTSTART');
      }
      $duration = $trigger->Value();
      $minus = (substr($duration,0,1) == '-');
      $related_trigger = trim(preg_replace( '#[PT-]#', ' ', $duration ));
      if ( $minus ) {
        $related_trigger = preg_replace( '{(\d+[WDHMS])}', '-$1 ', $related_trigger );
      }
    }
    $qry->Bind(':action', $v->GetPValue('ACTION'));
    $qry->Bind(':trigger', $trigger->Render());
    $qry->Bind(':summary', $v->GetPValue('SUMMARY'));
    $qry->Bind(':description', $v->GetPValue('DESCRIPTION'));
    $qry->Bind(':component', $v->Render());
    $qry->Bind(':related', $related );
    $qry->Bind(':related_trigger', $related_trigger );
    $qry->Exec('PUT',__LINE__,__FILE__);
  }
}


/**
* Parse out the attendee property and write a row to the
* calendar_attendee table for each one.
* @param int $dav_id The dav_id of the caldav_data we're processing
* @param iCalComponent The VEVENT or VTODO containing the ATTENDEEs
* @return null
*/
function write_attendees( $dav_id, $ical ) {
  $qry = new AwlQuery('DELETE FROM calendar_attendee WHERE dav_id = '.$dav_id );
  $qry->Exec('PUT',__LINE__,__FILE__);

  $attendees = $ical->GetProperties('ATTENDEE');
  if ( count($attendees) < 1 ) return;

  $qry->SetSql('INSERT INTO calendar_attendee ( dav_id, status, partstat, cn, attendee, role, rsvp, property )
          VALUES( '.$dav_id.', :status, :partstat, :cn, :attendee, :role, :rsvp, :property )' );
  $qry->Prepare();
  $processed = array();
  foreach( $attendees AS $v ) {
    $attendee = $v->Value();
    if ( isset($processed[$attendee]) ) {
      dbg_error_log( 'LOG', 'Duplicate attendee "%s" in resource "%d"', $attendee, $dav_id );
      dbg_error_log( 'LOG', 'Original:  "%s"', $processed[$attendee] );
      dbg_error_log( 'LOG', 'Duplicate: "%s"', $v->Render() );
      continue; /** @TODO: work out why we get duplicate ATTENDEE on one VEVENT */
    }
    $qry->Bind(':attendee', $attendee );
    $qry->Bind(':status',   $v->GetParameterValue('STATUS') );
    $qry->Bind(':partstat', $v->GetParameterValue('PARTSTAT') );
    $qry->Bind(':cn',       $v->GetParameterValue('CN') );
    $qry->Bind(':role',     $v->GetParameterValue('ROLE') );
    $qry->Bind(':rsvp',     $v->GetParameterValue('RSVP') );
    $qry->Bind(':property', $v->Render() );
    $qry->Exec('PUT',__LINE__,__FILE__);
    $processed[$attendee] = $v->Render();
  }
}


/**
* Actually write the resource to the database.  All checking of whether this is reasonable
* should be done before this is called.
* @param int $user_no The user_no owning this resource on the server
* @param string $path The path to the resource being written
* @param string $caldav_data The actual resource to be written
* @param int $collection_id The ID of the collection containing the resource being written
* @param int $author The user_no who wants to put this resource on the server
* @param string $etag An etag unique for this event
* @param object $ic The parsed iCalendar object
* @param string $put_action_type INSERT or UPDATE depending on what we are to do
* @param boolean $caldav_context True, if we are responding via CalDAV, false for other ways of calling this
* @param string Either 'INSERT' or 'UPDATE': the type of action we are doing
* @param boolean $log_action Whether to log the fact that we are writing this into an action log (if configured)
* @param string $weak_etag An etag that is NOT modified on ATTENDEE changes for this event
* @return boolean True for success, false for failure.
*/
function write_resource( $user_no, $path, $caldav_data, $collection_id, $author, $etag, $ic, $put_action_type, $caldav_context, $log_action=true, $weak_etag=null ) {
  global $tz_regex;

  $resources = $ic->GetComponents('VTIMEZONE',false); // Not matching VTIMEZONE
  if ( !isset($resources[0]) ) {
    $resource_type = 'Unknown';
    /** @TODO: Handle writing non-calendar resources, like address book entries or random file data */
    rollback_on_error( $caldav_context, $user_no, $path, translate('No calendar content'), 412 );
    return false;
  }
  else {
    $first = $resources[0];
    $resource_type = $first->GetType();
  }

  $qry = new AwlQuery();
  $qry->Begin();

  $dav_params = array(
      ':etag' => $etag,
      ':dav_data' => $caldav_data,
      ':caldav_type' => $resource_type,
      ':session_user' => $author,
      ':weak_etag' => $weak_etag
  );

  $calitem_params = array(
      ':etag' => $etag
  );

  if ( $put_action_type == 'INSERT' ) {
    $qry->QDo('SELECT nextval(\'dav_id_seq\') AS dav_id');
  }
  else {
    $qry->QDo('SELECT dav_id FROM caldav_data WHERE dav_name = :dav_name ', array(':dav_name' => $path));
  }
  if ( $qry->rows() != 1 || !($row = $qry->Fetch()) ) {
    // No dav_id?  => We're toast!
    rollback_on_error( $caldav_context, $user_no, $path);
    return false;
  }
  $dav_id = $row->dav_id;
  $dav_params[':dav_id'] = $dav_id;
  $calitem_params[':dav_id'] = $dav_id;
  
  $dtstart = $first->GetPValue('DTSTART');
  $calitem_params[':dtstart'] = $dtstart;
  if ( (!isset($dtstart) || $dtstart == '') && $first->GetPValue('DUE') != '' ) {
    $dtstart = $first->GetPValue('DUE');
  }

  $dtend = $first->GetPValue('DTEND');
  if ( isset($dtend) && $dtend != '' ) {
    dbg_error_log( 'PUT', ' DTEND: "%s", DTSTART: "%s", DURATION: "%s"', $dtend, $dtstart, $first->GetPValue('DURATION') );
    $calitem_params[':dtend'] = $dtend;
    $dtend = ':dtend';
  }
  else {
    $dtend = 'NULL';
    if ( $first->GetPValue('DURATION') != '' AND $dtstart != '' ) {
      $duration = trim(preg_replace( '#[PT]#', ' ', $first->GetPValue('DURATION') ));
      if ( $duration == '' ) $duration = '0 seconds';
      $dtend = '(:dtstart::timestamp with time zone + :duration::interval)';
      $calitem_params[':duration'] = $duration;
    }
    elseif ( $first->GetType() == 'VEVENT' ) {
      /**
      * From RFC2445 4.6.1:
      * For cases where a "VEVENT" calendar component specifies a "DTSTART"
      * property with a DATE data type but no "DTEND" property, the events
      * non-inclusive end is the end of the calendar date specified by the
      * "DTSTART" property. For cases where a "VEVENT" calendar component specifies
      * a "DTSTART" property with a DATE-TIME data type but no "DTEND" property,
      * the event ends on the same calendar date and time of day specified by the
      * "DTSTART" property.
      *
      * So we're looking for 'VALUE=DATE', to identify the duration, effectively.
      *
      */
      $value_type = $first->GetPParamValue('DTSTART','VALUE');
      dbg_error_log('PUT','DTSTART without DTEND. DTSTART value type is %s', $value_type );
      if ( isset($value_type) && $value_type == 'DATE' )
        $dtend = '(:dtstart::timestamp with time zone::date + \'1 day\'::interval)';
      else
        $dtend = ':dtstart';
    }
  }

  $dtstamp = $first->GetPValue('DTSTAMP');
  if ( !isset($dtstamp) || $dtstamp == '' ) {
    // Strictly, we're dealing with an out of spec component here, but we'll try and survive
    $dtstamp = gmdate( 'Ymd\THis\Z' );
  }
  $calitem_params[':dtstamp'] = $dtstamp;

  $last_modified = $first->GetPValue('LAST-MODIFIED');
  if ( !isset($last_modified) || $last_modified == '' ) $last_modified = $dtstamp;
  $dav_params[':modified'] = $last_modified;
  $calitem_params[':modified'] = $last_modified;

  $created = $first->GetPValue('CREATED');
  if ( $created == '00001231T000000Z' ) $created = '20001231T000000Z';
  
  $class = $first->GetPValue('CLASS');
  /* Check and see if we should over ride the class. */
  /** @TODO: is there some way we can move this out of this function? Or at least get rid of the need for the SQL query here. */
  if ( public_events_only($user_no, $path) ) {
    $class = 'PUBLIC';
  }

  /*
   * It seems that some calendar clients don't set a class...
   * RFC2445, 4.8.1.3:
   * Default is PUBLIC
   */
  if ( !isset($class) || $class == '' ) {
    $class = 'PUBLIC';
  }
  $calitem_params[':class'] = $class;


  /** Calculate what timezone to set, first, if possible */
  $last_tz_locn = 'Turkmenikikamukau';  // I really hope this location doesn't exist!
  $tzid = $first->GetPParamValue('DTSTART','TZID');
  if ( !isset($tzid) || $tzid == '' ) $tzid = $first->GetPParamValue('DUE','TZID');
  $timezones = $ic->GetComponents('VTIMEZONE');
  foreach( $timezones AS $k => $tz ) {
    if ( $tz->GetPValue('TZID') != $tzid ) {
      /**
      * We'll pretend they didn't forget to give us a TZID and that they
      * really hope the server is running in the timezone they supplied... but be noisy about it.
      */
      dbg_error_log( 'ERROR', ' Event includes TZID[%s] but uses TZID[%s]!', $tz->GetPValue('TZID'), $tzid );
      $tzid = $tz->GetPValue('TZID');
    }
    // This is the one
    $tz_locn = $tz->GetPValue('X-LIC-LOCATION');
    if ( ! isset($tz_locn) ) {
      if ( preg_match( '#([^/]+/[^/]+)$#', $tzid, $matches ) )
        $tz_locn = $matches[1];
      else if ( isset($tzid) && $tzid != '' ) {
        dbg_error_log( 'ERROR', ' Couldn\'t guess Olsen TZ from TZID[%s].  This may end in tears...', $tzid );
      }
    }
    else {
      if ( ! preg_match( $tz_regex, $tz_locn ) ) {
        if ( preg_match( '#([^/]+/[^/]+)$#', $tzid, $matches ) ) $tz_locn = $matches[1];
      }
    }

    dbg_error_log( 'PUT', ' Using TZID[%s] and location of [%s]', $tzid, (isset($tz_locn) ? $tz_locn : '') );
    if ( isset($tz_locn) && ($tz_locn != $last_tz_locn) && preg_match( $tz_regex, $tz_locn ) ) {
      dbg_error_log( 'PUT', ' Setting timezone to %s', $tz_locn );
      if ( $tz_locn != '' ) {
        $qry->QDo('SET TIMEZONE TO \''.$tz_locn."'" );
      }
      $last_tz_locn = $tz_locn;
    }
    $params = array( ':tzid' => $tzid);
    $qry = new AwlQuery('SELECT tz_locn FROM time_zone WHERE tz_id = :tzid', $params );
    if ( $qry->Exec('PUT',__LINE__,__FILE__) && $qry->rows() == 0 ) {
      $params[':tzlocn'] = $tz_locn;
      $params[':tzspec'] = (isset($tz) ? $tz->Render() : null );
      $qry->QDo('INSERT INTO time_zone (tz_id, tz_locn, tz_spec) VALUES(:tzid,:tzlocn,:tzspec)', $params );
    }
    if ( !isset($tz_locn) || $tz_locn == '' ) $tz_locn = $tzid;

  }


  $calitem_params[':tzid'] = $tzid;
  $calitem_params[':uid'] = $first->GetPValue('UID');
  $calitem_params[':summary'] = $first->GetPValue('SUMMARY');
  $calitem_params[':location'] = $first->GetPValue('LOCATION');
  $calitem_params[':transp'] = $first->GetPValue('TRANSP');
  $calitem_params[':description'] = $first->GetPValue('DESCRIPTION');
  $calitem_params[':rrule'] = $first->GetPValue('RRULE');
  $calitem_params[':url'] = $first->GetPValue('URL');
  $calitem_params[':priority'] = $first->GetPValue('PRIORITY');
  $calitem_params[':due'] = $first->GetPValue('DUE');
  $calitem_params[':percent_complete'] = $first->GetPValue('PERCENT-COMPLETE');
  $calitem_params[':status'] = $first->GetPValue('STATUS');

  if ( !isset($dav_params[':modified']) ) $dav_params[':modified'] = 'now';
  if ( $put_action_type == 'INSERT' ) {
    create_scheduling_requests($vcal);
    $sql = 'INSERT INTO caldav_data ( dav_id, user_no, dav_name, dav_etag, caldav_data, caldav_type, logged_user, created, modified, collection_id, weak_etag )
            VALUES( :dav_id, :user_no, :dav_name, :etag, :dav_data, :caldav_type, :session_user, :created, :modified, :collection_id, :weak_etag )';
    $dav_params[':collection_id'] = $collection_id;
    $dav_params[':user_no'] = $user_no;
    $dav_params[':dav_name'] = $path;
    $dav_params[':created'] = (isset($created) && $created != '' ? $created : $dtstamp);
  }
  else {
    update_scheduling_requests($vcal);
    $sql = 'UPDATE caldav_data SET caldav_data=:dav_data, dav_etag=:etag, caldav_type=:caldav_type, logged_user=:session_user,
            modified=:modified, weak_etag=:weak_etag WHERE dav_id=:dav_id';
  }
  if ( !$qry->QDo($sql,$dav_params) ) {
    rollback_on_error( $caldav_context, $user_no, $path);
    return false;
  }

  
  if ( $put_action_type == 'INSERT' ) {
    $sql = <<<EOSQL
INSERT INTO calendar_item (user_no, dav_name, dav_id, dav_etag, uid, dtstamp,
                dtstart, dtend, summary, location, class, transp,
                description, rrule, tz_id, last_modified, url, priority,
                created, due, percent_complete, status, collection_id )
   VALUES ( :user_no, :dav_name, currval('dav_id_seq'), :etag, :uid, :dtstamp,
                :dtstart, $dtend, :summary, :location, :class, :transp,
                :description, :rrule, :tzid, :modified, :url, :priority,
                :created, :due, :percent_complete, :status, :collection_id )
EOSQL;
    $sync_change = 201;
    $calitem_params[':collection_id'] = $collection_id;
    $calitem_params[':user_no'] = $user_no;
    $calitem_params[':dav_name'] = $path;
    $calitem_params[':created'] = $dav_params[':created'];
  }
  else {
    $sql = <<<EOSQL
UPDATE calendar_item SET dav_etag=:etag, uid=:uid, dtstamp=:dtstamp,
                dtstart=:dtstart, dtend=$dtend, summary=:summary, location=:location, class=:class, transp=:transp,
                description=:description, rrule=:rrule, tz_id=:tzid, last_modified=:modified, url=:url, priority=:priority,
                due=:due, percent_complete=:percent_complete, status=:status
       WHERE dav_id=:dav_id
EOSQL;
    $sync_change = 200;
  }

  write_alarms($dav_id, $first);
  write_attendees($dav_id, $first);

  if ( $log_action && function_exists('log_caldav_action') ) {
    log_caldav_action( $put_action_type, $first->GetPValue('UID'), $user_no, $collection_id, $path );
  }
  else if ( $log_action  ) {
    dbg_error_log( 'PUT', 'No log_caldav_action( %s, %s, %s, %s, %s) can be called.',
            $put_action_type, $first->GetPValue('UID'), $user_no, $collection_id, $path );
  }
  
  $qry = new AwlQuery( $sql, $calitem_params );
  if ( !$qry->Exec('PUT',__LINE__,__FILE__) ) {
    rollback_on_error( $caldav_context, $user_no, $path);
    return false;
  }
  $qry->QDo("SELECT write_sync_change( $collection_id, $sync_change, :dav_name)", array(':dav_name' => $path ) );
  $qry->Commit();

  dbg_error_log( 'PUT', 'User: %d, ETag: %s, Path: %s', $author, $etag, $path);

  return true;  // Success!
}



/**
* A slightly simpler version of write_resource which will make more sense for calling from
* an external program.  This makes assumptions that the collection and user do exist
* and bypasses all checks for whether it is reasonable to write this here.
* @param string $path The path to the resource being written
* @param string $caldav_data The actual resource to be written
* @param string $put_action_type INSERT or UPDATE depending on what we are to do
* @return boolean True for success, false for failure.
*/
function simple_write_resource( $path, $caldav_data, $put_action_type, $write_action_log = false ) {

  $etag = md5($caldav_data);
  $ic = new iCalComponent( $caldav_data );

  /**
  * We pull the user_no & collection_id out of the collection table, based on the resource path
  */
  $collection_path = preg_replace( '#/[^/]*$#', '/', $path );
  $qry = new AwlQuery( 'SELECT user_no, collection_id FROM collection WHERE dav_name = :dav_name ', array( ':dav_name' => $collection_path ) );
  if ( $qry->Exec('PUT',__LINE__,__FILE__) && $qry->rows() == 1 ) {
    $collection = $qry->Fetch();
    $user_no = $collection->user_no;

    return write_resource( $user_no, $path, $caldav_data, $collection->collection_id, $user_no, $etag, $ic, $put_action_type, false, $write_action_log );
  }
  return false;
}
