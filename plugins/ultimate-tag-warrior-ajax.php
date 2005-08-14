<?php
	require('../../../wp-blog-header.php');
	include_once('ultimate-tag-warrior-core.php');

	$action = $_REQUEST['action'];
	$tag = $_REQUEST['tag'];
	$post = $_REQUEST['post'];
	$format = $_REQUEST['format'];

	switch($action) {
		case 'del':
			if ( $user_level > 3 ) {
				$utw->RemoveTag($post, $tag);
				echo $post . "|";
				$utw->ShowTagsForPost($post, $utw->GetFormatForType("superajax"));
			}
			break;

		case 'add':
			$utw->AddTag($post, $tag);
			echo $post . "|";
			$utw->ShowTagsForPost($post, $utw->GetFormatForType("superajax"));
			break;

		case 'expand':
			echo "$post-$tag|";
			echo $utw->FormatTags($utw->GetTagsForTagString('"' . $tag . '"'), $utw->GetFormatForType("linkset"));
			break;

		case 'expandrel':
			echo "$post-$tag|";
			echo $utw->FormatTags($utw->GetTagsForTagString('"' . $tag . '"'), $utw->GetFormatForType("linksetrel"));
			break;

		case 'shrink':
			echo "$post-$tag|";
			echo $utw->FormatTags($utw->GetTagsForTagString('"' . $tag . '"'), $utw->GetFormatForType($format . "item"));
			break;

		case 'shrinkrel':
			echo "$post-$tag|";
			echo $utw->FormatTags($utw->GetTagsForTagString('"' . $tag . '"'), $utw->GetFormatForType($format . "item"));
			break;

	}
?>