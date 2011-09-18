<?php
/**
* DAViCal CalDAV Server - handle principal-search-property-set report (RFC3744)
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Morphoss Ltd - http://www.morphoss.com/
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/


/**
* Wrap an individual property name as needed
*/
function property_response( &$xmldoc, $property ) {
  $prop = new XMLElement( 'prop' );
  $xmldoc->NSElement($prop, $property );
  return new XMLElement( 'principal-search-property', $prop );
}

$principal_search_property_set = array(
  'DAV::displayname',
  'urn:ietf:params:xml:ns:caldav:calendar-home-set',
  'urn:ietf:params:xml:ns:caldav:calendar-user-address-set'
);

$responses = array();
foreach( $principal_search_property_set AS $k => $tag ) {
  $responses[] = property_response( $reply, $tag );
}


$report = new XMLElement( 'principal-search-property-set', $responses, $reply->GetXmlNsArray() );

$request->XMLResponse( 207, $report );
