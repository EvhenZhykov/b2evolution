<?php
/**
 * Displays blog properties form
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );
?>
<script type="text/javascript">
<!--
function update_IDs( ids, text, notrailingslash )
{
	//replace = document.createTextNode( 'lala' );

	if( !notrailingslash )
	{
		if( text.length && text.charAt( text.length-1 ) != '/' ) text=text+'/';
	}

	i = 0;
	while( document.getElementById( ids+String(i) ) )
	{
		if( document.getElementById( ids+String(i) ).hasChildNodes() )
			document.getElementById( ids+String(i) ).firstChild.data = text;
		else
			document.getElementById( ids+String(i) ).appendChild( document.createTextNode( text ) );

		i++;
	};
}
//-->
</script>

<form action="blogs.php" class="fform" method="post">
	<input type="hidden" name="action" value="<?php echo $next_action ?>" />
	<input type="hidden" name="blog" value="<?php echo $blog; ?>" />

	<fieldset>
		<legend><?php echo T_('General parameters') ?></legend>
		<?php
			form_text( 'blog_name', $edited_Blog->get( 'name' ), 50, T_('Full Name'), T_('Will be displayed on top of the blog.') );
			form_text( 'blog_shortname', $edited_Blog->get( 'shortname', 'formvalue' ), 12, T_('Short Name'), T_('Will be used in selection menus and throughout the admin interface.') );
			form_select( 'blog_locale', $edited_Blog->get( 'locale' ), 'locale_options', T_('Main Locale'), T_('Determines the language of the navigation links on the blog.') );
		?>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('Access parameters') ?></legend>

		<?php
			if( $Settings->get('default_blog_ID') && ($Settings->get('default_blog_ID') != $edited_Blog->ID) )
			{
				if( $default_Blog = $BlogCache->get_by_ID($Settings->get('default_blog_ID'), false) )
				{ // Default blog exists
					$defblog = $default_Blog->dget('shortname');
				}
			}
			form_radio( 'blog_access_type', $edited_Blog->get( 'access_type' ),
					array(
						array( 'default',
										T_('Default blog on index.php'),
										'<span id="blogbaseurl0">'.$edited_Blog->get('baseurl').'</span>index.php'.( isset($defblog)
											? '  ['. /* TRANS: current default blog */ T_('Current default is:').' '.$defblog.']'
											: '' )
						),
						array( 'index.php',
										T_('Other blog through index.php'),
										'<span id="blogbaseurl1">'.$edited_Blog->get('baseurl').'</span>index.php'.( $Settings->get('links_extrapath')
											? '/'.$edited_Blog->get( 'stub' )
											: '?blog='.$edited_Blog->ID)
						),
						array( 'stub',
										T_('Other blog through stub file (Advanced)'),
										'<span id="blogbaseurl2">'.$edited_Blog->get('baseurl').'</span><span id="blog_stub_js0">'.$edited_Blog->dget( 'stub' ).'</span>',
										'<div class="label"><label for="blog_stub">'.T_('Stub name').':</label></div>'
										.'<div class="input"><input type="text" name="blog_stub" id="blog_stub" size="20" maxlength="'.$maxlength_urlname_stub.'" value="'.$edited_Blog->dget( 'stub', 'formvalue' ).'" onkeyup="update_IDs( \'blog_stub_js\', this.value, true )" onfocus="update_IDs( \'blog_stub_js\', this.value, true ); document.getElementsByName(\'blog_access_type\')[2].checked = true;" /></div>'
										.'<div class="notes">'.T_("You MUST create a stub file for this to work or handle it accordingly on the Webserver (like Apache's mod_rewrite).").'</div>',
										'onclick="document.getElementById( \'blog_stub\' ).focus()"'

						),
					), T_('Preferred access type'), true );

			form_radio( 'blog_siteurl_type', $blog_siteurl_type,
					array(
						array( 'relative',
										T_('relative to baseurl').':',
										'',
										' <span class="nobr"><code>'.$baseurl.'/</code>
											<input type="text" id="blog_siteurl_relative" name="blog_siteurl_relative" size="40" maxlength="120" value="'.format_to_output( $blog_siteurl_relative, 'formvalue' ).'" onkeyup="update_IDs( \'blogbaseurl\', \''.$baseurl.'/\'+this.value );" onfocus="document.getElementsByName(\'blog_siteurl_type\')[0].checked=true; update_IDs( \'blogbaseurl\', \''.$baseurl.'/\'+this.value );" /></span>',
										'onclick="document.getElementById( \'blog_siteurl_relative\' ).focus();"'
						),
						array( 'absolute',
										T_('absolute URL').':',
										'',
										'<input type="text" id="blog_siteurl_absolute" name="blog_siteurl_absolute" size="40" maxlength="120" value="'.format_to_output( $blog_siteurl_absolute, 'formvalue' ).'" onkeyup="update_IDs( \'blogbaseurl\', this.value );" onfocus="document.getElementsByName(\'blog_siteurl_type\')[1].checked=true; update_IDs( \'blogbaseurl\', this.value );" />',
										'onclick="document.getElementById( \'blog_siteurl_absolute\' ).focus();"'
						)
					),
					T_('Blog Folder URL'), true, T_('No trailing slash. (If you don\'t know, leave this field empty.)') );

			form_text( 'blog_urlname', $edited_Blog->get( 'urlname' ), 20, T_('URL blog name'), T_('Used in URLs to identify this blog.'), $maxlength_urlname_stub );
		?>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('Default display options') ?></legend>
		<?php
			form_select( 'blog_default_skin', $edited_Blog->get( 'default_skin' ), 'skin_options', T_('Default skin') , T_('This is the default skin that will be used to display this blog.') );

			form_checkbox( 'blog_force_skin', 1-$edited_Blog->get( 'force_skin' ), T_('Allow skin switching'), T_('Users will be able to select another skin to view the blog (and their prefered skin will be saved in a cookie).') );

			form_checkbox( 'blog_disp_bloglist', $edited_Blog->get( 'disp_bloglist' ), T_('Display public blog list'), T_('Check this if you want to display the list of all blogs on your blog page (if your skin supports this).') );

			form_checkbox( 'blog_in_bloglist', $edited_Blog->get( 'in_bloglist' ), T_('Include in public blog list'), T_('Check this if you want to this blog to be displayed in the list of all public blogs.') );

			form_select_object( 'blog_links_blog_ID', $edited_Blog->get( 'links_blog_ID' ), $BlogCache, T_('Default linkblog'), T_('Will be displayed next to this blog (if your skin supports this).'), true );
		?>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('Description') ?></legend>
		<?php
			form_text( 'blog_tagline', $edited_Blog->get( 'tagline' ), 50, T_('Tagline'), T_('This is diplayed under the blog name on the blog template.'), 250 );

			form_textarea( 'blog_longdesc', $edited_Blog->get( 'longdesc' ), 8, T_('Long Description'), T_('This is displayed on the blog template.'), 50, 'large' );

			form_text( 'blog_description', $edited_Blog->get( 'description' ), 60, T_('Short Description'), T_('This is is used in meta tag description and RSS feeds. NO HTML!'), 250, 'large' );

			form_text( 'blog_keywords', $edited_Blog->get( 'keywords' ), 60, T_('Keywords'), T_('This is is used in meta tag keywords. NO HTML!'), 250, 'large' );

			form_textarea( 'blog_notes', $edited_Blog->get( 'notes' ), 8, T_('Notes'), T_('Additional info.'), 50, 'large' );
		?>
	</fieldset>

	<?php form_submit(); ?>

</form>

<?php /*<script type="text/javascript">
<!--
	for( var i = 0; i < document.getElementsByName( 'blog_siteurl_type' ).length; i++ )
	{
		if( document.getElementsByName( 'blog_siteurl_type' )[i].checked )
			document.getElementsByName( 'blog_siteurl_type' )[i].click();
	}
	document.getElementById( 'blog_name' ).focus();
// -->
</script> */ ?>
