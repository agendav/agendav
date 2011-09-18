<?php
include_once('DAVResource.php');

class WritableCollection extends DAVResource {

  /**
   * Writes the data to a member in the collection and returns the segment_name of the resource in our internal namespace. 
   * @param $data iCalendar The resource to be written.
   * @param $create_resource boolean True if this is a new resource.
   * @param $segment_name The name of the resource within the collection.
   */
  function WriteCalendarMember( $data, $create_resource, $segment_name = null ) {
    if ( !$this->IsSchedulingCollection() && !$this->IsCalendar() ) return false;

// function write_resource( $user_no, $path, $caldav_data, $collection_id, $author, $etag, $ic, $put_action_type, $caldav_context, $log_action=true, $weak_etag=null ) {
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
  $params = array(
      ':dav_name' => $path,
      ':user_no' => $user_no,
      ':etag' => $etag,
      ':dav_data' => $caldav_data,
      ':caldav_type' => $resource_type,
      ':session_user' => $author,
      ':weak_etag' => $weak_etag
  );
  if ( $put_action_type == 'INSERT' ) {
    create_scheduling_requests($vcal);
    $sql = 'INSERT INTO caldav_data ( user_no, dav_name, dav_etag, caldav_data, caldav_type, logged_user, created, modified, collection_id, weak_etag )
            VALUES( :user_no, :dav_name, :etag, :dav_data, :caldav_type, :session_user, current_timestamp, current_timestamp, :collection_id, :weak_etag )';
    $params[':collection_id'] = $collection_id;
  }
  else {
    update_scheduling_requests($vcal);
    $sql = 'UPDATE caldav_data SET caldav_data=:dav_data, dav_etag=:etag, caldav_type=:caldav_type, logged_user=:session_user,
            modified=current_timestamp, weak_etag=:weak_etag WHERE user_no=:user_no AND dav_name=:dav_name';
  }
  if ( !$qry->QDo($sql,$params) ) {
    rollback_on_error( $caldav_context, $user_no, $path);
    return false;
  }

  $qry->QDo('SELECT dav_id FROM caldav_data WHERE dav_name = :dav_name ', array(':dav_name' => $path));
  if ( $qry->rows() == 1 && $row = $qry->Fetch() ) {
    $dav_id = $row->dav_id;
  }


  $calitem_params = array(
      ':dav_name' => $path,
      ':user_no' => $user_no,
      ':etag' => $etag
  );
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
      $duration = preg_replace( '#[PT]#', ' ', $first->GetPValue('DURATION') );
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
  if ( !isset($last_modified) || $last_modified == '' ) {
    $last_modified = gmdate( 'Ymd\THis\Z' );
  }
  $calitem_params[':modified'] = $last_modified;

  $dtstamp = $first->GetPValue('DTSTAMP');
  if ( !isset($dtstamp) || $dtstamp == '' ) {
    $dtstamp = $last_modified;
  }
  $calitem_params[':dtstamp'] = $dtstamp;

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

  $created = $first->GetPValue('CREATED');
  if ( $created == '00001231T000000Z' ) $created = '20001231T000000Z';
  $calitem_params[':created'] = $created;

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
  if ( $put_action_type == 'INSERT' ) {
    $sql = <<<EOSQL
INSERT INTO calendar_item (user_no, dav_name, dav_id, dav_etag, uid, dtstamp,
                dtstart, dtend, summary, location, class, transp,
                description, rrule, tz_id, last_modified, url, priority,
                created, due, percent_complete, status, collection_id )
   VALUES ( :user_no, :dav_name, currval('dav_id_seq'), :etag, :uid, :dtstamp,
                :dtstart, $dtend, :summary, :location, :class, :transp,
                :description, :rrule, :tzid, :modified, :url, :priority,
                :created, :due, :percent_complete, :status, $collection_id )
EOSQL;
    $sync_change = 201;
  }
  else {
    $sql = <<<EOSQL
UPDATE calendar_item SET dav_etag=:etag, uid=:uid, dtstamp=:dtstamp,
                dtstart=:dtstart, dtend=$dtend, summary=:summary, location=:location, class=:class, transp=:transp,
                description=:description, rrule=:rrule, tz_id=:tzid, last_modified=:modified, url=:url, priority=:priority,
                created=:created, due=:due, percent_complete=:percent_complete, status=:status
       WHERE user_no=:user_no AND dav_name=:dav_name
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

    
    return $segment_name;
  }
  
  /**
   * Writes the data to a member in the collection and returns the segment_name of the resource in our internal namespace. 
   * @param $data mixed The resource to be written.
   * @param $create_resource boolean True if this is a new resource.
   * @param $segment_name The name of the resource within the collection.
   */
  function WriteMember( $data, $create_resource, $segment_name = null ) {
    if ( ! $this->IsCollection() ) return false;
    if ( is_object($data) ) {
      if ( gettype($data) == 'iCalendar' ) return $this->WriteCalendarMember($data,$create_resource,$segment_name);
      else if ( gettype($data) == 'VCard' ) return $this->WriteAddressbookMember($data,$create_resource,$segment_name);
    }
    
    return $segment_name;
  }

}
