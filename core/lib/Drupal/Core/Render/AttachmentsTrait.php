<?php

/**
 * @file
 * Contains \Drupal\Core\Render\AttachmentsTrait.
 */

namespace Drupal\Core\Render;

/**
 * Provides an implementation of AttachmentsInterface.
 *
 * @see \Drupal\Core\Render\AttachmentsInterface
 */
trait AttachmentsTrait {

  /**
   * The attachments for this response.
   *
   * @var array
   */
  protected $attachments = [];

  /**
   * {@inheritdoc}
   */
  public function getAttachments() {
    return $this->attachments;
  }

  /**
   * {@inheritdoc}
   */
  public function addAttachments(array $attachments) {
    $this->attachments = BubbleableMetadata::mergeAttachments($this->attachments, $attachments);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAttachments(array $attachments) {
    $this->attachments = $attachments;
    return $this;
  }

}
