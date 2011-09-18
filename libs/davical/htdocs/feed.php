<?php
/**
 * A script for returning a feed (currently Atom) of recent changes to a calendar collection
 * @author Leho Kraav <leho@kraav.com>
 * @author Andrew McMillan <andrew@morphoss.com>
 * @license GPL v2 or later
 */
require_once("./always.php");
dbg_error_log( "feed", " User agent: %s", ((isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "Unfortunately Mulberry and Chandler don't send a 'User-agent' header with their requests :-(")) );
dbg_log_array( "headers", '_SERVER', $_SERVER, true );

require_once("HTTPAuthSession.php");
$session = new HTTPAuthSession();

require_once('CalDAVRequest.php');
$request = new CalDAVRequest();

/**
 * Function for creating anchor links out of plain text.
 * Source: http://stackoverflow.com/questions/1960461/convert-plain-text-hyperlinks-into-html-hyperlinks-in-php
 */
function hyperlink( $text ) {
  return preg_replace( '@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" target="_blank">$1</a>', htmlspecialchars($text) );
}

function caldav_get_feed( $request ) {
  global $c;

  dbg_error_log("feed", "GET method handler");

  require_once("vComponent.php");
  require_once("DAVResource.php");

  $collection = new DAVResource($request->path);
  $collection->NeedPrivilege( array('DAV::read') );

  if ( ! $collection->Exists() ) {
    $request->DoResponse( 404, translate("Resource Not Found.") );
  }

  if ( $collection->IsCollection() ) {
    if ( ! $collection->IsCalendar() && !(isset($c->get_includes_subcollections) && $c->get_includes_subcollections) ) {
      $request->DoResponse( 405, translate("Feeds are only supported for calendars at present.") );
    }
    
    $principal = $collection->GetProperty('principal');
    
    /**
     * The CalDAV specification does not define GET on a collection, but typically this is
     * used as a .ics download for the whole collection, which is what we do also.
     */
    $sql = 'SELECT caldav_data, caldav_type, caldav_data.user_no, caldav_data.dav_name,';
    $sql .= ' caldav_data.modified, caldav_data.created, ';
    $sql .= ' summary, dtstart, dtend, calendar_item.description ';
    $sql .= ' FROM collection INNER JOIN caldav_data USING(collection_id) INNER JOIN calendar_item USING ( dav_id ) WHERE ';
    if ( isset($c->get_includes_subcollections) && $c->get_includes_subcollections ) {
      $sql .= ' (collection.dav_name ~ :path_match ';
      $sql .= ' OR collection.collection_id IN (SELECT bound_source_id FROM dav_binding WHERE dav_binding.dav_name ~ :path_match)) ';
      $params = array( ':path_match' => '^'.$request->path );
    }
    else {
      $sql .= ' caldav_data.collection_id = :collection_id ';
      $params = array( ':collection_id' => $collection->resource_id() );
    }
    $sql .= ' ORDER BY caldav_data.created DESC';
    $sql .= ' LIMIT '.(isset($c->feed_item_limit) ? $c->feed_item_limit : 15);
    $qry = new AwlQuery( $sql, $params );
    if ( !$qry->Exec("GET",__LINE__,__FILE__) ) {
      $request->DoResponse( 500, translate("Database Error") );
    }

    /**
     * Here we are constructing the feed response for this collection, including
     * the timezones that are referred to by the events we have selected.
     * Library used: http://framework.zend.com/manual/en/zend.feed.writer.html
     */
    require_once('AtomFeed.php');
    $feed = new AtomFeed();

    $feed->setTitle('DAViCal Atom Feed: '. $collection->GetProperty('displayname'));
    $url = $c->protocol_server_port . $collection->url();
    $url = preg_replace( '{/$}', '.ics', $url);
    $feed->setLink($url);
    $feed->setFeedLink($c->protocol_server_port_script . $request->path, 'atom');
    $feed->addAuthor(array(
    			'name'  => $principal->GetProperty('displayname'),
    			'email' => $principal->GetProperty('email'),
    			'uri'   => $c->protocol_server_port . $principal->url(),
    ));
    $feed_description = $collection->GetProperty('description');
    if ( isset($feed_description) && $feed_description != '' ) $feed->setDescription($feed_description);

    require_once('RRule-v2.php');

    $need_zones = array();
    $timezones = array();
    while( $event = $qry->Fetch() ) {
      if ( $event->caldav_type != 'VEVENT' && $event->caldav_type != 'VTODO' && $event->caldav_type != 'VJOURNAL') {
        dbg_error_log( 'feed', 'Skipping peculiar "%s" component in VCALENDAR', $event->caldav_type );
        continue;
      }
      $is_todo = ($event->caldav_type == 'VTODO');

      $ical = new vComponent( $event->caldav_data );
      $event_data = $ical->GetComponents('VTIMEZONE', false);
      
      $item = $feed->createEntry();
      $item->setId( $c->protocol_server_port_script . ConstructURL($event->dav_name) );

      $dt_created = new RepeatRuleDateTime( $event->created );
      $item->setDateCreated( $dt_created->epoch() );

      $dt_modified = new RepeatRuleDateTime( $event->modified );
      $item->setDateModified( $dt_modified->epoch() );

      $summary = $event->summary;
      $p_title = ($summary != '' ? $summary : translate('No summary'));
      if ( $is_todo ) $p_title = "TODO: " . $p_title;
      $item->setTitle($p_title);

      $content = "";

      $dt_start = new RepeatRuleDateTime($event->dtstart);
      if  ( $dt_start != null ) {
        $p_time = '<strong>' . translate('Time') . ':</strong> ' . strftime(translate('%F %T'), $dt_start->epoch());

        $dt_end = new RepeatRuleDateTime($event->dtend);
        if  ( $dt_end != null ) {
          $p_time .= ' - ' . ( $dt_end->AsDate() == $dt_start->AsDate()
                                   ? strftime(translate('%T'), $dt_end->epoch())
                                   : strftime(translate('%F %T'), $dt_end->epoch())
                              );
        }
        $content .= $p_time;
      }

      $p_location = $event_data[0]->GetProperty('LOCATION');
      if ( $p_location != null )
      $content .= '<br />'
      .'<strong>' . translate('Location') . '</strong>: ' . hyperlink($p_location->Value());

      $p_attach = $event_data[0]->GetProperty('ATTACH');
      if ( $p_attach != null )
      $content .= '<br />'
      .'<strong>' . translate('Attachment') . '</strong>: ' . hyperlink($p_attach->Value());

      $p_url = $event_data[0]->GetProperty('URL');
      if ( $p_url != null )
      $content .= '<br />'
      .'<strong>' . translate('URL') . '</strong>: ' . hyperlink($p_url->Value());

      $p_cat = $event_data[0]->GetProperty('CATEGORIES');
      if ( $p_cat != null ) {
        $content .= '<br />' .'<strong>' . translate('Categories') . '</strong>: ' . $p_cat->Value();
        $categories = explode(',',$p_cat->Value());
        foreach( $categories AS $category ) {
          $item->addCategory( array('term' => trim($category)) );
        }
      }

      $p_description = $event->description;
      if ( $p_description != '' ) {
        $content .= '<br />'
        .'<br />'
        .'<strong>' . translate('Description') . '</strong>:<br />' . ( nl2br(hyperlink($p_description)) )
        ;
        $item->setDescription($p_description);
      }

      $item->setContent($content);
      $feed->addEntry($item);
      //break;
    }
    $last_modified = new RepeatRuleDateTime($collection->GetProperty('modified'));
    $feed->setDateModified($last_modified->epoch());
    $response = $feed->export('atom');
    header( 'Content-Length: '.strlen($response) );
    header( 'Etag: '.$collection->unique_tag() );
    $request->DoResponse( 200, ($request->method == 'HEAD' ? '' : $response), 'text/xml; charset="utf-8"' );
  }
}

if ( $request->method == 'GET' ) {
  caldav_get_feed( $request );
}
else {
  dbg_error_log( 'feed', 'Unhandled request method >>%s<<', $request->method );
  dbg_log_array( 'feed', '_SERVER', $_SERVER, true );
  dbg_error_log( 'feed', 'RAW: %s', str_replace("\n", '',str_replace("\r", '', $request->raw_post)) );
}

$request->DoResponse( 500, translate('The application program does not understand that request.') );

/* vim: set ts=2 sw=2 tw=0 :*/
