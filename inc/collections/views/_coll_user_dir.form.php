<?php
/**
 * This file implements the UI view for the Collection features user directory properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;


$Form = new Form( NULL, 'coll_other_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'userdir' );
$Form->hidden( 'blog', $edited_Blog->ID );

$Form->begin_fieldset( T_('User directory').get_manual_link( 'user-directory-other' ) );

	$Form->checkbox( 'userdir_enable', $edited_Blog->get_setting( 'userdir_enable' ), T_('Enable User directory') );

	$Form->checklist( array(
			array( 'userdir_filter_restrict_to_members', 1, T_('Restrict to members'), $edited_Blog->get_setting( 'userdir_filter_restrict_to_members' ) ),
			array( 'userdir_filter_name', 1, T_('Name').' / '.T_('Username'), $edited_Blog->get_setting( 'userdir_filter_name' ) ),
			array( 'userdir_filter_name_email', 1, T_('Name').' / '.T_('Username').' / '.T_('Email'), $edited_Blog->get_setting( 'userdir_filter_name_email' ) ),
			array( 'userdir_filter_firstname', 1, T_('First name'), $edited_Blog->get_setting( 'userdir_filter_firstname' ) ),
			array( 'userdir_filter_lastname', 1, T_('Last name'), $edited_Blog->get_setting( 'userdir_filter_lastname' ) ),
			array( 'userdir_filter_nickname', 1, T_('Nickname'), $edited_Blog->get_setting( 'userdir_filter_nickname' ) ),
			array( 'userdir_filter_email', 1, T_('Email'), $edited_Blog->get_setting( 'userdir_filter_email' ) ),
			array( 'userdir_filter_country', 1, T_('Country'), $edited_Blog->get_setting( 'userdir_filter_country' ) ),
			array( 'userdir_filter_region', 1, T_('Region'), $edited_Blog->get_setting( 'userdir_filter_region' ) ),
			array( 'userdir_filter_subregion', 1, T_('Subregion'), $edited_Blog->get_setting( 'userdir_filter_subregion' ) ),
			array( 'userdir_filter_city', 1, T_('City'), $edited_Blog->get_setting( 'userdir_filter_city' ) ),
			array( 'userdir_filter_age_group', 1, T_('Age group'), $edited_Blog->get_setting( 'userdir_filter_age_group' ) ),
			array( 'userdir_filter_gender', 1, T_('Gender'), $edited_Blog->get_setting( 'userdir_filter_gender' ) ),
			array( 'userdir_filter_level', 1, T_('User level'), $edited_Blog->get_setting( 'userdir_filter_level' ) ),
			array( 'userdir_filter_org', 1, T_('Organization'), $edited_Blog->get_setting( 'userdir_filter_org' ) ),
			array( 'userdir_filter_criteria', 1, T_('Specific Criteria'), $edited_Blog->get_setting( 'userdir_filter_criteria' ) ),
			array( 'userdir_filter_lastseen', 1, T_('User last seen'), $edited_Blog->get_setting( 'userdir_filter_lastseen' ) ),
		), 'userdir_filters', T_('Enabled filters') );

if( isset( $GLOBALS['files_Module'] ) )
{
	load_funcs( 'files/model/_image.funcs.php' );

	$Form->begin_line( T_('Profile picture'), 'userdir_picture' );
		$Form->checkbox( 'userdir_picture', $edited_Blog->get_setting( 'userdir_picture' ), '' );
		$Form->select_input_array( 'image_size_user_list', $edited_Blog->get_setting( 'image_size_user_list' ), get_available_thumb_sizes(), '', '', array( 'force_keys_as_values' => true ) );
	$Form->end_line();
}

$Form->checkbox( 'userdir_login', $edited_Blog->get_setting( 'userdir_login' ), /* TRANS: noun */ T_('Login') );
$Form->checkbox( 'userdir_firstname', $edited_Blog->get_setting( 'userdir_firstname' ), T_('First name') );
$Form->checkbox( 'userdir_lastname', $edited_Blog->get_setting( 'userdir_lastname' ), T_('Last name') );
$Form->checkbox( 'userdir_nickname', $edited_Blog->get_setting( 'userdir_nickname' ), T_('Nickname') );
$Form->checkbox( 'userdir_fullname', $edited_Blog->get_setting( 'userdir_fullname' ), T_('Full name') );

$Form->begin_line( T_('Country'), 'userdir_country' );
	$Form->checkbox( 'userdir_country', $edited_Blog->get_setting( 'userdir_country' ), '' );
	$Form->select_input_array( 'userdir_country_type', $edited_Blog->get_setting( 'userdir_country_type' ), array(
			'flag' => T_('Flag'),
			'name' => T_('Name'),
			'both' => T_('Both'),
		), '', '', array( 'force_keys_as_values' => true ) );
$Form->end_line();
$Form->checkbox( 'userdir_region', $edited_Blog->get_setting( 'userdir_region' ), T_('Region') );
$Form->checkbox( 'userdir_subregion', $edited_Blog->get_setting( 'userdir_subregion' ), T_('Sub-region') );
$Form->checkbox( 'userdir_city', $edited_Blog->get_setting( 'userdir_city' ), T_('City') );

$Form->checkbox( 'userdir_phone', $edited_Blog->get_setting( 'userdir_phone' ), T_('Phone') );
$Form->checkbox( 'userdir_soclinks', $edited_Blog->get_setting( 'userdir_soclinks' ), T_('Social links') );
$Form->begin_line( T_('Last seen date'), 'userdir_lastseen' );
	$Form->checkbox( 'userdir_lastseen', $edited_Blog->get_setting( 'userdir_lastseen' ), '' );
	$Form->select_input_array( 'userdir_lastseen_view', $edited_Blog->get_setting( 'userdir_lastseen_view' ), array(
			'exact_date' => T_('exact date'),
			'blurred_date' => T_('blurred date')
	), '', '', array( 'force_keys_as_values' => true ) );
	$Form->text_input( 'userdir_lastseen_cheat', $edited_Blog->Get_setting( 'userdir_lastseen_cheat' ), 4, T_('Cheat by'), 'days' );
$Form->end_line();

$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );
?>
<script>
	var selLastSeenView = jQuery( 'select#userdir_lastseen_view' );
	var selLastSeenCheat = jQuery( 'input#userdir_lastseen_cheat' );

	var checkLastSeen = function()
			{
				if( selLastSeenView.val() == 'blurred_date' )
				{
					selLastSeenCheat.removeAttr( 'disabled' );
				}
				else
				{
					selLastSeenCheat.attr( 'disabled', 'disabled' );
				}
			};

	selLastSeenView.on( 'change', checkLastSeen );

	checkLastSeen();
</script>