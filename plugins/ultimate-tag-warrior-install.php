<?php
require('../../wp-blog-header.php');

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
?>

<p>Ultimate Tag Warrior is done with your database.  Two tables were added:</p>

<dl>
	<dt><?= $tabletags ?></dt>
	<dd>Contains the names of tags, and their ID</dd>
	<dt><?= $tablepost2tag ?></dt>
	<dd>Links tags to posts</dd>
</dl>