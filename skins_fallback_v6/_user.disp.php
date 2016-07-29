<?php
/**
 * This is the template that displays the user profile page.
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
* @var Blog
*/
global $Blog;
/**
 * @var GeneralSettings
 */
global $Settings;
/**
 * @var Current User
 */
global $current_User;

// init is logged in status
$is_logged_in = is_logged_in();

// Default params:
$params = array_merge( array(
		'edit_my_profile_link_text'        => T_('Edit my profile'),
		'edit_user_admin_link_text'        => T_('Edit in Back-Office'),
		'skin_form_params'                 => array(),
	), $params );

// ------------------- PREV/NEXT USER LINKS (SINGLE USER MODE) -------------------
user_prevnext_links();
// ------------------------- END OF PREV/NEXT USER LINKS -------------------------


// ---- START OF PROFILE CONTENT ---- //
echo '<div class="profile_content">';

$user_ID = param( 'user_ID', 'integer', '' );
if( empty($user_ID) )
{	// Grab the current User
	$user_ID = $current_User->ID;
}

$UserCache = & get_UserCache();
/**
 * @var User
 */
$User = & $UserCache->get_by_ID( $user_ID );

$profileForm = new Form( NULL, '', 'post', NULL, '', 'div' );

$profileForm->switch_template_parts( $params['skin_form_params'] );

$profileForm->switch_layout( 'fixed', false );

$profileForm->begin_form( 'evo_form evo_form_user' );

// ---- START OF LEFT COLUMN ---- //
echo '<div class="profile_column_left">';

	// ------------------------- "User Profile - Left" CONTAINER EMBEDDED HERE --------------------------
	// Display container contents:
	skin_container( /* TRANS: Widget container name */ NT_('User Profile - Left'), array(
		'widget_context' => 'user',	// Signal that we are displaying within an User
		// The following (optional) params will be used as defaults for widgets included in this container:
		// This will enclose each widget in a block:
		'block_start' => '<div class="$wi_class$">',
		'block_end' => '</div>',
		// This will enclose the title of each widget:
		'block_title_start' => '<h3>',
		'block_title_end' => '</h3>',
	) );
	// ----------------------------- END OF "User Profile - Left" CONTAINER -----------------------------

	// Buttons:
	$buttons = array();

	// - Edit my profile:
	if( $is_logged_in && $User->ID == $current_User->ID && ! empty( $params['edit_my_profile_link_text'] ) )
	{ // Display edit link profile for owner:
		$buttons[] = '<a href="'.url_add_param( $Blog->get( 'url' ), 'disp=profile' ).'">'
				.'<button type="button" class="btn btn-primary">'.$params['edit_my_profile_link_text'].'</button>'
			.'</a>';
	}

	// Check if current user can edit other users from back-office:
	$user_perms_edit = ( $is_logged_in &&
			$current_User->can_moderate_user( $User->ID ) &&
			$current_User->check_status( 'can_access_admin' ) &&
			$current_User->check_perm( 'admin', 'restricted' )
		);

	// - Message:
	if( ! $is_logged_in || $current_User->ID != $User->ID )
	{ // Display a message to send a button only for other users
		$msgform_url = $User->get_msgform_url( $Blog->get( 'msgformurl' ) );
		if( ! empty( $msgform_url ) )
		{
			$msgform_url = url_add_param( $msgform_url, 'msg_type=PM' );
			$buttons[] = '<a href="'.$msgform_url.'"><button type="button" class="btn '.( $user_perms_edit ? 'btn-default' : 'btn-primary' ).'">'.T_('Send Message').'</button></a>';
		}
	}

	// - Contact:
	if( $is_logged_in && ( $current_User->ID != $User->ID ) &&
			$current_User->check_perm( 'perm_messaging', 'reply' ) &&
			$current_User->check_status( 'can_edit_contacts' ) )
	{ // User is logged in, has messaging access permission and is not the same user as displayed user
		$is_contact = check_contact( $User->ID );
		if( $is_contact === NULL )
		{ // User is not in current User contact list, so allow "Add to my contacts" action
			$buttons[] = '<button type="button" class="btn btn-default" onclick="return user_contact_groups( '.$User->ID.' )">'.T_('Add to Contacts').'</button>';
		}
		elseif( $is_contact === false )
		{ // User is blocked
			$buttons[] = '<button type="button" class="btn btn-danger" onclick="return user_contact_groups( '.$User->ID.' )">'.T_('Edit Blocked Contact').'</button>';
		}
		else
		{ // User is on current User contact list
			$buttons[] = '<button type="button" class="btn btn-success" onclick="return user_contact_groups( '.$User->ID.' )">'.T_('Edit Contact').'</button>';
		}

		$buttons['group'] = array();
		$contact_block_url = get_samedomain_htsrv_url().'action.php?mname=messaging&amp;disp=contacts&amp;user_ID='.$user_ID.'&amp;redirect_to='.rawurlencode( regenerate_url() ).'&amp;'.url_crumb( 'messaging_contacts' );
		if( $is_contact === NULL || $is_contact === true )
		{ // Display a button to block user
			$buttons['group'][] = '<a href="'.$contact_block_url.'&action=block" class="btn btn-warning">'
					.'<button type="button">'.T_('Block Contact').'</button>'
				.'</a>';
		}
		else
		{ // Display a button to unblock user
			$buttons['group'][] = '<a href="'.$contact_block_url.'&action=unblock" class="btn btn-danger">'
					.'<button type="button">'.T_('Unblock Contact').'</button>'
				.'</a>';
		}
	}

	// - Report:
	if( $is_logged_in && ( $current_User->ID != $User->ID ) &&
			$current_User->check_status( 'can_report_user' ) )
	{ // Current user must be logged in, cannot report own account, and must has a permission to report
		if( ! isset( $buttons['group'] ) )
		{
			$buttons['group'] = array();
		}
		// get current User report from edited User
		$current_report = get_report_from( $User->ID );
		if( $current_report == NULL )
		{ // current User didn't add any report from this user yet
			$buttons['group'][] = '<button type="button" class="btn btn-warning" onclick="return user_report( '.$User->ID.' )">'.T_('Report User').'</button>';
		}
		else
		{
			$buttons['group'][] = '<button type="button" class="btn btn-danger" onclick="return user_report( '.$User->ID.' )">'.T_('Edit Reported User').'</button>';
		}
	}

	if( $user_perms_edit )
	{ // Current user can edit other user's profiles
		global $admin_url;

		// - Edit in back-office:
		$buttons[] = '<a href="'.url_add_param( $admin_url, 'ctrl=user&amp;user_ID='.$User->ID ).'">'
				.'<button type="button" class="btn btn-primary">'.$params['edit_user_admin_link_text'].'</button>'
			.'</a>';

		if( $current_User->ID != $User->ID && $current_User->check_perm( 'users', 'edit' ) )
		{ // - Delete in back-office:
			$buttons['del'] = array();
			$buttons['del'][] = '<a href="'.url_add_param( $admin_url, 'ctrl=users&amp;action=delete&amp;user_ID='.$User->ID.'&amp;'.url_crumb( 'user' ) ).'" class="btn btn-danger">'
					.'<button type="button">'.T_('Delete').'</button>'
				.'</a>';
			$buttons['del'][] = '<a href="'.url_add_param( $admin_url, 'ctrl=users&amp;action=delete&amp;deltype=spammer&amp;user_ID='.$User->ID.'&amp;'.url_crumb( 'user' ) ).'" class="btn btn-danger">'
					.'<button type="button">'.T_('Delete Spammer').'</button>'
				.'</a>';
		}
	}

	if( count( $buttons ) )
	{ // Dispaly all available buttons
		echo '<div class="profile_buttons">';
		foreach( $buttons as $button )
		{
			if( is_array( $button ) )
			{ // The grouped buttons
				echo '<p'.( count( $button ) > 1 ? ' class="btn-group' : '' ).'">'.implode( "\n", $button ).'</p>';
			}
			else
			{ // Single button
				echo '<p>'.$button.'</p>';
			}
		}
		echo '</div>';
	}

	// Organizations:
	$user_organizations = $User->get_organizations();
	$count_organizations = count( $user_organizations );
	if( $count_organizations > 0 )
	{
		$org_names = array();
		foreach( $user_organizations as $org )
		{
			if( empty( $org->url ) )
			{ // Display just a text
				$org_names[] = $org->name;
			}
			else
			{ // Make a link for organization
				$org_names[] = '<a href="'.$org->url.'" rel="nofollow" target="_blank">'.$org->name.'</a>';
			}
		}
		echo '<hr class="profile_separator" />'."\n";
		echo '<p><b>'.T_('Organizations').':</b></p>';
		echo '<p>'.implode( ' &middot; ', $org_names ).'</p>';
	}

	echo '</p>';

echo '</div>';
// ---- END OF LEFT COLUMN ---- //

// ---- START OF RIGHT COLUMN ---- //
echo '<div class="profile_column_right">';

	// ------------------------- "User Profile - Right" CONTAINER EMBEDDED HERE --------------------------
	// Display container contents:
	skin_container( /* TRANS: Widget container name */ NT_('User Profile - Right'), array(
		'widget_context' => 'user',	// Signal that we are displaying within an User
		// The following (optional) params will be used as defaults for widgets included in this container:
		// This will enclose each widget in a block:
		'block_start' => '<div class="$wi_class$">',
		'block_end' => '</div>',
		// This will enclose the title of each widget:
		'block_title_start' => '<h3>',
		'block_title_end' => '</h3>',
	) );
	// ----------------------------- END OF "User Profile - Right" CONTAINER -----------------------------

	if( $is_logged_in && $current_User->check_status( 'can_view_user', $user_ID ) )
	{ // Display other pictures, but only for logged in and activated users:
		$user_avatars = $User->get_avatar_Links();
		if( count( $user_avatars ) > 0 )
		{
			echo '<div class="other_profile_pictures">';
			foreach( $user_avatars as $uLink )
			{
				echo $uLink->get_tag( array(
					'before_image'        => '<div class="avatartag">',
					'before_image_legend' => NULL,
					'after_image_legend'  => NULL,
					'image_size'          => 'crop-top-80x80',
					'image_link_to'       => 'original',
					'image_link_title'    => $User->login,
					'image_link_rel'      => 'lightbox[user]'
				) );
			}
			echo '</div>';
		}
	}

	// Load the user fields:
	$User->userfields_load();

	// fp> TODO: have some clean iteration support
	$group_ID = 0;
	foreach( $User->userfields as $userfield )
	{
		if( $group_ID != $userfield->ufgp_ID )
		{ // Start new group
			if( $group_ID > 0 )
			{ // End previous group
				$profileForm->end_fieldset();
			}
			$profileForm->begin_fieldset( $userfield->ufgp_name, array( 'id' => 'fieldset_user_fields' ) );
		}

		if( $userfield->ufdf_type == 'text' )
		{ // convert textarea values
			$userfield->uf_varchar = nl2br( $userfield->uf_varchar );
		}

		$userfield_icon = '';
		if( ! empty( $userfield->ufdf_icon_name ) )
		{ // Icon
			$userfield_icon = '<span class="'.$userfield->ufdf_icon_name.' ufld_'.$userfield->ufdf_code.' ufld__textcolor"></span> ';
		}

		$profileForm->info( $userfield_icon.$userfield->ufdf_name, $userfield->uf_varchar );

		$group_ID = $userfield->ufgp_ID;
	}
	if( $group_ID > 0 )
	{	// End fieldset if userfields are exist
		$profileForm->end_fieldset();
	}

	$profileForm->begin_fieldset( T_( 'Reputation' ) );

		$profileForm->info( T_('Number of posts'), $User->get_reputation_posts() );

		$profileForm->info( T_('Comments'), '<span class="reputation_message">'.$User->get_reputation_comments().'</span>' );

		$profileForm->info( T_('Photos'), '<span class="reputation_message">'.$User->get_reputation_files( array( 'file_type' => 'image' ) ).'</span>' );

		$profileForm->info( T_('Audio'), '<span class="reputation_message">'.$User->get_reputation_files( array( 'file_type' => 'audio' ) ).'</span>' );

		$profileForm->info( T_('Other files'), '<span class="reputation_message">'.$User->get_reputation_files( array( 'file_type' => 'other' ) ).'</span>' );

		$profileForm->info( T_('Spam fighter score'), '<span class="reputation_message">'.$User->get_reputation_spam().'</span>' );

	$profileForm->end_fieldset();

	$Plugins->trigger_event( 'DisplayProfileFormFieldset', array( 'Form' => & $profileForm, 'User' => & $User, 'edit_layout' => 'public' ) );

echo '</div>';
// ---- END OF RIGHT COLUMN ---- //

echo '<div class="clear"></div>';

// ---- END OF PROFILE CONTENT ---- //
echo '</div>'; // .profile_content


$profileForm->end_form();

// Init JS for user reporting
echo_user_report_window();
// Init JS for user contact editing
echo_user_contact_groups_window();
?>