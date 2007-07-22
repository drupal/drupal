<?php // $Id: forum-display.tpl.php,v 1.1 2007/07/22 07:01:07 dries Exp $
/**
 * @file forum-display.tpl.php
 * Default theme implementation to display a forum, which may contain forum
 * containers as well as forum topics.
 *
 * Variables available:
 *
 * - $links: An array of links that allow a user to post new forum topics.
 *   It may also contain a string telling a user they must log in in order
 *   to post.
 * - $forums: The forums to display (as processed by forum-list.tpl.php)
 * - $topics: The topics to display (as processed by forum-topic-list.tpl.php)
 *
 * @see template_preprocess_forum_display()
 * @see theme_forum_display()
 *
 */
?>

<?php if ($forums_defined): ?>

<div id="forum">
  <?php print theme('links', $links); ?>
  <?php print $forums; ?>
  <?php print $topics; ?>
</div>
<?php endif; ?>
