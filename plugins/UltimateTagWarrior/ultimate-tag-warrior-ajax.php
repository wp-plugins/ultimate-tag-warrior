<?php
ini_set("include_path", ini_get('include_path') . PATH_SEPARATOR . ".");

require('../../../wp-blog-header.php');
include_once('ultimate-tag-warrior-core.php');

$keywordAPISite = "tagyu.com";
$keywordAPIUrl = "/api/suggest/";

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


	case 'requestKeywords':
		if ($debug) {
			echo "Requested keywords...<br />";
		}

		$noUnicode = preg_replace("/%u[0-9A-F]{4}/i","",$HTTP_RAW_POST_DATA);


		$data = urlencode(strip_tags(urldecode($noUnicode)));

		$data = str_replace('%2F','/',$data);
		$data = str_replace('%09', '', $data);
		$data = str_replace('%26%238217%3B','\'',$data);
		$data = str_replace('%26%238220%3B','"',$data);
		$data = str_replace('%26%238221%3B','"',$data);
		$data = str_replace('%26%23038%3B','%26',$data);

		$xml = "";
		$tagyu_url = 'http://' . $keywordAPISite . $keywordAPIUrl . $data;

		if ($debug) {
			echo "Send Request to Tagyu...<br />";
		}
		if ($bypost) {
			$sock = fsockopen($keywordAPISite, 80, $errno, $errstr, 30);
			if (!$sock) die("$errstr ($errno)\n");

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

			while (!feof($sock))
			  $xml .= fgets($sock, 4096);

			fclose($sock);
		} else if (function_exists('curl_exec')) {
			$curl_conn = curl_init($tagyu_url);
			curl_setopt( $curl_conn, CURLOPT_RETURNTRANSFER, 1 );

			$xml = curl_exec($curl_conn);
		} else {
			$sock = fsockopen($keywordAPISite, 80, $errno, $errstr, 30);
			if (!$sock) die("$errstr ($errno)\n");

			fputs($sock, "GET " . $keywordAPIUrl . $data . " HTTP/1.0\r\n\r\n");
			fputs($sock, "Host: $keywordAPISite\r\n");
			fputs($sock, "Accept: */*\r\n");
			$headers = "";
			while ($str = trim(fgets($sock, 4096)))
			  $headers .= "$str\n";

			print "\n";

			while (!feof($sock))
			  $xml .= fgets($sock, 4096);

			fclose($sock);
		} /* else {
			// Fall back to whatever this approach is called if it isn't.

			$xml = file_get_contents($tagyu_url);
		} */

		if ($debug) {
		echo "Parse response...<br />";
		}
		$hasTags = false;
		if (strpos($xml,'<error>') === FALSE) {
			$loc = strpos($xml, "<tag>", 0);
			while($loc < strlen($xml) && $loc != false) {
				$loc += 5; // start of the tag
				$end = strpos($xml, "</tag>", $loc);

				echo "<a href=\"javascript:addTag('" . str_replace(' ','_',substr($xml, $loc, $end-$loc)) . "')\">" . substr($xml, $loc, $end-$loc) . "</a> ";
				$tagstr .= "'" . str_replace(' ','_',substr($xml, $loc, $end-$loc)) . "',";

				$loc = strpos($xml, "<tag>", $end);
				$hasTags = true;
			}

			if ($hasTags) {
				// eat the trailing comma.
				$tagstr = substr($tagstr,0,-1);
			}
		} else {
			echo $xml;
		}

		if ($hasTags) {
			echo $utw->FormatTags($utw->GetTagsForTagString($tagstr), "<a href=\"javascript:addTag('%tag%')\">%tagdisplay%</a> ");
		} else {
			echo "No tag suggestions";
		}
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