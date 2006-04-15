<div class="comment<?php if ($comment->status == COMMENT_NOT_PUBLISHED) print ' comment-unpublished'; ?>">
  <?php if ($picture) : ?>
    <?php print $picture ?>
  <?php endif; ?>
  <h3 class="title"><?php print $title ?></h3>
  <div class="submitted"><?php print $submitted ?><?php if ($comment->new) : ?><span class="new"> *<?php print $new ?></span><?php endif; ?></div>
  <div class="content"><?php print $content ?></div>
  <!-- BEGIN: links -->
  <div class="links">&raquo; <?php print $links ?></div>
  <!-- END: links -->
</div>
