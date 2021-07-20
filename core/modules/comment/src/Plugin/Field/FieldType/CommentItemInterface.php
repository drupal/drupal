<?php

namespace Drupal\comment\Plugin\Field\FieldType;

/**
 * Interface definition for Comment items.
 */
interface CommentItemInterface {

  /**
   * Comments for this entity are hidden.
   */
  const HIDDEN = 0;

  /**
   * Comments for this entity are closed.
   */
  const CLOSED = 1;

  /**
   * Comments for this entity are open.
   */
  const OPEN = 2;

  /**
   * Comment form should be displayed on a separate page.
   */
  const FORM_SEPARATE_PAGE = 0;

  /**
   * Comment form should be shown below post or list of comments.
   */
  const FORM_BELOW = 1;

  /**
   * Replying to the deepest comment creates a new comment on the same level.
   */
  const THREAD_DEPTH_REPLY_MODE_ALLOW = 0;

  /**
   * Replying to the deepest comment is not allowed.
   */
  const THREAD_DEPTH_REPLY_MODE_DENY = 1;

}
