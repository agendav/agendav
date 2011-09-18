<?php
/**
* CalDAV Server - handle PROPPATCH method
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Morphoss Ltd - http://www.morphoss.com/
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2
*/
dbg_error_log("PROPPATCH", "method handler");

require_once('iCalendar.php');
require_once('DAVResource.php');

$dav_resource = new DAVResource($request->path);
if ( ! ($dav_resource->HavePrivilegeTo('DAV::write-properties') || $dav_resource->IsBinding() ) ) {
  $request->DoResponse( 403 );
}

$position = 0;
$xmltree = BuildXMLTree( $request->xml_tags, $position);

// echo $xmltree->Render();

if ( $xmltree->GetTag() != "DAV::propertyupdate" ) {
  $request->DoResponse( 403 );
}

/**
* Find the properties being set, and the properties being removed
*/
$setprops = $xmltree->GetPath("/DAV::propertyupdate/DAV::set/DAV::prop/*");
$rmprops  = $xmltree->GetPath("/DAV::propertyupdate/DAV::remove/DAV::prop/*");

/**
* We build full status responses for failures.  For success we just record
* it, since the multistatus response only applies to failure.  While it is
* not explicitly stated in RFC2518, from reading between the lines (8.2.1)
* a success will return 200 OK [with an empty response].
*/
$failure   = array();
$success   = array();

/**
* Not much for it but to process the incoming settings in a big loop, doing
* the special-case stuff as needed and falling through to a default which
* stuffs the property somewhere we will be able to retrieve it from later.
*/
$qry = new AwlQuery();
$qry->Begin();
$setcalendar = count($xmltree->GetPath('/DAV::propertyupdate/DAV::set/DAV::prop/DAV::resourcetype/urn:ietf:params:xml:ns:caldav:calendar'));
foreach( $setprops AS $k => $setting ) {
  $tag = $setting->GetTag();
  $content = $setting->RenderContent();

  switch( $tag ) {

    case 'DAV::displayname':
      /**
      * Can't set displayname on resources - only collections or principals
      */
      if ( $dav_resource->IsCollection() || $dav_resource->IsPrincipal() ) {
        if ( $dav_resource->IsBinding() ) {
          $qry->QDo('UPDATE dav_binding SET dav_displayname = :displayname WHERE dav_name = :dav_name',
                                            array( ':displayname' => $content, ':dav_name' => $dav_resource->dav_name()) );
        }
        else if ( $dav_resource->IsPrincipal() ) {
          $qry->QDo('UPDATE dav_principal SET fullname = :displayname, displayname = :displayname, modified = current_timestamp WHERE user_no = :user_no',
                                            array( ':displayname' => $content, ':user_no' => $request->user_no) );
        }
        else {
          $qry->QDo('UPDATE collection SET dav_displayname = :displayname, modified = current_timestamp WHERE dav_name = :dav_name',
                                            array( ':displayname' => $content, ':dav_name' => $dav_resource->dav_name()) );
        }
        $success[$tag] = 1;
      }
      else {
        $failure['set-'.$tag] = new XMLElement( 'propstat', array(
            new XMLElement( 'prop', new XMLElement($tag)),
            new XMLElement( 'status', 'HTTP/1.1 403 Forbidden' ),
            new XMLElement( 'responsedescription', array(
                              new XMLElement( 'error', new XMLElement( 'cannot-modify-protected-property') ),
                              translate("The displayname may only be set on collections, principals or bindings.") )
                          )

        ));
      }
      break;

    case 'DAV::resourcetype':
      /**
      * We only allow resourcetype setting on a normal collection, and not on a resource, a principal or a bind.
      * Only collections may be CalDAV calendars or addressbooks, and they may not be both.
      */
      $setcollection  = count($setting->GetPath('DAV::resourcetype/DAV::collection'));
      $setaddressbook = count($setting->GetPath('DAV::resourcetype/urn:ietf:params:xml:ns:carddav:addressbook'));
      if ( $dav_resource->IsCollection() && $setcollection && ! $dav_resource->IsPrincipal()
                            && ! $dav_resource->IsBinding() && ! ($setaddressbook && $setcalendar) ) {
        $resourcetypes = $setting->GetPath('DAV::resourcetype/*');
        $resourcetypes = str_replace( "\n", "", implode('',$resourcetypes));
        $qry->QDo('UPDATE collection SET is_calendar = :is_calendar::boolean, is_addressbook = :is_addressbook::boolean,
                     resourcetypes = :resourcetypes WHERE dav_name = :dav_name',
                    array( ':dav_name' => $dav_resource->dav_name(), ':resourcetypes' => $resourcetypes,
                           ':is_calendar' => $setcalendar, ':is_addressbook' => $setaddressbook ) );
        $success[$tag] = 1;
      }
      else {
        $failure['set-'.$tag] = new XMLElement( 'propstat', array(
            new XMLElement( 'prop', new XMLElement($tag)),
            new XMLElement( 'status', 'HTTP/1.1 403 Forbidden' ),
            new XMLElement( 'responsedescription', array(
                              new XMLElement( 'error', new XMLElement( 'cannot-modify-protected-property') ),
                              translate("Resources may not be changed to / from collections.") )
                          )
        ));
      }
      break;

    case 'urn:ietf:params:xml:ns:caldav:schedule-calendar-transp':
      if ( $dav_resource->IsCollection() && ( $dav_resource->IsCalendar() || $setcalendar ) && !$dav_resource->IsBinding() ) {
        $transparency = $setting->GetPath('urn:ietf:params:xml:ns:caldav:schedule-calendar-transp/*');
        $transparency = preg_replace( '{^.*:}', '', $transparency[0]->GetTag());
        $qry->QDo('UPDATE collection SET schedule_transp = :transparency WHERE dav_name = :dav_name',
                    array( ':dav_name' => $dav_resource->dav_name(), ':transparency' => $transparency ) );
        $success[$tag] = 1;
      }
      else {
        $failure['set-'.$tag] = new XMLElement( 'propstat', array(
            new XMLElement( 'prop', new XMLElement($tag)),
              new XMLElement( 'status', 'HTTP/1.1 403 Forbidden' ),
              new XMLElement( 'responsedescription', array(
                                new XMLElement( 'error', new XMLElement( 'cannot-modify-protected-property') ),
                                translate("The CalDAV:schedule-calendar-transp property may only be set on calendars.") )
                            )
        ));
      }
      break;

    case 'urn:ietf:params:xml:ns:caldav:calendar-free-busy-set':
      $failure['set-'.$tag] = new XMLElement( 'propstat', array(
          new XMLElement( 'prop', new XMLElement($tag)),
          new XMLElement( 'status', 'HTTP/1.1 409 Conflict' ),
          new XMLElement( 'responsedescription', translate("The calendar-free-busy-set is superseded by the schedule-transp property of a calendar collection.") )
      ));
      break;

    case 'urn:ietf:params:xml:ns:caldav:calendar-timezone':
      if ( $dav_resource->IsCollection() && $dav_resource->IsCalendar() && ! $dav_resource->IsBinding() ) {
        $tzcomponent = $setting->GetPath('urn:ietf:params:xml:ns:caldav:calendar-timezone');
        $tzstring = $tzcomponent[0]->GetContent();
        $calendar = new iCalendar( array( 'icalendar' => $tzstring) );
        $timezones = $calendar->component->GetComponents('VTIMEZONE');
        if ( $timezones === false || count($timezones) == 0 ) break;
        $tz = $timezones[0];  // Backward compatibility
        $tzid = $tz->GetPValue('TZID');
        $qry->QDo('UPDATE collection SET timezone = :tzid WHERE dav_name = :dav_name',
                                       array( ':tzid' => $tzid, ':dav_name' => $dav_resource->dav_name()) );
      }
      else {
        $failure['set-'.$tag] = new XMLElement( 'propstat', array(
            new XMLElement( 'prop', new XMLElement($tag)),
            new XMLElement( 'status', 'HTTP/1.1 403 Forbidden' ),
            new XMLElement( 'responsedescription', array(
                              new XMLElement( 'error', new XMLElement( 'cannot-modify-protected-property') ),
                              translate("calendar-timezone property is only valid for a calendar.") )
                          )
        ));
      }
      break;

    /**
    * The following properties are read-only, so they will cause the request to fail
    */
    case 'http://calendarserver.org/ns/:getctag':
    case 'DAV::owner':
    case 'DAV::principal-collection-set':
    case 'urn:ietf:params:xml:ns:caldav:calendar-user-address-set':
    case 'urn:ietf:params:xml:ns:caldav:schedule-inbox-URL':
    case 'urn:ietf:params:xml:ns:caldav:schedule-outbox-URL':
    case 'DAV::getetag':
    case 'DAV::getcontentlength':
    case 'DAV::getcontenttype':
    case 'DAV::getlastmodified':
    case 'DAV::creationdate':
    case 'DAV::lockdiscovery':
    case 'DAV::supportedlock':
      $failure['set-'.$tag] = new XMLElement( 'propstat', array(
          new XMLElement( 'prop', new XMLElement($tag)),
          new XMLElement( 'status', 'HTTP/1.1 403 Forbidden' ),
          new XMLElement( 'responsedescription', array(
                               new XMLElement( 'error', new XMLElement( 'cannot-modify-protected-property') ),
                               translate("Property is read-only") )
                        )
      ));
      break;

    /**
    * If we don't have any special processing for the property, we just store it verbatim (which will be an XML fragment).
    */
    default:
      $qry->QDo('SELECT set_dav_property( :dav_name, :user_no, :tag::text, :value::text)',
            array( ':dav_name' => $dav_resource->dav_name(), ':user_no' => $request->user_no, ':tag' => $tag, ':value' => $content) );
      $success[$tag] = 1;
      break;
  }
}



foreach( $rmprops AS $k => $setting ) {
  $tag = $setting->GetTag();
  $content = $setting->RenderContent();

  switch( $tag ) {

    case 'DAV::resourcetype':
      $failure['rm-'.$tag] = new XMLElement( 'propstat', array(
          new XMLElement( 'prop', new XMLElement($tag)),
            new XMLElement( 'status', 'HTTP/1.1 403 Forbidden' ),
            new XMLElement( 'responsedescription', array(
                              new XMLElement( 'error', new XMLElement( 'cannot-modify-protected-property') ),
                              translate("DAV::resourcetype may only be set to a new value, it may not be removed.") )
                          )
      ));
      break;

    case 'urn:ietf:params:xml:ns:caldav:calendar-timezone':
      if ( $dav_resource->IsCollection() && $dav_resource->IsCalendar() && ! $dav_resource->IsBinding() ) {
        $qry->QDo('UPDATE collection SET timezone = NULL WHERE dav_name = :dav_name', array( ':dav_name' => $dav_resource->dav_name()) );
      }
      else {
        $failure['set-'.$tag] = new XMLElement( 'propstat', array(
            new XMLElement( 'prop', new XMLElement($tag)),
            new XMLElement( 'status', 'HTTP/1.1 403 Forbidden' ),
            new XMLElement( 'responsedescription', array(
                              new XMLElement( 'error', new XMLElement( 'cannot-modify-protected-property') ),
                              translate("calendar-timezone property is only valid for a calendar.") )
                          )
        ));
      }
      break;

    /**
    * The following properties are read-only, so they will cause the request to fail
    */
    case 'http://calendarserver.org/ns/:getctag':
    case 'DAV::owner':
    case 'DAV::principal-collection-set':
    case 'urn:ietf:params:xml:ns:caldav:CALENDAR-USER-ADDRESS-SET':
    case 'urn:ietf:params:xml:ns:caldav:schedule-inbox-URL':
    case 'urn:ietf:params:xml:ns:caldav:schedule-outbox-URL':
    case 'DAV::getetag':
    case 'DAV::getcontentlength':
    case 'DAV::getcontenttype':
    case 'DAV::getlastmodified':
    case 'DAV::creationdate':
    case 'DAV::displayname':
    case 'DAV::lockdiscovery':
    case 'DAV::supportedlock':
      $failure['rm-'.$tag] = new XMLElement( 'propstat', array(
          new XMLElement( 'prop', new XMLElement($tag)),
          new XMLElement( 'status', 'HTTP/1.1 409 Conflict' ),
          new XMLElement('responsedescription', translate("Property is read-only") )
      ));
      dbg_error_log( 'PROPPATCH', ' RMProperty %s is read only and cannot be removed', $tag);
      break;

    /**
    * If we don't have any special processing then we must have to just delete it.  Nonexistence is not failure.
    */
    default:
      $qry->QDo('DELETE FROM property WHERE dav_name=:dav_name AND property_name=:property_name',
                  array( ':dav_name' => $dav_resource->dav_name(), ':property_name' => $tag) );
      $success[$tag] = 1;
      break;
  }
}


/**
* If we have encountered any instances of failure, the whole damn thing fails.
*/
if ( count($failure) > 0 ) {
  foreach( $success AS $tag => $v ) {
    // Unfortunately although these succeeded, we failed overall, so they didn't happen...
    $failure[] = new XMLElement( 'propstat', array(
        new XMLElement( 'prop', new XMLElement($tag)),
        new XMLElement( 'status', 'HTTP/1.1 424 Failed Dependency' ),
    ));
  }

  $url = ConstructURL($request->path);
  array_unshift( $failure, new XMLElement('href', $url ) );
  $failure[] = new XMLElement('responsedescription', translate("Some properties were not able to be changed.") );

  $qry->Rollback();

  $multistatus = new XMLElement( "multistatus", new XMLElement( 'response', $failure ), array('xmlns'=>'DAV:') );
  $request->DoResponse( 207, $multistatus->Render(0,'<?xml version="1.0" encoding="utf-8" ?>'), 'text/xml; charset="utf-8"' );

}

/**
* Otherwise we will try and do the SQL. This is inside a transaction, so PostgreSQL guarantees the atomicity
*/
;
if ( $qry->Commit() ) {
  $url = ConstructURL($request->path);
  $href = new XMLElement('href', $url );
  $desc = new XMLElement('responsedescription', translate("All requested changes were made.") );

  $multistatus = new XMLElement( "multistatus", new XMLElement( 'response', array( $href, $desc ) ), array('xmlns'=>'DAV:') );
  $request->DoResponse( 200, $multistatus->Render(0,'<?xml version="1.0" encoding="utf-8" ?>'), 'text/xml; charset="utf-8"' );
}

/**
* Or it was all crap.
*/
$request->DoResponse( 500 );

exit(0);

