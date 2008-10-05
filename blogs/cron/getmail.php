<?php
/**
 * pop3-2-b2 mail to blog
 *
 * modified for 2.4.1 by Stephan Knauss. Contact me by PM in {@link http://forums.b2evolution.net/} (user stephankn)
 * or send a mail to stephankn at users.sourceforge.net
 * 
 * Uses MIME E-mail message parser classes written by Manuel Lemos: ({@link http://www.phpclasses.org/browse/package/3169.html})
 *
 * This script could be called with a parameter "test" to specify what
 * should be done and what level of debug output to generate:
 * <ul>
 * <li>level 0: default. Process everything, no debug output, no html (called by cronjob)</li>
 * <li>level 1: Test only connection to server, do not process messages</li>
 * <li>level 2: additionally process messages, but do not post</li>
 * <li>level 3: do everything with extended verbosity</li>
 * </ul>
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @author Stephan Knauss
 * @author Tilman Blumenbach
 * 
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 * This file built upon code from original b2 - http://cafelog.com/
 * @package htsrv
 *
 * @todo check different encodings. only tested with iso-8859-1
 * @todo try more exotic email clients like mobile phones
 * @todo tested and working with thunderbird (text, html, signed), yahoo mail (text, html), outlook webmail, K800i
 * @internal tblue> maybe we should add normal pop3 support (again)?
 */

/**
 * load b2evolution configuration
 */
require_once dirname( __FILE__ ) . '/../conf/_config.php';

require_once $inc_path . '_main.inc.php';
load_class('items/model/_itemlist.class.php');
load_funcs('files/model/_file.funcs.php');

/**
 * needed by the mime_parser_class
 */
require_once( 'rfc822_addresses.php' );
require_once( 'mime_parser.php' );

if ( !$Settings -> get( 'eblog_enabled' ) )
{
	echo T_( 'Blog by email feature is not enabled.' );
	debug_info();
	exit();
}

param( 'test', 'integer', 0 );

/**
 * Subject of the current email message
 *
 * @global string $subject
 */
$subject = '';

/**
 * post date of current message
 *
 * @global string $post_date
 */	
$post_date = '';

/**
 * message content of current email that is going to be posted
 *
 * @global string $content
 */
$content = '';

/**#@+
 * define colour constants for messages
 */
define( 'INFO', 'black' );
define( 'SUCCESS', 'green' );
define( 'WARNING', 'orange' );
define( 'ERROR', 'red' );
/**#@-*/

// if it's not called by a logged in user override test settings
if ( !isset( $current_User ) || !$current_User -> check_perm( 'options', 'edit', true ) )
{
	$test = 0;
}

/**
 * Whether to do real posting.
 *
 * It is set to true if the setting eblog_test_mode is set to false *and*
 * the test parameter is not set to 2.
 */
$do_real_posting = (!$Settings->get( 'eblog_test_mode' ) && $test != 2);
if ( ! $do_real_posting )
{
	echo_message( T_('You configured test mode in the settings or set $test to 2. Nothing will be posted to the database/mediastore nor will your inbox be altered.'), WARNING, 0, true );
}

if ( $test > 0 )
{
	//error_reporting (0);
	
	/**
	 * @todo I don't find a header to include for this popup window. There should exist one in b2evo. So right now no valid HTML
	 */
	$page_title = T_( 'Blog by email' );
	echo '<html><head><title>' . $page_title . '</title></head><body>';
}

/**
 * Print out a debugging message with optional HTML color added.
 *
 * This function only outputs any additional HTML (colors, <br />) if
 * $test is greater than 0.
 *
 * @global integer The test level
 * @param  string $strmessage The message to print
 * @param  string $color optional colour so use
 * @param  integer $level optional level to limit output to that level
 * @param  bool $newline insert a newline after message
 */
function echo_message( $strmessage , $color = '', $level = 0, $newline = false )
{
	global $test;

	if ( $level <= $test )
	{
		if ( $test > 0 && $color )
		{
			echo '<font color="'.$color.'">';
		}

		echo $strmessage;

		if ( $test > 0 && $color )
		{
			echo '</font>';
		}
		
		if ( $newline )
		{
			if ( $test > 0 )
			{
				echo '<br />';
			}
			echo "\n";
		}
	}
}

/**
 * provide sys_get_temp_dir for older versions of PHP.
 * 
 * code posted on php.net by minghong at gmail dot com
 * Based on {@link http://www.phpit.net/article/creating-zip-tar-archives-dynamically-php/2/}
 *
 * @return string path to system temporary directory
 */ 
if ( !function_exists( 'sys_get_temp_dir' ) )
{
	function sys_get_temp_dir()
	{
		// Try to get from environment variable
		if ( !empty( $_ENV['TMP'] ) )
		{
			return realpath( $_ENV['TMP'] );
		}
		else if ( !empty( $_ENV['TMPDIR'] ) )
		{
			return realpath( $_ENV['TMPDIR'] );
		}
		else if ( !empty( $_ENV['TEMP'] ) )
		{
			return realpath( $_ENV['TEMP'] );
		}

		// Detect by creating a temporary file
		else
		{
			// Try to use system's temporary directory
			// as random name shouldn't exist
			$temp_file = tempnam( md5( uniqid( rand(), true ) ), '' );
			if ( $temp_file )
			{
				$temp_dir = realpath( dirname( $temp_file ) );
				unlink( $temp_file );
				return $temp_dir;
			}
			else
			{
				return false;
			}
		}
	}
}


/**
 * Create a new directory with unique name.
 * This creates a new directory below the given path with the given prefix and a random number.
 *
 * @param  string $dir base path to new directory
 * @param  string $prefix prefix random number with this
 * @param  integer $mode permissions to use
 * @return string path to created directory
 */
function tempdir( $dir, $prefix = 'tmp', $mode = 0700 )
{
	if ( substr( $dir, -1 ) != '/' ) $dir .= '/';

	do
	{
		$path = $dir . $prefix . mt_rand();
	} while ( !mkdir( $path, $mode ) );

	return $path;
}



/**
 * process Header information like subject and date of a mail
 *
 * @global string The subject of the current message (write)
 * @global string The post date of the current message (write)
 * @global object b2evo settings (read)
 * @global integer The test level (read)
 * @param  array $header header as set by mime_parser_class::Analyze()
 * @return bool true if valid subject prefix is detected
 */
function processHeader( &$header )
{
	// write to these globals
	global $subject, $post_date;

	// read these globals
	global $Settings, $test;

	$subject = $header['Subject'];
	$ddate = $header['Date'];

	$prefix = $Settings->get( 'eblog_subject_prefix' );
	echo_message( T_( 'Subject' ) . ': ' . $subject, INFO, 3, true );

	if (substr($subject, 0, strlen($prefix)) !== $prefix)
	{
		echo_message( '&#x2718; ' . T_( 'The subject prefix is not ' ) . '"' . $prefix . '"', WARNING, 2, true );
		return false;
	}

	// of the form '20 Mar 2002 20:32:37'
	if (!preg_match('#^(.{3}, )?(\d{2}) (.{3}) (\d{4}) (\d{2}):(\d{2}):(\d{2})#', $ddate, $match))
	{
		echo_message(T_('Could not parse date header!'), ERROR, 0, $testtrue);
		//pre_dump($ddate);
		return false;
	}

	$dmonths = array(
		'Jan' => 1,
		'Feb' => 2,
		'Mar' => 3,
		'Apr' => 4,
		'May' => 5,
		'Jun' => 6,
		'Jul' => 7,
		'Aug' => 8,
		'Sep' => 9,
		'Oct' => 10,
		'Nov' => 11,
		'Dec' => 12,
	);

	$ddate_H = $match[5];
	$ddate_i = $match[6];
	$ddate_s = $match[7];

	$ddate_m = $dmonths[$match[3]];
	$ddate_d = $match[2];
	$ddate_Y = $match[4];

	$ddate_U = mktime( $ddate_H, $ddate_i, $ddate_s, $ddate_m, $ddate_d, $ddate_Y );
	$post_date = date( 'Y-m-d H:i:s', $ddate_U );

	return true;
}



/**
 * process attachments by saving into media directory and optionally creating image tag in post
 *
 * @global string message content that is optionally manipulated by adding image tags
 * @global bool do we really post?
 * @global object global b2evo settings
 * @param  array $mailAttachments array containing path to attachment files
 * @param  string $mediadir path to media directory of blog as seen by file system
 * @param  string $media_url url to media directory as seen by user
 * @param  bool $add_img_tags should img tags be added (instead of adding a normal link)
 * @return bool true for sucessfull execution
 */
function processAttachments( $mailAttachments, $mediadir, $media_url, $add_img_tags = true )
{
	global $content;
	global $do_real_posting;
	global $Settings;
	
	$return = true;

	echo_message( T_( 'Processing attachments' ), INFO, 3, true );
	
	foreach( $mailAttachments as $attachment )
	{
		$filename = strtolower( $attachment['FileName'] );
		if ( $filename == '' )
		{
			$filename = tempnam( $mediadir, 'upload' ) . '.' . $attachment['SubType'];
			echo_message( '&#x279C; ' . T_( 'Attachment without name. Using ' ) . htmlspecialchars( $filename ), WARNING, 2, true );
		}
		$filename = preg_replace( '/[^a-z0-9\-_.]/', '-', $filename );

		// Check valid filename/extension: (includes check for locked filenames)
		if ( $error_filename = validate_filename( $filename, false ) )
		{
			echo_message( '&#x2718; ' . T_( 'Invalid filename' ).': '.$error_filename, WARNING, 2, true );
			$return = false; // return: at least one error. try with next attachment
			continue;
		}

		// if file exists count up a number
		$cnt = 0;
		$prename = substr( $filename, 0, strrpos( $filename, '.' ) ).'-';
		$sufname = strrchr( $filename, '.' );
		while ( file_exists( $mediadir . $filename ) )
		{
			$filename = $prename.$cnt.$sufname;
			echo_message( '&#x2718; ' . T_( 'file already exists. Changing filename to: ' ) . $filename , WARNING, 2, true );
			++$cnt;
		}

		if ( $do_real_posting )
		{
			echo_message( '&#x279C; ' . T_( 'Saving file to: ') . htmlspecialchars( $mediadir . $filename  ), INFO, 3, true );
			if ( !rename( $attachment['DataFile'], $mediadir . $filename ) )
			{
				echo_message( '&#x2718; ' . T_( 'Problem saving upload to ') . htmlspecialchars( $mediadir . $filename ), WARNING, 2, true );
				$return = false; // return: at least one error. try with next attachment
				continue;
			}
	
			// chmod uploaded file:
			$chmod = $Settings -> get( 'fm_default_chmod_file' );
			@chmod( $mediadir . $filename, octdec( $chmod ) );
		}

		$imginfo = @getimagesize($mediadir.$filename);
		echo_message(T_('Attachment is an image: ').(is_array($imginfo) ? T_('yes') : T_('no')), INFO, 3, true);

		$content .= "\n";
		if (is_array($imginfo) && $add_img_tags)
		{
			$content .= '<img src="'.$media_url.$filename.'" '.$imginfo[3].' />';
		}
		else
		{
			$content .= '<a href="'.$media_url.$filename.'">'.basename($filename).'</a>';
		}
		$content .= "\n";
	}

	return $return;
}

/**
 * look inside message to get title for posting.
 * 
 * The message could contain a xml-tag <code><title>sample title</title></code> to specify a title for the posting.
 * If not tag is found there could be a global $post_default_title containing a global default title.
 * If none of these is found then the specified alternate title line is used.
 *
 * @param string $content message to search for title tag
 * @param string $alternate_title use this string if no title tag is found
 * @return string title of posting
 * 
 * @see $post_default_title
 */
function get_post_title( $content, $alternate_title )
{
	$title = xmlrpc_getposttitle( $content );
	if ( $title == '' )
	{
		$title = $alternate_title;
	}
	
	return $title;
}

// MAIN ROUTINE
switch ( $Settings -> get( 'eblog_method' ) )
{
	case 'pop3':
		// tblue> Since the setting is commented out in _features.form.php,
		//	we change it here because the user won't be able to. When we
		// 	will have more than one supported method in the future, the
		// 	user will be able to change the method on the Features tab anyway,
		// 	so this won't be needed anymore. 
		echo_message( T_( 'The POP3 retrieval method is no longer supported. The method has automatically been changed to "POP3 through IMAP extension".' ), WARNING, 0, true );
		$Settings->set( 'eblog_method', 'pop3a' );
		$Settings->dbupdate();
		// try to continue here (or better break??). 		break;

	case 'pop3a':
		// --------------------------------------------------------------------
		// eblog_method = POP3 through IMAP extension (default)
		// --------------------------------------------------------------------
		if ( ! extension_loaded( 'imap' ) )
		{
			echo_message( T_( 'The php_imap extension is not available to php on this server. Please configure a different email retrieval method on the Features tab.' ), ERROR, 0, true );
			exit;
		}
		echo_message( T_( 'Connecting and authenticating to mail server' ), INFO, 1, true );

		// Prepare the connection string
		$port = $Settings -> get( 'eblog_server_port' ) ? $Settings -> get( 'eblog_server_port' ) : '110';

		// @TODO: add setting to configure SSL/TLS
		$mailserver = '{' . $Settings -> get( 'eblog_server_host' ) . ':' . $port . '/pop3/notls}INBOX';

		// Connect to mail server
		$mbox = @imap_open( $mailserver, $Settings -> get( 'eblog_username' ), $Settings -> get( 'eblog_password' ) );
		if ( ! $mbox )
		{
			echo_message( T_( 'Connection failed: ' ) . imap_last_error(), ERROR, 0, true );
			exit();
		}
		@imap_errors();

		echo_message( '&#x2714; ' . T_( 'Success' ), SUCCESS, 1, true );
		if ( $test == 1 )
		{
			echo_message( T_( 'All Tests completed' ), INFO, 1, true );
			imap_close( $mbox );
			exit();
		}


		// Read messages from server
		echo_message( T_( 'Reading messages from server' ), INFO, 2, true );
		$imap_obj = imap_check( $mbox );
		echo_message( ' &#x279C; ' . $imap_obj -> Nmsgs . ' ' . T_( 'messages' ), INFO, 2, true );

		$delMsgs = 0;
		for ( $index = 1; $index <= $imap_obj -> Nmsgs; $index++ )
		{
			echo_message( '<b>' . T_( 'Message' ) . ' #'.$index . ':</b>', INFO, 2, true );
				
			$strbody = '';
			$hasAttachment = false;
				
			// save mail to disk because with attachments could take up much RAM
			if (!($tmpMIME = tempnam( sys_get_temp_dir(), 'b2evoMail' )))
			{
				echo_message( T_('Could not create temporary file.'), ERROR, 0, true );
				continue;
			}
			imap_savebody( $mbox, $tmpMIME, $index );
				
			$tmpDirMIME = tempdir( sys_get_temp_dir(), 'b2evo' );
			$mimeParser = new mime_parser_class;
			$mimeParser -> mbox = 0; // Set to 0 for parsing a single message file
			$mimeParser -> decode_bodies = 1;
			$mimeParser -> ignore_syntax_errors = 1;
			$mimeParser -> extract_addresses = 0;
			$MIMEparameters = array(
				'File' => $tmpMIME,
				'SaveBody' => $tmpDirMIME, // Save the message body parts to a directory
				'SkipBody' => 1, // Do not retrieve or save message body parts
			);
				
			if ( !$mimeParser -> Decode( $MIMEparameters, $decodedMIME ) )
			{
				echo_message( T_( 'MIME message decoding error: ' ) . $mimeParser -> error . T_(' at position ' ) . $mimeParser -> error_position, ERROR, 0, true );
				rmdir_r( $tmpDirMIME );
				unlink( $tmpMIME );
				continue;
			}
			else
			{
				echo_message( '&#x2714; ' . T_( 'MIME message decoding successful.'), INFO, 3, true );
				if ( ! $mimeParser -> Analyze( $decodedMIME[0], $parsedMIME ) )
				{
					echo_message( T_('MIME message analyse error: ') . $mimeParser -> error, ERROR, 0, true );
					// var_dump($parsedMIME);
					rmdir_r( $tmpDirMIME );
					unlink( $tmpMIME );
					continue;
				}

				// the following helps me debugging
				//pre_dump($decodedMIME[0], $parsedMIME);

				if (!processHeader($parsedMIME))
				{
					rmdir_r( $tmpDirMIME );
					unlink($tmpMIME);
					continue;
				}

				// TODO: handle type == "message" recursively

				// mail is html
				if ( $parsedMIME['Type'] == 'html' ){
					foreach ( $parsedMIME['Alternative'] as $alternative ){
						if ( $alternative['Type'] == 'text' ){
							echo_message( T_( 'HTML alternative message part saved as ' ) . $alternative['DataFile'], INFO, 3, true );
							$strbody = imap_qprint( file_get_contents( $alternative['DataFile'] ) );
							break; // stop after first alternative
						}
					}
				}

				// mail is plain text
				elseif ( $parsedMIME['Type'] == 'text' )
				{
					echo_message( T_( 'Plain-text message part saved as ' ) . $parsedMIME['DataFile'], INFO, 3, true );
					$strbody = imap_qprint( file_get_contents( $parsedMIME['DataFile'] ) );
				}

				// Check for attachments
				if ( isset( $parsedMIME['Attachments'] ) && count($parsedMIME['Attachments']) )
				{
					$hasAttachment = true;
					foreach( $parsedMIME['Attachments'] as $attachment )
					{
						echo_message( T_( 'Attachment: ' ) . $attachment['FileName'] . T_( ' stored as ' ) . $attachment['DataFile'], INFO, 3, true );
					}
				}

				$warning_count = count( $mimeParser->warnings ); 
				if ( $warning_count > 0 )
				{
					echo_message( '&#x2718; ' . $warning_count . T_( ' warnings during decode: ' ), WARNING, 2, true );
					foreach ($mimeParser->warnings as $k => $v)
					{
						echo_message( '&#x2718; ' . T_( 'Warning: ' ) . $v . T_( ' at position ' ) . $k, WARNING, 3, true );
					}
				}
			}
			unlink( $tmpMIME );
				
			// var_dump($strbody);
			// process body. First fix different line-endings (dos, mac, unix), remove double newlines
			$strbody = str_replace( array("\r", "\n\n"), "\n", $strbody );
				
			$a_body = explode( "\n", $strbody, 2 );

			// tblue> splitting only into 2 parts allows colons in the user PW
			$a_authentication = explode( ':', $a_body[0], 2 );
			$content = trim( $a_body[1] );
				
			echo_message( T_( 'Message content:' ) . ' <code>' . htmlspecialchars( $content ) . '</code>', INFO, 3, true );
				
			$user_login = trim( $a_authentication[0] );
			$user_pass = @trim( $a_authentication[1] );
				
			echo_message( T_( 'Authenticating user' ) . ': ' . $user_login, INFO, 3, true );
			// authenticate user
			if ( !user_pass_ok( $user_login, $user_pass ) )
			{
				echo_message( T_( 'Authentication failed for user ' ) . htmlspecialchars( $user_login ), ERROR, 0, true );
				echo_message( '&#x2718; ' . T_( 'Wrong login or password.' ) . ' ' . T_( 'First line of text in email must be in the format "username:password"' ), ERROR, 3, true );
				rmdir_r( $tmpDirMIME );
				continue;
			}
			else
			{
				echo_message( '&#x2714; ' . T_( 'Success' ), SUCCESS, 3, true );
			}
				
			$subject = trim( substr($subject, strlen($Settings->get( 'eblog_subject_prefix' ))) );
				
			// remove content after terminator
			$eblog_terminator = $Settings -> get( 'eblog_body_terminator' );
			if ( !empty( $eblog_terminator ) &&
				 ($os_terminator = strpos( $content, $eblog_terminator )) !== false)
			{
				$content = substr( $content, 0, $os_terminator );
			}
				
			// check_html_sanity needs local user set.
			$UserCache = & get_Cache( 'UserCache' );
			$current_User = & $UserCache -> get_by_login( $user_login );

			$post_title = get_post_title( $content, $subject );
				
			if ( ! ( $post_category = xmlrpc_getpostcategory( $content ) ) )
			{
				$post_category = $Settings -> get( 'eblog_default_category' );
			}
			echo_message( T_( 'Category ID' ) . ': ' . htmlspecialchars( $post_category ), INFO, 3, true );
				
			$content = xmlrpc_removepostdata( $content );
			$blog_ID = get_catblog( $post_category ); // TODO: should not die, if cat does not exist!
			echo_message( T_( 'Blog ID' ) . ': ' . $blog_ID, INFO, 3, true );
				
			$BlogCache = & get_Cache( 'BlogCache' );
			$Blog = $BlogCache -> get_by_ID( $blog_ID, false, false );
				
			if ( empty( $Blog ) )
			{
				echo_message( T_( 'Blog not found: ' ) . htmlspecialchars( $blog_ID ), ERROR, 0, true );
				rmdir_r( $tmpDirMIME );
				continue;
			}
				
				
			// Check permission:
			echo_message( sprintf( T_( 'Checking permissions for user &laquo;%s&raquo; to post to Blog #%d' ), $user_login, $blog_ID ), INFO, 3, true );
			if ( !$current_User -> check_perm( 'blog_post!published', 'edit', false, $blog_ID )
			|| ( $hasAttachment && !$current_User -> check_perm( 'files', 'add', false ) )
			)
			{
				echo_message( T_( 'Permission denied' ), ERROR, 0, true );
				rmdir_r( $tmpDirMIME );
				continue;
			}
			else
			{
				echo_message( '&#x2714; ' . T_( 'Pass' ), SUCCESS, 3, true );
			}
				
			// handle attachments
			if ( $hasAttachment )
			{
				$mediadir = $Blog->get_media_dir();
				if ( $mediadir )
				{
					processAttachments( $parsedMIME['Attachments'], $mediadir, $Blog->get_media_url(), $Settings->get('eblog_add_imgtag') );
				}
				else
				{
					echo_message( T_( 'Unable to access media directory. No attachments processed' ), ERROR, 0, true );
				}
			}
				
			// CHECK and FORMAT content
			$post_title = check_html_sanity( trim( $post_title ), 'posting', false );
			$content = check_html_sanity( trim( $content ), 'posting', $Settings -> get( 'AutoBR' ) );

			if ( ( $error = $Messages->get_string( T_( 'Cannot post, please correct these errors:' ), '', 'error' ) ) )
			{
				echo_message( $error, ERROR, 0, true );
				$Messages->clear( 'error' );
				rmdir_r( $tmpDirMIME );
				continue;
			}

			if ( $do_real_posting )
			{
				// INSERT NEW POST INTO DB:
				$edited_Item = & new Item();
	
				$post_ID = $edited_Item -> insert( $current_User -> ID, $post_title, $content, $post_date, $post_category, array(), 'published', $current_User -> locale );
	
				// Execute or schedule notifications & pings:
				$edited_Item -> handle_post_processing();
			}

			echo_message( '&#x2714; ' . T_( 'Message posting successfull.' ), SUCCESS, 2, true );
			echo_message( '&#x279C; ' . T_( 'Post title: ' ) . htmlspecialchars( $post_title ), INFO, 3, true );
			echo_message( '&#x279C; ' . T_( 'Post content: ' ) . htmlspecialchars( $content ), INFO, 3, true );
				
			rmdir_r( $tmpDirMIME );
				
			echo_message( T_( 'Marking message for deletion' ) . ': '.$index, INFO, 3, true );
			imap_delete( $mbox, $index );
			++$delMsgs;
		}

		if ( $do_real_posting )
		{
			imap_expunge( $mbox );
			echo_message( sprintf(T_( 'Deleted %d processed message(s) from inbox' ), $delMsgs), INFO, 3, true );
		}

		imap_close( $mbox );

		break;


	default:
		echo T_( 'Blog by email feature not configured' );
		break;
}

if ( $test > 0 )
{
	/**
	 * @todo I don't find a footer to include in this popup. b2evo should include one...
	 */
	echo '</body>';
}

?>
