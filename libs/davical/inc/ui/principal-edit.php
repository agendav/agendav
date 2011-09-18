<?php

param_to_global('id', 'int', 'old_id', 'principal_id' );

$privilege_names = array( 'read', 'write-properties', 'write-content', 'unlock', 'read-acl', 'read-current-user-privilege-set',
                         'bind', 'unbind', 'write-acl', 'read-free-busy', 'schedule-deliver-invite', 'schedule-deliver-reply',
                         'schedule-query-freebusy', 'schedule-send-invite', 'schedule-send-reply', 'schedule-send-freebusy' );
$privilege_xlate = array(
  'all' => translate('All privileges'),
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


$delete_collection_confirmation_required = null;
$delete_principal_confirmation_required = null;
$delete_ticket_confirmation_required = null;
$delete_bind_in_confirmation_required = null;
$delete_binding_confirmation_required = null;

function handle_subaction( $subaction ) {
  global $session, $c, $id, $editor;
  global $delete_collection_confirmation_required;
  global $delete_principal_confirmation_required;
  global $delete_ticket_confirmation_required;
  global $delete_bind_in_confirmation_required;
  global $delete_binding_confirmation_required;
  
  dbg_error_log('admin-principal-edit',':handle_action: Action %s', $subaction );

  switch( $subaction ) {
    case 'delete_collection':
      dbg_error_log('admin-principal-edit',':handle_action: Deleting collection %s for principal %d', $_GET['dav_name'], $id );
      if ( $session->AllowedTo('Admin')
                || ($id > 0 && $session->principal_id == $id) ) {
        if ( $session->CheckConfirmationHash('GET', 'confirm') ) {
          dbg_error_log('admin-principal-edit',':handle_action: Allowed to delete collection %s for principal %d', $_GET['dav_name'], $id );
          $qry = new AwlQuery('DELETE FROM collection WHERE dav_name=?;', $_GET['dav_name'] );
          if ( $qry->Exec() ) {
            $c->messages[] = i18n('Collection deleted');
            return true;
          }
          else {
            $c->messages[] = i18n('There was an error writing to the database.');
            return false;
          }
        }
        else {
          $c->messages[] = i18n('Please confirm deletion of collection - see below');
          $delete_collection_confirmation_required = $session->BuildConfirmationHash('GET', 'confirm');
          return false;
        }
      }
      break;

    case 'delete_principal':
      dbg_error_log('admin-principal-edit',':handle_action: Deleting principal %d', $id );
      if ( $session->AllowedTo('Admin') ) {
        if ( isset($id) && $id > 1 && $session->CheckConfirmationHash('GET', 'confirm') ) {
          dbg_error_log('admin-principal-edit',':handle_action: Allowed to delete principal %d -%s', $id );
          $qry = new AwlQuery('DELETE FROM dav_principal WHERE principal_id=?', $id );
          if ( $qry->Exec() ) {
            $c->messages[] = i18n('Principal deleted');
            return true;
          }
          else {
            $c->messages[] = i18n('There was an error writing to the database.');
            return false;
          }
        }
        else {
          $c->messages[] = i18n('Please confirm deletion of the principal');
          $delete_principal_confirmation_required = $session->BuildConfirmationHash('GET', 'confirm');
          return false;
        }
      }
      break;

    case 'delete_ticket':
      dbg_error_log('admin-principal-edit',':handle_action: Deleting ticket "%s" for principal %d', $_GET['ticket_id'], $id );
      if ( $session->AllowedTo('Admin')
                || ($id > 0 && $session->principal_id == $id) ) {
        if ( $session->CheckConfirmationHash('GET', 'confirm') ) {
          dbg_error_log('admin-principal-edit',':handle_action: Allowed to delete ticket "%s" for principal %d', $_GET['ticket_id'], $id );
          $qry = new AwlQuery('DELETE FROM access_ticket WHERE ticket_id=?;', $_GET['ticket_id'] );
          if ( $qry->Exec() ) {
            $c->messages[] = i18n('Access ticket deleted');
            return true;
          }
          else {
            $c->messages[] = i18n('There was an error writing to the database.');
            return false;
          }
        }
        else {
          $c->messages[] = i18n('Please confirm deletion of access ticket - see below');
          $delete_ticket_confirmation_required = $session->BuildConfirmationHash('GET', 'confirm');
          return false;
        }
      }
      break;

    case 'delete_bind_in':
    case 'delete_binding':
      dbg_error_log('admin-principal-edit',':handle_action: Deleting binding "%s" for principal %d', $_GET['bind_id'], $id );
      if ( $session->AllowedTo('Admin')
                || ($id > 0 && $session->principal_id == $id) ) {
        if ( $session->CheckConfirmationHash('GET', 'confirm') ) {
          dbg_error_log('admin-principal-edit',':handle_action: Allowed to delete ticket "%s" for principal %d', $_GET['bind_id'], $id );
          $qry = new AwlQuery('DELETE FROM dav_binding WHERE bind_id=?;', $_GET['bind_id'] );
          if ( $qry->Exec() ) {
            $c->messages[] = i18n('Binding deleted');
            return true;
          }
          else {
            $c->messages[] = i18n('There was an error writing to the database.');
            return false;
          }
        }
        else {
          $c->messages[] = i18n('Please confirm deletion of binding - see below');
          if ( $subaction == 'delete_bind_in' ) {
            $delete_bind_in_confirmation_required = $session->BuildConfirmationHash('GET', 'confirm');
          }
          else {
            $delete_binding_confirmation_required = $session->BuildConfirmationHash('GET', 'confirm');
          }
          return false;
        }
      }
      break;

      default:
      return false;
  }
  return false;
}

function principal_editor() {
  global $id, $can_write_principal, $session;
  $editor = new Editor(translate('Principal'), 'dav_principal');
  
  $editor->SetLookup( 'date_format_type', "SELECT 'E', 'European' UNION SELECT 'U', 'US Format' UNION SELECT 'I', 'ISO Format'" );
  $editor->SetLookup( 'type_id', 'SELECT principal_type_id, principal_type_desc FROM principal_type ORDER BY principal_type_id' );
  $editor->SetLookup( 'locale', 'SELECT \'\', \''.translate("*** Default Locale ***").'\' UNION SELECT locale, locale_name_locale FROM supported_locales ORDER BY 1 ASC' );
  $editor->AddAttribute( 'locale', 'title', translate("The preferred language for this person.") );
  $editor->AddAttribute( 'fullname', 'title', translate("The full name for this person, group or other type of principal.") );
  $editor->SetWhere( 'principal_id='.$id );
  
  $editor->AddField('is_admin', 'EXISTS( SELECT 1 FROM role_member WHERE role_no = 1 AND role_member.user_no = dav_principal.user_no )' );
  $editor->AddAttribute('is_admin', 'title', translate('An "Administrator" user has full rights to the whole DAViCal System'));
  
  $post_values = false;
  
  if ( isset($_POST['xxxxusername']) ) {
    $_POST['xxxxusername'] = trim(str_replace('/', '', $_POST['xxxxusername']));
    if ( $_POST['xxxxusername'] == '' ) {
      $c->messages[] = i18n("The username must not be blank, and may not contain a slash");
      $can_write_principal = false;
    }
  };
  if ( isset($_POST['fullname']) && trim($_POST['fullname']) == '' ) {
    $c->messages[] = i18n("The full name must not be blank.");
    $can_write_principal = false;
  };
  if ( isset($_POST['email']) && trim($_POST['email']) == '' ) {
    $c->messages[] = i18n("The email address really should not be blank.");
  }
  
  $pwstars = '@@@@@@@@@@';
  if ( $can_write_principal && $editor->IsSubmit() ) {
    $editor->WhereNewRecord( "principal_id=(SELECT CURRVAL('dav_id_seq'))" );
    if ( ! $session->AllowedTo('Admin') ) {
      unset($_POST['admin_role']);
      unset($_POST['user_active']);
    }
    unset($_POST['password']);
    if ( $_POST['newpass1'] != '' && $_POST['newpass1'] != $pwstars ) {
      if ( $_POST['newpass1'] == $_POST['newpass2'] ) {
        $_POST['password'] = $_POST['newpass1'];
      }
      else {
        $c->messages[] = "Password not updated. The supplied passwords do not match.";
      }
    }
    if ( isset($_POST['fullname']) && !isset($_POST['displayname']) ) {
      $_POST['displayname'] = $_POST['fullname'];
    }
    if ( isset($_POST['default_privileges']) ) {
      $privilege_bitpos = array_flip($privilege_names);
      $priv_names = array_keys($_POST['default_privileges']);
      $privs = privilege_to_bits($priv_names);
      $_POST['default_privileges'] = sprintf('%024s',decbin($privs));
      $editor->Assign('default_privileges', $privs_dec);
    }
    if ( $editor->IsCreate() ) {
      $c->messages[] = i18n("Creating new Principal record.");
    }
    else {
      $c->messages[] = i18n("Updating Principal record.");
    }
    $editor->Write();
    if ( $_POST['type_id'] != 3 && $editor->IsCreate() ) {
      /** We only add the default calendar if it isn't a group, and this is a create action */
      require_once('auth-functions.php');
      CreateHomeCalendar($editor->Value('username'));
    }
    if ( $session->AllowedTo('Admin') ) {
      if ( $_POST['is_admin'] == 'on' ) {
        $sql = 'INSERT INTO role_member (role_no, user_no) SELECT 1, dav_principal.user_no FROM dav_principal WHERE user_no = :user_no AND NOT EXISTS(SELECT 1 FROM role_member rm WHERE rm.role_no = 1 AND rm.user_no = dav_principal.user_no )';
        $editor->Assign('is_admin', 't');
      }
      else {
        $sql = 'DELETE FROM role_member WHERE role_no = 1 AND user_no = :user_no';
        $editor->Assign('is_admin', 'f');
      }
      $params[':user_no'] = $editor->Value('user_no');
      $qry = new AwlQuery( $sql, $params );
      $qry->Exec('admin-principal-edit');
    }
  }
  else if ( isset($id) && $id > 0 ) {
    $editor->GetRecord();
    if ( $editor->IsSubmit() ) {
      $c->messages[] = i18n('You do not have permission to modify this record.');
    }
  }
  if ( $editor->Available() ) {
    $c->page_title = $editor->Title(translate('Principal').': '.$editor->Value('fullname'));
  }
  else {
    $c->page_title = $editor->Title(translate('Create New Principal'));
    $privs = decbin(privilege_to_bits($c->default_privileges));
    $editor->Assign('default_privileges', $privs);
    $editor->Assign('user_active', 't');
    foreach( $c->template_usr AS $k => $v ) {
      $editor->Assign($k, $v);
    }
  }
  if ( $post_values ) {
    $editor->PostToValues();
    if ( isset($_POST['default_privileges']) ) {
      $privilege_bitpos = array_flip($privilege_names);
      $priv_names = array_keys($_POST['default_privileges']);
      $privs = privilege_to_bits($priv_names);
      $_POST['default_privileges'] = sprintf('%024s',decbin($privs));
      $editor->Assign('default_privileges', $_POST['default_privileges']);
    }
  }


  $prompt_principal_id = translate('Principal ID');
  $value_id = ( $editor->Available() ? '##principal_id.hidden####principal_id.value##' : translate('New Principal'));
  $prompt_username = translate('Username');
  $prompt_password_1 = translate('Change Password');
  $prompt_password_2 = translate('Confirm Password');
  $prompt_fullname = translate('Fullname');
  $prompt_displayname = translate('Display Name');
  $prompt_email = translate('Email Address');
  $prompt_date_format = translate('Date Format Style');
  $prompt_admin = translate('Administrator');
  $prompt_active = translate('Active');
  $prompt_locale = translate('Locale');
  $prompt_type = translate('Principal Type');
  $prompt_privileges = translate('Privileges granted to All Users');
  
  $privs_html = build_privileges_html( $editor, 'default_privileges');
  
  $admin_row_entry = '';
  $delete_principal_button = '';
  if ( $session->AllowedTo('Admin') ) {
    $admin_row_entry = ' <tr> <th class="right">'.$prompt_admin.':</th><td class="left">##is_admin.checkbox##</td> </tr>';
    $admin_row_entry .= ' <tr> <th class="right">'.$prompt_active.':</th><td class="left">##user_active.checkbox##</td> </tr>';
    if ( isset($id) )
      $delete_principal_button = '<a href="'.$c->base_url . '/admin.php?action=edit&t=principal&subaction=delete_principal&id='.$id.'" class="submit">' . translate("Delete Principal") . '</a>';
  }
  
  $id = $editor->Value('principal_id');
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
</script>
<style>
th.right, label.privilege {
  white-space:nowrap;
}
label.privilege {
  margin:0.2em 1em 0.2em 0.1em;
  padding:0 0.2em;
  line-height:1.6em;
  font-size:87%;
}
</style>
<table>
 <tr> <th class="right">$prompt_principal_id:</th><td class="left">
  <table width="100%" class="form_inner"><tr>
   <td>$value_id</td>
   <td align="right">$delete_principal_button</td>
  </tr></table>
 </td></tr>
 <tr> <th class="right">$prompt_username:</th>    <td class="left">##xxxxusername.input.50##</td> </tr>
 <tr> <th class="right">$prompt_password_1:</th>  <td class="left">##newpass1.password.$pwstars##</td> </tr>
 <tr> <th class="right">$prompt_password_2:</th>  <td class="left">##newpass2.password.$pwstars##</td> </tr>
 <tr> <th class="right">$prompt_fullname:</th>    <td class="left">##fullname.input.50##</td> </tr>
 <tr> <th class="right">$prompt_email:</th>       <td class="left">##email.input.50##</td> </tr>
 <tr> <th class="right">$prompt_locale:</th>      <td class="left">##locale.select##</td> </tr>
 <tr> <th class="right">$prompt_date_format:</th> <td class="left">##date_format_type.select##</td> </tr>
 <tr> <th class="right">$prompt_type:</th>        <td class="left">##type_id.select##</td> </tr>
 $admin_row_entry
 <tr> <th class="right" style="white-space:normal;">$prompt_privileges:</th><td class="left">$privs_html</td> </tr>
 <tr> <th class="right"></th>                   <td class="left" colspan="2">##submit##</td> </tr>
</table>
</form>
EOTEMPLATE;

  $editor->SetTemplate( $template );
  return $editor;
}


function build_privileges_html( $ed, $fname ) {
  global $privilege_xlate, $privilege_names;

  $btn_all = htmlspecialchars(translate('All'));             $btn_all_title = htmlspecialchars(translate('Toggle all privileges'));
  $btn_rw  = htmlspecialchars(translate('Read/Write'));      $btn_rw_title = htmlspecialchars(translate('Set read+write privileges'));
  $btn_read = htmlspecialchars(translate('Read'));           $btn_read_title = htmlspecialchars(translate('Set read privileges'));
  $btn_fb = htmlspecialchars(translate('Free/Busy'));        $btn_fb_title = htmlspecialchars(translate('Set free/busy privileges'));
  $btn_sd = htmlspecialchars(translate('Schedule Deliver')); $btn_sd_title = htmlspecialchars(translate('Set schedule-deliver privileges'));
  $btn_ss = htmlspecialchars(translate('Schedule Send'));    $btn_ss_title = htmlspecialchars(translate('Set schedule-deliver privileges'));
  
  $privs_dec = bindec($ed->Value($fname));
  $privileges_set = '<div id="privileges">'."\n";
  for( $i=0; $i < count($privilege_names); $i++ ) {
    $privilege_set = ( (1 << $i) & $privs_dec ? ' CHECKED' : '');
    $privileges_set .= sprintf( '  <label class="privilege"><input name="%s[%s]" id="%s_%s" type="checkbox"%s>%s</label>'."\n",
                  $fname, $privilege_names[$i], $fname, $privilege_names[$i], $privilege_set,
                  $privilege_xlate[$privilege_names[$i]]);
  }
  $privileges_set .= '</div>'."\n";

  $form_id = $ed->Id();
  $html = <<<EOTEMPLATE
<input type="button" value="$btn_all" class="submit" title="$btn_all_title"
 onclick="toggle_privileges('$fname', 'all', 'form_$form_id');">
<input type="button" value="$btn_rw" class="submit" title="$btn_rw_title"
 onclick="toggle_privileges('$fname', 'read', 'write-properties', 'write-content', 'bind', 'unbind', 'read-free-busy', 'read-current-user-privilege-set', 'schedule-deliver-invite', 'schedule-deliver-reply', 'schedule-query-freebusy', 'schedule-send-invite', 'schedule-send-reply', 'schedule-send-freebusy' );">
<input type="button" value="$btn_read" class="submit" title="$btn_read_title"
 onclick="toggle_privileges('$fname', 'read', 'read-free-busy', 'schedule-query-freebusy', 'read-current-user-privilege-set' );">
<input type="button" value="$btn_fb" class="submit" title="$btn_fb_title"
 onclick="toggle_privileges('$fname', 'read-free-busy', 'schedule-query-freebusy' );">
<input type="button" value="$btn_sd" class="submit" title="$btn_sd_title"
 onclick="toggle_privileges('$fname', 'schedule-deliver-invite', 'schedule-deliver-reply', 'schedule-query-freebusy' );">
<input type="button" value="$btn_ss" class="submit" title="$btn_ss_title"
 onclick="toggle_privileges('$fname', 'schedule-send-invite', 'schedule-send-reply', 'schedule-send-freebusy' );">
<br>$privileges_set
EOTEMPLATE;

  return $html;
}


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

  $privs = bits_to_privilege($value,'*');
  $formatted = '';
  foreach( $privs AS $k => $v ) {
    $formatted .= ($formatted == '' ? '' : ', ');
    $v = preg_replace( '{^.*:}', '', $v );
    $formatted .= (isset($privilege_xlate[$v]) ? $privilege_xlate[$v] : $v );
  }
  return $formatted;
}


function confirm_delete_principal($confirmation_hash, $displayname ) { 
  $html = '<p class="error">';
  $html .= sprintf('<b>%s</b> \'%s\' <a class="error" href="%s&%s">%s</a> %s',
       translate('Deleting Principal:'), $displayname, $_SERVER['REQUEST_URI'],
        $confirmation_hash, translate('Confirm Deletion of the Principal'),
        translate('All of the principal\'s calendars and events will be unrecoverably deleted.') );
  $html .= "</p>\n";
  return $html;
}


    
function group_memberships_browser() {
  global $c, $id, $editor;
  $browser = new Browser(translate('Group Memberships'));

  $browser->AddColumn( 'group_id', translate('ID'), 'right', '##principal_link##' );
  $rowurl = $c->base_url . '/admin.php?action=edit&t=principal&id=';
  $browser->AddHidden( 'principal_link', "'<a href=\"$rowurl' || principal_id || '\">' || principal_id || '</a>'" );
  $browser->AddColumn( 'displayname', translate('Display Name') );
  $browser->AddColumn( 'member_of', translate('Is Member of'), '', '', 'is_member_of_list(principal_id)' );
  $browser->AddColumn( 'members', translate('Has Members'), '', '', 'has_members_list(principal_id)' );

  $browser->SetOrdering( 'displayname', 'A' );

  $browser->SetJoins( "group_member LEFT JOIN dav_principal ON (group_id = principal_id) " );
  $browser->SetWhere( 'user_active AND member_id = '.$id );

  if ( $c->enable_row_linking ) {
    $browser->RowFormat( '<tr onMouseover="LinkHref(this,1);" title="'.translate('Click to edit principal details').'" class="r%d">', '</tr>', '#even' );
  }
  else {
    $browser->RowFormat( '<tr class="r%d">', '</tr>', '#even' );
  }
  $browser->DoQuery();
  return $browser;
}


function group_row_editor() {
  global $c, $id, $editor, $can_write_principal;
  $grouprow = new Editor("Group Members", "group_member");
  $grouprow->SetLookup( 'member_id', 'SELECT principal_id, coalesce(displayname,fullname,username) FROM dav_principal WHERE principal_id NOT IN (SELECT member_id FROM group_member WHERE group_id = '.$id.') AND principal_id != '.$id);
  $grouprow->SetSubmitName( 'savegrouprow' );

  if ( $can_write_principal ) {
    if ( $grouprow->IsSubmit() ) {
      if ( $grouprow->IsUpdate() )
        $c->messages[] = translate('Updating Member of this Group Principal');
      else
        $c->messages[] = translate('Adding new member to this Group Principal');

      $_POST['group_id'] = $id;
      $member_id = intval($_POST['member_id']);
      $grouprow->SetWhere( 'group_id='.$id.' AND member_id='.$member_id);
      $grouprow->Write( );
      unset($_GET['member_id']);
    }
    elseif ( isset($_GET['delete_member']) ) {
      $qry = new AwlQuery('DELETE FROM group_member WHERE group_id=:group_id AND member_id = :member_id',
                            array( ':group_id' => $id, ':member_id' => intval($_GET['delete_member']) ));
      $qry->Exec('principal-edit');
      $c->messages[] = translate('Member deleted from this Group Principal');
    }
  }
  return $grouprow;
}


function edit_group_row( $row_data ) {
  global $id, $grouprow;

  $form_url = preg_replace( '#&(edit|delete)_group=\d+#', '', $_SERVER['REQUEST_URI'] );

  $template = <<<EOTEMPLATE
<form method="POST" enctype="multipart/form-data" id="add_group" action="$form_url">
  <td class="left"><input type="hidden" name="id" value="$id"></td>
  <td class="left" colspan="3">##member_id.select## &nbsp; ##Add.submit##</td>
  <td class="center"></td>
</form>

EOTEMPLATE;

  $grouprow->SetTemplate( $template );
  $grouprow->Title("");
  if ( $row_data->group_id > -1 ) $grouprow->SetRecord( $row_data );

  return $grouprow->Render();
}

function group_members_browser() {
  global $c, $id, $editor, $can_write_principal;
  $browser = new Browser(translate('Group Members'));

  $browser->AddColumn( 'group_id', translate('ID'), 'right', '##principal_link##' );
  $rowurl = $c->base_url . '/admin.php?action=edit&t=principal&id=';
  $browser->AddHidden( 'principal_id' );
  $browser->AddHidden( 'principal_link', "'<a href=\"$rowurl' || principal_id || '\">' || principal_id || '</a>'" );
  $browser->AddColumn( 'displayname', translate('Display Name') );
  $browser->AddColumn( 'member_of', translate('Is Member of'), '', '', 'is_member_of_list(principal_id)' );
  $browser->AddColumn( 'members', translate('Has Members'), '', '', 'has_members_list(principal_id)' );

  if ( $can_write_principal ) {
    $del_link  = '<a href="'.$c->base_url.'/admin.php?action=edit&t=principal&id='.$id.'&delete_member=##principal_id##" class="submit">'.translate('Remove').'</a>';
    $browser->AddColumn( 'action', translate('Action'), 'center', '', "'$edit_link&nbsp;$del_link'" );
  }

  $browser->SetOrdering( 'displayname', 'A' );

  $browser->SetJoins( "group_member LEFT JOIN dav_principal ON (member_id = principal_id) " );
  $browser->SetWhere( 'user_active AND group_id = '.$id );

  if ( $c->enable_row_linking ) {
    $browser->RowFormat( '<tr onMouseover="LinkHref(this,1);" title="'.translate('Click to edit principal details').'" class="r%d">', '</tr>', '#even' );
  }
  else {
    $browser->RowFormat( '<tr class="r%d">', '</tr>', '#even' );
  }
  $browser->DoQuery();

  if ( $can_write_principal ) {
    $browser->RowFormat( '<tr class="r%d">', '</tr>', '#even' );
    $extra_row = array( 'group_id' => -1 );
    $browser->MatchedRow('group_id', -1, 'edit_group_row');
    $extra_row = (object) $extra_row;
    $browser->AddRow($extra_row);
  }
  return $browser;
}


function grant_row_editor() {
  global $c, $id, $editor, $can_write_principal, $privilege_names;

  $grantrow = new Editor("Grants", "grants");
  $grantrow->SetSubmitName( 'savegrantrow' );
  $edit_grant_clause = '';
  if ( isset($_GET['edit_grant']) ) {
    $edit_grant_clause = ' AND to_principal != '.intval($_GET['edit_grant']);
  }
  $grantrow->SetLookup( 'to_principal', 'SELECT principal_id, displayname FROM dav_principal WHERE principal_id NOT IN (SELECT to_principal FROM grants WHERE by_principal = '.$id.$edit_grant_clause.') ORDER BY fullname' );
  if ( $can_write_principal ) {
    if ( $grantrow->IsSubmit() ) {
      if ( $grantrow->IsUpdate() )
        $c->messages[] = translate('Updating grants by this Principal');
      else
        $c->messages[] = translate('Granting new privileges from this Principal');
      $_POST['by_principal'] = $id;
      $to_principal = intval($_POST['to_principal']);
      $orig_to_id =  intval($_POST['orig_to_id']);
      $grantrow->SetWhere( 'by_principal='.$id.' AND to_principal='.$orig_to_id);
      if ( isset($_POST['grant_privileges']) ) {
        $privilege_bitpos = array_flip($privilege_names);
        $priv_names = array_keys($_POST['grant_privileges']);
        $privs_dec = privilege_to_bits($priv_names);
        $_POST['privileges'] = sprintf('%024s',decbin($privs_dec));
        $grantrow->Assign('privileges', $privs_dec);
      }
      $grantrow->Write( );
      unset($_GET['to_principal']);
    }
    elseif ( isset($_GET['delete_grant']) ) {
      $qry = new AwlQuery("DELETE FROM grants WHERE by_principal=:grantor_id AND to_principal = :to_principal",
                            array( ':grantor_id' => $id, ':to_principal' => intval($_GET['delete_grant']) ));
      $qry->Exec('principal-edit');
      $c->messages[] = translate('Deleted a grant from this Principal');
    }
  }
  return $grantrow;
}


function edit_grant_row( $row_data ) {
  global $id, $grantrow;

  if ( $row_data->to_principal > -1 ) {
    $grantrow->Initialise( $row_data );
  }

  $privs_html = build_privileges_html( $grantrow, 'grant_privileges' );

  $orig_to_id = $row_data->to_principal;
  $form_id = $grantrow->Id();
  $form_url = preg_replace( '#&(edit|delete)_grant=\d+#', '', $_SERVER['REQUEST_URI'] );

  $template = <<<EOTEMPLATE
<form method="POST" enctype="multipart/form-data" id="form_$form_id" action="$form_url">
  <td class="left" colspan="2"><input type="hidden" name="id" value="$id"><input type="hidden" name="orig_to_id" value="$orig_to_id">##to_principal.select##</td>
  <td class="left" colspan="2">$privs_html</td>
  <td class="center">##submit##</td>
</form>

EOTEMPLATE;

  $grantrow->SetTemplate( $template );
  $grantrow->Title("");

  return $grantrow->Render();
}

    
function principal_grants_browser() {
  global $c, $id, $editor, $can_write_principal;
    $browser = new Browser(translate('Principal Grants'));

  $browser->AddColumn( 'to_principal', translate('To ID'), 'right', '##principal_link##' );
  $rowurl = $c->base_url . '/admin.php?action=edit&t=principal&id=';
  $browser->AddHidden( 'principal_link', "'<a href=\"$rowurl' || to_principal || '\">' || to_principal || '</a>'" );
  $browser->AddHidden( 'grant_privileges', 'privileges' );
  $browser->AddColumn( 'displayname', translate('Display Name') );
  $browser->AddColumn( 'privs', translate('Privileges'), '', '', 'privileges', '', '', 'privilege_format_function' );
  $browser->AddColumn( 'members', translate('Has Members'), '', '', 'has_members_list(principal_id)' );

  if ( $can_write_principal ) {
    $del_link  = '<a href="'.$c->base_url.'/admin.php?action=edit&t=principal&id='.$id.'&delete_grant=##to_principal##" class="submit">'.translate('Revoke').'</a>';
    $edit_link  = '<a href="'.$c->base_url.'/admin.php?action=edit&t=principal&id='.$id.'&edit_grant=##to_principal##" class="submit">'.translate('Edit').'</a>';
    $browser->AddColumn( 'action', translate('Action'), 'center', '', "'$edit_link&nbsp;$del_link'" );
  }

  $browser->SetOrdering( 'displayname', 'A' );

  $browser->SetJoins( "grants LEFT JOIN dav_principal ON (to_principal = principal_id) " );
  $browser->SetWhere( 'by_principal = '.$id );

  if ( $c->enable_row_linking ) {
    $browser->RowFormat( '<tr onMouseover="LinkHref(this,1);" title="'.translate('Click to edit principal details').'" class="r%d">', '</tr>', '#even' );
  }
  else {
    $browser->RowFormat( '<tr class="r%d">', '</tr>', '#even' );
  }
  $browser->DoQuery();


  if ( $can_write_principal ) {
    if ( isset($_GET['edit_grant']) ) {
      $browser->MatchedRow('to_principal', $_GET['edit_grant'], 'edit_grant_row');
    }
    else if ( isset($id ) ) {
      $browser->RowFormat( '<tr class="r%d">', '</tr>', '#even' );
      $extra_row = array( 'to_principal' => -1 );
      $browser->MatchedRow('to_principal', -1, 'edit_grant_row');
      $extra_row = (object) $extra_row;
      $browser->AddRow($extra_row);
    }
  }
  return $browser;
}


function ticket_row_editor() {
  global $c, $id, $editor, $can_write_principal, $privilege_names;

  $ticketrow = new Editor("Tickets", "access_ticket");
  $ticketrow->SetSubmitName( 'ticketrow' );
  dbg_error_log( "ERROR", "Creating ticketrow editor: %s - %s", $can_write_principal, $ticketrow->IsSubmit());
  if ( $can_write_principal && $ticketrow->IsSubmit() ) {

    $username = $editor->Value('username');
    $ugly_path = $_POST['target'];
    if ( $ugly_path == '/'.$username || $ugly_path == '/'.$username.'/' ) {
      $target_collection = $id;
    }
    else { 
      $username_len = strlen($username) + 2;
      $sql = "SELECT collection_id FROM collection WHERE dav_name = :exact_name"; 
      $sql .= " AND substring(dav_name FROM 1 FOR $username_len) = '/$username/'";
      $params = array( ':exact_name' => $ugly_path );
      if ( !preg_match( '#/$#', $ugly_path ) ) {
        $sql .= " OR dav_name = :truncated_name OR dav_name = :trailing_slash_name";
        $params[':truncated_name'] = preg_replace( '#[^/]*$#', '', $ugly_path);
        $params[':trailing_slash_name'] = $ugly_path."/";
      }
      $sql .= " ORDER BY LENGTH(dav_name) DESC LIMIT 1";
      $qry = new AwlQuery( $sql, $params );
      if ( $qry->Exec() && $qry->rows() > 0 ) {
        $row = $qry->Fetch();
        $target_collection = $row->collection_id;
      }
      else {
        $c->messages[] = translate('Can only add tickets for existing collection paths which you own');
        return $ticketrow;
      }
    }
      
    $_POST['dav_owner_id'] = $id;
    $_POST['target_collection_id'] = $target_collection;
    $ticket_id = clean_by_regex($_POST['ticket_id'], '/[A-Za-z0-9]+/');
    $ticketrow->SetWhere( 'dav_owner_id='.$id.' AND ticket_id='.AwlQuery::quote($ticket_id));
    if ( isset($_POST['ticket_privileges']) ) {
      $privilege_bitpos = array_flip($privilege_names);
      $priv_names = array_keys($_POST['ticket_privileges']);
      $privs_dec = privilege_to_bits($priv_names);
      $_POST['privileges'] = sprintf('%024s',decbin($privs_dec));
      $ticketrow->Assign('privileges', $privs_dec);
    }
    $c->messages[] = translate('Creating new ticket granting privileges to this Principal');
    $ticketrow->Write( );
  }
  return $ticketrow;
}


function edit_ticket_row( $row_data ) {
  global $id, $ticketrow;

  if ( isset($row_data->ticket_id) ) {
    $ticketrow->Initialise( $row_data );
  }

  $privs_html = build_privileges_html( $ticketrow, 'ticket_privileges' );

  $form_id = $ticketrow->Id();
  $ticket_id = $row_data->ticket_id;
  $form_url = preg_replace( '#&(edit|delete)_[a-z]+=\d+#', '', $_SERVER['REQUEST_URI'] );

  $template = <<<EOTEMPLATE
<form method="POST" enctype="multipart/form-data" id="form_$form_id" action="$form_url">
  <td class="left">$ticket_id<input type="hidden" name="id" value="$id"><input type="hidden" name="ticket_id" value="$ticket_id"></td>
  <td class="left"><input type="text" name="target" value="$row_data->target"></td>
  <td class="left"><input type="text" name="expires" value="$row_data->expires" size="10"></td>
  <td class="left">$privs_html</td>
  <td class="center">##submit##</td>
</form>

EOTEMPLATE;

  $ticketrow->SetTemplate( $template );
  $ticketrow->Title("");

  return $ticketrow->Render();
}


function access_ticket_browser() {
  global $c, $id, $editor, $can_write_principal;

  $browser = new Browser(translate('Access Tickets'));

  $browser->AddColumn( 'ticket_id', translate('Ticket ID'), '', '' );
  $browser->AddColumn( 'target', translate('Target'), '', '<td style="white-space:nowrap;">%s</td>', "COALESCE(d.dav_name,c.dav_name)" );
  $browser->AddColumn( 'expires', translate('Expires'), '', '', 'TO_CHAR(expires,\'YYYY-MM-DD HH:MI:SS\')');
  $browser->AddColumn( 'privs', translate('Privileges'), '', '', 'privileges', '', '', 'privilege_format_function' );
  $delurl = $c->base_url . '/admin.php?action=edit&t=principal&id='.$id.'&ticket_id=##URL:ticket_id##&subaction=delete_ticket';
  $browser->AddColumn( 'delete', translate('Action'), 'center', '', "'<a class=\"submit\" href=\"$delurl\">".translate('Delete')."</a>'" );

  $browser->SetOrdering( 'target', 'A' );

  $browser->SetJoins( 'access_ticket t LEFT JOIN collection c ON (target_collection_id=collection_id) LEFT JOIN caldav_data d ON (target_resource_id=dav_id)' );
  $browser->SetWhere( 'dav_owner_id = '.intval($editor->Value('principal_id')) );

  $browser->RowFormat( '<tr class="r%d">', '</tr>', '#even' );

  $browser->DoQuery();

  if ( $can_write_principal ) {
    $ticket_id = substr( str_replace('/', '', str_replace('+', '',base64_encode(sha1(date('r') .rand(0,2100000000) . microtime(true),true)))), 7, 8);
    $extra_row = array( 'ticket_id' => $ticket_id,
                        'expires' => date( 'Y-m-d', time() + (86400 * 31) ),
                        'target' => '/'.$editor->Value('username').'/home/'
                      );
    $browser->MatchedRow('ticket_id', $ticket_id, 'edit_ticket_row');
    $browser->AddRow($extra_row);
  }
  return $browser;
}

  
function confirm_delete_ticket($confirmation_hash) {
  $html = '<table><tr><td class="error">';
  $html .= sprintf('<b>%s</b> "%s" <a class="error" href="%s&%s">%s</a> %s',
              translate('Deleting Ticket:'), $_GET['ticket_id'], $_SERVER['REQUEST_URI'],
              $confirmation_hash,
              translate('Confirm Deletion of the Ticket'),
              translate('The access ticket will be deleted.') );
  $html .= "</td></tr></table>\n";
  return $html;
}


function principal_collection_browser() {
  global $c, $page_elements, $id, $editor;

  $browser = new Browser(translate('Principal Collections'));

  $browser->AddColumn( 'collection_id', translate('ID'), 'right', '##collection_link##' );
  $rowurl = $c->base_url . '/admin.php?action=edit&t=collection&id=';
  $browser->AddHidden( 'collection_link', "'<a href=\"$rowurl' || collection_id || '\">' || collection_id || '</a>'" );
  $browser->AddColumn( 'dav_name', translate('Path') );
  $browser->AddColumn( 'dav_displayname', translate('Display Name') );
  $browser->AddColumn( 'publicly_readable', translate('Public'), 'centre', '', 'CASE WHEN publicly_readable THEN \''.translate('Yes').'\' ELSE \''.translate('No').'\' END' );
  $browser->AddColumn( 'privs', translate('Privileges'), '', '',
          "COALESCE( privileges_list(default_privileges), '[".translate('from principal')."]')" );
  $delurl = $c->base_url . '/admin.php?action=edit&t=principal&id='.$id.'&dav_name=##URL:dav_name##&subaction=delete_collection';
  $browser->AddColumn( 'delete', translate('Action'), 'center', '', "'<a class=\"submit\" href=\"$delurl\">".translate('Delete')."</a>'" );

  $browser->SetOrdering( 'dav_name', 'A' );

  $browser->SetJoins( "collection " );
  $browser->SetWhere( 'user_no = '.intval($editor->Value('user_no')) );

  $browser->AddRow( array( 'dav_name' => '<a href="'.$rowurl.'&user_no='.intval($editor->Value('user_no')).'" class="submit">'.translate('Create Collection').'</a>' ));

  if ( $c->enable_row_linking ) {
    $browser->RowFormat( '<tr onMouseover="LinkHref(this,1);" title="'.translate('Click to edit principal details').'" class="r%d">', '</tr>', '#even' );
  }
  else {
    $browser->RowFormat( '<tr class="r%d">', '</tr>', '#even' );
  }
  $browser->DoQuery();
  return $browser;
}
  
function confirm_delete_collection($confirmation_hash) {
  $html = '<table><tr><td class="error">';
  $html .= sprintf('<b>%s</b> "%s" <a class="error" href="%s&%s">%s</a> %s',
              translate('Deleting Collection:'), $_GET['dav_name'], $_SERVER['REQUEST_URI'],
              $confirmation_hash,
              translate('Confirm Deletion of the Collection'),
              translate('All collection data will be unrecoverably deleted.') );
  $html .= "</td></tr></table>\n";
  return $html;
}

function bindings_to_other_browser() {
  global $c, $page_elements, $id, $editor;
  $browser = new Browser(translate('Bindings to other collections'));
  $browser->AddColumn( 'bind_id', translate('ID'), '', '' );
  $browser->AddHidden( 'b.dav_owner_id' );
  $browser->AddHidden( 'p.principal_id' );
  $browser->AddColumn( 'bound_as', translate('Bound As'), '', '<td style="white-space:nowrap;">%s</td>', 'b.dav_name' );
  $browser->AddColumn( 'dav_name', translate('To Collection'), '', '<td style="white-space:nowrap;">%s</td>', 'c.dav_name' );
  $browser->AddColumn( 'access_ticket_id', translate('Ticket ID'), '', '' );
  $browser->AddColumn( 'privs', translate('Privileges'), '', '', "privileges_list(privileges)" );
  $delurl = $c->base_url . sprintf('/admin.php?action=edit&t=principal&id=%s&bind_id=##bind_id##&subaction=delete_bind_in', $editor->Value('principal_id'));
  $browser->AddColumn( 'delete', translate('Action'), 'center', '', "'<a class=\"submit\" href=\"$delurl\">".translate('Delete')."</a>'" );

  $browser->SetOrdering( 'target', 'A' );

  $browser->SetJoins( 'dav_binding b LEFT JOIN collection c ON (bound_source_id=collection_id) LEFT JOIN access_ticket t ON (ticket_id=access_ticket_id) LEFT JOIN principal p USING(user_no)' );
  $browser->SetWhere( 'b.dav_name ~ '.sprintf("'^/%s/'", $editor->Value('username')) );

  $browser->RowFormat( '<tr class="r%d">', '</tr>', '#even' );

  $browser->DoQuery();
  return $browser;
}
  
function confirm_delete_bind_in($confirmation_hash) {
  $html = '<table><tr><td class="error">';
  $html .= sprintf('<b>%s</b> "%s" <a class="error" href="%s&%s">%s</a> %s',
              translate('Deleting Binding:'), $_GET['bind_id'], $_SERVER['REQUEST_URI'],
              $confirmation_hash,
              translate('Confirm Deletion of the Binding'),
              translate('The binding will be deleted.') );
  $html .= "</td></tr></table>\n";
  return $html;
}
  
  
function bindings_to_us_browser() {
  global $c, $page_elements, $id, $editor;
  $browser = new Browser(translate('Bindings to this Principal\'s Collections'));
  $browser->AddColumn( 'bind_id', translate('ID'), '', '' );
  $browser->AddHidden( 'b.dav_owner_id' );
  $browser->AddHidden( 'p.principal_id' );
  $browser->AddColumn( 'dav_name', translate('Collection'), '', '<td style="white-space:nowrap;">%s</td>', 'c.dav_name' );
  $browser->AddColumn( 'bound_as', translate('Bound As'), '', '<td style="white-space:nowrap;">%s</td>', 'b.dav_name' );
  $browser->AddColumn( 'access_ticket_id', translate('Ticket ID'), '', '' );
  $browser->AddColumn( 'privs', translate('Privileges'), '', '', "privileges_list(privileges)" );
  $delurl = $c->base_url . '/admin.php?action=edit&t=principal&id=##principal_id##&bind_id=##bind_id##&subaction=delete_binding';
  $browser->AddColumn( 'delete', translate('Action'), 'center', '', "'<a class=\"submit\" href=\"$delurl\">".translate('Delete')."</a>'" );

  $browser->SetOrdering( 'target', 'A' );

  $browser->SetJoins( 'dav_binding b LEFT JOIN collection c ON (bound_source_id=collection_id) LEFT JOIN access_ticket t ON (ticket_id=access_ticket_id) LEFT JOIN principal p USING(user_no)' );
  $browser->SetWhere( 'p.principal_id = '.intval($editor->Value('principal_id')) );

  $browser->RowFormat( '<tr class="r%d">', '</tr>', '#even' );

  $browser->DoQuery();
  return $browser;
}
  
function confirm_delete_binding( $confirmation_hash ) {
  $html = '<table><tr><td class="error">';
  $html .= sprintf('<b>%s</b> "%s" <a class="error" href="%s&%s">%s</a> %s',
              translate('Deleting Binding:'), $_GET['bind_id'], $_SERVER['REQUEST_URI'],
              $confirmation_hash,
              translate('Confirm Deletion of the Binding'),
              translate('The binding will be deleted.') );
  $html .= "</td></tr></table>\n";
  return $html;
}


if ( isset($_GET['subaction']) ) {
  if ( handle_subaction($_GET['subaction']) && 'delete_principal' == $_GET['subaction'] ) {
    return true;    
  }
}


$can_write_principal = ($session->AllowedTo('Admin') || $session->principal_id == $id );

$editor = principal_editor();
$page_elements[] = $editor;

if ( isset($id) && $id > 0 ) {
  $c->stylesheets[] = 'css/browse.css';
  $c->scripts[] = 'js/browse.js';

  if ( isset($delete_principal_confirmation_required) )
    $page_elements[] = confirm_delete_principal($delete_principal_confirmation_required, $editor->Value('displayname'));
  
  
  $page_elements[] = group_memberships_browser();
  if ( $editor->Value('type_id') == 3 ) {
    $grouprow = group_row_editor();
    $page_elements[] = group_members_browser();
  }
  $grantrow = grant_row_editor();
  $page_elements[] = principal_grants_browser();
  if ( isset($delete_grant_confirmation_required) ) $page_elements[] = confirm_delete_grant($delete_grant_confirmation_required);  
  
  $ticketrow = ticket_row_editor();
  $page_elements[] = access_ticket_browser();
  if ( isset($delete_ticket_confirmation_required) ) $page_elements[] = confirm_delete_ticket($delete_ticket_confirmation_required);  

  $page_elements[] = principal_collection_browser();
  if ( isset($delete_collection_confirmation_required) ) $page_elements[] = confirm_delete_collection($delete_collection_confirmation_required);  
  
  $page_elements[] = bindings_to_other_browser();
  if ( isset($delete_bind_in_confirmation_required) ) $page_elements[] = confirm_delete_bind_in($delete_bind_in_confirmation_required);  

  $page_elements[] = bindings_to_us_browser();
  if ( isset($delete_binding_confirmation_required) ) $page_elements[] = confirm_delete_binding($delete_binding_confirmation_required);
}
