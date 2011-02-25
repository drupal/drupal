<?php

/**
 * @file forum-topic-navigation.tpl.php
 * Default theme implementation to display the topic navigation string at the
 * bottom of all forum topics.
 *
 * Available variables:
 *
 * - $prev: The node ID of the previous post.
 * - $prev_url: The URL of the previous post.
 * - $prev_title: The title of the previous post.
 *
 * - $next: The node ID of the next post.
 * - $next_url: The URL of the next post.
 * - $next_title: The title of the next post.
 *
 * - $node: The raw node currently being viewed. Contains unsafe data
 *   and any data in this must be cleaned before presenting.
 *
 * @see template_preprocess_forum_topic_navigation()
 * @see theme_forum_topic_navigation()
 */
?>
<?php if ($prev || $next): ?>
  <div class="forum-topic-navigation clear-block">
    <?php if ($prev): ?>
      <a href="<?php print $prev_url; ?>" class="topic-previous" title="<?php print t('Go to previous forum topic') ?>">‹ <?php print $prev_title ?></a>
    <?php endif; ?>
    <?php if ($next): ?>
      <a href="<?php print $next_url; ?>" class="topic-next" title="<?php print t('Go to next forum topic') ?>"><?php print $next_title ?> ›</a>
    <?php endif; ?>
  </div>
<?php endif; ?>
