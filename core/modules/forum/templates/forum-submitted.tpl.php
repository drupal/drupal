<?php

/**
 * @file
 * Formats a forum post submission string.
 *
 * The submission string indicates when and by whom a topic was submitted.
 *
 * Available variables:
 * - $author: The author of the post.
 * - $time: How long ago the post was created.
 * - $topic: An object with the raw data of the post. Potentially unsafe. Be
 *   sure to clean this data before printing.
 *
 * @see template_preprocess_forum_submitted()
 * @see theme_forum_submitted()
 *
 * @ingroup themeable
 */
?>
<?php if ($time): ?>
  <span class="submitted">
  <?php print t('By !author @time ago', array(
    '@time' => $time,
    '!author' => $author,
    )); ?>
  </span>
<?php else: ?>
  <?php print t('n/a'); ?>
<?php endif; ?>
