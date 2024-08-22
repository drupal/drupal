<?php

namespace Drupal\Core\Render;

/**
 * The attached metadata collection for a renderable element.
 *
 * Libraries, JavaScript settings, feeds, HTML <head> tags, HTML <head> links,
 * HTTP headers, and the HTTP status code are attached to render arrays using
 * the #attached property. The #attached property is an associative array, where
 * the keys are the attachment types and the values are the attached data. For
 * example:
 *
 * @code
 *  $build['#attached']['library'][] = 'core/jquery';
 *  $build['#attached']['http_header'] = [
 *    ['Content-Type', 'application/rss+xml; charset=utf-8'],
 *  ];
 * @endcode
 *
 * The keys used by core are:
 * - drupalSettings: (optional) JavaScript settings.
 * - feed: (optional) RSS feeds.
 * - html_head: (optional) Tags used in HTML <head>.
 * - html_head_link: (optional) The <link> tags in HTML <head>.
 * - http_header: (optional) HTTP headers and status code.
 * - html_response_attachment_placeholders: (optional) Placeholders used in a
 *   response attachment
 * - library: (optional) Asset libraries.
 * - placeholders: (optional) Any placeholders.
 *
 * @todo If in Drupal 9, we remove attachments other than assets (libraries +
 *   drupalSettings), then we can look into unifying this with
 *   \Drupal\Core\Asset\AttachedAssetsInterface.
 *
 * @see \Drupal\Core\Render\AttachmentsTrait
 */
interface AttachmentsInterface {

  /**
   * Gets this object's attached collection.
   *
   * @return array
   *   The attachments array.
   */
  public function getAttachments();

  /**
   * Merges an array of attached data into this object's collection.
   *
   * @param array $attachments
   *   The attachments to add.
   *
   * @return $this
   */
  public function addAttachments(array $attachments);

  /**
   * Replaces this object's attached data with the provided array.
   *
   * @param array $attachments
   *   The attachments to set.
   *
   * @return $this
   */
  public function setAttachments(array $attachments);

}
