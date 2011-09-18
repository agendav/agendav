#!/usr/bin/php
<?php

set_include_path('.');

function add_path_for( $include, $paths ) {
  foreach( $paths AS $test_path ) {
    if ( @file_exists($test_path.'/'.$include) ) {
      set_include_path( $test_path. PATH_SEPARATOR. get_include_path());
      return;
    }
  }
}

add_path_for('AWLUtilities.php', array( '../../awl/inc' , '../awl/inc'
      , '../../../awl/inc' , '/usr/share/awl/inc' , '/usr/local/share/awl/inc') );
add_path_for('caldav-client-v2.php', array( '../../davical/inc' , '../davical/inc'
      , '../../../davical/inc' , '/usr/share/davical/inc' , '/usr/local/share/davical/inc') );
add_path_for('always.php', array( 'scripts' ) );
add_path_for('sync-config.php', array( 'config' ) );


require('always.php');
require_once('AwlQuery.php');
require_once('RRule-v2.php');
require_once('vComponent.php');

/**
* Call with something like e.g.:
*
* scripts/refresh_alarms.php -p P1800D -f P1D
*
*/

$args = (object) array();
$args->debug = false;
$args->set_last = false;

$args->future = 'P2000D';
$args->near_past = 'P1D';
$args->far_past = 'P1200D';
$debugging = null;

function parse_arguments() {
  global $args;

  $opts = getopt( 'f:p:n:d:lh' );
  foreach( $opts AS $k => $v ) {
    switch( $k ) {
      case 'f':   $args->future = $v;  break;
      case 'n':   $args->near_past = $v;  break;
      case 'p':   $args->far_past = $v;  break;
      case 'd':   $args->debug = true;  $debugging = explode(',',$v); break;
      case 'l':   $args->set_last = true; break;
      case 'h':   usage();  break;
      default:    $args->{$k} = $v;
    }
  }
}

function usage() {
  echo <<<EOUSAGE
Usage:
   refresh-alarms.php [-d]

  -n <duration>    Near past period to skip for finding last instances: default 1 days ('P1D')
  -p <duration>    Far past period to examine for finding last instances: default ~3 years ('P1200D')
  -f <duration>    Future period to consider for finding future alarms: default ~5 years ('P2000D')

  -l               Try to set the 'last' alarm date in historical alarms

  -d               Enable debugging

EOUSAGE;

  exit(0);
}

parse_arguments();

if ( $args->debug && is_array($debugging )) {
  foreach( $debugging AS $v ) {
    $c->dbg[$v] = 1;
  }
}
$args->near_past = '-' .  $args->near_past;
$args->far_past = '-' . $args->far_past;


/**
* Essentially what we are doing is:
*
UPDATE calendar_alarm
  SET next_trigger = (SELECT rrule_event_instances_range(
                        dtstart + icalendar_interval_to_SQL(replace(trigger,'TRIGGER:','')),
                        rrule,
                        current_timestamp, current_timestamp + '2 days'::interval,
                        1)
                     LIMIT 1)
 FROM calendar_item
WHERE calendar_alarm.dav_id = calendar_item.dav_id
  AND next_trigger is null
  AND rrule IS NOT NULL

*/
$expand_range_start = new RepeatRuleDateTime(gmdate('Ymd\THis\Z'));
$expand_range_end   = new RepeatRuleDateTime(gmdate('Ymd\THis\Z'));
$expand_range_end->modify( $args->future );



$earliest   = clone($expand_range_start);
$earliest->modify( $args->near_past );

$sql = 'SELECT * FROM calendar_alarm JOIN calendar_item USING (dav_id) JOIN caldav_data USING (dav_id) WHERE rrule IS NOT NULL AND next_trigger IS NULL';
if ( $args->debug ) printf( "%s\n", $sql );
$qry = new AwlQuery( $sql );
if ( $qry->Exec() && $qry->rows() ) {
  while( $alarm = $qry->Fetch() ) {
    if ( $args->debug ) printf( "Processing alarm for '%s' based on '%s','%s', '%s'\n",
                          $alarm->dav_name, $alarm->dtstart, $alarm->rrule, $alarm->trigger );
    $ic = new vComponent( $alarm->caldav_data );
    $expanded = expand_event_instances( $ic, $earliest, $expand_range_end );
    $expanded->MaskComponents( array( 'VEVENT', 'VTODO', 'VJOURNAL' ) );
    $instances = $expanded->GetComponents();

    $trigger = new vProperty( $alarm->trigger );
    $related = $trigger->GetParameterValue('RELATED');

    $first = new RepeatRuleDateTime($alarm->dtstart);
    $first->modify( $trigger->Value() );
    $next = null;
    $last = null;
    foreach( $instances AS $k => $component ) {
      $when = new RepeatRuleDateTime( $component->GetPValue('DTSTART') ); // a UTC value
      if ( $related == 'END' ) {
        $when->modify( $component->GetPValue('DURATION') );
      }
      $when->modify( $trigger->Value() );
      if ( $when > $expand_range_start && $when < $expand_range_end && (!isset($next) || $when < $next) ) {
        $next = clone($when);
      }
      if ( $args->set_last && (!isset($last) || $when > $last) ) {
        $last = clone($when);
      }
    }
    if ( isset($next) && $next < $expand_range_end ) {
      if ( $args->debug ) printf( "Found next alarm instance on '%s'\n", $next->UTC() );
      $sql = 'UPDATE calendar_alarm SET next_trigger = :next WHERE dav_id = :id AND component = :component';
      $update = new AwlQuery( $sql, array( ':next' => $next->UTC(), ':id' => $alarm->dav_id, ':component' => $alarm->component ) );
      $update->Exec('refresh-alarms', __LINE__, __FILE__ );
    }
    else if ( $args->set_last && isset($last) && $last < $earliest ) {
      if ( $args->debug ) printf( "Found past final alarm instance on '%s'\n", $last->UTC() );
      $sql = 'UPDATE calendar_alarm SET next_trigger = :last WHERE dav_id = :id AND component = :component';
      $update = new AwlQuery( $sql, array( ':last' => $last->UTC(), ':id' => $alarm->dav_id, ':component' => $alarm->component ) );
      $update->Exec('refresh-alarms', __LINE__, __FILE__ );
    }
  }
}

