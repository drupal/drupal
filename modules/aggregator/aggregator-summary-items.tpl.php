<?php
// $Id$

/**
 * @file aggregator-summary-items.tpl.php
 * Default theme implementation to present feeds as list items.
 *
 * Each iteration generates a single feed source or category.
 *
 * Available variables:
 * - $title: Title of the feed or category.
 * - $summary_list: Unordered list of linked feed items generated through
 *   theme_item_list().
 * - $source_url: URL to the local source or category.
 *
 * @see template_preprocess()
 * @see template_preprocess_aggregator_summary-items()
 */
?>
<h2><?php print $title; ?></h2>
<?php print $summary_list; ?>
<div class="links">
  <a href="<?php print $source_url; ?>"><?php print t('More'); ?></a>
</div>
