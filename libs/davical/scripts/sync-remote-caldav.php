#!/usr/bin/php
<?php

if ( @file_exists('../../awl/inc/AWLUtilities.php') ) {
  set_include_path('../inc:../htdocs:../../awl/inc');
}
else if ( @file_exists('../awl/inc/AWLUtilities.php') ) {
  set_include_path('inc:htdocs:../awl/inc:.');
}
else {
  set_include_path('../inc:../htdocs:/usr/share/awl/inc');
}
include('always.php');
require_once('AwlQuery.php');
require_once('caldav-PUT-functions.php');

include('caldav-client-v2.php');

/**
* Call with something like e.g.:
*
* scripts/sync-remote-caldav.php -U andrew@example.net -p 53cret -u https://www.google.com/calendar/dav/andrew@example.net/events -c /andrew/gsync/
*
* Optionally also:
*   Add '-a' to sync everything, rather than checking if getctag has changed. (DON'T USE THIS)
*   Add '-w remote' to make the remote end win arguments when there is a change to the same event in both places.
*   Add '-i' to only sync inwards, from the remote server into DAViCal
*   Add '-o' to only sync outwards, from DAViCal to the remote server
*
* Note that this script is ugly (though it works, at least with Google) and should really be rewritten
* with better structuring.  As it is it's more like one long stream of consciousness novel.
*
* One bug that would be better solved through restructuring is that if you supply -a and have changed an
* event locally, it will be overwritten by the remote server's copy while we then overwrite the remote
* server with our version!  These will then end up swapping each time thereafter in all likelihood...
* Recommendation: don't use '-a', except possibly for the very first sync (but why then, even?)
*
* Other improvements would be to not use command-line parameters, but a configuration file.
*/


$args = (object) null;
$args->sync_all = false;          // Back to basics and sync everything into one mess
$args->local_changes_win = true;  // If true, and something has changed at both places, our local update will overwrite the remote

$args->sync_in  = false;    // If true, remote changes will be applied locally
$args->sync_out = false;    // If true, local changes will be applied remotely

$args->cache_directory = '.sync-cache';

function parse_arguments() {
  global $args;

  $opts = getopt( 'u:U:p:c:w:ioa' );
  foreach( $opts AS $k => $v ) {
    switch( $k ) {
      case 'u':   $args->url  = $v;  break;
      case 'U':   $args->user = $v;  break;
      case 'p':   $args->pass = $v;  break;
      case 'a':   $args->sync_all = true;  break;
      case 'c':   $args->local_collection_path = $v;  break;
      case 'w':   $args->local_changes_win = (strtolower($v) != 'remote' );   break;
      case 'i':   $args->sync_in  = true;  break;
      case 'o':   $args->sync_out = true;  break;
      case 'h':   usage();  break;
      default:    $args->{$k} = $v;
    }
  }
}

function usage() {
  echo <<<EOUSAGE
Usage:
   sync-remote-caldav.php -u <url> -U <user> -p <password> -c <path> [...options]

Required Options:
  -u <remote_url>  The URL of the caldav collection on the remote server.
  -U <remote_user> The username on the remote server to connect as
  -p <remote_pass> The password for the remote server
  -c <local_path>  The path to the local collection, e.g. /username/home/ note that
                   any part of the local URL up to and including 'caldav.php' should
                   be omitted.

Other Options:
  -w remote        If set to 'remote' and changes are seen in both calendars, the remote
                   server will 'win' the argument.  Any other value and the default will
                   apply in that the changes on the local server will prevail.
  -i               Sync inwards only.
  -o               Sync outwards only

EOUSAGE;

  exit(0);
}

parse_arguments();

if ( !isset($args->url) ) usage();
if ( !isset($args->user) ) usage();
if ( !isset($args->pass) ) usage();
if ( !isset($args->local_collection_path) ) usage();


if ( !preg_match('{/$}', $args->local_collection_path) ) $args->local_collection_path .= '/';
if ( !preg_match('{^/[^/]+/[^/]+/$}', $args->local_collection_path) ) {
  printf( "The local URL of '%s' looks wrong.  It should be formed as '/username/collection/'\n", $args->local_collection_path );
}

if ( !preg_match('{/$}', $args->url) ) $args->url .= '/';

$caldav = new CalDAVClient( $args->url, $args->user, $args->pass );

// // This will find the 'Principal URL' which we can query for user-related
// // properties.
// $principal_url = $caldav->FindPrincipal($args->url);
//
// // This will find the 'Calendar Home URL' which will be the folder(s) which
// // contain all of the user's calendars
// $calendar_home_set = $caldav->FindCalendarHome();
//
// $calendar = null;
//
// // This will go through the calendar_home_set and find all of the users
// // calendars on the remote server.
// $calendars = $caldav->FindCalendars();
// if ( count($calendars) < 1 ) {
//   printf( "No calendars found based on '%s'\n", $args->url );
// }
//
// // Now we have all of the remote calendars, we will look for the URL that
// // matches what we were originally supplied.  While this seems laborious
// // because we already have it, it means we could provide a match in some
// // other way (e.g. on displayname) and we could also present a list to
// // the user which is built from following the above process.
// foreach( $calendars AS $k => $a_calendar ) {
//   if ( $a_calendar->url == $args->url ) $calendar = $a_calendar;
// }
// if ( !isset($calendar) ) $calendar = $calendars[0];

// In reality we could have omitted all of the above parts, If we really do
// know the correct URL at the start.

// Everything now will be at our calendar URL
$caldav->SetCalendar($args->url);

$calendar = $caldav->GetCalendarDetails();

printf( "Remote calendar '%s' is at %s\n", $calendar->displayname, $calendar->url );

// Generate a consistent filename for our synchronisation cache
if ( ! file_exists($args->cache_directory) && ! is_dir($args->cache_directory) ) {
  mkdir($args->cache_directory, 0750 );  // Not incredibly sensitive file contents - URLs and ETags
}
$sync_cache_filename = $args->cache_directory .'/'. md5($args->user . $calendar->url);

// Do we just need to sync everything across and overwrite all the local stuff?
$sync_all = ( !file_exists($sync_cache_filename) || $args->sync_all);
$sync_in  = false;
$sync_out = false;
if ( $args->sync_in  || !$args->sync_out ) $sync_in  = true;
if ( $args->sync_out || !$args->sync_in  ) $sync_out = true;


if ( ! $sync_all ) {
  /**
  * Read a structure out of the cache file containing:
  *   server_getctag - A collection tag (string) from the remote server
  *   local_getctag  - A collection tag (string) from the local DB
  *   server_etags   - An array of event tags (strings) keyed on filename, from the server
  *   local_etags    - An array of event tags (strings) keyed on filename, from local DAViCal
  */
  $cache = unserialize( file_get_contents($sync_cache_filename) );

  // First compare the ctag for the calendar
  if ( isset($cache) && isset($cache->server_ctag) && isset($calendar->getctag) && $calendar->getctag == $cache->server_ctag ) {
    printf( 'No changes to remote calendar "%s" at "%s"'."\n", $calendar->displayname, $calendar->url );
    $sync_in = false;
  }

  $qry = new AwlQuery('SELECT collection_id, dav_displayname AS displayname, dav_etag AS getctag FROM collection WHERE dav_name = :collection_dav_name', array(':collection_dav_name' => $args->local_collection_path) );
  if ( $qry->Exec('sync-pull',__LINE__,__FILE__) && $qry->rows() > 0 ) {
    $local_calendar = $qry->Fetch();

    // First compare the ctag for the calendar
    if ( isset($cache) && isset($cache->local_ctag) && isset($local_calendar->getctag) && $local_calendar->getctag == $cache->local_ctag ) {
      printf( 'No changes to local calendar "%s" at "%s"'."\n", $local_calendar->displayname, $args->local_collection_path );
      $sync_out = false;
    }
  }
}
if ( !isset($cache) || !isset($cache->server_ctag) ) $sync_all = true;

$remote_event_prefix = preg_replace('{^https?://[^/]+/}', '/', $calendar->url);
$insert_urls = array();
$update_urls = array();
$local_delete_urls = array();
$server_delete_urls = array();
$push_urls = array();
$push_events = array();

$newcache = (object) array( 'server_ctag' => $calendar->getctag,
                            'local_ctag' => (isset($local_calendar->getctag) ? $local_calendar->getctag : null),
                            'server_etags' => array(), 'local_etags' => array() );
if ( isset($cache) ) {
  if ( !$sync_in && isset($cache->server_etags) ) $newcache->server_etags = $cache->server_etags;
  if ( !$sync_out && isset($cache->local_etags) ) $newcache->local_etags  = $cache->local_etags;
}

if ( $sync_in ) {
  // So it seems we do need to sync.  We now need to check each individual event
  // which might have changed, so we pull a list of event etags from the server.
  $server_etags = $caldav->GetCollectionETags();
  // printf( "\nGetCollectionEtags Response:\n%s\n", $caldav->GetXmlResponse() );
  // print_r( $server_etags );



  if ( $sync_all ) {
    // The easy case.  Sync them all, delete nothing
    $insert_urls = $server_etags;
    foreach( $server_etags AS $href => $etag ) {
      $fname = preg_replace('{^.*/}', '', $href);
      $newcache->server_etags[$fname] = $etag;
      printf( 'Need to pull "%s"'."\n", $href );
    }
  }
  else {
    // Only sync the ones where the etag has changed.  Delete any that are no
    // longer present at the remote end.
    foreach( $server_etags AS $href => $etag ) {
      $fname = preg_replace('{^.*/}', '', $href);
      $newcache->server_etags[$fname] = $etag;
      if ( isset($cache->server_etags[$fname]) ) {
        $cache_etag = $cache->server_etags[$fname];
        unset($cache->server_etags[$fname]);
        if ( $cache_etag == $etag ) continue;
        $update_urls[$href] = 1;
        printf( 'Need to pull to update "%s"'."\n", $href );
      }
      else {
        $insert_urls[$href] = 1;
        printf( 'Need to pull to insert "%s"'."\n", $href );
      }
    }
    $local_delete_urls = $cache->server_etags;
  }


  // Fetch the calendar data
  $events = $caldav->CalendarMultiget( array_merge( array_keys($insert_urls), array_keys($update_urls)) );
  // printf( "\nCalendarMultiget Request:\n%s\n Response:\n%s\n", $caldav->GetXmlRequest(), $caldav->GetXmlResponse() );
  // print_r($events);

  printf( "Fetched %d possible changes.\n", count($events) );

  if ( !preg_match( '{/$}', $remote_event_prefix) ) $remote_event_prefix .= '/';
}


/**
* This is a fairly tricky bit.  We find local changes and check to see if they
* are collisions.  We actually have to check the data for a collision, since the
* real data may in fact be identical, e.g.  because of the -a option or something.
*
* Once we have verified that the target objects actually *are* different, then:
*    Change vs No change      => The change is propagated to the other server
*    DELETE vs UPDATE/INSERT  => DELETE always loses
*    UPDATE vs UPDATE => pick the winner according to arbitrary setting (see top of file)
*    INSERT vs INSERT => pick the winner according to arbitrary setting (see top of file)  v. unlikely
*/
// Read the local ETag from DAViCal.
$qry = new AwlQuery( 'SELECT dav_name, dav_etag, caldav_data FROM caldav_data WHERE collection_id = (SELECT collection_id FROM collection WHERE dav_name = :collection_dav_name)',
                    array(':collection_dav_name' => $args->local_collection_path) );
if ( $qry->Exec('sync-pull',__LINE__,__FILE__) && $qry->rows() > 0 ) {
  $local_etags = array();
  while( $local = $qry->Fetch() ) {
    $fname = preg_replace('{^.*/}', '', $local->dav_name);
    $newcache->local_etags[$fname] = $local->dav_etag;
    if ( !$sync_all && isset($cache->local_etags[$fname]) ) {
      $cache_etag = $cache->local_etags[$fname];
      unset($cache->local_etags[$fname]);
      if ( $cache_etag == $local->dav_etag ) continue;
    }
    if ( isset($insert_urls[$remote_event_prefix.$fname]) ) {
      if ( $local->caldav_data == $events[$remote_event_prefix.$fname] ) {
        // Not actually changed.  Ignore it at *both* ends!
        printf( "Not inserting '%s' (same at both ends).\n", $fname );
        unset($insert_urls[$remote_event_prefix.$fname]);
        continue;
      }
      unset($insert_urls[$remote_event_prefix.$fname]);
      if ( ! $args->local_changes_win ) {
        printf( "Remote change to '%s' will overwrite local.\n", $fname );
        $update_urls[$remote_event_prefix.$fname] = 1;
        continue;
      }
      printf( "Local change to '%s' will overwrite remote.\n", $fname );
    }
    else if ( isset($update_urls[$remote_event_prefix.$fname]) ) {
      if ( $local->caldav_data == $events[$remote_event_prefix.$fname] ) {
        // Not actually changed.  Ignore it at *both* ends!
        printf( "Not updating '%s' (same at both ends).\n", $fname );
        unset($update_urls[$remote_event_prefix.$fname]);
        continue;
      }
      if ( $args->local_changes_win ) {
        unset($update_urls[$remote_event_prefix.$fname]);
        printf( "Local change to '%s' will overwrite remote.\n", $fname );
      }
      else {
        printf( "Remote change to '%s' will overwrite local.\n", $fname );
        continue;
      }
    }
    if ( $sync_out ) {
      $push_urls[$fname] = (isset($cache->server_etags[$remote_event_prefix.$fname]) ? $cache->server_etags[$remote_event_prefix.$fname] : '*');
      $push_events[$fname] = $local->caldav_data;
      printf( "Need to push '%s'\n", $local->dav_name );
    }
    else {
      printf( "Would push '%s' but not syncing out.\n", $local->dav_name );
    }
  }

  if ( !$sync_all ) {
    foreach( $cache->local_etags AS $href => $etag ) {
      $fname = preg_replace('{^.*/}', '', $href);

      if (     !isset($insert_urls[$remote_event_prefix.$fname])
            && !isset($update_urls[$remote_event_prefix.$fname])
            && isset($cache->server_etags[$fname]) ) {
        $server_delete_urls[$fname] = $cache->server_etags[$remote_event_prefix.$fname];
        printf( "Need to delete remote '%s'.\n", $fname );
      }
    }
  }
}

printf( "Push: Found %d local changes to push & %d local deletions to push.\n", count($push_urls), count($server_delete_urls) );
printf( "Pull: Found %d creates, %d updates and %d deletions to apply locally.\n", count($insert_urls), count($update_urls), count($local_delete_urls) );

if ( $sync_in ) {
  printf( "Sync in\n" );
  // Delete any local events which have been removed from the remote server
  foreach( $local_delete_urls AS $href => $v ) {
    $fname = preg_replace('{^.*/}', '', $href);
    $local_fname = $args->local_collection_path . $fname;
    $qry = new AwlQuery('DELETE FROM caldav_data WHERE caldav_type!=\'VTODO\' and dav_name = :dav_name', array( ':dav_name' => $local_fname ) );
    $qry->Exec('sync_pull',__LINE__,__FILE__);
    unset($newcache->local_etags[$fname]);
  }


  unset($c->dbg['querystring']);
  // Update the local system with events that are new or updated on the remote server
  foreach( $events AS $href => $event ) {
    // Do what we need to write $v into the local calendar we are syncing to
    // at the
    $fname = preg_replace('{^.*/}', '', $href);
    $local_fname = $args->local_collection_path . $fname;
    simple_write_resource( $local_fname, $event, (isset($insert_urls[$href]) ? 'INSERT' : 'UPDATE') );
    $newcache->local_etags[$fname] = md5($event);
  }

  $qry = new AwlQuery('SELECT collection_id, dav_displayname AS displayname, dav_etag AS getctag FROM collection WHERE dav_name = :collection_dav_name', array(':collection_dav_name' => $args->local_collection_path) );
  if ( $qry->Exec('sync-pull',__LINE__,__FILE__) && $qry->rows() > 0 ) {
    $local_calendar = $qry->Fetch();
    if ( isset($local_calendar->getctag) ) $newcache->local_ctag = $local_calendar->getctag;
  }
}

if ( $sync_out ) {
  printf( "Sync out\n" );
  // Delete any remote events which have been removed from the local server
  foreach( $server_delete_urls AS $href => $etag ) {
    $caldav->DoDELETERequest( $args->url . $href, $etag );
    printf( "\nDELETE Response:\n%s\n", $caldav->GetResponseHeaders() );
    unset($newcache->server_etags[$fname]);
  }

  // Push locally updated events to the remote server
  foreach( $push_urls AS $href => $etag ) {
    $new_etag = $caldav->DoPUTRequest( $args->url . $href, $push_events[$href], $etag );
    printf( "\nPUT:\n%s\nResponse:\n%s\n", $caldav->GetHttpRequest(), $caldav->GetResponseHeaders() );
    if ( !isset($new_etag) || $new_etag == '' ) {
      if ( preg_match( '{^Location:\s+.*/([^/]+)$}im', $caldav->GetResponseHeaders(), $matches ) ) {
        /** How annoying.  It seems the other server renamed the event on PUT so we move the local copy to match their name */
        $new_href = preg_replace( '{\r?\n.*$}s', '', $matches[1]);
        $qry = new AwlQuery('UPDATE caldav_data SET dav_name = :new_dav_name WHERE dav_name = :old_dav_name',
                        array( ':new_dav_name' => $args->local_collection_path . $new_href,
                               ':old_dav_name' => $args->local_collection_path . $href ) );
        $qry->Exec('sync_pull',__LINE__,__FILE__);
        $new_cache->local_etags[$new_href] = $new_cache->local_etags[$href];
        unset($new_cache->local_etags[$href]);
        $href = $new_href; 
        $caldav->DoHEADRequest( $args->url . $href );
        if ( preg_match( '{^Etag:\s+"([^"]*)"\s*$}im', $caldav->httpResponseHeaders, $matches ) ) $new_etag = $matches[1];
        printf( "\nHEAD:\n%s\nResponse:\n%s\n", $caldav->GetHttpRequest(), $caldav->GetResponseHeaders() );
      }
      if ( !isset($new_etag) || $new_etag == '' ) {
        printf( "Unable to retrieve ETag for new event on remote server. Forcing bad ctag.");
        $force_ctag = 'Naughty server!';
      }
    }
    $newcache->server_etags[$href] = $new_etag;
  }

  $calendar = $caldav->GetCalendarDetails();
  if ( isset($force_ctag) )              $newcache->server_ctag = $force_ctag;
  else if ( isset($calendar->getctag) )  $newcache->server_ctag = $calendar->getctag;
}

// Now (re)write the cache file reflecting the current state.
printf( "Rewriting cache file.\n" );
$cache_file = fopen($sync_cache_filename, 'w');
fwrite( $cache_file, serialize($newcache) );
fclose($cache_file);

print_r($newcache);

printf( "Completed.\n" );

