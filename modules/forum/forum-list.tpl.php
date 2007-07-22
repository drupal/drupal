<?php
// $Id
/**
 * @file forum-list.tpl.php
 * Default theme implementation to display a list of forums.
 *
 * Available variables:
 * - $forums: An array of forums to display.
 *
 * Each $forum in $forums contains:
 * - $forum->is_container: Is TRUE if the forum can contain other forums. Is
 *   FALSE if the forum can contain only topics.
 * - $forum->depth: How deep the forum is in the current hierarchy.
 * - $forum->name: The name of the forum.
 * - $forum->link: The URL to link to this forum.
 * - $forum->description: The description of this forum.
 * - $forum->new_topics: True if the forum contains unread posts.
 * - $forum->new_url: A URL to the forum's unread posts.
 * - $forum->new_text: Text for the above URL which tells how many new posts.
 * - $forum->old_topics: A count of posts that have already been read.
 * - $forum->num_posts: The total number of posts in the forum.
 * - $forum->last_reply: Text representing the last time a forum was posted or
 *   commented in.
 *
 * @see template_preprocess_forum_list()
 * @see theme_forum_list()
 */
?>
<table>
  <thead>
    <tr>
      <th><?php print t('Forum'); ?></th>
      <th><?php print t('Topics');?></th>
      <th><?php print t('Posts'); ?></th>
      <th><?php print t('Last post'); ?></th>
    </tr>
  </thead>
  <tbody>
  <?php // Keep a row count for striping. ?>
  <?php $row = 0; ?>
  <?php foreach ($forums as $forum): ?>
    <tr class="<?php print $row % 2 == 0 ? 'odd' : 'even';?>">
      <?php if ($forum->is_container): ?>
        <td colspan="4" class="container">
      <?php else: ?>
        <td class="forum">
      <?php endif; ?>
          <?php /* Enclose the contents of this cell with X divs, where X is the
                 * depth this forum resides at. This will allow us to use CSS
                 * left-margin for indenting.
                 */ ?>
          <?php $end_divs = ''; ?>
          <?php for ($i = 0; $i < $forum->depth; $i++): ?>
            <div class="indent">
            <?php $end_divs .= '</div>'; ?>
          <?php endfor; ?>
          <div class="name"><a href="<?php print $forum->link; ?>"><?php print $forum->name; ?></a></div>
          <div class="description"><?php print $forum->description; ?></div>
          <?php print $end_divs; ?>
        </td>
      <?php if (!$forum->is_container): ?>
        <td class="topics">
          <?php print $forum->num_topics ?>
          <?php if ($forum->new_topics): ?>
            <br />
            <a href="<?php print $forum->new_url; ?>"><?php print $forum->new_text; ?></a>
          <?php endif; ?>
        </td>
        <td class="posts"><?php print $forum->num_posts ?></td>
        <td class="last-reply"><?php print $forum->last_reply ?></td>
      <?php endif; ?>
    </tr>

    <?php $row++; ?>
  <?php endforeach; ?>
  </tbody>
</table>
