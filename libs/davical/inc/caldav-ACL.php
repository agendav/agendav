<?php
/**
* CalDAV Server - handle ACL method
*
* @package   davical
* @subpackage   caldav
* @author    Andrew McMillan <andrew@morphoss.com>
* @copyright Morphoss Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2
*/
dbg_error_log("ACL", "method handler");

require_once('DAVResource.php');

$request->NeedPrivilege('DAV::write-acl');

if ( ! ini_get('open_basedir') && (isset($c->dbg['ALL']) || (isset($c->dbg['put']) && $c->dbg['put'])) ) {
  $fh = fopen('/tmp/MOVE.txt','w');
  if ( $fh ) {
    fwrite($fh,$request->raw_post);
    fclose($fh);
  }
}

$resource = new DAVResource( $request->path );

/**
* Preconditions
   (DAV:no-ace-conflict): The ACEs submitted in the ACL request MUST NOT
   conflict with each other.  This is a catchall error code indicating
   that an implementation-specific ACL restriction has been violated.

   (DAV:no-protected-ace-conflict): The ACEs submitted in the ACL
   request MUST NOT conflict with the protected ACEs on the resource.
   For example, if the resource has a protected ACE granting DAV:write
   to a given principal, then it would not be consistent if the ACL
   request submitted an ACE denying DAV:write to the same principal.

   (DAV:no-inherited-ace-conflict): The ACEs submitted in the ACL
   request MUST NOT conflict with the inherited ACEs on the resource.
   For example, if the resource inherits an ACE from its parent
   collection granting DAV:write to a given principal, then it would not
   be consistent if the ACL request submitted an ACE denying DAV:write
   to the same principal.  Note that reporting of this error will be
   implementation-dependent.  Implementations MUST either report this
   error or allow the ACE to be set, and then let normal ACE evaluation
   rules determine whether the new ACE has any impact on the privileges
   available to a specific principal.

   (DAV:limited-number-of-aces): The number of ACEs submitted in the ACL
   request MUST NOT exceed the number of ACEs allowed on that resource.
   However, ACL-compliant servers MUST support at least one ACE granting
   privileges to a single principal, and one ACE granting privileges to
   a group.

   (DAV:deny-before-grant): All non-inherited deny ACEs MUST precede all
   non-inherited grant ACEs.

   (DAV:grant-only): The ACEs submitted in the ACL request MUST NOT
   include a deny ACE.  This precondition applies only when the ACL
   restrictions of the resource include the DAV:grant-only constraint
   (defined in Section 5.6.1).

   (DAV:no-invert):  The ACL request MUST NOT include a DAV:invert
   element.  This precondition applies only when the ACL semantics of
   the resource includes the DAV:no-invert constraint (defined in
   Section 5.6.2).

   (DAV:no-abstract): The ACL request MUST NOT attempt to grant or deny
   an abstract privilege (see Section 5.3).

   (DAV:not-supported-privilege): The ACEs submitted in the ACL request
   MUST be supported by the resource.

   (DAV:missing-required-principal): The result of the ACL request MUST
   have at least one ACE for each principal identified in a
   DAV:required-principal XML element in the ACL semantics of that
   resource (see Section 5.5).

   (DAV:recognized-principal): Every principal URL in the ACL request
   MUST identify a principal resource.

   (DAV:allowed-principal): The principals specified in the ACEs
   submitted in the ACL request MUST be allowed as principals for the
   resource.  For example, a server where only authenticated principals
   can access resources would not allow the DAV:all or
   DAV:unauthenticated principals to be used in an ACE, since these
   would allow unauthenticated access to resources.
*/

$position = 0;
$xmltree = BuildXMLTree( $request->xml_tags, $position);
$aces = $xmltree->GetPath("/DAV::acl/*");

$grantor = new DAVResource($request->path);
if ( ! $grantor->Exists() ) $request->DoResponse( 404 );
$by_principal  = null;
$by_collection = null;
if ( $grantor->IsPrincipal() ) $by_principal = $grantor->GetProperty('principal_id');
else if ( $grantor->IsCollection() ) $by_collection = $grantor->GetProperty('collection_id');
else $request->PreconditionFailed(403,'not-supported-privilege','ACLs may only be applied to Principals or Collections');

$qry = new AwlQuery('BEGIN');
$qry->Exec('ACL',__LINE__,__FILE__);

foreach( $aces AS $k => $ace ) {
  $elements = $ace->GetContent();
  $principal = $elements[0];
  $grant = $elements[1];
  if ( $principal->GetTag() != 'DAV::principal' ) $request->MalformedRequest('ACL request must contain a principal, not '.$principal->GetTag());
  $grant_tag = $grant->GetTag();
  if ( $grant_tag == 'DAV::deny' )   $request->PreconditionFailed(403,'grant-only');
  if ( $grant_tag == 'DAV::invert' ) $request->PreconditionFailed(403,'no-invert');
  if ( $grant->GetTag() != 'DAV::grant' ) $request->MalformedRequest('ACL request must contain a principal for each ACE');

  $privilege_names = array();
  $xml_privs = $grant->GetPath("/DAV::grant/DAV::privilege/*");
  foreach( $xml_privs AS $k => $priv ) {
    $privilege_names[] = $priv->GetTag();
  }
  $privileges = privilege_to_bits($privilege_names);

  $principal_content = $principal->GetContent();
  if ( count($principal_content) != 1 ) $request->MalformedRequest('ACL request must contain exactly one principal per ACE');
  $principal_content = $principal_content[0];
  switch( $principal_content->GetTag() ) {
    case 'DAV::property':
      $principal_property = $principal_content->GetContent();
      if ( $principal_property[0]->GetTag() != 'DAV::owner' ) $request->PreconditionFailed(403, 'recognized-principal' );
      if ( privilege_to_bits('all') != $privileges ) {
        $request->PreconditionFailed(403, 'no-protected-ace-conflict', 'Owner must always have all permissions' );
      }
      continue;  // and then we ignore it, since it's protected
      break;

    case 'DAV::unauthenticated':
      $request->PreconditionFailed(403, 'allowed-principal', 'May not set privileges for unauthenticated users' );
      break;

    case 'DAV::href':
      $principal_type = 'href';
      $principal = new DAVResource( DeconstructURL($principal_content->GetContent()) );
      if ( ! $principal->Exists() || !$principal->IsPrincipal() )
        $request->PreconditionFailed(403,'recognized-principal', 'Principal "' + $principal_content->GetContent() + '" not found.');
      $sqlparms = array( ':to_principal' => $principal->GetProperty('principal_id') );
      $where = 'WHERE to_principal=:to_principal AND ';
      if ( isset($by_principal) ) {
        $sqlparms[':by_principal'] = $by_principal;
        $where .= 'by_principal = :by_principal';
      }
      else {
        $sqlparms[':by_collection'] = $by_collection;
        $where .= 'by_collection = :by_collection';
      }
      $qry = new AwlQuery('SELECT privileges FROM grants '.$where, $sqlparms);
      if ( $qry->Exec('ACL',__LINE__,__FILE__) && $qry->rows() == 1 && $current = $qry->Fetch() ) {
        $sql = 'UPDATE grants SET privileges=:privileges::INT::BIT(24) '.$where;
      }
      else {
        $sqlparms[':by_principal'] = $by_principal;
        $sqlparms[':by_collection'] = $by_collection;
        $sql = 'INSERT INTO grants (by_principal, by_collection, to_principal, privileges) VALUES(:by_principal, :by_collection, :to_principal, :privileges::INT::BIT(24))';
      }
      $sqlparms[':privileges'] = $privileges;
      $qry = new AwlQuery($sql, $sqlparms);
      $qry->Exec('ACL',__LINE__,__FILE__);
      break;

    case 'DAV::authenticated':
      $principal_type = 'authenticated';
      if ( bindec($grantor->GetProperty('default_privileges')) == $privileges ) continue; // There is no change, so skip it
      $sqlparms = array( ':privileges' => $privileges );
      if ( isset($by_collection) ) {
        $sql = 'UPDATE collection SET default_privileges=:privileges::INT::BIT(24) WHERE collection_id=:by_collection';
        $sqlparms[':by_collection'] = $by_collection;
      }
      else {
        $sql = 'UPDATE principal SET default_privileges=:privileges::INT::BIT(24) WHERE principal_id=:by_principal';
        $sqlparms[':by_principal'] = $by_principal;
      }
      $qry = new AwlQuery($sql, $sqlparms);
      $qry->Exec('ACL',__LINE__,__FILE__);
      break;

    case 'DAV::all':
//      $principal_type = 'all';
      $request->PreconditionFailed(403, 'allowed-principal', 'May not set privileges for unauthenticated users' );
      break;

    default:
      $request->PreconditionFailed(403, 'recognized-principal' );
      break;
  }

}

$qry = new AwlQuery('COMMIT');
$qry->Exec('ACL',__LINE__,__FILE__);


$request->DoResponse( 200 );
