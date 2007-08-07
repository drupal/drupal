<?php
// $Id: comment-folded.tpl.php,v 1.2 2007/08/07 08:39:35 goba Exp $

/**
 * @file comment-folded.tpl.php
 * Default theme implementation for folded comments.
 *
 * Available variables:
 * - $title: Linked title to full comment.
 * - $new: New comment marker.
 * - $author: Comment author. Can be link or plain text.
 * - $date: Date and time of posting.
 * - $comment: Full comment object.
 *
 * @see template_preprocess_comment_folded()
 * @see theme_comment_folded()
 */
?>
<div class="comment-folded">
  <span class="subject"><?php print $title .' '. $new; ?></span><span class="credit"><?php print t('by') .' '. $author; ?></span>
</div>
