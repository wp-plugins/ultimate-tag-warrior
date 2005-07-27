<?php
require('../../../wp-blog-header.php');

$tabletags = $table_prefix . "tags";
$tablepost2tag = $table_prefix . "post2tag";

$q = <<<SQL
CREATE TABLE IF NOT EXISTS $tabletags (
  ID int(11) NOT NULL auto_increment,
  tag varchar(255) NOT NULL default '',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;
SQL;

$wpdb->query($q);

$q = <<<SQL
CREATE TABLE IF NOT EXISTS $tablepost2tag (
  rel_id int(11) NOT NULL auto_increment,
  tag_id int(11) NOT NULL default '0',
  post_id int(11) NOT NULL default '0',
  PRIMARY KEY  (rel_id)
) TYPE=MyISAM;
SQL;

$wpdb->query($q);

add_option('utw_include_technorati_links', 'yes', 'Indicates whether technorati links should be automatically appended to the content.', 'yes');
add_option('utw_include_local_links', 'no', 'Indicates whether local tag links should be automatically appended to the content.', 'yes');
add_option('utw_base_url', '/tag/', 'The base url for tag links i.e. {base url}{sometag}', 'yes');
add_option('utw_include_categories_as_tags', 'no', 'Will include any selected categories as tags', 'yes');

add_option('utw_use_pretty_urls', 'no', 'Use /tag/tag urls instead of index.php?tag=tag urls', 'yes');

add_option('utw_tag_cloud_max_color', '#000000', 'The color of popular tags in tag clouds', 'yes');
add_option('utw_tag_cloud_min_color', '#FFFFFF', 'The color of unpopular tags in tag clouds', 'yes');

add_option('utw_tag_cloud_max_font', '250', 'The maximum font size (as a percentage) for popular tags in tag clouds', 'yes');
add_option('utw_tag_cloud_min_font', '70', 'The minimum font size (as a percentage) unpopular tags in tag clouds', 'yes');

add_option ('utw_tag_cloud_font_units', '%', 'The units to display the font sizes with, on tag clouds.');

add_option('utw_tag_line_max_color', '#000000', 'The color of popular tags in a tag line', 'yes');
add_option('utw_tag_line_min_color', '#FFFFFF', 'The color of unpopular tags in a tag line', 'yes');

add_option('utw_long_tail_max_color', '#000000', 'The color of popular tags in a long tail chart', 'yes');
add_option('utw_long_tail_min_color', '#FFFFFF', 'The color of unpopular tags in a long tail chart', 'yes');

add_option('utw_always_show_links_on_edit_screen', 'no', 'Always display existing tags as links; regardles of how many there are', 'yes');

?>

<p>Ultimate Tag Warrior is done with your database.  Two tables were added:</p>

<dl>
	<dt><?= $tabletags ?></dt>
	<dd>Contains the names of tags, and their ID</dd>
	<dt><?= $tablepost2tag ?></dt>
	<dd>Links tags to posts</dd>
</dl>

<p>Along with a whole pile of options.</p>