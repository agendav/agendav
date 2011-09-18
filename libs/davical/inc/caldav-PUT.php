<?php
/**
* CalDAV Server - handle PUT method
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst .Net Ltd, Morphoss Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/
dbg_error_log("PUT", "method handler");

require_once('DAVResource.php');

$dav_resource = new DAVResource($request->path);
if ( ! $dav_resource->HavePrivilegeTo('DAV::write-content') ) {
  $request->DoResponse(403);
}

if ( ! $dav_resource->Exists() && ! $dav_resource->HavePrivilegeTo('DAV::bind') ) {
  $request->DoResponse(403);
}

if ( ! ini_get('open_basedir') && (isset($c->dbg['ALL']) || (isset($c->dbg['put']) && $c->dbg['put'])) ) {
  $fh = fopen('/tmp/PUT.txt','w');
  if ( $fh ) {
    fwrite($fh,$request->raw_post);
    fclose($fh);
  }
}

include_once('caldav-PUT-functions.php');
controlRequestContainer( $dav_resource->GetProperty('username'), $dav_resource->GetProperty('user_no'), $dav_resource->bound_from(), true);

$lock_opener = $request->FailIfLocked();


if ( $dav_resource->IsCollection()  ) {
  if ( $dav_resource->IsPrincipal() || $dav_resource->IsBinding() || !isset($c->readonly_webdav_collections) || $c->readonly_webdav_collections == true ) {
    $request->DoResponse( 405 ); // Method not allowed
    return;
  }

  $appending = (isset($_GET['mode']) && $_GET['mode'] == 'append' );

  /**
  * CalDAV does not define the result of a PUT on a collection.  We treat that
  * as an import. The code is in caldav-PUT-functions.php
  */
  import_collection($request->raw_post,$request->user_no,$request->path,true, $appending);
  $request->DoResponse( 200 );
  return;
}

$etag = md5($request->raw_post);
$ic = new iCalComponent( $request->raw_post );

if ( ! $dav_resource->Exists() && (isset($request->etag_if_match) && $request->etag_if_match != '') ) {
  /**
  * RFC2068, 14.25:
  * If none of the entity tags match, or if "*" is given and no current
  * entity exists, the server MUST NOT perform the requested method, and
  * MUST return a 412 (Precondition Failed) response.
  */
  $request->PreconditionFailed(412,'if-match');
}

if ( $dav_resource->Exists() ) {
  if ( isset($request->etag_if_match) && $request->etag_if_match != '' && $request->etag_if_match != $dav_resource->unique_tag() ) {
    /**
    * RFC2068, 14.25:
    * If none of the entity tags match, or if "*" is given and no current
    * entity exists, the server MUST NOT perform the requested method, and
    * MUST return a 412 (Precondition Failed) response.
    */
    $request->PreconditionFailed(412,'if-match',sprintf('Existing resource ETag of "%s" does not match "%s"', $dav_resource->unique_tag(), $request->etag_if_match) );
  }
  else if ( isset($request->etag_none_match) && $request->etag_none_match != ''
               && ($request->etag_none_match == $dav_resource->unique_tag() || $request->etag_none_match == '*') ) {
    /**
    * RFC2068, 14.26:
    * If any of the entity tags match the entity tag of the entity that
    * would have been returned in the response to a similar GET request
    * (without the If-None-Match header) on that resource, or if "*" is
    * given and any current entity exists for that resource, then the
    * server MUST NOT perform the requested method.
    */
    $request->PreconditionFailed(412,'if-none-match', translate( 'Existing resource matches "If-None-Match" header - not accepted.'));
  }
}

$put_action_type = ($dav_resource->Exists() ? 'UPDATE' : 'INSERT');

write_resource( $dav_resource->GetProperty('user_no'), $dav_resource->bound_from(), $request->raw_post, $dav_resource->GetProperty('collection_id'),
                                $session->user_no, $etag, $ic, $put_action_type, true, true );

header(sprintf('ETag: "%s"', $etag) );

$request->DoResponse( ($dav_resource->Exists() ? 204 : 201) );
