<?php
/* Maybe you'll need this.. maybe you won't...
$path = ini_get('include_path');
if (!(substr($path, strlen( $path ) - strlen(PATH_SEPARATOR)) === PATH_SEPARATOR)) {
	$path .= PATH_SEPARATOR;
}
$path .= $_SERVER['DOCUMENT_ROOT'] . "/wp-content/plugins/UltimateTagWarrior";
ini_set("include_path", $path);
*/

require('../../../wp-blog-header.php');
require_once('ultimate-tag-warrior-core.php');

$appID = "wp-UltimateTagWarrior";

$action = $_REQUEST['action'];
$tag = $_REQUEST['tag'];
$post = $_REQUEST['post'];
$format = $_REQUEST['format'];

$debug = get_option('utw_debug');

switch($action) {
	case 'del':
		if ( $user_level > 3 ) {
			$utw->RemoveTag($post, $tag);
			echo $post . "|";
			$utw->ShowTagsForPost($post, $utw->GetFormatForType("superajax"));
		}
		break;

	case 'add':
		$tags = explode(',',$tag);
		foreach ($tags as $t) {
			$utw->AddTag($post, $t);
		}
		echo $post . "|";
		if("" == $format) {
			$format = "superajax";
		}
		$utw->ShowTagsForPost($post, $utw->GetFormatForType($format));
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
		echo $utw->FormatTags($utw->GetTagsForTagString('"' . $tag . '"'), $utw->GetFormatForType($format));
		break;

	case 'shrinkrel':
		echo "$post-$tag|";
		echo $utw->FormatTags($utw->GetTagsForTagString('"' . $tag . '"'), $utw->GetFormatForType($format . "item"));
		break;

	case 'editsynonyms':

		echo '<input type="text" name="synonyms" value="' . $utw->FormatTags($utw->GetSynonymsForTag("", $tag), array("first"=>"%tag%", "default"=>", %tag%")) . '" />';
		break;

	case 'tagSearch':
		$tagset = explode('|',$tag);

		for ($i = 0; $i < count($tagset); $i++) {
			if (trim($tagset[$i]) <> "") {
				$taglist[] = "'" . trim($tagset[$i]) . "'";
			}
		}

		if (count($taglist) > 0) {
			$searchtype = $_REQUEST['type'];
			$op = "";
			$tags = $utw->GetTagsForTagString( implode(',',$taglist));

			if ($searchtype == "any") {
				$posts = $utw->GetPostsForAnyTags($tags);
				$op = "or";
			} else {
				$posts = $utw->GetPostsForTags($tags);
				$op = "and";
			}

			echo "<h4>Matches for ";
			echo $utw->FormatTags($tags, array('first'=>'%taglink%', 'default'=>', %taglink%', 'last'=>" $op %taglink%"));
			echo "</h4>";
			echo $utw->FormatPosts($posts, array('first'=>'<dl><dt>%postlink%</dt><dd>%excerpt%</dd>','default'=>'<dt>%postlink%</dt><dd>%excerpt%</dd>', 'last'=>'<dt>%postlink%</dt><dd>%excerpt%</dd></dl>', 'none'=>'No Matching Posts'));
		}

		break;
	}

?>