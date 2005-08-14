<?php
/*
Plugin Name: Ultimate Tag Warrior
Plugin URI: http://www.neato.co.nz/ultimate-tag-warrior/
Description: UTW2:  Like UTW1,  but with even greater justice.  Allows tagging posts in a non-external-system dependent way;  with a righteous data structure for advanced tagging-mayhem.
Version: 2.6.2
Author: Christine Davis
Author URI: http://www.neato.co.nz
*/
include_once('ultimate-tag-warrior-core.php');
include_once('ultimate-tag-warrior-actions.php');

$utw = new UltimateTagWarriorCore();

$utw->CheckForInstall();

function UTW_ShowTagsForCurrentPost($formattype, $format="") {
	global $utw, $post;

	if ($format == "") {
		$format = $utw->GetFormatForType($formattype);
	}

	$utw->ShowTagsForPost($post->ID , $format);
}

function UTW_ShowRelatedTagsForCurrentPost($formattype, $format="") {
	global $utw, $post;

	if ($format == "") {
		$format = $utw->GetFormatForType($formattype);
	}

	$utw->ShowRelatedTags($utw->GetTagsForPost($post->ID), $format);
}

function UTW_ShowRelatedPostsForCurrentPost($formattype, $format="") {
	global $utw, $post;

	if ($format == "") {
		$format = $utw->GetFormatForType($formattype);
	}

	$utw->ShowRelatedPosts($utw->GetTagsForPost($post->ID), $format);
}

function UTW_ShowRelatedTagsForCurrentTagSet($formattype, $format="") {
	global $utw;

	if ($format == "") {
		$format = $utw->GetFormatForType($formattype);
	}

	$utw->ShowRelatedTags($utw->GetCurrentTagSet(), $format);
}

function UTW_ShowCurrentTagSet($formattype, $format="") {
	global $utw;

	if ($format == "") {
		$format = $utw->GetFormatForType($formattype);
	}

	echo $utw->FormatTags($utw->GetCurrentTagSet(), $format);
}

function UTW_ShowWeightedTagSet($formattype, $format="", $limit=150) {
	global $utw;

	if ($format == "") {
		$format = $utw->GetFormatForType($formattype);
	}

	echo $utw->FormatTags($utw->GetWeightedTags("weight", "desc", $limit), $format);
}

function UTW_ShowWeightedTagSetAlphabetical($formattype, $format="", $limit=150) {
	global $utw;

	if ($format == "") {
		$format = $utw->GetFormatForType($formattype);
	}

	echo $utw->FormatTags($utw->GetWeightedTags("tag", "asc", $limit), $format);
}

?>