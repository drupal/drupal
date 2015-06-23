<?php

/**
 * @file
 * Contains \Drupal\Core\Render\AttachmentsInterface.
 */

namespace Drupal\Core\Render;

/**
 * Defines an interface for responses that can expose #attached metadata.
 *
 * @todo If in Drupal 9, we remove attachments other than assets (libraries +
 *   drupalSettings), then we can look into unifying this with
 *   \Drupal\Core\Asset\AttachedAssetsInterface.
 *
 * @see \Drupal\Core\Render\AttachmentsTrait
 */
interface AttachmentsInterface {

  /**
   * Gets attachments.
   *
   * @return array
   *   The attachments.
   */
  public function getAttachments();

  /**
   * Adds attachments.
   *
   * @param array $attachments
   *   The attachments to add.
   *
   * @return $this
   */
  public function addAttachments(array $attachments);

  /**
   * Sets attachments.
   *
   * @param array $attachments
   *   The attachments to set.
   *
   * @return $this
   */
  public function setAttachments(array $attachments);

}
