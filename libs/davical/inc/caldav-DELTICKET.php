<?php
/**
* CalDAV Server - handle DELTICKET method in line with defunct proposed RFC
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
dbg_error_log('DELTICKET', 'method handler');
require_once('DAVResource.php');

if ( ! $request->HavePrivilegeTo('DAV::unbind') && $request->ticket->owner() != $session->principal_id ) {
  $request->NeedPrivilege('DAV::unbind');
}

if ( ! isset($request->ticket) ) {
  if ( isset($_GET['ticket']) || isset($_SERVER['HTTP_TICKET']) ) {
    $r = new DAVResource($request->path);
    if ( ! $r->Exists() ) {
      $request->PreconditionFailed(404,'not-found');
    }
    else {
      $request->PreconditionFailed(412,'ticket-does-not-exist','The specified ticket does not exist');
    }
  }
  else
    $request->MalformedRequest('No ticket specified');
}

$qry = new AwlQuery('DELETE FROM access_ticket WHERE ticket_id=:ticket_id', array( ':ticket_id'   => $request->ticket->id() ) );
if ( $qry->Exec('DELTICKET', __LINE__, __FILE__) ) {
  $request->DoResponse( 204 );
}
$request->DoResponse( 500 );
