<?php
/**
 * This file implements the item_info_line Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author erhsatingin: Erwin Rommel Satingin.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class item_info_line_Widget extends ComponentWidget
{
	var $icon = 'info';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_info_line' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'info-line-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Info Line');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Item Info Line') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display information about the item.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $Blog, $current_User, $admin_url;

		// Get available templates:
		$TemplateCache = & get_TemplateCache();
		$TemplateCache->load_where( 'tpl_parent_tpl_ID IS NULL' );
		$template_options = array( NULL => T_('No template / use settings below').':' ) + $TemplateCache->get_code_option_array();

		$r = array_merge( array(
				'title' => array(
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display' ),
					'defaultvalue' => '',
				),
				'template' => array(
					'label' => T_('Template'),
					'type' => 'select',
					'options' => $template_options,
					'defaultvalue' => NULL,
					'input_suffix' => ( is_logged_in() && $current_User->check_perm( 'options', 'edit' ) ? '&nbsp;'
							.action_icon( '', 'edit', $admin_url.'?ctrl=templates', NULL, NULL, NULL, array(), array( 'title' => T_('Manage templates').'...' ) ) : '' ),
				),
				'flag_icon' => array(
					'label' => T_( 'Flag icon' ),
					'note' => T_( 'Display flag icon' ),
					'type' => 'checkbox',
					'defaultvalue' => true,
				),
				'permalink_icon' => array(
					'label' => T_( 'Permalink icon' ),
					'note' => T_( 'Display permalink icon' ),
					'type' => 'checkbox',
					'defaultvalue' => false,
				),
				'before_author' => array(
					'label' => T_( 'Before author' ),
					'note' => T_( 'Display author information' ),
					'type' => 'radio',
					'options' => array(
						array( 'posted_by', T_( 'Posted by' ) ),
						array( 'started_by', T_( 'Started by' ) ),
						array( 'none', T_( 'None' ) )
					),
					'defaultvalue' => 'posted_by',
					'field_lines' => true,
				),
				'display_date' => array(
					'label' => T_('Post date to display'),
					'note' => '',
					'type' => 'radio',
					'options' => array(
						array( 'issue_date', T_('Issue date') ),
						array( 'date_created', T_('Date created') ),
						array( 'none', T_('None') )
					),
					'defaultvalue' => in_array( $Blog->type, array( 'forum', 'group' ) ) ? 'date_created' : 'issue_date',
					'field_lines' => true
				),
				'last_touched' => array(
					'label' => T_( 'Last touched' ),
					'note' => T_( 'Display date and time when item/post was last touched' ),
					'type' => 'checkbox',
					'defaultvalue' => false
				),
				'contents_updated' => array(
					'label' => T_( 'Contents last updated' ),
					'note' => T_( 'Display date and time when item/post contents (title, content, URL or attachments) were last updated' ),
					'type' => 'checkbox',
					'defaultvalue' => false
				),
				'date_format' => array(
					'label' => T_( 'Date format' ),
					'note' => T_( 'Item/post date display format' ),
					'type' => 'radio',
					'options' => array(
						array( 'extended', sprintf( T_('Extended format %s'), '<code>'.locale_extdatefmt().'</code>' ) ),
						array( 'long', sprintf( T_('Long format %s'), '<code>'.locale_longdatefmt().'</code>' ) ),
						array( 'short', sprintf( T_('Short format %s'), '<code>'.locale_datefmt().'</code>' ) ),
					),
					'defaultvalue' => 'extended',
					'field_lines' => true,
				),
				'time_format' => array(
					'label' => T_( 'Time format' ),
					'note' => T_( 'Item/post time display format' ),
					'type' => 'radio',
					'options' => array(
						array( 'long', sprintf( T_('Long format %s'), '<code>'.locale_timefmt().'</code>' ) ),
						array( 'short', sprintf( T_('Short format %s'), '<code>'.locale_shorttimefmt().'</code>' ) ),
						array( 'none', T_('None') )
					),
					'defaultvalue' => 'none',
					'field_lines' => true,
				),
				'category' => array(
					'label' => T_( 'Category' ),
					'note' => T_( 'Display item/post category' ),
					'type' => 'checkbox',
					'defaultvalue' => true,
				),
				'edit_link' => array(
					'label' => T_( 'Edit link' ),
					'note' => T_( 'Display link to edit the item/post' ),
					'type' => 'checkbox',
					'defaultvalue' => false,
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache" because this widget displays dynamic data:
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Item;

		if( empty( $Item ) )
		{	// Don't display this widget when there is no Item object:
			$this->display_error_message( 'Widget "'.$this->get_name().'" is hidden because there is no Item.' );
			return false;
		}

		$params = array_merge( array(
			'author_link_text' => 'preferredname',
			'block_body_start' => '<div>',
			'block_body_end'   => '</div>',
			// Use the following when building automatic template but NOT when using a template:
			'widget_item_info_line_before'  => '<span class="small text-muted">',
			'widget_item_info_line_after'   => '</span>',
			'widget_item_info_line_params'  => array(),
		), $params );

		$this->init_display( $params );

		if( $this->disp_params['template'] )
		{
			load_funcs( 'templates/model/_template.funcs.php' );
			$TemplateCache = & get_TemplateCache();
			if( ! $TemplateCache->get_by_code( $this->disp_params['template'], false, false ) )
			{
				$this->display_error_message( sprintf( 'Template not found: %s', '<code>'.$this->disp_params['template'].'</code>' ) );
				return false;
			}

			$template_code = $this->disp_params['template'];
			$info_line = render_template_code( $template_code, array_merge( $params, array(
					'date_format' => '#'.$this->disp_params['date_format'].( $this->disp_params['date_format'] == 'none' ? '' : '_date' ),
					'time_format' => '#'.$this->disp_params['time_format'].( $this->disp_params['time_format'] == 'none' ? '' : '_time' ),
				) ) );
		}
		else
		{	// Build an automatic template:
			$template = '';

			// Flag icon:
			if( $this->disp_params['flag_icon'] )
			{
				$template .= '$flag_icon$';
			}

			// Permalink icon:
			if( $this->disp_params['permalink_icon'] )
			{
				$template .= ' $permalink_icon$';
			}

			// Author:
			$before_author = '';
			switch( $this->disp_params['before_author'] )
			{
				case 'posted_by':
					$before_author = T_('Posted by').' ';
					$template .= ' $author$';
					break;

				case 'started_by':
					$before_author = T_('Started by').' ';
					$template .= ' $author$';
					break;

				default:
					// do nothing
			}

			// Date issued / Creation date:
			$before_issue_time = '';
			$before_creation_time = '';
			switch( $this->disp_params['display_date'] )
			{
				case 'issue_date':
					$before_issue_time = empty( $before_author ) ? '' : $T_('on').' ';
					$template .= ' $issue_time$';
					break;

				case 'date_created':
					$before_creation_time = empty( $before_author ) ? '' : T_('on').' ';
					$template .= ' $creation_time$';
					break;

				default:
					// do nothing
			}

			// Categories:
			if( $this->disp_params['category'] )
			{
				$template .= ' $categories$';
			}

			// Last touched:
			if( $this->disp_params['last_touched'] )
			{
				$template .= ' $last_touched$';
			}

			// Last content updated:
			if( $this->disp_params['contents_updated'] )
			{
				$template .= ' $last_updated$';
			}

			// Edit link:
			if( $this->disp_params['edit_link'] )
			{
				$template .= ' $edit_link$';
			}

			$widget_params = array_merge( array(
					'date_format' => '#'.$this->disp_params['date_format'].( $this->disp_params['date_format'] == 'none' ? '' : '_date' ),
					'time_format' => '#'.$this->disp_params['time_format'].( $this->disp_params['time_format'] == 'none' ? '' : '_time' ),

					'before_flag' => '',
					'after_flag'  => '',

					'before_permalink' => '',
					'after_permalink'  => '',
					'permalink_text'   => '#icon#',

					'before_author' => $before_author,
					'after_author'  => '',

					'before_issue_time' => $before_issue_time,
					'after_issue_time'  => '',
					'issue_time_format' => NULL, // Use date_format and time_format params

					'before_creation_time' => $before_creation_time,
					'after_creation_time'  => '',
					'creation_time_format' => NULL, // Use date_format and time_format params

					'before_categories'   => T_('in').' ',
					'after_categories'    => '',

					'before_last_touched' => '<span class="text-muted">&ndash; '.T_('Last touched').': ',
					'after_last_touched'  => '</span>',
					'last_touched_format' => '',

					'before_last_updated' => '<span class="text-muted">&ndash; '.T_('Contents updated').': ',
					'after_last_updated'  => '</span>',
					'last_updated_format' => '',

					'before_edit_link'    => '&bull; ',
					'after_edit_link'     => '',
					
					'edit_link_text'      => '#',
				), $params['widget_item_info_line_params'] );

			// Automatic template used, render raw template:
			$info_line = render_template( $template, array_merge( $params, $widget_params ) );

			if( ! empty( $info_line ) )
			{
				$info_line = $params['widget_item_info_line_before'].$info_line.$params['widget_item_info_line_after'];
			}
		}

		if( ! empty( $info_line ) )
		{
			echo $this->disp_params['block_start'];

			$this->disp_title();

			echo $this->disp_params['block_body_start'];

			echo $info_line;

			echo $this->disp_params['block_body_end'];
			echo $this->disp_params['block_end'];

			return true;
		}

		$this->display_debug_message();
		return false;
	}
}

?>
