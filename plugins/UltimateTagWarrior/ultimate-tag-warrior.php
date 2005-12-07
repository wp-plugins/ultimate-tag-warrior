<?php
/*
Plugin Name: Ultimate Tag Warrior
Plugin URI: http://www.neato.co.nz/ultimate-tag-warrior/
Description: UTW2:  Like UTW1,  but with even greater justice.  Allows tagging posts in a non-external-system dependent way;  with a righteous data structure for advanced tagging-mayhem.
Version: 2.8.9
Author: Christine Davis
Author URI: http://www.neato.co.nz
*/
ini_set("include_path", ini_get('include_path') . PATH_SEPARATOR . ".");

include_once('ultimate-tag-warrior-core.php');
include_once('ultimate-tag-warrior-actions.php');
load_plugin_textdomain('ultimate-tag-warrior');

$utw = new UltimateTagWarriorCore();

$utw->CheckForInstall();

function UTW_ShowTagsForCurrentPost($formattype, $format="", $limit = 0) {
	global $utw, $post;

	if ($format == "") {
		$format = $utw->GetFormatForType($formattype);
	}

	$utw->ShowTagsForPost($post->ID , $format, $limit);
}

function UTW_ShowRelatedTagsForCurrentPost($formattype, $format="", $limit = 0) {
	global $utw, $post;

	if ($format == "") {
		$format = $utw->GetFormatForType($formattype);
	}

	$utw->ShowRelatedTags($utw->GetTagsForPost($post->ID), $format, $limit);
}

function UTW_ShowRelatedPostsForCurrentPost($formattype, $format="", $limit = 0) {
	global $utw, $post;

	if ($format == "") {
		$format = $utw->GetFormatForType($formattype);
	}

	$utw->ShowRelatedPosts($utw->GetTagsForPost($post->ID), $format, $limit);
}

function UTW_ShowRelatedTagsForCurrentTagSet($formattype, $format="", $limit = 0) {
	global $utw;

	if ($format == "") {
		$format = $utw->GetFormatForType($formattype);
	}

	$utw->ShowRelatedTags($utw->GetCurrentTagSet(), $format, $limit);
}

function UTW_ShowCurrentTagSet($formattype, $format="", $limit = 0) {
	global $utw;

	if ($format == "") {
		$format = $utw->GetFormatForType($formattype);
	}

	echo $utw->FormatTags($utw->GetCurrentTagSet(), $format, $limit);
}

function UTW_ShowWeightedTagSet($formattype, $format="", $limit=150) {
	global $utw;

	if ($format == "") {
		$format = $utw->GetFormatForType($formattype);
	}

	echo $utw->FormatTags($utw->GetWeightedTags("weight", "desc", $limit), $format);
}

function UTW_ShowTimeSensitiveWeightedTagSet($formattype, $format="", $limit=150) {
	global $utw;

	if ($format == "") {
		$format = $utw->GetFormatForType($formattype);
	}

	echo $utw->FormatTags($utw->GetWeightedTags("weight", "desc", $limit, true), $format);
}

function UTW_ShowWeightedTagSetAlphabetical($formattype, $format="", $limit=150) {
	global $utw;

	if ($format == "") {
		$format = $utw->GetFormatForType($formattype);
	}

	echo $utw->FormatTags($utw->GetWeightedTags("tag", "asc", $limit), $format);
}

function UTW_ShowTimeSensitiveWeightedTagSetAlphabetical($formattype, $format="", $limit=150) {
	global $utw;

	if ($format == "") {
		$format = $utw->GetFormatForType($formattype);
	}

	echo $utw->FormatTags($utw->GetWeightedTags("tag", "asc", $limit, true), $format);
}

function UTW_HasTags() {
	global $utw, $post;

	return (bool)$utw->GetPostHasTags($post->ID);
}

function is_tag() {
	global $utw;

	return (count($utw->GetCurrentTagSet()) > 0);
}

/* if $format is passed in,  then the tags will replace the contents of the div named "tags-{tagid}" with a new tag list using the named format.  Otherwise, it'll just add the tag. */
function UTW_AddTagToCurrentPost($format="") {
	global $post;
	$postid = $post->ID;
	if ($format=="") {
	?><input type="text" size="9" id="soloAddTag-<?php echo $postid ?>" /> <input type="button" value="+" onClick="sndReqNoResp('add', document.getElementById('soloAddTag-<?php echo $postid ?>').value, '<?php echo $postid ?>')" /><?
	} else {
	?><input type="text" size="9" id="soloAddTag-<?php echo $postid ?>" /> <input type="button" value="+" onClick="sndReq('add', document.getElementById('soloAddTag-<?php echo $postid ?>').value, '<?php echo $postid ?>', '<?php echo $format ?>')" /><?
	}
}
?>