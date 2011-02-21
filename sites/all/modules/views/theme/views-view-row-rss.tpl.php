<?php
// $Id: views-view-row-rss.tpl.php,v 1.1 2008/12/02 22:17:42 merlinofchaos Exp $
/**
 * @file views-view-row-rss.tpl.php
 * Default view template to display a item in an RSS feed.
 *
 * @ingroup views_templates
 */
?>
  <item>
    <title><?php print $title; ?></title>
    <link><?php print $link; ?></link>
    <description><?php print $description; ?></description>
    <?php print $item_elements; ?>
  </item>
