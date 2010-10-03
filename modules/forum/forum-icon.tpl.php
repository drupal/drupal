<?php
// $Id: forum-icon.tpl.php,v 1.7 2010/10/03 00:41:14 dries Exp $

/**
 * @file
 * Default theme implementation to display an appropriate icon for a forum post.
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
<div class="topic-status-<?php print $icon_class ?>" title="<?php print $icon_title ?>">
<?php if ($new_posts): ?>
  <a id="new">
<?php endif; ?>

  <span class="element-invisible"><?php print $icon_title ?></span>

<?php if ($new_posts): ?>
  </a>
<?php endif; ?>
</div>
