<?php

/**
 * @file
 * Contains \Drupal\Core\Render\HtmlResponse.
 */

namespace Drupal\Core\Render;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheableResponseTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * A response that contains and can expose cacheability metadata and attachments.
 *
 * Supports Drupal's caching concepts: cache tags for invalidation and cache
 * contexts for variations.
 *
 * Supports Drupal's idea of #attached metadata: libraries, settings, http_header and html_head.
 *
 * @see \Drupal\Core\Cache\CacheableResponse
 * @see \Drupal\Core\Render\AttachmentsInterface
 * @see \Drupal\Core\Render\AttachmentsTrait
 */
class HtmlResponse extends Response implements CacheableResponseInterface, AttachmentsInterface {

  use CacheableResponseTrait;
  use AttachmentsTrait;

  /**
   * {@inheritdoc}
   */
  public function setContent($content) {
    // A render array can automatically be converted to a string and set the
    // necessary metadata.
    if (is_array($content) && (isset($content['#markup']))) {
      $content += ['#attached' => [
        'html_response_attachment_placeholders' => [],
        'placeholders' => []],
      ];
      $this->addCacheableDependency(CacheableMetadata::createFromRenderArray($content));
      $this->setAttachments($content['#attached']);
      $content = $content['#markup'];
    }

    return parent::setContent($content);
  }

}
