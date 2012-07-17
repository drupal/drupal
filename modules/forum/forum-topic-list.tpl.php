<?php

/**
 * @file
 * Displays a list of forum topics.
 *
 * Available variables:
 * - $header: The table header. This is pre-generated with click-sorting
 *   information. If you need to change this, see
 *   template_preprocess_forum_topic_list().
 * - $pager: The pager to display beneath the table.
 * - $topics: An array of topics to be displayed. Each $topic in $topics
 *   contains:
 *   - $topic->icon: The icon to display.
 *   - $topic->moved: A flag to indicate whether the topic has been moved to
 *     another forum.
 *   - $topic->title: The title of the topic. Safe to output.
 *   - $topic->message: If the topic has been moved, this contains an
 *     explanation and a link.
 *   - $topic->zebra: 'even' or 'odd' string used for row class.
 *   - $topic->comment_count: The number of replies on this topic.
 *   - $topic->new_replies: A flag to indicate whether there are unread
 *     comments.
 *   - $topic->new_url: If there are unread replies, this is a link to them.
 *   - $topic->new_text: Text containing the translated, properly pluralized
 *     count.
 *   - $topic->created: A string representing when the topic was posted. Safe
 *     to output.
 *   - $topic->last_reply: An outputtable string representing when the topic was
 *     last replied to.
 *   - $topic->timestamp: The raw timestamp this topic was posted.
 * - $topic_id: Numeric ID for the current forum topic.
 *
 * @see template_preprocess_forum_topic_list()
 * @see theme_forum_topic_list()
 *
 * @ingroup themeable
 */
?>
<table id="forum-topic-<?php print $topic_id; ?>">
  <thead>
    <tr><?php print $header; ?></tr>
  </thead>
  <tbody>
  <?php foreach ($topics as $topic): ?>
    <tr class="<?php print $topic->zebra;?>">
      <td class="icon"><?php print $topic->icon; ?></td>
      <td class="title">
        <div>
          <?php print $topic->title; ?>
        </div>
        <div>
          <?php print $topic->created; ?>
        </div>
      </td>
    <?php if ($topic->moved): ?>
      <td colspan="3"><?php print $topic->message; ?></td>
    <?php else: ?>
      <td class="replies">
        <?php print $topic->comment_count; ?>
        <?php if ($topic->new_replies): ?>
          <br />
          <a href="<?php print $topic->new_url; ?>"><?php print $topic->new_text; ?></a>
        <?php endif; ?>
      </td>
      <td class="last-reply"><?php print $topic->last_reply; ?></td>
    <?php endif; ?>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php print $pager; ?>
