<?php
require_once('ultimate-tag-warrior-core.php');
$utw = new UltimateTagWarriorCore();

class UltimateTagWarriorActions {

	/* ultimate_admin_menus
	Adds a tag management page to the menu.
	*/
	function ultimate_admin_menus() {
		// Add a new menu under Manage:
		add_management_page('Tag Management', 'Tags', 8, basename(__FILE__), array('UltimateTagWarriorActions', 'ultimate_tag_admin'));

	}

/* ultimate_rewrite_rules

*/
function &ultimate_rewrite_rules(&$rules) {

	$baseurl = get_option("utw_base_url");

	$rules[substr($baseurl, 1) . "?(.*)"] = "index.php?tag=$1";

	return $rules;
}


/* ultimate_tag_admin
Allows performing administrative tasks to tags.  So far,  this means renaming tags, and deleting tags.
*/
function ultimate_tag_admin() {
	global $wpdb, $utw, $table_prefix, $user_level, $tabletags, $tablepost2tag;

	if ( $user_level < 3 ) {
		echo "You gots to be at least this tall to play with tags : 3.  You are $user_level";
		return;
	}

	$showtable = true;
	$siteurl = get_option('siteurl');
	echo "<div class=\"wrap\">";
	echo "<h2>Tag Management</h2>";

	if ($_GET["action"] == "saveconfiguration") {
		update_option('utw_base_url', $_GET["baseurl"]);
		update_option('utw_include_local_links', $_GET["includelocal"]);
		update_option('utw_include_technorati_links', $_GET["includetechno"]);
		update_option('utw_include_categories_as_tags', $_GET["includecats"]);
		update_option('utw_use_pretty_urls', $_GET["prettyurls"]);
		update_option('utw_always_show_links_on_edit_screen', $_GET["editlinks"]);

		update_option ('utw_tag_cloud_max_color', $_GET["tcmax"]);
		update_option ('utw_tag_cloud_min_color', $_GET["tcmin"]);

		update_option ('utw_tag_line_max_color', $_GET["tpmax"]);
		update_option ('utw_tag_line_min_color', $_GET["tpmin"]);

		update_option ('utw_long_tail_max_color', $_GET["ltmax"]);
		update_option ('utw_long_tail_min_color', $_GET["ltmin"]);

		echo "<div class=\"updated\"><p>Updated settings</p></div>";
	} else if ($_GET["action"] == "savetagupdate") {
		$tagid = $_GET["edittag"];

		if ($_GET["updateaction"] == "Rename") {
			$tag = $_GET["renametagvalue"];

			$tagset = explode(" ", $tag);

			$q = "SELECT post_id FROM $tablepost2tag WHERE tag_id = $tagid";
			$postids = $wpdb->get_results($q);

			$tagids = array();

			foreach ($tagset as $tag) {
				$q = "SELECT id FROM $tabletags WHERE tag = '$tag'";
				$thistagid = $wpdb->get_var($q);

				if (is_null($thistagid)) {
					$q = "INSERT INTO $tabletags (tag) VALUES ('$tag')";
					$wpdb->query($q);
					$thistagid = $wpdb->insert_id;
				}
				$tagids[] = $thistagid;
			}

			$keepold = false;
			foreach($tagids as $newtagid) {
				if ($postids ) {
					foreach ($postids as $postid) {
						if ($wpdb->get_var("SELECT COUNT(*) FROM $tablepost2tag WHERE tag_id = $newtagid AND post_id = $postid->post_id") == 0) {
							$wpdb->query("INSERT INTO $tablepost2tag (tag_id, post_id) VALUES ($newtagid, $postid->post_id)");
						}
					}
				} else {
					// I guess we were renaming something which wasn't being used...
				}

				if ($newtagid == $tagid) {
					$keepold = true;
				}
			}

			if (!$keepold) {
				$q = "delete from $tablepost2tag where tag_id = $tagid";
				$wpdb->query($q);

				$q = "delete from $tabletags where ID = $tagid";
				$wpdb->query($q);
			}
			echo "<div class=\"updated\"><p>Tags have been updated.</p></div>";
		}
		if ($_GET["updateaction"] == "Delete Tag") {
			$q = "delete from $tablepost2tag where tag_id = $tagid";
			$wpdb->query($q);

			$q = "delete from $tabletags where ID = $tagid";
			$wpdb->query($q);

			echo "<div class=\"updated\"><p>Tag has been deleted.</p></div>";
		}
		if ($_GET["updateaction"] == "Tidy Tags") {
			$utw->TidyTags();
		}
		if ($_GET["updateaction"] == "Convert Categories to Tags") {
			$postids = $wpdb->get_results("SELECT id FROM $wpdb->posts");
			foreach ($postids as $postid) {
				$utw->SaveCategoriesAsTags($postid->id);
			}
		}
		if ($_GET["updateaction"] == "Import from Custom Field") {
			update_option('utw_custom_field_conversion_field_name', $_GET["fieldName"]);
			update_option('utw_custom_field_conversion_delimiter', $_GET["delimiter"]);

			$postids = $wpdb->get_results("SELECT id FROM $wpdb->posts");
			foreach ($postids as $postid) {
				$utw->SaveCustomFieldAsTags($postid->id, $_GET["fieldName"], $_GET["delimiter"]);
			}
		}
		if ($_GET["updateaction"] == "Export to Custom Field") {
			update_option('utw_custom_field_conversion_field_name', $_GET["fieldName"]);
			update_option('utw_custom_field_conversion_delimiter', $_GET["delimiter"]);

			$postids = $wpdb->get_results("SELECT id FROM $wpdb->posts");
			foreach ($postids as $postid) {
				$utw->SaveTagsAsCustomField($postid->id, $_GET["fieldName"], $_GET["delimiter"]);
			}
		}
	}

	if ($showtable) {
		$q = <<<SQL
	select t.tag, t.ID from $tabletags t
	order by t.tag
SQL;

		$baseurl = get_option('utw_base_url');
		$includelocal = get_option('utw_include_local_links');
		$includetechnorati = get_option('utw_include_technorati_links');
		$includecatsastags = get_option('utw_include_categories_as_tags');
		$prettyurls = get_option('utw_use_pretty_urls');
		$editlinks = get_option('utw_always_show_links_on_edit_screen');

		$fieldName = get_option('utw_custom_field_conversion_field_name');
		$delimiter = get_option('utw_custom_field_conversion_delimiter');

		$tcmax = get_option ('utw_tag_cloud_max_color');
		$tcmin = get_option ('utw_tag_cloud_min_color');

		$tpmax = get_option ('utw_tag_line_max_color');
		$tpmin = get_option ('utw_tag_line_min_color');

		$ltmax = get_option ('utw_long_tail_max_color');
		$ltmin = get_option ('utw_long_tail_min_color');

		if ($includelocal == "yes") {
			$ilychecked=" checked";
		} else {
			$ilnchecked=" checked";
		}

		if ($includetechnorati == "yes") {
			$itychecked=" checked";
		} else {
			$itnchecked=" checked";
		}

		if ($includecatsastags == "yes") {
			$icychecked=" checked";
		} else {
			$icnchecked=" checked";
		}

		if ($prettyurls == "yes") {
			$puychecked=" checked";
		} else {
			$punchecked=" checked";
		}

		if ($editlinks == "yes") {
			$elychecked=" checked";
		} else {
			$elnchecked=" checked";
		}

		echo <<<OPTIONS
	<fieldset class="options">
	<legend>Help!</legend>
	<a href="$siteurl/wp-content/plugins/UltimateTagWarrior/ultimate-tag-warrior-help.html" target="_new">Local help</a> | <a href="http://www.neato.co.nz/ultimate-tag-warrior" target="_new">Author help</a>
	</fieldset>
	<fieldset class="options">
	<legend>Configuration</legend>
	<form action="$siteurl/wp-admin/edit.php">
		<table>
			<tr><td>Base Url</td><td><input type="text" name="baseurl" value="$baseurl"></td></tr>
			<tr><td>Automatically include local tag links</td><td><label for="ily">Yes </label><input type="radio" name="includelocal" id="ily" value="yes" $ilychecked> <label for="iln">No</label> <input type="radio" name="includelocal" id="iln" value="no" $ilnchecked></td></tr>
			<tr><td>Automatically include Technorati tag links</td><td><label for="ity">Yes </label><input type="radio" name="includetechno" id="ity" value="yes" $itychecked> <label for="itn">No</label> <input type="radio" name="includetechno" id="itn" value="no" $itnchecked></td></tr>
			<tr><td>Automatically add categories as tags</td><td><label for="icy">Yes </label><input type="radio" name="includecats" id="icy" value="yes" $icychecked> <label for="icn">No</label> <input type="radio" name="includecats" id="icn" value="no" $icnchecked></td></tr>
			<tr><td>Use url rewriting for local tag urls (/tag/tag instead of index.php?tag=tag)</td><td><label for="puy">Yes </label><input type="radio" name="prettyurls" id="puy" value="yes" $puychecked> <label for="pun">No</label> <input type="radio" name="prettyurls" id="icn" value="no" $punchecked></td></tr>
			<tr><td>Always display tag links on edit post page (instead of switching to a dropdown when there are many tags)</td><td><label for="ely">Yes </label><input type="radio" name="editlinks" id="ely" value="yes" $elychecked> <label for="eln">No</label> <input type="radio" name="editlinks" id="eln" value="no" $elnchecked></td></tr>
			<tr><td colspan="2">Tag cloud colors</td></tr>
			<tr><td>Most popular color</td><td><input type="text" name="tcmax" size="8" maxlength="7" value="$tcmax"></td></tr>
			<tr><td>Least popular color</td><td><input type="text" name="tcmin" size="8" maxlength="7" value="$tcmin"></td></tr>
			<!-- Just as soon as I think of a good way for deciding which colour pair to use where..
			<tr><td colspan="2">Tag popularity graph</td></tr>
			<tr><td>Most popular colour</td><td><input type="text" name="tpmax" size="8" maxlength="7" value="$tpmax"></td></tr>
			<tr><td>Least popular colour</td><td><input type="text" name="tpmin" size="8" maxlength="7" value="$tpmin"></td></tr>
			<tr><td colspan="2">Long tail graph colours</td></tr>
			<tr><td>Most popular colour</td><td><input type="text" name="ltmax" size="8" maxlength="7" value="$ltmax"></td></tr>
			<tr><td>Least popular colour</td><td><input type="text" name="ltmin" size="8" maxlength="7" value="$ltmin"></td></tr>
			-->
		</table>
		<input type="hidden" name="action" value="saveconfiguration">
		<input type="hidden" name="page" value="ultimate-tag-warrior-actions.php">
		<input type="submit" value="Save">
	</form>
</fieldset>
<fieldset class="options">
	<legend>Edit Tags</legend>
OPTIONS;
		$tags = $wpdb->get_results($q);
		if ($tags) {
			echo "<form action=\"$siteurl/wp-admin/edit.php\">";
			echo "<select name=\"edittag\">";
				foreach($tags as $tag) {
					echo "<option value=\"$tag->ID\">$tag->tag</option>";
				}
			echo <<<FORMTEXT
				</select> <input type="text" name="renametagvalue"> <input type="submit" name="updateaction" value="Rename"> <input type="submit" name="updateaction" value="Delete Tag" OnClick="javascript:return(confirm('Are you sure you want to delete this tag?'))"></fieldset>
			<fieldset class="options"><legend>Tidy Tags</legend>
			<p>Tidy Tags is a scary, scary thing.  <em>Make sure you back up your database before clicking the button.</em></p>
			<p>Tidy Tags will delete any tag&lt;-&gt;post associations which have either a deleted tag or deleted post;  delete any tags not associated with a post;  and merge tags with the same name into single tags.</p>
			<input type="submit" name="updateaction" value="Tidy Tags" OnClick="javascript:return(confirm('Are you sure you want to purge tags?'))"></fieldset>
			<fieldset class="options"><legend>Convert Tags</legend>
			<p>Again.. very scary.. back up your database first!</p>
			<input type="submit" name="updateaction" onClick="javascript:return(confirm('Are you sure you want to convert categories to tags?'))" value="Convert Categories to Tags">
			</fieldset>
			<fieldset class="options"><legend>Custom Fields</legend>
			<p>This pair of actions allow the moving of tag information from custom fields into the tag structure,  and moving the tag structure into a custom field.</p>
			<p>When moving information from the custom field to the tag structure,  the existing tags are retained.  However, copying the tags to the custom field <strong>will overwrite the existing values</strong>.  To retain the existing values,  do an import before the export.</p>
			<p><strong>This stuff seems to work,  but backup your database before trying,  just in case.</strong></p>
			<table>
			<tr><td>Custom field name</td><td><input type="text" name="fieldName" value="$fieldName" /></td></tr>
			<tr><td>Tag delimiter</td><td><input type="text" name="delimiter" value="$delimiter" /></td></tr>
			</table>
			<input type="submit" name="updateaction" value="Import from Custom Field" />
			<input type="submit" name="updateaction" value="Export to Custom Field" OnClick="javascript:return(confirm('Beware:  This will overwrite any data in the custom field.  Continue?'))"/>
			</fieldset>
			<input type="hidden" name="action" value="savetagupdate">
			<input type="hidden" name="page" value="ultimate-tag-warrior-actions.php"></form>
FORMTEXT;
		} else {
			echo "<p>No tags are in use at the moment.</p>";
		}
	}
	echo "</div>";
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
		ultimate_get_posts();

		if (file_exists(TEMPLATEPATH . "/tag.php")) {
			include(TEMPLATEPATH . '/tag.php');
			exit;
		} else {
	//		include(TEMPLATEPATH . '/index.php');
		}
	}
}

/*
ultimate_save_tags
Saves the tags for the current post to the database.

$postID the ID of the current post
$_POST['tagset'] the list of tags.
*/
function ultimate_save_tags($postID)
{
	global $wpdb, $tableposts, $table_prefix, $utw;

	$tags = $wpdb->escape($_POST['tagset']);
	$tags = explode(' ',$tags);
	// remove duplicates
	$tags = array_flip(array_flip($tags));

	$utw->SaveTags($postID, $tags);

	if (get_option('utw_include_categories_as_tags') == "yes") {
		$utw->SaveCategoriesAsTags($postID);
	}


    return $postID;
}

function ultimate_delete_post($postID) {
	global $utw;

	$utw->DeletePostTags($postID);

	return $postID;
}

/*
ultimate_display_tag_widget
Displays the tag box on the content editing page.
*/
function ultimate_display_tag_widget() {
  global $post, $wpdb, $table_prefix, $utw;

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
	echo '<legend>Tags (Space seperated list. -\'s and _\'s display as spaces)</legend>';
	echo "<input name=\"tagset\" type=\"text\" value=\"$taglist\" size=\"100\"><br />";
		echo <<<JAVASCRIPT
<script language="javascript">
function addTag(tagname) {
	document.forms[0].tagset.value += " " + tagname;
}
</script>
JAVASCRIPT;


	echo "Add existing tag: ";
	if ($utw->GetDistinctTagCount() <= 50 || get_option('utw_always_show_links_on_edit_screen') == "yes") {

		$format = "<a href=\"javascript:addTag('%tag%')\">%tagdisplay%</a> ";
		echo $utw->ShowPopularTags(-1, $format, 'tag', 'asc');

	} else {
		$format = array(
		'pre' => '<select onchange="if (document.getElementById(\'tag-menu\').value != \'\') { addTag(document.getElementById(\'tag-menu\').value) }" id="tag-menu"><option selected="selected" value="">Choose a tag</option>',
		'default' => '<option value="%tag%">%tagdisplay% (%tagcount%)</option>',
		'post' => '</select>');

		echo $utw->ShowPopularTags(-1, $format, 'tag', 'asc');
	}
  echo '</fieldset>';

}

function ultimate_the_content_filter($thecontent='') {
	global $post, $utw;

	if (get_option('utw_include_local_links') == 'yes') {
		$thecontent = $thecontent . $utw->FormatTags($utw->GetTagsForPost($post->ID), "%taglink% ");
	}
	if (get_option('utw_include_technorati_links') == 'yes') {
		$thecontent = $thecontent . "Technorati Tags: " . $utw->FormatTags($utw->GetTagsForPost($post->ID), "%technoratitag% ");
	}
	return $thecontent;
}

function ultimate_add_tags_to_rss($the_list, $type="") {
	global $post, $utw;

    $categories = get_the_category();
    $the_list = '';
    foreach ($categories as $category) {
        $category->cat_name = convert_chars($category->cat_name);
        if ('rdf' == $type) {
            $the_list .= "\n\t<dc:subject>$category->cat_name</dc:subject>";
        } else {
            $the_list .= "\n\t<category>$category->cat_name</category>";
        }
    }

	$format="<dc:subject>%tagdisplay%</dc:subject>";
	echo $the_list;
	echo $utw->FormatTags($utw->GetTagsForPost($post->ID), $format);
}

/*
function ultimate_posts_join() {
	if ($_GET["tag"] != "") {
		global $table_prefix, $wpdb;

		$tabletags = $table_prefix . "tags";
		$tablepost2tag = $table_prefix . "post2tag";

		$join = " INNER JOIN $tablepost2tag p2t on $wpdb->posts.ID = p2t.post_id INNER JOIN $tabletags t on p2t.tag_id = t.id ";
		return $join;
	}
}

function ultimate_posts_where() {
	if ($_GET["tag"] != "") {
		global $table_prefix, $wpdb;

		$tabletags = $table_prefix . "tags";
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

		$where = " AND t.tag IN ($taglist) ";

		return $where;
	}
}

function ultimate_posts_having () {
	if ($_GET["tag"] != "") {
		$tags = $_GET["tag"];
		$tagset = explode(" ", $tags);
		$taglist = "'" . $tagset[0] . "'";
		$tagcount = count($tagset);

		return " HAVING count(wp_posts.id) = $tagcount ";
	}
}
*/
}

// Admin menu items
add_action('admin_menu', array('UltimateTagWarriorActions', 'ultimate_admin_menus'));

// Add or edit tags
add_action('simple_edit_form', array('UltimateTagWarriorActions','ultimate_display_tag_widget'));
add_action('edit_form_advanced', array('UltimateTagWarriorActions','ultimate_display_tag_widget'));

// Save changes to tags
add_action('publish_post', array('UltimateTagWarriorActions','ultimate_save_tags'));
add_action('edit_post', array('UltimateTagWarriorActions','ultimate_save_tags'));
add_action('save_post', array('UltimateTagWarriorActions','ultimate_save_tags'));

add_action('delete_post', array('UltimateTagWarriorActions', 'ultimate_delete_post'));

// Display tag pages
add_action('template_redirect', array('UltimateTagWarriorActions','ultimate_tag_templates'));

// add_filter('posts_join', array('UltimateTagWarriorActions','ultimate_posts_join'));
// add_filter('posts_where', array('UltimateTagWarriorActions','ultimate_posts_where'));
// add_filter('posts_having',array('UltimateTagWarriorActions','ultimate_posts_having'));

// URL rewriting
add_filter('rewrite_rules_array', array('UltimateTagWarriorActions','ultimate_rewrite_rules'));

add_filter('the_content', array('UltimateTagWarriorActions', 'ultimate_the_content_filter'));
add_filter('the_category_rss', array('UltimateTagWarriorActions', 'ultimate_add_tags_to_rss'));
?>