<?php
/**
* We support both LOCK and UNLOCK methods in this function
*/

require_once('XMLDocument.php');
$reply = new XMLDocument(array( 'DAV:' => '' ));

if ( ! $request->AllowedTo('write') ) {
  $request->NeedPrivilege( 'write', $request->path );
}

if ( ! isset($request->xml_tags) ) {
  if ( isset($request->lock_token) ) {
    // It's OK for LOCK refresh requests to be empty.
    $request->xml_tags = array();
  }
  else {
    $request->XMLResponse( 400, new XMLElement( 'error', new XMLElement('missing-xml-for-request'), $reply->GetXmlNsArray() ) );
  }
}


$unsupported = array();
$lockinfo = array();
$inside = array();

foreach( $request->xml_tags AS $k => $v ) {

  $tag = $v['tag'];
  dbg_error_log( "LOCK", " Handling Tag '%s' => '%s' ", $k, $v );
  switch ( $tag ) {
    case 'DAV::lockinfo':
      dbg_error_log( "LOCK", ":Request: %s -> %s", $v['type'], $tag );
      if ( $v['type'] == "open" ) {
        $lockscope = "";
        $locktype = "";
        $lockowner = "";
        $inside[$tag] = true;
      }
      else if ( $inside[$tag] && $v['type'] == "close" ) {
        $lockinfo['scope']  = $lockscope;   unset($lockscope);
        $lockinfo['type']   = $locktype;    unset($locktype);
        $lockinfo['owner']  = $lockowner;   unset($lockowner);
        $inside[$tag] = false;
      }
      break;

    case 'DAV::owner':
    case 'DAV::locktype':
    case 'DAV::lockscope':
      dbg_error_log( "LOCK", ":Request: %s -> %s", $v['type'], $tag );
      if ( $inside['DAV::lockinfo'] ) {
        if ( $v['type'] == "open" ) {
          $inside[$tag] = true;
        }
        else if ( $inside[$tag] && $v['type'] == "close" ) {
          $inside[$tag] = false;
        }
      }
      break;

    /*case 'DAV::SHARED': */ /** Shared lock is not supported yet */
    case 'DAV::exclusive':
      dbg_error_log( "LOCK", ":Request: %s -> %s", $v['type'], $tag );
      if ( $inside['DAV::lockscope'] && $v['type'] == "complete" ) {
        $lockscope = strtolower(substr($tag,5));
      }
      break;

    /* case 'DAV::READ': */ /** RFC2518 is pretty vague about read locks */
    case 'DAV::write':
      dbg_error_log( "LOCK", ":Request: %s -> %s", $v['type'], $tag );
      if ( $inside['DAV::locktype'] && $v['type'] == "complete" ) {
        $locktype = strtolower(substr($tag,5));
      }
      break;

    case 'DAV::href':
      dbg_error_log( "LOCK", ":Request: %s -> %s", $v['type'], $tag );
      dbg_log_array( "LOCK", "DAV:href", $v, true );
      if ( $inside['DAV::owner'] && $v['type'] == "complete" ) {
        $lockowner = $v['value'];
      }
      break;

    default:
      if ( preg_match('/^(.*):([^:]+)$/', $tag, $matches) ) {
        $unsupported[$matches[2]] = $matches[1];
      }
      else {
        $unsupported[$tag] = "";
      }
      dbg_error_log( "LOCK", "Unhandled tag >>%s<<", $tag);
  }
}




$request->UnsupportedRequest($unsupported); // Won't return if there was unsupported stuff.

$lock_opener = $request->FailIfLocked();


if ( $request->method == "LOCK" ) {
  dbg_error_log( "LOCK", "Attempting to lock resource '%s'", $request->path);
  if ( ($lock_token = $request->IsLocked()) ) { // NOTE Assignment in if() is expected here.
    $sql = 'UPDATE locks SET start = current_timestamp WHERE opaquelocktoken = :lock_token';
    $params = array( ':lock_token' => $lock_token);
  }
  else {
    /**
    * A fresh lock
    */
    $lock_token = uuid();
    $sql = 'INSERT INTO locks ( dav_name, opaquelocktoken, type, scope, depth, owner, timeout, start )
             VALUES( :dav_name, :lock_token, :type, :scope, :request_depth, :owner, :timeout::interval, current_timestamp )';
    $params = array(
        ':dav_name'      => $request->path,
        ':lock_token'    => $lock_token,
        ':type'          => $lockinfo['type'],
        ':scope'         => $lockinfo['scope'],
        ':request_depth' => $request->depth,
        ':owner'         => $lockinfo['owner'],
        ':timeout'       => $request->timeout.' seconds'
    );
    header( "Lock-Token: <opaquelocktoken:$lock_token>" );
  }
  $qry = new AwlQuery($sql, $params  );
  $qry->Exec("LOCK",__LINE__,__FILE__);

  $lock_row = $request->GetLockRow($lock_token);
  $activelock = array(
      new XMLElement( 'locktype',  new XMLElement( $lock_row->type )),
      new XMLElement( 'lockscope', new XMLElement( $lock_row->scope )),
      new XMLElement( 'depth',     $request->GetDepthName() ),
      new XMLElement( 'owner',     new XMLElement( 'href', $lock_row->owner )),
      new XMLElement( 'timeout',   'Second-'.$request->timeout),
      new XMLElement( 'locktoken', new XMLElement( 'href', 'opaquelocktoken:'.$lock_token ))
  );
  $response = new XMLElement("lockdiscovery", new XMLElement( "activelock", $activelock), array("xmlns" => "DAV:") );
}
elseif (  $request->method == "UNLOCK" ) {
  /**
  * @TODO: respond with preconditionfailed(409,'lock-token-matches-request-uri') if
  * there is no lock to be deleted.
  */
  dbg_error_log( "LOCK", "Attempting to unlock resource '%s'", $request->path);
  if ( ($lock_token = $request->IsLocked()) ) { // NOTE Assignment in if() is expected here.
    $sql = 'DELETE FROM locks WHERE opaquelocktoken = :lock_token';
    $qry = new AwlQuery($sql, array( ':lock_token' => $lock_token) );
    $qry->Exec("LOCK",__LINE__,__FILE__);
  }
  $request->DoResponse( 204 );
}


$prop = new XMLElement( "prop", $response, array('xmlns'=>'DAV:') );
// dbg_log_array( "LOCK", "XML", $response, true );
$xmldoc = $prop->Render(0,'<?xml version="1.0" encoding="utf-8" ?>');
$request->DoResponse( 200, $xmldoc, 'text/xml; charset="utf-8"' );

