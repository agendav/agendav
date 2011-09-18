<?php

$need_expansion = false;
function check_for_expansion( $calendar_data_node ) {
  global $need_expansion, $expand_range_start, $expand_range_end;

  if ( !class_exists('DateTime') ) return; /** We don't support expansion on PHP5.1 */

  $expansion = $calendar_data_node->GetElements('urn:ietf:params:xml:ns:caldav:expand');
  if ( isset($expansion[0]) ) {
    $need_expansion = true;
    $expand_range_start = $expansion[0]->GetAttribute('start');
    $expand_range_end = $expansion[0]->GetAttribute('end');
    if ( isset($expand_range_start) ) $expand_range_start = new RepeatRuleDateTime($expand_range_start);
    if ( isset($expand_range_end) )   $expand_range_end   = new RepeatRuleDateTime($expand_range_end);
  }
}

/**
 * Build the array of properties to include in the report output
 */
$qry_content = $xmltree->GetContent('urn:ietf:params:xml:ns:caldav:calendar-query');
$proptype = $qry_content[0]->GetTag();
$properties = array();
switch( $proptype ) {
  case 'DAV::prop':
    $qry_props = $xmltree->GetPath('/urn:ietf:params:xml:ns:caldav:calendar-query/'.$proptype.'/*');
    foreach( $qry_content[0]->GetElements() AS $k => $v ) {
      $propertyname = preg_replace( '/^.*:/', '', $v->GetTag() );
      $properties[$propertyname] = 1;
      if ( $v->GetTag() == 'urn:ietf:params:xml:ns:caldav:calendar-data' ) check_for_expansion($v);
    }
    break;

  case 'DAV::allprop':
    $properties['allprop'] = 1;
    if ( $qry_content[1]->GetTag() == 'DAV::include' ) {
      foreach( $qry_content[1]->GetElements() AS $k => $v ) {
        $include_properties[] = $v->GetTag(); /** $include_properties is referenced in DAVResource where allprop is expanded */
        if ( $v->GetTag() == 'urn:ietf:params:xml:ns:caldav:calendar-data' ) check_for_expansion($v);
      }
    }
    break;

  default:
    $propertyname = preg_replace( '/^.*:/', '', $proptype );
    $properties[$propertyname] = 1;
}

/**
 * There can only be *one* FILTER element, and it must contain *one* COMP-FILTER
 * element.  In every case I can see this contained COMP-FILTER element will be a
 * VCALENDAR, but perhaps there are others.  In our case we strip it if that is
 * the case and leave it alone otherwise.
 */
$qry_filters = $xmltree->GetPath('/urn:ietf:params:xml:ns:caldav:calendar-query/urn:ietf:params:xml:ns:caldav:filter/*');
if ( count($qry_filters) == 1 ) {
  $qry_filters = $qry_filters[0];  // There can only be one FILTER element
  if ( $qry_filters->GetTag() == "urn:ietf:params:xml:ns:caldav:comp-filter" && $qry_filters->GetAttribute("name") == "VCALENDAR" )
    $qry_filters = $qry_filters->GetContent();  // Everything is inside a VCALENDAR AFAICS
  else {
    dbg_error_log("calquery", "Got bizarre CALDAV:FILTER[%s=%s]] which does not contain comp-filter = VCALENDAR!!", $qry_filters->GetTag(), $qry_filters->GetAttribute("name") );
    $qry_filters = false;
  }
}
else {
  $qry_filters = false;
}


/**
* While we can construct our SQL to apply some filters in the query, other filters
* need to be checked against the retrieved record.  This is for handling those ones.
*
* @param array $filter An array of XMLElement which is the filter definition
* @param string $item The database row retrieved for this calendar item
*
* @return boolean True if the check succeeded, false otherwise.
*/
function apply_filter( $filters, $item ) {
  global $session, $c, $request;

  if ( count($filters) == 0 ) return true;

  dbg_error_log("calquery","Applying filter for item '%s'", $item->dav_name );
  $ical = new iCalendar( array( "icalendar" => $item->caldav_data) );
  return $ical->TestFilter($filters);
}


/**
 * Process a filter fragment returning an SQL fragment
 */
$need_post_filter = false;
function SqlFilterFragment( $filter, $components, $property = null, $parameter = null ) {
  global $need_post_filter, $target_collection;
  $sql = "";
  $params = array();
  if ( !is_array($filter) ) {
    dbg_error_log( "calquery", "Filter is of type '%s', but should be an array of XML Tags.", gettype($filter) );
  }

  foreach( $filter AS $k => $v ) {
    $tag = $v->GetTag();
    dbg_error_log("calquery", "Processing $tag into SQL - %d, '%s', %d\n", count($components), $property, isset($parameter) );

    $not_defined = "";
    switch( $tag ) {
      case 'urn:ietf:params:xml:ns:caldav:is-not-defined':
        $not_defined = "not-"; // then fall through to IS-DEFINED case
      case 'urn:ietf:params:xml:ns:caldav:is-defined':
        if ( isset( $parameter ) ) {
          $need_post_filter = true;
          dbg_error_log("calquery", "Could not handle 'is-%sdefined' on property %s, parameter %s in SQL", $not_defined, $property, $parameter );
          return false;  // Not handled in SQL
        }
        if ( isset( $property ) ) {
          switch( $property ) {
            case 'created':
            case 'completed':  /** @todo when it can be handled in the SQL - see around line 200 below */
            case 'dtend':
            case 'dtstamp':
            case 'dtstart':
              if ( ! $target_collection->IsSchedulingCollection() ) {
                $property_defined_match = "IS NOT NULL";
              }
              break;

            case 'priority':
              $property_defined_match = "IS NOT NULL";
              break;

            default:
              $property_defined_match = "LIKE '_%'";  // i.e. contains a single character or more
          }
          $sql .= sprintf( "AND %s %s%s ", $property, $not_defined, $property_defined_match );
        }
        break;

      case 'urn:ietf:params:xml:ns:caldav:time-range':
        /**
        * @todo We should probably allow time range queries against other properties, since eventually some client may want to do this.
        */
        $start_column = ($components[sizeof($components)-1] == 'VTODO' ? "due" : 'dtend');     // The column we compare against the START attribute
        $finish_column = 'dtstart';  // The column we compare against the END attribute
        $start = $v->GetAttribute("start");
        $finish = $v->GetAttribute("end");
        if ( isset($start) || isset($finish) ) {
          $sql .= ' AND (rrule_event_overlaps( dtstart, dtend, rrule, :time_range_start, :time_range_end ) OR event_has_exceptions(caldav_data.caldav_data) ) ';
          $params[':time_range_start'] = $start;
          $params[':time_range_end'] = $finish;
        }
        break;

      case 'urn:ietf:params:xml:ns:caldav:text-match':
        $search = $v->GetContent();
        $negate = $v->GetAttribute("negate-condition");
        $collation = $v->GetAttribute("collation");
        switch( strtolower($collation) ) {
          case 'i;octet':
            $comparison = 'LIKE';
            break;
          case 'i;ascii-casemap':
          default:
            $comparison = 'ILIKE';
            break;
        }
        $params[':text_match'] = '%'.$search.'%';
        dbg_error_log("calquery", " text-match: (%s IS NULL OR %s%s %s '%s') ", $property, (isset($negate) && strtolower($negate) == "yes" ? "NOT ": ""),
                                          $property, $comparison, $params[':text_match'] );
        $sql .= sprintf( "AND (%s IS NULL OR %s%s %s :text_match) ", $property, (isset($negate) && strtolower($negate) == "yes" ? "NOT ": ""),
                                          $property, $comparison );
        break;

      case 'urn:ietf:params:xml:ns:caldav:comp-filter':
        $comp_filter_name = $v->GetAttribute("name");
        if ( count($components) == 0 ) {
          $sql .= "AND caldav_data.caldav_type = :component_name_filter ";
          $params[':component_name_filter'] = $comp_filter_name;
        }
        $components[] = $comp_filter_name;
        $subfilter = $v->GetContent();
        if ( is_array( $subfilter ) ) {
          $success = SqlFilterFragment( $subfilter, $components, $property, $parameter );
          if ( $success === false ) continue; else {
            $sql .= $success['sql'];
            $params = array_merge( $params, $success['params'] );
          }
        }
        break;

      case 'urn:ietf:params:xml:ns:caldav:prop-filter':
        $propertyname = $v->GetAttribute("name");
        switch( $propertyname ) {
          case 'PERCENT-COMPLETE':
            $property = 'percent_complete';
            break;

          case 'UID':
          case 'SUMMARY':
          case 'LOCATION':
          case 'DESCRIPTION':
          case 'CLASS':
          case 'TRANSP':
          case 'RRULE':  // Likely that this is not much use
          case 'URL':
          case 'STATUS':
          case 'CREATED':
          case 'DTSTAMP':
          case 'DTSTART':
          case 'DTEND':
          case 'DUE':
          case 'PRIORITY':
            $property = strtolower($propertyname);
            break;

          case 'COMPLETED':  /** @todo this should be moved into the properties supported in SQL. */
          default:
            $need_post_filter = true;
            dbg_error_log("calquery", "Could not handle 'prop-filter' on %s in SQL", $propertyname );
            continue;
        }
        $subfilter = $v->GetContent();
        $success = SqlFilterFragment( $subfilter, $components, $property, $parameter );
        if ( $success === false ) continue; else {
          $sql .= $success['sql'];
          $params = array_merge( $params, $success['params'] );
        }
        break;

      case 'urn:ietf:params:xml:ns:caldav:param-filter':
        $need_post_filter = true;
        return false; // Can't handle PARAM-FILTER conditions in the SQL
        $parameter = $v->GetAttribute("name");
        $subfilter = $v->GetContent();
        $success = SqlFilterFragment( $subfilter, $components, $property, $parameter );
        if ( $success === false ) continue; else {
          $sql .= $success['sql'];
          $params = array_merge( $params, $success['params'] );
        }
        break;

      default:
        dbg_error_log("calquery", "Could not handle unknown tag '%s' in calendar query report", $tag );
        break;
    }
  }
  dbg_error_log("calquery", "Generated SQL was '%s'", $sql );
  return array( 'sql' => $sql, 'params' => $params );
}

/**
 * Build an SQL 'WHERE' clause which implements (parts of) the filter. The
 * elements of the filter which are implemented in the SQL will be removed.
 *
 * @param arrayref &$filter A reference to an array of XMLElement defining the filter
 *
 * @return string A string suitable for use as an SQL 'WHERE' clause selecting the desired records.
 */
function BuildSqlFilter( $filter ) {
  $components = array();
  return SqlFilterFragment( $filter, $components );
}


/**
* Something that we can handle, at least roughly correctly.
*/

$responses = array();
$target_collection = new DAVResource($request->path);
$bound_from = $target_collection->bound_from();
if ( !$target_collection->Exists() ) {
  $request->DoResponse( 404 );
}

$params = array();
$where = ' WHERE caldav_data.collection_id = ' . $target_collection->resource_id();

if ( ! ($target_collection->IsCalendar() || $target_collection->IsSchedulingCollection()) ) {
  if ( !(isset($c->allow_recursive_report) && $c->allow_recursive_report) || $target_collection->IsSchedulingCollection() ) {
    $request->DoResponse( 403, translate('The calendar-query report must be run against a calendar or a scheduling collection') );
  }
  /**
   * We're here because they allow recursive reports, and this appears to be such a location.
   */
  $where = 'WHERE (collection.dav_name ~ :path_match ';
  $where .= 'OR collection.collection_id IN (SELECT bound_source_id FROM dav_binding WHERE dav_binding.dav_name ~ :path_match)) ';
  $params = array( ':path_match' => '^'.$target_collection->bound_from() );
}

if ( is_array($qry_filters) ) {
  dbg_log_array( "calquery", "qry_filters", $qry_filters, true );
  $components = array();
  $filter_fragment =  SqlFilterFragment( $qry_filters, $components );
  if ( $filter_fragment !== false ) {
    $where .= ' '.$filter_fragment['sql'];
    $params = array_merge( $params, $filter_fragment['params']);
  }
}
if ( $target_collection->Privileges() != privilege_to_bits('DAV::all') ) {
  $where .= " AND (calendar_item.class != 'PRIVATE' OR calendar_item.class IS NULL) ";
}

if ( isset($c->hide_TODO) && $c->hide_TODO && ! $target_collection->HavePrivilegeTo('DAV::write-content') ) {
  $where .= " AND caldav_data.caldav_type NOT IN ('VTODO') ";
}

if ( isset($c->hide_older_than) && intval($c->hide_older_than > 0) ) {
  $where .= " AND calendar_item.dtstart > (now() - interval '".intval($c->hide_older_than)." days') ";
}

$sql = 'SELECT caldav_data.*,calendar_item.*  FROM collection INNER JOIN caldav_data USING(collection_id) INNER JOIN calendar_item USING(dav_id) '. $where;
if ( isset($c->strict_result_ordering) && $c->strict_result_ordering ) $sql .= " ORDER BY caldav_data.dav_id";
$qry = new AwlQuery( $sql, $params );
if ( $qry->Exec("calquery",__LINE__,__FILE__) && $qry->rows() > 0 ) {
  while( $calendar_object = $qry->Fetch() ) {
    if ( !$need_post_filter || apply_filter( $qry_filters, $calendar_object ) ) {
      if ( $bound_from != $target_collection->dav_name() ) {
        $calendar_object->dav_name = str_replace( $bound_from, $target_collection->dav_name(), $calendar_object->dav_name);
      }
      if ( $need_expansion ) {
        $vResource = new vComponent($calendar_object->caldav_data);
        $expanded = expand_event_instances($vResource, $expand_range_start, $expand_range_end);
        if ( $expanded->ComponentCount() == 0 ) continue;
        $calendar_object->caldav_data = $expanded->Render();
      }
      $responses[] = calendar_to_xml( $properties, $calendar_object );
    }
  }
}
$multistatus = new XMLElement( "multistatus", $responses, $reply->GetXmlNsArray() );

$request->XMLResponse( 207, $multistatus );
