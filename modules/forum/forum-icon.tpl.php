<?php
// $Id$

/**
 * @file forum-icon.tpl.php
 * Display an appropriate icon for a forum post.
 *
 * Available variables:
 * - $new_posts: Indicates whether or not the topic contains new posts.
 * - $icon: The icon to display. May be one of 'hot', 'hot-new', 'new',
 *   'default', 'closed', or 'sticky'.
 *
 * @see template_preprocess_forum_icon()
 * @see theme_forum_icon()
 */
?>
<?php if ($new_posts): ?>
  <a name="new">
<?php endif; ?>

<?php print theme('image', "misc/forum-$icon.png") ?>

<?php if ($new_posts): ?>
  </a>
<?php endif; ?>
