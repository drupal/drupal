<?php

/**
 * @file
 * Entity class for comments.
 */

namespace Drupal\comment;

use Drupal\entity\Entity;

/**
 * Defines the comment entity class.
 */
class Comment extends Entity {

  /**
   * The comment ID.
   *
   * @var integer
   */
  public $cid;

  /**
   * The parent comment ID if this is a reply to a comment.
   *
   * @var integer
   */
  public $pid;

  /**
   * The comment language code.
   *
   * @var string
   */
  public $langcode = LANGUAGE_NOT_SPECIFIED;

  /**
   * The comment title.
   *
   * @var string
   */
  public $subject;


  /**
   * The comment author ID.
   *
   * @var integer
   */
  public $uid = 0;

  /**
   * The comment author's name.
   *
   * For anonymous authors, this is the value as typed in the comment form.
   *
   * @var string
   */
  public $name = '';

  /**
   * The comment author's e-mail address.
   *
   * For anonymous authors, this is the value as typed in the comment form.
   *
   * @var string
   */
  public $mail;

  /**
   * The comment author's home page address.
   *
   * For anonymous authors, this is the value as typed in the comment form.
   *
   * @var string
   */
  public $homepage;

  /**
   * Implements Drupal\entity\EntityInterface::id().
   */
  public function id() {
    return $this->cid;
  }

  /**
   * Implements Drupal\entity\EntityInterface::bundle().
   */
  public function bundle() {
    return $this->node_type;
  }
}
