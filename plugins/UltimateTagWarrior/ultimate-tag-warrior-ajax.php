<?php
require('../../../wp-blog-header.php');
include_once('ultimate-tag-warrior-core.php');

$keywordAPISite = "api.search.yahoo.com";
$keywordAPIUrl = "/ContentAnalysisService/V1/termExtraction";

$appID = "wp-UltimateTagWarrior";

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
		echo $utw->FormatTags($utw->GetTagsForTagString('"' . $tag . '"'), $utw->GetFormatForType($format));
		break;

	case 'shrinkrel':
		echo "$post-$tag|";
		echo $utw->FormatTags($utw->GetTagsForTagString('"' . $tag . '"'), $utw->GetFormatForType($format . "item"));
		break;


	case 'requestKeywords':
		$sock = fsockopen($keywordAPISite, 80, $errno, $errstr, 30);
		if (!$sock) die("$errstr ($errno)\n");

		$data = "appid=" . $appID . "&context=" . $HTTP_RAW_POST_DATA;

		fputs($sock, "POST $keywordAPIUrl HTTP/1.0\r\n");
		fputs($sock, "Host: $keywordAPISite\r\n");
		fputs($sock, "Content-type: application/x-www-form-urlencoded\r\n");
		fputs($sock, "Content-length: " . strlen($data) . "\r\n");
		fputs($sock, "Accept: */*\r\n");
		fputs($sock, "\r\n");
		fputs($sock, "$data\r\n");
		fputs($sock, "\r\n");

		$headers = "";
		while ($str = trim(fgets($sock, 4096)))
		  $headers .= "$str\n";

		print "\n";

		$body = "";
		while (!feof($sock))
		  $body .= fgets($sock, 4096);

		fclose($sock);

		$loc = strpos($body, "<Result>", 0);
		while($loc < strlen($body) && $loc != false) {
			$loc += 8; // start of the tag
			$end = strpos($body, "</Result>", $loc);

			echo "<a href=\"javascript:addTag('" . str_replace(' ','_',substr($body, $loc, $end-$loc)) . "')\">" . substr($body, $loc, $end-$loc) . "</a> ";
			$tagstr .= "'" . str_replace(' ','_',substr($body, $loc, $end-$loc)) . "',";

			$loc = strpos($body, "<Result>", $end);
		}

		$tagstr .= "''";

		$utw->ShowRelatedTags($utw->GetTagsForTagString($tagstr), "<a href=\"javascript:addTag('%tag%')\">%tagdisplay%</a> ");

		break;
	}

?>