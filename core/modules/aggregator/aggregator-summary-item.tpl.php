<?php

/**
 * @file
 * Default theme implementation to present a linked feed item for summaries.
 *
 * Available variables:
 * - $feed_url: Link to originating feed.
 * - $feed_title: Title of feed.
 * - $feed_age: Age of remote feed.
 * - $source_url: Link to remote source.
 * - $source_title: Locally set title for the source.
 *
 * @see template_preprocess()
 * @see template_preprocess_aggregator_summary_item()
 */
?>
<a href="<?php print $feed_url; ?>"><?php print $feed_title; ?></a>
<span class="age"><?php print $feed_age; ?></span>

<?php if ($source_url): ?>,
  <span class="source"><a href="<?php print $source_url; ?>"><?php print $source_title; ?></a></span>
<?php endif; ?>
