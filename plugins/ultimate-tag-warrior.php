<?php
/*
Plugin Name: Ultimate Tag Warrior
Plugin URI: http://www.neato.co.nz/manyfaces/wordpress-plugins/ultimate-tag-warrior/
Description: Add tags to wordpress.  Tags and tag/post associations are seperated out for great justice.
			 And when I say great justice,  I mean doing more with tags than just listing them.  This is,
			 the ultimate tag warrior.
Version: 1.0
Author: Christine Davis
Author URI: http://www.neato.co.nz
*/

/*
ultimate_show_popular_tags
Creates a list of the most popular tags.  Intended for sidebar use.
The format of the tags is:

<li>{tagname} ({count})</li>
*/
function ultimate_show_popular_tags($limit = 10) {
	global $wpdb, $tabletags;

	$query = "select tag, count(tag) as count
			  from $tabletags
			  group by tag
			  order by count desc
			  limit $limit";

	$tags = $wpdb->get_results($query);

	foreach ($tags as $tag) {
		echo "<li><a href=\"/tag/$tag->tag\">$tag->tag</a> ($tag->count)</li>";
	}

}

/*
ultimate_show_post_tags
Displays a list of tags associated with the current post.

$seperator goes between each tag (but not at the beginning or the end of the list)
$baseurl is the base url for the text link
$notagmessage is what will display if there are no tags for the post.
*/
function ultimate_show_post_tags($separator="&nbsp;", $baseurl='/tag/', $notagmessage = "No Tags", $morelinks="")
{
  global $post, $wpdb, $table_prefix, $tableposts;

  $tabletags = $table_prefix . "tags";
  $tablepost2tag = $table_prefix . "post2tag";

  $id = $post->postid;
  if(empty($id)) {
	$id = $post->ID;
  }
  $q = "select distinct t.tag from $tabletags t inner join $tablepost2tag p2t on p2t.tag_id = t.id inner join $tableposts p on p2t.post_id = p.ID and p.ID=$id";
  $results = $wpdb->get_results($q);

  if ($results) {
	  foreach($results as $result) {
	  	  if (trim($result->tag) != "") {
	  	  	  $hasTags = true;
			  $out .= '<a href="' . $baseurl . trim($result->tag) . '" rel="tag" ';
			  $out .= 'title="View all posts tagged ' . trim($result->tag) . '">';;
			  $out .= ucfirst(trim($result->tag)) . "</a>";

				if ($morelinks) {
					if (is_array($morelinks)) {
				  	  foreach($morelinks as $link) {
						  $out .= ' <a href="' . $link . trim($result->tag) .'" rel="tag">';
						  $out .= '&raquo;</a>';
					  }
					} else {
						  $out .= ' <a href="' . $morelinks . trim($result->tag) .'" rel="tag">';
						  $out .= '&raquo;</a>';
					}
			  }
			  $out .= $separator;
		  }
	  }
	  if ($hasTags) {
		  $out = substr($out, 0, 0-strlen($separator));
	  } else {
		  $out = $notagmessage;
	  }
  } else {
	  $out = $notagmessage;
  }

  echo $out;
}

/*
ultimate_tag_archive
You may remember my plugin Category Archive.  This is a tag archive in the same style.

Displays a list of the top x most popular tags,  with the top y most recent posts for that tag.  If there are more posts,  there is also a link to the tag page for that tag.
$limit the maximum number of tags to display
$postlimit the maximum number of posts to display for each tag
*/
function ultimate_tag_archive($limit = 20, $postlimit=20) {
  global $wpdb, $tableposts, $table_prefix;

  $tabletags = $table_prefix . "tags";
  $tablepost2tag = $table_prefix . "post2tag";

  $q = "select t.tag, count(t.tag) as count from $tabletags t inner join $tablepost2tag p2t on p2t.tag_id = t.id group by tag order by count desc limit $limit";
  $tags = $wpdb->get_results($q);
	if ($tags) {
  foreach($tags as $tag) {
    $out .= "<div class=\"tagarchive\">";

  	$out .= "<div class=\"tagarchivename\">" . $tag->tag . " - " . $tag->count . "</div>";
    $q = "select p.ID, p.post_title from $tabletags t inner join $tablepost2tag p2t on p2t.tag_id = t.id inner join $tableposts p on p2t.post_id = p.ID and t.tag='$tag->tag' limit $postlimit";
	$posts = $wpdb->get_results($q);

	$out .= "<div class=\"tagarchiveposts\">";

	if ($posts) {
		foreach ($posts as $post) {
			$out .= "<a href=\"" . get_permalink($post->ID) . "\">$post->post_title</a>, ";
		}
		if (count($posts) == $postlimit) {
			$out .= "<a href=\"/tag/$tag->tag\">More from $tag->tag</a>...";
		} else {
			// trim trailing comma
			$out = substr($out, 0, -2);
		}
	}
	$out .= "</div>";
	$out .= "</div>";
  }
	} else {
		$out = "No Tags";
	}
  echo $out;
}

/*
ultimate_tag_cloud
Creates a tag cloud,  which can be styled in CSS.
*/

function ultimate_tag_cloud($order='tag', $direction='asc') {
  global $wpdb, $tableposts, $table_prefix;

  $tabletags = $table_prefix . "tags";
  $tablepost2tag = $table_prefix . "post2tag";

	if ($order <> "tag" && $order <> "count") { $order = "tag"; }
	if ($direction <> "asc" && $direction <> "desc") { $direction = "asc"; }

	$q = "SELECT count(*) FROM $tablepost2tag";
	$totalTags = $wpdb->get_var($q);

	$q = <<<SQL
SELECT t.tag, count(t.tag) as count from $tabletags t inner join $tablepost2tag p2t on p2t.tag_id = t.id
GROUP BY tag
ORDER BY $order $direction
SQL;

  $tags = $wpdb->get_results($q);

	// The average number of times a tag appears on each post.
	$average = (count($tags) / $totalTags);

  	foreach($tags as $tag) {
		if ($tag->count > $average * 20) {
			$tagclass = "taglevel1";
		} else if ($tag->count > $average * 10) {
			$tagclass = "taglevel2";
		} else if ($tag->count > $average * 6) {
			$tagclass = "taglevel3";
		} else if ($tag->count > $average * 4) {
			$tagclass = "taglevel4";
		} else if ($tag->count > $average * 2) {
			$tagclass = "taglevel5";
		} else if ($tag->count > $average) {
			$tagclass = "taglevel6";
		} else if ($tag->count <= $average) {
			$tagclass = "taglevel7";
		}
		echo "<a href=\"/tag/$tag->tag\" class=\"$tagclass\" title=\"$tag->tag ($tag->count)\">$tag->tag</a> ";
  	}
}

/* ultimate_get_posts()
Retrieves the posts for the tags specified in $_GET["tag"].  Gets the intersection when there are multiple tags.
*/
function ultimate_get_posts() {
	global $wpdb, $table_prefix, $posts, $table_prefix, $tableposts, $id;
	$tabletags = $table_prefix . 'tags';
	$tablepost2tag = $table_prefix . "post2tag";

	$tags = $_GET["tag"];
	$tagset = explode(" ", $tags);
	$taglist = "'" . $tagset[0] . "'";
	$tagcount = count($tagset);
	if ($tagcount > 1) {
		for ($i = 1; $i <= $tagcount; $i++) {
			if ($tagset[$i] <> "") {
				$taglist = $taglist . ", '" . $tagset[$i] . "'";
			}
		}
	}

	$now = current_time('mysql', 1);

   $q = <<<SQL
SELECT * from
	$tabletags t, $tablepost2tag p2t, $tableposts p
WHERE t.ID = p2t.tag_id
  AND p.ID = p2t.post_id
  AND t.tag IN ($taglist)
  AND post_date_gmt < '$now'
  AND post_status = 'publish'
GROUP BY p.ID
HAVING COUNT(p.id) = $tagcount
ORDER BY post_date desc
SQL;

   $posts = $wpdb->get_results($q);
}

/*
ultimate_save_tags
Saves the tags for the current post to the database.

$postID the ID of the current post
$_POST['tagset'] the list of tags.
*/
function ultimate_save_tags($postID)
{
  global $wpdb, $tableposts, $table_prefix;

  $tabletags = $table_prefix . "tags";
  $tablepost2tag = $table_prefix . "post2tag";

  $wpdb->show_errors();
  $tags = $wpdb->escape($_POST['tagset']);
  $tagset = explode(' ',$tags);
  // remove duplicates
  $tagset = array_flip(array_flip($tagset));

  foreach($tagset as $tag) {
  	if ($tag <> "") {
		$q = "SELECT id from $tabletags WHERE tag='$tag' limit 1";
		$tagid = $wpdb->get_var($q);

		if (is_null($tagid)) {
		  $q = "INSERT INTO $tabletags (tag) values ('$tag')";
		  $wpdb->query($q);
		  $tagid = $wpdb->insert_id;
		}

		$q = "select rel_id from $tablepost2tag where post_id = '$postID' and tag_id = '$tagid'";

		if ( is_null($wpdb->get_var($q))) {
			$q = "INSERT INTO $tablepost2tag (post_id, tag_id) VALUES ('$postID','$tagid')";
			$wpdb->query($q);
		}

		$taglist .= $tagid . ", ";
	 }
  }

  // Remove any tags that are no longer associated with the post.
	$taglist = substr($taglist, 0 ,-2);

	if ($taglist == "") {
		// since "not in ()" doesn't play nice.
		$q = "delete from $tablepost2tag where post_id = $postID";
	} else {
		$q = "delete from $tablepost2tag where post_id = $postID and tag_id not in ($taglist)";
	}
	$wpdb->query($q);

    return $postID;
}

/*
ultimate_display_tag_widget
Displays the tag box on the content editing page.
*/
function ultimate_display_tag_widget() {
  global $post, $wpdb, $table_prefix;

  $tabletags = $table_prefix . "tags";
  $tablepost2tag = $table_prefix . "post2tag";

  $taglist = "";
  if ($post) {
    $q = "select t.tag from $tabletags t inner join $tablepost2tag p2t on t.id = p2t.tag_id and p2t.post_id=$post";
    $tags = $wpdb->get_results($q);

    if ($tags) {
	  foreach($tags as $tag) {
		  $taglist .= $tag->tag . " ";
      }
	  $taglist = substr($taglist, 0, -1); // trim the trailing space.
    }
  }
  echo '<fieldset id="tagsdiv">';
  echo '<legend>Tags (Space seperated list.)</legend>';
  echo "<input name=\"tagset\" type=\"text\" value=\"$taglist\" size=\"100\">";
  echo '</fieldset>';
}

/*
ultimate_tag_templates
Handles the inclusion of templates, when appropriate.

index.php?archive=tag (or equivalent) will try and use the template tag_all.php
index.php?tag={tag name} (or equivalent) will try and use the template tag.php
*/
function ultimate_tag_templates() {
	if ($_GET["archive"] == "tag") {
		include(TEMPLATEPATH . '/tag_all.php');
		exit;
	} else 	if ($_GET["tag"] != "") {
		include(TEMPLATEPATH . '/tag.php');
		exit;
	}
}

/* ultimate_rewrite_rules
Adds a rewrite rule that catches requests to /tag/ and /tags/
*/
function &ultimate_rewrite_rules(&$rules) {
	$rules["^tag/?(.*)"] = "/index.php?tag=$1 [QSA]";
	$rules["^tags/?(.*)"] = "/index.php?tag=$1 [QSA]";

	return $rules;
}

/* ultimate_admin_menus
Adds a tag management page to the menu.
*/
function ultimate_admin_menus() {
    // Add a new menu under Manage:
    add_management_page('Tag Management', 'Tags', 8, basename(__FILE__), 'ultimate_tag_admin');

}

/* ultimate_tag_admin
Allows performing administrative tasks to tags.  So far,  this means renaming tags, and deleting tags.
*/
function ultimate_tag_admin() {
	global $wpdb, $table_prefix, $user_level;

	$tabletags = $table_prefix . "tags";
	$tablepost2tag = $table_prefix . "post2tag";

	if ( $user_level < 3 ) {
		echo "You gots to be at least this tall to play with tags : 3.  You are $user_level";
		return;
	}

	$showtable = true;
	echo "<div class=\"wrap\">";
	echo "<h2>Tag Management</h2>";

	if ($_GET["action"] == "saverename") {
		$tag = $_GET["newname"];
		$tagid = $_GET["tag"];

		$q = "UPDATE $tabletags SET tag = '$tag' WHERE ID = $tagid";
		$wpdb->query($q);

		echo "<div class=\"updated\"><p>Tag '$tag' has been updated.</p></div>";

	} else if ($_GET["action"] == "rename") {
		echo "<h3>Rename Tag</h3>";
		echo "<form action=\"/wp-admin/edit.php\" method=\"GET\">";
		$tagid = $_GET["tag"];
		$q = "select tag from $tabletags where ID = $tagid";
		$tag = $wpdb->get_var($q);

		echo "<input type=\"text\" name=\"newname\" value=\"$tag\">";
		echo "<input type=\"hidden\" name=\"tag\" value=\"$tagid\">";
		echo "<input type=\"hidden\" name=\"action\" value=\"saverename\">";
		echo "<input type=\"hidden\" name=\"page\" value=\"ultimate-tag-warrior.php\">";
		echo "<input type=\"submit\" value=\"Save\">";
		echo "</form>";

		$showtable = false;
	} else if ($_GET["action"] == "delete") {
		$tagid = $_GET["tag"];
		$q = "select tag from $tabletags where ID = $tagid";
		$tag = $wpdb->get_var($q);

		if($_GET["imeanit"] == "true") {
			$q = "delete from $tablepost2tag where tag_id = $tagid";
			$wpdb->query($q);

			$q = "delete from $tabletags where ID = $tagid";
			$wpdb->query($q);

			echo "<div class=\"updated\"><p>Tag '$tag' has been deleted.</p></div>";
		} else {
			echo "Are you sure you want to delete $tag?<br />";
			echo "<a href=\"/wp-admin/edit.php?page=ultimate-tag-warrior.php&action=delete&tag=$tagid&imeanit=true\">Yes</a> <a href=\"/wp-admin/edit.php?page=ultimate-tag-warrior.php\">No</a>";
			$showtable = false;
		}
	}

	if ($showtable) {
		$q = <<<SQL
	select t.tag, t.ID, count(*) as cnt from $tabletags t inner join $tablepost2tag p2t on t.ID = p2t.tag_id
	group by t.tag, t.ID
	order by t.tag
SQL;

		$tags = $wpdb->get_results($q);
		echo "<table>";
		foreach($tags as $tag) {
		$tagrow = <<<HTML
		<tr><td>$tag->tag</td><td><a href="/wp-admin/edit.php?page=ultimate-tag-warrior.php&action=rename&tag=$tag->ID">Rename Tag</a></td><td><a href="/wp-admin/edit.php?page=ultimate-tag-warrior.php&action=delete&tag=$tag->ID">Delete Tag</a></td></tr>
HTML;
			echo $tagrow;
		}
		echo "</table>";
	}
	echo "</div>";
}

// Add or edit tags
add_action('simple_edit_form', 'ultimate_display_tag_widget');
add_action('edit_form_advanced', 'ultimate_display_tag_widget');

// Save changes to tags
add_action('publish_post', 'ultimate_save_tags');
add_action('edit_post', 'ultimate_save_tags');
add_action('save_post', 'ultimate_save_tags');

// Display tag pages
add_action('template_redirect', 'ultimate_tag_templates');

// URL rewriting
add_filter('rewrite_rules_array', 'ultimate_rewrite_rules');

// Admin menu items
add_action('admin_menu', 'ultimate_admin_menus');
?>