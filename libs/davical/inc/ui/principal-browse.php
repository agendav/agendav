<?php
param_to_global( 'principal_type', 'int', 'type' );
param_to_global( 'principal_active', '([tf])', 'active' );

$browser = new Browser(translate('Calendar Principals'));
if ( isset($principal_type) ) {
  switch($principal_type) {
    case 1:  $browser->Title(translate('User Calendar Principals'));      break;
    case 2:  $browser->Title(translate('Resource Calendar Principals'));  break;
    case 3:  $browser->Title(translate('Group Principals'));              break;
  }
}

$browser->AddColumn( 'principal_id', translate('ID'), 'right', '##principal_link##' );
$browser->AddColumn( 'username', translate('Name') );
$rowurl = $c->base_url . '/admin.php?action=edit&t=principal&id=';
$browser->AddHidden( 'principal_link', "'<a href=\"$rowurl' || principal_id || '\">' || principal_id || '</a>'" );
$browser->AddColumn( 'displayname', translate('Display Name') );
$browser->AddColumn( 'email', translate('EMail') );
$browser->AddColumn( 'member_of', translate('Is Member of'), '', '', 'is_member_of_list(principal_id)' );

if ( !isset($principal_type) || $principal_type == 3 ) {
  $browser->AddColumn( 'members', translate('Has Members'), '', '', 'has_members_list(principal_id)' );
}

$browser->SetOrdering( 'username', 'A' );
$browser->SetJoins( "dav_principal " );

if ( isset($principal_active) && $principal_active == 'f' )
  $browser->SetWhere( 'NOT user_active' );
else
  $browser->SetWhere( 'user_active' );

if ( isset($principal_type) ) {
  $browser->AndWhere( 'type_id = '.$principal_type );
}


$c->page_title = $browser->Title();

if ( $c->enable_row_linking ) {
  $browser->RowFormat( '<tr onMouseover="LinkHref(this,1);" title="'.htmlspecialchars(translate('Click to display user details')).'" class="r%d">', '</tr>', '#even' );
}
else {
  $browser->RowFormat( '<tr class="r%d">', '</tr>', '#even' );
}

$page_elements[] = $browser;

