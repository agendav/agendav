<?php
/**
* CalDAV Server - handle OPTIONS method
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst .Net Ltd, Morphoss Ltd <http://www.morphoss.com/>
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/
dbg_error_log("OPTIONS", "method handler");

include_once('DAVResource.php');
$resource = new DAVResource($request->path);

$resource->NeedPrivilege( 'DAV::read', true );

if ( !$resource->Exists() ) {
  $request->DoResponse( 404, translate("No collection found at that location.") );
}

$allowed = implode( ', ', array_keys($resource->FetchSupportedMethods()) );
header( 'Allow: '.$allowed);

$request->DoResponse( 200, "" );

