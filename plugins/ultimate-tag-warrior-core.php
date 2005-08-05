<?php
$tabletags = $table_prefix . "tags";
$tablepost2tag = $table_prefix . "post2tag";

$tag_cache;

class UltimateTagWarriorCore {

	/* Fundamental functions for dealing with tags */
	/* The post corresponding to the postID are updated to be the tags in the list.  Previously assigned
		tags not in the list are deleted. */
	function SaveTags($postID, $tags) {
		global $tabletags, $tablepost2tag, $wpdb;

		$tags = array_flip(array_flip($tags));

		foreach($tags as $tag) {
			if ($tag <> "") {
				$q = "SELECT id FROM $tabletags WHERE tag='$tag' limit 1";
				$tagid = $wpdb->get_var($q);

				if (is_null($tagid)) {
					$q = "INSERT INTO $tabletags (tag) VALUES ('$tag')";
					$wpdb->query($q);
					$tagid = $wpdb->insert_id;
				}

				$q = "SELECT rel_id FROM $tablepost2tag WHERE post_id = '$postID' AND tag_id = '$tagid'";

				if ( is_null($wpdb->get_var($q))) {
					$q = "INSERT INTO $tablepost2tag (post_id, tag_id) VALUES ('$postID','$tagid')";
					$wpdb->query($q);
				}

				$taglist .= $tagid . ", ";
			}
		}

		// Remove any tags that are no longer associated with the post.

		if ($taglist == "") {
			// since "not in ()" doesn't play nice.
			$q = "delete from $tablepost2tag where post_id = $postID";
		} else {
			// lop off the trailing space+comma
			$taglist = substr($taglist, 0 ,-2);

			$q = "delete from $tablepost2tag where post_id = $postID and tag_id not in ($taglist)";
		}
		$wpdb->query($q);
	}

	/* Adds the specified tag to the post corresponding with the post ID */
	function AddTag($postID, $tag) {
		global $tabletags, $tablepost2tag, $wpdb;

		if ($tag <> "") {

			$q = "SELECT id FROM $tabletags WHERE tag='$tag' limit 1";
			$tagid = $wpdb->get_var($q);

			if (is_null($tagid)) {
				$q = "INSERT INTO $tabletags (tag) VALUES ('$tag')";
				$wpdb->query($q);
				$tagid = $wpdb->insert_id;
			}

			$q = "SELECT rel_id FROM $tablepost2tag WHERE post_id = '$postID' AND tag_id = '$tagid'";

			if ( is_null($wpdb->get_var($q))) {
				$q = "INSERT INTO $tablepost2tag (post_id, tag_id) VALUES ('$postID','$tagid')";
				$wpdb->query($q);
			}
		}
	}

	/* Adds the specified tag to the post corresponding with the post ID */
	function RemoveTag($postID, $tag) {
		global $tabletags, $tablepost2tag, $wpdb;

		if ($tag <> "") {

			$q = "SELECT id FROM $tabletags WHERE tag='$tag' limit 1";
			$tagid = $wpdb->get_var($q);

			if (!is_null($tagid)) {
				$q = "DELETE FROM $tablepost2tag WHERE post_id = '$postID' AND tag_id = '$tagid'";

				$wpdb->query($q);
			}

			$q = "SELECT count(*) FROM $tablepost2tag WHERE tag_id = '$tagid'";

			if ( 0 == $wpdb->get_var($q)) {
				$q = "DELETE FROM $tabletag WHERE ID = $tagid";
				$wpdb->query($q);
			}
		}
	}

	/*
	 * Add any categories assigned to the post as tags.  This retains any exising tags.
	 */
	function SaveCategoriesAsTags($postID) {
		global $wpdb, $tablepost2tag, $wpdb;

		$default = get_option('default_category');

		$categories = $wpdb->get_results("SELECT c.cat_name FROM $wpdb->post2cat p2c INNER JOIN $wpdb->categories c ON p2c.category_id = c.cat_id WHERE p2c.post_id = $postID AND c.cat_ID != $default");
		$tags = $this->GetTagsForPost($postID);

		$alltags = array();
		if ($tags) {
			foreach($tags as $tag) {
				$alltags[] = $tag->tag;
			}
		}

		if ($categories) {
			foreach($categories as $cat) {
				$alltags[] = str_replace(" ", "_", $cat->cat_name);
			}
		}

		if (count($alltags) > 0) {
			$this->SaveTags($postID, $alltags);
		}
	}

	/*
	 * Add any tags, from the specified custom field as tags.  This retains any existing tags.
	 */
	function SaveCustomFieldAsTags($postID, $fieldName, $separator) {
		if (!$fieldName || !$separator) return;

		$allExisting = get_post_meta($postID, $fieldName, false);

		$tags = $this->GetTagsForPost($postID);

		$alltags = array();

		if ($tags) {
			foreach($tags as $tag) {
				$alltags[] = $tag->tag;
			}
		}

		foreach ($allExisting as $existing) {
			$items = explode($separator, $existing);
			foreach ($items as $tag) {
				$alltags[] = str_replace(" ", "_", trim($tag));
			}
		}

		if (count($alltags) > 0) {
			$this->SaveTags($postID, $alltags);
		}
	}

	/*
	 * Write the set of tags to the custom field specified.
	 * If the separator is anything but a space; -'s and _' will be converted back to spaces.
	 * NB.  It's generally a good idea to call SaveCustomFieldAsTags first.
	 */
	function SaveTagsAsCustomField($postID, $fieldName, $separator) {
		$tags = $this->GetTagsForPost($postID);

		if ($tags) {
			foreach ($tags as $tag) {
				if ($separator == " ") {
					$tagstr .= $tag->tag . $separator;
				} else {
					$tagstr .= str_replace("-", " ", str_replace("_"," ",$tag->tag)) . $separator;
				}
			}

			$tagstr = substr($tagstr, 0, strlen($separator)*-1);
		}
		delete_post_meta($postID, $fieldName);
		add_post_meta($postID, $fieldName, $tagstr);
	}

	function DeleteTags($postID) {
		global $tabletags, $tablepost2tag, $wpdb;

		$query = "DELETE FROM $tablepost2tag WHERE post_id = $postID";
		$wpdb->query($query);
	}

	function DeletePostTags($postID) {
		$this->DeleteTags($postID);
	}

	function GetTagsForTagString($tags) {
		global $wpdb, $tabletags;
		$q = "SELECT * FROM $tabletags WHERE tag IN ($tags)";
		return $wpdb->get_results($q);
	}

	function GetCurrentTagSet() {
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
		return ($this->GetTagsForTagString($taglist));
	}

	function TidyTags() {
		global $wpdb, $tablepost2tag, $tabletags;

		/* Phase 1:  delete the post-tag relationships from posts which have been deleted */
		$q = "SELECT post_id FROM $tablepost2tag left join $wpdb->posts on ID = post_id where ID is null group by post_id";
		$orphanpostids = $wpdb->get_results($q);

		if ($orphanpostids) {
			foreach ($orphanpostids as $orphanpostid) {
				$q = "DELETE FROM $tablepost2tag WHERE post_id = $orphanpostid->post_id";
				$wpdb->query($q);
			}
		}

		/* Phase 2:  delete any tags which are no longer in use */
		$q = "SELECT t.id FROM $tabletags t LEFT JOIN $tablepost2tag p2t ON p2t.tag_id = t.id WHERE p2t.tag_id IS NULL";
		$orphantagids = $wpdb->get_results($q);

		if ($orphantagids) {
			foreach ($orphantagids as $orphantagid) {
				$q = "DELETE FROM $tabletags where id = $orphantagid->id";
				$wpdb->query($q);
			}
		}

		/* Phase 3:  consolidate any duplicate tags */
		$q = "SELECT tag, MIN(id) as lowid, COUNT(*) cnt FROM $tabletags GROUP BY tag HAVING cnt > 1";
		$duplicatetags = $wpdb->get_results($q);

		if ($duplicatetags) {
			foreach($duplicatetags as $duplicatetag) {
				$trueid = $duplicatetag->lowid;

				$duplicatetagids = $wpdb->get_results("SELECT id FROM $tabletags WHERE tag = '$duplicatetag->tag' AND id != $trueid");
				$tagidstr = "";
				if ($duplicatetagids) {
					foreach($duplicatetagids as $tagid) {
						$tagidstr .= $tagid->id . ', ';
					}

					$tagidstr = substr($tagidstr, 0, -2);
				}

				$effectedposts = $wpdb->get_results("SELECT post_id FROM $tablepost2tag WHERE tag_id IN ($tagidstr) OR tag_id = $trueid");

				foreach($effectedposts as $post) {
					if(is_null($wpdb->get_var("SELECT rel_id FROM $tablepost2tag WHERE post_id = $post->post_id AND tag_id = $trueid"))) {
						$wpdb->query("INSERT INTO $tablepost2tag (post_id, tag_id) VALUES ($post->post_id, $trueid)");
					}
				}

				if ($tagidstr) {
					$wpdb->query("DELETE FROM $tablepost2tag WHERE tag_id IN ($tagidstr)");

					$wpdb->query("DELETE FROM $tabletags WHERE id IN ($tagidstr)");
				}
			}
		}
	}








	/* Functions for the tags associated with a post */
	function ShowTagsForPost($postID, $format) {
		echo $this->FormatTags($this->GetTagsForPost($postID), $format);
	}

	function GetTagsForPost($postID) {
		global $tabletags, $tablepost2tag, $wpdb;

		$q = "SELECT DISTINCT t.tag FROM $tabletags t INNER JOIN $tablepost2tag p2t ON p2t.tag_id = t.id INNER JOIN $wpdb->posts p ON p2t.post_id = p.ID AND p.ID=$postID";
		return($wpdb->get_results($q));
	}

	function GetPostsForTag($tag) {
		global $tabletags, $tablepost2tag, $wpdb;

		if (is_object($tag)) {
			$tag = $tag->tag;
		}

		$now = current_time('mysql', 1);

		   $q = <<<SQL
		SELECT * from
			$tabletags t, $tablepost2tag p2t, $wpdb->posts p
		WHERE t.ID = p2t.tag_id
		  AND p.ID = p2t.post_id
		  AND t.tag = '$tag'
		  AND post_date_gmt < '$now'
		  AND post_status = 'publish'
		ORDER BY post_date desc
SQL;

		   return ($wpdb->get_results($q));
	}









	/* Functions for the related tags */
	function ShowRelatedTags($tags, $format) {
		echo $this->FormatTags($this->GetRelatedTags($tags), $format);
	}

	function GetRelatedTags($tags) {
		global $wpdb, $tabletags, $tablepost2tag;

		$now = current_time('mysql', 1);

		$taglist = "'" . $tags[0]->tag . "'";
		$tagcount = count($tags);
		if ($tagcount > 1) {
			for ($i = 1; $i <= $tagcount; $i++) {
				$taglist = $taglist . ", '" . $tags[$i]->tag . "'";
			}
		}

		$q = <<<SQL
		SELECT p2t.post_id
			 FROM $tablepost2tag p2t, $tabletags t, $wpdb->posts p
			 WHERE p2t.tag_id = t.id
			 AND p2t.post_id = p.ID
			 AND (t.tag IN ($taglist))
			 AND post_date_gmt < '$now'
			 AND post_status = 'publish'
			 GROUP BY p2t.post_id HAVING COUNT(p2t.post_id)=$tagcount
SQL;
		$postids = $wpdb->get_results($q);
		if ($postids) {

			$postidlist = $postids[0]->post_id;

			for ($i = 1; $i <= count($postids); $i++) {
				$postidlist = $postidlist . ", '" . $postids[$i]->post_id . "'";
			}

			$q = <<<SQL
		SELECT t.tag, COUNT(p2t.post_id) AS count
		FROM $tablepost2tag p2t, $tabletags t, $wpdb->posts p
		WHERE p2t.post_id IN ($postidlist)
		AND p2t.post_id = p.ID
		AND t.tag NOT IN ($taglist)
		AND t.id = p2t.tag_id
		AND post_date_gmt < '$now'
		AND post_status = 'publish'
		GROUP BY p2t.tag_id
		ORDER BY count DESC, t.tag ASC
SQL;

			return $wpdb->get_results($q);
		}
	}

	function ShowRelatedPosts($tags, $format) {
		echo $this->FormatPosts($this->GetRelatedPosts($tags), $format);
	}

	function GetRelatedPosts($tags) {
		global $wpdb, $tabletags, $tablepost2tag, $post;

		$now = current_time('mysql', 1);

		$taglist = "'" . $tags[0]->tag . "'";
		$tagcount = count($tags);
		if ($tagcount > 1) {
			for ($i = 1; $i <= $tagcount; $i++) {
				$taglist = $taglist . ", '" . $tags[$i]->tag . "'";
			}
		}

		if ($post->ID) {
			$notclause = "AND p.ID != $post->ID";
		}

		$q = <<<SQL
		SELECT DISTINCT p.*, count(p2t.post_id) as cnt
			 FROM $tablepost2tag p2t, $tabletags t, $wpdb->posts p
			 WHERE p2t.tag_id = t.id
			 AND p2t.post_id = p.ID
			 AND (t.tag IN ($taglist))
			 AND post_date_gmt < '$now'
			 AND post_status = 'publish'
			 $notclause
			 GROUP BY p2t.post_id
			 ORDER BY cnt desc
SQL;

		return $wpdb->get_results($q);
	}











	/* Functions for popular tags */
	function ShowPopularTags($maximum, $format, $order='count', $direction='desc') {
		echo $this->FormatTags($this->GetPopularTags($maximum, $order, $direction), $format);
	}

	function GetPopularTags($maximum, $order, $direction) {
		global $wpdb, $tabletags, $tablepost2tag;

		if ($order <> "tag" && $order <> "count") { $order = "tag"; }
		if ($direction <> "asc" && $direction <> "desc") { $direction = "asc"; }

		$now = current_time('mysql', 1);

		$query = <<<SQL
			select tag, count(p2t.post_id) as count
			from $tabletags t inner join $tablepost2tag p2t on t.id = p2t.tag_id
							  inner join $wpdb->posts p on p2t.post_id = p.ID
			 WHERE post_date_gmt < '$now'
			 AND post_status = 'publish'
			group by t.tag
			having count > 0
			order by $order $direction
SQL;
		if ($maximum > 0) {
			$query .= " limit $maximum";
		}

		return $wpdb->get_results($query);
	}

	function GetWeightedTags($order, $direction, $limit = 150) {
		global $wpdb, $tabletags, $tablepost2tag;

		if ($order <> "tag" && $order <> "weight") { $order = "weight"; }
		if ($direction <> "asc" && $direction <> "desc") { $direction = "desc"; }

		$totaltags = $this->GetDistinctTagCount();
		$maxtag = $this->GetMostPopularTagCount();

		if ($totaltags == 0 || $maxtag == 0) {
			return;
		}

		$now = current_time('mysql', 1);

		$query = <<<SQL
			select tag, count(p2t.post_id) as count, ((count(p2t.post_id)/$totaltags)*100) as weight, ((count(p2t.post_id)/$maxtag)*100) as relativeweight
			from $tabletags t inner join $tablepost2tag p2t on t.id = p2t.tag_id
							  inner join $wpdb->posts p on p2t.post_id = p.ID
			 WHERE post_date_gmt < '$now'
			 AND post_status = 'publish'

			group by t.tag
			order by $order $direction
			limit $limit
SQL;

		return $wpdb->get_results($query);
	}

	function GetDistinctTagCount() {
		global $wpdb, $tablepost2tag;

		return $wpdb->get_var("select count(*) from $tablepost2tag p2t inner join $wpdb->posts p on p2t.post_id = p.ID WHERE post_date_gmt < '" . current_time('mysql', 1) . "' AND post_status = 'publish'");
	}

	function GetMostPopularTagCount() {
		global $wpdb, $tabletags, $tablepost2tag;

		return $wpdb->get_var("select count(p2t.post_id) cnt from $tabletags t inner join $tablepost2tag p2t on t.id = p2t.tag_id inner join $wpdb->posts p on p2t.post_id = p.ID WHERE post_date_gmt < '" . current_time('mysql', 1) . "' AND post_status = 'publish' group by t.tag order by cnt desc limit 1");
	}








	/* Functions for formatting things*/
	function FormatTags($tags, $format) {
		if (is_array($format) && $format["pre"]) {
			$out .= $this->FormatTag(null, $format["pre"]);
		}

		if ($tags) {
			for ($i = 0; $i < count($tags); $i++) {
				if (is_array($format)) {
					if ($i == 0 && $format["first"]) {
						$out .= $this->FormatTag($tags[$i], $format["first"]);
					} else if ($i == (count($tags) -1) && $format["last"]) {
						$out .= $this->FormatTag($tags[$i], $format["last"]);
					} else {
						$out .= $this->FormatTag($tags[$i], $format["default"]);
					}
				} else {
					$out .= $this->FormatTag($tags[$i], $format);
				}
			}
		} else {
			if (is_array($format) && $format["none"]) {
				$out .= $format["none"];
			}
		}

		if (is_array($format) && $format["post"]) {
			$out .= $this->FormatTag(null, $format["post"]);
		}

		return $out;
	}

	function FormatTag($tag, $format) {
		$tag_display = str_replace('_',' ', $tag->tag);
		$tag_display = str_replace('-',' ',$tag_display);
		$tag_name = strtolower($tag->tag);
		$baseurl = get_option('utw_base_url');
		$home = get_option('home');
		$siteurl = get_option('siteurl');



		$prettyurls = get_option('utw_use_pretty_urls');

		global $post;

		// This feels so... dirty.
		if ($prettyurls == "yes") {
			$format = str_replace('%tagurl%', "$home$baseurl$tag_name", $format);
			$format = str_replace('%taglink%', "<a href=\"$home$baseurl$tag_name\" rel=\"tag\">$tag_display</a>", $format);
		} else {
			$format = str_replace('%tagurl%', "$home/index.php?tag=$tag_name", $format);
			$format = str_replace('%taglink%', "<a href=\"$home/index.php?tag=$tag_name\" rel=\"tag\">$tag_display</a>", $format);
		}

		$format = str_replace('%tag%', $tag_name, $format);
		$format = str_replace('%tagdisplay%', $tag_display, $format);
		$format = str_replace('%tagcount%', $tag->count, $format);
		$format = str_replace('%tagweight%', $tag->weight, $format);
		$format = str_replace('%tagweightint%', ceil($tag->weight), $format);
		$format = str_replace("%tagweightcolor%", $this->GetColorForWeight($tag->weight), $format);
		$format = str_replace("%tagweightfontsize%", $this->GetFontSizeForWeight($tag->weight), $format);

		$format = str_replace('%tagrelweight%', $tag->relativeweight, $format);
		$format = str_replace('%tagrelweightint%', ceil($tag->relativeweight), $format);
		$format = str_replace("%tagrelweightcolor%", $this->GetColorForWeight($tag->relativeweight), $format);
		$format = str_replace("%tagrelweightfontsize%", $this->GetFontSizeForWeight($tag->relativeweight), $format);

		$format = str_replace('%technoratitag%', "<a href=\"http://www.technorati.com/tag/$tag_name\" rel=\"tag\">$tag_display</a>", $format);
		$format = str_replace('%flickrtag%', "<a href=\"http://www.flickr.com/tags/$tag_name\" rel=\"tag\">$tag_display</a>", $format);
		$format = str_replace('%delicioustag%', "<a href=\"http://del.icio.us/tag/$tag_name\" rel=\"tag\">$tag_display</a>", $format);
		$format = str_replace('%wikipediatag%', "<a href=\"http://en.wikipedia.org/wiki/$tag_name\" rel=\"tag\">$tag_display</a>", $format);

		$format = str_replace('%technoratiicon%', "<a href=\"http://www.technorati.com/tag/$tag_name\" rel=\"tag\"><img src=\"$siteurl/wp-content/plugins/UltimateTagWarrior/technoratiicon.jpg\" border=\"0\" hspace=\"1\"/></a>", $format);
		$format = str_replace('%flickricon%', "<a href=\"http://www.flickr.com/tags/$tag_name\" rel=\"tag\"><img src=\"$siteurl/wp-content/plugins/UltimateTagWarrior/flickricon.jpg\" border=\"0\" hspace=\"1\"/></a>", $format);
		$format = str_replace('%deliciousicon%', "<a href=\"http://del.icio.us/tag/$tag_name\" rel=\"tag\"><img src=\"$siteurl/wp-content/plugins/UltimateTagWarrior/deliciousicon.jpg\" border=\"0\" hspace=\"1\"/></a>", $format);
		$format = str_replace('%wikipediaicon%', "<a href=\"http://en.wikipedia.org/wiki/$tag_name\" rel=\"tag\"><img src=\"$siteurl/wp-content/plugins/UltimateTagWarrior/wikiicon.jpg\" border=\"0\" hspace=\"1\"/></a>", $format);

		if ($post->ID) {
			$format = str_replace('%postid%', $post->ID, $format);
		} else {
			$format = str_replace('%postid%', $_REQUEST["post"], $format);
		}
		return $format;
	}

	function FormatPosts($posts, $format) {

		if (is_array($format) && $format["pre"]) {
			$out .= $format["pre"];
		}

		if ($posts) {
			for ($i = 0; $i < count($posts); $i++) {
				if (is_array($format)) {
					if ($i == 0 && $format["first"]) {
						$out .= $this->FormatPost($posts[$i], $format["first"]);
					} else if ($i == (count($posts) -1) && $format["last"]) {
						$out .= $this->FormatPost($posts[$i], $format["last"]);
					} else {
						$out .= $this->FormatPost($posts[$i], $format["default"]);
					}
				} else {
					$out .= $this->FormatPost($posts[$i], $format);
				}
			}
		} else {
			if (is_array($format) && $format["none"]) {
				$out .= $format["none"];
			}
		}

		if (is_array($format) && $format["post"]) {
			$out .= $format["post"];
		}

		return $out;
	}

	function FormatPost($post, $format) {
		$url = get_permalink($post->ID);

		$format = str_replace('%title%', $post->post_title, $format);
		$format = str_replace('%postlink%', "<a href=\"$url\">$post->post_title</a>", $format);

		return $format;
	}

	function GetFormatForType($formattype) {
		global $user_level;

		switch($formattype) {
			case "htmllist":
				return array ("default"=>"<li>%taglink%</li>", "none"=>"<li>No Tags</li>");

			case "commalist":
				return array ("default"=>"%taglink%, ", "last"=>"%taglink%", "none"=>"No Tags");

			case "technoraticommalist":
				return array ("default"=>"%technoratitag%, ", "last"=>"%technoratitag%", "none"=>"No Tags");

			case "superajax":
			case "superajaxitem":
				$default = "<span id=\"tags-%postid%-%tag%\">%taglink%";
				if ($user_level > 3) {
					$default .= "[<a href=\"javascript:sndReq('del', '%tag%', '%postid%')\">-</a>]";
					$post = " <input type=\"text\" size=\"9\" id=\"addTag-%postid%\"> <input type=\"button\" value=\"+\" onClick=\"sndReq('add', document.getElementById('addTag-%postid%').value, '%postid%')\">";
				}

				$default .= "<a href=\"javascript:sndReq('expand', '%tag%', '%postid%')\">&raquo;</a> </span>";
				$post .= "</span>";
				if ($formattype == "superajax") {
					return array("pre"=>"<span id=\"tags-%postid%\">","default"=>$default, "post"=>"$post");
				} else {
					return $default;
				}


			case "linkset":
				return "%taglink% %technoratiicon%%flickricon%%deliciousicon%%wikipediaicon%<a href=\"javascript:sndReq('shrink', '%tag%', '%postid%')\">&laquo;</a>&nbsp;";

			case "weightedlinearbar":
				return array("default"=>"<td width=\"%tagweightint%%\" style=\"background-color:%tagrelweightcolor%; border-right:1px solid black;\"><a href=\"%tagurl%\" title=\"%tagdisplay%\"><div>&nbsp;</div></a></td>", "pre"=>"<table cellpadding=\"0\" cellspacing=\"0\" style=\"border:1px solid black; border-right:0px\" width=\"100%\"><tr>", "post"=>"</tr></table>");

			case "weightedlongtail":
				// Thanks http://www.cssirc.com/codes/?code=23!
				$css = <<<CSS
				<style type="text/css">
				.longtail, .longtail li { list-style: none; margin: 0; padding: 0; }
				.longtail {position: relative; height: 100px;}
				.longtail:after { display: block; visibility: hidden; content: "."; height: 0; overflow: hidden; clear: both;}
				.longtail li {float: left; position: relative; height: 100%;width: 5px;margin:0px;background-color:#fff;}
				.longtail li div {position: absolute;bottom: 0; left: 0;width: 100%;background-color:#000;}
				</style>
CSS;
				return array("pre"=>"$css<ol class=\"longtail\">", "default"=>"<li><a href=\"%tagurl%\" title=\"%tagdisplay%\"><div style=\"height:%tagrelweightint%%\"></div></a></li>", "post"=>"</ol>");

			case "coloredtagcloud":
				return array("default"=>"<a href=\"%tagurl%\" style=\"color:%tagrelweightcolor%\">%tagdisplay%</a> ");

			case "sizedtagcloud":
				return array("default"=>"<a href=\"%tagurl%\" style=\"font-size:%tagrelweightfontsize%\">%tagdisplay%</a> ");

			case "coloredsizedtagcloud":
			case "sizedcoloredtagcloud":
				return array("default"=>"<a href=\"%tagurl%\" style=\"font-size:%tagrelweightfontsize%; color:%tagrelweightcolor%\">%tagdisplay%</a> ");

			// Thanks drac! http://lair.fierydragon.org/
			case "coloredsizedtagcloudwithcount":
				return array("default"=>"<a href=\"%tagurl%\" style=\"font-size:%tagrelweightfontsize%; color:%tagrelweightcolor%\">%tagdisplay%<sub style=\"font-size:60%; color:#ccc;\">%tagcount%</sub></a> ");

			case "postcommalist":
				return array ("default"=>"%postlink%, ", "last"=>"%postlink%", "none"=>"No Related Posts");

			case "posthtmllist":
				return array ("default"=>"<li>%postlink%</li>", "none"=>"<li>No Related Posts</li>");

			case "custom":
				return "";
		}
	}

	/* This is pretty filthy.  Doing math in hex is much too weird.  It's more likely to work,  this way! */
	function GetColorForWeight($weight) {
		if ($weight) {
			$weight = $weight/100;

			$max = get_option ('utw_tag_cloud_max_color');
			$min = get_option ('utw_tag_cloud_min_color');

			$minr = hexdec(substr($min, 1, 2));
			$ming = hexdec(substr($min, 3, 2));
			$minb = hexdec(substr($min, 5, 2));

			$maxr = hexdec(substr($max, 1, 2));
			$maxg = hexdec(substr($max, 3, 2));
			$maxb = hexdec(substr($max, 5, 2));

			$r = dechex(intval((($maxr - $minr) * $weight) + $minr));
			$g = dechex(intval((($maxg - $ming) * $weight) + $ming));
			$b = dechex(intval((($maxb - $minb) * $weight) + $minb));

			if (strlen($r) == 1) $r = "0" . $r;
			if (strlen($g) == 1) $g = "0" . $g;
			if (strlen($b) == 1) $b = "0" . $b;

			return "#$r$g$b";
		}
	}


	function GetFontSizeForWeight($weight) {
		$max = get_option ('utw_tag_cloud_max_font');
		$min = get_option ('utw_tag_cloud_min_font');

		$units = get_option ('utw_tag_cloud_font_units');
		if ($units == "") $units = '%';

		if ($max > $min) {
			$fontsize = (($weight/100) * ($max - $min)) + $min;

		} else {
			$fontsize = (((100-$weight)/100) * ($min - $max)) + $max;
		}

		return intval($fontsize) . $units;
	}





}



/* ultimate_get_posts()
Retrieves the posts for the tags specified in $_GET["tag"].  Gets the intersection when there are multiple tags.
*/
function ultimate_get_posts() {
	global $wpdb, $table_prefix, $posts, $table_prefix, $tableposts, $id, $wp_query;
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
	// Thanks Mark! http://txfx.net/
	$posts = apply_filters('the_posts', $posts);
	$wp_query->posts = $posts;
	$wp_query->post_count = count($posts);
	update_post_caches($posts);
	if ($wp_query->post_count > 0)
		$wp_query->post = $wp_query->posts[0];
}

?>