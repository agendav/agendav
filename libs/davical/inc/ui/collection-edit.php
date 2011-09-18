<?php

// Editor component for company records
$editor = new Editor(translate('Collection'), 'collection');
param_to_global('id', 'int', 'old_id', 'collection_id' );
param_to_global('user_no', 'int' );
param_to_global('principal_id', 'int' );
param_to_global('collection_name', '{^.+$}' );
if ( isset($user_no) ) $usr = getUserByID($user_no);
if ( isset($principal_id) ) $usr = getPrincipalByID($principal_id);
$editor->SetLookup( 'timezone', 'SELECT \'\', \'*** Unknown ***\' UNION SELECT tz_id, tz_locn FROM time_zone WHERE tz_id = tz_locn AND length(tz_spec) > 100 ORDER BY 1' );
$editor->SetLookup( 'schedule_transp', 'SELECT \'opaque\', \'Opaque\' UNION SELECT \'transp\', \'Transparent\'' );


$editor->AddAttribute('timezone', 'id', 'fld_timezone' );
$editor->AddAttribute('schedule_transp', 'id', 'fld_schedule_transp' );
$editor->AddAttribute('is_calendar', 'id', 'fld_is_calendar');
$editor->AddAttribute('is_addressbook', 'id', 'fld_is_addressbook');
$editor->AddAttribute('is_calendar', 'onclick', 'toggle_enabled(\'fld_is_calendar\',\'=fld_timezone\',\'=fld_schedule_transp\',\'!fld_is_addressbook\',\'=fld_ics_file\');');
$editor->AddAttribute('is_addressbook', 'onclick', 'toggle_enabled(\'fld_is_addressbook\',\'!fld_is_calendar\');');

$editor->AddField('use_default_privs','default_privileges IS NULL');
$editor->AddAttribute('use_default_privs', 'id', 'fld_use_default_privs');
$editor->AddAttribute('use_default_privs', 'onclick', 'toggle_visible(\'fld_use_default_privs\',\'!privileges_settings\');');

$editor->AddField('ics_file', "''");
$editor->AddAttribute('ics_file', 'title', translate('Upload a .ics calendar in iCalendar format to initialise or replace this calendar.'));
$editor->AddAttribute('ics_file', 'id', 'fld_ics_file');

$editor->SetWhere( 'collection_id='.$id );

$privilege_names = array( 'read', 'write-properties', 'write-content', 'unlock', 'read-acl', 'read-current-user-privilege-set',
                         'bind', 'unbind', 'write-acl', 'read-free-busy', 'schedule-deliver-invite', 'schedule-deliver-reply',
                         'schedule-query-freebusy', 'schedule-send-invite', 'schedule-send-reply', 'schedule-send-freebusy' );

$params = array(
  ':session_principal' => $session->principal_id,
  ':scan_depth'        => $c->permission_scan_depth
);
$is_update = ( $_POST['_editor_action'][$editor->Id] == 'update' );
if ( isset($collection_name) ) $collection_name = trim(str_replace( '/', '', $collection_name));
if ( !$is_update && isset($collection_name) && $collection_name != '' && is_object($usr) ) {
  $_POST['dav_name'] = sprintf('/%s/%s/', $usr->username, $collection_name );
  $_POST['parent_container'] = sprintf('/%s/', $usr->username );
  $params[':collection_path'] = $_POST['dav_name'];
  $privsql = 'SELECT path_privs( :session_principal, :collection_path, :scan_depth) AS priv';
}
else if ( $id > 0 ) {
  $params[':collection_id'] = $id;
  $privsql = 'SELECT path_privs( :session_principal, dav_name, :scan_depth) AS priv FROM collection WHERE collection_id = :collection_id';
}
else {
  if ( $editor->IsSubmit() && !$is_update && isset($collection_name) && $collection_name == '' ) {
    $c->messages[] =  i18n('The collection name may not be blank.');
  }
}

if ( isset($privsql) ) {
  $privqry = new AwlQuery( $privsql, $params );
  $privqry->Exec('admin-collection-edit',__LINE__,__FILE__);
  $permissions = $privqry->Fetch();
  $can_write_collection = ($session->AllowedTo('Admin') || (bindec($permissions->priv) & privilege_to_bits('DAV::bind')) );
}

dbg_error_log('collection-edit', "Can write collection: %s", ($can_write_collection? 'yes' : 'no') );

$pwstars = '@@@@@@@@@@';
if ( $can_write_collection && $editor->IsSubmit() ) {
  $editor->WhereNewRecord( "collection_id=(SELECT CURRVAL('dav_id_seq'))" );
  if ( $_POST['use_default_privs'] == 'on' ) {
    $_POST['default_privileges'] = '';
  }
  else if ( isset($_POST['default_privileges']) ) {
    $privilege_bitpos = array_flip($privilege_names);
    $priv_names = array_keys($_POST['default_privileges']);
    $privs = privilege_to_bits($priv_names);
    $_POST['default_privileges'] = sprintf('%024s',decbin($privs));
    $editor->Assign('default_privileges', $privs_dec);
  }
  $is_update = ( $_POST['_editor_action'][$editor->Id] == 'update' );
  if ( $_POST['timezone'] == '' ) unset($_POST['timezone']);
  $resourcetypes = '<DAV::collection/>';
  if ( isset($_POST['is_calendar'])    && $_POST['is_calendar'] == 'on' )    $resourcetypes .= '<urn:ietf:params:xml:ns:caldav:calendar/>';
  if ( isset($_POST['is_addressbook']) && $_POST['is_addressbook'] == 'on' ) $resourcetypes .= '<urn:ietf:params:xml:ns:carddav:addressbook/>';
  $_POST['resourcetypes'] = $resourcetypes;
  if ( $editor->IsCreate() ) {
    $c->messages[] = i18n("Creating new Collection.");
  }
  else {
    $c->messages[] = i18n("Updating Collection record.");
  }
  if ( !$editor->Write() ) { 
    $c->messages[] = i18n("Failed to write collection.");
    if ( $id > 0 ) $editor->GetRecord();
  }
  else if ( isset($_FILES['ics_file']['tmp_name']) && $_FILES['ics_file']['tmp_name'] != '' ) {
    /**
    * If the user has uploaded a .ics file as a calendar, we fake this out
    * as if it were a "PUT" request against a collection.  This is something
    * of a hack.  It works though :-)
    */
    $ics = trim(file_get_contents($_FILES['ics_file']['tmp_name']));
    dbg_error_log('collection-edit',':Write: Loaded %d bytes from %s', strlen($ics), $_FILES['ics_file']['tmp_name'] );
    include_once('check_UTF8.php');
    if ( !check_string($ics) ) $ics = force_utf8($ics);

    if ( check_string($ics) ) {
      $path = $editor->Value('dav_name');
      $user_no = $editor->Value('user_no');
      $username = $editor->Value('username');
      include_once('caldav-PUT-functions.php');
      controlRequestContainer( $username, $user_no, $path, false, ($publicly_readable == 'on' ? true : false));
      import_collection( $ics, $user_no, $path, $session->user_no );
      $c->messages[] = sprintf(translate('Calendar "%s" was loaded from file.'), $path);
    }
    else {
      $c->messages[] =  i18n('The file is not UTF-8 encoded, please check the error for more details.');
    }
  }
}
else {
  if ( $id > 0 ) $editor->GetRecord();
  if ( $editor->IsSubmit() ) {
    $c->messages[] = i18n('You do not have permission to modify this record.');
  }
}
if ( $editor->Available() ) {
  $c->page_title = $editor->Title(translate('Collection').': '.$editor->Value('dav_displayname'));
  $entryqry = new AwlQuery( 'SELECT count(*) as count from caldav_data where collection_id='.$editor->Value('collection_id')  );
  $entryqry->Exec('admin-collection-edit');
  $entries = $entryqry->Fetch();  $entries = $entries->count;
}
else {
  $c->page_title = $editor->Title(translate('Create New Collection'));
  $privs = decbin(privilege_to_bits($c->default_privileges));
  $editor->Assign('default_privileges', $privs);
  $editor->Assign('username', $usr->username);
  $editor->Assign('user_no', $usr->user_no);
  $editor->Assign('is_calendar', 't' );
  $editor->Assign('use_default_privs', 't');
  $entries = 0;
}


$privilege_xlate = array(
  'read' => translate('Read'),
  'write-properties' => translate('Write Metadata'),
  'write-content' => translate('Write Data'),
  'unlock' => translate('Override a Lock'),
  'read-acl' => translate('Read Access Controls'),
  'read-current-user-privilege-set' => translate('Read Current User\'s Access'),
  'bind' => translate('Create Events/Collections'),
  'unbind' => translate('Delete Events/Collections'),
  'write-acl' => translate('Write Access Controls'),
  'read-free-busy' => translate('Read Free/Busy Information'),
  'schedule-deliver-invite' => translate('Scheduling: Deliver an Invitation'),
  'schedule-deliver-reply' => translate('Scheduling: Deliver a Reply'),
  'schedule-query-freebusy' => translate('Scheduling: Query free/busy'),
  'schedule-send-invite' => translate('Scheduling: Send an Invitation'),
  'schedule-send-reply' => translate('Scheduling: Send a Reply'),
  'schedule-send-freebusy' => translate('Scheduling: Send free/busy'),
  'write' => translate('Write'),
  'schedule-deliver' => translate('Scheduling: Delivery'),
  'schedule-send' => translate('Scheduling: Sending')
);

/**
* privilege_format_function is for formatting the binary privileges from the
* database, including localising them.  This is a hook function for a browser
* column object, so it takes three parameters:
* @param mixed $value The value of the column.
* @param BrowserColumn $column The BrowserColumn object we are hooked into.
* @param dbrow $row The row object we read from the database.
* @return string The formatted privileges.
*/
function privilege_format_function( $value, $column, $row ) {
  global $privilege_xlate;

  $privs = bits_to_privilege($value);
  $formatted = '';
  foreach( $privs AS $k => $v ) {
    $formatted .= ($formatted == '' ? '' : ' , ');
    $v = preg_replace( '{^.*:}', '', $v );
    $formatted .= (isset($privilege_xlate[$v]) ? $privilege_xlate[$v] : $v );
  }
  return $formatted;
}

$default_privileges = bindec($editor->Value('default_privileges'));
$privileges_set = '<div id="privileges">';
for( $i=0; $i<count($privilege_names); $i++ ) {
  $privilege_set = ( (1 << $i) & $default_privileges ? ' CHECKED' : '');
  $privileges_set .= '<label class="privilege"><input name="default_privileges['.$privilege_names[$i].']" id="default_privileges_'.$privilege_names[$i].'" type="checkbox"'.$privilege_set.'>'.$privilege_xlate[$privilege_names[$i]].'</label>'."\n";
}
$privileges_set .= '</div>';

$prompt_collection_id = translate('Collection ID');
$value_id = ( $editor->Available() ? '##collection_id.hidden####collection_id.value##' : translate('New Collection'));
$prompt_dav_name = translate('DAV Path');
$value_dav_name = $c->base_url.'/caldav.php'. ( $editor->Available() ? '##dav_name.value##' : '/##user_no.hidden####username.value##/ ##collection_name.input.30##' );
$prompt_load_file = translate('Load From File');
$prompt_displayname = translate('Displayname');
$prompt_entries = translate('Items in Collection');
$prompt_public = translate('Publicly Readable');
$prompt_calendar = translate('Is a Calendar');
$prompt_addressbook = translate('Is an Addressbook');
$prompt_use_default_privs = translate('Specific Privileges');
$prompt_privileges = translate('Default Privileges');
$prompt_description = translate('Description');
$prompt_schedule_transp = translate('Schedule Transparency');
$prompt_timezone = translate('Calendar Timezone');

$btn_all = htmlspecialchars(translate('All'));             $btn_all_title = htmlspecialchars(translate('Toggle all privileges'));
$btn_rw  = htmlspecialchars(translate('Read/Write'));      $btn_rw_title = htmlspecialchars(translate('Set read+write privileges'));
$btn_read = htmlspecialchars(translate('Read'));           $btn_read_title = htmlspecialchars(translate('Set read privileges'));
$btn_fb = htmlspecialchars(translate('Free/Busy'));        $btn_fb_title = htmlspecialchars(translate('Set free/busy privileges'));
$btn_sd = htmlspecialchars(translate('Schedule Deliver')); $btn_sd_title = htmlspecialchars(translate('Set schedule-deliver privileges'));
$btn_ss = htmlspecialchars(translate('Schedule Send'));    $btn_ss_title = htmlspecialchars(translate('Set schedule-deliver privileges'));


$id = $editor->Value('collection_id');
$template = <<<EOTEMPLATE
##form##
<script language="javascript">
function toggle_privileges() {
  var argv = toggle_privileges.arguments;
  var argc = argv.length;

  if ( argc < 2 ) {
    return;
  }
  var match_me = argv[0];

  var set_to = -1;
  if ( argv[1] == 'all' ) {
    var form = document.getElementById(argv[2]);
    var fieldcount = form.elements.length;
    var matching = '/^' + match_me + '/';
    for (var i = 0; i < fieldcount; i++) {
      var fieldname = form.elements[i].name;
      if ( fieldname.match( match_me ) ) {
        if ( set_to == -1 ) {
          set_to = ( form.elements[i].checked ? 0 : 1 );
        }
        form.elements[i].checked = set_to;
      }
    }
  }
  else {
    for (var i = 1; i < argc; i++) {
      var f = document.getElementById( match_me + '_' + argv[i]);
      if ( set_to == -1 ) {
        set_to = ( f.checked ? 0 : 1 );
      }
      f.checked = set_to;
    }
  }
}

function toggle_enabled() {
  var argv = toggle_enabled.arguments;
  var argc = argv.length;

  var fld_checkbox =  document.getElementById(argv[0]);

  if ( argc < 2 ) {
    return;
  }

  for (var i = 1; i < argc; i++) {
    var fld_id = argv[i].substr(1);
    var fld_logical = argv[i].substr(0,1);
    var f = document.getElementById(fld_id);
    if ( fld_logical == '=' )
      f.disabled = !fld_checkbox.checked;
    else
      f.disabled = fld_checkbox.checked;
  }
}

function toggle_visible() {
  var argv = toggle_visible.arguments;
  var argc = argv.length;

  var fld_checkbox =  document.getElementById(argv[0]);

  if ( argc < 2 ) {
    return;
  }

  for (var i = 1; i < argc; i++) {
    var block_id = argv[i].substr(1);
    var block_logical = argv[i].substr(0,1);
    var b = document.getElementById(block_id);
    if ( block_logical == '!' )
      b.style.display = (fld_checkbox.checked ? 'none' : '');
    else
      b.style.display = (!fld_checkbox.checked ? 'none' : '');
  }
}
</script>
<style>
th.right, label.privilege {
  white-space:nowrap;
}
label.privilege {
  margin:0.2em 1em 0.2em 0.1em;
  padding:0 0.2em;
  line-height:1.6em;
  font-size: 87%;
}
</style>
<table>
 <tr> <th class="right">$prompt_collection_id:</th>    <td class="left">$value_id</td> </tr>
 <tr> <th class="right">$prompt_dav_name:</th>         <td class="left">$value_dav_name</td> </tr>
 <tr> <th class="right">$prompt_entries:</th>          <td class="left">$entries</td> </tr>
 <tr> <th class="right">$prompt_load_file:</th>        <td class="left">##ics_file.file.60##</td> </tr>
 <tr> <th class="right">$prompt_displayname:</th>      <td class="left">##dav_displayname.input.50##</td> </tr>
 <tr> <th class="right">$prompt_public:</th>           <td class="left">##publicly_readable.checkbox##</td> </tr>
 <tr> <th class="right">$prompt_calendar:</th>         <td class="left">##is_calendar.checkbox##</td> </tr>
 <tr> <th class="right">$prompt_addressbook:</th>      <td class="left">##is_addressbook.checkbox##</td> </tr>
 <tr> <th class="right">$prompt_privileges:</th><td class="left">##use_default_privs.checkbox## &nbsp; &nbsp; &nbsp;
 <div id="privileges_settings">
<input type="button" value="$btn_all" class="submit" title="$btn_all_title" onclick="toggle_privileges('default_privileges', 'all', 'editor_1');">
<input type="button" value="$btn_rw" class="submit" title="$btn_rw_title"
 onclick="toggle_privileges('default_privileges', 'read', 'write-properties', 'write-content', 'bind', 'unbind', 'read-free-busy',
                            'read-current-user-privilege-set', 'schedule-deliver-invite', 'schedule-deliver-reply', 'schedule-query-freebusy',
                            'schedule-send-invite', 'schedule-send-reply', 'schedule-send-freebusy' );">
<input type="button" value="$btn_read" class="submit" title="$btn_read_title"
 onclick="toggle_privileges('default_privileges', 'read', 'read-free-busy', 'schedule-query-freebusy', 'read-current-user-privilege-set' );">
<input type="button" value="$btn_fb" class="submit" title="$btn_fb_title"
 onclick="toggle_privileges('default_privileges', 'read-free-busy', 'schedule-query-freebusy' );">
<input type="button" value="$btn_sd" class="submit" title="$btn_sd_title"
 onclick="toggle_privileges('default_privileges', 'schedule-deliver-invite', 'schedule-deliver-reply', 'schedule-query-freebusy' );">
<input type="button" value="$btn_ss" class="submit" title="$btn_ss_title"
 onclick="toggle_privileges('default_privileges', 'schedule-send-invite', 'schedule-send-reply', 'schedule-send-freebusy' );">
<br>$privileges_set</div></td> </tr>
 <tr> <th class="right">$prompt_timezone:</th>         <td class="left">##timezone.select##</td> </tr>
 <tr> <th class="right">$prompt_schedule_transp:</th>  <td class="left">##schedule_transp.select##</td> </tr>
 <tr> <th class="right">$prompt_description:</th>      <td class="left">##description.textarea.78x6##</td> </tr>
 <tr> <th class="right"></th>                   <td class="left" colspan="2">##submit##</td> </tr>
</table>
</form>
<script language="javascript">
toggle_enabled('fld_is_calendar','=fld_timezone','=fld_schedule_transp','!fld_is_addressbook','=fld_ics_file');
toggle_enabled('fld_is_addressbook','!fld_is_calendar');
toggle_visible('fld_use_default_privs','!privileges_settings');
</script>

EOTEMPLATE;


$editor->SetTemplate( $template );
$page_elements[] = $editor;


if ( $editor->Available() ) {

  $c->stylesheets[] = 'css/browse.css';
  $c->scripts[] = 'js/browse.js';


  $grantrow = new Editor("Grants", "grants");
  $grantrow->SetSubmitName( 'savegrantrow' );
  $grantrow->SetLookup( 'to_principal', 'SELECT principal_id, displayname FROM dav_principal WHERE principal_id NOT IN (SELECT member_id FROM group_member WHERE group_id = '.$id.') ORDER BY displayname' );
  if ( $can_write_collection ) {
    if ( $grantrow->IsSubmit() ) {
      $_POST['by_collection'] = $id;
      $to_principal = intval($_POST['to_principal']);
      $orig_to_id =  intval($_POST['orig_to_id']);
      $grantrow->SetWhere( "by_collection=".$id." AND to_principal=$orig_to_id");
      if ( isset($_POST['grant_privileges']) ) {
        $privilege_bitpos = array_flip($privilege_names);
        $priv_names = array_keys($_POST['grant_privileges']);
        $privs = privilege_to_bits($priv_names);
        $_POST['privileges'] = sprintf('%024s',decbin($privs));
        $grantrow->Assign('privileges', $privs_dec);
      }
      $grantrow->Write( );
      unset($_GET['to_principal']);
    }
    elseif ( isset($_GET['delete_grant']) ) {
      $qry = new AwlQuery("DELETE FROM grants WHERE by_collection=:grantor_id AND to_principal = :to_principal",
                            array( ':grantor_id' => $id, ':to_principal' => intval($_GET['delete_grant']) ));
      $qry->Exec('collection-edit');
    }
  }

  function edit_grant_row( $row_data ) {
    global $grantrow, $id, $privilege_xlate, $privilege_names;
    global $btn_all, $btn_all_title, $btn_rw, $btn_rw_title, $btn_read, $btn_read_title;
    global $btn_fb, $btn_fb_title, $btn_sd, $btn_sd_title, $btn_ss, $btn_ss_title;

    $submit_label = translate('Grant');
    if ( $row_data->to_principal > -1 ) {
      $grantrow->SetRecord( $row_data );
      $submit_label = translate('Apply Changes');
    }

    $grant_privileges = bindec($grantrow->Value('grant_privileges'));
    $privileges_set = '<div id="privileges">';
    for( $i=0; $i < count($privilege_names); $i++ ) {
      $privilege_set = ( (1 << $i) & $grant_privileges ? ' CHECKED' : '');
      $privileges_set .= '<label class="privilege"><input name="grant_privileges['.$privilege_names[$i].']" id="grant_privileges_'.$privilege_names[$i].'" type="checkbox"'.$privilege_set.'>'.$privilege_xlate[$privilege_names[$i]].'</label>'."\n";
    }
    $privileges_set .= '</div>';

    $orig_to_id = $row_data->to_principal;
    $form_id = $grantrow->Id();
    $form_url = preg_replace( '#&(edit|delete)_grant=\d+#', '', $_SERVER['REQUEST_URI'] );

    $template = <<<EOTEMPLATE
<form method="POST" enctype="multipart/form-data" id="form_$form_id" action="$form_url">
  <td class="left" colspan="2"><input type="hidden" name="id" value="$id"><input type="hidden" name="orig_to_id" value="$orig_to_id">##to_principal.select##</td>
  <td class="left" colspan="2">
<input type="button" value="$btn_all" class="submit" title="$btn_all_title" onclick="toggle_privileges('grant_privileges', 'all', 'form_$form_id');">
<input type="button" value="$btn_rw" class="submit" title="$btn_rw_title"
 onclick="toggle_privileges('grant_privileges', 'read', 'write-properties', 'write-content', 'bind', 'unbind', 'read-free-busy',
                            'read-current-user-privilege-set', 'schedule-deliver-invite', 'schedule-deliver-reply', 'schedule-query-freebusy',
                            'schedule-send-invite', 'schedule-send-reply', 'schedule-send-freebusy' );">
<input type="button" value="$btn_read" class="submit" title="$btn_read_title"
 onclick="toggle_privileges('grant_privileges', 'read', 'read-free-busy', 'schedule-query-freebusy', 'read-current-user-privilege-set' );">
<input type="button" value="$btn_fb" class="submit" title="$btn_fb_title"
 onclick="toggle_privileges('grant_privileges', 'read-free-busy', 'schedule-query-freebusy' );">
<input type="button" value="$btn_sd" class="submit" title="$btn_sd_title"
 onclick="toggle_privileges('grant_privileges', 'schedule-deliver-invite', 'schedule-deliver-reply', 'schedule-query-freebusy' );">
<input type="button" value="$btn_ss" class="submit" title="$btn_ss_title"
 onclick="toggle_privileges('grant_privileges', 'schedule-send-invite', 'schedule-send-reply', 'schedule-send-freebusy' );">
<br>$privileges_set
  <td class="center">##$submit_label.submit##</td>
</form>

EOTEMPLATE;

    $grantrow->SetTemplate( $template );
    $grantrow->Title("");

    return $grantrow->Render();
  }

  $browser = new Browser(translate('Collection Grants'));

  $browser->AddColumn( 'to_principal', translate('To ID'), 'right', '##principal_link##' );
  $rowurl = $c->base_url . '/admin.php?action=edit&t=principal&id=';
  $browser->AddHidden( 'principal_link', "'<a href=\"$rowurl' || to_principal || '\">' || to_principal || '</a>'" );
  $browser->AddHidden( 'grant_privileges', 'privileges' );
  $browser->AddColumn( 'displayname', translate('Display Name') );
  $browser->AddColumn( 'privs', translate('Privileges'), '', '', 'privileges', '', '', 'privilege_format_function' );
  $browser->AddColumn( 'members', translate('Has Members'), '', '', 'has_members_list(principal_id)' );

  if ( $can_write_collection ) {
    $del_link  = '<a href="'.$c->base_url.'/admin.php?action=edit&t=collection&id='.$id.'&delete_grant=##to_principal##" class="submit">'.translate('Revoke').'</a>';
    $edit_link  = '<a href="'.$c->base_url.'/admin.php?action=edit&t=collection&id='.$id.'&edit_grant=##to_principal##" class="submit">'.translate('Edit').'</a>';
    $browser->AddColumn( 'action', translate('Action'), 'center', '', "'$edit_link&nbsp;$del_link'" );
  }

  $browser->SetOrdering( 'displayname', 'A' );

  $browser->SetJoins( 'grants LEFT JOIN dav_principal ON (to_principal = principal_id) ' );
  $browser->SetWhere( 'by_collection = '.$id );

  if ( $c->enable_row_linking ) {
    $browser->RowFormat( '<tr onMouseover="LinkHref(this,1);" title="'.translate('Click to edit principal details').'" class="r%d">', '</tr>', '#even' );
  }
  else {
    $browser->RowFormat( '<tr class="r%d">', '</tr>', '#even' );
  }
  $browser->DoQuery();
  $page_elements[] = $browser;

  if ( $can_write_collection ) {
    if ( isset($_GET['edit_grant']) ) {
      $browser->MatchedRow('to_principal', $_GET['edit_grant'], 'edit_grant_row');
    }
    else {
      $extra_row = array( 'to_principal' => -1 );
      $browser->MatchedRow('to_principal', -1, 'edit_grant_row');
      $extra_row = (object) $extra_row;
      $browser->AddRow($extra_row);
    }
  }


  $browser = new Browser(translate('Access Tickets'));
  $browser->AddHidden( 'dav_owner_id' );
  $browser->AddColumn( 'ticket_id', translate('Ticket ID'), '', '' );
  $browser->AddColumn( 'target', translate('Target'), '', '<td style="white-space:nowrap;">%s</td>', "'".$c->base_url.'/caldav.php'."' ||COALESCE(d.dav_name,c.dav_name)" );
  $browser->AddColumn( 'expiry', translate('Expires'), '', '', 'TO_CHAR(expires,\'YYYYMMDD"T"HH:MI:SS\')');
  $browser->AddColumn( 'privs', translate('Privileges'), '', '', "privileges_list(privileges)" );
  $delurl = $c->base_url . '/admin.php?action=edit&t=principal&id=##dav_owner_id##&ticket_id=##URL:ticket_id##&subaction=delete_ticket';
  $browser->AddColumn( 'delete', translate('Action'), 'center', '', "'<a class=\"submit\" href=\"$delurl\">".translate('Delete')."</a>'" );

  $browser->SetOrdering( 'target', 'A' );

  $browser->SetJoins( 'access_ticket t LEFT JOIN collection c ON (target_collection_id=collection_id) LEFT JOIN caldav_data d ON (target_resource_id=dav_id)' );
  $browser->SetWhere( 'target_collection_id = '.intval($editor->Value('collection_id')) );

  $browser->RowFormat( '<tr class="r%d">', '</tr>', '#even' );

  $browser->DoQuery();
  $page_elements[] = $browser;

  
/**
 bind_id          | bigint | not null default nextval('dav_id_seq'::regclass)
 bound_source_id  | bigint | 
 access_ticket_id | text   | 
 dav_owner_id     | bigint | not null
 parent_container | text   | not null
 dav_name         | text   | not null
 dav_displayname  | text   | 
 */

  $browser = new Browser(translate('Bindings to this Collection'));
  $browser->AddColumn( 'bind_id', translate('ID'), '', '' );
  $browser->AddHidden( 'b.dav_owner_id' );
  $browser->AddColumn( 'bound_as', translate('Bound As'), '', '<td style="white-space:nowrap;">%s</td>', 'b.dav_name' );
  $browser->AddColumn( 'access_ticket_id', translate('Ticket ID'), '', '' );
  $browser->AddColumn( 'privs', translate('Privileges'), '', '', "privileges_list(privileges)" );
  $delurl = $c->base_url . '/admin.php?action=edit&t=principal&id=##dav_owner_id##&bind_id=##URL:bind_id##&subaction=delete_binding';
  $browser->AddColumn( 'delete', translate('Action'), 'center', '', "'<a class=\"submit\" href=\"$delurl\">".translate('Delete')."</a>'" );

  $browser->SetOrdering( 'target', 'A' );

  $browser->SetJoins( 'dav_binding b LEFT JOIN collection c ON (bound_source_id=collection_id) LEFT JOIN access_ticket t ON (ticket_id=access_ticket_id)' );
  $browser->SetWhere( 'bound_source_id = '.intval($editor->Value('collection_id')) );

  $browser->RowFormat( '<tr class="r%d">', '</tr>', '#even' );

  $browser->DoQuery();
  $page_elements[] = $browser;

}

