<?php
/**
* Class for parsing RRule and getting us the dates
*
* @package   awl
* @subpackage   caldav
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Morphoss Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

if ( !class_exists('DateTime') ) return;

$rrule_expand_limit = array(
  'YEARLY'  => array( 'bymonth' => 'expand', 'byweekno' => 'expand', 'byyearday' => 'expand', 'bymonthday' => 'expand',
                      'byday' => 'expand', 'byhour' => 'expand', 'byminute' => 'expand', 'bysecond' => 'expand' ),
  'MONTHLY' => array( 'bymonth' => 'limit', 'bymonthday' => 'expand',
                      'byday' => 'expand', 'byhour' => 'expand', 'byminute' => 'expand', 'bysecond' => 'expand' ),
  'WEEKLY'  => array( 'bymonth' => 'limit',
                      'byday' => 'expand', 'byhour' => 'expand', 'byminute' => 'expand', 'bysecond' => 'expand' ),
  'DAILY'   => array( 'bymonth' => 'limit', 'bymonthday' => 'limit',
                      'byday' => 'limit', 'byhour' => 'expand', 'byminute' => 'expand', 'bysecond' => 'expand' ),
  'HOURLY'  => array( 'bymonth' => 'limit', 'bymonthday' => 'limit',
                      'byday' => 'limit', 'byhour' => 'limit', 'byminute' => 'expand', 'bysecond' => 'expand' ),
  'MINUTELY'=> array( 'bymonth' => 'limit', 'bymonthday' => 'limit',
                      'byday' => 'limit', 'byhour' => 'limit', 'byminute' => 'limit', 'bysecond' => 'expand' ),
  'SECONDLY'=> array( 'bymonth' => 'limit', 'bymonthday' => 'limit',
                      'byday' => 'limit', 'byhour' => 'limit', 'byminute' => 'limit', 'bysecond' => 'limit' ),
);

$rrule_day_numbers = array( 'SU' => 0, 'MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6 );

define( 'DEBUG_RRULE', false );
// define( 'DEBUG_RRULE', true);

/**
* Wrap the DateTimeZone class to allow parsing some iCalendar TZID strangenesses
*/
class RepeatRuleTimeZone extends DateTimeZone {
  private $tz_defined;

  public function __construct($in_dtz = null) {
    $this->tz_defined = false;
    if ( !isset($in_dtz) ) return;

    $olson = olson_from_tzstring($in_dtz);
    if ( isset($olson) ) {
      try {
        parent::__construct($olson);
        $this->tz_defined = $olson;
      }
      catch (Exception $e) {
        dbg_error_log( 'ERROR', 'Could not handle timezone "%s" (%s) - will use floating time', $in_dtz, $olson );
        parent::__construct('UTC');
        $this->tz_defined = false;
      }
    }
    else {
      dbg_error_log( 'ERROR', 'Could not recognize timezone "%s" - will use floating time', $in_dtz );
      parent::__construct('UTC');
      $this->tz_defined = false;
    }
  }

  function tzid() {
    if ( $this->tz_defined === false ) return false;
    $tzid = $this->getName();
    if ( $tzid != 'UTC' ) return $tzid;
    return $this->tz_defined;
  }
}


/**
* Wrap the DateTime class to make it friendlier to passing in random strings from iCalendar
* objects, and especially the random stuff used to identify timezones.  We also add some
* utility methods and stuff too, in order to simplify some of the operations we need to do
* with dates.
*/
class RepeatRuleDateTime extends DateTime {
  // public static $Format = 'Y-m-d H:i:s';
  public static $Format = 'c';
  private static $UTCzone;
  private $tzid;
  private $is_date;

  public function __construct($date = null, $dtz = null) {
    if ( !isset(self::$UTCzone) ) self::$UTCzone = new RepeatRuleTimeZone('UTC');
    $this->is_date = false;
    if ( !isset($date) ) {
      $date = date('Ymd\THis');            
      // Floating
      $dtz = self::$UTCzone;
      $this->tzid = null;
    }

    if ( is_object($date) && method_exists($date,'GetParameterValue') ) {
      $tzid = $date->GetParameterValue('TZID');
      $actual_date = $date->Value();
      if ( isset($tzid) ) {
        $dtz = new RepeatRuleTimeZone($tzid);
        $this->tzid = $dtz->tzid();
      }
      else {
        $dtz = self::$UTCzone;
        if ( substr($actual_date,-1) == 'Z' ) {
          $this->tzid = 'UTC';
          $actual_date = substr($actual_date, 0, strlen($actual_date) - 1);          
        }
      }
      if ( strlen($actual_date) == 8 ) {
        // We allow dates without VALUE=DATE parameter, but we don't create them like that
        $this->is_date;
      }
      $date = $actual_date;
      if ( DEBUG_RRULE ) printf( "Date%s property%s: %s%s\n", ($this->is_date ? "" : "Time"),
              (isset($this->tzid) ? ' with timezone' : ''), $date,
              (isset($this->tzid) ? ' in '.$this->tzid : '') );
    }
    elseif (preg_match('/;TZID= ([^:;]+) (?: ;.* )? : ( \d{8} (?:T\d{6})? ) (Z)?/x', $date, $matches) ) {
      $date = $matches[2];
      $this->is_date = (strlen($date) == 8);
      if ( isset($matches[3]) && $matches[3] == 'Z' ) {
        $dtz = self::$UTCzone;
        $this->tzid = 'UTC';
      }
      else if ( isset($matches[1]) && $matches[1] != '' ) {
        $dtz = new RepeatRuleTimeZone($matches[1]);
        $this->tzid = $dtz->tzid();
      }
      else {
        $dtz = self::$UTCzone;
        $this->tzid = null;
      }
      if ( DEBUG_RRULE ) printf( "Date%s property%s: %s%s\n", ($this->is_date ? "" : "Time"),
              (isset($this->tzid) ? ' with timezone' : ''), $date,
              (isset($this->tzid) ? ' in '.$this->tzid : '') );
    }
    elseif ( ( $dtz === null || $dtz == '' )
             && preg_match('{;VALUE=DATE (?:;[^:]+) : ((?:[12]\d{3}) (?:0[1-9]|1[012]) (?:0[1-9]|[12]\d|3[01]Z?) )$}x', $date, $matches) ) {
      $this->is_date = true;
      $date = $matches[1];
      // Floating
      $dtz = self::$UTCzone;
      $this->tzid = null;
      if ( DEBUG_RRULE ) printf( "Floating Date value: %s\n", $date );
    } 
    elseif ( $dtz === null || $dtz == '' ) {
      $dtz = self::$UTCzone;
      if ( preg_match('/(\d{8}(T\d{6})?)(Z?)/', $date, $matches) ) {
        $date = $matches[1];
        $this->tzid = ( $matches[3] == 'Z' ? 'UTC' : null );
      }
      $this->is_date = (strlen($date) == 8 );
      if ( DEBUG_RRULE ) printf( "Date%s value with timezone: %s in %s\n", ($this->is_date?"":"Time"), $date, $this->tzid );
    }
    elseif ( is_string($dtz) ) {
      $dtz = new RepeatRuleTimeZone($dtz);
      $this->tzid = $dtz->tzid();
      $type = gettype($date);
      if ( DEBUG_RRULE ) printf( "Date%s $type with timezone: %s in %s\n", ($this->is_date?"":"Time"), $date, $this->tzid );
    }
    else {
      $this->tzid = $dtz->getName();
      $type = gettype($date);
      if ( DEBUG_RRULE ) printf( "Date%s $type with timezone: %s in %s\n", ($this->is_date?"":"Time"), $date, $this->tzid );
    }

    parent::__construct($date, $dtz);

    return $this;
  }


  public function __toString() {
    return (string)parent::format(self::$Format) . ' ' . parent::getTimeZone()->getName();
  }


  public function AsDate() {
    return $this->format('Ymd');
  }


  public function isFloating() {
    return !isset($this->tzid);
  }

  public function isDate() {
    return !isset($this->is_date);
  }

  
  public function modify( $interval ) {
//    print ">>$interval<<\n";
    if ( preg_match('{^(-)?P(([0-9-]+)W)?(([0-9-]+)D)?T?(([0-9-]+)H)?(([0-9-]+)M)?(([0-9-]+)S)?$}', $interval, $matches) ) {
      $minus = $matches[1];
      $interval = '';
      if ( isset($matches[2]) && $matches[2] != '' ) $interval .= $minus . $matches[3] . ' weeks ';
      if ( isset($matches[4]) && $matches[4] != '' ) $interval .= $minus . $matches[5] . ' days ';
      if ( isset($matches[6]) && $matches[6] != '' ) $interval .= $minus . $matches[7] . ' hours ';
      if ( isset($matches[8]) && $matches[8] != '' ) $interval .= $minus . $matches[9] . ' minutes ';
      if (isset($matches[10]) &&$matches[10] != '' ) $interval .= $minus . $matches[11] . ' seconds ';
    }
//    printf( "Modify '%s' by: >>%s<<\n", $this->__toString(), $interval );
//    print_r($this);
    if ( !isset($interval) || $interval == '' ) $interval = '1 day';
    parent::modify($interval);
    return $this->__toString();
  }


  public function UTC() {
    $gmt = clone($this);
    if ( isset($this->tzid) && $this->tzid != 'UTC' ) {
      $dtz = parent::getTimezone();
      $offset = 0 - $dtz->getOffset($gmt);
      $gmt->modify( $offset . ' seconds' );
    }
    if ( $this->is_date ) return $gmt->format('Ymd');
    return $gmt->format('Ymd\THis\Z');
  }


  public function FloatOrUTC() {
    $gmt = clone($this);
    if ( isset($this->tzid) && $this->tzid != 'UTC' ) {
      $dtz = parent::getTimezone();
      $offset = 0 - $dtz->getOffset($gmt);
      $gmt->modify( $offset . ' seconds' );
    }
    if ( $this->is_date ) return $gmt->format('Ymd');
    $result = $gmt->format('Ymd\THis');
    if ( isset($this->tzid) ) $result .= 'Z';
    return $result;
  }


  public function RFC5545() {
    $result = '';
    if ( isset($this->tzid) && $this->tzid != 'UTC' ) {
      $result = ';TZID='.$this->tzid;
    }
    if ( $this->is_date ) {
      $result .= ';VALUE=DATE:' . $this->format('Ymd');
    }
    else {
      $result .= ':' . $this->format('Ymd\THis');
      if ( isset($this->tzid) && $this->tzid == 'UTC' ) {
        $result .= 'Z';
      }
    }
    return $result;
  }


  public function RFC5545Duration( $end_stamp ) {
    return sprintf( 'PT%dM', intval(($end_stamp->epoch() - $this->epoch()) / 60) );
  }


  public function setTimeZone( $tz ) {
    if ( is_string($tz) ) {
      $tz = new RepeatRuleTimeZone($tz);
      $this->tzid = $tz->tzid();
    }
    parent::setTimeZone( $tz );
    return $this;
  }


  function setDate( $year=null, $month=null, $day=null ) {
    if ( !isset($year) )  $year  = parent::format('Y');
    if ( !isset($month) ) $month = parent::format('m');
    if ( !isset($day) )   $day   = parent::format('d');
    parent::setDate( $year , $month , $day );
    return $this;
  }

  function year() {
    return parent::format('Y');
  }

  function month() {
    return parent::format('m');
  }

  function day() {
    return parent::format('d');
  }

  function hour() {
    return parent::format('H');
  }

  function minute() {
    return parent::format('i');
  }

  function second() {
    return parent::format('s');
  }

  function epoch() {
    return parent::format('U');
  }
}


class RepeatRule {

  private $base;
  private $until;
  private $freq;
  private $count;
  private $interval;
  private $bysecond;
  private $byminute;
  private $byhour;
  private $bymonthday;
  private $byyearday;
  private $byweekno;
  private $byday;
  private $bymonth;
  private $bysetpos;
  private $wkst;

  private $instances;
  private $position;
  private $finished;
  private $current_base;


  public function __construct( $basedate, $rrule ) {
    $this->base = ( is_object($basedate) ? $basedate : new RepeatRuleDateTime($basedate) );

    if ( DEBUG_RRULE ) {
      printf( "Constructing RRULE based on: '%s', rrule: '%s'\n", $basedate, $rrule );
    }

    if ( preg_match('{FREQ=([A-Z]+)(;|$)}', $rrule, $m) ) $this->freq = $m[1];
    if ( preg_match('{UNTIL=([0-9TZ]+)(;|$)}', $rrule, $m) ) $this->until = new RepeatRuleDateTime($m[1]);
    if ( preg_match('{COUNT=([0-9]+)(;|$)}', $rrule, $m) ) $this->count = $m[1];
    if ( preg_match('{INTERVAL=([0-9]+)(;|$)}', $rrule, $m) ) $this->interval = $m[1];
    if ( preg_match('{WKST=(MO|TU|WE|TH|FR|SA|SU)(;|$)}', $rrule, $m) ) $this->wkst = $m[1];

    if ( preg_match('{BYDAY=(([+-]?[0-9]{0,2}(MO|TU|WE|TH|FR|SA|SU),?)+)(;|$)}', $rrule, $m) )  $this->byday = explode(',',$m[1]);

    if ( preg_match('{BYYEARDAY=([0-9,+-]+)(;|$)}', $rrule, $m) ) $this->byyearday = explode(',',$m[1]);
    if ( preg_match('{BYWEEKNO=([0-9,+-]+)(;|$)}', $rrule, $m) ) $this->byweekno = explode(',',$m[1]);
    if ( preg_match('{BYMONTHDAY=([0-9,+-]+)(;|$)}', $rrule, $m) ) $this->bymonthday = explode(',',$m[1]);
    if ( preg_match('{BYMONTH=(([+-]?[0-1]?[0-9],?)+)(;|$)}', $rrule, $m) ) $this->bymonth = explode(',',$m[1]);
    if ( preg_match('{BYSETPOS=(([+-]?[0-9]{1,3},?)+)(;|$)}', $rrule, $m) ) $this->bysetpos = explode(',',$m[1]);

    if ( preg_match('{BYSECOND=([0-9,]+)(;|$)}', $rrule, $m) ) $this->bysecond = explode(',',$m[1]);
    if ( preg_match('{BYMINUTE=([0-9,]+)(;|$)}', $rrule, $m) ) $this->byminute = explode(',',$m[1]);
    if ( preg_match('{BYHOUR=([0-9,]+)(;|$)}', $rrule, $m) ) $this->byhour = explode(',',$m[1]);

    if ( !isset($this->interval) ) $this->interval = 1;
    switch( $this->freq ) {
      case 'SECONDLY': $this->freq_name = 'second'; break;
      case 'MINUTELY': $this->freq_name = 'minute'; break;
      case 'HOURLY':   $this->freq_name = 'hour';   break;
      case 'DAILY':    $this->freq_name = 'day';    break;
      case 'WEEKLY':   $this->freq_name = 'week';   break;
      case 'MONTHLY':  $this->freq_name = 'month';  break;
      case 'YEARLY':   $this->freq_name = 'year';   break;
      default:
        /** need to handle the error, but FREQ is mandatory so unlikely */
    }
    $this->frequency_string = sprintf('+%d %s', $this->interval, $this->freq_name );
    if ( DEBUG_RRULE ) printf( "Frequency modify string is: '%s', base is: '%s'\n", $this->frequency_string, $this->base->format('c') );
    $this->Start();
  }


  public function set_timezone( $tzstring ) {
    $this->base->setTimezone(new DateTimeZone($tzstring));
  }


  public function Start() {
    $this->instances = array();
    $this->GetMoreInstances();
    $this->rewind();
    $this->finished = false;
  }


  public function rewind() {
    $this->position = -1;
  }


  public function next() {
    $this->position++;
    return $this->current();
  }


  public function current() {
    if ( !$this->valid() ) return null;
    if ( !isset($this->instances[$this->position]) ) $this->GetMoreInstances();
    if ( !$this->valid() ) return null;
    if ( DEBUG_RRULE ) printf( "Returning date from position %d: %s (%s)\n", $this->position, $this->instances[$this->position]->format('c'), $this->instances[$this->position]->UTC() );
    return $this->instances[$this->position];
  }


  public function key() {
    if ( !$this->valid() ) return null;
    if ( !isset($this->instances[$this->position]) ) $this->GetMoreInstances();
    if ( !isset($this->keys[$this->position]) ) {
      $this->keys[$this->position] = $this->instances[$this->position];
    }
    return $this->keys[$this->position];
  }


  public function valid() {
    if ( isset($this->instances[$this->position]) || !$this->finished ) return true;
    return false;
  }


  private function GetMoreInstances() {
    global $rrule_expand_limit;

    if ( $this->finished ) return;
    $got_more = false;
    $loop_limit = 10;
    $loops = 0;
    while( !$this->finished && !$got_more && $loops++ < $loop_limit ) {
      if ( !isset($this->current_base) ) {
        $this->current_base = clone($this->base);
      }
      else {
        $this->current_base->modify( $this->frequency_string );
      }
      if ( DEBUG_RRULE ) printf( "Getting more instances from: '%s' - %d\n", $this->current_base->format('c'), count($this->instances) );
      $this->current_set = array( clone($this->current_base) );
      foreach( $rrule_expand_limit[$this->freq] AS $bytype => $action ) {
        if ( isset($this->{$bytype}) ) $this->{$action.'_'.$bytype}();
        if ( !isset($this->current_set[0]) ) break;
      }
      sort($this->current_set);
      if ( isset($this->bysetpos) ) $this->limit_bysetpos();

      $position = count($this->instances) - 1;
      if ( DEBUG_RRULE ) printf( "Inserting %d from current_set into position %d\n", count($this->current_set), $position + 1 );
      foreach( $this->current_set AS $k => $instance ) {
        if ( $instance < $this->base ) continue;
        if ( isset($this->until) && $instance > $this->until ) {
          $this->finished = true;
          return;
        }
        if ( !isset($this->instances[$position]) || $instance != $this->instances[$position] ) {
          $got_more = true;
          $position++;
          $this->instances[$position] = $instance;
          if ( DEBUG_RRULE ) printf( "Added date %s into position %d in current set\n", $instance->format('c'), $position );
          if ( isset($this->count) && ($position + 1) >= $this->count ) $this->finished = true;
        }
      }
    }
  }


  static public function date_mask( $date, $y, $mo, $d, $h, $mi, $s ) {
    $date_parts = explode(',',$date->format('Y,m,d,H,i,s'));

    $tz = $date->getTimezone();
    if ( isset($y) || isset($mo) || isset($d) ) {
      if ( isset($y) ) $date_parts[0] = $y;
      if ( isset($mo) ) $date_parts[1] = $mo;
      if ( isset($d) ) $date_parts[2] = $d;
      $date->setDate( $date_parts[0], $date_parts[1], $date_parts[2] );
    }
    if ( isset($h) || isset($mi) || isset($s) ) {
      if ( isset($h) ) $date_parts[3] = $h;
      if ( isset($mi) ) $date_parts[4] = $mi;
      if ( isset($s) ) $date_parts[5] = $s;
      $date->setTime( $date_parts[3], $date_parts[4], $date_parts[5] );
    }
    return $date;
  }


  private function expand_bymonth() {
    $instances = $this->current_set;
    $this->current_set = array();
    foreach( $instances AS $k => $instance ) {
      foreach( $this->bymonth AS $k => $month ) {
        $expanded = $this->date_mask( clone($instance), null, $month, null, null, null, null);
        if ( DEBUG_RRULE ) printf( "Expanded BYMONTH $month into date %s\n", $expanded->format('c') );
        $this->current_set[] = $expanded;
      }
    }
  }

  private function expand_bymonthday() {
    $instances = $this->current_set;
    $this->current_set = array();
    foreach( $instances AS $k => $instance ) {
      foreach( $this->bymonthday AS $k => $monthday ) {
        $this->current_set[] = $this->date_mask( clone($instance), null, null, $monthday, null, null, null);
      }
    }
  }


  private function expand_byday_in_week( $day_in_week ) {
    global $rrule_day_numbers;

    /**
    * @TODO: This should really allow for WKST, since if we start a series
    * on (eg.) TH and interval > 1, a MO, TU, FR repeat will not be in the
    * same week with this code.
    */
    $dow_of_instance = $day_in_week->format('w'); // 0 == Sunday
    foreach( $this->byday AS $k => $weekday ) {
      $dow = $rrule_day_numbers[$weekday];
      $offset = $dow - $dow_of_instance;
      if ( $offset < 0 ) $offset += 7;
      $expanded = clone($day_in_week);
      $expanded->modify( sprintf('+%d day', $offset) );
      $this->current_set[] = $expanded;
      if ( DEBUG_RRULE ) printf( "Expanded BYDAY(W) $weekday into date %s\n", $expanded->format('c') );
    }
  }


  private function expand_byday_in_month( $day_in_month ) {
    global $rrule_day_numbers;

    $first_of_month = $this->date_mask( clone($day_in_month), null, null, 1, null, null, null);
    $dow_of_first = $first_of_month->format('w'); // 0 == Sunday
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $first_of_month->format('m'), $first_of_month->format('Y'));
    foreach( $this->byday AS $k => $weekday ) {
      if ( preg_match('{([+-])?(\d)?(MO|TU|WE|TH|FR|SA|SU)}', $weekday, $matches ) ) {
        $dow = $rrule_day_numbers[$matches[3]];
        $first_dom = 1 + $dow - $dow_of_first;  if ( $first_dom < 1 ) $first_dom +=7;  // e.g. 1st=WE, dow=MO => 1+1-3=-1 => MO is 6th, etc.
        $whichweek = intval($matches[2]);
        if ( DEBUG_RRULE ) printf( "Expanding BYDAY(M) $weekday in month of %s\n", $first_of_month->format('c') );
        if ( $whichweek > 0 ) {
          $whichweek--;
          $monthday = $first_dom;
          if ( $matches[1] == '-' ) {
            $monthday += 35;
            while( $monthday > $days_in_month ) $monthday -= 7;
            $monthday -= (7 * $whichweek);
          }
          else {
            $monthday += (7 * $whichweek);
          }
          if ( $monthday > 0 && $monthday <= $days_in_month ) {
            $expanded = $this->date_mask( clone($day_in_month), null, null, $monthday, null, null, null);
            if ( DEBUG_RRULE ) printf( "Expanded BYDAY(M) $weekday now $monthday into date %s\n", $expanded->format('c') );
            $this->current_set[] = $expanded;
          }
        }
        else {
          for( $monthday = $first_dom; $monthday <= $days_in_month; $monthday += 7 ) {
            $expanded = $this->date_mask( clone($day_in_month), null, null, $monthday, null, null, null);
            if ( DEBUG_RRULE ) printf( "Expanded BYDAY(M) $weekday now $monthday into date %s\n", $expanded->format('c') );
            $this->current_set[] = $expanded;
          }
        }
      }
    }
  }


  private function expand_byday_in_year( $day_in_year ) {
    global $rrule_day_numbers;

    $first_of_year = $this->date_mask( clone($day_in_year), null, 1, 1, null, null, null);
    $dow_of_first = $first_of_year->format('w'); // 0 == Sunday
    $days_in_year = 337 + cal_days_in_month(CAL_GREGORIAN, 2, $first_of_year->format('Y'));
    foreach( $this->byday AS $k => $weekday ) {
      if ( preg_match('{([+-])?(\d)?(MO|TU|WE|TH|FR|SA|SU)}', $weekday, $matches ) ) {
        $expanded = clone($first_of_year);
        $dow = $rrule_day_numbers[$matches[3]];
        $first_doy = 1 + $dow - $dow_of_first;  if ( $first_doy < 1 ) $first_doy +=7;  // e.g. 1st=WE, dow=MO => 1+1-3=-1 => MO is 6th, etc.
        $whichweek = intval($matches[2]);
        if ( DEBUG_RRULE ) printf( "Expanding BYDAY(Y) $weekday from date %s\n", $instance->format('c') );
        if ( $whichweek > 0 ) {
          $whichweek--;
          $yearday = $first_doy;
          if ( $matches[1] == '-' ) {
            $yearday += 371;
            while( $yearday > $days_in_year ) $yearday -= 7;
            $yearday -= (7 * $whichweek);
          }
          else {
            $yearday += (7 * $whichweek);
          }
          if ( $yearday > 0 && $yearday <= $days_in_year ) {
            $expanded->modify(sprintf('+%d day', $yearday - 1));
            if ( DEBUG_RRULE ) printf( "Expanded BYDAY(Y) $weekday now $yearday into date %s\n", $expanded->format('c') );
            $this->current_set[] = $expanded;
          }
        }
        else {
          $expanded->modify(sprintf('+%d day', $first_doy - 1));
          for( $yearday = $first_doy; $yearday <= $days_in_year; $yearday += 7 ) {
            if ( DEBUG_RRULE ) printf( "Expanded BYDAY(Y) $weekday now $yearday into date %s\n", $expanded->format('c') );
            $this->current_set[] = clone($expanded);
            $expanded->modify('+1 week');
          }
        }
      }
    }
  }


  private function expand_byday() {
    if ( !isset($this->current_set[0]) ) return;
    if ( $this->freq == 'MONTHLY' || $this->freq == 'YEARLY' ) {
      if ( isset($this->bymonthday) || isset($this->byyearday) ) {
        $this->limit_byday();  /** Per RFC5545 3.3.10 from note 1&2 to table */
        return;
      }
    }
    $instances = $this->current_set;
    $this->current_set = array();
    foreach( $instances AS $k => $instance ) {
      if ( $this->freq == 'MONTHLY' ) {
        $this->expand_byday_in_month($instance);
      }
      else if ( $this->freq == 'WEEKLY' ) {
        $this->expand_byday_in_week($instance);
      }
      else { // YEARLY
        if ( isset($this->bymonth) ) {
          $this->expand_byday_in_month($instance);
        }
        else if ( isset($this->byweekno) ) {
          $this->expand_byday_in_week($instance);
        }
        else {
          $this->expand_byday_in_year($instance);
        }
      }

    }
  }

  private function expand_byhour() {
    $instances = $this->current_set;
    $this->current_set = array();
    foreach( $instances AS $k => $instance ) {
      foreach( $this->bymonth AS $k => $month ) {
        $this->current_set[] = $this->date_mask( clone($instance), null, null, null, $hour, null, null);
      }
    }
  }

  private function expand_byminute() {
    $instances = $this->current_set;
    $this->current_set = array();
    foreach( $instances AS $k => $instance ) {
      foreach( $this->bymonth AS $k => $month ) {
        $this->current_set[] = $this->date_mask( clone($instance), null, null, null, null, $minute, null);
      }
    }
  }

  private function expand_bysecond() {
    $instances = $this->current_set;
    $this->current_set = array();
    foreach( $instances AS $k => $instance ) {
      foreach( $this->bymonth AS $k => $second ) {
        $this->current_set[] = $this->date_mask( clone($instance), null, null, null, null, null, $second);
      }
    }
  }


  private function limit_generally( $fmt_char, $element_name ) {
    $instances = $this->current_set;
    $this->current_set = array();
    foreach( $instances AS $k => $instance ) {
      foreach( $this->{$element_name} AS $k => $element_value ) {
        if ( DEBUG_RRULE ) printf( "Limiting '$fmt_char' on '%s' => '%s' ?=? '%s' ? %s\n", $instance->format('c'), $instance->format($fmt_char), $element_value, ($instance->format($fmt_char) == $element_value ? 'Yes' : 'No') );
        if ( $instance->format($fmt_char) == $element_value ) $this->current_set[] = $instance;
      }
    }
  }

  private function limit_byday() {
    global $rrule_day_numbers;

    $fmt_char = 'w';
    $instances = $this->current_set;
    $this->current_set = array();
    foreach( $this->byday AS $k => $weekday ) {
      $dow = $rrule_day_numbers[$weekday];
      foreach( $instances AS $k => $instance ) {
        if ( DEBUG_RRULE ) printf( "Limiting '$fmt_char' on '%s' => '%s' ?=? '%s' (%d) ? %s\n", $instance->format('c'), $instance->format($fmt_char), $weekday, $dow, ($instance->format($fmt_char) == $dow ? 'Yes' : 'No') );
        if ( $instance->format($fmt_char) == $dow ) $this->current_set[] = $instance;
      }
    }
  }

  private function limit_bymonth()    {   $this->limit_generally( 'm', 'bymonth' );     }
  private function limit_byyearday()  {   $this->limit_generally( 'z', 'byyearday' );   }
  private function limit_bymonthday() {   $this->limit_generally( 'd', 'bymonthday' );  }
  private function limit_byhour()     {   $this->limit_generally( 'H', 'byhour' );      }
  private function limit_byminute()   {   $this->limit_generally( 'i', 'byminute' );    }
  private function limit_bysecond()   {   $this->limit_generally( 's', 'bysecond' );    }


  private function limit_bysetpos( ) {
    $instances = $this->current_set;
    $count = count($instances);
    $this->current_set = array();
    foreach( $this->bysetpos AS $k => $element_value ) {
      if ( DEBUG_RRULE ) printf( "Limiting bysetpos %s of %d instances\n", $element_value, $count );
      if ( $element_value > 0 ) {
        $this->current_set[] = $instances[$element_value - 1];
      }
      else if ( $element_value < 0 ) {
        $this->current_set[] = $instances[$count + $element_value];
      }
    }
  }


}


require_once("vComponent.php");

/**
* Expand the event instances for an RDATE or EXDATE property
*
* @param string $property RDATE or EXDATE, depending...
* @param array $component A vComponent which is a VEVENT, VTODO or VJOURNAL
* @param array $range_end A date after which we care less about expansion
*
* @return array An array keyed on the UTC dates, referring to the component
*/
function rdate_expand( $dtstart, $property, $component, $range_end = null ) {
  $properties = $component->GetProperties($property);
  $expansion = array();
  foreach( $properties AS $p ) {
    $timezone = $p->GetParameterValue('TZID');
    $rdate = $p->Value();
    $rdates = explode( ',', $rdate );
    foreach( $rdates AS $k => $v ) {
      $rdate = new RepeatRuleDateTime( $v, $timezone);
      $expansion[$rdate->UTC()] = $component;
      if ( $rdate > $range_end ) break;
    }
  }
  return $expansion;
}


/**
* Expand the event instances for an RRULE property
*
* @param object $dtstart A RepeatRuleDateTime which is the master dtstart
* @param string $property RDATE or EXDATE, depending...
* @param array $component A vComponent which is a VEVENT, VTODO or VJOURNAL
* @param array $range_end A date after which we care less about expansion
*
* @return array An array keyed on the UTC dates, referring to the component
*/
function rrule_expand( $dtstart, $property, $component, $range_end ) {
  $expansion = array();

  $recur = $component->GetProperty($property);
  if ( !isset($recur) ) return $expansion;
  $recur = $recur->Value();

  $this_start = $component->GetProperty('DTSTART');
  if ( isset($this_start) ) {
    $timezone = $this_start->GetParameterValue('TZID');
    $this_start = new RepeatRuleDateTime($this_start->Value(),$timezone);
  }
  else {
    $this_start = clone($dtstart);
  }

//  print_r( $this_start );
//  printf( "RRULE: %s\n", $recur );
  $rule = new RepeatRule( $this_start, $recur );
  $i = 0;
  $result_limit = 1000;
  while( $date = $rule->next() ) {
//    printf( "[%3d] %s\n", $i, $date->UTC() );
    $expansion[$date->UTC()] = $component;
    if ( $i++ >= $result_limit || $date > $range_end ) break;
  }
//  print_r( $expansion );
  return $expansion;
}


/**
* Expand the event instances for an iCalendar VEVENT (or VTODO)
* 
* Note: expansion here does not apply modifications to instances other than modifying start/end/due/duration.
*
* @param object $vResource A vComponent which is a VCALENDAR containing components needing expansion
* @param object $range_start A RepeatRuleDateTime which is the beginning of the range for events, default -6 weeks
* @param object $range_end A RepeatRuleDateTime which is the end of the range for events, default +6 weeks
*
* @return vComponent The original vComponent, with the instances of the internal components expanded.
*/
function expand_event_instances( $vResource, $range_start = null, $range_end = null ) {
  $components = $vResource->GetComponents();

  if ( !isset($range_start) ) { $range_start = new RepeatRuleDateTime(); $range_start->modify('-6 weeks'); }
  if ( !isset($range_end) )   { $range_end   = clone($range_start);      $range_end->modify('+6 months');  }

  $new_components = array();
  $result_limit = 1000;
  $instances = array();
  $expand = false;
  $dtstart = null;
  foreach( $components AS $k => $comp ) {
    if ( $comp->GetType() != 'VEVENT' && $comp->GetType() != 'VTODO' && $comp->GetType() != 'VJOURNAL' ) {
      if ( $comp->GetType() != 'VTIMEZONE' ) $new_components[] = $comp;
      continue;
    }
    if ( !isset($dtstart) ) {
      $dtstart = $comp->GetProperty('DTSTART');
      $dtstart = new RepeatRuleDateTime( $dtstart );
      $instances[$dtstart->UTC()] = $comp;
    }
    $p = $comp->GetProperty('RECURRENCE-ID');
    if ( isset($p) && $p->Value() != '' ) {
      $range = $p->GetParameterValue('RANGE');
      $recur_utc = new RepeatRuleDateTime($p);
      $recur_utc = $recur_utc->UTC();
      if ( isset($range) && $range == 'THISANDFUTURE' ) {
        foreach( $instances AS $k => $v ) {
          if ( DEBUG_RRULE ) printf( "Removing overridden instance at: $k\n" );
          if ( $k >= $recur_utc ) unset($instances[$k]);
        }
      }
      else {
        unset($instances[$recur_utc]);
      }
    }
    else if ( DEBUG_RRULE ) {
      $p =  $comp->GetProperty('SUMMARY');
      $summary = ( isset($p) ? $p->Value() : 'not set');
      $p =  $comp->GetProperty('UID');
      $uid = ( isset($p) ? $p->Value() : 'not set');
      printf( "Processing event '%s' with UID '%s' starting on %s\n",
                 $summary, $uid, $dtstart->UTC() );      
      print( "Instances at start");
      foreach( $instances AS $k => $v ) {
        print ' : '.$k;        
      }
      print "\n";
    }
    $instances = array_merge( $instances, rrule_expand($dtstart, 'RRULE', $comp, $range_end) );
    if ( DEBUG_RRULE ) {
      print( "After rrule_expand");
      foreach( $instances AS $k => $v ) {
        print ' : '.$k;        
      }
      print "\n";
    }
    $instances = array_merge( $instances, rdate_expand($dtstart, 'RDATE', $comp, $range_end) );
    if ( DEBUG_RRULE ) {
      print( "After rdate_expand");
      foreach( $instances AS $k => $v ) {
        print ' : '.$k;        
      }
      print "\n";
    }
    foreach ( rdate_expand($dtstart, 'EXDATE', $comp, $range_end) AS $k => $v ) {
      unset($instances[$k]);
    }
    if ( DEBUG_RRULE ) {
      print( "After exdate_expand");
      foreach( $instances AS $k => $v ) {
        print ' : '.$k;        
      }
      print "\n";
    }
  }

  $last_duration = null;
  $early_start = null;
  $new_components = array();
  $start_utc = $range_start->UTC();
  $end_utc = $range_end->UTC();
  foreach( $instances AS $utc => $comp ) {
    if ( $utc > $end_utc ) break;

    $end_type = ($comp->GetType() == 'VTODO' ? 'DUE' : 'DTEND');
    $duration = $comp->GetProperty('DURATION');
    if ( !isset($duration) || $duration->Value() == '' ) {
      $instance_start = $comp->GetProperty('DTSTART');
      $dtsrt = new RepeatRuleDateTime( $instance_start );
      $instance_end = $comp->GetProperty($end_type);
      if ( isset($instance_end) ) {
        $dtend = new RepeatRuleDateTime( $instance_end );
        $duration = $dtstart->RFC5545Duration( $dtend );
      }
      else {
        if ( $instance_start->GetParameterValue('VALUE') == 'DATE' ) {
          $duration = 'P1D';
        }
        else {
          $duration = 'P0D';  // For clarity
        }
      }
    }
    else {
      $duration = $duration->Value();
    }

    if ( $utc < $start_utc ) {
      if ( isset($early_start) && isset($last_duration) && $duration == $last_duration) {
        if ( $utc < $early_start ) continue;
      }
      else {
        /** Calculate the latest possible start date when this event would overlap our range start */
        $latest_start = clone($range_start);
        $latest_start->modify('-'.$duration);
        $early_start = $latest_start->UTC();
        $last_duration = $duration;
        if ( $utc < $early_start ) continue;
      }
    }
    $component = clone($comp);
    $component->ClearProperties( array('DTSTART'=> true, 'DUE' => true, 'DTEND' => true) );
    $component->AddProperty('DTSTART', $utc );
    $component->AddProperty('DURATION', $duration );
    $new_components[] = $component;
  }

  $vResource->SetComponents($new_components);

  return $vResource;
}
