<?php
/**
* CalDAV Server - handle MKTICKET method in line with defunct proposed RFC
*   from:  http://tools.ietf.org/html/draft-ito-dav-ticket-00
*
* Why are we using a defunct RFC?  Well, we want to support some kind of system
* for providing a URI to people to give out for granting privileged access
* without requiring logins.  Using a defunct proposed spec seems better than
* inventing our own.  As well as Xythos, Cosmo follows this specification,
* with some documented variations, which we will also follow.  In particular
* we use the xmlns="http://www.xythos.com/namespaces/StorageServer" rather
* than the DAV: namespace.
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Morphoss Ltd - http://www.morphoss.com/
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/
dbg_error_log('MKTICKET', 'method handler');
require_once('DAVResource.php');

$request->NeedPrivilege('DAV::bind');

require_once('XMLDocument.php');
$reply = new XMLDocument(array( 'DAV:' => '', 'http://www.xythos.com/namespaces/StorageServer' => 'T' ));

$target = new DAVResource( $request->path );
if ( ! $target->Exists() ) {
  $request->XMLResponse( 404, new XMLElement( 'error', new XMLElement('resource-must-not-be-null'), $reply->GetXmlNsArray() ) );
}

if ( ! isset($request->xml_tags) ) {
  $request->XMLResponse( 400, new XMLElement( 'error', new XMLElement('missing-xml-for-request'), $reply->GetXmlNsArray() ) );
}

$xmltree = BuildXMLTree( $request->xml_tags, $position);
if ( $xmltree->GetTag() != 'http://www.xythos.com/namespaces/StorageServer:ticketinfo' &&
     $xmltree->GetTag() != 'DAV::ticketinfo' ) {
  $request->XMLResponse( 400, new XMLElement( 'error', new XMLElement('invalid-xml-for-request'), $reply->GetXmlNsArray() ) );
}

$ticket_timeout = 'Seconds-3600';
$ticket_privs_array = array('read-free-busy');
foreach( $xmltree->GetContent() AS $k => $v ) {
  // <!ELEMENT ticketinfo (id?, owner?, timeout, visits, privilege)>
  switch( $v->GetTag() ) {
    case 'DAV::timeout':
    case 'http://www.xythos.com/namespaces/StorageServer:timeout':
      $ticket_timeout = $v->GetContent();
      break;

    case 'DAV::privilege':
    case 'http://www.xythos.com/namespaces/StorageServer:privilege':
      $ticket_privs_array = $v->GetElements(); // Ensure we always get an array back
      $ticket_privileges = 0;
      foreach( $ticket_privs_array AS $k1 => $v1 ) {
        $ticket_privileges |= privilege_to_bits( $v1->GetTag() );
      }
      if ( $ticket_privileges & privilege_to_bits('write') )          $ticket_privileges |= privilege_to_bits( 'read' );
      if ( $ticket_privileges & privilege_to_bits('read') )           $ticket_privileges |= privilege_to_bits( array('read-free-busy', 'read-current-user-privilege-set') );
      if ( $ticket_privileges & privilege_to_bits('read-free-busy') ) $ticket_privileges |= privilege_to_bits( 'schedule-query-freebusy');
      break;
  }
}

if ( $ticket_timeout == 'infinity' ) {
  $sql_timeout = null;
}
else if ( preg_match( '{^([a-z]+)-(\d+)$}i', $ticket_timeout, $matches ) ) {
  /** It isn't specified, but timeout seems to be 'unit-number' like 'Seconds-3600', so we make it '3600 Seconds' which PostgreSQL understands */
  $sql_timeout = $matches[2] . ' ' . $matches[1];
}
else {
  $sql_timeout = $ticket_timeout;
}

$collection_id = $target->GetProperty('collection_id');
$resource_id   = $target->GetProperty('dav_id');

$i = 0;
do {
  $ticket_id = substr( str_replace('/', '', str_replace('+', '',base64_encode(sha1(date('r') .rand(0,2100000000) . microtime(true),true)))), 7, 8);
  $qry = new AwlQuery(
    'INSERT INTO access_ticket ( ticket_id, dav_owner_id, privileges, target_collection_id, target_resource_id, expires )
                VALUES( :ticket_id, :owner, :privs::INT::BIT(24), :collection, :resource, (current_timestamp + :expires::interval) )',
    array(
      ':ticket_id'   => $ticket_id,
      ':owner'       => $session->principal_id,
      ':privs'       => $ticket_privileges,
      ':collection'  => $collection_id,
      ':resource'    => $resource_id,
      ':expires'     => $sql_timeout,
    )
  );
  $result = $qry->Exec('MKTICKET', __LINE__, __FILE__);
} while( !$result && $i++ < 2 );

$privs = new XMLElement('privilege');
foreach( bits_to_privilege($ticket_privileges) AS $k => $v ) {
  $reply->NSElement($privs, $v);
}

$ticketinfo = new XMLElement( 'T:ticketinfo', array(
      new XMLElement( 'T:id', $ticket_id),
      new XMLElement( 'owner', $reply->href( ConstructURL('/'.$session->username.'/') ) ),
      $privs,
      new XMLElement( 'T:timeout', $ticket_timeout),
      new XMLElement( 'T:visits', 'infinity')
  )
);

$prop = new XMLElement( "prop", new XMLElement('T:ticketdiscovery', $ticketinfo), $reply->GetXmlNsArray() );
header('Ticket: '.$ticket_id);
$request->XMLResponse( 200, $prop );
