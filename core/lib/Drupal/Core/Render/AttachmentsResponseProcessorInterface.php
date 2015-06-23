<?php
/**
 * @file
 * Contains \Drupal\Core\Render\AttachmentsResponseProcessorInterface.
 */

namespace Drupal\Core\Render;

/**
 * Defines an interface for processing attachments of responses that have them.
 *
 * @see \Drupal\Core\Render\HtmlResponse
 * @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor
 */
interface AttachmentsResponseProcessorInterface {

  /**
   * Processes the attachments of a response that has attachments.
   *
   * @param \Drupal\Core\Render\AttachmentsInterface $response
   *   The response to process the attachments for.
   *
   * @return \Drupal\Core\Render\AttachmentsInterface
   *   The processed response.
   *
   * @throws \InvalidArgumentException
   */
  public function processAttachments(AttachmentsInterface $response);

}
