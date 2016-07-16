<?php

namespace Drupal\Core\Render;

/**
 * Defines an interface for processing attachments of responses that have them.
 *
 * @see \Drupal\Core\Ajax\AjaxResponse
 * @see \Drupal\Core\Ajax\AjaxResponseAttachmentsProcessor
 * @see \Drupal\Core\Render\HtmlResponse
 * @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor
 */
interface AttachmentsResponseProcessorInterface {

  /**
   * Processes the attachments of a response that has attachments.
   *
   * Libraries, JavaScript settings, feeds, HTML <head> tags, HTML <head> links,
   * HTTP headers, and the HTTP status code are attached to render arrays using
   * the #attached property. The #attached property is an associative array,
   * where the keys are the attachment types and the values are the attached
   * data. For example:
   *
   * @code
   * $build['#attached']['library'][] = [
   *   'library' => ['core/jquery']
   * ];
   * $build['#attached']['http_header'] = [
   *   ['Content-Type', 'application/rss+xml; charset=utf-8'],
   * ];
   * @endcode
   *
   * The available keys are:
   * - 'library' (asset libraries)
   * - 'drupalSettings' (JavaScript settings)
   * - 'feed' (RSS feeds)
   * - 'html_head' (tags in HTML <head>)
   * - 'html_head_link' (<link> tags in HTML <head>)
   * - 'http_header' (HTTP headers and status code)
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
   */
  public function processAttachments(AttachmentsInterface $response);

}
