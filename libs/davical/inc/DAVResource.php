<?php
/**
* An object representing a DAV 'resource'
*
* @package   davical
* @subpackage   Resource
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Morphoss Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once('AwlQuery.php');
require_once('DAVTicket.php');


/**
* A class for things to do with a DAV Resource
*
* @package   davical
*/
class DAVResource
{
  /**
  * @var The partial URL of the resource within our namespace, which this resource is being retrieved as
  */
  protected $dav_name;

  /**
  * @var Boolean: does the resource actually exist yet?
  */
  protected $exists;

  /**
  * @var The unique etag associated with the current version of the resource
  */
  protected $unique_tag;

  /**
  * @var The actual resource content, if it exists and is not a collection
  */
  protected $resource;

  /**
  * @var The parent of the resource, which will always be a collection
  */
  protected $parent;

  /**
  * @var The types of the resource, possibly multiple
  */
  protected $resourcetypes;

  /**
  * @var The type of the content
  */
  protected $contenttype;

  /**
  * @var The canonical name which this resource exists at
  */
  protected $bound_from;

  /**
  * @var An object which is the collection record for this resource, or for it's container
  */
  private $collection;

  /**
  * @var An object which is the principal for this resource, or would be if it existed.
  */
  private $principal;

  /**
  * @var A bit mask representing the current user's privileges towards this DAVResource
  */
  private $privileges;

  /**
  * @var True if this resource is a collection of any kind
  */
  private $_is_collection;

  /**
  * @var True if this resource is a principal-URL
  */
  private $_is_principal;

  /**
  * @var True if this resource is a calendar collection
  */
  private $_is_calendar;

  /**
  * @var True if this resource is a binding to another resource
  */
  private $_is_binding;

  /**
  * @var True if this resource is an addressbook collection
  */
  private $_is_addressbook;

  /**
  * @var True if this resource is, or is in, a proxy collection
  */
  private $_is_proxy_request;

  /**
  * @var An array of the methods we support on this resource.
  */
  private $supported_methods;

  /**
  * @var An array of the reports we support on this resource.
  */
  private $supported_reports;

  /**
  * @var An array of the dead properties held for this resource
  */
  private $dead_properties;

  /**
  * @var An array of the component types we support on this resource.
  */
  private $supported_components;

  /**
  * @var An array of DAVTicket objects if any apply to this resource, such as via a bind.
  */
  private $tickets;

  /**
  * Constructor
  * @param mixed $parameters If null, an empty Resourced is created.
  *     If it is an object then it is expected to be a record that was
  *     read elsewhere.
  */
  function __construct( $parameters = null ) {
    $this->exists        = null;
    $this->bound_from    = null;
    $this->dav_name      = null;
    $this->unique_tag    = null;
    $this->resource      = null;
    $this->collection    = null;
    $this->principal     = null;
    $this->parent        = null;
    $this->resourcetypes = null;
    $this->contenttype   = null;
    $this->privileges    = null;
    $this->dead_properties   = null;
    $this->supported_methods = null;
    $this->supported_reports = null;

    $this->_is_collection    = false;
    $this->_is_principal     = false;
    $this->_is_calendar      = false;
    $this->_is_binding       = false;
    $this->_is_addressbook   = false;
    $this->_is_proxy_request = false;
    if ( isset($parameters) && is_object($parameters) ) {
      $this->FromRow($parameters);
    }
    else if ( isset($parameters) && is_array($parameters) ) {
      if ( isset($parameters['path']) ) {
        $this->FromPath($parameters['path']);
      }
    }
    else if ( isset($parameters) && is_string($parameters) ) {
      $this->FromPath($parameters);
    }
  }


  /**
  * Initialise from a database row
  * @param object $row The row from the DB.
  */
  function FromRow($row) {
    global $c;

    if ( $row == null ) return;

    $this->exists = true;
    $this->dav_name = $row->dav_name;
    $this->bound_from = (isset($row->bound_from)? $row->bound_from : $row->dav_name);
    $this->_is_collection = preg_match( '{/$}', $this->dav_name );

    if ( $this->_is_collection ) {
      $this->contenttype = 'httpd/unix-directory';
      $this->collection = (object) array();
      $this->resource_id = $row->collection_id;

      $this->_is_principal = preg_match( '{^/[^/]+/$}', $this->dav_name );
      if ( preg_match( '#^(/principals/[^/]+/[^/]+)/?$#', $this->dav_name, $matches) ) {
        $this->collection->dav_name = $matches[1].'/';
        $this->collection->type = 'principal_link';
        $this->_is_principal = true;
      }
    }
    else {
      $this->resource = (object) array();
      if ( isset($row->dav_id) ) $this->resource_id = $row->dav_id;
    }

    dbg_error_log( 'DAVResource', ':FromRow: Named "%s" is%s a collection.', $this->dav_name, ($this->_is_collection?'':' not') );

    foreach( $row AS $k => $v ) {
      if ( $this->_is_collection )
        $this->collection->{$k} = $v;
      else
        $this->resource->{$k} = $v;
      switch ( $k ) {
        case 'created':
        case 'modified':
        case 'resourcetypes':
          $this->{$k} = $v;
          break;

        case 'dav_etag':
          $this->unique_tag = '"'.$v.'"';
          break;

      }
    }

    if ( $this->_is_collection ) {
      if ( !isset( $this->collection->type ) || $this->collection->type == 'collection' ) {
        if ( $this->_is_principal )
          $this->collection->type = 'principal';
        else if ( $row->is_calendar == 't' ) {
          $this->collection->type = 'calendar';
        }
        else if ( $row->is_addressbook == 't' ) {
          $this->collection->type = 'addressbook';
        }
        else if ( isset($row->is_proxy) && $row->is_proxy == 't' ) {
          $this->collection->type = 'proxy';
        }
        else if ( preg_match( '#^((/[^/]+/)\.(in|out)/)[^/]*$#', $this->dav_name, $matches ) )
          $this->collection->type = 'schedule-'. $matches[3]. 'box';
        else if ( $this->dav_name == '/' )
          $this->collection->type = 'root';
        else
          $this->collection->type = 'collection';
      }

      $this->_is_calendar     = ($this->collection->is_calendar == 't');
      $this->_is_addressbook  = ($this->collection->is_addressbook == 't');
      $this->_is_proxy_request= ($this->collection->type == 'proxy');
      if ( $this->_is_principal && !isset($this->resourcetypes) ) {
        $this->resourcetypes = '<DAV::collection/><DAV::principal/>';
      }
      else if ( $this->_is_proxy_request ) {
        $this->resourcetypes  = $this->collection->resourcetypes;
      }
      if ( isset($this->collection->dav_displayname) ) $this->collection->displayname = $this->collection->dav_displayname;
    }
    else {
      $this->resourcetypes = '';
      if ( isset($this->resource->caldav_data) ) {
        if ( substr($this->resource->caldav_data,0,15) == 'BEGIN:VCALENDAR' ) {
          $this->contenttype = 'text/calendar';
        }
        else if ( strtoupper(substr($this->resource->caldav_data,0,11)) == 'BEGIN:VCARD' ) {
          $this->contenttype = 'text/vcard';
        }
        else if ( strtoupper(substr($this->resource->caldav_data,0,11)) == 'BEGIN:VLIST' ) {
          $this->contenttype = 'text/x-vlist';
        }
        $this->resource->displayname = $this->resource->summary;
      }
    }
  }


  /**
  * Initialise from a path
  * @param object $inpath The path to populate the resource data from
  */
  function FromPath($inpath) {
    global $c;

    $this->dav_name = DeconstructURL($inpath);

    $this->FetchCollection();
    if ( $this->_is_collection ) {
      if ( $this->_is_principal || $this->collection->type == 'principal' ) $this->FetchPrincipal();
    }
    else {
      $this->FetchResource();
    }
    dbg_error_log( 'DAVResource', ':FromPath: Path "%s" is%s a collection%s.',
               $this->dav_name, ($this->_is_collection?' '.$this->resourcetypes:' not'), ($this->_is_principal?' and a principal':'') );
  }


  /**
  * Find the collection associated with this resource.
  */
  function FetchCollection() {
    global $c, $session, $request;
    /**
    * RFC4918, 8.3: Identifiers for collections SHOULD end in '/'
    *    - also discussed at more length in 5.2
    *
    * So we look for a collection which matches one of the following URLs:
    *  - The exact request.
    *  - If the exact request, doesn't end in '/', then the request URL with a '/' appended
    *  - The request URL truncated to the last '/'
    * The collection URL for this request is therefore the longest row in the result, so we
    * can "... ORDER BY LENGTH(dav_name) DESC LIMIT 1"
    */
    dbg_error_log( 'DAVResource', ':FetchCollection: Looking for collection for "%s".', $this->dav_name );

    $this->collection = (object) array(
      'collection_id' => -1,
      'type' => 'nonexistent',
      'is_calendar' => false, 'is_principal' => false, 'is_addressbook' => false
    );

    $base_sql = 'SELECT collection.*, path_privs(:session_principal::int8, collection.dav_name,:scan_depth::int), ';
    $base_sql .= 'p.principal_id, p.type_id AS principal_type_id, ';
    $base_sql .= 'p.displayname AS principal_displayname, p.default_privileges AS principal_default_privileges, ';
    $base_sql .= 'time_zone.tz_spec ';
    $base_sql .= 'FROM collection LEFT JOIN principal p USING (user_no) ';
    $base_sql .= 'LEFT JOIN time_zone ON (collection.timezone=time_zone.tz_id) ';
    $base_sql .= 'WHERE ';
    $sql = $base_sql .'collection.dav_name = :raw_path ';
    $params = array( ':raw_path' => $this->dav_name, ':session_principal' => $session->principal_id, ':scan_depth' => $c->permission_scan_depth );
    if ( !preg_match( '#/$#', $this->dav_name ) ) {
      $sql .= ' OR collection.dav_name = :up_to_slash OR collection.dav_name = :plus_slash ';
      $params[':up_to_slash'] = preg_replace( '#[^/]*$#', '', $this->dav_name);
      $params[':plus_slash']  = $this->dav_name.'/';
    }
    $sql .= 'ORDER BY LENGTH(collection.dav_name) DESC LIMIT 1';
    $qry = new AwlQuery( $sql, $params );
    if ( $qry->Exec('DAVResource') && $qry->rows() == 1 && ($row = $qry->Fetch()) ) {
      $this->collection = $row;
      $this->collection->exists = true;
      if ( $row->is_calendar == 't' )
        $this->collection->type = 'calendar';
      else if ( $row->is_addressbook == 't' )
        $this->collection->type = 'addressbook';
      else if ( preg_match( '#^((/[^/]+/)\.(in|out)/)[^/]*$#', $this->dav_name, $matches ) )
        $this->collection->type = 'schedule-'. $matches[3]. 'box';
      else
        $this->collection->type = 'collection';
    }
    else if ( preg_match( '{^( ( / ([^/]+) / ) \.(in|out)/ ) [^/]*$}x', $this->dav_name, $matches ) ) {
      // The request is for a scheduling inbox or outbox (or something inside one) and we should auto-create it
      $params = array( ':username' => $matches[3], ':parent_container' => $matches[2], ':dav_name' => $matches[1] );
      $params[':boxname'] = ($matches[4] == 'in' ? ' Inbox' : ' Outbox');
      $this->collection_type = 'schedule-'. $matches[4]. 'box';
      $params[':resourcetypes'] = sprintf('<DAV::collection/><urn:ietf:params:xml:ns:caldav:%s/>', $this->collection_type );
      $sql = <<<EOSQL
INSERT INTO collection ( user_no, parent_container, dav_name, dav_displayname, is_calendar, created, modified, dav_etag, resourcetypes )
    VALUES( (SELECT user_no FROM usr WHERE username = :username),
            :parent_container, :dav_name,
            (SELECT fullname FROM usr WHERE username = :username) || :boxname,
             FALSE, current_timestamp, current_timestamp, '1', :resourcetypes )
EOSQL;
      $qry = new AwlQuery( $sql, $params );
      $qry->Exec('DAVResource');
      dbg_error_log( 'DAVResource', 'Created new collection as "%s".', trim($params[':boxname']) );

      $params = array( ':raw_path' => $this->dav_name, ':session_principal' => $session->principal_id, ':scan_depth' => $c->permission_scan_depth );
      $qry = new AwlQuery( $base_sql . ' dav_name = :raw_path', $params );
      if ( $qry->Exec('DAVResource') && $qry->rows() == 1 && ($row = $qry->Fetch()) ) {
        $this->collection = $row;
        $this->collection->exists = true;
        $this->collection->type = $this->collection_type;
      }
    }
    else if ( preg_match( '#^(/([^/]+)/calendar-proxy-(read|write))/?[^/]*$#', $this->dav_name, $matches ) ) {
      $this->collection->type = 'proxy';
      $this->_is_proxy_request = true;
      $this->proxy_type = $matches[3];
      $this->collection->dav_name = $this->dav_name;
      $this->collection->dav_displayname = sprintf( '%s proxy %s', $matches[2], $matches[3] );
      $this->collection->exists = true;
      $this->collection->parent_container = $matches[1] . '/';
    }
    else if ( preg_match( '#^(/[^/]+)/?$#', $this->dav_name, $matches)
           || preg_match( '#^((/principals/[^/]+/)[^/]+)/?$#', $this->dav_name, $matches) ) {
      $this->_is_principal = true;
      $this->FetchPrincipal();
    }
    else if ( $this->dav_name == '/' ) {
      $this->collection->dav_name = '/';
      $this->collection->type = 'root';
      $this->collection->exists = true;
      $this->collection->displayname = $c->system_name;
      $this->collection->default_privileges = (1 | 16 | 32);
      $this->collection->parent_container = '/';
    }
    else {
      $sql = <<<EOSQL
SELECT collection.*, path_privs(:session_principal::int8, collection.dav_name,:scan_depth::int), p.principal_id,
    p.type_id AS principal_type_id, p.displayname AS principal_displayname, p.default_privileges AS principal_default_privileges,
    time_zone.tz_spec, dav_binding.access_ticket_id, dav_binding.parent_container AS bind_parent_container,
    dav_binding.dav_displayname, owner.dav_name AS bind_owner_url, dav_binding.dav_name AS bound_to
FROM dav_binding
    LEFT JOIN collection ON (collection.collection_id=bound_source_id)
    LEFT JOIN principal p USING (user_no)
    LEFT JOIN dav_principal owner ON (dav_binding.dav_owner_id=owner.principal_id)
    LEFT JOIN time_zone ON (collection.timezone=time_zone.tz_id)
 WHERE dav_binding.dav_name = :raw_path
EOSQL;
      $params = array( ':raw_path' => $this->dav_name, ':session_principal' => $session->principal_id, ':scan_depth' => $c->permission_scan_depth );
      if ( !preg_match( '#/$#', $this->dav_name ) ) {
        $sql .= ' OR dav_binding.dav_name = :up_to_slash OR collection.dav_name = :plus_slash ';
        $params[':up_to_slash'] = preg_replace( '#[^/]*$#', '', $this->dav_name);
        $params[':plus_slash']  = $this->dav_name.'/';
      }
      $sql .= ' ORDER BY LENGTH(dav_binding.dav_name) DESC LIMIT 1';
      $qry = new AwlQuery( $sql, $params );
      if ( $qry->Exec('DAVResource',__LINE__,__FILE__) && $qry->rows() == 1 && ($row = $qry->Fetch()) ) {
        $this->collection = $row;
        $this->collection->exists = true;
        $this->_is_binding = true;
        $this->collection->parent_set = $row->parent_container;
        $this->collection->parent_container = $row->bind_parent_container;
        $this->bound_from = str_replace( $row->bound_to, $row->dav_name, $this->dav_name);
        $this->collection->bound_from = $row->dav_name;
        $this->collection->dav_name = $row->bound_to;
        if ( isset($row->access_ticket_id) ) {
          if ( !isset($this->tickets) ) $this->tickets = array();
          $this->tickets[] = new DAVTicket($row->access_ticket_id);
        }
        if ( $row->is_calendar == 't' )
          $this->collection->type = 'calendar';
        else if ( $row->is_addressbook == 't' )
          $this->collection->type = 'addressbook';
        else if ( preg_match( '#^((/[^/]+/)\.(in|out)/)[^/]*$#', $this->dav_name, $matches ) )
          $this->collection->type = 'schedule-'. $matches[3]. 'box';
        else
          $this->collection->type = 'collection';
      }
      else {
        dbg_error_log( 'DAVResource', 'No collection for path "%s".', $this->dav_name );
        $this->collection->exists = false;
        $this->collection->dav_name = preg_replace('{/[^/]*$}', '/', $this->dav_name);
      }
    }

    @dbg_error_log( 'DAVResource', ':FetchCollection: Found collection named "%s" of type "%s".', $this->collection->dav_name, $this->collection->type );

    $this->_is_collection = ( $this->_is_principal || $this->collection->dav_name == $this->dav_name || $this->collection->dav_name == $this->dav_name.'/' );
    if ( $this->_is_collection ) {
      $this->dav_name = $this->collection->dav_name;
      $this->resource_id = $this->collection->collection_id;
      $this->_is_calendar    = ($this->collection->type == 'calendar');
      $this->_is_addressbook = ($this->collection->type == 'addressbook');
      $this->contenttype = 'httpd/unix-directory';
      if ( !isset($this->exists) && isset($this->collection->exists) ) {
        // If this seems peculiar it's because we only set it to false above...
        $this->exists = $this->collection->exists;
      }
      if ( $this->exists ) {
        if ( isset($this->collection->dav_etag) ) $this->unique_tag = '"'.$this->collection->dav_etag.'"';
        if ( isset($this->collection->created) )  $this->created = $this->collection->created;
        if ( isset($this->collection->modified) ) $this->modified = $this->collection->modified;
        if ( isset($this->collection->dav_displayname) ) $this->collection->displayname = $this->collection->dav_displayname;
      }
      else {
        if ( !isset($this->parent) ) $this->FetchParentContainer();
        $this->user_no = $this->parent->GetProperty('user_no');
      }
      if ( isset($this->collection->resourcetypes) )
        $this->resourcetypes = $this->collection->resourcetypes;
      else {
        $this->resourcetypes = '<DAV::collection/>';
        if ( $this->_is_principal )   $this->resourcetypes .= '<DAV::principal/>';
        if ( $this->_is_addressbook ) $this->resourcetypes .= '<urn:ietf:params:xml:ns:carddav:addressbook/>';
        if ( $this->_is_calendar )    $this->resourcetypes .= '<urn:ietf:params:xml:ns:caldav:calendar/>';
      }
    }
  }


  /**
  * Find the principal associated with this resource.
  */
  function FetchPrincipal() {
    if ( isset($this->principal) ) return;
    $this->principal = new CalDAVPrincipal( array( "path" => $this->bound_from() ) );
    if ( $this->_is_principal ) {
      $this->exists = $this->principal->Exists();
      $this->collection->dav_name = $this->dav_name;
      $this->collection->type = 'principal';
      if ( $this->exists ) {
        $this->displayname = $this->principal->GetProperty('displayname');
        $this->unique_tag = '"'.$this->principal->dav_etag.'"';
        $this->created = $this->principal->created;
        $this->modified = $this->principal->modified;
        $this->resourcetypes = '<DAV::collection/><DAV::principal/>';
        $this->resource_id = $this->principal->principal_id;
        $this->collection = $this->principal->AsCollection();
        $this->user_no = $this->principal->user_no;
      }
    }
  }


  /**
  * Retrieve the actual resource.
  */
  function FetchResource() {
    global $c, $session;

    if ( isset($this->exists) ) return;   // True or false, we've got what we can already
    if ( $this->_is_collection ) return;   // We have all we're going to read

    $sql = <<<EOQRY
SELECT calendar_item.*, addressbook_resource.*, caldav_data.*
     FROM caldav_data LEFT JOIN calendar_item USING (collection_id,dav_id)
                       LEFT JOIN addressbook_resource USING (dav_id)
     WHERE caldav_data.dav_name = :dav_name
EOQRY;
    $params = array( ':dav_name' => $this->bound_from() );

    $qry = new AwlQuery( $sql, $params );
    if ( $qry->Exec('DAVResource') && $qry->rows() > 0 ) {
      $this->exists = true;
      $row = $qry->Fetch();
      $this->FromRow($row);
    }
    else {
      $this->exists = false;
    }
  }


  /**
  * Fetch any dead properties for this URL
  */
  function FetchDeadProperties() {
    if ( isset($this->dead_properties) ) return;

    $this->dead_properties = array();
    $qry = new AwlQuery('SELECT property_name, property_value FROM property WHERE dav_name= :dav_name', array(':dav_name' => $this->dav_name) );
    if ( $qry->Exec('DAVResource') ) {
      while ( $property = $qry->Fetch() ) {
        $this->dead_properties[$property->property_name] = $property->property_value;
      }
    }
  }


  /**
  * Fetch the parent to this resource.
  */
  function FetchParentContainer() {
    if ( $this->dav_name == '/' ) return null;
    if ( !isset($this->parent) ) {
      if ( $this->_is_collection ) {
        dbg_error_log( 'DAVResource', 'Retrieving "%s" - parent of "%s" (dav_name: %s)', $this->parent_path(), $this->collection->dav_name, $this->dav_name() );
        $this->parent = new DAVResource( $this->parent_path() );
      }
      else {
        dbg_error_log( 'DAVResource', 'Retrieving "%s" - parent of "%s" (dav_name: %s)', $this->parent_path(), $this->collection->dav_name, $this->dav_name() );
        $this->parent = new DAVResource($this->collection->dav_name);
      }
    }
    return $this->parent;
  }


  /**
  * Build permissions for this URL
  */
  function FetchPrivileges() {
    global $session, $request;

    if ( $this->dav_name == '/' || $this->dav_name == '' ) {
      $this->privileges = (1 | 16 | 32); // read + read-acl + read-current-user-privilege-set
      dbg_error_log( 'DAVResource', 'Read permissions for user accessing /' );
      return;
    }

    if ( $session->AllowedTo('Admin') ) {
      $this->privileges = privilege_to_bits('all');
      dbg_error_log( 'DAVResource', 'Full permissions for an administrator.' );
      return;
    }

    if ( $this->IsPrincipal() ) {
      if ( !isset($this->principal) ) $this->FetchPrincipal();
      $this->privileges = $this->principal->Privileges();
      dbg_error_log( 'DAVResource', 'Privileges of "%s" for user accessing principal "%s"', $this->privileges, $this->principal->username() );
      return;
    }

    if ( ! isset($this->collection) ) $this->FetchCollection();
    $this->privileges = 0;
    if ( !isset($this->collection->path_privs) ) {
      if ( !isset($this->parent) ) $this->FetchParentContainer();

      $this->collection->path_privs = $this->parent->Privileges();
      $this->collection->user_no = $this->parent->GetProperty('user_no');
      $this->collection->principal_id = $this->parent->GetProperty('principal_id');
    }

    $this->privileges = $this->collection->path_privs;
    if ( is_string($this->privileges) ) $this->privileges = bindec( $this->privileges );

    dbg_error_log( 'DAVResource', 'Privileges of "%s" for user "%s" accessing "%s"',
                       decbin($this->privileges), $session->username, $this->dav_name() );

    if ( isset($request->ticket) && $request->ticket->MatchesPath($this->bound_from()) ) {
      $this->privileges |= $request->ticket->privileges();
      dbg_error_log( 'DAVResource', 'Applying permissions for ticket "%s" now: %s', $request->ticket->id(), decbin($this->privileges) );
    }

    if ( isset($this->tickets) ) {
      if ( !isset($this->resource_id) ) $this->FetchResource();
      foreach( $this->tickets AS $k => $ticket ) {
        if ( $ticket->MatchesResource($this->resource_id()) || $ticket->MatchesPath($this->bound_from()) ) {
          $this->privileges |= $ticket->privileges();
          dbg_error_log( 'DAVResource', 'Applying permissions for ticket "%s" now: %s', $ticket->id(), decbin($this->privileges) );
        }
      }
    }
  }


  /**
  * Return the privileges bits for the current session user to this resource
  */
  function Privileges() {
    if ( !isset($this->privileges) ) $this->FetchPrivileges();
    return $this->privileges;
  }


  /**
  * Is the user has the privileges to do what is requested.
  * @param $do_what mixed The request privilege name, or array of privilege names, to be checked.
  * @param $any boolean Whether we accept any of the privileges. The default is true, unless the requested privilege is 'all', when it is false.
  * @return boolean Whether they do have one of those privileges against this resource.
  */
  function HavePrivilegeTo( $do_what, $any = null ) {
    if ( !isset($this->privileges) ) $this->FetchPrivileges();
    if ( !isset($any) ) $any = ($do_what != 'all');
    $test_bits = privilege_to_bits( $do_what );
    dbg_error_log( 'DAVResource', 'Testing %s privileges of "%s" (%s) against allowed "%s" => "%s" (%s)', ($any?'any':'exactly'),
        $do_what, decbin($test_bits), decbin($this->privileges), ($this->privileges & $test_bits), decbin($this->privileges & $test_bits) );
    if ( $any ) {
      return ($this->privileges & $test_bits) > 0;
    }
    else {
      return ($this->privileges & $test_bits) == $test_bits;
    }
  }


  /**
  * Check if we have the needed privilege or send an error response.  If the user does not have the privileges then
  * the call will not return, and an XML error document will be output.
  *
  * @param string $privilege The name of the needed privilege.
  * @param boolean $any Whether we accept any of the privileges. The default is true, unless the requested privilege is 'all', when it is false.
  */
  function NeedPrivilege( $privilege, $any = null ) {
    global $request;

    // Do the test
    if ( $this->HavePrivilegeTo($privilege, $any) ) return;

    // They failed, so output the error
    $request->NeedPrivilege( $privilege, $this->dav_name );
    exit(0);  // Unecessary, but might clarify things
  }


  /**
  * Returns the array of privilege names converted into XMLElements
  */
  function BuildPrivileges( $privilege_names=null, &$xmldoc=null ) {
    if ( $privilege_names == null ) {
      if ( !isset($this->privileges) ) $this->FetchPrivileges();
      $privilege_names = bits_to_privilege($this->privileges, ($this->_is_collection ? $this->collection->type : null ) );
    }
    return privileges_to_XML( $privilege_names, $xmldoc);
  }


  /**
  * Returns the array of supported methods
  */
  function FetchSupportedMethods( ) {
    if ( isset($this->supported_methods) ) return $this->supported_methods;

    $this->supported_methods = array(
      'OPTIONS' => '',
      'PROPFIND' => '',
      'REPORT' => '',
      'DELETE' => '',
      'LOCK' => '',
      'UNLOCK' => '',
      'MOVE' => ''
    );
    if ( $this->IsCollection() ) {
/*      if ( $this->IsPrincipal() ) {
        $this->supported_methods['MKCALENDAR'] = '';
        $this->supported_methods['MKCOL'] = '';
      } */
      switch ( $this->collection->type ) {
        case 'root':
        case 'email':
          // We just override the list completely here.
          $this->supported_methods = array(
            'OPTIONS' => '',
            'PROPFIND' => '',
            'REPORT' => ''
          );
          break;

        case 'schedule-outbox':
          $this->supported_methods = array_merge(
            $this->supported_methods,
            array(
              'POST' => '', 'PROPPATCH' => '', 'MKTICKET' => '', 'DELTICKET' => ''
            )
          );
          break;
        case 'schedule-inbox':
        case 'calendar':
          $this->supported_methods['GET'] = '';
          $this->supported_methods['PUT'] = '';
          $this->supported_methods['HEAD'] = '';
          $this->supported_methods['MKTICKET'] = '';
          $this->supported_methods['DELTICKET'] = '';
          $this->supported_methods['ACL'] = '';
          break;
        case 'collection':
          $this->supported_methods['MKTICKET'] = '';
          $this->supported_methods['DELTICKET'] = '';
          $this->supported_methods['BIND'] = '';
          $this->supported_methods['ACL'] = '';
        case 'principal':
          $this->supported_methods['GET'] = '';
          $this->supported_methods['HEAD'] = '';
          $this->supported_methods['MKCOL'] = '';
          $this->supported_methods['MKCALENDAR'] = '';
          $this->supported_methods['PROPPATCH'] = '';
          $this->supported_methods['BIND'] = '';
          $this->supported_methods['ACL'] = '';
          break;
      }
    }
    else {
      $this->supported_methods = array_merge(
        $this->supported_methods,
        array(
          'GET' => '', 'HEAD' => '', 'PUT' => '', 'MKTICKET' => '', 'DELTICKET' => ''
        )
      );
    }

    return $this->supported_methods;
  }


  /**
  * Returns the array of supported methods converted into XMLElements
  */
  function BuildSupportedMethods( ) {
    if ( !isset($this->supported_methods) ) $this->FetchSupportedMethods();
    $methods = array();
    foreach( $this->supported_methods AS $k => $v ) {
//      dbg_error_log( 'DAVResource', ':BuildSupportedMethods: Adding method "%s" which is "%s".', $k, $v );
      $methods[] = new XMLElement( 'supported-method', null, array('name' => $k) );
    }
    return $methods;
  }


  /**
  * Returns the array of supported reports
  */
  function FetchSupportedReports( ) {
    if ( isset($this->supported_reports) ) return $this->supported_reports;

    $this->supported_reports = array(
      'DAV::principal-property-search' => '',
      'DAV::principal-search-property-set' => '',
      'DAV::expand-property' => '',
      'DAV::sync-collection' => ''
    );

    if ( !isset($this->collection) ) $this->FetchCollection();

    if ( $this->collection->is_calendar ) {
      $this->supported_reports = array_merge(
        $this->supported_reports,
        array(
          'urn:ietf:params:xml:ns:caldav:calendar-query' => '',
          'urn:ietf:params:xml:ns:caldav:calendar-multiget' => '',
          'urn:ietf:params:xml:ns:caldav:free-busy-query' => ''
        )
      );
    }
    if ( $this->collection->is_addressbook ) {
      $this->supported_reports = array_merge(
        $this->supported_reports,
        array(
          'urn:ietf:params:xml:ns:carddav:addressbook-query' => '',
          'urn:ietf:params:xml:ns:carddav:addressbook-multiget' => ''
        )
      );
    }
    return $this->supported_reports;
  }


  /**
  * Returns the array of supported reports converted into XMLElements
  */
  function BuildSupportedReports( &$reply ) {
    if ( !isset($this->supported_reports) ) $this->FetchSupportedReports();
    $reports = array();
    foreach( $this->supported_reports AS $k => $v ) {
      dbg_error_log( 'DAVResource', ':BuildSupportedReports: Adding supported report "%s" which is "%s".', $k, $v );
      $report = new XMLElement('report');
      $reply->NSElement($report, $k );
      $reports[] = new XMLElement('supported-report', $report );
    }
    return $reports;
  }


  /**
  * Fetches an array of the access_ticket records applying to this path
  */
  function FetchTickets( ) {
    global $c;
    if ( isset($this->access_tickets) ) return;
    $this->access_tickets = array();

    $sql =
'SELECT access_ticket.*, COALESCE( resource.dav_name, collection.dav_name) AS target_dav_name,
        (access_ticket.expires < current_timestamp) AS expired,
        dav_principal.dav_name AS principal_dav_name,
        EXTRACT( \'epoch\' FROM (access_ticket.expires - current_timestamp)) AS seconds,
        path_privs(access_ticket.dav_owner_id,collection.dav_name,:scan_depth) AS grantor_collection_privileges
    FROM access_ticket JOIN collection ON (target_collection_id = collection_id)
        JOIN dav_principal ON (dav_owner_id = principal_id)
        LEFT JOIN caldav_data resource ON (resource.dav_id = access_ticket.target_resource_id)
  WHERE target_collection_id = :collection_id ';
    $params = array(':collection_id' => $this->collection->collection_id, ':scan_depth' => $c->permission_scan_depth);
    if ( $this->IsCollection() ) {
      $sql .= 'AND target_resource_id IS NULL';
    }
    else {
      if ( !isset($this->exists) ) $this->FetchResource();
      $sql .= 'AND target_resource_id = :dav_id';
      $params[':dav_id'] = $this->resource->dav_id;
    }
    if ( isset($this->exists) && !$this->exists ) return;

    $qry = new AwlQuery( $sql, $params );
    if ( $qry->Exec('DAVResource',__LINE__,__FILE__) && $qry->rows() ) {
      while( $ticket = $qry->Fetch() ) {
        $this->access_tickets[] = $ticket;
      }
    }
  }


  /**
  * Returns the array of tickets converted into XMLElements
  *
  * If the current user does not have DAV::read-acl privilege on this resource they
  * will only get to see the tickets where they are the owner, or which they supplied
  * along with the request.
  *
  * @param &XMLDocument $reply A reference to the XMLDocument used to construct the reply
  * @return XMLTreeFragment A fragment of an XMLDocument to go in the reply
  */
  function BuildTicketinfo( &$reply ) {
    global $session, $request;

    if ( !isset($this->access_tickets) ) $this->FetchTickets();
    $tickets = array();
    $show_all = $this->HavePrivilegeTo('DAV::read-acl');
    foreach( $this->access_tickets AS $meh => $trow ) {
      if ( !$show_all && ( $trow->dav_owner_id == $session->principal_id || $request->ticket->id() == $trow->ticket_id ) ) continue;
      dbg_error_log( 'DAVResource', ':BuildTicketinfo: Adding access_ticket "%s" which is "%s".', $trow->ticket_id, $trow->privileges );
      $ticket = new XMLElement( $reply->Tag( 'ticketinfo', 'http://www.xythos.com/namespaces/StorageServer', 'TKT' ) );
      $reply->NSElement($ticket, 'http://www.xythos.com/namespaces/StorageServer:id', $trow->ticket_id );
      $reply->NSElement($ticket, 'http://www.xythos.com/namespaces/StorageServer:owner', $reply->href( ConstructURL($trow->principal_dav_name)) );
      $reply->NSElement($ticket, 'http://www.xythos.com/namespaces/StorageServer:timeout', (isset($trow->seconds) ? sprintf( 'Seconds-%d', $trow->seconds) : 'infinity') );
      $reply->NSElement($ticket, 'http://www.xythos.com/namespaces/StorageServer:visits', 'infinity' );
      $privs = array();
      foreach( bits_to_privilege(bindec($trow->privileges) & bindec($trow->grantor_collection_privileges) ) AS $k => $v ) {
        $privs[] = $reply->NewXMLElement($v);
      }
      $reply->NSElement($ticket, 'DAV::privilege', $privs );
      $tickets[] = $ticket;
    }
    return $tickets;
  }


  /**
  * Checks whether the resource is locked, returning any lock token, or false
  *
  * @todo This logic does not catch all locking scenarios.  For example an infinite
  * depth request should check the permissions for all collections and resources within
  * that.  At present we only maintain permissions on a per-collection basis though.
  */
  function IsLocked( $depth = 0 ) {
    if ( !isset($this->_locks_found) ) {
      $this->_locks_found = array();
      /**
      * Find the locks that might apply and load them into an array
      */
      $sql = 'SELECT * FROM locks WHERE :this_path::text ~ (\'^\'||dav_name||:match_end)::text';
      $qry = new AwlQuery($sql, array( ':this_path' => $this->dav_name, ':match_end' => ($depth == DEPTH_INFINITY ? '' : '$') ) );
      if ( $qry->Exec('DAVResource',__LINE__,__FILE__) ) {
        while( $lock_row = $qry->Fetch() ) {
          $this->_locks_found[$lock_row->opaquelocktoken] = $lock_row;
        }
      }
      else {
        $this->DoResponse(500,i18n("Database Error"));
        // Does not return.
      }
    }

    foreach( $this->_locks_found AS $lock_token => $lock_row ) {
      if ( $lock_row->depth == DEPTH_INFINITY || $lock_row->dav_name == $this->dav_name ) {
        return $lock_token;
      }
    }

    return false;  // Nothing matched
  }


  /**
  * Checks whether this resource is a collection
  */
  function IsCollection() {
    return $this->_is_collection;
  }


  /**
  * Checks whether this resource is a principal
  */
  function IsPrincipal() {
    return $this->_is_collection && $this->_is_principal;
  }


  /**
  * Checks whether this resource is a calendar
  */
  function IsCalendar() {
    return $this->_is_collection && $this->_is_calendar;
  }


  /**
  * Checks whether this resource is a calendar
  * @param string $type The type of scheduling collection, 'read', 'write' or 'any'
  */
  function IsSchedulingCollection( $type = 'any' ) {
    if ( $this->_is_collection && preg_match( '{schedule-(inbox|outbox)}', $this->collection->type, $matches ) ) {
      return ($type == 'any' || $type == $matches[1]);
    }
    return false;
  }


  /**
  * Checks whether this resource is an addressbook
  */
  function IsAddressbook() {
    return $this->_is_collection && $this->_is_addressbook;
  }


  /**
  * Checks whether this resource is a bind to another resource
  */
  function IsBinding() {
    return $this->_is_binding;
  }


  /**
  * Checks whether this resource actually exists, in the virtual sense, within the hierarchy
  */
  function Exists() {
    if ( ! isset($this->exists) ) {
      if ( $this->IsPrincipal() ) {
        if ( !isset($this->principal) ) $this->FetchPrincipal();
        $this->exists = $this->principal->Exists();
      }
      else if ( ! $this->IsCollection() ) {
        if ( !isset($this->resource) ) $this->FetchResource();
      }
    }
//    dbg_error_log('DAVResource',' Checking whether "%s" exists.  It would appear %s.', $this->dav_name, ($this->exists ? 'so' : 'not') );
    return $this->exists;
  }


  /**
  * Checks whether the container for this resource actually exists, in the virtual sense, within the hierarchy
  */
  function ContainerExists() {
    if ( $this->collection->dav_name != $this->dav_name ) {
      return $this->collection->exists;
    }
    $parent = $this->FetchParentContainer();
    return $parent->Exists();
  }


  /**
  * Returns the URL of our resource
  */
  function url() {
    if ( !isset($this->dav_name) ) {
      throw Exception("What! How can dav_name not be set?");
    }
    return ConstructURL($this->dav_name);
  }


  /**
  * Returns the dav_name of the resource in our internal namespace
  */
  function dav_name() {
    if ( isset($this->dav_name) ) return $this->dav_name;
    return null;
  }


  /**
  * Returns the dav_name of the resource we are bound to, within our internal namespace
  */
  function bound_from() {
    if ( isset($this->bound_from) ) return $this->bound_from;
    return $this->dav_name();
  }


  /**
  * Sets the dav_name of the resource we are bound as
  */
  function set_bind_location( $new_dav_name ) {
    if ( !isset($this->bound_from) && isset($this->dav_name) ) {
      $this->bound_from = $this->dav_name;
    }
    $this->dav_name = $new_dav_name;
    return $this->dav_name;
  }


  /**
  * Returns the dav_name of the resource in our internal namespace
  */
  function parent_path() {
    if ( $this->IsCollection() ) {
      if ( !isset($this->collection) ) $this->FetchCollection();
      if ( !isset($this->collection->parent_container) ) {
        $this->collection->parent_container = preg_replace( '{[^/]+/$}', '', $this->bound_from());
      }
      return $this->collection->parent_container;
    }
    return preg_replace( '{[^/]+$}', '', $this->bound_from());
  }



  /**
  * Returns the principal-URL for this resource
  */
  function principal_url() {
    if ( !isset($this->principal) ) $this->FetchPrincipal();
    return $this->principal->url();
  }


  /**
  * Returns the database row for this resource
  */
  function resource() {
    if ( !isset($this->resource) ) $this->FetchResource();
    return $this->resource;
  }


  /**
  * Returns the unique_tag (ETag or getctag) for this resource
  */
  function unique_tag() {
    if ( isset($this->unique_tag) ) return $this->unique_tag;
    if ( $this->IsPrincipal() && !isset($this->principal) ) $this->FetchPrincipal();
    else if ( !$this->_is_collection && !isset($this->resource) ) $this->FetchResource();

    if ( $this->exists !== true || !isset($this->unique_tag) ) $this->unique_tag = '';

    return $this->unique_tag;
  }


  /**
  * Returns the definitive resource_id for this resource - usually a dav_id
  */
  function resource_id() {
    if ( isset($this->resource_id) ) return $this->resource_id;
    if ( $this->IsPrincipal() && !isset($this->principal) ) $this->FetchPrincipal();
    else if ( !$this->_is_collection && !isset($this->resource) ) $this->FetchResource();

    if ( $this->exists !== true || !isset($this->resource_id) ) $this->resource_id = null;

    return $this->resource_id;
  }


  /**
  * Checks whether the target collection is publicly_readable
  */
  function IsPublic() {
    return ( isset($this->collection->publicly_readable) && $this->collection->publicly_readable == 't' );
  }


  /**
  * Return the type of whatever contains this resource, or would if it existed.
  */
  function ContainerType() {
    if ( $this->IsPrincipal() ) return 'root';
    if ( !$this->IsCollection() ) return $this->collection->type;

    if ( ! isset($this->collection->parent_container) ) return null;

    if ( isset($this->parent_container_type) ) return $this->parent_container_type;

    if ( preg_match('#/[^/]+/#', $this->collection->parent_container) ) {
      $this->parent_container_type = 'principal';
    }
    else {
      $qry = new AwlQuery('SELECT * FROM collection WHERE dav_name = :parent_name',
                                array( ':parent_name' => $this->collection->parent_container ) );
      if ( $qry->Exec('DAVResource') && $qry->rows() > 0 && $parent = $qry->Fetch() ) {
        if ( $parent->is_calendar == 't' )
          $this->parent_container_type = 'calendar';
        else if ( $parent->is_addressbook == 't' )
          $this->parent_container_type = 'addressbook';
        else if ( preg_match( '#^((/[^/]+/)\.(in|out)/)[^/]*$#', $this->dav_name, $matches ) )
          $this->parent_container_type = 'schedule-'. $matches[3]. 'box';
        else
          $this->parent_container_type = 'collection';
      }
      else
        $this->parent_container_type = null;
    }
    return $this->parent_container_type;
  }


  /**
  * BuildACE - construct an XMLElement subtree for a DAV::ace
  */
  function BuildACE( &$xmldoc, $privs, $principal ) {
    $privilege_names = bits_to_privilege($privs, ($this->_is_collection ? $this->collection->type : 'resource'));
    $privileges = array();
    foreach( $privilege_names AS $k ) {
      $privilege = new XMLElement('privilege');
      if ( isset($xmldoc) )
        $xmldoc->NSElement($privilege,$k);
      else
        $privilege->NewElement($k);
      $privileges[] = $privilege;
    }
    $ace = new XMLElement('ace', array(
                new XMLElement('principal', $principal),
                new XMLElement('grant', $privileges ) )
              );
    return $ace;
  }

  /**
  * Return ACL settings
  */
  function GetACL( &$xmldoc ) {
    global $c, $session;

    if ( !isset($this->principal) ) $this->FetchPrincipal();
    $default_privs = $this->principal->default_privileges;
    if ( isset($this->collection->default_privileges) ) $default_privs = $this->collection->default_privileges;

    $acl = array();
    $acl[] = $this->BuildACE($xmldoc, pow(2,25) - 1, new XMLElement('property', new XMLElement('owner')) );

    $qry = new AwlQuery('SELECT dav_principal.dav_name, grants.* FROM grants JOIN dav_principal ON (to_principal=principal_id) WHERE by_collection = :collection_id OR by_principal = :principal_id ORDER BY by_collection',
                                array( ':collection_id' => $this->collection->collection_id, ':principal_id' => $this->principal->principal_id ) );
    if ( $qry->Exec('DAVResource') && $qry->rows() > 0 ) {
      $by_collection = null;
      while( $grant = $qry->Fetch() ) {
        if ( !isset($by_collection) ) $by_collection = isset($grant->by_collection);
        if ( $by_collection &&  !isset($grant->by_collection) ) break;
        $acl[] = $this->BuildACE($xmldoc, $grant->privileges, $xmldoc->href(ConstructURL($grant->dav_name)) );
      }
    }

    $acl[] = $this->BuildACE($xmldoc, $default_privs, new XMLElement('authenticated') );

    return $acl;

  }


  /**
  * Return general server-related properties, in plain form
  */
  function GetProperty( $name ) {
    global $c, $session;

//    dbg_error_log( 'DAVResource', ':GetProperty: Fetching "%s".', $name );
    $value = null;

    switch( $name ) {
      case 'collection_id':
        return $this->collection->collection_id;
        break;

      case 'resourcetype':
        if ( isset($this->resourcetypes) ) {
          $this->resourcetypes = preg_replace('{^\s*<(.*)/>\s*$}', '$1', $this->resourcetypes);
          $type_list = preg_split('{(/>\s*<|\n)}', $this->resourcetypes);
          foreach( $type_list AS $k => $resourcetype ) {
            if ( preg_match( '{^([^:]+):([^:]+) \s+ xmlns:([^=]+)="([^"]+)" \s* $}x', $resourcetype, $matches ) ) {
              $type_list[$k] = $matches[4] .':' .$matches[2];
            }
            else if ( preg_match( '{^([^:]+) \s+ xmlns="([^"]+)" \s* $}x', $resourcetype, $matches ) ) {
              $type_list[$k] = $matches[2] .':' .$matches[1];
            }
          }
          return $type_list;
        }

      case 'resource':
        if ( !isset($this->resource) ) $this->FetchResource();
        return clone($this->resource);
        break;

      case 'principal':
        if ( !isset($this->principal) ) $this->FetchPrincipal();
        return clone($this->principal);
        break;

      default:
        if ( isset($this->{$name}) ) {
          if ( ! is_object($this->{$name}) ) return $this->{$name};
          return clone($this->{$name});
        } 
        if ( $this->_is_principal ) {
          if ( !isset($this->principal) ) $this->FetchPrincipal();
          if ( isset($this->principal->{$name}) ) return $this->principal->{$name};
          if ( isset($this->collection->{$name}) ) return $this->collection->{$name};
        }
        else if ( $this->_is_collection ) {
          if ( isset($this->collection->{$name}) ) return $this->collection->{$name};
          if ( isset($this->principal->{$name}) ) return $this->principal->{$name};
        }
        else {
          if ( !isset($this->resource) ) $this->FetchResource();
          if ( isset($this->resource->{$name}) ) return $this->resource->{$name};
          if ( !isset($this->principal) ) $this->FetchPrincipal();
          if ( isset($this->principal->{$name}) ) return $this->principal->{$name};
          if ( isset($this->collection->{$name}) ) return $this->collection->{$name};
        }
        if ( isset($this->{$name}) ) {
          if ( ! is_object($this->{$name}) ) return $this->{$name};
          return clone($this->{$name});
        } 
        // dbg_error_log( 'DAVResource', ':GetProperty: Failed to find property "%s" on "%s".', $name, $this->dav_name );
    }

    return $value;
  }


  /**
  * Return an array which is an expansion of the DAV::allprop
  */
  function DAV_AllProperties() {
    if ( isset($this->dead_properties) ) $this->FetchDeadProperties();
    $allprop = array_merge( (isset($this->dead_properties)?$this->dead_properties:array()),
      (isset($include_properties)?$include_properties:array()),
      array(
        'DAV::getcontenttype', 'DAV::resourcetype', 'DAV::getcontentlength', 'DAV::displayname', 'DAV::getlastmodified',
        'DAV::creationdate', 'DAV::getetag', 'DAV::getcontentlanguage', 'DAV::supportedlock', 'DAV::lockdiscovery',
        'DAV::owner', 'DAV::principal-URL', 'DAV::current-user-principal',
        'urn:ietf:params:xml:ns:carddav:max-resource-size', 'urn:ietf:params:xml:ns:carddav:supported-address-data',
        'urn:ietf:params:xml:ns:carddav:addressbook-description', 'urn:ietf:params:xml:ns:carddav:addressbook-home-set'
      ) );

    return $allprop;
  }


  /**
  * Return general server-related properties for this URL
  */
  function ResourceProperty( $tag, $prop, &$reply, &$denied ) {
    global $c, $session, $request;

//    dbg_error_log( 'DAVResource', 'Processing "%s" on "%s".', $tag, $this->dav_name );

    if ( $reply === null ) $reply = $GLOBALS['reply'];

    switch( $tag ) {
      case 'DAV::allprop':
        $property_list = $this->DAV_AllProperties();
        $discarded = array();
        foreach( $property_list AS $k => $v ) {
          $this->ResourceProperty($v, $prop, $reply, $discarded);
        }
        break;

      case 'DAV::href':
        $prop->NewElement('href', ConstructURL($this->dav_name) );
        break;

      case 'DAV::resource-id':
        if ( $this->resource_id > 0 )
          $reply->DAVElement( $prop, 'resource-id', $reply->href(ConstructURL('/.resources/'.$this->resource_id) ) );
        else
          return false;
        break;

      case 'DAV::parent-set':
        $sql = <<<EOQRY
SELECT b.parent_container FROM dav_binding b JOIN collection c ON (b.bound_source_id=c.collection_id)
 WHERE regexp_replace( b.dav_name, '^.*/', c.dav_name ) = :bound_from
EOQRY;
        $qry = new AwlQuery($sql, array( ':bound_from' => $this->bound_from() ) );
        $parents = array();
        if ( $qry->Exec('DAVResource',__LINE__,__FILE__) && $qry->rows() > 0 ) {
          while( $row = $qry->Fetch() ) {
            $parents[$row->parent_container] = true;
          }
        }
        $parents[preg_replace( '{(?<=/)[^/]+/?$}','',$this->bound_from())] = true;
        $parents[preg_replace( '{(?<=/)[^/]+/?$}','',$this->dav_name())] = true;

        $parent_set = $reply->DAVElement( $prop, 'parent-set' );
        foreach( $parents AS $parent => $v ) {
          if ( preg_match( '{^(.*)?/([^/]+)/?$}', $parent, $matches ) ) {
            $reply->DAVElement($parent_set, 'parent', array(
                                new XMLElement( 'href', ConstructURL($matches[1])),
                                new XMLElement( 'segment', $matches[2])
                              ));
          }
          else if ( $parent == '/' ) {
            $reply->DAVElement($parent_set, 'parent', array(
                                new XMLElement( 'href', '/'),
                                new XMLElement( 'segment', ( ConstructURL('/') == '/caldav.php/' ? 'caldav.php' : ''))
                              ));
          }
        }
        break;

      case 'DAV::getcontenttype':
        if ( !isset($this->contenttype) && !$this->_is_collection && !isset($this->resource) ) $this->FetchResource();
        $prop->NewElement('getcontenttype', $this->contenttype );
        break;

      case 'DAV::resourcetype':
        $resourcetypes = $prop->NewElement('resourcetype' );
        $type_list = $this->GetProperty('resourcetype');
        if ( !is_array($type_list) ) return true;
//        dbg_error_log( 'DAVResource', ':ResourceProperty: "%s" are "%s".', $tag, implode(', ',$type_list) );
        foreach( $type_list AS $k => $v ) {
          if ( $v == '' ) continue;
          $reply->NSElement( $resourcetypes, $v );
        }
        if ( $this->_is_binding ) {
          $reply->NSElement( $resourcetypes, 'http://xmlns.davical.org/davical:webdav-binding' );
        }
        break;

      case 'DAV::getlastmodified':
        /** peculiarly, it seems that getlastmodified is HTTP Date format! */
        $reply->NSElement($prop, $tag, ISODateToHTTPDate($this->GetProperty('modified')) );
        break;

      case 'DAV::creationdate':
        /** bizarrely, it seems that creationdate is ISO8601 format */
        $reply->NSElement($prop, $tag, DateToISODate($this->GetProperty('created')) );
        break;

      case 'DAV::getcontentlength':
        if ( $this->_is_collection ) return false;
        if ( !isset($this->resource) ) $this->FetchResource();
        if ( isset($this->resource) ) {
          $reply->NSElement($prop, $tag, strlen($this->resource->caldav_data) );
        }
        break;

      case 'DAV::getcontentlanguage':
        $locale = (isset($c->current_locale) ? $c->current_locale : '');
        if ( isset($this->locale) && $this->locale != '' ) $locale = $this->locale;
        $reply->NSElement($prop, $tag, $locale );
        break;

      case 'DAV::acl-restrictions':
        $reply->NSElement($prop, $tag, array( new XMLElement('grant-only'), new XMLElement('no-invert') ) );
        break;

      case 'DAV::inherited-acl-set':
        $inherited_acls = array();
        if ( ! $this->_is_collection ) {
          $inherited_acls[] = $reply->href(ConstructURL($this->collection->dav_name));
        }
        $reply->NSElement($prop, $tag, $inherited_acls );
        break;

      case 'DAV::owner':
        // After a careful reading of RFC3744 we see that this must be the principal-URL of the owner
        $owner_url = ( isset($this->_is_binding) && $this->_is_binding ? $this->collection->bind_owner_url : $this->principal_url() );
        $reply->DAVElement( $prop, 'owner', $reply->href( $owner_url ) );
        break;

      // Empty tag responses.
      case 'DAV::group':
      case 'DAV::alternate-URI-set':
        $reply->NSElement($prop, $tag );
        break;

      case 'DAV::getetag':
        if ( $this->_is_collection ) return false;
        $reply->NSElement($prop, $tag, $this->unique_tag() );
        break;

      case 'http://calendarserver.org/ns/:getctag':
        if ( ! $this->_is_collection ) return false;
        $reply->NSElement($prop, $tag, $this->unique_tag() );
        break;

      case 'http://calendarserver.org/ns/:calendar-proxy-read-for':
        $proxy_type = 'read';
      case 'http://calendarserver.org/ns/:calendar-proxy-write-for':
        if ( !isset($proxy_type) ) $proxy_type = 'write';
        $reply->CalendarserverElement($prop, 'calendar-proxy-'.$proxy_type.'-for', $reply->href( $this->principal->ProxyFor($proxy_type) ) );
        break;

      case 'DAV::current-user-privilege-set':
        if ( $this->HavePrivilegeTo('DAV::read-current-user-privilege-set') ) {
          $reply->NSElement($prop, $tag, $this->BuildPrivileges() );
        }
        else {
          $denied[] = $tag;
        }
        break;

      case 'urn:ietf:params:xml:ns:caldav:supported-calendar-data':
        if ( ! $this->IsCalendar() && ! $this->IsSchedulingCollection() ) return false;
        $reply->NSElement($prop, $tag, 'text/calendar' );
        break;

      case 'urn:ietf:params:xml:ns:caldav:supported-calendar-component-set':
        if ( ! $this->_is_collection ) return false;
        if ( $this->IsCalendar() )
          $set_of_components = array( 'VEVENT', 'VTODO', 'VJOURNAL', 'VTIMEZONE', 'VFREEBUSY' );
        else if ( $this->IsSchedulingCollection() )
          $set_of_components = array( 'VEVENT', 'VTODO', 'VFREEBUSY' );
        else return false;
        $components = array();
        foreach( $set_of_components AS $v ) {
          $components[] = $reply->NewXMLElement( 'comp', '', array('name' => $v), 'urn:ietf:params:xml:ns:caldav');
        }
        $reply->CalDAVElement($prop, 'supported-calendar-component-set', $components );
        break;

      case 'DAV::supported-method-set':
        $prop->NewElement('supported-method-set', $this->BuildSupportedMethods() );
        break;

      case 'DAV::supported-report-set':
        $prop->NewElement('supported-report-set', $this->BuildSupportedReports( $reply ) );
        break;

      case 'DAV::supportedlock':
        $prop->NewElement('supportedlock',
          new XMLElement( 'lockentry',
            array(
              new XMLElement('lockscope', new XMLElement('exclusive')),
              new XMLElement('locktype',  new XMLElement('write')),
            )
          )
        );
        break;

      case 'DAV::supported-privilege-set':
        $prop->NewElement('supported-privilege-set', $request->BuildSupportedPrivileges($reply) );
        break;

      case 'DAV::principal-collection-set':
        $prop->NewElement( 'principal-collection-set', $reply->href( ConstructURL('/') ) );
        break;

      case 'DAV::current-user-principal':
        $prop->NewElement('current-user-principal', $reply->href( $request->principal->principal_url ) );
        break;

      case 'SOME-DENIED-PROPERTY':  /** @todo indicating the style for future expansion */
        $denied[] = $reply->Tag($tag);
        break;

      case 'urn:ietf:params:xml:ns:caldav:calendar-timezone':
        if ( ! $this->_is_collection ) return false;
        if ( !isset($this->collection->tz_spec) || $this->collection->tz_spec == '' ) return false;

        $cal = new iCalComponent();
        $cal->VCalendar();
        $cal->AddComponent( new iCalComponent($this->collection->tz_spec) );
        $reply->NSElement($prop, $tag, $cal->Render() );
        break;

      case 'urn:ietf:params:xml:ns:carddav:address-data':
      case 'urn:ietf:params:xml:ns:caldav:calendar-data':
        if ( $this->_is_collection ) return false;
        if ( !isset($this->resource) ) $this->FetchResource();
        $reply->NSElement($prop, $tag, $this->resource->caldav_data );
        break;

      case 'urn:ietf:params:xml:ns:carddav:max-resource-size':
        if ( ! $this->_is_collection || !$this->_is_addressbook ) return false;
        $reply->NSElement($prop, $tag, 65500 );
        break;

      case 'urn:ietf:params:xml:ns:carddav:supported-address-data':
        if ( ! $this->_is_collection || !$this->_is_addressbook ) return false;
        $address_data = $reply->NewXMLElement( 'address-data', false,
                      array( 'content-type' => 'text/vcard', 'version' => '3.0'), 'urn:ietf:params:xml:ns:carddav');
        $reply->NSElement($prop, $tag, $address_data );
        break;

      case 'DAV::acl':
        if ( $this->HavePrivilegeTo('DAV::read-acl') ) {
          $reply->NSElement($prop, $tag, $this->GetACL( $reply ) );
        }
        else {
          $denied[] = $tag;
        }
        break;

      case 'http://www.xythos.com/namespaces/StorageServer:ticketdiscovery':
      case 'DAV::ticketdiscovery':
        $reply->NSElement($prop,'http://www.xythos.com/namespaces/StorageServer:ticketdiscovery', $this->BuildTicketinfo($reply) );
        break;

      default:
        $property_value = $this->GetProperty(preg_replace('{^.*:}', '', $tag));
        if ( isset($property_value) ) {
          $reply->NSElement($prop, $tag, $property_value );
        }
        else {
          if ( !isset($this->dead_properties) ) $this->FetchDeadProperties();
          if ( isset($this->dead_properties[$tag]) ) {
            $reply->NSElement($prop, $tag, $this->dead_properties[$tag] );
          }
          else {
//            dbg_error_log( 'DAVResource', 'Request for unsupported property "%s" of path "%s".', $tag, $this->dav_name );
            return false;
          }
        }
    }

    return true;
  }


  /**
  * Construct XML propstat fragment for this resource
  *
  * @param array of string $properties The requested properties for this resource
  *
  * @return string An XML fragment with the requested properties for this resource
  */
  function GetPropStat( $properties, &$reply, $props_only = false ) {
    global $request;

    dbg_error_log('DAVResource',':GetPropStat: propstat for href "%s"', $this->dav_name );

    $prop = new XMLElement('prop');
    $denied = array();
    $not_found = array();
    foreach( $properties AS $k => $tag ) {
      if ( is_object($tag) ) {
        dbg_error_log( 'DAVResource', ':GetPropStat: "$properties" should be an array of text. Assuming this object is an XMLElement!.' );
        $tag = $tag->GetTag();
      }
      $found = $this->ResourceProperty($tag, $prop, $reply, $denied );
      if ( !$found ) {
        if ( !isset($this->principal) ) $this->FetchPrincipal();
        $found = $this->principal->PrincipalProperty( $tag, $prop, $reply, $denied );
      }
      if ( ! $found ) {
//        dbg_error_log( 'DAVResource', 'Request for unsupported property "%s" of resource "%s".', $tag, $this->dav_name );
        $not_found[] = $reply->Tag($tag);
      }
    }
    if ( $props_only ) return $prop;

    $status = new XMLElement('status', 'HTTP/1.1 200 OK' );

    $elements = array( new XMLElement( 'propstat', array($prop,$status) ) );

    if ( count($denied) > 0 ) {
      $status = new XMLElement('status', 'HTTP/1.1 403 Forbidden' );
      $noprop = new XMLElement('prop');
      foreach( $denied AS $k => $v ) {
        $reply->NSElement($noprop, $v);
      }
      $elements[] = new XMLElement( 'propstat', array( $noprop, $status) );
    }

    if ( count($not_found) > 0 ) {
      $status = new XMLElement('status', 'HTTP/1.1 404 Not Found' );
      $noprop = new XMLElement('prop');
      foreach( $not_found AS $k => $v ) {
        $noprop->NewElement($v);
      }
      $elements[] = new XMLElement( 'propstat', array( $noprop, $status) );
    }
    return $elements;
  }


  /**
  * Render XML for this resource
  *
  * @param array $properties The requested properties for this principal
  * @param reference $reply A reference to the XMLDocument being used for the reply
  *
  * @return string An XML fragment with the requested properties for this principal
  */
  function RenderAsXML( $properties, &$reply, $bound_parent_path = null ) {
    global $session, $c, $request;

    dbg_error_log('DAVResource',':RenderAsXML: Resource "%s" exists(%d)', $this->dav_name, $this->Exists() );

    if ( !$this->Exists() ) return null;

    $elements = $this->GetPropStat( $properties, $reply );
    if ( isset($bound_parent_path) ) {
      $dav_name = str_replace( $this->parent_path(), $bound_parent_path, $this->dav_name );
    }
    else {
      $dav_name = $this->dav_name;
    }

    array_unshift( $elements, $reply->href(ConstructURL($dav_name)));

    $response = new XMLElement( 'response', $elements );

    return $response;
  }

}
