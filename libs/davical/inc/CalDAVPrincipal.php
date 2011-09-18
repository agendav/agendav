<?php
/**
* An object representing a DAV 'Principal'
*
* @package   davical
* @subpackage   Principal
* @author    Andrew McMillan <andrew@mcmillan.net.nz>
* @copyright Catalyst .Net Ltd, Morphoss Ltd <http://www.morhposs.com/>
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*/

/**
* @var $_CalDAVPrincipalCache
* A global variable holding a cache of any DAV Principals which are
* read from the DB.
*/
$_CalDAVPrincipalCache = (object) array( 'p' => array(), 'u' => array() );


/**
* A class for things to do with a DAV Principal
*
* @package   davical
*/
class CalDAVPrincipal
{
  /**
  * @var The home URL of the principal
  */
  private $url;

  /**
  * @var Identifies whether this principal exists in the DB yet
  */
  private $exists;

  /**
  * @var RFC4791: Identifies the URL(s) of any WebDAV collections that contain
  * calendar collections owned by the associated principal resource.
  */
  private $calendar_home_set;

  /**
  * @var CardDAV: Identifies the URL(s) of any WebDAV collections that contain
  * addressbook collections owned by the associated principal resource.
  */
  private $addressbook_home_set;

  /**
  * @var Obsolete: Identifies the URL(s) of any calendars participating in free/busy
  */
  private $calendar_free_busy_set;

  /**
  * @var draft-desruisseaux-caldav-sched-03: Identify the URL of the scheduling
  * Inbox collection owned by the associated principal resource.
  */
  var $schedule_inbox_url;

  /**
  * @var draft-desruisseaux-caldav-sched-03: Identify the URL of the scheduling
  * Outbox collection owned by the associated principal resource.
  */
  var $schedule_outbox_url;

  /**
  * @var Whether or not we are using an e-mail address based URL.
  */
  var $by_email;

  /**
  * @var RFC3744: The principals that are direct members of this group.
  */
  protected $_is_group;

  /**
  * @var RFC3744: The principals that are direct members of this group.
  */
  protected $group_member_set;

  /**
  * @var RFC3744: The groups in which the principal is directly a member.
  */
  protected $group_membership;

  /**
   * @var caldav-cu-proxy-02: The principals which this one has read permissions on.
   */
  protected $read_proxy_for;

  /**
   * @var caldav-cu-proxy-02: The principals which this one has read-write prmissions for.
   */
  protected $write_proxy_for;

   /**
   * @var caldav-cu-proxy-02: The principals which have read permissions on this one.
   */
  protected $read_proxy_group;

  /**
   * @var caldav-cu-proxy-02: The principals which have write permissions on this one.
   */
  protected $write_proxy_group;

  /**
   * @var CardDAV: The URL to an addressbook entry for this principal
   */
  protected $principal_address;

  /**
   * @var The username for this principal
   */
  protected $username;

  /**
   * @var The dav_name for this principal - a partial path
   */
  protected $dav_name;

  /**
  * Constructor
  * @param mixed $parameters If null, an empty Principal is created.  If it
  *              is an integer then that ID is read (if possible).  If it is
  *              an array then the Principal matching the supplied elements
  *              is read.  If it is an object then it is expected to be a 'usr'
  *              record that was read elsewhere.
  *
  * @return boolean Whether we actually read data from the DB to initialise the record.
  */
  function __construct( $parameters = null ) {
    global $session, $c;

    $this->exists = null;
    $this->url = null;

    if ( $parameters == null ) return false;
    $this->by_email = false;
    if ( is_object($parameters) ) {
      dbg_error_log( 'principal', 'Principal: record for %s', $parameters->username );
      $usr = $parameters;
    }
    else if ( is_int($parameters) ) {
      dbg_error_log( 'principal', 'Principal: %d', $parameters );
      $usr = getUserByID($parameters);
      $this->user_no = $parameters['user_no'];
    }
    else if ( is_array($parameters) ) {
      if ( ! isset($parameters['options']['allow_by_email']) ) $parameters['options']['allow_by_email'] = false;
      if ( isset($parameters['username']) ) {
        $usr = getUserByName($parameters['username']);
        $this->username = $parameters['username'];
      }
      else if ( isset($parameters['user_no']) ) {
        $usr = getUserByID($parameters['user_no']);
        $this->user_no = $parameters['user_no'];
      }
      else if ( isset($parameters['email']) && $parameters['options']['allow_by_email'] ) {
        if ( $username = $this->UsernameFromEMail($parameters['email']) ) {
          $usr = getUserByName($username);
          $this->username = $username;
        }
      }
      else if ( isset($parameters['path']) ) {
        dbg_error_log( 'principal', 'Finding Principal from path: "%s", options.allow_by_email: "%s"', $parameters['path'], $parameters['options']['allow_by_email'] );
        if ( $username = $this->UsernameFromPath($parameters['path'], $parameters['options']) ) {
          $usr = getUserByName($username);
          $this->username = $username;
        }
      }
      else if ( isset($parameters['principal-property-search']) ) {
        $usr = $this->PropertySearch($parameters['principal-property-search']);
      }
    }
    if ( !isset($usr) || !is_object($usr) ) {
      $this->exists = false;
      return false;
    }

    $this->exists = true;
    $this->InitialiseRecord($usr);

    if ( is_array($parameters) && !isset($parameters['username']) && !isset($parameters['user_no'])
                 && isset($parameters['path']) && preg_match('{^/(~|principals/)}', $parameters['path']) ) {
      // Force it to match
      $this->url = $parameters['path'];
      $this->dav_name = $parameters['path'];
    }
  }


  /**
  * Initialise the Principal object from a $usr record from the DB.
  * @param object $usr The usr record from the DB.
  */
  function InitialiseRecord($usr) {
    global $c;
    foreach( $usr AS $k => $v ) {
      $this->{$k} = $v;
    }
    if ( !isset($this->modified) ) $this->modified = $this->updated;
    if ( !isset($this->created) )  $this->created  = $this->joined;

    $this->dav_etag = md5($this->username . $this->updated);

    $this->_is_group = (isset($usr->type_id) && $usr->type_id == 3);

    $this->principal_url = ConstructURL( '/'.$this->username.'/', true );
    $this->url = $this->principal_url;

    $this->principal_address = $this->principal_url . 'principal.vcf';

    $this->user_address_set = array(
       'mailto:'.$this->email,
       ConstructURL( '/'.$this->username.'/', true ),
//       ConstructURL( '/~'.$this->username.'/', true ),
//       ConstructURL( '/__uuids__/'.$this->username.'/', true ),
    );
    $this->schedule_inbox_url = sprintf( '%s.in/', $this->url);
    $this->schedule_outbox_url = sprintf( '%s.out/', $this->url);
    $this->dropbox_url = sprintf( '%s.drop/', $this->url);
    $this->notifications_url = sprintf( '%s.notify/', $this->url);

    if ( isset ( $c->notifications_server ) ) {
      $this->xmpp_uri = 'xmpp:pubsub.'.$c->notifications_server['host'].'?pubsub;node=/davical-'.$this->principal_id;
      $this->xmpp_server = $c->notifications_server['host'];
    }

    if ( $this->_is_group ) {
      $this->group_member_set = array();
      $qry = new AwlQuery('SELECT usr.username FROM group_member JOIN principal ON (principal_id=member_id) JOIN usr USING(user_no) WHERE group_id = :group_id ORDER BY principal.principal_id ', array( ':group_id' => $this->principal_id) );
      if ( $qry->Exec('CalDAVPrincipal') && $qry->rows() > 0 ) {
        while( $member = $qry->Fetch() ) {
          $this->group_member_set[] = ConstructURL( '/'. $member->username . '/', true);
        }
      }
    }

    $this->group_membership = array();
    $qry = new AwlQuery('SELECT usr.username FROM group_member JOIN principal ON (principal_id=group_id) JOIN usr USING(user_no) WHERE member_id = :member_id UNION SELECT usr.username FROM group_member LEFT JOIN grants ON (to_principal=group_id) JOIN principal ON (principal_id=by_principal) JOIN usr USING(user_no) WHERE member_id = :member_id and by_principal != member_id ORDER BY 1', array( ':member_id' => $this->principal_id ) );
    if ( $qry->Exec('CalDAVPrincipal') && $qry->rows() > 0 ) {
      while( $group = $qry->Fetch() ) {
        $this->group_membership[] = ConstructURL( '/'. $group->username . '/', true);
      }
    }

    $this->read_proxy_group = null;
    $this->write_proxy_group = null;
    $this->write_proxy_for = null;
    $this->read_proxy_for = null;

    dbg_error_log( 'principal', ' User: %s (%d) URL: %s, Home: %s, By Email: %d', $this->username, $this->user_no, $this->url, $this->principal_url, $this->by_email );
  }


  /**
  * Split this out so we do it as infrequently as possible, given the cost.
  */
  function FetchProxyGroups() {
    global $c;

    $this->read_proxy_group = array();
    $this->write_proxy_group = array();
    $this->write_proxy_for = array();
    $this->read_proxy_for = array();

    if ( !isset($c->disable_caldav_proxy) || $c->disable_caldav_proxy === false ) {

      $write_priv = privilege_to_bits(array('write'));
      // whom are we a proxy for? who is a proxy for us?
      // (as per Caldav Proxy section 5.1 Paragraph 7 and 5)
      $sql = 'SELECT principal_id, username, pprivs(:request_principal::int8,principal_id,:scan_depth::int) FROM principal JOIN usr USING(user_no) WHERE principal_id IN (SELECT * from p_has_proxy_access_to(:request_principal,:scan_depth))';
      $params = array( ':request_principal' => $this->principal_id, ':scan_depth' => $c->permission_scan_depth );
      $qry = new AwlQuery($sql, $params);
      if ( $qry->Exec('CalDAVPrincipal') && $qry->rows() > 0 ) {
        while( $relationship = $qry->Fetch() ) {
          if ( (bindec($relationship->pprivs) & $write_priv) != 0 ) {
            $this->write_proxy_for[] = ConstructURL( '/'. $relationship->username . '/', true);
            $this->group_membership[] = ConstructURL( '/'. $relationship->username . '/calendar-proxy-write/', true);
          }
          else {
            $this->read_proxy_for[] = ConstructURL( '/'. $relationship->username . '/', true);
            $this->group_membership[] = ConstructURL( '/'. $relationship->username . '/calendar-proxy-read/', true);
          }
        }
      }

      $sql = 'SELECT principal_id, username, pprivs(:request_principal::int8,principal_id,:scan_depth::int) FROM principal JOIN usr USING(user_no) WHERE principal_id IN (SELECT * from grants_proxy_access_from_p(:request_principal,:scan_depth))';
      $qry = new AwlQuery($sql, $params ); // reuse $params assigned for earlier query
      if ( $qry->Exec('CalDAVPrincipal') && $qry->rows() > 0 ) {
        while( $relationship = $qry->Fetch() ) {
          if ( bindec($relationship->pprivs) & $write_priv ) {
            $this->write_proxy_group[] = ConstructURL( '/'. $relationship->username . '/', true);
          }
          else {
            $this->read_proxy_group[] = ConstructURL( '/'. $relationship->username . '/', true);
          }
        }
      }
//      @dbg_error_log( 'principal', 'Read-proxy-for:    %s', implode(',',$this->read_proxy_for) );
//      @dbg_error_log( 'principal', 'Write-proxy-for:   %s', implode(',',$this->write_proxy_for) );
//      @dbg_error_log( 'principal', 'Read-proxy-group:  %s', implode(',',$this->read_proxy_group) );
//      @dbg_error_log( 'principal', 'Write-proxy-group: %s', implode(',',$this->write_proxy_group) );
    }
  }


  /**
  * Accessor for the read proxy group
  */
  function ReadProxyGroup() {
    if ( !isset($this->read_proxy_group) ) $this->FetchProxyGroups();
    return $this->read_proxy_group;
  }


  /**
  * Accessor for the write proxy group
  */
  function WriteProxyGroup() {
    if ( !isset($this->write_proxy_group) ) $this->FetchProxyGroups();
    return $this->write_proxy_group;
  }


  /**
  * Accessor for read or write proxy
  * @param string read/write - which sort of proxy list is requested.
  */
  function ProxyFor( $type ) {
    if ( !isset($this->read_proxy_for) ) $this->FetchProxyGroups();
    if ( $type == 'write' ) return $this->write_proxy_for;
    return $this->read_proxy_for;
  }


  /**
  * Accessor for the group membership - the groups this principal is a member of
  */
  function GroupMembership() {
    if ( !isset($this->read_proxy_group) ) $this->FetchProxyGroups();
    return $this->group_membership;
  }


  /**
  * Accessor for the group member set - the members of this group
  */
  function GroupMemberSet() {
    if ( ! $this->_is_group ) return null;
    return $this->group_member_set;
  }


  /**
  * Work out the username, based on elements of the path.
  * @param string $path The path to be used.
  * @param array $options The request options, controlling whether e-mail paths are allowed.
  */
  function UsernameFromPath( $path, $options = null ) {
    global $session, $c;

    if ( $path == '/' || $path == '' ) {
      dbg_error_log( 'principal', 'No useful path split possible' );
      return $session->username;
    }

    $path_split = explode('/', $path );
    @dbg_error_log( 'principal', 'Path split into at least /// %s /// %s /// %s', $path_split[1], $path_split[2], $path_split[3] );

    $username = $path_split[1];
    if ( $path_split[1] == 'principals' && isset($path_split[3]) ) $username = $path_split[3];
    if ( substr($username,0,1) == '~' ) $username = substr($username,1);

    if ( isset($options['allow_by_email']) && $options['allow_by_email'] && preg_match( '#^(\S+@\S+[.]\S+)$#', $username) ) {
      $username = $this->UsernameFromEMail($username);
    }
    return $username;
  }


  /**
  * Work out the username, based on the given e-mail
  * @param string $email The email address to be used.
  */
  function UsernameFromEMail( $email ) {
    @dbg_error_log( 'principal', 'Retrieving username from e-mail address "%s" ', $email );
    $qry = new AwlQuery('SELECT username FROM usr WHERE email = :email', array( ':email' => $email ) );
    if ( $qry->Exec('principal') && $user = $qry->Fetch() ) {
      $username = $user->username;
      $this->by_email = true;
      return $username;
    }
    return null;
  }


  /**
  * Does this principal exist?
  * @return boolean Whether or not it exists.
  */
  function Exists() {
    return $this->exists;
  }


  /**
  * Is this a group principal?
  * @return boolean Whether this is a group principal
  */
  function IsGroup() {
    return $this->_is_group;
  }


  /**
  * Return the username
  * @return string The username
  */
  function username() {
    return (isset($this->username)?$this->username:'username not set');
  }


  /**
  * Return the partial path representing this principal
  * @return string The dav_name
  */
  function dav_name() {
    if ( !isset($this->dav_name) ) {
      if ( !isset($this->username) ) $this->dav_name = '';
      else $this->dav_name = '/'.$this->username.'/';
    }
    return $this->dav_name;
  }


  /**
  * Return an arbitrary property
  * @return string The name of the arbitrary property
  */
  function GetProperty( $property_id ) {

    switch( $property_id ) {
      case 'DAV::resource-id':
        if ( $this->exists && $this->principal_id > 0 )
          ConstructURL('/.resources/'.$this->principal_id);
        else
          return null;
        break;
    }

    if ( isset($this->{$property_id}) ) {
      if ( ! is_object($this->{$property_id}) ) return $this->{$property_id};
      return clone($this->{$property_id});
    } 
    return null;
  }


  /**
  * Return the URL for this principal
  * @return string The principal-URL, or null if they don't exist
  */
  function url() {
    return ($this->exists ? $this->url : null );
  }


  /**
  * Get the calendar_home_set, as lazily as possible
  */
  function calendar_home_set() {
    if ( !isset($this->calendar_home_set) ) {
      $this->calendar_home_set = array();
/*      $qry = new AwlQuery('SELECT DISTINCT parent_container FROM collection WHERE is_calendar AND user_no = :user_no', array( ':user_no' => $this->user_no));
      if ( $qry->Exec('principal',__LINE__,__FILE__) ) {
        if ( $qry->rows() > 0 ) {
          while( $calendar = $qry->Fetch() ) {
            $this->calendar_home_set[] = ConstructURL($calendar->parent_container, true);
          }
        }
        else {*/
          $this->calendar_home_set[] = $this->principal_url;
//         }
//       }
    }
    return $this->calendar_home_set;
  }


  /**
  * Get the addressbook_home_set, as lazily as possible
  */
  function addressbook_home_set() {
    if ( !isset($this->addressbook_home_set) ) {
     $this->addressbook_home_set = array();
/*      $qry = new AwlQuery('SELECT DISTINCT parent_container FROM collection WHERE is_addressbook AND user_no = :user_no', array( ':user_no' => $this->user_no));
      if ( $qry->Exec('principal',__LINE__,__FILE__) ) {
        if ( $qry->rows() > 0 ) {
          while( $addressbook = $qry->Fetch() ) {
            $this->addressbook_home_set[] = ConstructURL($addressbook->parent_container, true);
          }
        }
        else {*/
          $this->addressbook_home_set[] = $this->principal_url;
//         }
//       }
    }
    return $this->addressbook_home_set;
  }


  /**
  * Get the calendar_free_busy_set, as lazily as possible
  */
  function calendar_free_busy_set() {
    if ( !isset($this->calendar_free_busy_set) ) {
      /**
      * calendar-free-busy-set has been dropped from draft 5 of the scheduling extensions for CalDAV
      * in favour of
      */
      $this->calendar_free_busy_set = array();
      $qry = new AwlQuery('SELECT dav_name FROM collection WHERE user_no = :user_no AND is_calendar AND (schedule_transp = \'opaque\' OR schedule_transp IS NULL) ORDER BY user_no, collection_id',
                        array( ':user_no' => $this->user_no) );
      if ( $qry->Exec('principal',__LINE__,__FILE__) ) {
        while( $calendar = $qry->Fetch() ) {
          $this->calendar_free_busy_set[] = ConstructURL($calendar->dav_name, true);
        }
      }
    }
    return $this->calendar_free_busy_set;
  }

  /**
  * Return the privileges bits for the current session user to this resource
  */
  function Privileges() {
    global $session;
    if ( !isset($this->privileges) ) $this->privileges = 0;
    if ( is_string($this->privileges) ) $this->privileges = bindec( $this->privileges );
    if ( $this->_is_group && in_array(ConstructURL('/'.$session->username.'/'), $this->GroupMemberSet()) ) {
      $this->privileges |=  privilege_to_bits( array('DAV::read', 'DAV::read-current-user-privilege-set') ); 
    }
    return $this->privileges;
  }


  /**
  * Returns a representation of the principal as a collection
  */
  function AsCollection() {
    $collection = (object) array(
                            'collection_id' => (isset($this->principal_id) ? $this->principal_id : 0),
                            'is_calendar' => false,
                            'is_addressbook' => false,
                            'is_principal' => true,
                            'type'     => 'principal' . (substr($this->dav_name(), 0, 12) == '/principals/'?'_link':''),
                            'user_no'  => (isset($this->user_no)  ? $this->user_no : 0),
                            'username' => $this->username(),
                            'dav_name' => $this->dav_name,
                            'parent_container' => '/',
                            'email'    => (isset($this->email)    ? $this->email : ''),
                            'created'  => (isset($this->created)  ? $this->created : date('Ymd\THis')),
                            'updated'  => (isset($this->updated)  ? $this->updated : date('Ymd\THis'))
                  );
    $collection->dav_etag = (isset($this->dav_etag) ? $this->dav_etag : md5($collection->username . $collection->updated));
    $collection->dav_displayname =  (isset($this->dav_displayname) ? $this->dav_displayname : (isset($this->fullname) ? $this->fullname : $collection->username));

    return $collection;
  }

  /**
  * Returns properties which are specific to this principal
  */
  function PrincipalProperty( $tag, $prop, &$reply, &$denied ) {

    dbg_error_log('principal',': RenderAsXML: Principal Property "%s"', $tag );
    switch( $tag ) {
      case 'DAV::getcontenttype':
        $prop->NewElement('getcontenttype', 'httpd/unix-directory' );
        break;

      case 'DAV::resourcetype':
        $prop->NewElement('resourcetype', array( new XMLElement('principal'), new XMLElement('collection')) );
        break;

      case 'DAV::displayname':
        $prop->NewElement('displayname', $this->fullname );
        break;

      case 'DAV::principal-URL':
        $prop->NewElement('principal-URL', $reply->href($this->principal_url) );
        break;

      case 'DAV::getlastmodified':
        $prop->NewElement('getlastmodified', ISODateToHTTPDate($this->modified) );
        break;

      case 'DAV::creationdate':
        $prop->NewElement('creationdate', DateToISODate($this->created) );
        break;

      case 'DAV::getcontentlanguage':
        /** Use the principal's locale by preference, otherwise system default */
        $locale = (isset($c->current_locale) ? $c->current_locale : '');
        if ( isset($this->locale) && $this->locale != '' ) $locale = $this->locale;
        $prop->NewElement('getcontentlanguage', $locale );
        break;

      case 'DAV::group-member-set':
        if ( ! $this->_is_group ) return false;
        $prop->NewElement('group-member-set', $reply->href($this->group_member_set) );
        break;

      case 'DAV::group-membership':
        $prop->NewElement('group-membership', $reply->href($this->GroupMembership()) );
        break;

      case 'urn:ietf:params:xml:ns:caldav:schedule-inbox-URL':
        $reply->CalDAVElement($prop, 'schedule-inbox-URL', $reply->href($this->schedule_inbox_url) );
        break;

      case 'urn:ietf:params:xml:ns:caldav:schedule-outbox-URL':
        $reply->CalDAVElement($prop, 'schedule-outbox-URL', $reply->href($this->schedule_outbox_url) );
        break;

      case 'http://calendarserver.org/ns/:dropbox-home-URL':
        $reply->CalendarserverElement($prop, 'dropbox-home-URL', $reply->href($this->dropbox_url) );
        break;

      case 'http://calendarserver.org/ns/:xmpp-server':
        if ( ! isset( $this->xmpp_uri ) ) return false;
        $reply->CalendarserverElement($prop, 'xmpp-server', $this->xmpp_server );
        break;

      case 'http://calendarserver.org/ns/:xmpp-uri':
        if ( ! isset( $this->xmpp_uri ) ) return false;
        $reply->CalendarserverElement($prop, 'xmpp-uri', $this->xmpp_uri );
        break;

      case 'urn:ietf:params:xml:ns:carddav:addressbook-home-set':
        $reply->NSElement($prop, $tag, $reply->href( $this->addressbook_home_set() ) );
        break;

      case 'urn:ietf:params:xml:ns:caldav:calendar-home-set':
        $reply->NSElement($prop, $tag, $reply->href( $this->calendar_home_set() ) );
        break;

      case 'urn:ietf:params:xml:ns:caldav:calendar-free-busy-set':
        $reply->CalDAVElement( $prop, 'calendar-free-busy-set', $reply->href( $this->calendar_free_busy_set() ) );
        break;

      case 'urn:ietf:params:xml:ns:caldav:calendar-user-address-set':
        $reply->CalDAVElement($prop, 'calendar-user-address-set', $reply->href($this->user_address_set) );
        break;

      case 'DAV::owner':
        // After a careful reading of RFC3744 we see that this must be the principal-URL of the owner
        $reply->DAVElement( $prop, 'owner', $reply->href( $this->principal_url ) );
        break;

      // Empty tag responses.
      case 'DAV::alternate-URI-set':
        $prop->NewElement( $reply->Tag($tag));
        break;

      case 'SOME-DENIED-PROPERTY':  /** @todo indicating the style for future expansion */
        $denied[] = $reply->Tag($tag);
        break;

      default:
        return false;
        break;
    }

    return true;
  }


  /**
  * Render XML for a single Principal (user) from the DB
  *
  * @param array $properties The requested properties for this principal
  * @param reference $reply A reference to the XMLDocument being used for the reply
  * @param boolean $props_only Default false.  If true will only return the fragment with the properties, not a full response fragment.
  *
  * @return string An XML fragment with the requested properties for this principal
  */
  function RenderAsXML( $properties, &$reply, $props_only = false ) {
    global $request;

    dbg_error_log('principal',': RenderAsXML: Principal "%s"', $this->username );

    $prop = new XMLElement('prop');
    $denied = array();
    $not_found = array();
    foreach( $properties AS $k => $tag ) {
      if ( ! $this->PrincipalProperty( $tag, $prop, $reply, $denied ) ) {
        dbg_error_log( 'principal', 'Request for unsupported property "%s" of principal "%s".', $tag, $this->username );
        $not_found[] = $reply->Tag($tag);
      }
    }

    if ( $props_only ) return $prop;

    $status = new XMLElement('status', 'HTTP/1.1 200 OK' );

    $propstat = new XMLElement( 'propstat', array( $prop, $status) );
    $href = $reply->href($this->url );

    $elements = array($href,$propstat);

    if ( count($denied) > 0 ) {
      $status = new XMLElement('status', 'HTTP/1.1 403 Forbidden' );
      $noprop = new XMLElement('prop');
      foreach( $denied AS $k => $v ) {
        $noprop->NewElement( $v );
      }
      $elements[] = new XMLElement( 'propstat', array( $noprop, $status) );
    }

    if ( count($not_found) > 0 ) {
      $status = new XMLElement('status', 'HTTP/1.1 404 Not Found' );
      $noprop = new XMLElement('prop');
      foreach( $not_found AS $k => $v ) {
        $noprop->NewElement( $v );
      }
      $elements[] = new XMLElement( 'propstat', array( $noprop, $status) );
    }

    $response = new XMLElement( 'response', $elements );

    return $response;
  }

}
