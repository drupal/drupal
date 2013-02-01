<?php

/**
 * @file
 * Default theme implementation to present feeds as list items.
 *
 * Each iteration generates a single feed source or category.
 *
 * Available variables:
 * - $title: Title of the feed or category.
 * - $summary_list: Render array of unordered feed items list.
 * - $source_url: URL to the local source or category.
 *
 * @see template_preprocess()
 * @see template_preprocess_aggregator_summary_items()
 *
 * @ingroup themeable
 */
?>
<h3><?php print $title; ?></h3>
<?php print render($summary_list); ?>
<div class="links">
  <a href="<?php print $source_url; ?>"><?php print t('More'); ?></a>
</div>
