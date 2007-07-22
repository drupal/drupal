<?php
// $Id: forum-format-topic.tpl.php,v 1.1 2007/07/22 07:01:07 dries Exp $
/**
 * @file forum-format-topic.tpl.php
 * Default theme implementation to format a simple string indicated when and
 * by whom a topic was posted.
 *
 * Available variables:
 *
 * - $author: The author of the post.
 * - $time: How long ago the post was created.
 * - $topic: An object with the raw data of the post. Unsafe, be sure
 *   to clean this data before printing.
 *
 * @see template_preprocess_forum_format_topic()
 * @see theme_forum_format_topic()
 */
?>
<?php if ($topic->timestamp): ?>
  <?php print t(
  '@time ago<br />by !author', array(
    '@time' => $time,
    '!author' => $author,
    )); ?>
<?php else: ?>
  <?php print t('n/a'); ?>
<?php endif; ?>
