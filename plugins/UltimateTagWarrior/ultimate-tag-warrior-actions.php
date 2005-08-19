<?php
require_once('ultimate-tag-warrior-core.php');
$utw = new UltimateTagWarriorCore();

$install_directory = "/UltimateTagWarrior";

class UltimateTagWarriorActions {

	/* ultimate_admin_menus
	Adds a tag management page to the menu.
	*/
	function ultimate_admin_menus() {
		// Add a new menu under Manage:
		add_management_page('Tag Management', 'Tags', 8, basename(__FILE__), array('UltimateTagWarriorActions', 'ultimate_better_admin'));

	}

/* ultimate_rewrite_rules

*/
function &ultimate_rewrite_rules(&$rules) {

	$baseurl = get_option("utw_base_url");

	$rules[substr($baseurl, 1) . "?(.*)/feed/(feed|rdf|rss|rss2|atom)/?$"] = "index.php?tag=$1&feed=$2";

	$rules[substr($baseurl, 1) . "?(.*)/page/?(.*)/$"] = "index.php?tag=$1&paged=$2";
	$rules[substr($baseurl, 1) . "?(.*)/$"] = "index.php?tag=$1";

	$rules[substr($baseurl, 1) . "?(.*)/page/?(.*)$"] = "index.php?tag=$1&paged=$2";
	$rules[substr($baseurl, 1) . "?(.*)$"] = "index.php?tag=$1";

	return $rules;
}


/* ultimate_tag_admin
Allows performing administrative tasks to tags.  So far,  this means renaming tags, and deleting tags.
*/
function ultimate_tag_admin() {
	global $wpdb, $utw, $table_prefix, $user_level, $tabletags, $tablepost2tag, $install_directory;

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

		update_option ('utw_tag_cloud_min_font', $_GET["tcfmin"]);
		update_option ('utw_tag_cloud_max_font', $_GET["tcfmax"]);
		update_option ('utw_tag_cloud_font_units', $_GET["tcfunits"]);


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
				$q = "SELECT tag_id FROM $tabletags WHERE tag = '$tag'";
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

				$q = "delete from $tabletags where tag_id = $tagid";
				$wpdb->query($q);
			}
			echo "<div class=\"updated\"><p>Tags have been updated.</p></div>";
		}
		if ($_GET["updateaction"] == "Delete Tag") {
			$q = "delete from $tablepost2tag where tag_id = $tagid";
			$wpdb->query($q);

			$q = "delete from $tabletags where tag_id = $tagid";
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
	select t.tag, t.tag_id from $tabletags t
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

		$tcfmin = get_option ('utw_tag_cloud_min_font');
		$tcfmax = get_option ('utw_tag_cloud_max_font');
		$tcfunits = get_option ('utw_tag_cloud_font_units');

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
	<a href="$siteurl/wp-content/plugins$install_directory/ultimate-tag-warrior-help.html" target="_new">Local help</a> | <a href="http://www.neato.co.nz/ultimate-tag-warrior" target="_new">Author help</a>
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
			<tr><td>Most popular </td><td>color <input type="text" name="tcmax" size="8" maxlength="7" value="$tcmax"> size <input type="text" name="tcfmax" size="3" maxlength="3" value="$tcfmax" /></td></tr>
			<tr><td>Least popular </td><td>color <input type="text" name="tcmin" size="8" maxlength="7" value="$tcmin"> size <input type="text" name="tcfmin" size="3" maxlength="3" value="$tcfmin" /></td></tr>
			<tr><td>Font size units</td><td><select name="tcfunits"><option value="$tcfunits">Current: $tcfunits</option><option /><option value="em">em</option><option value="pt">pt</option><option value="px">px</option><option value="%">%</option></select></td></tr>
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
				echo "<option value=\"$tag->tag_id\">$tag->tag</option>";
			}

			echo "</select> <input type=\"text\" name=\"renametagvalue\"> <input type=\"submit\" name=\"updateaction\" value=\"Rename\"> <input type=\"submit\" name=\"updateaction\" value=\"Delete Tag\" OnClick=\"javascript:return(confirm('Are you sure you want to delete this tag?'))\">";

		} else {
			echo "<p>No tags are in use at the moment.</p>";
		}
		echo "</fieldset>";

		echo <<<FORMTEXT
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
	}
	echo "</div>";
}


function ultimate_better_admin() {
	global $lzndomain, $utw, $wpdb, $tableposts, $tabletags, $tablepost2tag, $install_directory;

	$siteurl = get_option('siteurl');

	echo '<div class="wrap">';

	$configValues = array();

	$configValues[] = array("setting"=>"utw_base_url", "label"=>__("Base url", $lzndomain),  "type"=>"string");
	$configValues[] = array("setting"=>"utw_trailing_slash", 'label'=>__("Include trailing slash on tag urls", $lzndomain), 'type'=>'boolean');

	$configValues[] = array("setting"=>"utw_include_local_links", "label"=>__("Automatically include local tag links", $lzndomain),  "type"=>"boolean");

	$configValues[] = array("setting"=>"utw_include_technorati_links", "label"=>__("Automatically include Technorati tag links", $lzndomain),  "type"=>"boolean");
	$configValues[] = array("setting"=>"utw_include_categories_as_tags", "label"=>__("Automatically add categories as tags", $lzndomain),  "type"=>"boolean");
	$configValues[] = array("setting"=>"utw_use_pretty_urls", "label"=>__("Use url rewriting for local tag urls (/tag/tag instead of index.php?tag=tag)", $lzndomain),  "type"=>"boolean");
	$configValues[] = array("setting"=>"utw_always_show_links_on_edit_screen", "label"=>__("Always display tag links on edit post page (instead of switching to a dropdown when there are many tags)", $lzndomain),  "type"=>"dropdown", "options"=>array('none', 'dropdown', 'tag list'));

	$configValues[] = array("setting"=>"", "label"=>__("Tag cloud colors", $lzndomain),  "type"=>"label");

	$configValues[] = array("setting"=>"utw_tag_cloud_max_color", "label"=>__("Most popular color", $lzndomain),  "type"=>"color");
	$configValues[] = array("setting"=>"utw_tag_cloud_max_font", "label"=>__("Most popular size", $lzndomain),  "type"=>"color");
	$configValues[] = array("setting"=>"utw_tag_cloud_min_color", "label"=>__("Least popular color", $lzndomain),  "type"=>"color");
	$configValues[] = array("setting"=>"utw_tag_cloud_min_font", "label"=>__("Least popular size", $lzndomain),  "type"=>"color");

	$configValues[] = array("setting"=>'utw_tag_cloud_font_units', 'label'=>__('Font size units', $lzndomain), "type"=>"dropdown", "options"=>array('%','pt','px','em'));

	if ($_GET["action"] == "saveconfiguration") {
		foreach($configValues as $setting) {
			if ($setting['type'] != 'label') {
				update_option($setting['setting'], $_GET[$setting['setting']]);
			}
		}
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
				$q = "SELECT tag_id FROM $tabletags WHERE tag = '$tag'";
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

				$q = "delete from $tabletags where tag_id = $tagid";
				$wpdb->query($q);
			}
			echo "<div class=\"updated\"><p>Tags have been updated.</p></div>";
		}

		if ($_GET["updateaction"] ==__("Delete Tag", $lzndomain)) {
			$q = "delete from $tablepost2tag where tag_id = $tagid";
			$wpdb->query($q);

			$q = "delete from $tabletags where tag_id = $tagid";
			$wpdb->query($q);

			echo "<div class=\"updated\"><p>Tag has been deleted.</p></div>";
		}
		if ($_GET["updateaction"] == __("Tidy Tags", $lzndomain)) {
			$utw->TidyTags();
			echo "<div class=\"updated\"><p>Tags have been tidied</p></div>";
		}
		if ($_GET["updateaction"] == __("Convert Categories to Tags", $lzndomain)) {
			$postids = $wpdb->get_results("SELECT id FROM $wpdb->posts");
			foreach ($postids as $postid) {
				$utw->SaveCategoriesAsTags($postid->id);
			}

			echo "<div class=\"updated\"><p>Categories have been converted to tags</p></div>";
		}
		if ($_GET["updateaction"] == __("Import from Custom Field", $lzndomain)) {
			update_option('utw_custom_field_conversion_field_name', $_GET["fieldName"]);
			update_option('utw_custom_field_conversion_delimiter', $_GET["delimiter"]);

			if ($_GET['fieldName'] && $_GET['delimiter']) {
				$postids = $wpdb->get_results("SELECT id FROM $wpdb->posts");
				foreach ($postids as $postid) {
					$utw->SaveCustomFieldAsTags($postid->id, $_GET["fieldName"], $_GET["delimiter"]);
				}
				echo "<div class=\"updated\"><p>Tags have been imported from a custom field</p></div>";
			} else {
				echo "<div class=\"updated\"><p>Could not import tags from custom field</p></div>";
			}
		}
		if ($_GET["updateaction"] == __("Export to Custom Field", $lzndomain)) {
			update_option('utw_custom_field_conversion_field_name', $_GET["fieldName"]);
			update_option('utw_custom_field_conversion_delimiter', $_GET["delimiter"]);

			if ($_GET['fieldName'] && $_GET['delimiter']) {
				$postids = $wpdb->get_results("SELECT id FROM $wpdb->posts");
				foreach ($postids as $postid) {
					$utw->SaveTagsAsCustomField($postid->id, $_GET["fieldName"], $_GET["delimiter"]);
				}
				echo "<div class=\"updated\"><p>Tags have been exported to a custom field</p></div>";
			} else {
				echo "<div class=\"updated\"><p>Could not export tags to custom field</p></div>";
			}
		}
	}

	echo "<fieldset class=\"options\"><legend>" . __("Help!", $lzndomain) . "</legend><a href=\"$siteurl/wp-content/plugins$install_directory/ultimate-tag-warrior-help.html\" target=\"_new\">" . __("Local help", $lzndomain) . "</a> | <a href=\"http://www.neato.co.nz/ultimate-tag-warrior\" target=\"_new\">" . __("Author help", $lzndomain) . "</a></fieldset>";
	echo '<fieldset class="options"><legend>' . __('Configuration', $lzndomain) . '</legend>';
	echo "<form action=\"$siteurl/wp-admin/edit.php\" method=\"GET\">";
	echo "<table>";

	foreach($configValues as $setting) {
		if ($setting['type'] == 'boolean') {
			UltimateTagWarriorActions::show_toggle($setting['setting'], $setting['label'], get_option($setting['setting']));
		}

		if ($setting['type'] == 'string') {
			UltimateTagWarriorActions::show_string($setting['setting'], $setting['label'], get_option($setting['setting']));
		}

		if ($setting['type'] == 'color') {
			UltimateTagWarriorActions::show_color($setting['setting'], $setting['label'], get_option($setting['setting']));
		}

		if ($setting['type'] == 'label') {
			UltimateTagWarriorActions::show_label($setting['setting'], $setting['label'], get_option($setting['setting']));
		}
		if ($setting['type'] == 'dropdown') {
			UltimateTagWarriorActions::show_dropdown($setting['setting'], $setting['label'], get_option($setting['setting']), $setting['options']);
		}
	}
echo <<<CONFIGFOOTER
	</table>
			<input type="hidden" name="action" value="saveconfiguration">
			<input type="hidden" name="page" value="ultimate-tag-warrior-actions.php">
			<input type="submit" value="Save">
		</form>
	</fieldset>
CONFIGFOOTER;


	echo '<fieldset class="options"><legend>' . __("Edit Tags", $lzndomain) .'</legend>';
OPTIONS;
		$tags = $utw->GetPopularTags(-1, 'asc', 'tag');
		if ($tags) {
			echo "<form action=\"$siteurl/wp-admin/edit.php\">";
			echo "<select name=\"edittag\">";
			foreach($tags as $tag) {
				echo "<option value=\"$tag->tag_id\">$tag->tag</option>";
			}

			echo '</select> <input type="text" name="renametagvalue"> <input type="submit" name="updateaction" value="' . __("Rename", $lzndomain) . '"> <input type="submit" name="updateaction" value="' . __("Delete Tag", $lzndomain) . '" OnClick="javascript:return(confirm(\'' . __("Are you sure you want to delete this tag?", $lzndomain) . '\'))">';
			echo '<input type="hidden" name="action" value="savetagupdate">';
			echo '<input type="hidden" name="page" value="ultimate-tag-warrior-actions.php">';
			echo '</form>';
		} else {
			echo '<p>' . __('No tags are in use at the moment.', $lzndomain) . '</p>';
		}
		echo "</fieldset>";

		echo "<form action=\"$siteurl/wp-admin/edit.php\">";

		echo '<fieldset class="options"><legend>' . __('Tidy Tags', $lzndomain) . '</legend>';
		_e('<p>Tidy Tags is a scary, scary thing.  <em>Make sure you back up your database before clicking the button.</em></p><p>Tidy Tags will delete any tag&lt;-&gt;post associations which have either a deleted tag or deleted post;  delete any tags not associated with a post;  and merge tags with the same name into single tags.</p>');
		echo '<input type="submit" name="updateaction" value="' . __('Tidy Tags', $lzndomain) . '" OnClick="javascript:return(confirm(\'' . __("Are you sure you want to purge tags?", $lzndomain) . '\'))"></fieldset>';

		echo '<fieldset class="options"><legend>' . __('Convert Categories to Tags', $lzndomain) . '</legend>';
		_e('<p>Again.. very scary.. back up your database first!</p>');
		echo '<input type="submit" name="updateaction" onClick="javascript:return(confirm(\'' . __('Are you sure you want to convert categories to tags?', $lzndomain) . '\'))" value="' . __('Convert Categories to Tags', $lzndomain) . '"></fieldset>';

		echo '<fieldset class="options"><legend>' . __('Custom Fields', $lzndomain) . '</legend>';
		_e('<p>This pair of actions allow the moving of tag information from custom fields into the tag structure,  and moving the tag structure into a custom field.</p><p>When moving information from the custom field to the tag structure,  the existing tags are retained.  However, copying the tags to the custom field <strong>will overwrite the existing values</strong>.  To retain the existing values,  do an import before the export.</p><p><strong>This stuff seems to work,  but backup your database before trying,  just in case.</strong></p>', $lzndomain);
		echo '<table><tr><td>' . __("Custom field name", $lzndomain) . '</td><td><input type="text" name="fieldName" value="' . $fieldName . '" /></td></tr>';
		echo '<tr><td>' . __("Tag delimiter", $lzndomain) . '</td><td><input type="text" name="delimiter" value="' . $delimiter . '" /></td></tr></table>';
		echo '<input type="submit" name="updateaction" value="' . __("Import from Custom Field", $lzndomain) . '" />';
		echo '<input type="submit" name="updateaction" value="' . __("Export to Custom Field", $lzndomain) . '" OnClick="javascript:return(confirm(\'' . __('Beware:  This will overwrite any data in the custom field.  Continue?', $lzndomain) . '\'))"/></fieldset>';

		echo '<input type="hidden" name="action" value="savetagupdate">';
		echo '<input type="hidden" name="page" value="ultimate-tag-warrior-actions.php">';
		echo '</form>';
}

function show_dropdown($settingName, $label, $value, $options) {
	echo "<tr><td>$label</td><td><select name=\"$settingName\">";

	foreach($options as $option) {
		echo "<option value=\"$option\"";
		if ($value == $option) {
			echo " selected";
		}
		echo ">$option</option>";
	}

	echo "</select></td></tr>";
FORMWIDGET;
}

function show_label($settingName, $label, $value) {
	echo <<<FORMWIDGET
<tr><td colspan="2"><strong>$label</strong></td></tr>
FORMWIDGET;
}

function show_color($settingName, $label, $value) {
	echo <<<FORMWIDGET
<tr><td>$label</td><td><input type="text" name="$settingName" value="$value" maxlength="7" size="9"></td></tr>
FORMWIDGET;
}

function show_string($settingName, $label, $value) {
	echo <<<FORMWIDGET
<tr><td>$label</td><td><input type="text" name="$settingName" value="$value"></td></tr>
FORMWIDGET;
}

function show_toggle($settingName, $label, $value) {
	if ($value == 'yes') {
		$yeschecked = " checked";
	} else {
		$nochecked = " checked";
	}
	echo <<<FORMWIDGET
<tr><td>$label</td><td><label for="y$settingName">Yes </label><input type="radio" name="$settingName" id="y$settingName" value="yes" $yeschecked> <label for="n$settingName">No</label> <input type="radio" name="$settingName" id="n$settingName" value="no" $nochecked></td></tr>
FORMWIDGET;
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
		if (file_exists(TEMPLATEPATH . "/tag.php" && (is_null($_GET['feed']) || $_GET["feed"] == ''))) {
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


  if ( (is_object($post) && $post->ID) || (!is_object($post) && $post)) {
	if (is_object($post)) {
		$postid = $post->ID;
	} else {
		$postid = $post;
	}


    $q = "select t.tag from $tabletags t inner join $tablepost2tag p2t on t.tag_id = p2t.tag_id and p2t.post_id=$postid";
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

	$widgetToUse = get_option('utw_always_show_links_on_edit_screen');

	if ($widgetToUse != 'none') {
		echo <<<JAVASCRIPT
	<script language="javascript">
	function addTag(tagname) {
		document.forms[0].tagset.value += " " + tagname;
	}
	</script>
JAVASCRIPT;


		echo "Add existing tag: ";
		if ($widgetToUse=='tag list') {

			$format = "<a href=\"javascript:addTag('%tag%')\">%tagdisplay%</a> ";
			echo $utw->ShowPopularTags(-1, $format, 'tag', 'asc');

		} else {
			$format = array(
			'pre' => '<select onchange="if (document.getElementById(\'tag-menu\').value != \'\') { addTag(document.getElementById(\'tag-menu\').value) }" id="tag-menu"><option selected="selected" value="">Choose a tag</option>',
			'default' => '<option value="%tag%">%tagdisplay% (%tagcount%)</option>',
			'post' => '</select>');

			echo $utw->ShowPopularTags(-1, $format, 'tag', 'asc');
		}
	}
  echo '</fieldset>';

}

function ultimate_the_content_filter($thecontent='') {
	global $post, $utw, $lzndomain;

	if (get_option('utw_include_local_links') == 'yes') {
		$thecontent = $thecontent . $utw->FormatTags($utw->GetTagsForPost($post->ID), "%taglink% ");
	}
	if (get_option('utw_include_technorati_links') == 'yes') {
		$thecontent = $thecontent . $utw->FormatTags($utw->GetTagsForPost($post->ID), array("first"=>__("Technorati Tags", $lzndomain) . ": %technoratitag% ", "default"=>"%technoratitag% ", "none"=>""));
	}
	return $thecontent;
}

function ultimate_add_tags_to_rss($the_list, $type="") {
	global $post, $utw;

    $categories = get_the_category();
    $the_list = '';
    foreach ($categories as $category) {
        $category->cat_name = convert_chars($category->cat_name);
        $the_list .= "\n\t<dc:subject>$category->cat_name</dc:subject>";
    }

	$format="<dc:subject>%tagdisplay%</dc:subject>";
	echo $the_list;
	echo $utw->FormatTags($utw->GetTagsForPost($post->ID), $format);
}

function ultimate_add_ajax_javascript() {
	global $install_directory;
	$rpcurl = get_option('siteurl') . "/wp-content/plugins$install_directory/ultimate-tag-warrior-ajax.php";
	$jsurl = get_option('siteurl') . "/wp-content/plugins$install_directory/ultimate-tag-warrior-ajax-js.php";
	echo "<script language=\"javascript\" src=\"$jsurl?ajaxurl=$rpcurl\" type=\"text/javascript\"></script>";

}

function ultimate_posts_join($join) {
	if ($_GET["tag"] != "") {
		global $table_prefix, $wpdb;

		$tabletags = $table_prefix . "tags";
		$tablepost2tag = $table_prefix . "post2tag";

		$join .= " INNER JOIN $tablepost2tag p2t on $wpdb->posts.ID = p2t.post_id INNER JOIN $tabletags t on p2t.tag_id = t.tag_id ";
	}
	return $join;
}

function ultimate_posts_where($where) {
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

		$where .= " AND t.tag IN ($taglist) ";
	}
	return $where;
}

/* Maaaaaybe some day...

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

add_filter('posts_join', array('UltimateTagWarriorActions','ultimate_posts_join'));
add_filter('posts_where', array('UltimateTagWarriorActions','ultimate_posts_where'));
// add_filter('posts_having',array('UltimateTagWarriorActions','ultimate_posts_having'));

// URL rewriting
add_filter('rewrite_rules_array', array('UltimateTagWarriorActions','ultimate_rewrite_rules'));

add_filter('the_content', array('UltimateTagWarriorActions', 'ultimate_the_content_filter'));
add_filter('the_category_rss', array('UltimateTagWarriorActions', 'ultimate_add_tags_to_rss'));

add_filter('wp_head', array('UltimateTagWarriorActions', 'ultimate_add_ajax_javascript'));
?>