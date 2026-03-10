<?php

namespace Drupal\comment\Plugin\Field\FieldType;

/**
 * Interface definition for Comment items.
 */
interface CommentItemInterface {

  /**
   * Comments for this entity are hidden.
   *
   * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0.
   *   Use \Drupal\comment\CommentingStatus::Hidden instead.
   *
   * @see https://www.drupal.org/node/3547362
 */
  const HIDDEN = 0;

  /**
   * Comments for this entity are closed.
   *
   * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0.
   *   Use \Drupal\comment\CommentingStatus::Closed instead.
   *
   * @see https://www.drupal.org/node/3547362
   */
  const CLOSED = 1;

  /**
   * Comments for this entity are open.
   *
   * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0.
   *   Use \Drupal\comment\CommentingStatus::Open instead.
   *
   * @see https://www.drupal.org/node/3547362
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

}
