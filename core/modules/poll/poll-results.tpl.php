<?php

/**
 * @file
 * Default theme implementation to display the poll results in a block.
 *
 * Variables available:
 * - $title: The title of the poll.
 * - $results: The results of the poll.
 * - $votes: The total results in the poll.
 * - $links: Links in the poll.
 * - $nid: The nid of the poll
 * - $cancel_form: A form to cancel the user's vote, if allowed.
 * - $raw_links: The raw array of links.
 * - $vote: The choice number of the current user's vote.
 *
 * @see template_preprocess_poll_results()
 */
?>
<article class="poll">
  <?php if ($block): ?>
    <h3 class="poll-title"><?php print $title; ?></h3>
  <?php endif; ?>
  <dl>
    <?php print $results; ?>
  </dl>
  <div class="total">
    <?php print t('Total votes: @votes', array('@votes' => $votes)); ?>
  </div>
  <?php if (!empty($cancel_form)): ?>
    <?php print $cancel_form; ?>
  <?php endif; ?>
</article>
<?php if ($block): ?>
  <div class="links"><?php print $links; ?></div>
<?php endif; ?>
