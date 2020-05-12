<?php
/**
 * This file implements Template functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Render template content code depending on current locale
 * 
 * @param string Template code
 * @param array Parameters (by reference)
 * @param array Objects
 * @return string|boolean Rendered template or FALSE on wrong request
 */
function render_template_code( $code, & $params, $objects = array(), & $used_template_tags = NULL )
{
	global $current_locale;

	$TemplateCache = & get_TemplateCache();
	if( ! ( $Template = & $TemplateCache->get_by_code( $code, false, false ) ) )
	{
		return false;
	}

	// Check if the template has a child matching the current locale:
	$localized_templates = $Template->get_localized_templates( $current_locale );
	if( ! empty( $localized_templates ) )
	{	// Use localized template:
		$Template = & $localized_templates[0];
	}

	if( $Template )
	{	// Render variables in available Template:
		return render_template( $Template->template_code, $params, $objects, $used_template_tags );
	}

	return false;
}


/**
 * Render template content
 * 
 * @param string Template
 * @param array Parameters (by reference)
 * @param array Objects
 * @return string Rendered template
 */
function render_template( $template, & $params, $objects = array(), & $used_template_tags = NULL )
{
	$current_pos = 0;
	$r = '';

	// New
	preg_match_all( '/\[((?:(?:Cat|Coll|Form|Item|Link|Plugin|echo|set):)?([a-z0-9_]+))\|?((?:.|\n|\r|\t)*?)\]/i', $template, $matches, PREG_OFFSET_CAPTURE );
	foreach( $matches[0] as $i => $match )
	{
		// Output everything until new tag:
		$r .= substr( $template, $current_pos, $match[1] - $current_pos );
		$current_pos = $match[1] + strlen( $match[0] );

		// New tag to handle:
		$tag = $matches[1][$i][0];

		// Params specified for the tag:
		$tag_param_strings = empty( $matches[3][$i][0] ) ? NULL : $matches[3][$i][0];

		if( substr( $tag, 0, 4 ) == 'set:' )
		{	// Set a param value in the $params[] array used for the whole template (will affect all future template tags)

			$param_name = substr( $tag, 4 );
			$param_val  = substr( $tag_param_strings, strpos( $tag_param_strings, '=' ) + 1 );
			$param_strings = $param_name.'='.$param_val;
			$param_strings = explode( '|', $param_strings );

			foreach( $param_strings as $param_string )
			{
				if( empty( $param_string ) || ctype_space( $param_string ) )
				{	// Nothing here, ignore:
					continue;
				}

				$param_name = substr( $param_string, 0, strpos( $param_string, '=' ) );

				if( empty( $param_name ) )
				{	// Does not contain a param name, ignore:
					continue;
				}

				if( strpos( $param_name, '//' ) !== false )
				{	// We found a comment that we should remove:
					$param_name = preg_replace( '~(.*)//.*$~im','$1', $param_name );
				}

				// Trim off whitespace:
				$param_name = trim( $param_name );

				$param_val  = substr( $param_string, strpos( $param_string, '=' ) + 1 );

				// Set param:
				// we MUST do this here and in & $params[] so that it sticks. This cannot be done in the callback or $this_tag_params[]
				$params[ $param_name ] = $param_val;
			}
		}
		else
		{	// Process a normal template tag:

			// Decode PARAMS like |name=value|name=value]
			$this_tag_params = $params;
			if( ! empty( $tag_param_strings ) )
			{	
				$tag_param_strings = explode( '|', $tag_param_strings );
				
				// Process each param individually:
				foreach( $tag_param_strings as $tag_param_string )
				{
					if( empty( $tag_param_string ) || ctype_space( $tag_param_string) )
					{
						continue;
					}

					$tag_param_name = substr( $tag_param_string, 0, strpos( $tag_param_string, '=' ) );

					if( strpos( $tag_param_name, '//' ) !== false )
					{	// We found a comment that we should remove:
						$tag_param_name = preg_replace( '~(.*)//.*$~im','$1', $tag_param_name );
					}

					// Trim off whitespace:
					$tag_param_name = trim( $tag_param_name );

					$tag_param_val  = substr( $tag_param_string, strpos( $tag_param_string, '=' ) + 1 );

					if( preg_match('/\$([a-z_]+)\$/i', $tag_param_val, $tag_param_val_matches ) )
					{	// We have a variable to replace: // TODO: allow multiple variable replace
						$found_param_name = $tag_param_val_matches[1];
						if( isset( $params[$found_param_name] ) )
						{	// We have an original param of that name:
							$tag_param_val = $params[$found_param_name];
						}
					}

					// TODO: need to escape " and > from $tag_param_val, otherwise they will end up breaking something

					$this_tag_params[$tag_param_name] = $tag_param_val;
				}
			}
			if( is_array( $used_template_tags ) )
			{
				$used_template_tags[] = $tag; 
			}
			$r .= render_template_callback( $tag, $this_tag_params, $objects );
		}
	}

	// Print remaining template code:
	$r .= substr( $template, $current_pos );

	return $r;
}

/**
 * Callback function to replace variables in template
 * 
 * @param string Variable to be replaced
 * @param array Additional parameters (by reference)
 * @param array Objects
 * @return string Replacement string
 */
function render_template_callback( $var, $params, $objects = array() )
{
	// Get scope and var name:
	preg_match( '#^(([a-z]+):)?(.+)$#i', $var, $match_var );
	$scope = ( empty( $match_var[2] ) ? 'Item': $match_var[2] );
	$var = $scope.':'.$match_var[3];
	switch( $scope )
	{
		case 'Cat':
			global $Chapter;
			$rendered_Chapter = ( !isset($objects['Chapter']) ? $Chapter : $objects['Chapter'] );
			if( empty( $rendered_Chapter ) || ! ( $rendered_Chapter instanceof Chapter ) )
			{
				return '<span class="evo_param_error">['.$var.']: Object Chapter/Category is not defined at this moment.</span>';
			}
			break;

		case 'Coll':
			global $Blog;
			$rendered_Blog = ( !isset($objects['Collection']) ? $Blog : $objects['Collection'] );
			if( empty( $rendered_Blog ) || ! ( $rendered_Blog instanceof Blog ) )
			{
				return '<span class="evo_param_error">['.$var.']: Object Collection/Blog is not defined at this moment.</span>';
			}
			break;

		case 'Form':
			$rendered_Form = ( !isset($objects['Form']) ? $Form : $objects['Form'] );
			if( empty( $rendered_Form ) || ! ( $rendered_Form instanceof Form ) )
			{
				return '<span class="evo_param_error">['.$var.']: Object Form is not defined at this moment.</span>';
			}
			break;

		case 'Link':
			// do nothing
			break;

		case 'Item':
			global $Item;

			$rendered_Item = ( !isset($objects['Item']) ? $Item : $objects['Item'] );

			if( empty( $rendered_Item ))
			{
				return '<span class="evo_param_error">['.$var.']: Object Item is not defined at this moment.</span>';
			}
			if( ! ( $rendered_Item instanceof Item ) )
			{
				return '<span class="evo_param_error">Item object has class <code>'.get_class($rendered_Item).'</code> instead of expected <code>Item</code>.</span>';
			}
			break;

		case 'Plugin':
			global $Plugins;

			$rendered_Plugin = & $Plugins->get_by_code( $match_var[3] );

			if( empty( $rendered_Plugin ) )
			{
				return '<span class="evo_param_error">Plugin <code>'.$match_var[3].'</code> is not recognized.</span>';
			}

			$var = $scope;
			break;

		case 'echo':
			$param_name = substr( $var, 5 );
			if( ! isset( $params[ $param_name ] ) )
			{	// Param is not found:
				return '<span class="evo_param_error">Param <code>'.$param_name.'</code> is not passed.</span>';
			}
			elseif( ! is_scalar( $params[ $param_name ] ) )
			{	// Param is not scalar and cannot be printed on screen:
				return '<span class="evo_param_error">Param <code>'.$param_name.'</code> is not scalar.</span>';
			}
			break;

		default:
			return '<span class="evo_param_error">['.$var.']: Scope "'.$scope.':" is not recognized.</span>';
	}

	$match_found = true;

	ob_start();
	switch( $var )
	{
		// Chapter / Category:
		case 'Cat:background_image_css':
			echo $rendered_Chapter->get_background_image_css( $params );
			break;

		case 'Cat:description':
			echo $rendered_Chapter->dget( 'description' );
			break;

		case 'Cat:image':
			echo $rendered_Chapter->get_image_tag( array_merge( array(
					'before_classes' => '', // Allow injecting additional classes into 'before'
					'size'        => 'crop-256x256',
					'link_to'     => '#category_url',
					'placeholder' => '#folder_icon',
				), $params ) );
			break;

		case 'Cat:image_url':
			echo $rendered_Chapter->get_image_url( $params );
			break;

		case 'Cat:name':
			echo $rendered_Chapter->dget( 'name' );
			break;

		case 'Cat:permalink':
			echo $rendered_Chapter->get_permanent_link( array_merge( array(
					'text'   => '#name',
					'title'  => '',
				), $params ) );
			break;

		// Collection:
		case 'Coll:shortname':
			echo $rendered_Blog->dget( 'shortname' );
			break;

		// Form:
		case 'Form:country':
			global $Settings;

			$country = param( 'country', 'integer', 0 );
			$temp_params = array(
					'name'       => 'country',
					'value'      => $country,
					'label'      => T_('Country'),
					'allow_none' => true,
					'required'   => isset( $params['reg1_required'] ) ? in_array( 'country', array_map( 'trim', explode( ',', $params['reg1_required'] ) ) ) : false,
					'class'      => '',
					'hide_label' => false,
					'style'      => '',
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );

			$CountryCache = & get_CountryCache();
			$rendered_Form->select_country( $temp_params['name'], $temp_params['value'], $CountryCache, $temp_params['label'], $temp_params );
			break;

		case 'Form:email':
			global $dummy_fields;

			$email = utf8_strtolower( param( $dummy_fields['email'], 'string', '' ) );
			if( isset( $objects['register_user_data']['email'] ) )
			{
				$email = $objects['register_user_data']['email'];
			}
			$temp_params = array(
					'name'        => $dummy_fields['email'],
					'value'       => $email,
					'size'        => 50,
					'label'       => T_('Email'),
					'placeholder' => $params['register_use_placeholders'] ? T_('Email address') : '',
					'bottom_note' => T_('We respect your privacy. Your email will remain strictly confidential.'),
					'maxlength'   => 255,
					'class'       => 'input_text wide_input',
					'required'    => isset( $params['reg1_required'] ) ? in_array( 'email', array_map( 'trim', explode( ',', $params['reg1_required'] ) ) ) : false,
					'class'      => '',
					'hide_label' => false,
					'style'      => '',
			);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );

			$rendered_Form->email_input( $temp_params['name'], $temp_params['value'], $temp_params['size'], $temp_params['label'], $temp_params );
			break;

		case 'Form:firstname':
			global $Settings;

			$firstname = param( 'firstname', 'string', '' );
			$temp_params = array(
					'name'        => 'firstname',
					'value'       => $firstname,
					'size'        => 18,
					'label'       => T_('First name'),
					'note'        => T_('Your real first name'),
					'placeholder' => '',
					'maxlength'   => 50,
					'class'       => 'input_text',
					'required'    => isset( $params['reg1_required'] ) ? in_array( 'firstname', array_map( 'trim', explode( ',', $params['reg1_required'] ) ) ) : false,
					'class'      => '',
					'hide_label' => false,
					'style'      => '',
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );

			$rendered_Form->text_input( $temp_params['name'], $temp_params['value'], $temp_params['size'], $temp_params['label'], $temp_params['note'], $temp_params );
			break;

		case 'Form:gender':
			global $Settings;
		
			$gender = param( 'gender', 'string', false );
			$temp_params = array(
					'name' => 'gender',
					'value' => $gender,
					'label' => T_('I am'),
					'required' => isset( $params['reg1_required'] ) ? in_array( 'gender', array_map( 'trim', explode( ',', $params['reg1_required'] ) ) ) : false,
					'class'      => '',
					'hide_label' => false,
					'style'      => '',
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );

			$rendered_Form->radio_input( $temp_params['name'], $temp_params['value'], array(
					array( 'value' => 'M', 'label' => T_('A man') ),
					array( 'value' => 'F', 'label' => T_('A woman') ),
					array( 'value' => 'O', 'label' => T_('Other') ),
				), $temp_params['label'], $temp_params );
			break;

		case 'Form:lastname':
			global $Settings;

			$lastname = param( 'lastname', 'string', '' );
			$temp_params = array(
					'name'        => 'lastname',
					'value'       => $lastname,
					'size'        => 18,
					'label'       => T_('Last name'),
					'note'        => T_('Your real last name'),
					'placeholder' => '',
					'maxlength'   => 50,
					'class'       => 'input_text',
					'required'    => isset( $params['reg1_required'] ) ? in_array( 'lastname', array_map( 'trim', explode( ',', $params['reg1_required'] ) ) ) : false,
					'class'      => '',
					'hide_label' => false,
					'style'      => '',
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );

			$rendered_Form->text_input( $temp_params['name'], $temp_params['value'], $temp_params['size'], $temp_params['label'], $temp_params['note'], $temp_params );
			break;

		case 'Form:locale':
			global $Settings, $current_locale;

			$temp_params = array(
					'name'     => 'locale',
					'value'    => $current_locale,
					'label'    => T_('Locale'),
					'class'    => '',
					'note'     => T_('Preferred language'),
					'class'      => '',
					'required'   => isset( $params['reg1_required'] ) ? in_array( 'locale', array_map( 'trim', explode( ',', $params['reg1_required'] ) ) ) : false,
					'hide_label' => false,
					'style'      => '',
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );
			
			$rendered_Form->select_input( $temp_params['name'], $temp_params['value'], 'locale_options_return', $temp_params['label'], $temp_params );
			break;

		case 'Form:login':
			global $dummy_fields;

			$login = param( $dummy_fields['login'], 'string', '' );
			if( isset( $objects['register_user_data']['login'] ) )
			{
				$login = $objects['register_user_data']['login'];
			}
			$temp_params = array(  // Here, we make sure not to modify $params
					'name'         => $dummy_fields['login'],
					'value'        => $login,
					'size'         => 22,
					'label'        => /* TRANS: noun */ T_('Login'),
					'note'         => $params['register_use_placeholders'] ? '' : T_('Choose a username').'.',
					'placeholder'  => $params['register_use_placeholders'] ? T_('Choose a username') : '',
					'maxlength'    => 20,
					'class'        => 'input_text',
					'required'     => isset( $params['reg1_required'] ) ? in_array( 'login', array_map( 'trim', explode( ',', $params['reg1_required'] ) ) ) : false,
					'input_suffix' => ' <span id="login_status"></span><span class="help-inline"><div id="login_status_msg" class="red"></div></span>',
					'style'        => 'width:'.( $params['register_field_width'] - 2 ).'px',
					'class'      => '',
					'hide_label' => false,
					'style'      => '',
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );

			$rendered_Form->text_input( $temp_params['name'], $temp_params['value'], $temp_params['size'], $temp_params['label'], $temp_params['note'], $temp_params );
			break;

		case 'Form:password':
			global $dummy_fields;

			$temp_params = array(
					'name'         => $dummy_fields['pass1'],
					'value'        => '',
					'size'         => 18,
					'label'        => T_('Password'),
					'note'         => $params['register_use_placeholders'] ? '' : T_('Choose a password').'.',
					'placeholder'  => $params['register_use_placeholders'] ? T_('Choose a password') : '',
					'maxlength'    => 70,
					'class'        => 'input_text',
					'required'     => isset( $params['reg1_required'] ) ? in_array( 'password', array_map( 'trim', explode( ',', $params['reg1_required'] ) ) ) : false,
					'style'        => 'width:'.$params['register_field_width'].'px',
					'autocomplete' => 'off',
					'class'      => '',
					'hide_label' => false,
					'style'      => '',
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );

			$rendered_Form->password_input( $temp_params['name'], $temp_params['value'], $temp_params['size'], $temp_params['label'], $temp_params );


			$temp_params = array(
					'name'         => $dummy_fields['pass2'],
					'value'        => '',
					'size'         => 18,
					'label'        => '',
					'note'         => ( $params['register_use_placeholders'] ? '' : T_('Please type your password again').'.' ).'<div id="pass2_status" class="red"></div>',
					'placeholder'  => $params['register_use_placeholders'] ? T_('Please type your password again') : '',
					'maxlength'    => 70,
					'class'        => 'input_text',
					'required'     => true,
					'style'        => 'width:'.$params['register_field_width'].'px',
					'autocomplete' => 'off',
					'class'      => '',
					'hide_label' => false,
					'style'      => '',
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );

			$rendered_Form->password_input( $temp_params['name'], $temp_params['value'], $temp_params['size'], $temp_params['label'], $temp_params );
				
			break;

		case 'Form:submit':
			$temp_params = array(
					'name' => 'submit',
					'value' => T_('Submit'),
					'class' => 'btn btn-primary',
					'hide_label' => false,
					'style'      => '',
				);
			// Only params specified in $temp_params above will be passed to prevent unknown params transformed into input attributes!
			$temp_params = array_merge( $temp_params, array_intersect_key( $params, $temp_params ) );

			$rendered_Form->submit_input( $temp_params );
			break;

		// Item:
		case 'Item:author':
			$rendered_Item->author( array_merge( array(
					'link_text' => 'auto',		// select login or nice name automatically
				), $params ) );
			break;
		
		case 'Item:background_image_css':
			echo $rendered_Item->get_background_image_css( $params );
			break;

		case 'Item:cat_name':
			if( $item_main_Chapter = & $rendered_Item->get_main_Chapter() )
			{
				echo $item_main_Chapter->dget( 'name' );
			}
			break;

		case 'Item:categories':
			$rendered_Item->categories( array_merge( array(
					'before'          => '',  // For some reason the core has ' ' as default, which is not good for templates
					'after'           => '',  // For some reason the core has ' ' as default, which is not good for templates
				), $params ) );
			break;

		case 'Item:content_extension':
			echo $rendered_Item->content_extension( $params );
			break;

		case 'Item:content_teaser':
			echo $rendered_Item->content_teaser( $params );
			break;

		case 'Item:contents_last_updated':
		case 'Item:last_updated':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'format' => '#short_date_time',		
				), $params );
			echo $rendered_Item->get_contents_last_updated_ts( $temp_params['format'] );
			break;

		case 'Item:creation_time':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'format' => '#short_date_time',		
				), $params );
			echo $rendered_Item->get_creation_time( $temp_params['format'] );
			break;

		case 'Item:custom':
			$temp_params = array_merge( array( // Here, we make sure not to modify $params
					'field' => '',
				), $params );
			$rendered_Item->custom( $temp_params );
			break;

		case 'Item:custom_fields':
			echo $rendered_Item->get_custom_fields( $params );
			break;

		case 'Item:edit_link':
			$rendered_Item->edit_link( $params );
			break;

		case 'Item:excerpt':
			$rendered_Item->excerpt( array_merge( array(
					'before'              => '',
					'after'               => '',
					'excerpt_before_more' => ' <span class="evo_post__excerpt_more_link">',
					'excerpt_after_more'  => '</span>',
					'excerpt_more_text'   => '#more+arrow',
				), $params ) );
			break;

		case 'Item:feedback_link':
			echo $rendered_Item->get_feedback_link( $params );
			break;

		case 'Item:flag_icon':
			echo $rendered_Item->get_flag( $params );
			break;

		case 'Item:footer':
			echo $rendered_Item->footer( array_merge( array( // Here, we make sure not to modify $params
					'block_start' => '<div class="evo_post_footer">',
					'block_end'   => '</div>',
				), $params ) );
			break;

		case 'Item:history_link':
			echo $rendered_Item->get_history_link( array_merge( array(
					'link_text' => T_('View change history'),
				), $params ) );
			break;

		case 'Item:id':
			echo $rendered_Item->ID;
			break;

		case 'Item:image_url':
			echo $rendered_Item->get_image_url( $params );
			break;

		case 'Item:images':
			echo $rendered_Item->get_images( array_merge( array(
					'restrict_to_image_position' => 'teaser,teaserperm,teaserlink,aftermore', 	// 'teaser'|'teaserperm'|'teaserlink'|'aftermore'|'inline'|'cover',
																// '#teaser_all' => 'teaser,teaserperm,teaserlink',
																// '#cover_and_teaser_all' => 'cover,teaser,teaserperm,teaserlink'
					'limit'                      => 1000, // Max # of images displayed
					'before'                     => '<div>',
					'before_image'               => '<figure class="evo_image_block">',
					'before_image_classes'       => '', // Allow injecting additional classes into 'before image'
					'before_image_legend'        => '<div class="evo_image_legend">',
					'after_image_legend'         => '</div>',
					'after_image'                => '</figure>',
					'after'                      => '</div>',
					'image_size'                 => 'fit-720x500',
					'image_size_x'               => 1, // Use '2' to build 2x sized thumbnail that can be used for Retina display
					'image_sizes'                => NULL, // Simplified "sizes=" attribute for browser to select correct size from "srcset=".
																// Must be set DIFFERENTLY depending on WIDGET/CONTAINER/SKIN LAYOUT. Each time we must estimate the size the image will have on screen.
																// Sample value: (max-width: 430px) 400px, (max-width: 670px) 640px, (max-width: 991px) 720px, (max-width: 1199px) 698px, 848px
					'image_link_to'              => 'original', // Can be 'original' (image), 'single' (this post), an be URL, can be empty
					// Note: Widget MAY have set the following for same CAT navigation:
					//	'post_navigation' => 'same_category',			// Stay in the same category if Item is cross-posted
					//	'nav_target'      => $params['chapter_ID'],	// for use with 'same_category' : set the category ID as nav target
					// Note: Widget MAY have set the following for same COLL navigation:
					//	'target_blog'     => 'auto', 						// Stay in current collection if it is allowed for the Item
				), $params ) );
			break;
			
		case 'Item:issue_date':
			$rendered_Item->issue_date( $params );
			break;

		case 'Item:issue_time':
			$rendered_Item->issue_time( $params );
			break;

		case 'Item:last_touched':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'format' => '#short_date_time',		
				), $params );
			echo $rendered_Item->get_last_touched_ts( $temp_params['format'] );
			break;

		case 'Item:lastedit_user':
			$rendered_Item->lastedit_user( array_merge( array(
					'link_text' => 'auto',		// select login or nice name automatically
				), $params ) );
			break;

		case 'Item:mod_date':
			$temp_params = array_merge( array(  // Here, we make sure not to modify $params
					'format' => '#short_date_time',		
				), $params );
			echo $rendered_Item->get_mod_date( $temp_params['format'] );
			break;

		case 'Item:more_link':
			// Display "more" link to "After more" or follow-up anchor
			// WARNING: does not work if no "after more" part
			// If you want a "go from excerpt to full post, use "Item:permalink"
			echo $rendered_Item->get_more_link( array_merge( array(
					'before' => '<p class="evo_post_more_link">',
					'after'  => '</p>',
				), $params ) );
			break;

		case 'Item:page_links':
			echo $rendered_Item->get_page_links( array_merge( array(
					'separator'   => '&middot; ',
				), $params ) );
			break;

		case 'Item:permalink':
		case 'Item:permanent_link':
			$rendered_Item->permanent_link( array_merge( array(
					'text'   => '#title',
					'title'  => '',  // No tooltip by default
					// Note: Widget MAY have set the following for same CAT navigation:
					//	'post_navigation' => 'same_category',			// Stay in the same category if Item is cross-posted
					//	'nav_target'      => $params['chapter_ID'],	// for use with 'same_category' : set the category ID as nav target
					// Note: Widget MAY have set the following for same COLL navigation:
					//	'target_blog'     => 'auto', 						// Stay in current collection if it is allowed for the Item
				), $params ) );
			break;

		case 'Item:permanent_url':
			$temp_params = array_merge( array(  
					'target_blog'     => '',		
					'post_navigation' => '',		
					'nav_target'      => NULL,		
				), $params );
			echo $rendered_Item->get_item_url( $temp_params['target_blog'], $temp_params['post_navigation'], $temp_params['nav_target'] );
			break;

		case 'Item:propose_change_link':
			$rendered_Item->propose_change_link( array_merge( array(
					'text'   => T_('Propose a change'),
				), $params ) );
			break;

		case 'Item:read_status':
			$rendered_Item->display_unread_status( array_merge( array(
					'style'  => 'text',
					'before' => '<span class="evo_post_read_status">',
					'after'  => '</span>'
				), $params ) );
			break;

		case 'Item:refresh_contents_last_updated_link':
			echo $rendered_Item->get_refresh_contents_last_updated_link( $params );
			break;

		case 'Item:tags':
			$rendered_Item->tags( array_merge( array(
					'before'          => '',  // For some reason the core has '<div>... ' as default, which is not good for templates
					'after'           => '',  // For some reason the core has '</div>' as default, which is not good for templates
				), $params ) );
			break;

		case 'Item:title':
			echo $rendered_Item->dget( 'title' );
			break;

		case 'Item:visibility_status':
			if( $rendered_Item->status != 'published' )
			{
				$rendered_Item->format_status( array_merge( array(
						'template' => '<div class="evo_status evo_status__$status$ badge" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div>',
					), $params ) );
			}
			break;

		// Link:
		case 'Link:disp':
			$temp_params = array_merge( array(
					'text'           => '',
					'class'          => '',
					'max_url_length' => NULL,
				), $params );

			if( empty( $temp_params['disp'] ) )
			{
				echo '<span class="evo_param_error">['.$var.']: Missing required param "disp".</span>';
				break;
			}

			switch( $temp_params['disp'] )
			{
				case 'login':
					$source      = param( 'source', 'string', 'register form' );
					$redirect_to = param( 'redirect_to', 'url', '' );
					$return_to   = param( 'return_to', 'url', '' );

					// We are not using 
					$temp_params = array_merge( array(
							'source'             => $source,
							'redirect_to'        => $redirect_to,
							'return_to'          => $return_to,
							'force_normal_login' => false,
							'blog_ID'            => NULL,
							'blog_page'          => 'loginurl',
						), $temp_params );
	
					$disp_url = get_login_url( $temp_params['source'], $temp_params['redirect_to'], $temp_params['force_normal_login'],
							$temp_params['blog_ID'], $temp_params['blog_page'] );
					break;
				
				default:
					$temp_params = array_merge( array(
							'params' => '',
						), $temp_params );

					$disp_url = get_dispctrl_url( $temp_params['disp'], $temp_params['params'] );
			}
		
			if( $disp_url )
			{
				echo get_link_tag( $disp_url, $temp_params['text'], $temp_params['class'], $temp_params['max_url_length'] );
			}
			else
			{
				echo '<span class="evo_param_error">['.$var.']: disp "'.$temp_params['disp'].'" is not recognized.</span>';
			}
			break;

		case 'Plugin':
			$rendered_Plugin->SkinTag( $params );
			break;
		
		// Others
		default:
			switch( $scope )
			{
				case 'echo':
					// Print param var value, No need check this because all done above:
					echo $params[ $param_name ];
					break;

				default:
					// Unknown template var:
					$match_found = false;
			}
	}
	$r = ob_get_clean();

	if( $match_found )
	{
		return $r;
	}
	else
	{	// Display error for not recognized variable:
		return '<span class="evo_param_error">['.$var.'] is not recognized.</span>';
	}
}


/**
 * Validate Template code for uniqueness. This will add a numeric suffix if the specified template code is already in use.
 *
 * @param string Template code to validate
 * @param integer ID of template
 * @param string The name of the template code column
 * @param string The name of the template ID column
 * @param string The name of the template table to use
 * @return string Unique template code
 */
function unique_template_code( $code, $ID = 0, $db_code_fieldname = 'tpl_code', $db_ID_fieldname = 'tpl_ID', $db_table = 'T_templates' )
{
	global $DB, $Messages;
	
	load_funcs( 'locales/_charset.funcs.php' );

	// Convert code:
	$code = strtolower( replace_special_chars( $code, NULL, false, '_' ) );
	$base = preg_replace( '/_[0-9]+$/', '', $code );

	// CHECK FOR UNIQUENESS:
	// Find all occurrences of code-number in the DB:
	$SQL = new SQL( 'Find all occurrences of template code "'.$base.'..."' );
	$SQL->SELECT( $db_code_fieldname.', '.$db_ID_fieldname );
	$SQL->FROM( $db_table );
	$SQL->WHERE( $db_code_fieldname." REGEXP '^".$base."(_[0-9]+)?$'" );

	$exact_match = false;
	$highest_number = 0;
	$use_existing_number = NULL;

	foreach( $DB->get_results( $SQL->get(), ARRAY_A ) as $row )
	{
		$existing_code = $row[$db_code_fieldname];
		if( ( $existing_code == $code ) && ( $row[$db_ID_fieldname] != $ID ) )
		{	// Specified code already in use by another template, we'll have to change the number.
			$exact_match = true;
		}
		if( preg_match( '/_([0-9]+)$/', $existing_code, $matches ) )
		{	// This template code already has a number, we extract it:
			$existing_number = (int)$matches[1];

			if( ! isset( $use_existing_number ) && $row[$db_ID_fieldname] == $ID )
			{	// if there is a numbered entry for the current ID, use this:
				$use_existing_number = $existing_number;
			}

			if( $existing_number > $highest_number )
			{	// This is the new high
				$highest_number = $existing_number;
			}
		}
	}

	if( $exact_match )
	{	// We got an exact (existing) match, we need to change the number:
		$number = $use_existing_number ? $use_existing_number : ( $highest_number + 1 );
		$code = $base.'_'.$number;
	}

	return $code;
}


/**
 * Get list of context available to templates
 */
function get_template_contexts()
{
	return array(
		'custom1', 'custom2', 'custom3',
		'content_list_master', 'content_list_item', 'content_list_category',
		'content_block', 'item_details', 'item_content', 'registration_master', 'registration' );
}
?>
