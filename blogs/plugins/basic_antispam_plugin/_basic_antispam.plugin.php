<?php
/**
 * This file implements the basic Antispam plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package plugins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER - {@link http://daniel.hahler.de/}
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Basic Antispam Plugin
 *
 * This plugin doublechecks referers/referrers for Hit logging and trackbacks.
 *
 * @todo Ideas:
 *  - forbid cloned comments (same content) (on the same entry or all entries)
 *  - detect same/similar URLs in a short period (also look at author name: if it differs, it's more likely to be spam)
 */
class basic_antispam_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */
	var $name = 'Basic Antispam';
	var $code = '';
	var $priority = 60;
	var $version = 'CVS $Revision$';
	var $author = 'The b2evo Group';
	var $help_url = '';


	/**
	 * Constructor
	 */
	function basic_antispam_plugin()
	{
		$this->short_desc = T_('Basic antispam methods');
		$this->long_desc = T_('This plugin provides basic methods to reduce spam.');
	}


	function GetDefaultSettings()
	{
		return array(
				'check_dupes' => array(
					'type' => 'checkbox',
					'label' => T_('Check for duplicate feedback'),
					'note' => T_('Check this to check comments and trackback for duplicate content.'),
					'defaultvalue' => '1',
				),
				'nofollow_for_hours' => array(
					'type' => 'integer',
					'label' => T_('Apply rel="nofollow"'),
					'note'=>T_('hours. For how long should rel="nofollow" be applied to comment links? (0 means never, -1 means always)'),
					'defaultvalue' => '48', // use "nofollow" for 2 days
					'size' => 5,
				),
			);
	}


	/**
	 * - Check if our hostname is linked in the URL of the trackback.
	 * - Check for duplicate trackbacks.
	 */
	function BeforeTrackbackInsert( & $params )
	{
		if( $this->is_duplicate_comment( $params['Comment'] ) )
		{
			$this->msg( T_('The trackback seems to be a duplicate.'), 'error' );
			return;
		}

		if( ! $this->is_referer_linking_us( $params['Comment']->author_url, '' ) )
		{ // Our hostname is not linked by the permanent url of the refering entry:
			$this->msg( T_('Could not find link to us in your URL!'), 'error' );
		}
	}


	/**
	 * Check for duplicate comments.
	 */
	function BeforeCommentFormInsert( & $params )
	{
		if( $this->is_duplicate_comment( $params['Comment'] ) )
		{
			$this->msg( T_('The comment seems to be a duplicate.'), 'error' );
		}
	}


	/**
	 * If we use "makelink", handle nofollow rel attrib.
	 *
	 * @uses basic_antispam_plugin::apply_nofollow()
	 */
	function FilterCommentAuthor( & $params )
	{
		if( ! $params['makelink'] )
		{
			return false;
		}

		$this->apply_nofollow( $params['data'], $params['Comment'] );
	}


	/**
	 * Handle nofollow in author URL (if it's made clickable)
	 *
	 * @uses basic_antispam_plugin::FilterCommentAuthor()
	 */
	function FilterCommentAuthorUrl( & $params )
	{
		$this->FilterCommentAuthor( $params );
	}


	/**
	 * Handle nofollow rel attrib in comment content.
	 *
	 * @uses basic_antispam_plugin::FilterCommentAuthor()
	 */
	function FilterCommentContent( & $params )
	{
		$this->apply_nofollow( $params['data'], $params['Comment'] );
	}


	/**
	 * Do we want to apply rel="nofollow" tag?
	 *
	 * @return boolean
	 */
	function apply_nofollow( & $data, $Comment )
	{
		global $localtimenow;

		$hours = $this->Settings->get('nofollow_for_hours'); // 0=never, -1 always, otherwise for x hours

		if( $hours == 0 )
		{ // "never"
			return;
		}

		pre_dump( date( 'H:i:s', mysql2timestamp( $Comment->date ) ), date( 'H:i:s', ( $localtimenow - $hours*3600 ) ) );
		if( $hours > 0 // -1 is "always"
			&& mysql2timestamp( $Comment->date ) <= ( $localtimenow - $hours*3600 ) )
		{
			return;
		}

		$data = preg_replace_callback( '~(<a\s)([^>]+)>~i', create_function( '$m', '
				if( preg_match( \'~\brel=([\\\'"])(.*?)\1~\', $m[2], $match ) )
				{ // there is already a rel attrib:
					$rel_values = explode( " ", $match[2] );

					if( ! in_array( \'nofollow\', $rel_values ) )
					{
						$rel_values[] = \'nofollow\';
					}

					return $m[1]
						.preg_replace(
							\'~\brel=([\\\'"]).*?\1~\',
							\'rel=$1\'.implode( " ", $rel_values ).\'$1\',
							$m[2] )
						.">";
				}
				else
				{
					return $m[1].$m[2].\' rel="nofollow">\';
				}' ), $data );
		pre_dump( $data );
		#$data = preg_replace( '~<a~', '<a rel="nofollow"', $data );
	}


	/**
	 * Check if the deprecated hit_doublecheck_referer setting is set and then
	 * do not disable the AppendHitLog event. Also removes the old setting.
	 */
	function AfterInstall()
	{
		global $Settings;

		if( ! $Settings->get('hit_doublecheck_referer') )
		{ // old general settings, "transform it"
			$this->disable_event( 'AppendHitLog' );
		}

		$Settings->delete('hit_doublecheck_referer');
		$Settings->dbupdate();
	}


	/**
	 * Check if our Host+URI is in the referred page, preferrably through
	 * {@link register_shutdown_function()}.
	 *
	 * @return boolean true, if we handle {@link Hit::record_the_hit() recording of the Hit} ourself
	 */
	function AppendHitLog( & $params )
	{
		global $debug_no_register_shutdown;

		$Hit = & $params['Hit'];

		if( $Hit->referer_type != 'referer' )
		{
			return false;
		}

		if( empty($debug_no_register_shutdown) && function_exists( 'register_shutdown_function' ) )
		{ // register it as a shutdown function, because it will be slow!
			$this->debug_log( 'AppendHitLog: loading referering page.. (through register_shutdown_function())' );

			register_shutdown_function( array( &$this, 'double_check_referer' ), $Hit->referer ); // this will also call Hit::record_the_hit()
		}
		else
		{
			// flush now, so that the meat of the page will get shown before it tries to check back against the refering URL.
			flush();

			$this->debug_log( 'AppendHitLog: loading referering page..' );

			$this->double_check_referer($Hit->referer); // this will also call Hit::record_the_hit()
		}

		return true; // we handle recording
	}


	/**
	 * This function gets called (as a {@link register_shutdown_function() shutdown function}, if possible) and checks
	 * if the referering URL's content includes the current URL - if not it is probably spam!
	 *
	 * On success, this methods records the hit.
	 *
	 * @uses Hit::record_the_hit()
	 */
	function double_check_referer( $referer )
	{
		global $Hit, $ReqURI;

		if( $this->is_referer_linking_us( $referer, $ReqURI ) )
		{
			$Hit->record_the_hit();
		}

		return;
	}


	/**
	 * Check the content of a given URL (referer), if the requested URI (with different hostname variations)
	 * is present.
	 *
	 * @todo Use DB cache to avoid checking the same page again and again! (Plugin DB table)
	 *
	 * @param string
	 * @param string URI to append to matching pattern for hostnames
	 * @return boolean
	 */
	function is_referer_linking_us( $referer, $uri )
	{
		global $misc_inc_path, $lib_subdir, $ReqHost;

		if( empty($referer) )
		{
			return false;
		}

		if( ! ($fp = @fopen( $referer, 'r' )) )
		{ // could not access referring page
			$this->debug_log( 'is_referer_linking_us(): could not access &laquo;'.$referer.'&raquo;' );

			return false;
		}

		socket_set_timeout($fp, 5); // timeout after 5 seconds
		// Get the refering page's content
		$content_ref_page = '';
		$bytes_read = 0;
		while( ($l_byte = fgetc($fp)) !== false )
		{
			$content_ref_page .= $l_byte;
			if( ++$bytes_read > 512000 )
			{ // do not pull more than 500kb of data!
				break;
			}
		}

		/**
		 * IDNA converter class
		 */
		require_once $misc_inc_path.'ext/_idna_convert.class.php';
		$IDNA = new Net_IDNA_php4();

		// Build the search pattern:
		// We match for basically for 'href="[SERVER][URI]', where [SERVER] is a list of possible hosts (especially IDNA)
		$search_pattern = '~\shref=["\']?https?://(';
		$possible_hosts = array( $_SERVER['HTTP_HOST'] );
		if( $_SERVER['SERVER_NAME'] != $_SERVER['HTTP_HOST'] )
		{
			$possible_hosts[] = $_SERVER['SERVER_NAME'];
		}
		$search_pattern_hosts = array();
		foreach( $possible_hosts as $l_host )
		{
			if( preg_match( '~^([^.]+\.)(.*?)([^.]+\.[^.]+)$~', $l_host, $match ) )
			{ // we have subdomains in this hostname
				if( stristr( $match[1], 'www' ) )
				{ // search also for hostname without 'www.'
					$search_pattern_hosts[] = $match[2].$match[3];
				}
			}
			$search_pattern_hosts[] = $l_host;
		}
		$search_pattern_hosts = array_unique($search_pattern_hosts);
		foreach( $search_pattern_hosts as $l_host )
		{ // add IDN, because this is probably linked
			$l_idn_host = $IDNA->decode( $l_host ); // the decoded puny-code ("xn--..") name (utf8)

			if( $l_idn_host != $l_host )
			{
				$search_pattern_hosts[] = $l_idn_host;
			}
		}

		// add hosts to pattern, preg_quoted
		for( $i = 0, $n = count($search_pattern_hosts); $i < $n; $i++ )
		{
			$search_pattern_hosts[$i] = preg_quote( $search_pattern_hosts[$i], '~' );
		}
		$search_pattern .= implode( '|', $search_pattern_hosts ).')'.$uri.'~i';


		// TODO: handle encoding of the refering page (mbstrings), if we have decoded base name, $content_ref_page must be utf8
		if( preg_match( $search_pattern, $content_ref_page ) )
		{
			$this->debug_log( 'is_referer_linking_us(): found current URL in page ('.bytesreadable($bytes_read).' read)' );

			return true;
		}
		else
		{
			if( strpos( $referer, $ReqHost ) === 0 && ! empty($uri) )
			{ // Referer is the same host.. just search for $uri
				if( strpos( $content_ref_page, $uri ) !== false )
				{
					$this->debug_log( 'is_referer_linking_us(): found current URI in page ('.bytesreadable($bytes_read).' read)' );

					return true;
				}
			}
			$this->debug_log( 'is_referer_linking_us(): '.sprintf('did not find &laquo;%s&raquo; in &laquo;%s&raquo; (%s bytes read).', $search_pattern, $referer, bytesreadable($bytes_read) ) );

			return false;
		}
	}


	/**
	 * Simple check for duplicate comment/content from same author
	 *
	 * @param Comment
	 */
	function is_duplicate_comment( $Comment )
	{
		global $DB;

		if( ! $this->Settings->get('check_dupes') )
		{
			return false;
		}

		$sql = '
				SELECT comment_ID
				  FROM T_comments
				 WHERE comment_post_ID = '.$Comment->Item->ID.'
				   AND ';
		if( isset($Comment->author_User) )
		{ // registered user:
			$sql .= 'comment_author_ID = '.$Comment->author_User->ID;
		}
		else
		{ // visitor (also trackback):
			$sql_ors = array();
			if( ! empty($Comment->author) )
			{
				$sql_ors[] = 'comment_author = '.$DB->quote($Comment->author);
			}
			if( ! empty($Comment->author_email) )
			{
				$sql_ors[] = 'comment_author_email = '.$DB->quote($Comment->author_email);
			}
			if( ! empty($Comment->author_url) )
			{
				$sql_ors[] = 'comment_author_url = '.$DB->quote($Comment->author_url);
			}

			if( ! empty($sql_ors) )
			{
				$sql .= '( '.implode( ' OR ', $sql_ors ).' )';
			}
		}

		$sql .= ' AND comment_content = '.$DB->quote($Comment->content).' LIMIT 1';

		return $DB->get_var( $sql, 0, 0, 'Checking for duplicate feedback content.' );
	}

}


/*
 * $Log$
 * Revision 1.4  2006/05/02 01:27:55  blueyed
 * Moved nofollow handling to basic antispam plugin; added Filter events to Comment class
 *
 * Revision 1.3  2006/05/01 05:20:38  blueyed
 * Check for duplicate content in comments/trackback.
 *
 * Revision 1.2  2006/05/01 04:25:07  blueyed
 * Normalization
 *
 * Revision 1.1  2006/04/29 23:11:23  blueyed
 * Added basic_antispam_plugin; Moved double-check-referers there; added check, if trackback links to us
 *
 */
?>