<?php
/**
 * This file implements the Form for the all blogs settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $current_User;
/**
 * @var GeneralSettings
 */
global $Settings;

$Form = new Form( NULL, 'settings_checkchanges' );
$Form->begin_form( 'fform', '',
	// enable all form elements on submit (so values get sent):
	array( 'onsubmit'=>'var es=this.elements; for( var i=0; i < es.length; i++ ) { es[i].disabled=false; };' ) );

$Form->add_crumb( 'collectionsettings' );
$Form->hidden( 'ctrl', 'collections' );
$Form->hidden( 'tab', 'blog_settings' );
$Form->hidden( 'action', 'update_settings_blog' );

// --------------------------------------------

	$Form->begin_fieldset( T_('Display options').get_manual_link('collections-display-options') );

		$Form->select_input_options( 'blogs_order_by', array_to_option_list( get_coll_sort_options(), $Settings->get('blogs_order_by') ), T_('Order blogs by'), T_('Select blog list order.') );

		$Form->select_input_options( 'blogs_order_dir', array_to_option_list(
				array( 'ASC' => T_('Ascending'), 'DESC' => T_('Descending') ), $Settings->get('blogs_order_dir') ), T_('Order direction'), T_('Select default blog list order direction.') );

	$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Caching').get_manual_link('collections-caching-settings') );

	$Form->checkbox_input( 'general_cache_enabled', $Settings->get('general_cache_enabled'), get_icon( 'page_cache_on' ).' '.T_('Enable general cache'), array( 'note'=>T_('Cache rendered pages that are not controlled by a skin. See Blog Settings for skin output caching.') ) );

	$cache_note = '('.T_( 'See Blog Settings for existing' ).')';
	$Form->checklist( array(
			array( 'newblog_cache_enabled', 1, T_( 'Enable page cache for NEW blogs' ), $Settings->get('newblog_cache_enabled'), false, $cache_note ),
			array( 'newblog_cache_enabled_widget', 1, T_( 'Enable widget cache for NEW blogs' ), $Settings->get('newblog_cache_enabled_widget'), false, $cache_note )
			), 'new_blogs_cahe', T_( 'Enable for new blogs' ) );

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('After each new post or comment...').get_manual_link('after-each-post-settings') );
	$Form->radio_input( 'outbound_notifications_mode', $Settings->get('outbound_notifications_mode'),
		array(
			array( 'value'=>'off', 'label'=>T_('Off'), 'note'=>T_('No notification about your new content will be sent out.') ),
			array( 'value'=>'immediate', 'label'=>T_('Immediate'), 'note'=>T_('This is guaranteed to work but may create an annoying delay after each post or comment publication.') ),
			array( 'value'=>'cron', 'label'=>T_('Asynchronous'), 'note'=>T_('Recommended if you have your scheduled jobs properly set up.') )
		),
		T_('Outbound pings & email notifications'),
		array( 'lines' => true ) );
$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Categories').get_manual_link('categories-global-settings'), array( 'id'=>'categories') );
	$Form->checkbox_input( 'allow_moving_chapters', $Settings->get('allow_moving_chapters'), T_('Allow moving categories'), array( 'note' => T_('Check to allow moving categories accross blogs. (Caution: can break pre-existing permalinks!)' ) ) );
$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Cross posting').get_manual_link('collections-cross-posting-settings') );
	$Form->checklist( array(
		array( 'cross_posting', 1, T_('Allow admins to cross-post to several collections'), $Settings->get('cross_posting'), false, T_('(Extra cats in different blogs)').get_admin_badge() ),
		array( 'cross_posting_blogs', 1, T_('Allow admins to move posts between collections'), $Settings->get('cross_posting_blogs'), false, T_('(Main cat can move to different blog)').get_admin_badge() ) ),
		'allow_cross_posting', T_('Cross posting') );

	$redirect_moved_posts_params = array( 'note' => T_('check to allow redirects to the correct Collection if the requested Item Slug was found in a different collection.') );
	if( $Settings->get( 'always_match_slug' ) )
	{
		$redirect_moved_posts_params['disabled'] = 'disabled';
	}
	$Form->checkbox_input( 'redirect_moved_posts', $Settings->get( 'redirect_moved_posts' ), T_('Redirect if post has moved'), $redirect_moved_posts_params );

	$Form->checkbox_input( 'always_match_slug', $Settings->get( 'always_match_slug' ), T_('Always try to match slug'), array( 'note' => T_('check to redirect to correct Collection if an Item Slug was found in <b>any</b> URL (including invalid URLs).') ) );

	$Form->checklist( array(
		array( 'redirect_tinyurl', 1, TB_('301 redirect to canonical URL'), $Settings->get( 'redirect_tinyurl' ) ),
		), 'tinyurl_options', TB_('Tiny URLs') );
$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Subscribing to new blogs').get_manual_link('collections-subscription-settings') );
	$Form->radio_input( 'subscribe_new_blogs', $Settings->get('subscribe_new_blogs'),
		array(
			array( 'value' => 'page', 'label' => T_('From blog page only') ),
			array( 'value' => 'public', 'label' => T_('Show a list of all <b>Public</b> blogs allowing subscriptions') ),
			array( 'value' => 'all', 'label' => T_('Show a list of <b>All</b> blogs allowing subsciptions') )
		),
		T_('Subscribing to new blogs'),
		array( 'lines' => true ) );
$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Default Skins for New Collections').get_manual_link( 'collections-default-skins' ) );
	$normal_skins = array();
	$mobile_skins = array( 0 => T_('Same as standard skin') );
	$tablet_skins = array( 0 => T_('Same as standard skin') );

	$SkinCache = & get_SkinCache();
	$SkinCache->load_all();
	$SkinCache->rewind();
	while( ( $iterator_Skin = & $SkinCache->get_next() ) != NULL )
	{
		switch( $iterator_Skin->get( 'type' ) )
		{
			case 'rwd':
			case 'normal':
				$normal_skins[ $iterator_Skin->ID ] = $iterator_Skin->get( 'name' );
				break;

			case 'rwd':
			case 'mobile':
				$mobile_skins[ $iterator_Skin->ID ] = $iterator_Skin->get( 'name' );
				break;

			case 'rwd':
			case 'tablet':
				$tablet_skins[ $iterator_Skin->ID ] = $iterator_Skin->get( 'name' );
				break;

			//default: It's not a skin whit a type what we should show in these select lists ( e.g. feed )
		}
	}
	$field_params = array( 'force_keys_as_values' => true );
	$Form->select_input_array( 'def_normal_skin_ID', $Settings->get( 'def_normal_skin_ID' ), $normal_skins, T_('Default standard skin'), NULL, $field_params );
	$Form->select_input_array( 'def_mobile_skin_ID', $Settings->get( 'def_mobile_skin_ID' ), $mobile_skins, T_('Default mobile phone skin'), NULL, $field_params );
	$Form->select_input_array( 'def_tablet_skin_ID', $Settings->get( 'def_tablet_skin_ID' ), $tablet_skins, T_('Default tablet skin'), NULL, $field_params );
$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Default URL for New Collections').get_manual_link( 'default-url-for-new-collections' ) );

	global $baseurl, $basehost, $baseprotocol, $baseport;
	$access_type_options = array(
		array( 'index.php', T_('Explicit param on index.php'),
				'<code>'.$baseurl.'index.php?blog=1</code>',
			),
		array( 'extrabase', T_('Extra path on baseurl'),
				'<code>'.$baseurl.'<span class="blog_url_text">urlname</span>/</code> ('.T_('Requires mod_rewrite').')',
			),
		array( 'extrapath', T_('Extra path on index.php'),
				'<code>'.$baseurl.'index.php/<span class="blog_url_text">urlname</span>/</code>',
			),
	);
	if( ! is_valid_ip_format( $basehost ) )
	{	// Not an IP address, we can use subdomains:
		$access_type_options[] = array( 'subdom', T_('Subdomain of basehost'),
				'<code>'.$baseprotocol.'://<span class="blog_url_text">urlname</span>.'.$basehost.$baseport.'/</code>',
			);
	}
	else
	{	// Don't allow subdomain for IP address:
		$access_type_options[] = array( 'subdom', T_('Subdomain').':',
				sprintf( T_('(Not possible for %s)'), $basehost ),
				'',
				'disabled="disabled"'
			);
	}
	$Form->radio( 'coll_access_type', $Settings->get( 'coll_access_type' ), $access_type_options, T_('Collection base URL'), true );

$Form->end_fieldset();

// --------------------------------------------

if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>
<script>
jQuery( '#always_match_slug' ).click( function()
{
	if( jQuery( this ).prop( 'checked' ) )
	{
		jQuery( '#redirect_moved_posts' ).prop( 'checked', true );
	}
	jQuery( '#redirect_moved_posts' ).prop( 'disabled', jQuery( this ).prop( 'checked' ) );
} );
</script>