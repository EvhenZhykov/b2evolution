<?php 
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file built upon code from original b2 - http://cafelog.com/
 */
if( $use_textile ) require_once (dirname(__FILE__).'/_functions_textile.php');

/*
 * bpost_create(-)
 *
 * Create a new post
 * This funtion has to handle all needed DB dependencies!
 *
 * fplanque: created
 */
function bpost_create( 
	$author_user_ID, 							// Author
	$post_title,
	$post_content,
	$post_timestamp,
	$main_cat_ID = 1,									// Main cat ID
	$extra_cat_IDs = array(),			// Table of extra cats
	$post_status = 'published',
	$post_lang = 'en',
	$post_trackbacks = '',
	$autobr = 0,									// No AutoBR has been used by default
	$pingsdone = true,
	$post_url = '',
	$post_comments = 'open' )
{
	global $tableposts, $tablepostcats, $query, $querycount;
	global $use_bbcode, $use_gmcode, $use_smartquotes, $use_smilies;

	// Handle the flags:
	$post_flags = array();
	if( $pingsdone ) $post_flags[] = 'pingsdone';
	$post_flags[] = 'html';
	if( $use_bbcode ) $post_flags[] = 'bbcode';
	if( $use_gmcode ) $post_flags[] = 'gmcode';
	if( $use_smartquotes ) $post_flags[] = 'smartquotes';
	if( $use_smilies ) $post_flags[] = 'smileys';
	
	// make sure main cat is in extracat list and there are no duplicates
	$extra_cat_IDs[] = $main_cat_ID;
	$extra_cat_IDs = array_unique( $extra_cat_IDs );

	// TODO: START TRANSACTION

	$query = "INSERT INTO $tableposts( post_author, post_title, post_content, post_date, post_category,  post_status, post_lang, post_trackbacks, post_autobr, post_flags, post_wordcount, post_comments ) ";
	$query .= "VALUES( $author_user_ID, '".addslashes($post_title)."', '".addslashes($post_content)."',	'$post_timestamp', $main_cat_ID,  '$post_status', '$post_lang', '".addslashes($post_url)."', $autobr, '".implode(',',$post_flags)."', ".bpost_count_words($post_content).", '".addslashes($post_comments)."' )";
	$querycount++;
	$result = mysql_query($query);
	if( !$result ) return 0;
	$post_ID = mysql_insert_id();
	//echo "post ID:".$post_ID;

	// insert new extracats
	$query = "INSERT INTO $tablepostcats( postcat_post_ID, postcat_cat_ID ) VALUES ";
	foreach( $extra_cat_IDs as $extra_cat_ID )
	{
		//echo "extracat: $extracat_ID <br />";
		$query .= "( $post_ID, $extra_cat_ID ),";
	}
	$query = substr( $query, 0, strlen( $query ) - 1 );
	$querycount++;
	mysql_query($query);
	if( !$result ) return 0;

	// TODO: END TRANSACTION

	return $post_ID;
}


/*
 * bpost_update(-)
 *
 * Update a post
 * This funtion has to handle all needed DB dependencies!
 *
 * fplanque: created
 */
function bpost_update( 
	$post_ID,
	$post_title,
	$post_content,
	$post_timestamp = '',
	$main_cat_ID = 1,							// Main cat ID
	$extra_cat_IDs = array(),			// Table of extra cats
	$post_status = 'published',
	$post_lang = 'en',
	$post_trackbacks = '',
	$autobr = 0,									// No AutoBR has been used by default
	$pingsdone = true,
	$post_url = '',
	$post_comments = 'open' )
{
	global $tableposts, $tablepostcats, $query, $querycount;
	global $use_bbcode, $use_gmcode, $use_smartquotes, $use_smilies;

	// Handle the flags:
	$post_flags = array();
	if( $pingsdone ) $post_flags[] = 'pingsdone';
	$post_flags[] = 'html';
	if( $use_bbcode ) $post_flags[] = 'bbcode';
	if( $use_gmcode ) $post_flags[] = 'gmcode';
	if( $use_smartquotes ) $post_flags[] = 'smartquotes';
	if( $use_smilies ) $post_flags[] = 'smileys';

	// make sure main cat is in extracat list and there are no duplicates
	$extra_cat_IDs[] = $main_cat_ID;
	$extra_cat_IDs = array_unique( $extra_cat_IDs );

	// TODO: START TRANSACTION
	$query = "UPDATE $tableposts SET ";
	$query .= "post_title = '".addslashes($post_title)."', ";
	$query .= "post_trackbacks = '".addslashes($post_url)."', ";		// temporay use of post_trackbacks
	$query .= "post_content = '".addslashes($post_content)."', ";
	if( !empty($post_timestamp) )	$query .= "post_date = '$post_timestamp', ";
	$query .= "post_category = $main_cat_ID, ";
	$query .= "post_status = '$post_status', ";
	$query .= "post_lang = '$post_lang', ";
	// $query .= "post_trackbacks = '$post_trackbacks', ";
	$query .= "post_autobr = $autobr, ";
	$query .= "post_flags = '".implode(',',$post_flags)."', ";
	$query .= "post_wordcount = ".bpost_count_words($post_content).", ";
	$query .= "post_comments = '".addslashes($post_comments)."' ";
	$query .= "WHERE ID = $post_ID";
	// echo $query;
	$querycount++;
	$result = mysql_query($query);
	if( !$result ) return 0;

	// delete previous extracats
	$query = "DELETE FROM $tablepostcats WHERE postcat_post_ID = $post_ID";

	$querycount++;
	$result = mysql_query($query);
	if( !$result ) return 0;

	// insert new extracats
	$query = "INSERT INTO $tablepostcats( postcat_post_ID, postcat_cat_ID ) VALUES ";
	foreach( $extra_cat_IDs as $extra_cat_ID )
	{
		//echo "extracat: $extracat_ID <br />";
		$query .= "( $post_ID, $extra_cat_ID ),";
	}
	$query = substr( $query, 0, strlen( $query ) - 1 );

	$querycount++;
	mysql_query($query);
	if( !$result ) return 0;

	// TODO: END TRANSACTION

	return 1;	// success
}

/*
 * bpost_update_status(-)
 *
 * Update a post's status
 * This funtion has to handle all needed DB dependencies!
 *
 * fplanque: created
 */
function bpost_update_status( 
	$post_ID,
	$post_status = 'published',
	$pingsdone = true,
	$post_timestamp = '' )
{
	global $tableposts, $tablepostcats, $query, $querycount;
	global $use_bbcode, $use_gmcode, $use_smartquotes, $use_smilies;

	// Handle the flags:
	$post_flags = array();
	if( $pingsdone ) $post_flags[] = 'pingsdone';
	$post_flags[] = 'html';
	if( $use_bbcode ) $post_flags[] = 'bbcode';
	if( $use_gmcode ) $post_flags[] = 'gmcode';
	if( $use_smartquotes ) $post_flags[] = 'smartquotes';
	if( $use_smilies ) $post_flags[] = 'smileys';

	$query = "UPDATE $tableposts SET ";
	if( !empty($post_timestamp) )	$query .= "post_date = '$post_timestamp', ";
	$query .= "post_status = '$post_status', ";
	$query .= "post_flags = '".implode(',',$post_flags)."' ";
	$query .= "WHERE ID = $post_ID";
	// echo $query;
	$querycount++;
	$result = mysql_query($query);
	if( !$result ) return 0;

	return 1;	// success
}


/*
 * bpost_delete(-)
 *
 * Delete a post
 * This funtion has to handle all needed DB dependencies!
 *
 * fplanque: created
 */
function bpost_delete( $post_ID )
{
	global $tableposts, $tablepostcats, $tablecomments, $query, $querycount;

	// TODO: START TRANSACTION

	// delete extracats
	$query = "DELETE FROM $tablepostcats WHERE postcat_post_ID = $post_ID";
	$querycount++;
	$result = mysql_query($query);
	if( !$result ) return 0;

	// delete comments
	$query = "DELETE FROM $tablecomments WHERE comment_post_ID = $post_ID";
	$querycount++;
	$result = mysql_query($query);
	if( !$result ) return 0;

	// delete post
	$query = "DELETE FROM $tableposts WHERE ID = $post_ID";
	$querycount++;
	$result = mysql_query($query);
	if( !$result ) return 0;

	// TODO: END TRANSACTION

	return 1;	// success

}


/* 
 * get_lastpostdate(-) 
 */
function get_lastpostdate( $blog = 1, $show_statuses = '' ) 
{
	global $localtimenow, $postdata;
	
	// echo 'getting last post date';
	$LastPostList = & new ItemList( $blog, $show_statuses, '', '', '', '', array(), '', 'DESC', 'date', 1, '','', '', '', '', '', '', 1, 'posts', '', 'now' );

	if( $LastItem = $LastPostList->get_item() )
	{
		// echo 'we have a last item';
		$last_postdata = $LastPostList->get_postdata();	// will set $postdata;
		$lastpostdate = $postdata['Date'];
	}
	else
	{
		// echo 'we have no last item';
		$lastpostdate = date("Y-m-d H:i:s", $localtimenow);
	}
	// echo $lastpostdate;
	return($lastpostdate);
}


/*
 * get_postdata(-)
 *
 * if global $postdata was not set il will be
 */
function get_postdata($postid) 
{
	global $postdata, $tableusers, $tablesettings, $tablecategories, $tableposts, $tablecomments, $querycount, $show_statuses;

	if( !empty($postdata) && $postdata['ID'] == $postid )
	{	// We are asking for postdata of current post in memory! (we're in the b2 loop)
		// Already in memory! This will be the case when generating permalink at display
		// (but not when sending trackbacks!)
		// echo "*** Accessing post data in memory! ***<br />\n";
		return($postdata);
	}

	// echo "*** Loading post data! ***<br>\n";
	// We have to load the post
	$sql = "SELECT ID, post_author, post_date, post_status, post_lang, post_content, post_title, post_trackbacks, post_category, post_autobr, post_flags, post_wordcount, post_comments, cat_blog_ID FROM $tableposts INNER JOIN $tablecategories ON post_category = cat_ID WHERE ID = $postid";
	// Restrict to the statuses we want to show:
	// echo $show_statuses;
	$sql .= ' AND '.statuses_where_clause( $show_statuses );

	// echo $sql;

	$result = mysql_query($sql) or die("Your SQL query: <br />$sql<br /><br />MySQL said:<br />".mysql_error());
	$querycount++;
	if (mysql_num_rows($result)) 
	{
		$myrow = mysql_fetch_object($result);
		$mypostdata = array (
			'ID' => $myrow->ID, 
			'Author_ID' => $myrow->post_author, 
			'Date' => $myrow->post_date, 
			'Status' => $myrow->post_status, 
			'Lang' =>  $myrow->post_lang, 
			'Content' => $myrow->post_content, 
			'Title' => $myrow->post_title, 
			'Url' => $myrow->post_trackbacks, 
			'Category' => $myrow->post_category, 
			'AutoBR' => $myrow->post_autobr,
			'Flags' => explode( ',', $myrow->post_flags ),
			'Wordcount' => $myrow->post_wordcount,
			'comments' => $myrow->post_comments, 
			'Blog' => $myrow->cat_blog_ID,
			);

		// Caching is particularly useful when displaying a single post and you call single_post_title several times
		if( !isset( $postdata ) ) $postdata = $mypostdata;	// Will save time, next time :)

		return($mypostdata);
	} 
	
	return false;
}




/**
 * get_the_title(-)
 *
 * @deprecated
 */
function get_the_title() 
{
	global $id,$postdata;
	$output = trim(stripslashes($postdata['Title']));
	return($output);
}




/* 
 * TEMPLATE FUNCTIONS
 */


/*****
 * Post tags 
 *****/



/*
 * the_ID(-)
 *
 *
 * @deprecated deprecated by {@link DataObject::ID()}
 *
 */
function the_ID() 
{
	global $id;
	echo $id;
}

/*
 * the_status(-)
 *
 * Display post status
 *
 * @deprecated deprecated by {@link Item::scope()}
 */
function the_status( $raw = true ) 
{
	global $post_statuses, $postdata;
	$status = $postdata['Status'];
	if( $raw )
		echo $status;
	else
		echo T_($post_statuses[$status]);
}


/*
 * the_lang(-)
 *
 * Display post language code
 *
 * @deprecated deprecated by {@link Item::lang()}
 */
function the_lang() 
{
	global $postdata;
	echo $postdata['Lang'];
}

/*
 * the_language(-)
 *
 * Display post language name
 *
 * @deprecated deprecated by {@link Item::language()}
 */
function the_language() 
{
	global $postdata, $languages;
	$post_lang = $postdata['Lang'];
	echo $languages[ $post_lang ];
}

/*
 * the_wordcount(-)
 * Display the number of words in the post
 *
 *
 * @deprecated deprecated by {@link Item::wordcount()}
 */
function the_wordcount()
{
	global $postdata;
	echo $postdata['Wordcount'];
}

/*
 * the_title(-)
 *
 * Display post title
 * 03.10.10 - Updated function to allow for silent operations
 *
 * @deprecated deprecated by {@link Item::title()}
 */
function the_title( 
	$before='',						// HTML/text to be displayed before title
	$after='', 						// HTML/text to be displayed after title
	$add_link = true, 		// Added link to this title?
	$format = 'htmlbody',	// Format to use (example: "htmlbody" or "xml")
	$disp = true )				// Display output?
{
	global $postdata; 
	
	$title = get_the_title();
	$url = trim($postdata['Url']);

	if( empty($title) && $add_link )
	{
		$title = $url;
	}

	if( empty($title) )
		return;

	if( $add_link && (!empty($url)) )
	{
		$title = $before.'<a href="'.$url.'">'.$title.'</a>'.$after;
	}
	else
	{
		$title = $before.$title.$after;
	}

	//	ADDED: 03.10.08 by Travis S. :Support for silent operation
	$return_str = format_to_output( $title, $format );
	if( $disp == true )
		echo $return_str;
	else
		return $return_str;
}


/*
 * the_link(-)
 *
 * Display post link
 */
function the_link( $before='', $after='', $format = 'htmlbody' ) 
{
	global $postdata; 
	
	$url = trim($postdata['Url']);
	
	if( empty($url) )
	{
		return false;
	}

	$link = $before.'<a href="'.$url.'">'.$url.'</a>'.$after;

	echo format_to_output( $link, $format );
}



/*
 * single_post_title(-)
 *
 * fplanque: 0.8.3: changed defaults
 */
function single_post_title($prefix = '#', $display = 'htmlhead' ) 
{
	if( $prefix == '#' ) $prefix = ' '.T_('Post details').': ';

	global $p;
	if (intval($p)) {
		$post_data = get_postdata($p);
		$title = stripslashes($post_data['Title']);
		if ($display) 
		{
			echo format_to_output( $prefix.$title, $display );
		}
		else 
		{
			return $title;
		}
	}
}


/**
 * the_content(-)
 *
 * @deprecated deprecated by {@link Item::content()}
 */
function the_content( 
	$more_link_text='#', 
	$stripteaser=0, 
	$more_file='', 
	$more_anchor='#', 
	$before_more_link = '#', 
	$after_more_link = '#', 
	$format = 'htmlbody', 
	$cut = 0,
	$dispmore = '#', 	// 1 to display 'more' text, # for url parameter
	$disppage = '#' ) // page number to display specific page, # for url parameter
{
	global $use_textile;
	global $id, $postdata, $pages, $multipage, $numpages;
	global $preview, $use_extra_path_info;
	
	// echo $format,'-',$cut,'-',$dispmore,'-',$disppage;
	
	if( $more_link_text == '#' ) 
	{	// TRANS: this is the default text for the extended post "more" link
		$more_link_text = '=> '.T_('Read more!');
	}

	if( $more_anchor == '#' ) 
	{	// TRANS: this is the default text displayed once the more link has been activated
		$more_anchor = '['.T_('More:').']';
	}

	if( $before_more_link == '#' ) 
		$before_more_link = '<p class="bMore">';

	if( $after_more_link == '#' ) 
		$after_more_link = '</p>';
	
	if( $dispmore === '#' )
	{
		global $more;
		$dispmore = $more;
	}

	if( $disppage === '#' )
	{
		global $page;
		$disppage = $page;
	}
	if( $disppage > $numpages ) $disppage = $numpages;	
	// echo 'Using: dmore=', $dispmore, ' dpage=', $disppage;

	$output = '';
	if ($more_file != '') 
		$file = $more_file;
	else
		$file = get_bloginfo('blogurl');

	$content = $pages[$disppage-1];
	$content = explode('<!--more-->', $content);

	if ((preg_match('/<!--noteaser-->/', $postdata['Content']) && ((!$multipage) || ($disppage==1))))
		$stripteaser=1;
	$teaser=$content[0];
	if (($dispmore) && ($stripteaser))
	{	// We don't want to repeat the teaser:
		$teaser='';
	}
	$output .= $teaser;

	if (count($content)>1) 
	{
		if ($dispmore) 
		{	// Viewer has already asked for more
			if( !empty($more_anchor) ) $output .= $before_more_link;
			$output .= '<a id="more'.$id.'" name="more'.$id.'"></a>'.$more_anchor;
			if( !empty($more_anchor) ) $output .= $after_more_link;
			$output .= $content[1];
		} 
		else 
		{ // We are offering to read more
			$more_link = gen_permalink( $file, $id, 'id', 'single', 1 );
			$output .= $before_more_link.'<a href="'.$more_link.'#more'.$id.'">'.$more_link_text.'</a>'.$after_more_link;
		}
	}
	if ($preview) 
	{ // preview fix for javascript bug with foreign languages
		$output =  preg_replace('/\%u([0-9A-F]{4,4})/e',  "'&#'.base_convert('\\1',16,10).';'", $output);
	}


	$content = $output;



	if( $use_textile ) $content = textile( $content );

	$content = format_to_output( $content, $format );
	
	if( ($format == 'xml') && $cut )
	{	// Let's cut this down...
		$blah = explode(' ', $content);
		if (count($blah) > $cut) 
		{
			for ($i=0; $i<$cut; $i++) 
			{
				$excerpt .= $blah[$i].' ';
			}
			$content = $excerpt . '...';
		}
	}
	echo $content;
}




/*
 * link_pages(-)
 * vegarg: small bug when using $more_file fixed
 */
function link_pages($before='<br />', $after='<br />', $next_or_number='number', $nextpagelink='#', $previouspagelink='#', $pagelink='%', $more_file='') 
{
	global $id,$page,$numpages, $multipage, $more;
	global $blogfilename;

	if( $nextpagelink == '#' ) $nextpagelink = T_('Next page');
	if( $previouspagelink == '#' ) $previouspagelink = T_('Previous page');

	if ($more_file != '') 
		$file = $more_file;
	else
		$file = get_bloginfo('blogurl');

	if(($multipage)) { // && ($more)) {
		echo $before;
		if ($next_or_number=='number') 
		{
			for ($i = 1; $i < ($numpages+1); $i = $i + 1) 
			{
				$j=str_replace('%',"$i",$pagelink);
				echo " ";
				if (($i != $page) || ((!$more) && ($page==1)))
					echo '<a href="'.$file.'?p='.$id.'&amp;more=1&amp;page='.$i.'">';
				echo $j;
				if (($i != $page) || ((!$more) && ($page==1)))
					echo '</a>';
			}
		} 
		else
		{
			$i=$page-1;
			if ($i)
				echo ' <a href="'.$file.'?p='.$id.'&amp;page='.$i.'">'.$previouspagelink.'</a>';
			$i=$page+1;
			if ($i<=$numpages)
				echo ' <a href="'.$file.'?p='.$id.'&amp;page='.$i.'">'.$nextpagelink.'</a>';
		}
		echo $after;
	}
}


/*
 * previous_post(-)
 *
 *
 */
function previous_post($format='%', $previous='#', $title='yes', $in_same_cat='no', $limitprev=1, $excluded_categories='') 
{
	if( $previous == '#' ) $previous = T_('Previous post') . ': ';

	global $tableposts, $id, $postdata, $siteurl, $blogfilename, $querycount;
	global $p, $posts, $posts_per_page, $s;

	if(($p) || ($posts==1)) 
	{
		
		$current_post_date = $postdata['Date'];
		$current_category = $postdata['Category'];

		$sqlcat = '';
		if ($in_same_cat != 'no') {
			$sqlcat = " AND post_category='$current_category' ";
		}

		$sql_exclude_cats = '';
		if (!empty($excluded_categories)) {
			$blah = explode('and', $excluded_categories);
			foreach($blah as $category) {
				$category = intval($category);
				$sql_exclude_cats .= " AND post_category != $category";
			}
		}

		$limitprev--;
		$sql = "SELECT ID,post_title FROM $tableposts WHERE post_date < '$current_post_date' AND post_category > 0 $sqlcat $sql_exclude_cats ORDER BY post_date DESC LIMIT $limitprev,1";

		$query = @mysql_query($sql);
		$querycount++;
		if (($query) && (mysql_num_rows($query))) {
			$p_info = mysql_fetch_object($query);
			$p_title = $p_info->post_title;
			$p_id = $p_info->ID;
			$string = '<a href="'.get_bloginfo('blogurl').'?p='.$p_id.'&amp;more=1&amp;c=1">'.$previous;
			if (!($title!='yes')) {
				$string .= stripslashes($p_title);
			}
			$string .= '</a>';
			$format = str_replace('%',$string,$format);
			echo $format;
		}
	}
}


/*
 * next_post(-)
 *
 *
 */
function next_post($format='%', $next='#', $title='yes', $in_same_cat='no', $limitnext=1, $excluded_categories='') 
{
	if( $next == '#' ) $next = T_('Next post') . ': ';

	global $tableposts, $p, $posts, $id, $postdata, $siteurl, $blogfilename, $querycount, $localtimenow;
	global $time_difference;
	if(($p) || ($posts==1)) 
	{
		
		$current_post_date = $postdata['Date'];
		$current_category = $postdata['Category'];
		$sqlcat = '';
		if ($in_same_cat != 'no') 
		{
			$sqlcat = " AND post_category='$current_category' ";
		}

		$sql_exclude_cats = '';
		if (!empty($excluded_categories)) {
			$blah = explode('and', $excluded_categories);
			foreach($blah as $category) {
				$category = intval($category);
				$sql_exclude_cats .= " AND post_category != $category";
			}
		}

		$now = date('Y-m-d H:i:s', $localtimenow );

		$limitnext--;
		$sql = "SELECT ID,post_title FROM $tableposts WHERE post_date > '$current_post_date' AND post_date < '$now' AND post_category > 0 $sqlcat $sql_exclude_cats ORDER BY post_date ASC LIMIT $limitnext,1";

		$query = @mysql_query($sql);
		$querycount++;
		if (($query) && (mysql_num_rows($query))) {
			$p_info = mysql_fetch_object($query);
			$p_title = $p_info->post_title;
			$p_id = $p_info->ID;
			$string = '<a href="'.get_bloginfo('blogurl').'?p='.$p_id.'&amp;more=1&amp;c=1">'.$next;
			if ($title=='yes') {
				$string .= stripslashes($p_title);
			}
			$string .= '</a>';
			$format = str_replace('%',$string,$format);
			echo $format;
		}
	}
}


/*
 * next_posts(-)
 *
 * original by cfactor at cooltux.org
 * fplanque: removed relying on querystring.
 */
function next_posts($max_page = 0, $page='' )
{
	global $p, $paged, $what_to_show;
	if (empty($p) && ($what_to_show == 'paged')) 
	{
		if (!$paged) $paged = 1;
		$nextpage = intval($paged) + 1;
		if (!$max_page || $max_page >= $nextpage) 
		{
			echo regenerate_url( 'paged', 'paged='.$nextpage, $page );
		}
	}
}



/*
 * previous_posts(-)
 *
 * fplanque: reduced to the max!
 */
function previous_posts( $page='' ) 
{
	global $p, $paged, $what_to_show;
	if (empty($p) && ($what_to_show == 'paged'))
	{
		$nextpage = intval($paged) - 1;
		if ($nextpage < 1) $nextpage = 1;
		echo regenerate_url( 'paged', 'paged='.$nextpage, $page );
	}
} 


/*
 * next_posts_link(-)
 *
 *
 */
function next_posts_link($label='#', $max_page=0, $page='') 
{
	if( $label == '#' ) $label = T_('Next Page').' >>';

	global $p, $paged, $result, $request, $posts_per_page, $what_to_show;
	if ($what_to_show == 'paged') 
	{
		global $MainList;
		if (!$max_page)	$max_page = $MainList->get_max_paged();
		if (!$paged) $paged = 1;
		$nextpage = intval($paged) + 1;
		if (empty($p) && (empty($paged) || $nextpage <= $max_page)) 
		{
			echo '<a href="';
			echo next_posts($max_page, $page);
			echo '">'. htmlspecialchars($label) .'</a>';
		}
	}
}



/*
 * previous_posts_link(-)
 *
 *
 */
function previous_posts_link($label='#', $page='') 
{
	if( $label == '#' ) $label = '<< '.T_('Previous Page');

	global $p, $paged, $what_to_show;
	if (empty($p)  && ($paged > 1) && ($what_to_show == 'paged')) 
	{
		echo '<a href="';
		echo previous_posts( $page );
		echo '">'.  htmlspecialchars($label) .'</a>';
	}
}



/*
 * posts_nav_link(-)
 *
 *
 */
function posts_nav_link($sep=' :: ', $prelabel='#', $nxtlabel='#', $page='') 
{
	global $request, $p, $what_to_show;
	if( !empty( $request ) && empty($p) && ($what_to_show == 'paged')) 
	{
		global $MainList;
		$max_paged = $MainList->get_max_paged();
		if( $max_paged > 1 ) 
		{
			previous_posts_link( $prelabel, $page );
			echo htmlspecialchars($sep);
			next_posts_link( $nxtlabel, $max_paged, $page );
		}
	}
}

/*****
 * // Post tags 
 *****/




/*****
 * Date/Time tags 
 *****/
 
/**
 * the_date(-)
 *
 * @deprecated deprecated by {@link ItemList::date_if_changed()}
 */
function the_date($d='', $before='', $after='', $echo = 1) 
{
	global $id, $postdata, $day, $previousday, $newday;
	$the_date = '';
	if ($day != $previousday) 
	{
		$the_date .= $before;
		if ($d=='') {
			$the_date .= mysql2date( locale_datefmt(), $postdata['Date']);
		} else {
			$the_date .= mysql2date( $d, $postdata['Date']);
		}
		$the_date .= $after;
		$previousday = $day;
	}
	if ($echo) {
		echo $the_date;
	} else {
		return $the_date;
	}
}

/**
 * the_time(-)
 *
 *
 * @deprecated deprecated by {@link Item::time()} / {@link Item::date()}
 * 
 */
function the_time($d='', $echo = 1, $useGM = 0)
{
	global $id,$postdata;
	if ($d=='') 
	{
		$the_time = mysql2date( locale_timefmt(), $postdata['Date'], $useGM);
	} else {
		$the_time = mysql2date( $d, $postdata['Date'], $useGM);
	}

	if ($echo) 
	{
		echo $the_time;
	} else {
		return $the_time;
	}
}

/*
 * the_weekday(-)
 *
 *
 */
function the_weekday() 
{
	global $weekday,$id,$postdata;
	$the_weekday = T_($weekday[mysql2date('w', $postdata['Date'])]);
	echo $the_weekday;
}

/*
 * the_weekday_date(-)
 *
 *
 */
function the_weekday_date($before='',$after='') 
{
	global $weekday,$id,$postdata,$day,$previousweekday;
	$the_weekday_date = '';
	if ($day != $previousweekday) {
		$the_weekday_date .= $before;
		$the_weekday_date .= T_($weekday[mysql2date('w', $postdata['Date'])]);
		$the_weekday_date .= $after;
		$previousweekday = $day;
	}

	echo $the_weekday_date;
}

/*****
 * // Date/Time tags 
 *****/

/*****
 * Author tags 
 *****/

/*
 * the_author(-)
 *
 * @deprecated deprecated by {@link User::prefered_name()}
 */
function the_author( $format = 'htmlbody' ) 
{
	global $authordata;
	switch( $authordata['user_idmode'] )
	{
		case 'nickname':
			$author = $authordata['user_nickname'];
			break;
			
		case 'login':
			$author = $authordata['user_login'];
			break;
			
		case 'firstname':
			$author = $authordata['user_firstname'];
			break;
			
		case 'lastname':
			$author = $authordata['user_lastname'];
			break;
			
		case 'namefl':
			$author = $authordata['user_firstname'].' '.$authordata['user_lastname'];
			break;
			
		case 'namelf':
			$author = $authordata['user_lastname'].' '.$authordata['user_firstname'];
			break;
			
		default:
			$author = $authordata['user_nickname'];
	}
	
	echo format_to_output( $author, $format );
}


/*
 * the_author_level(-)
 *
 * @deprecated deprecated by {@link User::level()}
 */
function the_author_level() 
{
	global $authordata;
	echo $authordata['user_level'];
}

/*
 * the_author_login(-)
 *
 * @deprecated deprecated by {@link User::level()}
 */
function the_author_login( $format = 'htmlbody' ) 
{
	global $id,$authordata;
	echo format_to_output( $authordata['user_login'], $format );
}

/*
 * the_author_firstname(-)
 */
function the_author_firstname( $format = 'htmlbody' ) 
{
	global $id,$authordata;	
	echo format_to_output( $authordata['user_firstname'], $format );
}

/*
 * the_author_lastname(-)
 */
function the_author_lastname( $format = 'htmlbody' ) 
{
	global $id,$authordata;	
	echo format_to_output( $authordata['user_lastname'], $format );
}

/*
 * the_author_nickname(-)
 */
function the_author_nickname( $format = 'htmlbody' ) 
{
	global $id,$authordata;	
	echo format_to_output( $authordata['user_nickname'], $format );
}

/*
 * the_author_ID(-)
 *
 * @deprecated deprecated by {@link DataObject::ID()}
 */
function the_author_ID() 
{
	global $id,$authordata;		
	echo $authordata['ID'];
}

/*
 * the_author_email(-)
 */
function the_author_email( $format = 'raw' ) 
{
	global $id,$authordata;			
	echo format_to_output( antispambot($authordata['user_email']), $format );
}

/*
 * the_author_url(-)
 */
function the_author_url( $format = 'raw' ) 
{
	global $id,$authordata;			
	echo format_to_output( $authordata['user_url'], $format );
}

/*
 * the_author_icq(-)
 */
function the_author_icq( $format = 'raw' ) 
{
	global $id,$authordata;			
	echo format_to_output( $authordata['user_icq'], $format );
}

/*
 * the_author_aim(-)
 */
function the_author_aim( $format = 'raw' ) 
{
	global $id,$authordata;			
	echo format_to_output( str_replace(' ', '+', $authordata['user_aim']), $format );
}

/*
 * the_author_yim(-)
 */
function the_author_yim( $format = 'raw' ) 
{
	global $id,$authordata;			
	echo format_to_output( $authordata['user_yim'], $format );
}

/*
 * the_author_msn(-)
 */
function the_author_msn( $format = 'raw' ) 
{
	global $id,$authordata;	
	echo format_to_output( $authordata['user_msn'], $format );
}

/*
 * the_author_posts(-)
 */
function the_author_posts() 
{
	global $id,$postdata;	
	$posts=get_usernumposts($postdata['Author_ID']);
	echo $posts;
}

/*****
 * // Author tags 
 *****/





/***** Permalink tags *****/

/**
 * permalink_anchor(-)
 *
 * generate anchor for permalinks to refer to
 *
 * TODO: archives modes in clean mode
 *
 * @deprecated deprecated by {@link Item::anchor()}
 */
function permalink_anchor( $mode = 'id' ) 
{
	global $id, $postdata;
	switch(strtolower($mode))
	{
		case 'title':
			$title = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $postdata['Title']);
			echo '<a name="'.$title.'"></a>';
			break;
		case 'id':
		default:
			echo '<a name="'.$id.'"></a>';
			break;
	}
}

/*
 * gen_permalink(-)
 *
 * generate permalink
 *
 * TODO: archives modes in clean mode
 */
function gen_permalink(
	$file, 											// base URL of the blog
	$id,												// post ID to be linked to
	$use_anchor_mode = '', 			// Default to id
	$use_destination = '',			// Default to config
	$use_more = NULL,
	$use_comments = NULL,
	$use_trackback = NULL,
	$use_pingback = NULL )
{
	global $cacheweekly, $use_extra_path_info, $permalink_destination;
	global $permalink_include_more, $permalink_include_comments;
	global $permalink_include_trackback, $permalink_include_pingback;

	// We're gonna need access to more postdata in several cases:
	$postdata = get_postdata( $id );

	// Defaults:
	if (empty($use_anchor_mode)) $use_anchor_mode = 'id';
	if (empty($use_destination)) $use_destination = $permalink_destination;
	if ($use_destination=='archive') $use_destination = get_settings('archive_mode');
	if (empty($use_more)) $use_more = $permalink_include_more;
	if (empty($use_comments)) $use_comments = $permalink_include_comments;
	if (empty($use_trackback)) $use_trackback = $permalink_include_trackback;
	if (empty($use_pingback)) $use_pingback = $permalink_include_pingback;

	// Generate anchor
	switch(strtolower($use_anchor_mode)) 
	{
		case 'title':
			$title = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $postdata['Title']);
			$anchor = $title;
			break;
	
		case 'id':
		default:
			$anchor = $id;
			break;
	}

	if( ! $use_extra_path_info )
	{	// We reference by Query: Dirty but explicit permalinks

		// Generate options
		$options = '';
		if( $use_more )
		{ // permalinks to include full post text
			$options .=  '&amp;more=1';
		}
		if( $use_comments )
		{ // permalinks to include comments
			$options .=  '&amp;c=1';
		}
		if( $use_trackback )
		{ // permalinks to include trackbacks
			$options .=  '&amp;tb=1';
		}
		if( $use_pingback )
		{ // permalinks to include pingbacks
			$options .=  '&amp;pb=1';
		}

		switch($use_destination) 
		{
			case 'monthly':
				$permalink = $file.'?m='.substr($postdata['Date'],0,4).substr($postdata['Date'],5,2).$options.'#'.$anchor;
				break;
			case 'weekly':
				if((!isset($cacheweekly)) || (empty($cacheweekly[$postdata['Date']]))) {
					$sql = "SELECT WEEK('".$postdata['Date']."')";
					$result = mysql_query($sql);
					$row = mysql_fetch_row($result);
					$cacheweekly[$postdata['Date']] = $row[0];
				}
				$permalink = $file.'?m='.substr($postdata['Date'],0,4).'&amp;w='.$cacheweekly[$postdata['Date']].$options.'#'.$anchor;
				break;
			case 'daily':
				$permalink = $file.'?m='.substr($postdata['Date'],0,4).substr($postdata['Date'],5,2).substr($postdata['Date'],8,2).$options.'#'.$anchor;
				break;
			case 'postbypost':
			case 'single':
			default:
				$permalink = $file.'?p='.$id.$options;
				break;
		}
	}
	else
	{	// We reference by path (CLEAN permalinks!)
		switch($use_destination) 
		{
			case 'monthly':
				$permalink = $file.mysql2date("/Y/m/", $postdata['Date']).'#'.$anchor;
				break;
			case 'weekly':
				if((!isset($cacheweekly)) || (empty($cacheweekly[$postdata['Date']]))) {
					$sql = "SELECT WEEK('".$postdata['Date']."')";
					$result = mysql_query($sql);
					$row = mysql_fetch_row($result);
					$cacheweekly[$postdata['Date']] = $row[0];
				}
				$permalink = $file.mysql2date("/Y/m/", $postdata['Date']).'w'.$cacheweekly[$postdata['Date']].'/#'.$anchor;
				break;
			case 'daily':
				$permalink = $file.mysql2date("/Y/m/d/", $postdata['Date']).'#'.$anchor;
				break;
			case 'postbypost':
			case 'single':
			default:
				// This is THE CLEANEST available: RECOMMENDED!
				$permalink = $file.mysql2date("/Y/m/d/", $postdata['Date']).'p'.$id;
				$title = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $postdata['Title']);
				$permalink .= "-".$title;
				break;
		}
	}
	
	return $permalink;
}


/**
 * permalink_link(-)
 *
 * Display permalink
 *
 * @deprecated deprecated by {@link (Item::permalink())} but still used by _archives.php
 */
function permalink_link($file='', $mode = 'id', $post_ID = '' )		// id or title
{
	global $id;
	if( empty($post_ID) ) $post_ID = $id;
	if( empty($file) ) $file = get_bloginfo('blogurl');
	echo gen_permalink( $file, $post_ID, $mode );
}

/*
 * permalink_single(-)
 *
 * Permalink forced to a single post
 */
function permalink_single($file='') 
{
	global $id;
	if (empty($file)) $file = get_bloginfo('blogurl');
	echo gen_permalink( $file, $id, 'id', 'single' );
}


/***** // Permalink tags *****/



// @@@ These aren't template tags, do not edit them


/*
 * is_new_day(-)
 */
function is_new_day() 
{
	global $day, $previousday;
	if ($day != $previousday) {
		return(1);
	} else {
		return(0);
	}
}


/*
 * bpost_count_words(-)
 *
 * Returns the number of the words in a string, sans HTML
 */
function bpost_count_words($string)
{
	$string = trim(strip_tags($string));
	if( function_exists( 'str_word_count' ) )
	{	// PHP >= 4.3
		return str_word_count($string);
	}

	/* In case str_word_count() doesn't exist (to accomodate PHP < 4.3).
		(Code adapted from post by "brettNOSPAM at olwm dot NO_SPAM dot com" at
		PHP documentation page for str_word_count(). A better implementation
		probably exists.)
	*/
	if($string == '')
	{
		return 0;
	}

	$pattern = "/[^(\w|\d|\'|\"|\.|\!|\?|;|,|\\|\/|\-\-|:|\&|@)]+/";
	$string = preg_replace($pattern, " ", $string);
	$string = count(explode(" ", $string));

	return $string;
}

/*
 * statuses_where_clause(-)
 *
 * Construct the where clause to limit post statuses
 *
 * fplanque: created
 */
function statuses_where_clause( $show_statuses = '' )
{
	if( empty($show_statuses) )
		$show_statuses = array( 'published', 'protected', 'private' );

	$where = ' ( ';
	$or = '';

	if( ($key = array_search( 'private', $show_statuses )) !== false )
	{	// Special handling for Private status:
		unset( $show_statuses[$key] );
		if( is_logged_in() )
		{	// We need to be logged in to have a chance to see this:
			global $user_ID;
			$where .= $or." ( post_status = 'private' AND post_author = $user_ID ) ";
			$or = ' OR ';
		}
	}

	if( $key = array_search( 'protected', $show_statuses ) )
	{	// Special handling for Private status:
		if( ! is_logged_in() )
		{ // we are not allowed to see this if we are not logged in:
			unset( $show_statuses[$key] );
		}
	}
	
	// Remaining statuses:
	$other_statuses = '';
	$sep = '';
	foreach( $show_statuses as $other_status )
	{
		$other_statuses .= $sep.'\''.$other_status.'\'';
		$sep = ',';
	}
	if( strlen( $other_statuses ) )
	{				
		$where .= $or.'post_status IN ('. $other_statuses .') ';
	}

	$where .= ') ';

	// echo $where;
	return $where;
}

?>
