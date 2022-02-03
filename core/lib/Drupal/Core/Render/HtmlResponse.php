<?php

namespace Drupal\Core\Render;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheableResponseTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Response that contains and can expose cacheability metadata and attachments.
 *
 * Supports Drupal's caching concepts: cache tags for invalidation and cache
 * contexts for variations.
 *
 * Supports Drupal's idea of #attached metadata: libraries, settings,
 * http_header and html_head.
 *
 * @see \Drupal\Core\Cache\CacheableResponse
 * @see \Drupal\Core\Render\AttachmentsInterface
 * @see \Drupal\Core\Render\AttachmentsTrait
 */
class HtmlResponse extends Response implements CacheableResponseInterface, AttachmentsInterface {

  use CacheableResponseTrait;
  use AttachmentsTrait;

  /**
   * Constructs a HtmlResponse object.
   *
   * @param array|string $content
   *   The response content, see setContent().
   * @param int $status
   *   The response status code.
   * @param array $headers
   *   An array of response headers.
   *
   * @throws \InvalidArgumentException
   *   When the HTTP status code is not valid.
   */
  public function __construct($content = '', int $status = 200, array $headers = []) {
    parent::__construct('', $status, $headers);
    $this->setContent($content);
  }

  /**
   * Sets the response content.
   *
   * @param mixed $content
   *   Content that can be cast to string, or a render array.
   *
   * @return $this
   */
  public function setContent($content): static {
    // A render array can automatically be converted to a string and set the
    // necessary metadata.
    if (is_array($content) && (isset($content['#markup']))) {
      $content += [
        '#attached' => [
          'html_response_attachment_placeholders' => [],
          'placeholders' => [],
        ],
      ];
      $this->addCacheableDependency(CacheableMetadata::createFromRenderArray($content));
      $this->setAttachments($content['#attached']);
      $content = $content['#markup'];
    }

    return parent::setContent($content);
  }

}
