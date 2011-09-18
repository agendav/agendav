<?php

require_once('vcard.php');

$address_data_properties = array();
function get_address_properties( $address_data_xml ) {
  global $address_data_properties;
  $expansion = $address_data_xml->GetElements();
  foreach( $expansion AS $k => $v ) {
    $address_data_properties[strtoupper($v->GetAttribute('name'))] = true;
  }
}


/**
 * Build the array of properties to include in the report output
 */
$qry_content = $xmltree->GetContent('urn:ietf:params:xml:ns:carddav:addressbook-query');
$proptype = $qry_content[0]->GetTag();
$properties = array();
switch( $proptype ) {
  case 'DAV::prop':
    $qry_props = $xmltree->GetPath('/urn:ietf:params:xml:ns:carddav:addressbook-query/'.$proptype.'/*');
    foreach( $qry_content[0]->GetElements() AS $k => $v ) {
      $propertyname = preg_replace( '/^.*:/', '', $v->GetTag() );
      $properties[$propertyname] = 1;
      if ( $v->GetTag() == 'urn:ietf:params:xml:ns:carddav:address-data' ) get_address_properties($v);
    }
    break;

  case 'DAV::allprop':
    $properties['allprop'] = 1;
    if ( $qry_content[1]->GetTag() == 'DAV::include' ) {
      foreach( $qry_content[1]->GetElements() AS $k => $v ) {
        $include_properties[] = $v->GetTag(); /** $include_properties is referenced in DAVResource where allprop is expanded */
        if ( $v->GetTag() == 'urn:ietf:params:xml:ns:carddav:address-data' ) get_address_properties($v);
      }
    }
    break;

  default:
    $propertyname = preg_replace( '/^.*:/', '', $proptype );
    $properties[$propertyname] = 1;
}

/**
 * There can only be *one* FILTER element.
 */
$qry_filters = $xmltree->GetPath('/urn:ietf:params:xml:ns:carddav:addressbook-query/urn:ietf:params:xml:ns:carddav:filter/*');
if ( count($qry_filters) != 1 ) {
/*  $qry_filters = $qry_filters[0];  // There can only be one FILTER element
}
else { */
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

  dbg_error_log("cardquery","Applying filter for item '%s'", $item->dav_name );
  $vcard = new vComponent( $item->caldav_data );
  return $vcard->TestFilter($filters);
}


/**
 * Process a filter fragment returning an SQL fragment
 */
$need_post_filter = false;
$matchnum = 0;
function SqlFilterCardDAV( $filter, $components, $property = null, $parameter = null ) {
  global $need_post_filter, $target_collection, $matchnum;
  $sql = "";
  $params = array();
  if ( !is_array($filter) ) {
    dbg_error_log( "cardquery", "Filter is of type '%s', but should be an array of XML Tags.", gettype($filter) );
  }

  foreach( $filter AS $k => $v ) {
    $tag = $v->GetTag();
    dbg_error_log("cardquery", "Processing $tag into SQL - %d, '%s', %d\n", count($components), $property, isset($parameter) );

    $not_defined = "";
    switch( $tag ) {
      case 'urn:ietf:params:xml:ns:carddav:text-match':
        $search = $v->GetContent();
        $negate = $v->GetAttribute("negate-condition");
        $collation = $v->GetAttribute("collation");
        switch( strtolower($collation) ) {
          case 'i;octet':
            $comparison = 'LIKE';
            break;
          case 'i;ascii-casemap':
          case 'i;unicode-casemap':
          default:
            $comparison = 'ILIKE';
            break;
        }
        $pname = ':text_match_'.$matchnum++;
        $params[$pname] = '%'.$search.'%';
        dbg_error_log("cardquery", " text-match: (%s%s %s '%s') ", (isset($negate) && strtolower($negate) == "yes" ? "NOT ": ""),
                                          $property, $comparison, $params[$pname] );
        $sql .= sprintf( "AND (%s%s %s $pname) ", (isset($negate) && strtolower($negate) == "yes" ? "NOT ": ""),
                                          $property, $comparison );
        break;

      case 'urn:ietf:params:xml:ns:carddav:prop-filter':
        $propertyname = $v->GetAttribute("name");
        switch( $propertyname ) {
          case 'VERSION':
          case 'UID':
          case 'NICKNAME':
          case 'FN':
          case 'NOTE':
          case 'ORG':
          case 'URL':
          case 'FBURL':
          case 'CALADRURI':
          case 'CALURI':
            $property = strtolower($propertyname);
            break;

          case 'N':
            $property = 'name';
            break;

          default:
            $need_post_filter = true;
            dbg_error_log("cardquery", "Could not handle 'prop-filter' on %s in SQL", $propertyname );
            continue;
        }
        $subfilter = $v->GetContent();
        $success = SqlFilterCardDAV( $subfilter, $components, $property, $parameter );
        if ( $success === false ) continue; else {
          $sql .= $success['sql'];
          $params = array_merge( $params, $success['params'] );
        }
        break;

      case 'urn:ietf:params:xml:ns:carddav:param-filter':
        $need_post_filter = true;
        return false; /** Figure out how to handle PARAM-FILTER conditions in the SQL */
        /*
        $parameter = $v->GetAttribute("name");
        $subfilter = $v->GetContent();
        $success = SqlFilterCardDAV( $subfilter, $components, $property, $parameter );
        if ( $success === false ) continue; else {
          $sql .= $success['sql'];
          $params = array_merge( $params, $success['params'] );
        }
        break;
        */

      default:
        dbg_error_log("cardquery", "Could not handle unknown tag '%s' in calendar query report", $tag );
        break;
    }
  }
  dbg_error_log("cardquery", "Generated SQL was '%s'", $sql );
  return array( 'sql' => $sql, 'params' => $params );
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
if ( ! ($target_collection->IsAddressbook() || $target_collection->IsSchedulingCollection()) ) {
  $request->DoResponse( 403, translate('The addressbook-query report must be run against an addressbook collection') );
}

/**
* @todo Once we are past DB version 1.2.1 we can change this query more radically.  The best performance to
* date seems to be:
*   SELECT caldav_data.*,address_item.* FROM collection JOIN address_item USING (collection_id,user_no)
*         JOIN caldav_data USING (dav_id) WHERE collection.dav_name = '/user1/home/'
*              AND caldav_data.caldav_type = 'VEVENT' ORDER BY caldav_data.user_no, caldav_data.dav_name;
*/

$params = array();
$where = ' WHERE caldav_data.collection_id = ' . $target_collection->resource_id();
if ( is_array($qry_filters) ) {
  dbg_log_array( 'cardquery', 'qry_filters', $qry_filters, true );
  $components = array();
  $filter_fragment =  SqlFilterCardDAV( $qry_filters, $components );
  if ( $filter_fragment !== false ) {
    $where .= ' '.$filter_fragment['sql'];
    $params = $filter_fragment['params'];
  }
}
else {
  dbg_error_log( 'cardquery', 'No query filters' );
}

$sql = 'SELECT * FROM caldav_data INNER JOIN addressbook_resource USING(dav_id)'. $where;
if ( isset($c->strict_result_ordering) && $c->strict_result_ordering ) $sql .= " ORDER BY dav_id";
$qry = new AwlQuery( $sql, $params );
if ( $qry->Exec("cardquery",__LINE__,__FILE__) && $qry->rows() > 0 ) {
  while( $address_object = $qry->Fetch() ) {
    if ( !$need_post_filter || apply_filter( $qry_filters, $address_object ) ) {
      if ( $bound_from != $target_collection->dav_name() ) {
        $address_object->dav_name = str_replace( $bound_from, $target_collection->dav_name(), $address_object->dav_name);
      }
      if ( count($address_data_properties) > 0 ) {
        $vcard = new VCard($address_object->caldav_data);
        $vcard->MaskProperties($address_data_properties);
        $address_object->caldav_data = $vcard->Render();
      }
      $responses[] = component_to_xml( $properties, $address_object );
    }
  }
}
$multistatus = new XMLElement( "multistatus", $responses, $reply->GetXmlNsArray() );

$request->XMLResponse( 207, $multistatus );
