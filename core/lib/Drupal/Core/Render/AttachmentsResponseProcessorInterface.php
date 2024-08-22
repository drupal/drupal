<?php

namespace Drupal\Core\Render;

/**
 * Defines an interface for processing attachments of responses that have them.
 *
 * @see \Drupal\Core\Ajax\AjaxResponse
 * @see \Drupal\Core\Ajax\AjaxResponseAttachmentsProcessor
 * @see \Drupal\Core\Render\AttachmentsInterface
 * @see \Drupal\Core\Render\HtmlResponse
 * @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor
 */
interface AttachmentsResponseProcessorInterface {

  /**
   * Processes the attachments of a response that has attachments.
   *
   * Placeholders need to be rendered first in order to have all attachments
   * available for processing. For an example, see
   * \Drupal\Core\Render\HtmlResponseAttachmentsProcessor::renderPlaceholders()
   *
   * @param \Drupal\Core\Render\AttachmentsInterface $response
   *   The response to process.
   *
   * @return \Drupal\Core\Render\AttachmentsInterface
   *   The processed response, with the attachments updated to reflect their
   *   final values.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the $response parameter is not the type of response object
   *   the processor expects.
   *
   * @see \Drupal\Core\Render\AttachmentsInterface
   */
  public function processAttachments(AttachmentsInterface $response);

}
