<?php
// vim: set ts=2 sw=2 sts=2 et:

/**
 * @file
 * Default theme implementation to display a list of forum topics.
 *
 * Available variables:
 * - $header: The table header. This is pre-generated with click-sorting
 *   information. If you need to change this, see
 *   template_preprocess_forum_topic_list().
 * - $pager: The pager to display beneath the table.
 * - $topics: An array of topics to be displayed.
 * - $topic_id: Numeric id for the current forum topic.
 *
 * Each $topic in $topics contains:
 * - $topic->icon: The icon to display.
 * - $topic->moved: A flag to indicate whether the topic has been moved to
 *   another forum.
 * - $topic->title: The title of the topic. Safe to output.
 * - $topic->message: If the topic has been moved, this contains an
 *   explanation and a link.
 * - $topic->zebra: 'even' or 'odd' string used for row class.
 * - $topic->comment_count: The number of replies on this topic.
 * - $topic->new_replies: A flag to indicate whether there are unread comments.
 * - $topic->new_url: If there are unread replies, this is a link to them.
 * - $topic->new_text: Text containing the translated, properly pluralized count.
 * - $topic->created: An outputtable string represented when the topic was posted.
 * - $topic->last_reply: An outputtable string representing when the topic was
 *   last replied to.
 * - $topic->timestamp: The raw timestamp this topic was posted.
 *
 * @package   LiteCommerce3 theme
 * @author    Creative Development LLC <info@cdev.ru>
 * @copyright Copyright (c) 2010 Creative Development LLC <info@cdev.ru>. All rights reserved
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2
 * @version   SVN: $Id: forum-topic-list.tpl.php 4784 2010-12-24 09:33:57Z xplorer $
 * @link      http://www.litecommerce.com/
 * @see       ____file_see____
 * @see       template_preprocess_forum_topic_list()
 * @see       theme_forum_topic_list()
 * @since     1.0.0
*/
?>
<table id="forum-topic-<?php print $topic_id; ?>" class="forum-topic-list">
  <thead>
    <tr><?php print $header; ?></tr>
  </thead>
  <tbody>
  <?php foreach ($topics as $topic): ?>
    <tr class="<?php print $topic->zebra;?>">
      <td class="title">
        <div class="icon"><?php print $topic->icon; ?></div>
        <div class="title"><?php print $topic->title; ?></div>
        <div class="created"><?php print $topic->created; ?></div>
      </td>
    <?php if ($topic->moved): ?>
      <td colspan="3"><?php print $topic->message; ?></td>
    <?php else: ?>
      <td class="replies">
        <div class="comment-count"><?php print $topic->comment_count; ?></div>
        <?php if ($topic->new_replies): ?>
          <div class="new-comment-count"><a href="<?php print $topic->new_url; ?>"><?php print $topic->new_text; ?></a></div>
        <?php endif; ?>
      </td>
      <td class="last-reply"><?php print $topic->last_reply; ?></td>
    <?php endif; ?>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php print $pager; ?>
