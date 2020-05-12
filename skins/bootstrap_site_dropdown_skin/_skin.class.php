<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage bootstrap_site_navbar_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class bootstrap_site_dropdown_Skin extends Skin
{
	/**
	 * Skin version
	 * @var string
	 */
	var $version = '7.1.2';

	/**
	 * Do we want to use style.min.css instead of style.css ?
	 */
	var $use_min_css = true;  // true|false|'check' Set this to true for better optimization

	/**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'Bootstrap Site Dropdown';
	}


	/**
	 * Get default type for the skin.
	 */
	function get_default_type()
	{
		return 'rwd';
	}


	/**
	 * Does this skin provide normal (collection) skin functionality?
	 */
	function provides_collection_skin()
	{
		return false;
	}


	/**
	 * Does this skin provide site-skin functionality?
	 */
	function provides_site_skin()
	{
		return true;
	}


	/**
	 * What evoSkins API does has this skin been designed with?
	 *
	 * This determines where we get the fallback templates from (skins_fallback_v*)
	 * (allows to use new markup in new b2evolution versions)
	 */
	function get_api_version()
	{
		return 7;
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 * @return array
	 */
	function get_param_definitions( $params )
	{
		global $current_User, $admin_url;

		// Set params for setting "Collection for Info Pages":
		$BlogCache = & get_BlogCache();
		$BlogCache->none_option_text = T_('Same as "Default collection to display"');

		$SiteMenuCache = & get_SiteMenuCache();
		$SiteMenuCache->load_where( 'menu_translates_menu_ID IS NULL');

		$r = array_merge( array(
				'section_layout_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('CSS files')
				),
					'css_files' => array(
						'label' => T_('CSS files'),
						'note' => '',
						'type' => 'checklist',
						'options' => array(
								array( 'style.css',      'style.css', 0 ),
								array( 'style.min.css',  'style.min.css', 1 ), // default
								array( 'custom.css',     'custom.css', 0 ),
								array( 'custom.min.css', 'custom.min.css', 0 ),
							)
					),
				'section_layout_end' => array(
					'layout' => 'end_fieldset',
				),

				'section_header_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Header')
				),
					'menu_type' => array(
						'label' => T_('Menu type'),
						'options' => array(
								array( 'auto', T_('Automatic (from collection List)') ),
								array( 'custom', T_('Custom menu') ),
							),
						'defaultvalue' => 'auto',
						'type' => 'radio',
						'field_lines' => true,
					),
					'grouping' => array(
						'label' => T_('Grouping'),
						'note' => T_('Check to group collections into dropdown menus'),
						'type' => 'checkbox',
						'defaultvalue' => 1,
						'hide' => ( $this->get_setting( 'menu_type', NULL, 'auto' ) == 'custom' ),
					),
					'info_coll_ID' => array(
						'label' => T_('Collection for Info Pages'),
						'type' => 'select_blog',
						'allow_none' => true,
						'defaultvalue' => 0,
						'hide' => ( $this->get_setting( 'menu_type', NULL, 'auto' ) == 'custom' ),
					),
					'menu_ID' => array(
						'label' => T_('Menu to display'),
						'input_suffix' => ( is_logged_in() && $current_User->check_perm( 'options', 'edit' ) ? ' <a href="'.$admin_url.'?ctrl=menus">'.T_('Manage Menus').' &gt;&gt;</a>' : '' ),
						'type' => 'select_object',
						'object' => $SiteMenuCache,
						'allow_none' => true,
						'defaultvalue' => '',
						'hide' => ( $this->get_setting( 'menu_type', NULL, 'auto' ) == 'auto' ),
					),
					'fixed_header' => array(
						'label' => T_('Fixed position'),
						'note' => T_('Check to fix header top on scroll down'),
						'type' => 'checkbox',
						'defaultvalue' => 1,
					),

					'section_topmenu_start' => array(
						'layout' => 'begin_fieldset',
						'label'  => T_('Top menu settings')
					),
						'menu_bar_bg_color' => array(
							'label' => T_('Menu bar background color'),
							'defaultvalue' => '#f8f8f8',
							'type' => 'color',
						),
						'menu_bar_border_color' => array(
							'label' => T_('Menu bar border color'),
							'defaultvalue' => '#e7e7e7',
							'type' => 'color',
						),
						'menu_bar_logo_padding' => array(
							'label' => T_('Menu bar logo padding'),
							'input_suffix' => ' px ',
							'note' => T_('Set the padding around the logo.'),
							'defaultvalue' => '2',
							'type' => 'integer',
							'size' => 1,
						),
						'tab_text_color' => array(
							'label' => T_('Tab text color'),
							'defaultvalue' => '#777',
							'type' => 'color',
						),
						'hover_tab_bg_color' => array(
							'label' => T_('Hover tab color'),
							'defaultvalue' => '#f8f8f8',
							'type' => 'color',
						),
						'hover_tab_text_color' => array(
							'label' => T_('Hover tab text color'),
							'defaultvalue' => '#333',
							'type' => 'color',
						),
						'selected_tab_bg_color' => array(
							'label' => T_('Selected tab color'),
							'defaultvalue' => '#e7e7e7',
							'type' => 'color',
						),
						'selected_tab_text_color' => array(
							'label' => T_('Selected tab text color'),
							'defaultvalue' => '#555',
							'type' => 'color',
						),
					'section_topmenu_end' => array(
						'layout' => 'end_fieldset',
					),

					'section_submenu_start' => array(
						'layout' => 'begin_fieldset',
						'label'  => T_('Submenu settings')
					),
						'sub_tab_bg_color' => array(
							'label' => T_('Menu bar background color'),
							'defaultvalue' => '#fff',
							'type' => 'color',
						),
						'sub_tab_border_color' => array(
							'label' => T_('Menu bar border color'),
							'defaultvalue' => '#ddd',
							'type' => 'color',
						),
						'sub_tab_text_color' => array(
							'label' => T_('Tab text color'),
							'defaultvalue' => '#555',
							'type' => 'color',
						),
						'sub_hover_tab_bg_color' => array(
							'label' => T_('Hover tab color'),
							'defaultvalue' => '#f5f5f5',
							'type' => 'color',
						),
						'sub_hover_tab_text_color' => array(
							'label' => T_('Hover tab text color'),
							'defaultvalue' => '#555',
							'type' => 'color',
						),
						'sub_selected_tab_bg_color' => array(
							'label' => T_('Selected tab color'),
							'defaultvalue' => '#eee',
							'type' => 'color',
						),
						'sub_selected_tab_text_color' => array(
							'label' => T_('Selected tab text color'),
							'defaultvalue' => '#555',
							'type' => 'color',
						),
					'section_submenu_end' => array(
						'layout' => 'end_fieldset',
					),

				'section_header_end' => array(
					'layout' => 'end_fieldset',
				),
				
				'section_floating_nav_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Floating navigation settings')
				),
						'back_to_top_button' => array(
							'label' => T_('"Back to Top" button'),
							'note' => T_('Check to enable "Back to Top" button'),
							'defaultvalue' => 1,
							'type' => 'checkbox',
						),
				'section_floating_nav_end' => array(
					'layout' => 'end_fieldset',
				),

				'section_footer_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Footer settings')
				),
					'footer_bg_color' => array(
						'label' => T_('Background color'),
						'defaultvalue' => '#f5f5f5',
						'type' => 'color',
					),
					'footer_text_color' => array(
						'label' => T_('Text color'),
						'defaultvalue' => '#777',
						'type' => 'color',
					),
					'footer_link_color' => array(
						'label' => T_('Link color'),
						'defaultvalue' => '#337ab7',
						'type' => 'color',
					),
				'section_footer_end' => array(
					'layout' => 'end_fieldset',
				),

			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Get ready for displaying the site skin.
	 *
	 * This may register some CSS or JS...
	 */
	function siteskin_init()
	{
		global $Blog, $Session;

		// Include the enabled skin CSS files relative current SITE skin folder:
		$css_files = $this->get_setting( 'css_files' );
		if( is_array( $css_files ) && count( $css_files ) )
		{
			foreach( $css_files as $css_file_name => $css_file_is_enabled )
			{
				if( $css_file_is_enabled )
				{
					require_css( $css_file_name, 'siteskin' );
				}
			}
		}

		// Add custom styles:
		// Top menu:
		$menu_bar_bg_color = $this->get_setting( 'menu_bar_bg_color' );
		$menu_bar_border_color = $this->get_setting( 'menu_bar_border_color' );
		$menu_bar_logo_padding = $this->get_setting( 'menu_bar_logo_padding' );
		$tab_text_color = $this->get_setting( 'tab_text_color' );
		$hover_tab_bg_color = $this->get_setting( 'hover_tab_bg_color' );
		$hover_tab_text_color = $this->get_setting( 'hover_tab_text_color' );
		$selected_tab_bg_color = $this->get_setting( 'selected_tab_bg_color' );
		$selected_tab_text_color = $this->get_setting( 'selected_tab_text_color' );
		// Sub menu:
		$sub_tab_bg_color = $this->get_setting( 'sub_tab_bg_color' );
		$sub_tab_border_color = $this->get_setting( 'sub_tab_border_color' );
		$sub_tab_text_color = $this->get_setting( 'sub_tab_text_color' );
		$sub_hover_tab_bg_color = $this->get_setting( 'sub_hover_tab_bg_color' );
		$sub_hover_tab_text_color = $this->get_setting( 'sub_hover_tab_text_color' );
		$sub_selected_tab_bg_color = $this->get_setting( 'sub_selected_tab_bg_color' );
		$sub_selected_tab_text_color = $this->get_setting( 'sub_selected_tab_text_color' );
		// Footer:
		$footer_bg_color = $this->get_setting( 'footer_bg_color' );
		$footer_text_color = $this->get_setting( 'footer_text_color' );
		$footer_link_color = $this->get_setting( 'footer_link_color' );


		$css = '
#evo_site_header .navbar {
	background-color: '.$menu_bar_bg_color.';
	border-color: '.$menu_bar_border_color.';
}
#evo_site_header .navbar .navbar-collapse .nav.navbar-right {
	border-color: '.$menu_bar_border_color.';
}
#evo_site_header .navbar-brand img {
	padding: '.$menu_bar_logo_padding.'px;
}
#evo_site_header .navbar .nav > li:not(.active) > a {
	color: '.$tab_text_color.';
}
#evo_site_header .navbar .nav > li:not(.active) > a:hover {
	background-color: '.$hover_tab_bg_color.';
	color: '.$hover_tab_text_color.';
}
#evo_site_header .navbar .nav > li.active > a {
	background-color: '.$selected_tab_bg_color.';
	color: '.$selected_tab_text_color.';
}

#evo_site_header .navbar .nav ul.dropdown-menu {
	background-color: '.$sub_tab_bg_color.';
	border-color: '.$sub_tab_border_color.';
}
#evo_site_header .navbar .nav ul.dropdown-menu li:not(.active) a {
	color: '.$sub_tab_text_color.';
}
#evo_site_header .navbar .nav ul.dropdown-menu li:not(.active) a:hover {
	background-color: '.$sub_hover_tab_bg_color.';
	color: '.$sub_hover_tab_text_color.';
}
#evo_site_header .navbar .nav ul.dropdown-menu li.active a {
	background-color: '.$sub_selected_tab_bg_color.';
	color: '.$sub_selected_tab_text_color.';
}

footer#evo_site_footer {
	background-color: '.$footer_bg_color.';
	color: '.$footer_text_color.';
}
footer#evo_site_footer .container a {
	color: '.$footer_link_color.';
}
';

		if( $this->get_setting( 'fixed_header' ) &&
		    ! $Session->get( 'display_containers_'.$Blog->ID ) &&
		    ! $Session->get( 'display_includes_'.$Blog->ID ) )
		{	// Enable fixed position for header only when no debug blocks:
			$css .= '#evo_site_header {
	position: fixed;
	top: 0;
	width: 100%;
	z-index: 10000;
}
body.evo_toolbar_visible #evo_site_header {
	top: 27px;
}
body {
	padding-top: 50px;
}';
		}

		add_css_headline( $css );
	}


	/**
	 * Get header tabs if custom menu is selected or when automatic menu is grouped
	 *
	 * @return array|boolean Array of header tabs OR FALSE when
	 */
	function get_header_tabs()
	{
		if( $this->get_setting( 'menu_type' ) == 'custom' &&
		    ( $SiteMenuCache = & get_SiteMenuCache() ) &&
		    ( $SiteMenu = & $SiteMenuCache->get_by_ID( $this->get_setting( 'menu_ID' ), false, false ) ) )
		{	// Use custom menu if it is found in DB:
			return $this->get_header_tabs_custom( $SiteMenu->ID );
		}
		elseif( $this->get_setting( 'grouping' ) )
		{	// Use automatic menu:
			return $this->get_header_tabs_auto();
		}

		// Don't use grouped header tabs:
		return false;
	}


	/**
	 * Get header tabs from custom menu
	 *
	 * @return array
	 */
	function get_header_tabs_custom( $menu_ID )
	{
		global $DB, $current_locale;

		$header_tabs = array();

		$SiteMenuCache = & get_SiteMenuCache();

		if( ! ( $SiteMenu = & $SiteMenuCache->get_by_ID( $this->get_setting( 'menu_ID' ), false, false ) ) )
		{	// Wrong Menu:
			return $header_tabs;
		}

		// Check if the menu has a child matching the current locale:
		$localized_menus = $SiteMenu->get_localized_menus( $current_locale );
		if( ! empty( $localized_menus ) )
		{	// Use localized menu:
			$SiteMenu = & $localized_menus[0];
		}

		$this->header_tab_active = NULL;

		$site_menu_entries = $SiteMenu->get_entries();
		$level0_index = 0;
		foreach( $site_menu_entries as $SiteMenuEntry )
		{
			if( $header_tab = $this->get_header_tab_custom( $SiteMenuEntry ) )
			{
				$header_tabs[] = $header_tab;
				if( ! empty( $header_tab['items'] ) )
				{
					foreach( $header_tab['items'] as $sub_item )
					{
						if( ! empty( $sub_item['active'] ) )
						{
							$this->header_tab_active = $level0_index;
							break;
						}
					}
				}
				$level0_index++;
			}
		}

		return $header_tabs;
	}


	/**
	 * Get custom header tab
	 *
	 * @param object SiteMenuEntry
	 * @param array header tab params
	 */
	function get_header_tab_custom( $SiteMenuEntry )
	{
		global $Blog;

		$header_tab = false;

		if( $SiteMenuEntry->get( 'type' ) == 'text' )
		{	// Only type "Text" supports sub-entries:
			$sub_entries = $SiteMenuEntry->get_children( true );
			$sub_tabs = array();
			foreach( $sub_entries as $sub_SiteMenuEntry )
			{
				if( $sub_tab = $this->get_header_tab_custom( $sub_SiteMenuEntry ) )
				{
					$sub_tabs[] = $sub_tab;
				}
			}

			if( ! empty( $sub_tabs ) )
			{	// Display parent tab only if at least one sub tab is allowed for current display:
				$header_tab = array(
						'name'  => $SiteMenuEntry->get_text(),
						'url'   => $sub_tabs[0]['url'],
						'items' => $sub_tabs,
					);
			}
		}
		elseif( $menu_entry_url = $SiteMenuEntry->get_url() )
		{	// Only if the menu entry is allowed for current User, page and etc.:
			$header_tab = array(
					'name'   => $SiteMenuEntry->get_text(),
					'url'    => $menu_entry_url,
					'active' => $SiteMenuEntry->is_active(),
				);
		}

		return $header_tab;
	}


	/**
	 * Get automatic (from collection List) header tabs
	 *
	 * @return array
	 */
	function get_header_tabs_auto()
	{
		global $Blog, $disp, $current_User;

		$header_tabs = array();

		// Get disp from request string if it is not initialized yet:
		$current_disp = isset( $_GET['disp'] ) ? $_GET['disp'] : ( isset( $disp ) ? $disp : NULL );

		// Get current collection ID:
		$current_blog_ID = isset( $Blog ) ? $Blog->ID : NULL;

		// Load all sections except of "No Section" because collections of this section are displayed as separate tabs at the end:
		$SectionCache = & get_SectionCache();
		$SectionCache->clear();
		$SectionCache->load_where( 'sec_ID != 1' );

		$this->header_tab_active = NULL;
		$level0_index = 0;
		foreach( $SectionCache->cache as $Section )
		{
			$tab_items = array();
			$group_blogs = $Section->get_blogs();

			$level0_is_active = false;

			// Check each collection if it can be viewed by current user:
			foreach( $group_blogs as $i => $group_Blog )
			{
				$coll_is_active = false;
				if( $current_blog_ID == $group_Blog->ID &&
						( $this->get_info_coll_ID() != $current_blog_ID || ( $current_disp != 'page' && $current_disp != 'msgform' ) ) )
				{	// Mark this menu as active:
					$coll_is_active = true;
				}

				$coll_data = array(
						'name'   => $group_Blog->get( 'name' ),
						'url'    => $group_Blog->get( 'url' ),
						'active' => ( $current_blog_ID == $group_Blog->ID )
					);

				// Get value of collection setting "Show in front-office list":
				$in_bloglist = $group_Blog->get( 'in_bloglist' );

				if( $in_bloglist == 'public' )
				{	// Everyone can view this collection, Keep this in menu:
					$tab_items[] = $coll_data;
					if( $coll_is_active )
					{
						$this->header_tab_active = $level0_index;
					}
					continue;
				}

				if( $in_bloglist == 'never' )
				{	// Nobody can view this collection, Skip it:
					continue;
				}

				if( ! is_logged_in() )
				{	// Only logged in users have an access to this collection, Skip it:
					continue;
				}

				if( $in_bloglist == 'member' &&
						! $current_User->check_perm( 'blog_ismember', 'view', false, $group_Blog->ID ) )
				{	// Only members have an access to this collection, Skip it:
					continue;
				}

				$tab_items[] = $coll_data;
				if( $coll_is_active )
				{
					$this->header_tab_active = $level0_index;
				}
			}

			if( ! empty( $tab_items ) )
			{	// Display section only if at least one collection is allowed for current display:
				$header_tabs[] = array(
						'name'  => $Section->get_name(),
						'url'   => $tab_items[0]['url'],
						'items' => $tab_items
					);

				$level0_index++;
			}
		}

		// Load all collection from "No Section" and put them after all section tabs:
		$BlogCache = & get_BlogCache();
		$BlogCache->clear();
		$public_colls_SQL = $BlogCache->get_public_colls_SQL();
		$public_colls_SQL->WHERE_and( 'blog_sec_ID = 1' );
		$BlogCache->load_by_sql( $public_colls_SQL );

		foreach( $BlogCache->cache as $nosec_Blog )
		{
			$header_tabs[] = array(
					'name' => $nosec_Blog->get( 'shortname' ),
					'url'  => $nosec_Blog->get( 'url' ),
				);

			if( $current_blog_ID == $nosec_Blog->ID )
			{	// Mark this tab as active if this is a current collection:
				$this->header_tab_active = $level0_index;
			}

			$level0_index++;
		}

		// Additional tab with pages and contact links:
		if( isset( $Blog ) )
		{
			$tab_items = array( 'pages' );

			if( $current_disp == 'msgform' )
			{	// Mark this menu as active:
				$this->header_tab_active = $level0_index;
			}

			if( $current_disp == 'page' && $this->get_info_coll_ID() == $Blog->ID )
			{	// If this menu contains the links to pages of the info/shared collection:
				$this->header_tab_active = $level0_index;
			}

			if( $contact_url = $Blog->get_contact_url() )
			{	// If contact page is allowed for current collection:
				$tab_item = array(
						'name'   => T_('Contact'),
						'url'    => $contact_url,
						'active' => ( $current_disp == 'msgform' )
					);
				if( $Blog->get_setting( 'msgform_nofollowto' ) )
				{	// Use nofollow attribute:
					$tab_item['rel'] = 'nofollow';
				}
				$tab_items[] = $tab_item;
			}

			if( ! empty( $contact_url ) )
			{	// Display additional tabs with static pages only user has an access to contact page:
				$header_tabs[] = array(
						'name'   => T_('About'),
						'url'    => $contact_url,
						'items'  => $tab_items
					);
			}
		}

		return $header_tabs;
	}


	/**
	 * Get ID of collection for Info Pages
	 *
	 * @return integer ID
	 */
	function get_info_coll_ID()
	{
		$info_coll_ID = $this->get_setting( 'info_coll_ID' );

		if( empty( $info_coll_ID ) )
		{	// Use same collection as "Default collection to display":
			global $Settings;
			return $Settings->get( 'default_blog_ID' );
		}

		return $info_coll_ID;
	}


	/**
	 * Additional JavaScript code for skin settings form
	 */
	function echo_settings_form_js()
	{
?>
<script>
jQuery( '[name=edit_skin_<?php echo $this->ID; ?>_set_menu_type]' ).click( function()
{
	var is_auto_mode = ( jQuery( '[name=edit_skin_<?php echo $this->ID; ?>_set_menu_type]:checked' ).val() == 'auto' );
	jQuery( '#ffield_edit_skin_<?php echo $this->ID; ?>_set_grouping, #ffield_edit_skin_<?php echo $this->ID; ?>_set_info_coll_ID' ).toggle( is_auto_mode );
	jQuery( '#ffield_edit_skin_<?php echo $this->ID; ?>_set_menu_ID' ).toggle( ! is_auto_mode );
} );
</script>
<?php
	}
}
?>
